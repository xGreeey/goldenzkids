<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once APP_ROOT . '/includes/guard_layout.php';
require_once APP_ROOT . '/includes/internal_messaging.php';
require_once APP_ROOT . '/includes/group_messaging.php';

auth_require_permission('guard.inbox.view');

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
$messagingCreateGroupUrl = '';
$messagingThreadApi = 'messaging-thread.php';
$messagingActionUrl = 'messaging-action.php';
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

        $groupParam = isset($_GET['group']) ? (int) $_GET['group'] : 0;
        if ($groupParam > 0 && group_messaging_user_in_group($conn, $groupParam, $messagingViewerId)) {
            $messagingMode = 'group';
            $messagingActiveGroupId = $groupParam;
            $messagingActivePeer = null;
            $messagingGroupMeta = group_messaging_get_group_meta($conn, $groupParam, $messagingViewerId);
            $messagingGroupThread = group_messaging_fetch_messages($conn, $groupParam, $messagingViewerId);
        }
    }

    if ($messagingMode === 'direct') {
        if ($messagingAvailable && $messagingActivePeer !== null && $messagingActivePeer !== '') {
            $messagingThread = internal_messaging_fetch_thread($conn, $messagingViewerId, $messagingActivePeer);
        }
    }
} catch (Throwable $e) {
    error_log('guard/inbox messaging: ' . $e->getMessage());
}

require_once APP_ROOT . '/includes/messaging_unread.php';
$guardInboxPageUnread = messaging_unread_total($conn, $company_id, auth_user_role());

$messagingHideSidebarHead = true;

$guardNavActive = 'inbox';
guard_layout_head('Inbox');
?>
        <div class="guard-section-stack guard-inbox-page" data-guard-page="inbox">
        <div class="inbox-messaging-solo">
            <?php require __DIR__ . '/../includes/messaging_board.php'; ?>
        </div>
        </div>
<?php
guard_layout_end();
