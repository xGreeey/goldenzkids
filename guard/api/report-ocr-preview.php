<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/guard_portal.php';
require_once __DIR__ . '/../../includes/document_ai.php';
require_once __DIR__ . '/../../includes/guard_dad.php';
require_once __DIR__ . '/../../includes/guard_incident.php';

if (!auth_user_can('guard.reports.submit')) {
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

$reportType = trim((string) ($_POST['report_type'] ?? ''));
if (!in_array($reportType, [GUARD_DTR_REPORT_TYPE, GUARD_DTR_REPORT_TYPE_LEGACY, GUARD_INCIDENT_REPORT_TYPE, GUARD_INCIDENT_REPORT_TYPE_LEGACY, 'Incident'], true)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid report type for OCR preview.']);
    exit;
}

if (!isset($_FILES['report_scan']) || ($_FILES['report_scan']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    echo json_encode(['ok' => false, 'error' => 'Upload a report image first.']);
    exit;
}

$tmp = (string) ($_FILES['report_scan']['tmp_name'] ?? '');
if ($tmp === '' || !is_uploaded_file($tmp)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid upload.']);
    exit;
}

$previewDir = APP_ROOT . '/uploads/guard/_ocr_preview';
if (!is_dir($previewDir) && !mkdir($previewDir, 0755, true) && !is_dir($previewDir)) {
    echo json_encode(['ok' => false, 'error' => 'Preview storage unavailable.']);
    exit;
}

$ext = pathinfo((string) ($_FILES['report_scan']['name'] ?? ''), PATHINFO_EXTENSION);
$ext = $ext !== '' ? '.' . preg_replace('/[^A-Za-z0-9]/', '', $ext) : '.jpg';
$dest = $previewDir . '/preview_' . bin2hex(random_bytes(8)) . $ext;
if (!move_uploaded_file($tmp, $dest)) {
    echo json_encode(['ok' => false, 'error' => 'Could not stage file for OCR.']);
    exit;
}

$result = document_ai_process_report_scan($dest, $reportType);
@unlink($dest);

if (!$result['ok']) {
    echo json_encode([
        'ok' => false,
        'error' => $result['error'] ?? 'Document AI could not read this image.',
        'configured' => document_ai_is_configured(),
    ]);
    exit;
}

$payload = $result['payload'] ?? [];
$structured = is_array($payload['structured'] ?? null) ? $payload['structured'] : [];

echo json_encode([
    'ok' => true,
    'formatted' => (string) ($payload['formatted'] ?? ''),
    'raw' => (string) ($payload['raw'] ?? ''),
    'structured' => $structured,
    'processed_at' => (string) ($payload['processed_at'] ?? ''),
    'dad_fields' => guard_dad_is_report_type($reportType)
        ? guard_dad_fields_from_ocr($structured, guard_portal_assigned_post($conn, (string) ($_SESSION['company_id'] ?? '')))
        : null,
    'extraction' => guard_incident_is_report_type($reportType)
        ? document_ai_incident_extraction_json($structured)
        : null,
    'assigned_post' => guard_incident_is_report_type($reportType)
        ? guard_portal_assigned_post($conn, (string) ($_SESSION['company_id'] ?? ''))
        : '',
]);
