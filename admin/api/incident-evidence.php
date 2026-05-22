<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once APP_ROOT . '/includes/guard_incident.php';

auth_require_permission('admin.reports.view');

$incId = (int) ($_GET['inc'] ?? 0);
$evidenceId = (int) ($_GET['ev'] ?? 0);
if ($incId <= 0 || $evidenceId <= 0) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Invalid request.';
    exit;
}

guard_incident_stream_evidence($conn, $incId, $evidenceId);
