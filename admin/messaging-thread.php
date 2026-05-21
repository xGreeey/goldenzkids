<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once APP_ROOT . '/includes/internal_messaging.php';
require_once APP_ROOT . '/includes/group_messaging.php';
require_once APP_ROOT . '/includes/messaging_ajax.php';

auth_require_permission('admin.inbox.manage');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    messaging_ajax_json(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$viewerId = (string) ($_SESSION['company_id'] ?? '');
$viewerRole = auth_user_role();
$peerId = trim((string) ($_GET['peer'] ?? ''));
$groupId = (int) ($_GET['group'] ?? 0);
$contacts = internal_messaging_list_contacts($conn, $viewerRole);

try {
    if ($groupId > 0) {
        $payload = messaging_ajax_build_group_payload(
            $conn,
            $viewerId,
            $viewerRole,
            $groupId,
            app_url('admin/send-group-message.php')
        );
    } elseif ($peerId !== '') {
        $payload = messaging_ajax_build_direct_payload(
            $conn,
            $viewerId,
            $viewerRole,
            $peerId,
            $contacts,
            app_url('admin/send-internal-message.php')
        );
    } else {
        messaging_ajax_json(['ok' => false, 'error' => 'Select a contact or group.'], 400);
    }

    $payload['csrf'] = csrf_token();
    messaging_ajax_json($payload);
} catch (Throwable $e) {
    error_log('admin/messaging-thread: ' . $e->getMessage());
    messaging_ajax_json(['ok' => false, 'error' => 'Could not load conversation.'], 500);
}
