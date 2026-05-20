<?php
require_once __DIR__ . '/../config/app.php';
require_once APP_ROOT . '/includes/auth_layout.php';

$otp_Err = null;

if (empty($_SESSION['password_reset_email'])) {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        header('Location: ' . app_url('auth/forgot-access-code.php'));
        exit();
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['otp'])) {
    csrf_verify();

    $otp = trim((string) ($_POST['otp'] ?? ''));

    if ($otp === '') {
        $otp_Err = 'Please enter your verification code.';
    } elseif (!preg_match('/^[0-9]{6}$/', $otp)) {
        $otp_Err = 'Please check your verification code.';
    } elseif (empty($_SESSION['password_reset_otp']) || empty($_SESSION['password_reset_email'])) {
        $otp_Err = 'Your session expired. Please start again from forgot password.';
    } elseif (time() > (int) ($_SESSION['password_reset_otp_expires'] ?? 0)) {
        $otp_Err = 'Your session expired. Please request a new reset link.';
    } elseif (!hash_equals((string) $_SESSION['password_reset_otp'], $otp)) {
        $otp_Err = 'Invalid verification code. Please try again.';
    } else {
        $email = (string) $_SESSION['password_reset_email'];
        $stmt = $conn->prepare('SELECT Company_ID FROM users WHERE Email = ? LIMIT 1');
        if (!$stmt) {
            $otp_Err = 'Unable to process verification right now. Please try again.';
            $match = false;
        } else {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $match = $stmt->get_result();
            $stmt->close();
        }

        if (!$match || $match->num_rows !== 1) {
            $otp_Err = 'We could not verify this reset request. Please start again.';
        } else {
            $row = $match->fetch_assoc();
            $_SESSION['password_reset_company_id'] = (string) ($row['Company_ID'] ?? '');
            $_SESSION['password_reset_verified'] = 1;
            unset($_SESSION['password_reset_otp'], $_SESSION['password_reset_otp_expires']);
            header('Location: ' . app_url('auth/reset-password.php'));
            exit();
        }
    }
}

$isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1'], true)
    || str_contains($_SERVER['HTTP_HOST'] ?? '', 'localhost');
if ($isLocal) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}

auth_page_head('Verify identity', 'Verify your identity to reset your portal password.');
auth_body_start();
auth_main_open();
auth_module_open();
auth_card_back_link(app_url('auth/forgot-access-code.php'), 'Back');
auth_card_intro(
    'Verify identity',
    'Enter the verification code sent to your email.'
);

if (!empty($otp_Err)) {
    auth_alert_error($otp_Err);
}
?>
<form class="login-form" action="" method="POST" novalidate>
    <?= csrf_field() ?>
                <div class="input-group">
                    <label class="input-label" for="verification_code">Verification code</label>
                    <input
                        type="password"
                        name="otp"
                        id="verification_code"
                        class="form-input no-toggle"
                        placeholder="Verification code"
                        autocomplete="one-time-code"
                        required
                    >
                </div>

                <button type="submit" class="btn-signin">Verify</button>
</form>
<?php

auth_module_close();
auth_main_close();
auth_page_end();
