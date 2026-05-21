<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once APP_ROOT . '/includes/guard_dad.php';

auth_require_permission('admin.dad.view');

$dadId = (int) ($_GET['dad'] ?? 0);
if ($dadId <= 0) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Invalid request.';
    exit;
}

guard_dad_stream_scan($conn, $dadId);
