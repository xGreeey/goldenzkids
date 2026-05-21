<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/guard_portal.php';
require_once __DIR__ . '/../../includes/document_ai.php';

if (!auth_user_can('admin.reports.view')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Forbidden']);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    csrf_verify();
} catch (Throwable $e) {
    http_response_code(419);
    echo json_encode(['ok' => false, 'error' => 'Session expired. Refresh and try again.']);
    exit;
}

$guardId = trim((string) ($_POST['guard_id'] ?? ''));
$reportTime = trim((string) ($_POST['report_time'] ?? ''));
$reportType = trim((string) ($_POST['report_type'] ?? ''));

if ($guardId === '' || $reportTime === '') {
    echo json_encode(['ok' => false, 'error' => 'Missing report identifiers.']);
    exit;
}

$row = db_fetch_one(
    $conn,
    'SELECT Company_ID, Template, Template_Path, AI_Extracted_Text, iv
     FROM dgd WHERE Company_ID = ? AND Time_of_Report = ? LIMIT 1',
    'ss',
    [$guardId, $reportTime]
);

if ($row === null) {
    echo json_encode(['ok' => false, 'error' => 'Report not found.']);
    exit;
}

if ($reportType === '') {
    $reportType = guard_portal_report_type_label((string) ($row['Template'] ?? ''));
}

$iv = base64_decode((string) ($row['iv'] ?? ''), true) ?: '';
if ($iv === '') {
    echo json_encode(['ok' => false, 'error' => 'Report encryption metadata is missing.']);
    exit;
}

$encryptedPath = (string) ($row['Template_Path'] ?? '');
$relativePath = $iv !== ''
    ? (openssl_decrypt($encryptedPath, $cipher_algo, $master_key, 0, $iv) ?: '')
    : '';

if ($relativePath === '') {
    echo json_encode(['ok' => false, 'error' => 'Could not decrypt the scan file path.']);
    exit;
}

$absolutePath = APP_ROOT . '/' . ltrim(str_replace('\\', '/', $relativePath), '/');
$result = document_ai_process_report_scan($absolutePath, $reportType);

if (!$result['ok']) {
    echo json_encode(['ok' => false, 'error' => $result['error'] ?? 'OCR failed.']);
    exit;
}

$payload = $result['payload'] ?? [];
try {
    $stored = document_ai_encode_stored($payload);
} catch (JsonException $e) {
    echo json_encode(['ok' => false, 'error' => 'Could not encode OCR output.']);
    exit;
}

$encryptedAi = openssl_encrypt($stored, $cipher_algo, $master_key, 0, $iv);
if ($encryptedAi === false) {
    echo json_encode(['ok' => false, 'error' => 'Could not secure OCR output.']);
    exit;
}

$updated = db_execute(
    $conn,
    'UPDATE dgd SET AI_Extracted_Text = ? WHERE Company_ID = ? AND Time_of_Report = ?',
    'sss',
    [$encryptedAi, $guardId, $reportTime]
);

if (!$updated) {
    echo json_encode(['ok' => false, 'error' => 'OCR completed but could not be saved.']);
    exit;
}

echo json_encode([
    'ok' => true,
    'formatted' => (string) ($payload['formatted'] ?? ''),
    'raw' => (string) ($payload['raw'] ?? ''),
    'structured' => $payload['structured'] ?? [],
    'processed_at' => (string) ($payload['processed_at'] ?? ''),
]);
