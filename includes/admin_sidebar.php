<?php
declare(strict_types=1);

$adminNavActive = $adminNavActive ?? 'dashboard';
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
<<<<<<< HEAD
        <a href="reports.php" class="sidebar-link<?= $adminNavActive === 'reports' ? ' active' : '' ?>"<?= $adminNavActive === 'reports' ? ' aria-current="page"' : '' ?><?= ui_tooltip('Incident reports — monitor and archive') ?>>
=======
        <a href="announcements.php" class="sidebar-link<?= $adminNavActive === 'announcements' ? ' active' : '' ?>"<?= $adminNavActive === 'announcements' ? ' aria-current="page"' : '' ?><?= ui_tooltip('Secured memos and internal announcements') ?>>
            <i class="fa-solid fa-bullhorn" aria-hidden="true"></i>
            Announcement
        </a>
        <a href="reports.php" class="sidebar-link<?= $adminNavActive === 'reports' ? ' active' : '' ?>"<?= $adminNavActive === 'reports' ? ' aria-current="page"' : '' ?><?= ui_tooltip('Daily guard report review') ?>>
>>>>>>> eed8e9d3e77bdacb37e57b3a5a0992d3efd5a7dd
            <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
            Incident report
        </a>
        <a href="duty-detail.php" class="sidebar-link<?= $adminNavActive === 'duty' ? ' active' : '' ?>"<?= $adminNavActive === 'duty' ? ' aria-current="page"' : '' ?><?= ui_tooltip('Duty posts and personnel assignments') ?>>
            <i class="fa-solid fa-user-shield" aria-hidden="true"></i>
            Duty detail
        </a>
    </nav>

    <div class="sidebar-footer">
<<<<<<< HEAD
=======
        <div class="sidebar-footer-user">
            <span class="sidebar-footer-name" title="<?= e($adminProfile['email']) ?>"><?= e($adminProfile['name']) ?></span>
            <div class="sidebar-footer-meta">
                <span class="sidebar-footer-role"><?= e($adminProfile['role']) ?></span>
            </div>
        </div>

>>>>>>> eed8e9d3e77bdacb37e57b3a5a0992d3efd5a7dd
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
?>

<div class="app-shell">
