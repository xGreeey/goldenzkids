<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/app.php';
require_once APP_ROOT . '/includes/guard_dad.php';

if (!auth_user_can('admin.dad.view')) {
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

$dadId = (int) ($_POST['dad_id'] ?? $_POST['dad'] ?? 0);
if ($dadId <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Invalid DAD record.']);
    exit;
}

$result = guard_dad_run_document_ai($conn, $dadId);
if (!$result['ok']) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => (string) ($result['error'] ?? 'OCR failed.')]);
    exit;
}

echo json_encode([
    'ok' => true,
    'formatted' => (string) ($result['formatted'] ?? ''),
    'raw' => (string) ($result['raw'] ?? ''),
    'structured' => $result['structured'] ?? [],
    'display_fields' => $result['display_fields'] ?? [],
]);
