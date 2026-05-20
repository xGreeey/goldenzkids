<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

$email_Err = null;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();

    $guard_email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);

    if ($guard_email === false) {
        $email_Err = 'Please enter a valid email address.';
    } else {
        $email_exists_in_db = false;

        $stmt = $conn->prepare(
            'SELECT Email FROM users WHERE Email = ? LIMIT 1'
        );
        if ($stmt) {
            $stmt->bind_param('s', $guard_email);
            $stmt->execute();
            $result = $stmt->get_result();
            $email_exists_in_db = $result && $result->num_rows === 1;
            $stmt->close();
        }

        if ($email_exists_in_db) {
            // OTP generation and Mailjet delivery should be implemented here using env credentials.
            header('Location: ' . app_url('auth/enter-otp.php'));
            exit();
        }

        $email_Err = 'Email not found in the system.';
    }
}
