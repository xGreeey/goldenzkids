<?php
declare(strict_types=1);

$guardNavActive = $guardNavActive ?? 'dashboard';
$adminProfile = admin_sidebar_profile();
?>
<aside class="app-sidebar" id="appSidebar" aria-label="Main navigation">
    <div class="sidebar-brand">
        <img src="<?= e(app_logo_url()) ?>" alt="<?= e(app_agency_name()) ?>" class="brand-logo">
    </div>

    <nav class="sidebar-nav" aria-label="Guard workspace">
        <a href="dashboard.php" class="sidebar-link<?= $guardNavActive === 'dashboard' ? ' active' : '' ?>"<?= $guardNavActive === 'dashboard' ? ' aria-current="page"' : '' ?><?= ui_tooltip('Field dashboard') ?>>
            <i class="fa-solid fa-chart-line" aria-hidden="true"></i>
            Dashboard
        </a>
        <a href="inbox.php" class="sidebar-link<?= $guardNavActive === 'inbox' ? ' active' : '' ?>"<?= $guardNavActive === 'inbox' ? ' aria-current="page"' : '' ?><?= ui_tooltip('Memos and notifications') ?>>
            <i class="fa-solid fa-inbox" aria-hidden="true"></i>
            Inbox
        </a>
        <a href="reports.php" class="sidebar-link<?= $guardNavActive === 'reports' ? ' active' : '' ?>"<?= $guardNavActive === 'reports' ? ' aria-current="page"' : '' ?><?= ui_tooltip('Daily guard reports') ?>>
            <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
            My reports
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
                    <a href="inbox.php" class="sidebar-footer-icon" aria-label="Inbox"<?= ui_tooltip('Inbox', 'bottom') ?>>
                        <?= admin_sidebar_icon('audit') ?>
                    </a>
                    <a href="reports.php" class="sidebar-footer-icon" aria-label="My reports"<?= ui_tooltip('My reports', 'bottom') ?>>
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
            <div class="sidebar-footer-theme">
                <?= theme_toggle_markup([
                    'id' => 'sidebarThemeToggle',
                    'mode' => 'light-class',
                    'title' => 'Toggle light or dark appearance',
                    'tipPosition' => 'bottom',
                ]) ?>
            </div>
        </div>
    </div>
</aside>

<div class="app-shell">
