<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

auth_require_permission('admin.memo.send');

$adminNavActive = 'announcements';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?= mobile_meta_tags() ?>
    <title><?= e(app_agency_name()) ?> | Announcement</title>
    <script src="https://kit.fontawesome.com/3142eebea3.js" crossorigin="anonymous"></script>
    <?= app_fonts_link() ?>
    <style>
<?php admin_shell_styles(); ?>
<?php readfile(__DIR__ . '/assets/css/dashboard.css'); ?>
    </style>
</head>
<body class="light-mode">
<?php admin_theme_body_boot(); ?>

<?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <main class="app-main">
        <header class="page-header">
            <h1 class="page-title">Announcement</h1>
            <p class="page-subtitle">Publish secured memos to all head guards. Each memo appears on Guard corner → Board → Announcement.</p>
        </header>

        <div class="announcement-layout">
            <?php require __DIR__ . '/../includes/admin_memo_compose.php'; ?>
        </div>
    </main>
</div>

<?php admin_shell_scripts(); ?>

<?php require_once __DIR__ . '/../includes/global-alerts.php'; ?>
</body>
</html>
