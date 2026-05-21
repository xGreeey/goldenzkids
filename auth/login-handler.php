<?php
declare(strict_types=1);

$company_id = '';
$password = '';

$company_idErr = null;
$passwordErr = null;
$error = null;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();

    $company_id = trim($_POST['company_id'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $time_of_event = date('Y-m-d H:i:s');

    if ($company_id === '') {
        $company_idErr = 'Please enter your username.';
    } elseif (!auth_login_identifier_valid($company_id)) {
        $company_idErr = 'Enter your username or guard ID (e.g. ABC-2024-0021).';
    }

    if (auth_guard_company_id_valid($company_id)) {
        $company_id = strtoupper($company_id);
    }

    if ($password === '') {
        $passwordErr = 'Please enter your password.';
    }

    if ($company_idErr === null && $passwordErr === null) {
        $user = auth_find_user_by_company_id($conn, $company_id);
        $authenticated = false;
        $permissions = [];

        if ($user !== null && auth_verify_password($password, $user['password_hash'])) {
            $authenticated = true;
            $user['role'] = auth_resolve_role_at_login($conn, $user);
            $permissions = auth_permissions_for_role((int) $user['role']);

            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                $newHash = auth_hash_password($password);
                db_execute(
                    $conn,
                    'UPDATE users SET password_hash = ? WHERE Company_ID = ?',
                    'ss',
                    [$newHash, $company_id]
                );
            }
        } elseif ($user !== null) {
            auth_record_failed_login($conn, $company_id);
        }

        if (!$authenticated || $user === null) {
            if (auth_is_deactivated_account($conn, $company_id)) {
                $error = 'This account is deactivated, please contact administrator';
            } else {
                $error = 'Invalid username or password. Please try again.';
            }
        } else {
            $role = (int) $user['role'];
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
