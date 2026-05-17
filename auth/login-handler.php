<?php

$company_id = '';
$pin = '';

$company_idErr = null;
$pin_Err = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_id = trim($_POST['company_id'] ?? '');
    $pin = trim($_POST['pin'] ?? '');

    $d = strtotime('now');
    $time_of_event = date('Y-m-d H:i:s', $d);

    if (!preg_match('/^ABC-2[0-9]{3}-[0-9]{4}$/i', $company_id)) {
        $company_idErr = 'Enter a valid employee ID (example: ABC-2001-0042).';
    }

    if (!preg_match('/^[0-9]{6}$/', $pin)) {
        $pin_Err = 'Access code must be exactly 6 digits.';
    }

    if ($company_idErr === null && $pin_Err === null) {
        $company_id = strtoupper($company_id);

        $stmt = $conn->prepare('SELECT Pin, Designation FROM users WHERE Company_ID = ? LIMIT 1');
        $stmt->bind_param('s', $company_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        $storedPin = '';
        if ($row) {
            // Pin column is int(6) in MySQL — compare as strings to avoid type mismatch
            $storedPin = str_pad((string)($row['Pin'] ?? ''), 6, '0', STR_PAD_LEFT);
        }

        if (!$row || !hash_equals($storedPin, $pin)) {
            $error = 'Invalid employee ID or access code. Please try again.';
        } else {
            $designation = strtoupper(trim($row['Designation'] ?? ''));

            if ($designation === 'GUARD') {
                $log = $conn->prepare(
                    'INSERT INTO recording (Company_ID, Designation, Event, Time_Of_Event) VALUES (?, ?, ?, ?)'
                );
                $event = 'LOGIN';
                $role = 'GUARD';
                $log->bind_param('ssss', $company_id, $role, $event, $time_of_event);
                $insertOk = $log->execute();
                $log->close();

                if ($insertOk) {
                    $_SESSION['company_id'] = $company_id;
                    $_SESSION['designation'] = 'GUARD';
                    header('Location: ' . app_url('guard/portal.php'));
                    exit();
                }
                $error = 'Unable to complete sign-in. Please contact support.';
            } elseif ($designation === 'ADMIN') {
                $log = $conn->prepare(
                    'INSERT INTO recording (Company_ID, Designation, Event, Time_Of_Event) VALUES (?, ?, ?, ?)'
                );
                $event = 'LOGIN';
                $role = 'ADMIN';
                $log->bind_param('ssss', $company_id, $role, $event, $time_of_event);
                $insertOk = $log->execute();
                $log->close();

                if ($insertOk) {
                    $_SESSION['company_id'] = $company_id;
                    $_SESSION['designation'] = 'ADMIN';
                    header('Location: ' . app_url('admin/dashboard.php'));
                    exit();
                }
                $error = 'Unable to complete sign-in. Please contact support.';
            } else {
                $error = 'Invalid employee ID or access code. Please try again.';
            }
        }
    }
}
