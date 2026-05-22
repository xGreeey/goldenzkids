<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once APP_ROOT . '/includes/portal_audit.php';
require_once APP_ROOT . '/includes/memo_portal.php';

auth_require_permission('admin.memo.send');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: ' . app_url('admin/announcements.php'));
    exit();
}

csrf_verify();

$senderId = (string) ($_SESSION['company_id'] ?? '');
$distributionType = trim((string) ($_POST['distribution_type'] ?? ''));
$recipientScope = trim((string) ($_POST['recipient_scope'] ?? ''));
$targetGuard = trim((string) ($_POST['target_guard'] ?? ''));
$memoType = trim((string) ($_POST['memo_type'] ?? ''));
$bodyText = xss_sanitize_plaintext(trim((string) ($_POST['content'] ?? '')), 16000);

if ($senderId === '' || $memoType === '' || $bodyText === '') {
    redirect_with_alert('Please complete all required memo fields.', 'announcements.php');
}

if (!memo_portal_tables_ready($conn)) {
    redirect_with_alert('Memo tables are not set up. Run database migrations first.', 'announcements.php');
}

$recipientIds = [];
if ($recipientScope === 'head_guards' || $distributionType === 'broadcast') {
    $recipientIds = memo_portal_head_guard_recipient_ids($conn);
    if ($recipientIds === []) {
        redirect_with_alert('No active head guard accounts found to receive this memo.', 'announcements.php');
    }
    $distributionType = 'broadcast';
} elseif ($distributionType === 'targeted') {
    if ($targetGuard === '') {
        redirect_with_alert('Select a recipient for a targeted memo.', 'announcements.php');
    }
    $recipientIds[] = strtoupper($targetGuard);
} else {
    redirect_with_alert('Invalid memo delivery scope.', 'announcements.php');
}

$conn->beginTransaction();

try {
    if (!db_execute(
        $conn,
        'INSERT INTO memos (Company_ID, Distribution_Protocol, Category, Body_Text) VALUES (?, ?, ?, ?)',
        'ssss',
        [$senderId, $distributionType, $memoType, $bodyText]
    )) {
        throw new RuntimeException('Memo insert failed.');
    }

    $memoId = db_last_insert_id($conn);

    foreach ($recipientIds as $guardId) {
        if (!db_execute(
            $conn,
            'INSERT INTO memo_recipients (Memo_ID, Company_ID, is_read) VALUES (?, ?, 0)',
            'is',
            [$memoId, $guardId]
        )) {
            throw new RuntimeException('Recipient insert failed for ' . $guardId);
        }
    }

    $conn->commit();

    $recipientSummary = $recipientScope === 'head_guards' || count($recipientIds) > 1
        ? 'Broadcast to ' . count($recipientIds) . ' head guard(s)'
        : 'To ' . $recipientIds[0];
    portal_audit_log(
        $conn,
        'MEMO_SENT',
        $recipientSummary . '; category: ' . $memoType,
        count($recipientIds) === 1 ? $recipientIds[0] : null,
        $senderId,
        auth_user_role()
    );

    redirect_with_alert(
        'Memo published to all head guards. It will appear on Guard corner → Announcement.',
        'announcements.php'
    );
} catch (Throwable $e) {
    $conn->rollBack();
    error_log('send-memo: ' . $e->getMessage());
    redirect_with_alert('Memo could not be sent. Please try again.', 'announcements.php');
}
