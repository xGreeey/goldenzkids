<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once APP_ROOT . '/includes/admin_weekly_activity_reports.php';

auth_require_permission('admin.reports.view');

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed.'], JSON_THROW_ON_ERROR);
    exit;
}

csrf_verify();

$result = admin_weekly_activity_war_preview($_POST);
echo json_encode($result, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
