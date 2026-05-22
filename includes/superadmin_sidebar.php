<?php
declare(strict_types=1);

require_once __DIR__ . '/superadmin_report_nav.php';
require_once __DIR__ . '/admin_ui_icons.php';

$superadminNavActive = $superadminNavActive ?? 'dashboard';
$saReportNavOpen = superadmin_report_nav_is_open($superadminNavActive);
$saReportNavItems = superadmin_report_nav_items();
if (!isset($adminProfile)) {
    require_once __DIR__ . '/admin_shell.php';
    $adminProfile = admin_sidebar_profile();
}
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
        <div class="sidebar-nav-group<?= $saReportNavOpen ? ' is-open has-active' : '' ?>" data-sidebar-nav-group>
            <button type="button"
                    class="sidebar-nav-group__toggle"
                    aria-expanded="<?= $saReportNavOpen ? 'true' : 'false' ?>"
                    aria-controls="sidebarSaReportMenu"
                    id="sidebarSaReportToggle"<?= ui_tooltip('Deleted & archived reports — weekly, daily, DTR, incident') ?>>
                <span class="sidebar-nav-group__label">Report</span>
                <span class="sidebar-nav-group__chevron" aria-hidden="true"><?= admin_ui_icon('chevron-down', 16) ?></span>
            </button>
            <div id="sidebarSaReportMenu"
                 class="sidebar-nav-group__menu"
                 role="group"
                 aria-labelledby="sidebarSaReportToggle"
                 <?= $saReportNavOpen ? '' : ' hidden' ?>>
                <?php foreach ($saReportNavItems as $item):
                    $itemActive = in_array($superadminNavActive, $item['active'], true);
                    ?>
                <a href="<?= e($item['href']) ?>"
                   class="sidebar-link sidebar-link--sub<?= $itemActive ? ' active' : '' ?>"
                   data-nav-slug="<?= e((string) $item['slug']) ?>"
                   <?= $itemActive ? ' aria-current="page"' : '' ?>
                   <?= ui_tooltip((string) $item['tip']) ?>>
                    <?= e((string) ($item['menu_label'] ?? $item['label'])) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
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
