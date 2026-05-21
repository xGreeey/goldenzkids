<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once APP_ROOT . '/includes/group_messaging.php';

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
    redirect_with_alert('Enter a name for the group chat.', $redirect);
}

if ($memberIds === []) {
    redirect_with_alert('Select at least one head guard for the group.', $redirect);
}

$groupId = group_messaging_create_group($conn, $creatorId, $groupName, $memberIds);
if ($groupId === null) {
    redirect_with_alert('Group could not be created. Check the name and head guard selection.', $redirect);
}

redirect_with_alert('Group chat created.', 'inbox.php?group=' . $groupId . '#messaging-board');
