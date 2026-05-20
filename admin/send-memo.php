<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

auth_require_permission('admin.memo.send');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: ' . app_url('admin/inbox.php'));
    exit();
}

csrf_verify();

$senderId = (string) ($_SESSION['company_id'] ?? '');
$distributionType = trim((string) ($_POST['distribution_type'] ?? ''));
$targetGuard = trim((string) ($_POST['target_guard'] ?? ''));
$memoType = trim((string) ($_POST['memo_type'] ?? ''));
$bodyText = trim((string) ($_POST['content'] ?? ''));

if ($senderId === '' || $distributionType === '' || $memoType === '' || $bodyText === '') {
    redirect_with_alert('Please complete all required memo fields.', 'inbox.php');
}

if ($distributionType === 'targeted' && $targetGuard === '') {
    redirect_with_alert('Select a recipient for a targeted memo.', 'inbox.php');
}

$recipientIds = [];
if ($distributionType === 'broadcast') {
    $guards = $conn->query(
        "SELECT DISTINCT Company_ID FROM guards WHERE Company_ID IS NOT NULL AND Company_ID != ''"
    );
    if ($guards) {
        while ($row = $guards->fetch_assoc()) {
            $recipientIds[] = (string) $row['Company_ID'];
        }
    }
    if ($recipientIds === []) {
        redirect_with_alert('No guards found on the roster for broadcast.', 'inbox.php');
    }
} else {
    $recipientIds[] = strtoupper($targetGuard);
}

$conn->begin_transaction();

try {
    $stmtMemo = $conn->prepare(
        'INSERT INTO memos (Company_ID, Distribution_Protocol, Category, Body_Text) VALUES (?, ?, ?, ?)'
    );
    if (!$stmtMemo) {
        throw new RuntimeException('Could not prepare memo insert.');
    }

    $stmtMemo->bind_param('ssss', $senderId, $distributionType, $memoType, $bodyText);
    if (!$stmtMemo->execute()) {
        throw new RuntimeException('Memo insert failed.');
    }

    $memoId = (int) $conn->insert_id;
    $stmtMemo->close();

    $stmtRecipient = $conn->prepare(
        'INSERT INTO memo_recipients (Memo_ID, Company_ID, is_read) VALUES (?, ?, 0)'
    );
    if (!$stmtRecipient) {
        throw new RuntimeException('Could not prepare recipient insert.');
    }

    foreach ($recipientIds as $guardId) {
        $stmtRecipient->bind_param('is', $memoId, $guardId);
        if (!$stmtRecipient->execute()) {
            throw new RuntimeException('Recipient insert failed for ' . $guardId);
        }
    }
    $stmtRecipient->close();

    $conn->commit();
    redirect_with_alert('Memo sent successfully! (Nasend na ang memo!)', 'inbox.php');
} catch (Throwable $e) {
    $conn->rollback();
    error_log('send-memo: ' . $e->getMessage());
    redirect_with_alert('Memo could not be sent. Please try again.', 'inbox.php');
}
