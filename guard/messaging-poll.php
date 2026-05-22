<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once APP_ROOT . '/includes/internal_messaging.php';
require_once APP_ROOT . '/includes/group_messaging.php';
require_once APP_ROOT . '/includes/messaging_ajax.php';

auth_require_permission('guard.inbox.view');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    messaging_ajax_json(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$viewerId = (string) ($_SESSION['company_id'] ?? '');
$viewerRole = auth_user_role();
$peerId = trim((string) ($_GET['peer'] ?? ''));
$groupId = (int) ($_GET['group'] ?? 0);
$afterMessageId = (int) ($_GET['after'] ?? 0);

$activePeer = $peerId !== '' ? $peerId : null;
$activeGroup = $groupId > 0 ? $groupId : null;

try {
    $payload = messaging_ajax_build_poll_payload(
        $conn,
        $viewerId,
        $viewerRole,
        $activePeer,
        $activeGroup,
        $afterMessageId
    );
    messaging_ajax_json($payload);
} catch (Throwable $e) {
    error_log('guard/messaging-poll: ' . $e->getMessage());
    messaging_ajax_json(['ok' => false, 'error' => 'Could not refresh inbox.'], 500);
}
