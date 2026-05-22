<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once APP_ROOT . '/includes/internal_messaging.php';
require_once APP_ROOT . '/includes/messaging_ajax.php';

auth_require_permission('admin.messaging.send');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: ' . app_url('admin/inbox.php'));
    exit();
}

csrf_verify();

$senderId = (string) ($_SESSION['company_id'] ?? '');
$recipientId = trim((string) ($_POST['recipient_id'] ?? ''));
$returnPeer = trim((string) ($_POST['return_peer'] ?? $recipientId));
$body = trim((string) ($_POST['body'] ?? ''));

if ($body === '' || $recipientId === '') {
    if (messaging_ajax_wants_json()) {
        messaging_ajax_json(['ok' => false, 'error' => 'Please enter a message before sending.']);
    }
    redirect_with_alert('Please enter a message before sending.', 'inbox.php');
}

$messageId = internal_messaging_send($conn, $senderId, auth_user_role(), $recipientId, $body);
if ($messageId < 1) {
    if (messaging_ajax_wants_json()) {
        messaging_ajax_json(['ok' => false, 'error' => 'Message could not be sent.']);
    }
    redirect_with_alert('Message could not be sent. Check the recipient and try again.', 'inbox.php');
}

if (messaging_ajax_wants_json()) {
    messaging_ajax_json([
        'ok' => true,
        'message' => messaging_ajax_build_sent_message($body, $messageId),
    ]);
}

$redirect = 'inbox.php';
if ($returnPeer !== '') {
    $redirect .= '?peer=' . rawurlencode($returnPeer) . '#messaging-board';
}

redirect_with_alert('Message sent.', $redirect);
