<?php
declare(strict_types=1);

$superadminNavActive = $superadminNavActive ?? 'dashboard';
$superadminMobileTitle = $superadminMobileTitle ?? 'System Control';
?>
<div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>

<aside class="app-sidebar" id="appSidebar" aria-label="Superadmin navigation">
    <div class="sidebar-brand">
        <img src="https://i.imgur.com/uOClOiX.jpeg" alt="ABC Security" class="brand-logo" onerror="this.src='https://via.placeholder.com/42/0f2744/c9a227?text=ABC'">
        <div class="brand-text">
            <span class="brand-name">ABC Security Agency</span>
        </div>
    </div>

    <nav class="sidebar-nav" aria-label="Superadmin workspace">
        <a href="dashboard.php" class="sidebar-link<?= $superadminNavActive === 'dashboard' ? ' active' : '' ?>"<?= $superadminNavActive === 'dashboard' ? ' aria-current="page"' : '' ?>>
            <i class="fa-solid fa-gauge-high" aria-hidden="true"></i>
            Dashboard
        </a>
        <a href="users.php" class="sidebar-link<?= $superadminNavActive === 'users' ? ' active' : '' ?>"<?= $superadminNavActive === 'users' ? ' aria-current="page"' : '' ?>>
            <i class="fa-solid fa-users-gear" aria-hidden="true"></i>
            User Accounts
        </a>
        <a href="create-user.php" class="sidebar-link<?= $superadminNavActive === 'create-user' ? ' active' : '' ?>"<?= $superadminNavActive === 'create-user' ? ' aria-current="page"' : '' ?>>
            <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
            Create Account
        </a>
        <a href="audit-log.php" class="sidebar-link<?= $superadminNavActive === 'audit' ? ' active' : '' ?>"<?= $superadminNavActive === 'audit' ? ' aria-current="page"' : '' ?>>
            <i class="fa-solid fa-clipboard-list" aria-hidden="true"></i>
            Audit Log
        </a>
    </nav>

    <div class="sidebar-footer">
        <form method="POST" action="../auth/logout-superadmin.php" class="sidebar-logout-form">
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
        <span class="mobile-topbar-title"><?= e($superadminMobileTitle) ?></span>
    </div>
