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

    function renderThread(payload, csrf) {
        var isGroup = payload.mode === 'group';
        var html =
            '<div class="messaging-thread__header">' +
            '<strong>' +
            escapeHtml(payload.title) +
            '</strong>' +
            '<span class="messaging-thread__meta">' +
            escapeHtml(payload.meta) +
            '</span></div>' +
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

        var threadApi = board.dataset.threadApi || 'messaging-thread.php';
        var sendDirect = board.dataset.sendDirect || '';
        var sendGroup = board.dataset.sendGroup || '';
        var baseUrl = board.dataset.baseUrl || 'inbox.php';
        var csrf = board.dataset.csrf || '';
        var createPanelTemplate = document.getElementById('createGroupPanel');
        var idleTemplate = document.getElementById('messagingIdleTemplate');
        var loading = false;

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
            if (idleTemplate) {
                pane.innerHTML = idleTemplate.innerHTML;
            }
        }

        function showCreatePanel() {
            setActiveContact(null);
            updateUrl({ create: true });
            if (createPanelTemplate && createPanelTemplate.content) {
                pane.innerHTML = '';
                pane.appendChild(createPanelTemplate.content.cloneNode(true));
            }
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
                            alert(data.error || 'Message could not be sent.');
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
                        alert('Message could not be sent. Please try again.');
                    });
            });
        }

        board.addEventListener('click', function (event) {
            var chatBtn = event.target.closest('[data-chat-type]');
            if (chatBtn) {
                event.preventDefault();
                var type = chatBtn.getAttribute('data-chat-type');
                setActiveContact(chatBtn);
                if (type === 'direct') {
                    var peer = chatBtn.getAttribute('data-peer-id');
                    if (peer) {
                        updateUrl({ peer: peer });
                        loadThread({ peer: peer });
                        var badge = chatBtn.querySelector('.messaging-contact__badge');
                        if (badge) {
                            badge.remove();
                        }
                    }
                } else if (type === 'group') {
                    var groupId = chatBtn.getAttribute('data-group-id');
                    if (groupId) {
                        updateUrl({ group: groupId });
                        loadThread({ group: groupId });
                        var badgeG = chatBtn.querySelector('.messaging-contact__badge');
                        if (badgeG) {
                            badgeG.remove();
                        }
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
            showCreatePanel();
        } else if (initialGroup) {
            var groupBtn = board.querySelector('[data-chat-type="group"][data-group-id="' + initialGroup + '"]');
            if (groupBtn) {
                setActiveContact(groupBtn);
            }
            if (pane.querySelector('.js-messaging-compose') && pane.dataset.threadMode === 'group') {
                bindCompose(pane.querySelector('.js-messaging-compose'), 'group');
                scrollThread();
            } else {
                loadThread({ group: initialGroup });
            }
        } else if (initialPeer) {
            var peerBtn = Array.from(board.querySelectorAll('[data-chat-type="direct"]')).find(function (el) {
                return el.getAttribute('data-peer-id') === initialPeer;
            });
            if (peerBtn) {
                setActiveContact(peerBtn);
            }
            if (pane.querySelector('.js-messaging-compose') && pane.dataset.threadMode === 'direct') {
                bindCompose(pane.querySelector('.js-messaging-compose'), 'direct');
                scrollThread();
            } else {
                loadThread({ peer: initialPeer });
            }
        } else if (pane.querySelector('.js-messaging-compose')) {
            bindCompose(pane.querySelector('.js-messaging-compose'), pane.dataset.threadMode || 'direct');
            scrollThread();
        }
    }

    document.addEventListener('DOMContentLoaded', initMessagingBoard);
    window.initMessagingBoard = initMessagingBoard;
})();
