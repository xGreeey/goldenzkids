(function () {
    'use strict';

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatBodyHtml(text) {
        return escapeHtml(text).replace(/\n/g, '<br>');
    }

    function scrollThread() {
        var el = document.getElementById('messagingThreadScroll');
        if (el) {
            el.scrollTop = el.scrollHeight;
        }
    }

    function notify(opts) {
        if (window.appNotify && typeof window.appNotify.open === 'function') {
            window.appNotify.open(opts);
        } else if (opts && opts.message) {
            window.alert(opts.message);
        }
    }

    function confirmAction(opts) {
        if (window.appNotify && typeof window.appNotify.confirm === 'function') {
            window.appNotify.confirm(opts);
            return;
        }
        if (opts && opts.message && window.confirm(opts.message)) {
            if (typeof opts.onConfirm === 'function') {
                opts.onConfirm();
            }
        }
    }

    function renderMessages(messages, isGroup) {
        if (!messages || messages.length === 0) {
            return '<p class="messaging-board__placeholder">No messages yet. Send the first message below.</p>';
        }

        return messages
            .map(function (msg) {
                var mine = msg.is_mine ? ' messaging-bubble--mine' : ' messaging-bubble--theirs';
                var sender =
                    isGroup && !msg.is_mine && msg.sender_label
                        ? '<span class="messaging-bubble__sender">' + escapeHtml(msg.sender_label) + '</span>'
                        : '';
                return (
                    '<div class="messaging-bubble' +
                    mine +
                    '">' +
                    sender +
                    '<p class="messaging-bubble__text">' +
                    formatBodyHtml(msg.body_text) +
                    '</p>' +
                    '<time class="messaging-bubble__time" datetime="' +
                    escapeHtml(msg.created_at || '') +
                    '">' +
                    escapeHtml(msg.time_label || '') +
                    '</time></div>'
                );
            })
            .join('');
    }

    function renderCompose(data, csrf, mode) {
        if (mode === 'group') {
            return (
                '<form class="messaging-compose js-messaging-compose" data-mode="group">' +
                '<input type="hidden" name="_csrf" value="' +
                escapeHtml(csrf) +
                '">' +
                '<input type="hidden" name="group_id" value="' +
                escapeHtml(String(data.group_id)) +
                '">' +
                '<label class="visually-hidden" for="messagingGroupBody">Group message</label>' +
                '<div class="messaging-compose__field">' +
                '<textarea name="body" id="messagingGroupBody" class="messaging-compose__input" rows="2" maxlength="4000" required placeholder="Message the group…"></textarea>' +
                '<button type="submit" class="messaging-compose__submit" aria-label="Send group message">' +
                '<svg class="messaging-compose__submit-icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M2.01 21 23 12 2.01 3 2 10l15 2-15 2z"/></svg>' +
                '</button></div></form>'
            );
        }

        return (
            '<form class="messaging-compose js-messaging-compose" data-mode="direct">' +
            '<input type="hidden" name="_csrf" value="' +
            escapeHtml(csrf) +
            '">' +
            '<input type="hidden" name="recipient_id" value="' +
            escapeHtml(data.recipient_id) +
            '">' +
            '<input type="hidden" name="return_peer" value="' +
            escapeHtml(data.return_peer || data.recipient_id) +
            '">' +
            '<label class="visually-hidden" for="messagingBody">Message</label>' +
            '<div class="messaging-compose__field">' +
            '<textarea name="body" id="messagingBody" class="messaging-compose__input" rows="2" maxlength="4000" required placeholder="Type your message…"></textarea>' +
            '<button type="submit" class="messaging-compose__submit" aria-label="Send message">' +
            '<svg class="messaging-compose__submit-icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M2.01 21 23 12 2.01 3 2 10l15 2-15 2z"/></svg>' +
            '</button></div></form>'
        );
    }

    function renderThreadActions(actions, mode) {
        if (!actions) {
            return '';
        }

        var buttons = [];
        if (mode === 'direct' && actions.clear_history) {
            buttons.push(
                '<button type="button" class="messaging-thread__action messaging-thread__action--danger" data-thread-action="clear_direct">Delete chat</button>'
            );
        }
        if (mode === 'group') {
            if (actions.clear_history) {
                buttons.push(
                    '<button type="button" class="messaging-thread__action" data-thread-action="clear_group">Clear messages</button>'
                );
            }
            if (actions.leave_group) {
                buttons.push(
                    '<button type="button" class="messaging-thread__action" data-thread-action="leave_group">Leave group</button>'
                );
            }
            if (actions.delete_group) {
                buttons.push(
                    '<button type="button" class="messaging-thread__action messaging-thread__action--danger" data-thread-action="delete_group">Delete group</button>'
                );
            }
        }

        if (buttons.length === 0) {
            return '';
        }

        return '<div class="messaging-thread__actions" role="toolbar" aria-label="Conversation actions">' + buttons.join('') + '</div>';
    }

    function renderThread(payload, csrf) {
        var isGroup = payload.mode === 'group';
        var html =
            '<div class="messaging-thread__header">' +
            '<div class="messaging-thread__header-main">' +
            '<strong>' +
            escapeHtml(payload.title) +
            '</strong>' +
            '<span class="messaging-thread__meta">' +
            escapeHtml(payload.meta) +
            '</span></div>' +
            renderThreadActions(payload.actions, payload.mode) +
            '</div>' +
            '<div class="messaging-thread__messages" id="messagingThreadScroll" tabindex="0" aria-live="polite">' +
            renderMessages(payload.messages, isGroup) +
            '</div>' +
            renderCompose(payload.compose, csrf, payload.mode);

        return html;
    }

    function initMessagingBoard() {
        var board = document.getElementById('messaging-board');
        var pane = document.getElementById('messagingThreadPane');
        if (!board || !pane) {
            return;
        }

        if (board.dataset.messagingBound === '1') {
            return;
        }
        board.dataset.messagingBound = '1';

        var threadApi = board.dataset.threadApi || 'messaging-thread.php';
        var actionUrl = board.dataset.actionUrl || 'messaging-action.php';
        var sendDirect = board.dataset.sendDirect || '';
        var sendGroup = board.dataset.sendGroup || '';
        var baseUrl = board.dataset.baseUrl || 'inbox.php';
        var csrf = board.dataset.csrf || '';
        var currentThreadQuery = null;
        var createPanelTemplate = document.getElementById('messagingCreateGroupTemplate');
        var idleTemplate = document.getElementById('messagingIdleTemplate');
        var loading = false;

        function unreadBannerHtml(count) {
            if (count === 1) {
                return 'You have <strong>1 new message</strong> — open a conversation below.';
            }
            if (count > 1) {
                return 'You have <strong>' + count + ' new messages</strong> — open a conversation below.';
            }
            return '';
        }

        function updateUnreadUi(total) {
            total = Math.max(0, parseInt(String(total), 10) || 0);
            board.dataset.unreadTotal = String(total);

            var banner = document.getElementById('messagingUnreadBanner');
            var textEl = banner && banner.querySelector('[data-messaging-unread-banner-text]');
            if (banner) {
                if (total > 0) {
                    banner.hidden = false;
                    banner.classList.remove('is-hidden');
                    if (textEl) {
                        textEl.innerHTML = unreadBannerHtml(total);
                    }
                } else {
                    banner.hidden = true;
                    banner.classList.add('is-hidden');
                }
            }

            document.querySelectorAll('[data-guard-inbox-badge]').forEach(function (badge) {
                if (total > 0) {
                    badge.textContent = String(total);
                    badge.setAttribute('aria-label', total + ' unread messages');
                    badge.hidden = false;
                } else {
                    badge.remove();
                }
            });

            document.querySelectorAll('[data-guard-inbox-nav]').forEach(function (link) {
                if (total > 0) {
                    var existing = link.querySelector('[data-guard-inbox-badge]');
                    if (!existing) {
                        var span = document.createElement('span');
                        span.className = link.classList.contains('guard-app__drawer-link')
                            ? 'guard-app__drawer-link__badge'
                            : 'sidebar-link__badge';
                        span.setAttribute('data-guard-inbox-badge', '');
                        span.setAttribute('aria-label', total + ' unread messages');
                        span.textContent = String(total);
                        link.appendChild(span);
                    }
                }
            });

            var titleBadge = document.querySelector('.page-title__badge');
            if (titleBadge) {
                if (total > 0) {
                    titleBadge.textContent = String(total);
                    titleBadge.setAttribute('aria-label', total + ' new messages');
                    titleBadge.hidden = false;
                } else {
                    titleBadge.remove();
                }
            }

            var hint = document.querySelector('.inbox-new-messages-hint');
            if (hint) {
                if (total > 0) {
                    hint.innerHTML =
                        '<i class="fa-solid fa-circle" aria-hidden="true"></i> ' +
                        (total === 1 ? '1 new message waiting' : total + ' new messages waiting');
                    hint.hidden = false;
                } else {
                    hint.remove();
                }
            }
        }

        function markContactRead(btn) {
            if (!btn) {
                return;
            }
            var unread = parseInt(btn.getAttribute('data-unread') || '0', 10) || 0;
            if (unread <= 0) {
                return;
            }
            btn.setAttribute('data-unread', '0');
            btn.classList.remove('has-unread');
            var dot = btn.querySelector('.messaging-contact__dot');
            if (dot) {
                dot.remove();
            }
            var badge = btn.querySelector('.messaging-contact__badge');
            if (badge) {
                badge.remove();
            }
            var total = parseInt(board.dataset.unreadTotal || '0', 10) || 0;
            updateUnreadUi(Math.max(0, total - unread));
        }

        function resortContactLists() {
            board.querySelectorAll('.messaging-contact-list').forEach(function (list) {
                var items = Array.prototype.slice.call(list.children);
                items.sort(function (a, b) {
                    var btnA = a.querySelector('.messaging-contact');
                    var btnB = b.querySelector('.messaging-contact');
                    var unreadA = parseInt((btnA && btnA.getAttribute('data-unread')) || '0', 10) || 0;
                    var unreadB = parseInt((btnB && btnB.getAttribute('data-unread')) || '0', 10) || 0;
                    return unreadB - unreadA;
                });
                items.forEach(function (li) {
                    list.appendChild(li);
                });
            });
        }

        function setActiveContact(btn) {
            board.querySelectorAll('.messaging-contact').forEach(function (el) {
                el.classList.remove('is-active');
                el.removeAttribute('aria-current');
            });
            if (btn) {
                btn.classList.add('is-active');
                btn.setAttribute('aria-current', 'true');
            }
        }

        function updateUrl(params) {
            var url = new URL(baseUrl, window.location.href);
            url.searchParams.delete('peer');
            url.searchParams.delete('group');
            url.searchParams.delete('create_group');
            if (params.peer) {
                url.searchParams.set('peer', params.peer);
            }
            if (params.group) {
                url.searchParams.set('group', String(params.group));
            }
            if (params.create) {
                url.searchParams.set('create_group', '1');
            }
            url.hash = 'messaging-board';
            history.replaceState(null, '', url.pathname + url.search + url.hash);
        }

        function showIdle() {
            setActiveContact(null);
            currentThreadQuery = null;
            pane.dataset.threadMode = 'idle';
            if (idleTemplate) {
                pane.innerHTML = idleTemplate.innerHTML;
            }
        }

        function removeGroupFromSidebar(groupId) {
            var btn = board.querySelector('[data-chat-type="group"][data-group-id="' + groupId + '"]');
            if (btn && btn.parentElement) {
                btn.parentElement.remove();
            }
            var section = board.querySelector('.messaging-board__section--groups');
            if (!section) {
                return;
            }
            var list = section.querySelector('.messaging-contact-list');
            if (list && list.children.length === 0) {
                var empty = document.createElement('p');
                empty.className = 'messaging-board__empty';
                empty.innerHTML =
                    'No group chats yet. Use <strong>Create group chat</strong> to start one.';
                section.appendChild(empty);
            }
        }

        function confirmLabels(action) {
            switch (action) {
                case 'clear_direct':
                    return {
                        title: 'Delete chat?',
                        message: 'This removes all direct messages between you and this contact. This cannot be undone.',
                        confirmLabel: 'Delete chat',
                    };
                case 'clear_group':
                    return {
                        title: 'Clear group messages?',
                        message: 'All messages in this group will be removed for everyone. Members stay in the group.',
                        confirmLabel: 'Clear messages',
                    };
                case 'leave_group':
                    return {
                        title: 'Leave group?',
                        message: 'You will no longer see this group or receive new messages.',
                        confirmLabel: 'Leave group',
                    };
                case 'delete_group':
                    return {
                        title: 'Delete group?',
                        message: 'The group and all messages will be removed for everyone. This cannot be undone.',
                        confirmLabel: 'Delete group',
                    };
                default:
                    return { title: 'Confirm', message: 'Continue?', confirmLabel: 'Confirm' };
            }
        }

        function runThreadAction(action) {
            if (!currentThreadQuery) {
                return;
            }

            var labels = confirmLabels(action);
            confirmAction({
                type: 'warning',
                title: labels.title,
                message: labels.message,
                confirmLabel: labels.confirmLabel,
                onConfirm: function () {
                    var body = new FormData();
                    body.append('_csrf', csrf);
                    body.append('action', action);
                    if (currentThreadQuery.peer) {
                        body.append('peer_id', currentThreadQuery.peer);
                    }
                    if (currentThreadQuery.group) {
                        body.append('group_id', String(currentThreadQuery.group));
                    }

                    fetch(actionUrl, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                        body: body,
                    })
                        .then(function (res) {
                            return res.json();
                        })
                        .then(function (data) {
                            if (!data.ok) {
                                notify({
                                    type: 'error',
                                    title: data.title || 'Action failed',
                                    message: data.message || data.error || 'Please try again.',
                                });
                                return;
                            }

                            notify({
                                type: data.type || 'success',
                                title: data.title || 'Done',
                                message: data.message || '',
                                onClose: function () {
                                    if (data.redirect_idle) {
                                        if (action === 'leave_group' || action === 'delete_group') {
                                            removeGroupFromSidebar(String(data.group_id || currentThreadQuery.group));
                                        }
                                        updateUrl({});
                                        showIdle();
                                        return;
                                    }
                                    if (data.reload_thread) {
                                        loadThread(currentThreadQuery);
                                    }
                                },
                            });
                        })
                        .catch(function () {
                            notify({
                                type: 'error',
                                title: 'Action failed',
                                message: 'Something went wrong. Please try again.',
                            });
                        });
                },
            });
        }

        function showCreatePanel() {
            setActiveContact(null);
            updateUrl({ create: true });
            pane.dataset.threadMode = 'create';
            if (!createPanelTemplate) {
                return;
            }
            pane.innerHTML = '';
            if (createPanelTemplate.content) {
                pane.appendChild(createPanelTemplate.content.cloneNode(true));
            } else {
                pane.innerHTML = createPanelTemplate.innerHTML;
            }
            var nameInput = pane.querySelector('#groupNameInput');
            if (nameInput) {
                nameInput.focus();
            }
            bindCreateGroupForm(pane.querySelector('.js-messaging-create-group-form'));
        }

        function ensureGroupList() {
            var section = board.querySelector('.messaging-board__section--groups');
            if (!section) {
                return null;
            }
            var list = section.querySelector('.messaging-contact-list');
            if (list) {
                return list;
            }
            var empty = section.querySelector('.messaging-board__empty');
            if (empty) {
                empty.remove();
            }
            list = document.createElement('ul');
            list.className = 'messaging-contact-list';
            var createBtn = section.querySelector('.messaging-board__create-group-btn');
            if (createBtn && createBtn.nextSibling) {
                section.insertBefore(list, createBtn.nextSibling);
            } else {
                section.appendChild(list);
            }
            return list;
        }

        function addGroupToSidebar(data) {
            var list = ensureGroupList();
            if (!list || !data.group_id) {
                return;
            }
            if (list.querySelector('[data-group-id="' + data.group_id + '"]')) {
                return;
            }
            var li = document.createElement('li');
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'messaging-contact messaging-contact--group';
            btn.setAttribute('data-chat-type', 'group');
            btn.setAttribute('data-group-id', String(data.group_id));
            btn.setAttribute('data-chat-label', data.group_name || 'Group');
            btn.innerHTML =
                '<span class="messaging-contact__label"><i class="fa-solid fa-users" aria-hidden="true"></i> ' +
                escapeHtml(data.group_name || 'Group') +
                '</span><span class="messaging-contact__id">' +
                escapeHtml(String(data.member_count || '')) +
                ' members</span>';
            li.appendChild(btn);
            list.appendChild(li);
        }

        function bindCreateGroupForm(form) {
            if (!form || form.getAttribute('data-create-bound') === '1') {
                return;
            }
            form.setAttribute('data-create-bound', '1');
            var createUrl = board.dataset.createGroupUrl || 'create-message-group.php';

            form.addEventListener('submit', function (event) {
                event.preventDefault();
                var submitBtn = form.querySelector('.messaging-create-group__submit');
                if (submitBtn) {
                    submitBtn.disabled = true;
                }

                fetch(createUrl, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    body: new FormData(form),
                })
                    .then(function (res) {
                        return res.json();
                    })
                    .then(function (data) {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                        }
                        if (!data.ok) {
                            notify({
                                type: 'error',
                                title: 'Could not create group',
                                message: data.error || 'Please try again.',
                            });
                            return;
                        }
                        notify({
                            type: data.type || 'success',
                            title: data.title || 'Success',
                            message: data.message || 'Group chat created.',
                            onClose: function () {
                                addGroupToSidebar(data);
                                var groupBtn = board.querySelector(
                                    '[data-chat-type="group"][data-group-id="' + data.group_id + '"]'
                                );
                                setActiveContact(groupBtn);
                                updateUrl({ group: data.group_id });
                                loadThread({ group: data.group_id });
                            },
                        });
                    })
                    .catch(function () {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                        }
                        notify({
                            type: 'error',
                            title: 'Could not create group',
                            message: 'Something went wrong. Please try again.',
                        });
                    });
            });
        }

        function showLoading() {
            pane.innerHTML =
                '<p class="messaging-board__placeholder messaging-board__loading"><i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Loading conversation…</p>';
        }

        function loadThread(query) {
            if (loading) {
                return;
            }
            loading = true;
            currentThreadQuery = query;
            pane.dataset.threadMode = query.group ? 'group' : 'direct';
            showLoading();

            var url = threadApi + '?' + new URLSearchParams(query).toString();

            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
                credentials: 'same-origin',
            })
                .then(function (res) {
                    return res.json();
                })
                .then(function (data) {
                    loading = false;
                    if (!data.ok) {
                        pane.innerHTML =
                            '<p class="messaging-board__notice" role="alert">' +
                            escapeHtml(data.error || 'Could not load conversation.') +
                            '</p>';
                        return;
                    }
                    if (data.csrf) {
                        csrf = data.csrf;
                        board.dataset.csrf = csrf;
                    }
                    pane.innerHTML = renderThread(data, csrf);
                    scrollThread();
                    bindCompose(pane.querySelector('.js-messaging-compose'), data.mode);
                })
                .catch(function () {
                    loading = false;
                    pane.innerHTML =
                        '<p class="messaging-board__notice" role="alert">Could not load conversation. Please try again.</p>';
                });
        }

        function bindCompose(form, mode) {
            if (!form) {
                return;
            }
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                var bodyInput = form.querySelector('textarea[name="body"]');
                var body = bodyInput ? bodyInput.value.trim() : '';
                if (body === '') {
                    return;
                }

                var action = mode === 'group' ? sendGroup : sendDirect;
                if (!action) {
                    return;
                }

                var submitBtn = form.querySelector('.messaging-compose__submit');
                if (submitBtn) {
                    submitBtn.disabled = true;
                }

                fetch(action, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    body: new FormData(form),
                })
                    .then(function (res) {
                        return res.json();
                    })
                    .then(function (data) {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                        }
                        if (!data.ok) {
                            notify({
                                type: 'error',
                                title: 'Message not sent',
                                message: data.error || 'Message could not be sent.',
                            });
                            return;
                        }
                        if (data.csrf) {
                            csrf = data.csrf;
                            board.dataset.csrf = csrf;
                            var csrfInput = form.querySelector('input[name="_csrf"]');
                            if (csrfInput) {
                                csrfInput.value = csrf;
                            }
                        }
                        bodyInput.value = '';
                        var scroll = document.getElementById('messagingThreadScroll');
                        var placeholder = scroll && scroll.querySelector('.messaging-board__placeholder');
                        if (placeholder) {
                            placeholder.remove();
                        }
                        if (scroll && data.message) {
                            var bubble = document.createElement('div');
                            bubble.className = 'messaging-bubble messaging-bubble--mine';
                            bubble.innerHTML =
                                '<p class="messaging-bubble__text">' +
                                formatBodyHtml(data.message.body_text) +
                                '</p><time class="messaging-bubble__time" datetime="' +
                                escapeHtml(data.message.created_at || '') +
                                '">' +
                                escapeHtml(data.message.time_label || '') +
                                '</time>';
                            scroll.appendChild(bubble);
                            scrollThread();
                        }
                    })
                    .catch(function () {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                        }
                        notify({
                            type: 'error',
                            title: 'Message not sent',
                            message: 'Message could not be sent. Please try again.',
                        });
                    });
            });
        }

        pane.addEventListener('click', function (event) {
            var actionBtn = event.target.closest('[data-thread-action]');
            if (actionBtn) {
                event.preventDefault();
                runThreadAction(actionBtn.getAttribute('data-thread-action'));
            }
        });

        board.addEventListener('click', function (event) {
            var chatBtn = event.target.closest('[data-chat-type]');
            if (chatBtn) {
                event.preventDefault();
                var type = chatBtn.getAttribute('data-chat-type');
                setActiveContact(chatBtn);
                if (type === 'direct') {
                    var peer = chatBtn.getAttribute('data-peer-id');
                    if (peer) {
                        markContactRead(chatBtn);
                        updateUrl({ peer: peer });
                        loadThread({ peer: peer });
                    }
                } else if (type === 'group') {
                    var groupId = chatBtn.getAttribute('data-group-id');
                    if (groupId) {
                        markContactRead(chatBtn);
                        updateUrl({ group: groupId });
                        loadThread({ group: groupId });
                    }
                }
                return;
            }

            var createBtn = event.target.closest('[data-messaging-action="create-group"]');
            if (createBtn) {
                event.preventDefault();
                showCreatePanel();
            }
        });

        var initialPeer = board.dataset.initialPeer || '';
        var initialGroup = board.dataset.initialGroup || '';
        var initialCreate = board.dataset.initialCreate === '1';

        if (initialCreate) {
            if (pane.dataset.threadMode === 'create' && pane.querySelector('.messaging-create-group__form, .messaging-create-panel')) {
                setActiveContact(null);
                bindCreateGroupForm(pane.querySelector('.js-messaging-create-group-form'));
            } else {
                showCreatePanel();
            }
        } else if (initialGroup) {
            var groupBtnInit = board.querySelector('[data-chat-type="group"][data-group-id="' + initialGroup + '"]');
            if (groupBtnInit) {
                setActiveContact(groupBtnInit);
            }
            loadThread({ group: initialGroup });
        } else if (initialPeer) {
            var peerBtnInit = Array.from(board.querySelectorAll('[data-chat-type="direct"]')).find(function (el) {
                return el.getAttribute('data-peer-id') === initialPeer;
            });
            if (peerBtnInit) {
                setActiveContact(peerBtnInit);
            }
            loadThread({ peer: initialPeer });
        } else if (pane.querySelector('.js-messaging-compose')) {
            bindCompose(pane.querySelector('.js-messaging-compose'), pane.dataset.threadMode || 'direct');
            scrollThread();
        }

        resortContactLists();
        updateUnreadUi(parseInt(board.dataset.unreadTotal || '0', 10) || 0);
    }

    document.addEventListener('DOMContentLoaded', initMessagingBoard);
    window.initMessagingBoard = initMessagingBoard;
})();
