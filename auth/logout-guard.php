<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();
}

$time_of_event = date('Y-m-d H:i:s');
$company_id = (string) ($_SESSION['company_id'] ?? '');
$role = auth_role_label_for_recording(auth_user_role());
$event = 'LOGOUT';

$logging_out = false;
if ($company_id !== '') {
    $logging_out = db_execute(
        $conn,
        'INSERT INTO recording (Company_ID, Designation, Event, Time_Of_Event) VALUES (?, ?, ?, ?)',
        'ssss',
        [$company_id, $role, $event, $time_of_event]
    );
} else {
    $logging_out = db_execute(
        $conn,
        'INSERT INTO recording (Designation, Event, Time_Of_Event) VALUES (?, ?, ?)',
        'sss',
        [$role, $event, $time_of_event]
    );
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
