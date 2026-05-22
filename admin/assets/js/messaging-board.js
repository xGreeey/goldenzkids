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

    function avatarInitials(label) {
        var text = String(label || '').trim();
        if (!text) {
            return '?';
        }
        var email = text.match(/^([^@]+)@/);
        if (email && email[1]) {
            var local = email[1].replace(/[^a-zA-Z0-9]/g, '');
            if (local) {
                return local.substring(0, 2).toUpperCase();
            }
        }
        var parts = text.split(/\s+/).filter(Boolean);
        if (parts.length >= 2) {
            return (parts[0].charAt(0) + parts[1].charAt(0)).toUpperCase();
        }
        return text.replace(/\s+/g, '').substring(0, 2).toUpperCase();
    }

    function avatarTone(seed) {
        var tones = ['navy', 'slate', 'blue', 'steel'];
        var hash = 0;
        var s = String(seed || '');
        for (var i = 0; i < s.length; i++) {
            hash = (hash + s.charCodeAt(i)) | 0;
        }
        return 'messaging-avatar--' + tones[Math.abs(hash) % tones.length];
    }

    var paperclipSvg =
        '<svg class="messaging-compose__icon-svg messaging-compose__icon-svg--attach" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">' +
        '<path d="M21.44 11.05l-8.54 8.54a5 5 0 1 1-7.07-7.07l8.54-8.54a3.5 3.5 0 1 1 4.95 4.95l-9.19 9.19a2.5 2.5 0 1 1-3.54-3.54l8.28-8.28" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>';

    var sendSvg =
        '<svg class="messaging-compose__icon-svg messaging-compose__icon-svg--send" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">' +
        '<path d="M22 2 11 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>' +
        '<path d="M22 2 15 22 11 13 2 9 22 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';

    function avatarHtml(label, seed, size, extraClass) {
        size = size || 'sm';
        extraClass = extraClass || '';
        return (
            '<span class="messaging-avatar messaging-avatar--' +
            size +
            ' ' +
            avatarTone(seed) +
            ' ' +
            extraClass +
            '" aria-hidden="true">' +
            escapeHtml(avatarInitials(label)) +
            '</span>'
        );
    }

    function scrollThread(force) {
        var el = document.getElementById('messagingThreadScroll');
        if (!el) {
            return;
        }
        if (force === true || isNearThreadBottom(el)) {
            el.scrollTop = el.scrollHeight;
        }
    }

    function isNearThreadBottom(el, threshold) {
        if (!el) {
            return true;
        }
        threshold = typeof threshold === 'number' ? threshold : 96;
        return el.scrollHeight - el.scrollTop - el.clientHeight <= threshold;
    }

    function getLastMessageId() {
        var scroll = document.getElementById('messagingThreadScroll');
        if (!scroll) {
            return 0;
        }
        var max = 0;
        scroll.querySelectorAll('[data-message-id]').forEach(function (bubble) {
            var id = parseInt(bubble.getAttribute('data-message-id') || '0', 10) || 0;
            if (id > max) {
                max = id;
            }
        });
        return max;
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

    function renderMessageBubble(msg, isGroup, animate) {
        var mine = msg.is_mine ? ' messaging-bubble--mine' : ' messaging-bubble--theirs';
        var messageId = parseInt(String(msg.message_id || '0'), 10) || 0;
        var incoming = animate && !msg.is_mine ? ' messaging-bubble--incoming' : '';
        var sender =
            isGroup && !msg.is_mine && msg.sender_label
                ? '<span class="messaging-bubble__sender">' + escapeHtml(msg.sender_label) + '</span>'
                : '';
        return (
            '<div class="messaging-bubble' +
            mine +
            incoming +
            '"' +
            (messageId > 0 ? ' data-message-id="' + messageId + '"' : '') +
            '>' +
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
    }

    function renderMessages(messages, isGroup) {
        if (!messages || messages.length === 0) {
            return '<p class="messaging-board__placeholder">No messages yet. Send the first message below.</p>';
        }

        return messages.map(function (msg) {
            return renderMessageBubble(msg, isGroup, false);
        }).join('');
    }

    function renderCompose(data, csrf, mode) {
        var hidden =
            mode === 'group'
                ? '<input type="hidden" name="group_id" value="' + escapeHtml(String(data.group_id)) + '">'
                : '<input type="hidden" name="recipient_id" value="' +
                  escapeHtml(data.recipient_id) +
                  '"><input type="hidden" name="return_peer" value="' +
                  escapeHtml(data.return_peer || data.recipient_id) +
                  '">';
        var textareaId = mode === 'group' ? 'messagingGroupBody' : 'messagingBody';

        var attachInputId = mode === 'group' ? 'messagingAttachGroup' : 'messagingAttachDirect';

        return (
            '<form class="messaging-compose js-messaging-compose" data-mode="' +
            escapeHtml(mode) +
            '">' +
            '<input type="hidden" name="_csrf" value="' +
            escapeHtml(csrf) +
            '">' +
            hidden +
            '<label class="visually-hidden" for="' +
            textareaId +
            '">Message</label>' +
            '<div class="messaging-compose__bar">' +
            '<textarea name="body" id="' +
            textareaId +
            '" class="messaging-compose__input" rows="1" maxlength="4000" placeholder="Type a message…"></textarea>' +
            '<div class="messaging-compose__actions">' +
            '<label class="messaging-compose__attach" for="' +
            attachInputId +
            '" title="Attach photo or PDF">' +
            '<input type="file" name="attachment" id="' +
            attachInputId +
            '" class="messaging-compose__file-input" accept="image/jpeg,image/png,image/gif,image/webp,application/pdf">' +
            '<span class="messaging-compose__attach-icon" aria-hidden="true">' +
            paperclipSvg +
            '</span>' +
            '<span class="visually-hidden">Attach photo or PDF</span></label>' +
            '<button type="submit" class="messaging-compose__send" aria-label="Send message">' +
            '<span class="messaging-compose__send-label">Send</span>' +
            sendSvg +
            '</button></div></div>' +
            '<p class="messaging-compose__file-chip js-messaging-file-chip" hidden></p></form>'
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
        var seed = isGroup ? 'group-' + String(payload.compose && payload.compose.group_id ? payload.compose.group_id : payload.title) : payload.meta;
        var groupIcon = isGroup ? '<i class="fa-solid fa-users messaging-thread__header-icon" aria-hidden="true"></i>' : '';
        var html =
            '<div class="messaging-thread__header">' +
            '<div class="messaging-thread__header-profile">' +
            avatarHtml(payload.title, seed, 'lg', isGroup ? 'messaging-avatar--group' : '') +
            '<div class="messaging-thread__header-info">' +
            '<strong class="messaging-thread__name">' +
            escapeHtml(payload.title) +
            '</strong>' +
            '<span class="messaging-thread__status">' +
            groupIcon +
            escapeHtml(payload.meta) +
            '</span></div></div>' +
            renderThreadActions(payload.actions, payload.mode) +
            '</div>' +
            '<div class="messaging-thread__messages" id="messagingThreadScroll" tabindex="0" aria-live="off">' +
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

        if (document.body.classList.contains('guard-portal') && document.querySelector('.guard-inbox-page')) {
            document.body.classList.add('guard-page-inbox');
            var guardScroll = document.querySelector('.guard-app__scroll');
            if (guardScroll) {
                guardScroll.style.overflow = 'hidden';
            }
        }

        var threadApi = board.dataset.threadApi || 'messaging-thread.php';
        var pollApi = board.dataset.pollApi || 'messaging-poll.php';
        var pollMs = Math.max(800, parseInt(board.dataset.pollMs || '1000', 10) || 1000);
        var pollFastMs = Math.max(400, Math.floor(pollMs * 0.5));
        var actionUrl = board.dataset.actionUrl || 'messaging-action.php';
        var sendDirect = board.dataset.sendDirect || '';
        var sendGroup = board.dataset.sendGroup || '';
        var baseUrl = board.dataset.baseUrl || 'inbox.php';
        var csrf = board.dataset.csrf || '';
        var currentThreadQuery = null;
        var createPanelTemplate = document.getElementById('messagingCreateGroupTemplate');
        var idleTemplate = document.getElementById('messagingIdleTemplate');
        var loading = false;
        var polling = false;
        var pollTimer = null;

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

            var sidebarBadge = board.querySelector('.messaging-board__sidebar-badge');
            if (total > 0) {
                if (!sidebarBadge) {
                    var title = board.querySelector('.messaging-board__sidebar-title');
                    if (title) {
                        sidebarBadge = document.createElement('span');
                        sidebarBadge.className = 'messaging-board__sidebar-badge';
                        title.appendChild(sidebarBadge);
                    }
                }
                if (sidebarBadge) {
                    sidebarBadge.textContent = String(total);
                    sidebarBadge.setAttribute('aria-label', total + ' unread');
                    sidebarBadge.hidden = false;
                }
            } else if (sidebarBadge) {
                sidebarBadge.remove();
            }

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

        function updateContactUnreadBadge(btn, unread) {
            if (!btn) {
                return;
            }
            unread = Math.max(0, parseInt(String(unread), 10) || 0);
            btn.setAttribute('data-unread', String(unread));
            if (unread > 0) {
                btn.classList.add('has-unread');
                var row = btn.querySelector('.messaging-contact__row');
                if (row && !btn.querySelector('.messaging-contact__dot')) {
                    var dot = document.createElement('span');
                    dot.className = 'messaging-contact__dot';
                    dot.setAttribute('aria-hidden', 'true');
                    row.appendChild(dot);
                }
                var badge = btn.querySelector('.messaging-contact__badge');
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'messaging-contact__badge';
                    var body = btn.querySelector('.messaging-contact__body');
                    if (body) {
                        body.appendChild(badge);
                    }
                }
                if (badge) {
                    badge.textContent = String(unread);
                    badge.setAttribute('aria-label', unread + ' unread');
                }
            } else {
                btn.classList.remove('has-unread');
                var dotEl = btn.querySelector('.messaging-contact__dot');
                if (dotEl) {
                    dotEl.remove();
                }
                var badgeEl = btn.querySelector('.messaging-contact__badge');
                if (badgeEl) {
                    badgeEl.remove();
                }
            }
        }

        function syncPollUnread(contacts, groups) {
            (contacts || []).forEach(function (item) {
                var btn = board.querySelector(
                    '[data-chat-type="direct"][data-peer-id="' + CSS.escape(item.company_id) + '"]'
                );
                if (btn) {
                    updateContactUnreadBadge(btn, item.unread);
                }
            });
            (groups || []).forEach(function (item) {
                var btn = board.querySelector(
                    '[data-chat-type="group"][data-group-id="' + String(item.group_id) + '"]'
                );
                if (btn) {
                    updateContactUnreadBadge(btn, item.unread);
                }
            });
            resortContactLists();
        }

        function appendIncomingMessages(messages, isGroup, options) {
            if (!messages || messages.length === 0) {
                return false;
            }
            if (typeof options === 'boolean') {
                options = { animateIncoming: options };
            }
            options = options || {};
            var scroll = document.getElementById('messagingThreadScroll');
            if (!scroll) {
                return false;
            }
            var forceScroll = options.forceScroll === true;
            var stickBottom = forceScroll || isNearThreadBottom(scroll);
            var placeholder = scroll.querySelector('.messaging-board__placeholder');
            if (placeholder) {
                placeholder.remove();
            }
            var known = {};
            scroll.querySelectorAll('[data-message-id]').forEach(function (bubble) {
                var id = parseInt(bubble.getAttribute('data-message-id') || '0', 10) || 0;
                if (id > 0) {
                    known[id] = true;
                }
            });
            var added = false;
            messages.forEach(function (msg) {
                var messageId = parseInt(String(msg.message_id || '0'), 10) || 0;
                if (messageId > 0 && known[messageId]) {
                    return;
                }
                if (messageId > 0) {
                    known[messageId] = true;
                }
                var shouldAnimate = options.animateIncoming !== false && !msg.is_mine;
                scroll.insertAdjacentHTML(
                    'beforeend',
                    renderMessageBubble(msg, isGroup, shouldAnimate)
                );
                added = true;
            });
            if (added && stickBottom) {
                window.requestAnimationFrame(function () {
                    scrollThread(forceScroll);
                });
            }
            return added;
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
            board.dataset.pollThread = '';
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
            var groupName = data.group_name || 'Group';
            btn.innerHTML =
                avatarHtml(groupName, 'group-' + data.group_id, 'sm', 'messaging-avatar--group') +
                '<span class="messaging-contact__body"><span class="messaging-contact__row"><span class="messaging-contact__label">' +
                escapeHtml(groupName) +
                '</span></span><span class="messaging-contact__id">' +
                escapeHtml(String(data.member_count || '')) +
                ' members</span></span>';
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
            board.dataset.pollThread = query.group ? 'group:' + query.group : 'peer:' + query.peer;
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
                    scrollThread(true);
                    bindCompose(pane.querySelector('.js-messaging-compose'), data.mode);
                })
                .catch(function () {
                    loading = false;
                    pane.innerHTML =
                        '<p class="messaging-board__notice" role="alert">Could not load conversation. Please try again.</p>';
                });
        }

        function bindComposeFile(form) {
            if (form.dataset.composeFileBound === '1') {
                return;
            }
            form.dataset.composeFileBound = '1';
            var fileInput = form.querySelector('input[type="file"][name="attachment"]');
            var chip = form.querySelector('.js-messaging-file-chip');
            if (!fileInput || !chip) {
                return;
            }
            fileInput.addEventListener('change', function () {
                var file = fileInput.files && fileInput.files[0];
                if (!file) {
                    chip.hidden = true;
                    chip.textContent = '';
                    return;
                }
                chip.hidden = false;
                chip.textContent = file.name;
            });
        }

        function bindCompose(form, mode) {
            if (!form) {
                return;
            }
            if (form.dataset.composeBound === '1') {
                return;
            }
            form.dataset.composeBound = '1';
            bindComposeFile(form);
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                if (form.dataset.composeSending === '1') {
                    return;
                }
                var bodyInput = form.querySelector('textarea[name="body"]');
                var body = bodyInput ? bodyInput.value.trim() : '';
                if (body === '') {
                    return;
                }

                var action = mode === 'group' ? sendGroup : sendDirect;
                if (!action) {
                    return;
                }

                var submitBtn =
                    form.querySelector('.messaging-compose__send') ||
                    form.querySelector('.messaging-compose__submit');
                if (submitBtn) {
                    submitBtn.disabled = true;
                }
                form.dataset.composeSending = '1';

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
                        form.dataset.composeSending = '0';
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
                        var fileInput = form.querySelector('input[type="file"][name="attachment"]');
                        if (fileInput) {
                            fileInput.value = '';
                        }
                        var chip = form.querySelector('.js-messaging-file-chip');
                        if (chip) {
                            chip.hidden = true;
                            chip.textContent = '';
                        }
                        var scroll = document.getElementById('messagingThreadScroll');
                        var placeholder = scroll && scroll.querySelector('.messaging-board__placeholder');
                        if (placeholder) {
                            placeholder.remove();
                        }
                        if (scroll && data.message) {
                            appendIncomingMessages([data.message], mode === 'group', {
                                animateIncoming: false,
                                forceScroll: true,
                            });
                        }
                    })
                    .catch(function () {
                        form.dataset.composeSending = '0';
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

        function schedulePoll(delay) {
            if (pollTimer) {
                window.clearTimeout(pollTimer);
            }
            pollTimer = window.setTimeout(pollOnce, delay);
        }

        function pollOnce() {
            if (polling || loading || document.hidden) {
                schedulePoll(pollMs);
                return;
            }
            polling = true;
            var params = new URLSearchParams();
            if (currentThreadQuery) {
                if (currentThreadQuery.peer) {
                    params.set('peer', currentThreadQuery.peer);
                }
                if (currentThreadQuery.group) {
                    params.set('group', String(currentThreadQuery.group));
                }
                var afterId = getLastMessageId();
                if (afterId > 0) {
                    params.set('after', String(afterId));
                }
            }

            var url = pollApi + (params.toString() ? '?' + params.toString() : '');

            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
                credentials: 'same-origin',
                cache: 'no-store',
            })
                .then(function (res) {
                    return res.json();
                })
                .then(function (data) {
                    polling = false;
                    if (!data.ok) {
                        schedulePoll(pollMs);
                        return;
                    }
                    if (typeof data.unread_total === 'number') {
                        updateUnreadUi(data.unread_total);
                    }
                    syncPollUnread(data.contacts, data.groups);
                    var hadNew = false;
                    if (currentThreadQuery && data.messages && data.messages.length > 0) {
                        var isGroup = !!currentThreadQuery.group;
                        hadNew = appendIncomingMessages(data.messages, isGroup, { animateIncoming: true });
                        var activeBtn = isGroup
                            ? board.querySelector(
                                  '[data-chat-type="group"][data-group-id="' + currentThreadQuery.group + '"]'
                              )
                            : board.querySelector(
                                  '[data-chat-type="direct"][data-peer-id="' + CSS.escape(currentThreadQuery.peer) + '"]'
                              );
                        if (activeBtn) {
                            updateContactUnreadBadge(activeBtn, 0);
                        }
                    }
                    schedulePoll(hadNew ? pollFastMs : pollMs);
                })
                .catch(function () {
                    polling = false;
                    schedulePoll(pollMs);
                });
        }

        function startPolling() {
            stopPolling();
            schedulePoll(0);
        }

        function stopPolling() {
            if (pollTimer) {
                window.clearTimeout(pollTimer);
                pollTimer = null;
            }
        }

        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                stopPolling();
            } else {
                startPolling();
            }
        });

        resortContactLists();
        updateUnreadUi(parseInt(board.dataset.unreadTotal || '0', 10) || 0);
        startPolling();
    }

    document.addEventListener('DOMContentLoaded', initMessagingBoard);
    window.initMessagingBoard = initMessagingBoard;
})();
