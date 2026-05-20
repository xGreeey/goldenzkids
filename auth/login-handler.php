<?php
declare(strict_types=1);

$company_id = '';
$pin = '';

$company_idErr = null;
$pin_Err = null;
$error = null;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();

    $company_id = trim($_POST['company_id'] ?? '');
    $pin = trim($_POST['pin'] ?? '');

    $time_of_event = date('Y-m-d H:i:s');

    if ($company_id === '') {
        $company_idErr = 'Please enter your username.';
    } elseif (!preg_match('/^ABC-2[0-9]{3}-[0-9]{4}$/i', $company_id)) {
        $company_idErr = 'Please check your username.';
    }

    if ($pin === '') {
        $pin_Err = 'Please enter your password.';
    } elseif (!preg_match('/^[0-9]{6}$/', $pin)) {
        $pin_Err = 'Please check your password.';
    }

    if ($company_idErr === null && $pin_Err === null) {
        $company_id = strtoupper($company_id);

        $user = auth_find_user_by_company_id($conn, $company_id);
        $authenticated = false;
        $permissions = [];

        if ($user !== null && auth_verify_password($pin, $user['password_hash'])) {
            $authenticated = true;
            $permissions = auth_permissions_for_role($user['role']);

            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                $newHash = auth_hash_password($pin);
                db_execute(
                    $conn,
                    'UPDATE users SET password_hash = ?, password_changed_at = NOW() WHERE Company_ID = ?',
                    'ss',
                    [$newHash, $company_id]
                );
            }
        } else {
            $legacy = auth_attempt_legacy_login($conn, $company_id, $pin);
            if ($legacy !== null) {
                $authenticated = true;
                $user = $legacy['user'];
                $permissions = $legacy['permissions'];
            } elseif ($user !== null) {
                auth_record_failed_login($conn, $company_id);
            }
        }

        if (!$authenticated || $user === null) {
            $error = 'Invalid username or password. Please try again.';
        } else {
            $role = auth_normalize_role($user['role']);
            $roleLabel = auth_role_label_for_recording($role);

            $log = $conn->prepare(
                'INSERT INTO recording (Company_ID, Designation, Event, Time_Of_Event) VALUES (?, ?, ?, ?)'
            );
            $event = 'LOGIN';
            $log->bind_param('ssss', $company_id, $roleLabel, $event, $time_of_event);
            $insertOk = $log->execute();
            $log->close();

            if (!$insertOk) {
                error_log('Login audit insert failed for ' . $company_id . ': ' . $conn->error);
            }

            auth_login_session($user, $permissions);
            auth_record_login($conn, $company_id);
            header('Location: ' . auth_login_redirect_url($role));
            exit();
        }
    }
}
