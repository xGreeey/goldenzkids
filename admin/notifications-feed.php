<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once APP_ROOT . '/includes/admin_notifications.php';

auth_require_role(AUTH_ROLE_ADMIN);

header('Content-Type: application/json; charset=UTF-8');

$adminId = (string) ($_SESSION['company_id'] ?? '');
$viewerRole = auth_user_role();

try {
    $items = admin_notifications_fetch($conn, $adminId, $viewerRole, 40);
    $payload = [];
    foreach ($items as $item) {
        $payload[] = [
            'id' => $item['id'],
            'type' => $item['type'],
            'title' => $item['title'],
            'body' => $item['body'],
            'href' => $item['href'],
            'at' => $item['at'],
            'time_label' => $item['time_label'],
            'icon' => $item['icon'],
        ];
    }

    echo json_encode([
        'ok' => true,
        'count' => count($payload),
        'items' => $payload,
    ], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    error_log('admin/notifications-feed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not load notifications.']);
}
