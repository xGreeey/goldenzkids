<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once APP_ROOT . '/includes/guard_dad.php';

if (!auth_user_can('admin.dtr.view')) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Forbidden';
    exit;
}

if (!auth_is_logged_in()) {
    http_response_code(401);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Sign in required.';
    exit;
}

$dadId = (int) ($_GET['dad_id'] ?? $_GET['dad'] ?? 0);
if ($dadId <= 0) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Invalid DTR record.';
    exit;
}

$row = db_fetch_one($conn, 'SELECT * FROM guard_dad_submissions WHERE dad_id = ? LIMIT 1', 'i', [$dadId]);
if ($row === null) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'DTR record not found.';
    exit;
}

$record = guard_dad_map_row_for_admin($row);
if ($record === null) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'DTR record not found.';
    exit;
}

if (!guard_dad_has_ocr_export($record)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'No extracted text available for this record.';
    exit;
}

$actorId = (string) ($_SESSION['company_id'] ?? 'admin');
$result = guard_dad_send_ocr_protected_csv_export($conn, $record, $actorId);
if (!$result['ok']) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    echo (string) ($result['error'] ?? 'Export failed.');
    exit;
}
