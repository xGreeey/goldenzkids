<?php
declare(strict_types=1);

$adminNavActive = $adminNavActive ?? 'dashboard';
$adminMobileTitle = $adminMobileTitle ?? 'Operations';
?>
<div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>

<aside class="app-sidebar" id="appSidebar" aria-label="Main navigation">
    <div class="sidebar-brand">
        <img src="https://i.imgur.com/uOClOiX.jpeg" alt="ABC Security" class="brand-logo" onerror="this.src='https://via.placeholder.com/42/0f2744/c9a227?text=ABC'">
        <div class="brand-text">
            <span class="brand-name">ABC Security Agency</span>
            <span class="brand-tagline">Enterprise Operations Portal</span>
        </div>
    </div>

    <nav class="sidebar-nav" aria-label="Workspace">
        <a href="dashboard.php" class="sidebar-link<?= $adminNavActive === 'dashboard' ? ' active' : '' ?>"<?= $adminNavActive === 'dashboard' ? ' aria-current="page"' : '' ?>>
            <i class="fa-solid fa-chart-line" aria-hidden="true"></i>
            Dashboard
        </a>
        <a href="inbox.php" class="sidebar-link<?= $adminNavActive === 'inbox' ? ' active' : '' ?>"<?= $adminNavActive === 'inbox' ? ' aria-current="page"' : '' ?>>
            <i class="fa-solid fa-inbox" aria-hidden="true"></i>
            Report Inbox
        </a>
    </nav>

    <div class="sidebar-footer">
        <form method="POST" action="../auth/logout-admin.php" class="sidebar-logout-form">
            <?= csrf_field() ?>
            <button type="submit" class="sidebar-link sidebar-link--signout">
                <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                Sign Out
            </button>
        </form>
    </div>
    <div class="sidebar-appearance">
        <button type="button" id="themeToggle" class="btn-appearance" title="Switch to dark mode" aria-label="Toggle light or dark appearance">
            <i class="fa-solid fa-moon" aria-hidden="true"></i>
            Appearance
        </button>
    </div>
</aside>

<div class="app-shell">
    <div class="mobile-topbar">
        <button type="button" class="btn-menu" id="sidebarToggle" aria-label="Open navigation menu" aria-expanded="false" aria-controls="appSidebar">
            <i class="fa-solid fa-bars" aria-hidden="true"></i>
        </button>
        <span class="mobile-topbar-title"><?= e($adminMobileTitle) ?></span>
    </div>
