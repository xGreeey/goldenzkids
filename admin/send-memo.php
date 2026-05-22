<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/portal_audit.php';

auth_require_permission('admin.memo.send');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: ' . app_url('admin/announcements.php'));
    exit();
}

$senderId = (string) ($_SESSION['company_id'] ?? '');
$distributionType = trim((string) ($_POST['distribution_type'] ?? ''));
$targetGuard = trim((string) ($_POST['target_guard'] ?? ''));
$memoType = trim((string) ($_POST['memo_type'] ?? ''));
$bodyText = xss_sanitize_plaintext(trim((string) ($_POST['content'] ?? '')), 16000);

if ($senderId === '' || $distributionType === '' || $memoType === '' || $bodyText === '') {
    redirect_with_alert('Please complete all required memo fields.', 'announcements.php');
}

if ($distributionType === 'targeted' && $targetGuard === '') {
    redirect_with_alert('Select a recipient for a targeted memo.', 'announcements.php');
}

$recipientIds = [];
if ($distributionType === 'broadcast') {
    $rows = db_fetch_all(
        $conn,
        "SELECT DISTINCT Company_ID FROM guards WHERE Company_ID IS NOT NULL AND Company_ID != ''"
    );
    foreach ($rows as $row) {
        $recipientIds[] = (string) $row['Company_ID'];
    }
    if ($recipientIds === []) {
        redirect_with_alert('No guards found on the roster for broadcast.', 'announcements.php');
    }
} else {
    $recipientIds[] = strtoupper($targetGuard);
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

    $recipientSummary = $distributionType === 'broadcast'
        ? 'Broadcast to ' . count($recipientIds) . ' guard(s)'
        : 'To ' . $recipientIds[0];
    portal_audit_log(
        $conn,
        'MEMO_SENT',
        $recipientSummary . '; category: ' . $memoType,
        $distributionType === 'targeted' ? $recipientIds[0] : null,
        $senderId,
        auth_user_role()
    );

    redirect_with_alert('Memo sent successfully! (Nasend na ang memo!)', 'announcements.php');
} catch (Throwable $e) {
    $conn->rollBack();
    error_log('send-memo: ' . $e->getMessage());
    redirect_with_alert('Memo could not be sent. Please try again.', 'announcements.php');
}
