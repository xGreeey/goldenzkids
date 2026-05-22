<?php
declare(strict_types=1);

$adminNavActive = $adminNavActive ?? 'dashboard';
$adminProfile = admin_sidebar_profile();
?>
<aside class="app-sidebar" id="appSidebar" aria-label="Main navigation">
    <div class="sidebar-brand">
        <img src="<?= e(app_logo_url()) ?>" alt="<?= e(app_agency_name()) ?>" class="brand-logo" width="104" height="104" decoding="async">
    </div>

    <nav class="sidebar-nav" aria-label="Workspace">
        <a href="dashboard.php" class="sidebar-link<?= $adminNavActive === 'dashboard' ? ' active' : '' ?>"<?= $adminNavActive === 'dashboard' ? ' aria-current="page"' : '' ?><?= ui_tooltip('Operations dashboard') ?>>
            <i class="fa-solid fa-chart-line" aria-hidden="true"></i>
            Dashboard
        </a>
        <a href="inbox.php" class="sidebar-link<?= $adminNavActive === 'inbox' ? ' active' : '' ?>"<?= $adminNavActive === 'inbox' ? ' aria-current="page"' : '' ?><?= ui_tooltip('Staff messaging board') ?>>
            <i class="fa-solid fa-inbox" aria-hidden="true"></i>
            Inbox
        </a>
        <a href="announcements.php" class="sidebar-link<?= $adminNavActive === 'announcements' ? ' active' : '' ?>"<?= $adminNavActive === 'announcements' ? ' aria-current="page"' : '' ?><?= ui_tooltip('Secured memos and internal announcements') ?>>
            <i class="fa-solid fa-bullhorn" aria-hidden="true"></i>
            Announcement
        </a>
        <a href="reports.php" class="sidebar-link<?= $adminNavActive === 'reports' ? ' active' : '' ?>"<?= $adminNavActive === 'reports' ? ' aria-current="page"' : '' ?><?= ui_tooltip('Incident reports — monitor and archive') ?>>
            <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
            Incident report
        </a>
        <a href="dad.php" class="sidebar-link<?= in_array($adminNavActive, ['dad', 'duty'], true) ? ' active' : '' ?>"<?= in_array($adminNavActive, ['dad', 'duty'], true) ? ' aria-current="page"' : '' ?><?= ui_tooltip('Daily Attendance Detail (DAD) — time-in/out, NTE, missing values') ?>>
            <i class="fa-solid fa-calendar-day" aria-hidden="true"></i>
            DAD
        </a>
        <a href="head-guard-posts.php" class="sidebar-link<?= $adminNavActive === 'head-guards' ? ' active' : '' ?>"<?= $adminNavActive === 'head-guards' ? ' aria-current="page"' : '' ?><?= ui_tooltip('Assign duty posts to head guard accounts') ?>>
            <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>
            Head guard posts
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-footer-user">
            <span class="sidebar-footer-name" title="<?= e($adminProfile['email']) ?>"><?= e($adminProfile['name']) ?></span>
            <div class="sidebar-footer-meta">
                <span class="sidebar-footer-role"><?= e($adminProfile['role']) ?></span>
            </div>
        </div>

        <div class="sidebar-footer-settings">
            <div class="sidebar-footer-settings-row">
                <span class="sidebar-footer-label">Settings</span>
                <div class="sidebar-footer-actions" role="toolbar" aria-label="Settings shortcuts">
                    <a href="#" class="sidebar-footer-icon" aria-label="Audit Logs"<?= ui_tooltip('Audit logs', 'bottom') ?>>
                        <?= admin_sidebar_icon('audit') ?>
                    </a>
                    <a href="settings.php" class="sidebar-footer-icon<?= ($adminNavActive ?? '') === 'settings' ? ' active' : '' ?>" aria-label="Settings"<?= ($adminNavActive ?? '') === 'settings' ? ' aria-current="page"' : '' ?><?= ui_tooltip('Account settings', 'bottom') ?>>
                        <?= admin_sidebar_icon('settings') ?>
                    </a>
                    <form method="POST" action="../auth/logout-admin.php" class="sidebar-footer-logout">
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

require_once __DIR__ . '/admin_topbar.php';
?>

<div class="app-shell">
<?php admin_topbar_markup(); ?>
