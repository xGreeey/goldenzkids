<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once APP_ROOT . '/includes/group_messaging.php';
require_once APP_ROOT . '/includes/messaging_ajax.php';

auth_require_permission('admin.messaging.send');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: ' . app_url('admin/inbox.php'));
    exit();
}

csrf_verify();

$creatorId = (string) ($_SESSION['company_id'] ?? '');
$groupName = trim((string) ($_POST['group_name'] ?? ''));
$memberIds = $_POST['member_ids'] ?? [];
if (!is_array($memberIds)) {
    $memberIds = [];
}

$redirect = 'inbox.php#messaging-board';

if ($groupName === '') {
    if (messaging_ajax_wants_json()) {
        messaging_ajax_json(['ok' => false, 'error' => 'Enter a name for the group chat.']);
    }
    redirect_with_alert('Enter a name for the group chat.', $redirect, 'warning');
}

if ($memberIds === []) {
    if (messaging_ajax_wants_json()) {
        messaging_ajax_json(['ok' => false, 'error' => 'Select at least one head guard for the group.']);
    }
    redirect_with_alert('Select at least one head guard for the group.', $redirect, 'warning');
}

$groupId = group_messaging_create_group($conn, $creatorId, $groupName, $memberIds);
if ($groupId === null) {
    if (messaging_ajax_wants_json()) {
        messaging_ajax_json(['ok' => false, 'error' => 'Group could not be created. Check the name and head guard selection.']);
    }
    redirect_with_alert('Group could not be created. Check the name and head guard selection.', $redirect, 'error');
}

if (messaging_ajax_wants_json()) {
    messaging_ajax_json([
        'ok' => true,
        'message' => 'Group chat created.',
        'title' => 'Success',
        'type' => 'success',
        'group_id' => $groupId,
        'group_name' => $groupName,
        'member_count' => count($memberIds) + 1,
    ]);
}

redirect_with_alert('Group chat created.', 'inbox.php?group=' . $groupId . '#messaging-board', 'success');
