<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/guard_layout.php';
require_once __DIR__ . '/../includes/admin_profile.php';

auth_require_permission('guard.dashboard.view');

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
    if ($success !== null) {
        $_SESSION['role_name'] = $form['role_name'];
    }
}

$guardNavActive = 'settings';
guard_layout_head('Account settings', 'settings', true);
?>
        <header class="page-header">
            <h1 class="page-title">Account settings</h1>
            <p class="page-subtitle">Update your name, portal username, email, and password.</p>
        </header>

        <section class="card-panel profile-settings-panel">
            <h2 class="panel-title">Your profile</h2>
            <?php admin_render_profile_form($form, $error, $success); ?>
        </section>
<?php
guard_layout_end();
