<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

auth_require_permission('admin.reports.view');

$adminNavActive = 'weekly-accomplishment';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?= mobile_meta_tags() ?>
    <title><?= e(app_agency_name()) ?> | Weekly Accomplishment Report</title>
    <script src="https://kit.fontawesome.com/3142eebea3.js" crossorigin="anonymous"></script>
    <?= app_fonts_link() ?>
    <style>
<?php admin_shell_styles(); ?>
<?php readfile(__DIR__ . '/assets/css/dashboard.css'); ?>
    </style>
</head>
<body class="light-mode page-weekly-accomplishment">

<?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <main class="app-main">
        <header class="page-header page-header--inline">
            <h1 class="page-title">Weekly Accomplishment Report</h1>
            <p class="page-subtitle">Review weekly accomplishment summaries submitted by head guards. Registry and workflows for this module are coming next.</p>
        </header>
    </main>
</div>

<?php admin_shell_scripts(); ?>

<?php require_once __DIR__ . '/../includes/global-alerts.php'; ?>
</body>
</html>
