<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once APP_ROOT . '/includes/guard_daily_activity.php';

auth_require_permission('admin.reports.view');

$daId = (int) ($_GET['da'] ?? 0);
$evidenceId = (int) ($_GET['ev'] ?? 0);
if ($daId <= 0 || $evidenceId <= 0) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Invalid request.';
    exit;
}

guard_daily_activity_stream_evidence($conn, $daId, $evidenceId);
