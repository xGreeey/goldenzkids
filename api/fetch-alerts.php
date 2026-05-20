<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json; charset=UTF-8');

if (!auth_is_logged_in()) {
    echo json_encode(['count' => 0]);
    exit();
}

$alert_count = 0;
$isAdminArea = auth_role_is(AUTH_ROLE_ADMIN, AUTH_ROLE_SUPERADMIN);
$company_id = (string) ($_SESSION['company_id'] ?? '');

auth_session_release_lock();

if ($isAdminArea) {
    $query = db_query($conn, "SELECT COUNT(*) AS total FROM dgd WHERE Status = 'Pending'");
    if ($query) {
        $row = $query->fetch_assoc();
        $alert_count = (int) ($row['total'] ?? 0);
    }
} else {
    $query = db_query(
        $conn,
        'SELECT COUNT(*) AS total FROM memo_recipients WHERE Company_ID = ? AND is_read = 0',
        's',
        [$company_id]
    );
    if ($query) {
        $row = $query->fetch_assoc();
        $alert_count = (int) ($row['total'] ?? 0);
    }
}

echo json_encode(['count' => $alert_count]);
