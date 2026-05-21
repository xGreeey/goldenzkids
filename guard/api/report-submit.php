<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/guard_portal.php';

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

$uploadRoot = APP_ROOT . '/uploads/guard/' . preg_replace('/[^A-Za-z0-9_-]/', '', $companyId);
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

$pathEnc = guard_portal_encrypt('uploads/guard/' . basename($uploadRoot) . '/' . $scanName, (string) $master_key, (string) $cipher_algo);
if ($pathEnc === null) {
    @unlink($scanPath);
    echo json_encode(['ok' => false, 'error' => 'Could not secure file path.']);
    exit;
}

$time = date('Y-m-d H:i:s');
$iv = $encEst['iv'];

$ok = db_execute(
    $conn,
    'INSERT INTO dgd (Company_ID, Establishment, Template_Path, Template, Time_of_Report, AI_Extracted_Text, iv, Status)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
    'ssssssss',
    [
        $companyId,
        $encEst['cipher'],
        $pathEnc['cipher'],
        $templateName,
        $time,
        '',
        $iv,
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

if (db_table_exists($conn, 'guard_report_evidence') && isset($_FILES['evidence'])) {
    $evFiles = $_FILES['evidence'];
    $names = is_array($evFiles['name']) ? $evFiles['name'] : [$evFiles['name']];
    $tmps = is_array($evFiles['tmp_name']) ? $evFiles['tmp_name'] : [$evFiles['tmp_name']];
    $errs = is_array($evFiles['error']) ? $evFiles['error'] : [$evFiles['error']];
    $metas = $_POST['evidence_meta'] ?? [];
    if (!is_array($metas)) {
        $metas = [$metas];
    }

    foreach ($names as $i => $origName) {
        if (($errs[$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            continue;
        }
        $eext = pathinfo((string) $origName, PATHINFO_EXTENSION);
        $eext = $eext !== '' ? '.' . preg_replace('/[^A-Za-z0-9]/', '', $eext) : '.jpg';
        $evFile = 'evidence_' . $reportNumber . '_' . $i . $eext;
        $dest = $uploadRoot . '/' . $evFile;
        if (!move_uploaded_file((string) $tmps[$i], $dest)) {
            continue;
        }
        $meta = (string) ($metas[$i] ?? '');
        $lat = null;
        $lng = null;
        if (preg_match('/GPS\s+(-?\d+\.?\d*),\s*(-?\d+\.?\d*)/', $meta, $m)) {
            $lat = (float) $m[1];
            $lng = (float) $m[2];
        }
        db_execute(
            $conn,
            'INSERT INTO guard_report_evidence (report_number, company_id, file_name, gps_lat, gps_lng, captured_at)
             VALUES (?, ?, ?, ?, ?, ?)',
            'sisdds',
            [$reportNumber, $companyId, $evFile, $lat, $lng, date('Y-m-d H:i:s')]
        );
    }
}

echo json_encode([
    'ok' => true,
    'message' => 'Report submitted. Status: Pending review.',
    'report_number' => $reportNumber,
]);
