<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

auth_require_permission('admin.dashboard.view');

$adminNavActive = 'daily-activity';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?= mobile_meta_tags() ?>
    <title><?= e(app_agency_name()) ?> | Daily Activity</title>
    <script src="https://kit.fontawesome.com/3142eebea3.js" crossorigin="anonymous"></script>
    <?= app_fonts_link() ?>
    <style>
<?php admin_shell_styles(); ?>
<?php readfile(__DIR__ . '/assets/css/dashboard.css'); ?>
    </style>
</head>
<body class="light-mode page-daily-activity">

<?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <main class="app-main">
        <header class="page-header page-header--inline">
            <h1 class="page-title">Daily Activity</h1>
            <p class="page-subtitle">View shift logs and field activity for the current day. Full registry and filters for this module are coming next.</p>
        </header>
    </main>
</div>

<?php admin_shell_scripts(); ?>

<?php require_once __DIR__ . '/../includes/global-alerts.php'; ?>
</body>
</html>
