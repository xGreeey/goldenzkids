<?php
declare(strict_types=1);

require_once __DIR__ . '/admin_ui_icons.php';

$adminNavActive = $adminNavActive ?? 'dashboard';
$adminProfile = admin_sidebar_profile();

$reportNavOpen = in_array($adminNavActive, ['reports', 'dad', 'duty', 'weekly-accomplishment'], true);
/** @var list<array{slug: string, href: string, label: string, icon: string, tip: string, active: list<string>}> */
$reportNavItems = [
    [
        'slug' => 'weekly-accomplishment',
        'href' => 'weekly-accomplishment.php',
        'label' => 'Weekly accomplishment report',
        'icon' => 'clipboard-list',
        'tip' => 'Weekly Accomplishment Report — review head guard weekly summaries',
        'active' => ['weekly-accomplishment'],
    ],
    [
        'slug' => 'dad',
        'href' => 'dad.php',
        'label' => 'DAD',
        'icon' => 'calendar-day',
        'tip' => 'Daily Attendance Detail (DAD) — time-in/out, NTE, missing values',
        'active' => ['dad', 'duty'],
    ],
    [
        'slug' => 'reports',
        'href' => 'reports.php',
        'label' => 'Incident report',
        'icon' => 'file-lines',
        'tip' => 'Incident reports — monitor and archive',
        'active' => ['reports'],
    ],
];
?>
<aside class="app-sidebar" id="appSidebar" aria-label="Main navigation">
    <div class="sidebar-brand">
        <img src="<?= e(app_logo_url()) ?>" alt="<?= e(app_agency_name()) ?>" class="brand-logo" width="104" height="104" decoding="async">
    </div>

    <nav class="sidebar-nav" aria-label="Workspace">
        <a href="dashboard.php" class="sidebar-link<?= $adminNavActive === 'dashboard' ? ' active' : '' ?>"<?= $adminNavActive === 'dashboard' ? ' aria-current="page"' : '' ?><?= ui_tooltip('Operations dashboard') ?>>
            <?= admin_nav_icon('chart-line') ?>
            Dashboard
        </a>
        <a href="inbox.php" class="sidebar-link<?= $adminNavActive === 'inbox' ? ' active' : '' ?>"<?= $adminNavActive === 'inbox' ? ' aria-current="page"' : '' ?><?= ui_tooltip('Staff messaging board') ?>>
            <?= admin_nav_icon('inbox') ?>
            Inbox
        </a>
        <a href="announcements.php" class="sidebar-link<?= $adminNavActive === 'announcements' ? ' active' : '' ?>"<?= $adminNavActive === 'announcements' ? ' aria-current="page"' : '' ?><?= ui_tooltip('Secured memos and internal announcements') ?>>
            <?= admin_nav_icon('bullhorn') ?>
            Announcement
        </a>
        <div class="sidebar-nav-group<?= $reportNavOpen ? ' is-open has-active' : '' ?>" data-sidebar-nav-group>
            <button type="button"
                    class="sidebar-nav-group__toggle"
                    aria-expanded="<?= $reportNavOpen ? 'true' : 'false' ?>"
                    aria-controls="sidebarReportMenu"
                    id="sidebarReportToggle"<?= ui_tooltip('Reports — weekly accomplishment, DAD, incident') ?>>
                <?= admin_nav_icon('folder-open') ?>
                <span class="sidebar-nav-group__label">Report</span>
                <span class="sidebar-nav-group__chevron" aria-hidden="true"><?= admin_ui_icon('chevron-down', 16) ?></span>
            </button>
            <div id="sidebarReportMenu"
                 class="sidebar-nav-group__menu"
                 role="group"
                 aria-labelledby="sidebarReportToggle"
                 <?= $reportNavOpen ? '' : ' hidden' ?>>
                <?php foreach ($reportNavItems as $item):
                    $itemActive = in_array($adminNavActive, $item['active'], true);
                    ?>
                <a href="<?= e($item['href']) ?>"
                   class="sidebar-link sidebar-link--sub<?= $itemActive ? ' active' : '' ?>"
                   <?= $itemActive ? ' aria-current="page"' : '' ?>
                   <?= ui_tooltip((string) $item['tip']) ?>>
                    <?= admin_nav_icon((string) $item['icon']) ?>
                    <?= e((string) $item['label']) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <a href="daily-activity.php" class="sidebar-link<?= $adminNavActive === 'daily-activity' ? ' active' : '' ?>"<?= $adminNavActive === 'daily-activity' ? ' aria-current="page"' : '' ?><?= ui_tooltip('Daily Activity — shift logs and field activity for the current day') ?>>
            <?= admin_nav_icon('clock') ?>
            Daily Activity
        </a>
        <a href="head-guard-posts.php" class="sidebar-link<?= $adminNavActive === 'head-guards' ? ' active' : '' ?>"<?= $adminNavActive === 'head-guards' ? ' aria-current="page"' : '' ?><?= ui_tooltip('Assign duty posts to head guard accounts') ?>>
            <?= admin_nav_icon('map-location-dot') ?>
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
