(function () {
    'use strict';

    var VISIBLE_ROWS = 5;
    var ROW_HEIGHT_PX = 72;

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function renderItem(item) {
        return (
            '<li role="listitem">' +
            '<a href="' +
            escapeHtml(item.href || '#') +
            '" class="admin-notifications__item" data-notification-id="' +
            escapeHtml(item.id || '') +
            '">' +
            '<span class="admin-notifications__item-icon" aria-hidden="true">' +
            (item.icon_markup || '') +
            '</span>' +
            '<span class="admin-notifications__item-body">' +
            '<span class="admin-notifications__item-title">' +
            escapeHtml(item.title || '') +
            '</span>' +
            '<span class="admin-notifications__item-text">' +
            escapeHtml(item.body || '') +
            '</span></span>' +
            '<time class="admin-notifications__item-time" datetime="' +
            escapeHtml(item.at || '') +
            '">' +
            escapeHtml(item.time_label || '') +
            '</time></a></li>'
        );
    }

    function updateBadge(root, count) {
        var badge = document.getElementById('adminNotificationsBadge');
        var trigger = document.getElementById('adminNotificationsTrigger');
        if (!badge || !trigger) {
            return;
        }
        count = Math.max(0, parseInt(String(count), 10) || 0);
        root.dataset.unreadCount = String(count);
        if (count > 0) {
            badge.textContent = String(count);
            badge.hidden = false;
            badge.classList.remove('is-hidden');
            trigger.setAttribute('aria-label', 'Notifications, ' + count + ' unread');
        } else {
            badge.hidden = true;
            badge.classList.add('is-hidden');
            trigger.setAttribute('aria-label', 'Notifications, no new items');
        }

        var headCount = root.querySelector('.admin-notifications__count');
        if (headCount) {
            if (count > 0) {
                headCount.textContent = count + ' new';
                headCount.hidden = false;
            } else {
                headCount.remove();
            }
        } else if (count > 0) {
            var head = root.querySelector('.admin-notifications__head');
            if (head) {
                var span = document.createElement('span');
                span.className = 'admin-notifications__count';
                span.textContent = count + ' new';
                head.appendChild(span);
            }
        }
    }

    function renderList(root, items) {
        var list = document.getElementById('adminNotificationsList');
        var wrap = root.querySelector('.admin-notifications__list-wrap');
        if (!list || !wrap) {
            return;
        }

        wrap.style.maxHeight = VISIBLE_ROWS * ROW_HEIGHT_PX + 'px';

        if (!items || items.length === 0) {
            list.innerHTML =
                '<li class="admin-notifications__empty" role="listitem">You are all caught up.</li>';
            return;
        }

        list.innerHTML = items.map(renderItem).join('');
    }

    function fetchNotifications(root) {
        var url = root.dataset.feedUrl;
        if (!url) {
            return Promise.resolve();
        }

        return fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
            .then(function (res) {
                return res.json();
            })
            .then(function (data) {
                if (!data || !data.ok) {
                    return;
                }
                renderList(root, data.items || []);
                updateBadge(root, data.count || 0);
            })
            .catch(function () {
                /* keep server-rendered list */
            });
    }

    function closePanel(root, panel, trigger) {
        panel.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
        root.classList.remove('is-open');
    }

    function openPanel(root, panel, trigger) {
        panel.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');
        root.classList.add('is-open');
        fetchNotifications(root);
    }

    function initAdminNotifications() {
        var root = document.getElementById('adminNotifications');
        var trigger = document.getElementById('adminNotificationsTrigger');
        var panel = document.getElementById('adminNotificationsPanel');
        if (!root || !trigger || !panel) {
            return;
        }

        if (root.dataset.notificationsBound === '1') {
            fetchNotifications(root);
            return;
        }
        root.dataset.notificationsBound = '1';

        var wrap = root.querySelector('.admin-notifications__list-wrap');
        if (wrap) {
            wrap.style.maxHeight = VISIBLE_ROWS * ROW_HEIGHT_PX + 'px';
        }

        trigger.addEventListener('click', function (event) {
            event.stopPropagation();
            if (panel.hidden) {
                openPanel(root, panel, trigger);
            } else {
                closePanel(root, panel, trigger);
            }
        });

        document.addEventListener('click', function (event) {
            if (!root.contains(event.target)) {
                closePanel(root, panel, trigger);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !panel.hidden) {
                closePanel(root, panel, trigger);
            }
        });

        panel.addEventListener('click', function (event) {
            var link = event.target.closest('.admin-notifications__item');
            if (link) {
                closePanel(root, panel, trigger);
            }
        });

        fetchNotifications(root);
        window.setInterval(function () {
            fetchNotifications(root);
        }, 60000);
    }

    document.addEventListener('DOMContentLoaded', initAdminNotifications);
    window.initAdminNotifications = initAdminNotifications;
})();
