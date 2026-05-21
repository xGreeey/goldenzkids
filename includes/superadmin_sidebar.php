<?php
declare(strict_types=1);

$superadminNavActive = $superadminNavActive ?? 'dashboard';
?>
<aside class="app-sidebar" id="appSidebar" aria-label="Main navigation">
    <div class="sidebar-brand">
        <img src="<?= e(app_logo_url()) ?>" alt="<?= e(app_agency_name()) ?>" class="brand-logo">
    </div>

    <nav class="sidebar-nav" aria-label="System administration">
        <a href="dashboard.php" class="sidebar-link<?= $superadminNavActive === 'dashboard' ? ' active' : '' ?>"<?= $superadminNavActive === 'dashboard' ? ' aria-current="page"' : '' ?><?= ui_tooltip('System dashboard') ?>>
            <i class="fa-solid fa-chart-line" aria-hidden="true"></i>
            Dashboard
        </a>
        <a href="users.php" class="sidebar-link<?= $superadminNavActive === 'users' ? ' active' : '' ?>"<?= $superadminNavActive === 'users' ? ' aria-current="page"' : '' ?><?= ui_tooltip('Manage portal accounts') ?>>
            <i class="fa-solid fa-users-gear" aria-hidden="true"></i>
            User Accounts
        </a>
        <a href="audit-log.php" class="sidebar-link<?= $superadminNavActive === 'audit' ? ' active' : '' ?>"<?= $superadminNavActive === 'audit' ? ' aria-current="page"' : '' ?><?= ui_tooltip('Full audit log') ?>>
            <i class="fa-solid fa-clipboard-list" aria-hidden="true"></i>
            Audit Log
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
                    <a href="audit-log.php" class="sidebar-footer-icon" aria-label="Audit Logs"<?= ui_tooltip('Audit logs', 'bottom') ?>>
                        <?= admin_sidebar_icon('audit') ?>
                    </a>
                    <a href="settings.php" class="sidebar-footer-icon<?= ($superadminNavActive ?? '') === 'settings' ? ' active' : '' ?>" aria-label="Settings"<?= ($superadminNavActive ?? '') === 'settings' ? ' aria-current="page"' : '' ?><?= ui_tooltip('Account settings', 'bottom') ?>>
                        <?= admin_sidebar_icon('settings') ?>
                    </a>
                    <form method="POST" action="../auth/logout-superadmin.php" class="sidebar-footer-logout">
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
