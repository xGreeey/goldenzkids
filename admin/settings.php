<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/admin_profile.php';

auth_require_permission('admin.dashboard.view');

$companyId = (string) ($_SESSION['company_id'] ?? '');
$success = null;
$error = null;
$form = admin_profile_defaults($companyId);

$loaded = admin_profile_load($conn, $companyId);
if ($loaded === null) {
    http_response_code(404);
    exit('Account not found.');
}
$form = $loaded;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['update_profile'])) {
    csrf_verify();
    $result = admin_handle_profile_post($conn, $companyId);
    $form = $result['form'];
    $error = $result['error'];
    $success = $result['success'];
    $companyId = (string) ($_SESSION['company_id'] ?? $companyId);
}

$adminNavActive = 'settings';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?= mobile_meta_tags() ?>
    <title><?= e(app_agency_name()) ?> | Account Settings</title>
    <script src="https://kit.fontawesome.com/3142eebea3.js" crossorigin="anonymous"></script>
    <?= app_fonts_link() ?>
    <style>
<?php admin_shell_styles(); ?>
<?php require_once __DIR__ . '/../includes/superadmin_page.css.php'; superadmin_page_styles(); ?>
<?php admin_profile_page_styles(); ?>
    </style>
</head>
<body class="light-mode">

<?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <main class="app-main">
        <header class="page-header">
            <h1 class="page-title">Account settings</h1>
            <p class="page-subtitle">Update your portal username, email, and password.</p>
        </header>

        <section class="card-panel profile-settings-panel">
            <h2 class="panel-title">Your profile</h2>
            <?php admin_render_profile_form($form, $error, $success); ?>
        </section>
    </main>
</div>

<?php admin_shell_scripts(); ?>
<?php require_once __DIR__ . '/../includes/global-alerts.php'; ?>
</body>
</html>
