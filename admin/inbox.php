<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once APP_ROOT . '/includes/internal_messaging.php';

auth_require_permission('admin.inbox.manage');

$company_id = (string) $_SESSION['company_id'];

$messagingAvailable = false;
$messagingViewerId = $company_id;
$messagingContacts = [];
$messagingActivePeer = null;
$messagingThread = [];
$messagingPostUrl = 'send-internal-message.php';
$messagingReturnUrl = 'inbox.php';

try {
    $messagingAvailable = internal_messages_table_exists($conn);
    $messagingContacts = internal_messaging_list_contacts($conn, auth_user_role());
    $messagingActivePeer = isset($_GET['peer']) ? trim((string) $_GET['peer']) : null;
    if ($messagingActivePeer !== null && $messagingActivePeer !== ''
        && !internal_messaging_validate_peer($conn, $messagingActivePeer, internal_messaging_peer_role(auth_user_role()))) {
        $messagingActivePeer = null;
    }
    if (($messagingActivePeer === null || $messagingActivePeer === '') && count($messagingContacts) === 1) {
        $messagingActivePeer = $messagingContacts[0]['company_id'];
    }
    if ($messagingAvailable && $messagingActivePeer !== null && $messagingActivePeer !== '') {
        $messagingThread = internal_messaging_fetch_thread($conn, $messagingViewerId, $messagingActivePeer);
    }
} catch (Throwable $e) {
    error_log('admin/inbox messaging: ' . $e->getMessage());
}

$memo_guards_query = $conn->query('SELECT Company_ID, First_Name, Last_Name FROM guards ORDER BY Last_Name ASC');

$adminNavActive = 'inbox';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?= mobile_meta_tags() ?>
    <title><?= e(app_agency_name()) ?> | Inbox</title>
    <script src="https://kit.fontawesome.com/3142eebea3.js" crossorigin="anonymous"></script>
    <?= app_fonts_link() ?>
    <style>
<?php admin_shell_styles(); ?>
<?php readfile(__DIR__ . '/assets/css/dashboard.css'); ?>
    </style>
</head>
<body class="light-mode">

<?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <main class="app-main">
        <header class="page-header">
            <h1 class="page-title">Inbox</h1>
            <p class="page-subtitle">Internal communications, staff messaging, and memos.</p>
        </header>

        <div class="inbox-top-grid">
            <?php require __DIR__ . '/../includes/admin_internal_communications.php'; ?>
            <?php require __DIR__ . '/../includes/messaging_board.php'; ?>
        </div>
    </main>
</div>

<?php admin_shell_scripts(); ?>

<?php require_once __DIR__ . '/../includes/global-alerts.php'; ?>
</body>
</html>
