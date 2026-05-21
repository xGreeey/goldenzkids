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

$senderId = (string) ($_SESSION['company_id'] ?? '');
$groupId = (int) ($_POST['group_id'] ?? 0);
$body = trim((string) ($_POST['body'] ?? ''));

$redirect = 'inbox.php';
if ($groupId > 0) {
    $redirect .= '?group=' . $groupId . '#messaging-board';
}

if ($body === '' || $groupId < 1) {
    redirect_with_alert('Please enter a message before sending.', $redirect);
}

if (!group_messaging_send($conn, $groupId, $senderId, $body)) {
    redirect_with_alert('Message could not be sent. Check the group and try again.', $redirect);
}

redirect_with_alert('Message sent.', $redirect);
