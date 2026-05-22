<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/guard_portal.php';
require_once __DIR__ . '/../../includes/document_ai.php';
require_once __DIR__ . '/../../includes/guard_dad.php';
require_once __DIR__ . '/../../includes/guard_incident.php';
require_once __DIR__ . '/../../includes/guard_daily_activity.php';

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

$companyId = (string) ($_SESSION['company_id'] ?? '');
$establishment = guard_portal_assigned_post($conn, $companyId);
$reportType = trim((string) ($_POST['report_type'] ?? $_POST['template_name'] ?? ''));
if (!in_array($reportType, guard_portal_report_types(), true)) {
    echo json_encode(['ok' => false, 'error' => 'Please select a valid report type.']);
    exit;
}
$templateName = $reportType;

if ($establishment === '') {
    echo json_encode(['ok' => false, 'error' => 'No post is assigned to your guard profile. Contact your administrator.']);
    exit;
}

if (guard_daily_activity_is_report_type($reportType)) {
    $daResult = guard_daily_activity_handle_submit($conn, $companyId, $establishment, $reportType);
    echo json_encode($daResult);
    exit;
}

if (!isset($_FILES['report_scan']) || ($_FILES['report_scan']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    echo json_encode(['ok' => false, 'error' => 'Report scan image is required.']);
    exit;
}

global $master_key, $cipher_algo;

$encEst = guard_portal_encrypt($establishment, (string) $master_key, (string) $cipher_algo);
if ($encEst === null) {
    echo json_encode(['ok' => false, 'error' => 'Could not secure report data.']);
    exit;
}

$ivB64 = $encEst['iv'];
$ivBinary = base64_decode($ivB64, true);
if ($ivBinary === false || $ivBinary === '') {
    echo json_encode(['ok' => false, 'error' => 'Could not secure report data.']);
    exit;
}

$uploadRoot = APP_ROOT . '/uploads/guard/' . preg_replace('/[^A-Za-z0-9_-]/', '', $companyId);
$uploadsRelativePrefix = 'uploads/guard/' . basename($uploadRoot);
if (!is_dir($uploadRoot) && !mkdir($uploadRoot, 0755, true) && !is_dir($uploadRoot)) {
    echo json_encode(['ok' => false, 'error' => 'Upload directory unavailable.']);
    exit;
}

$scan = $_FILES['report_scan'];
$ext = pathinfo((string) $scan['name'], PATHINFO_EXTENSION);
$ext = $ext !== '' ? '.' . preg_replace('/[^A-Za-z0-9]/', '', $ext) : '.jpg';
$scanName = 'report_' . date('Ymd_His') . $ext;
$scanPath = $uploadRoot . '/' . $scanName;

if (!move_uploaded_file((string) $scan['tmp_name'], $scanPath)) {
    echo json_encode(['ok' => false, 'error' => 'Could not save report scan.']);
    exit;
}

$relativeScanPath = $uploadsRelativePrefix . '/' . $scanName;
$pathCipher = guard_portal_encrypt($relativeScanPath, (string) $master_key, (string) $cipher_algo, $ivBinary);
if ($pathCipher === null) {
    @unlink($scanPath);
    echo json_encode(['ok' => false, 'error' => 'Could not secure file path.']);
    exit;
}

$aiStored = '';
if (document_ai_is_configured()) {
    $ocr = document_ai_process_report_scan($scanPath, $reportType);
    if ($ocr['ok'] && isset($ocr['payload']) && is_array($ocr['payload'])) {
        try {
            $aiStored = document_ai_encode_stored($ocr['payload']);
        } catch (JsonException $e) {
            $aiStored = (string) ($ocr['payload']['formatted'] ?? '');
        }
    } else {
        error_log('Guard report OCR skipped: ' . ($ocr['error'] ?? 'unknown'));
    }
}

$aiCipher = $aiStored !== ''
    ? (openssl_encrypt($aiStored, (string) $cipher_algo, (string) $master_key, 0, $ivBinary) ?: '')
    : '';

$time = date('Y-m-d H:i:s');

$ok = db_execute(
    $conn,
    'INSERT INTO dgd (Company_ID, Establishment, Template_Path, Template, Time_of_Report, AI_Extracted_Text, iv, Status)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
    'ssssssss',
    [
        $companyId,
        $encEst['cipher'],
        $pathCipher['cipher'],
        $templateName,
        $time,
        $aiCipher,
        $ivB64,
        'Pending',
    ]
);

if (!$ok) {
    @unlink($scanPath);
    echo json_encode(['ok' => false, 'error' => 'Could not save report. Please try again.']);
    exit;
}

$reportNumber = db_last_insert_id($conn);

if (db_table_exists($conn, 'guard_duty_status')) {
    db_execute(
        $conn,
        'INSERT INTO guard_duty_status (company_id, duty_status) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE duty_status = VALUES(duty_status), updated_at = NOW()',
        'ss',
        [$companyId, 'on_report']
    );
}

$evidenceMeta = $_POST['evidence_meta'] ?? [];
if (!is_array($evidenceMeta)) {
    $evidenceMeta = $evidenceMeta !== '' ? [$evidenceMeta] : [];
}

$evidenceSaved = guard_portal_store_report_evidence(
    $conn,
    $reportNumber,
    $companyId,
    $uploadRoot,
    $uploadsRelativePrefix,
    $ivB64,
    (string) $master_key,
    (string) $cipher_algo,
    $evidenceMeta
);

$dadReference = null;
if (guard_dad_is_report_type($templateName)) {
    $structured = [];
    if ($aiStored !== '') {
        $decoded = document_ai_decode_stored($aiStored);
        $structured = is_array($decoded['structured'] ?? null) ? $decoded['structured'] : [];
    }

    $dadResult = guard_dad_create_submission(
        $conn,
        $companyId,
        $establishment,
        $reportNumber,
        $ivB64,
        $pathCipher['cipher'],
        $aiCipher !== '' ? $aiCipher : null,
        [
            'structured' => $structured,
            'sheet_latitude' => $_POST['sheet_latitude'] ?? null,
            'sheet_longitude' => $_POST['sheet_longitude'] ?? null,
            'sheet_accuracy_m' => $_POST['sheet_accuracy_m'] ?? null,
            'sheet_location_label' => (string) ($_POST['sheet_location_label'] ?? ''),
            'evidence_latitude' => $_POST['evidence_latitude'] ?? $_POST['submit_latitude'] ?? null,
            'evidence_longitude' => $_POST['evidence_longitude'] ?? $_POST['submit_longitude'] ?? null,
            'evidence_accuracy_m' => $_POST['evidence_accuracy_m'] ?? $_POST['submit_accuracy_m'] ?? null,
            'evidence_location_label' => (string) ($_POST['evidence_location_label'] ?? $_POST['location_label'] ?? ''),
            'submit_latitude' => $_POST['evidence_latitude'] ?? $_POST['submit_latitude'] ?? null,
            'submit_longitude' => $_POST['evidence_longitude'] ?? $_POST['submit_longitude'] ?? null,
            'submit_accuracy_m' => $_POST['evidence_accuracy_m'] ?? $_POST['submit_accuracy_m'] ?? null,
            'location_label' => (string) ($_POST['evidence_location_label'] ?? $_POST['location_label'] ?? ''),
        ]
    );

    if (!$dadResult['ok']) {
        echo json_encode(['ok' => false, 'error' => (string) ($dadResult['error'] ?? 'Could not register DTR record.')]);
        exit;
    }
    $dadReference = (string) ($dadResult['reference'] ?? '');
}

$incReference = null;
if (guard_incident_is_report_type($templateName)) {
    $structured = [];
    if ($aiStored !== '') {
        $decoded = document_ai_decode_stored($aiStored);
        $structured = is_array($decoded['structured'] ?? null) ? $decoded['structured'] : [];
    }

    $incResult = guard_incident_create_submission(
        $conn,
        $companyId,
        $establishment,
        $reportNumber,
        $ivB64,
        $pathCipher['cipher'],
        $aiCipher !== '' ? $aiCipher : null,
        [
            'structured' => $structured,
            'submit_latitude' => $_POST['evidence_latitude'] ?? $_POST['submit_latitude'] ?? null,
            'submit_longitude' => $_POST['evidence_longitude'] ?? $_POST['submit_longitude'] ?? null,
            'submit_accuracy_m' => $_POST['evidence_accuracy_m'] ?? $_POST['submit_accuracy_m'] ?? null,
            'location_label' => (string) ($_POST['evidence_location_label'] ?? $_POST['location_label'] ?? ''),
        ]
    );

    if (!$incResult['ok']) {
        echo json_encode(['ok' => false, 'error' => (string) ($incResult['error'] ?? 'Could not register incident report.')]);
        exit;
    }
    $incReference = (string) ($incResult['reference'] ?? '');
}

$message = guard_dad_is_report_type($templateName)
    ? 'Daily time record submitted. Reference: ' . ($dadReference ?? 'pending') . '.'
    : (guard_incident_is_report_type($templateName)
        ? 'Post incident submitted. Reference: ' . ($incReference ?? 'pending') . '. It will appear in Admin → Incident reports.'
        : 'Report submitted. Status: Pending review.');
if ($aiStored !== '') {
    $message .= ' Form text extracted via Document AI.';
}
if ($evidenceSaved > 0) {
    $message .= ' ' . $evidenceSaved . ' evidence photo(s) saved (encrypted).';
}

echo json_encode([
    'ok' => true,
    'message' => $message,
    'report_number' => $reportNumber,
    'evidence_saved' => $evidenceSaved,
    'ocr_applied' => $aiStored !== '',
    'dad_reference' => $dadReference,
    'incident_reference' => $incReference,
    'redirect' => guard_dad_is_report_type($templateName) ? 'submit-report.php?view=history' : null,
]);
