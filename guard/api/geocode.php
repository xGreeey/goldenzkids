<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/google_maps.php';

if (!auth_user_can('guard.reports.submit')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Forbidden']);
    exit;
}

$lat = isset($_GET['lat']) ? (float) $_GET['lat'] : (float) ($_POST['lat'] ?? 0);
$lng = isset($_GET['lng']) ? (float) $_GET['lng'] : (float) ($_POST['lng'] ?? 0);

if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 || ($lat === 0.0 && $lng === 0.0)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid coordinates']);
    exit;
}

$result = geocode_coordinates($lat, $lng);
echo json_encode($result, JSON_UNESCAPED_UNICODE);
