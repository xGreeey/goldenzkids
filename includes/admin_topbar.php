<?php
declare(strict_types=1);

require_once __DIR__ . '/admin_notifications.php';

/**
 * Sticky admin header with notification bell (upper right).
 */
function admin_topbar_markup(): void
{
    global $conn;

    $adminId = (string) ($_SESSION['company_id'] ?? '');
    $viewerRole = auth_user_role();
    $notifications = [];
    $notificationCount = 0;

    if (isset($conn) && $conn instanceof PDO && $adminId !== '') {
        try {
            $notifications = admin_notifications_fetch($conn, $adminId, $viewerRole, 40);
            $notificationCount = count($notifications);
        } catch (Throwable $e) {
            error_log('admin_topbar notifications: ' . $e->getMessage());
        }
    }

    $feedUrl = app_url('admin/notifications-feed.php');
    ?>
<header class="admin-app__topbar" aria-label="Admin toolbar">
    <div class="admin-app__topbar-inner">
        <p class="admin-app__topbar-title">Operations workspace</p>
        <div class="admin-notifications" id="adminNotifications"
             data-feed-url="<?= e($feedUrl) ?>"
             data-unread-count="<?= (int) $notificationCount ?>">
            <button type="button"
                    class="admin-notifications__trigger"
                    id="adminNotificationsTrigger"
                    aria-expanded="false"
                    aria-controls="adminNotificationsPanel"
                    aria-label="<?= $notificationCount > 0
                        ? 'Notifications, ' . $notificationCount . ' unread'
                        : 'Notifications, no new items' ?>">
                <i class="fa-solid fa-bell" aria-hidden="true"></i>
                <span class="admin-notifications__badge<?= $notificationCount > 0 ? '' : ' is-hidden' ?>"
                      id="adminNotificationsBadge"
                      <?= $notificationCount > 0 ? '' : ' hidden' ?>><?= (int) $notificationCount ?></span>
            </button>
            <div class="admin-notifications__panel"
                 id="adminNotificationsPanel"
                 role="region"
                 aria-labelledby="adminNotificationsTitle"
                 hidden>
                <div class="admin-notifications__head">
                    <h2 class="admin-notifications__title" id="adminNotificationsTitle">Notifications</h2>
                    <?php if ($notificationCount > 0): ?>
                        <span class="admin-notifications__count"><?= (int) $notificationCount ?> new</span>
                    <?php endif; ?>
                </div>
                <div class="admin-notifications__list-wrap">
                    <ul class="admin-notifications__list" id="adminNotificationsList" role="list">
                        <?php if ($notifications === []): ?>
                            <li class="admin-notifications__empty" role="listitem">You are all caught up.</li>
                        <?php else: ?>
                            <?php foreach ($notifications as $item): ?>
                            <li role="listitem">
                                <a href="<?= e((string) $item['href']) ?>"
                                   class="admin-notifications__item"
                                   data-notification-id="<?= e((string) $item['id']) ?>">
                                    <span class="admin-notifications__item-icon" aria-hidden="true">
                                        <i class="fa-solid fa-<?= e((string) $item['icon']) ?>"></i>
                                    </span>
                                    <span class="admin-notifications__item-body">
                                        <span class="admin-notifications__item-title"><?= e((string) $item['title']) ?></span>
                                        <span class="admin-notifications__item-text"><?= e((string) $item['body']) ?></span>
                                    </span>
                                    <time class="admin-notifications__item-time" datetime="<?= e((string) $item['at']) ?>">
                                        <?= e((string) $item['time_label']) ?>
                                    </time>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="admin-notifications__foot">
                    <a href="<?= e(app_url('admin/inbox.php')) ?>" class="admin-notifications__foot-link">Open inbox</a>
                </div>
            </div>
        </div>
    </div>
</header>
    <?php
}
