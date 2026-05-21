<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once APP_ROOT . '/includes/group_messaging.php';
require_once APP_ROOT . '/includes/messaging_ajax.php';

auth_require_permission('guard.inbox.view');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: ' . app_url('guard/inbox.php'));
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
    if (messaging_ajax_wants_json()) {
        messaging_ajax_json(['ok' => false, 'error' => 'Please enter a message before sending.']);
    }
    redirect_with_alert('Please enter a message before sending.', $redirect);
}

if (!group_messaging_send($conn, $groupId, $senderId, $body)) {
    if (messaging_ajax_wants_json()) {
        messaging_ajax_json(['ok' => false, 'error' => 'Message could not be sent.']);
    }
    redirect_with_alert('Message could not be sent. Check the group and try again.', $redirect);
}

if (messaging_ajax_wants_json()) {
    messaging_ajax_json([
        'ok' => true,
        'message' => [
            'body_text' => $body,
            'is_mine' => true,
            'sender_label' => '',
            'created_at' => date('Y-m-d H:i:s'),
            'time_label' => messaging_ajax_format_time(date('Y-m-d H:i:s')),
        ],
    ]);
}

redirect_with_alert('Message sent.', $redirect);
