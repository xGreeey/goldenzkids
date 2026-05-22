<?php
declare(strict_types=1);

$guardNavActive = $guardNavActive ?? 'dashboard';
$guardProfile = admin_sidebar_profile();
$guardInboxUnread = 0;

if (isset($conn) && $conn instanceof PDO) {
    try {
        require_once __DIR__ . '/messaging_unread.php';
        $guardInboxUnread = messaging_unread_total(
            $conn,
            (string) ($_SESSION['company_id'] ?? ''),
            auth_user_role()
        );
    } catch (Throwable $e) {
        error_log('guard_sidebar messaging unread: ' . $e->getMessage());
    }
}
?>
<aside class="app-sidebar" id="appSidebar" aria-label="Main navigation">
    <div class="sidebar-brand">
        <img src="<?= e(app_logo_url()) ?>" alt="<?= e(app_agency_name()) ?>" class="brand-logo">
    </div>

    <nav class="sidebar-nav" aria-label="Guard workspace">
        <a href="dashboard.php" class="sidebar-link<?= $guardNavActive === 'dashboard' ? ' active' : '' ?>"<?= $guardNavActive === 'dashboard' ? ' aria-current="page"' : '' ?><?= ui_tooltip('Overview') ?>>
            <i class="fa-solid fa-chart-line" aria-hidden="true"></i>
            Dashboard
        </a>
        <a href="submit-report.php" class="sidebar-link<?= $guardNavActive === 'submit' ? ' active' : '' ?>"<?= $guardNavActive === 'submit' ? ' aria-current="page"' : '' ?><?= ui_tooltip('Submit daily report') ?>>
            <i class="fa-solid fa-camera" aria-hidden="true"></i>
            Submit report
        </a>
        <a href="inbox.php" class="sidebar-link<?= $guardNavActive === 'inbox' ? ' active' : '' ?>"<?= $guardNavActive === 'inbox' ? ' aria-current="page"' : '' ?><?= ui_tooltip('Staff messaging') ?> data-guard-inbox-nav>
            <i class="fa-solid fa-inbox" aria-hidden="true"></i>
            Inbox
            <?php if ($guardInboxUnread > 0): ?>
                <span class="sidebar-link__badge" data-guard-inbox-badge aria-label="<?= (int) $guardInboxUnread ?> unread messages"><?= (int) $guardInboxUnread ?></span>
            <?php endif; ?>
        </a>
        <a href="corner.php" class="sidebar-link<?= $guardNavActive === 'corner' ? ' active' : '' ?>"<?= $guardNavActive === 'corner' ? ' aria-current="page"' : '' ?><?= ui_tooltip('Guard corner') ?>>
            <i class="fa-solid fa-comments" aria-hidden="true"></i>
            Guard corner
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-footer-user">
            <span class="sidebar-footer-name" title="<?= e($guardProfile['email']) ?>"><?= e($guardProfile['name']) ?></span>
            <div class="sidebar-footer-meta">
                <span class="sidebar-footer-role"><?= e($guardProfile['role']) ?></span>
            </div>
        </div>

        <div class="sidebar-footer-settings">
            <div class="sidebar-footer-settings-row">
                <span class="sidebar-footer-label">Settings</span>
                <div class="sidebar-footer-actions" role="toolbar" aria-label="Settings shortcuts">
                    <a href="settings.php" class="sidebar-footer-icon<?= $guardNavActive === 'settings' ? ' active' : '' ?>" aria-label="Account settings"<?= $guardNavActive === 'settings' ? ' aria-current="page"' : '' ?><?= ui_tooltip('Account settings', 'bottom') ?>>
                        <?= admin_sidebar_icon('settings') ?>
                    </a>
                    <form method="POST" action="../auth/logout-guard.php" class="sidebar-footer-logout">
                        <?= csrf_field() ?>
                        <button type="submit" class="sidebar-footer-icon" aria-label="Sign Out"<?= ui_tooltip('Sign out', 'bottom') ?>>
                            <?= admin_sidebar_icon('logout') ?>
                        </button>
                    </form>
                </div>
            </div>
            <div class="sidebar-footer-settings-row sidebar-footer-theme-row">
                <span class="sidebar-footer-label" id="sidebarThemeLabel">Theme</span>
                <div class="sidebar-footer-theme">
                    <?= theme_toggle_markup([
                        'id' => 'sidebarThemeToggle',
                        'mode' => 'light-class',
                        'title' => 'Toggle light or dark appearance',
                        'tipPosition' => 'bottom',
                        'showInactiveIcons' => 'next',
                    ]) ?>
                </div>
            </div>
        </div>
    </div>
</aside>
<?php
if (!function_exists('theme_sidebar_boot_script')) {
    require_once __DIR__ . '/theme.php';
}
theme_sidebar_boot_script('light-class');
?>

<div class="app-shell">
