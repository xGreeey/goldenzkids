<?php
declare(strict_types=1);

$guardNavActive = $guardNavActive ?? 'dashboard';
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
        <a href="inbox.php" class="sidebar-link<?= $guardNavActive === 'inbox' ? ' active' : '' ?>"<?= $guardNavActive === 'inbox' ? ' aria-current="page"' : '' ?><?= ui_tooltip('Memos') ?>>
            <i class="fa-solid fa-inbox" aria-hidden="true"></i>
            Inbox
        </a>
        <a href="corner.php" class="sidebar-link<?= $guardNavActive === 'corner' ? ' active' : '' ?>"<?= $guardNavActive === 'corner' ? ' aria-current="page"' : '' ?><?= ui_tooltip('Guard corner') ?>>
            <i class="fa-solid fa-comments" aria-hidden="true"></i>
            Guard corner
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
                <div class="sidebar-footer-actions" role="toolbar" aria-label="Sign out and appearance">
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
