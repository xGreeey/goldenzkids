<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/portal_audit.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();
}

$company_id = (string) ($_SESSION['company_id'] ?? '');
$role = auth_user_role();
$logging_out = false;
if ($company_id !== '') {
    portal_audit_auth_event($conn, 'LOGOUT', $company_id, $role);
    $logging_out = true;
}

if ($logging_out || $company_id !== '') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    header('Location: ' . app_url('index.php'));
    exit();
}

http_response_code(500);
exit('Logout failed. Please try again.');
