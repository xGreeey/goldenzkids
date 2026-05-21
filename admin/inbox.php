<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once APP_ROOT . '/includes/internal_messaging.php';
require_once APP_ROOT . '/includes/group_messaging.php';

auth_require_permission('admin.inbox.manage');

$company_id = (string) $_SESSION['company_id'];

$messagingAvailable = false;
$messagingViewerId = $company_id;
$messagingContacts = [];
$messagingActivePeer = null;
$messagingThread = [];
$messagingPostUrl = 'send-internal-message.php';
$messagingReturnUrl = 'inbox.php';
$messagingMode = 'direct';
$messagingGroups = [];
$messagingActiveGroupId = null;
$messagingGroupThread = [];
$messagingGroupMeta = null;
$messagingCanCreateGroups = false;
$messagingHeadGuardOptions = [];
$messagingGroupPostUrl = 'send-group-message.php';
$messagingCreateGroupUrl = 'create-message-group.php';
$groupsAvailable = false;
$messagingShowDirect = true;
$messagingShowCreatePanel = false;

try {
    $messagingAvailable = internal_messages_table_exists($conn);
    $messagingContacts = internal_messaging_list_contacts($conn, auth_user_role());
    $messagingActivePeer = isset($_GET['peer']) ? trim((string) $_GET['peer']) : null;
    if ($messagingActivePeer !== null && $messagingActivePeer !== ''
        && !internal_messaging_validate_peer_for_viewer($conn, $messagingActivePeer, auth_user_role())) {
        $messagingActivePeer = null;
    }

    $groupsAvailable = message_groups_table_exists($conn);
    if ($groupsAvailable) {
        $messagingGroups = group_messaging_list_groups_for_user($conn, $messagingViewerId);
        $messagingCanCreateGroups = auth_user_can('admin.messaging.send');
        $messagingHeadGuardOptions = $messagingCanCreateGroups
            ? group_messaging_list_head_guard_options($conn)
            : [];

        $wantsCreateGroup = isset($_GET['create_group']);
        $groupParam = isset($_GET['group']) ? (int) $_GET['group'] : 0;

        if ($wantsCreateGroup && $messagingCanCreateGroups) {
            $messagingShowCreatePanel = true;
            $messagingMode = 'create';
            $messagingActiveGroupId = null;
            $messagingActivePeer = null;
        } elseif ($groupParam > 0 && group_messaging_user_in_group($conn, $groupParam, $messagingViewerId)) {
            $messagingMode = 'group';
            $messagingActiveGroupId = $groupParam;
            $messagingActivePeer = null;
            $messagingGroupMeta = group_messaging_get_group_meta($conn, $groupParam, $messagingViewerId);
            $messagingGroupThread = group_messaging_fetch_messages($conn, $groupParam, $messagingViewerId);
        } elseif ($messagingCanCreateGroups && $messagingGroups === []) {
            $messagingShowCreatePanel = true;
            $messagingMode = 'create';
        }
    }

    if ($messagingMode === 'direct') {
        if ($messagingAvailable && $messagingActivePeer !== null && $messagingActivePeer !== '') {
            $messagingThread = internal_messaging_fetch_thread($conn, $messagingViewerId, $messagingActivePeer);
        }
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
