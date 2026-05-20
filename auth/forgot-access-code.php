<?php
require_once __DIR__ . '/../config/app.php';
require_once APP_ROOT . '/includes/auth_layout.php';

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

$email_Err = null;
$email = '';

/**
 * Send password-reset OTP email to the employee.
 */
function send_password_reset_otp_email(string $recipientEmail, string $otpCode): bool
{
    $smtpUser = trim((string) ($_ENV['EMAIL'] ?? getenv('EMAIL') ?? ''));
    $smtpPass = trim((string) ($_ENV['APP_PASSWORD'] ?? getenv('APP_PASSWORD') ?? ''));

    if ($smtpUser === '' || $smtpPass === '') {
        error_log('Password reset email failed: EMAIL/APP_PASSWORD missing in .env');
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUser;
        $mail->Password = $smtpPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom($smtpUser, app_agency_name());
        $mail->addAddress($recipientEmail);

        $mail->isHTML(true);
        $mail->Subject = 'Your Password Reset Verification Code';
        $mail->Body = '<p>Hello,</p>'
            . '<p>Your verification code is: <strong style="font-size:18px; letter-spacing:2px;">'
            . e($otpCode)
            . '</strong></p>'
            . '<p>This code expires in 15 minutes.</p>'
            . '<p>If you did not request this, please ignore this email.</p>';
        $mail->AltBody = "Your verification code is {$otpCode}. It expires in 15 minutes.";

        return $mail->send();
    } catch (Exception $exception) {
        error_log('Password reset email failed: ' . $exception->getMessage());
        return false;
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();

    $email = trim((string) ($_POST['email'] ?? ''));
    $guard_email = filter_var($email, FILTER_VALIDATE_EMAIL);

    if ($guard_email === false) {
        $email_Err = 'Please enter a valid email address.';
    } else {
        $email_exists_in_db = false;

        $stmt = $conn->prepare('SELECT Email FROM users WHERE Email = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('s', $guard_email);
            $stmt->execute();
            $result = $stmt->get_result();
            $email_exists_in_db = $result && $result->num_rows === 1;
            $stmt->close();
        }

        if ($email_exists_in_db) {
            $otpCode = (string) random_int(100000, 999999);
            $_SESSION['password_reset_email'] = $guard_email;
            $_SESSION['password_reset_otp'] = $otpCode;
            $_SESSION['password_reset_otp_expires'] = time() + 900;
            $_SESSION['password_reset_requested_at'] = time();
            unset($_SESSION['password_reset_verified'], $_SESSION['password_reset_company_id']);

            if (send_password_reset_otp_email($guard_email, $otpCode)) {
                header('Location: ' . app_url('auth/enter-otp.php'));
                exit();
            }

            unset(
                $_SESSION['password_reset_email'],
                $_SESSION['password_reset_otp'],
                $_SESSION['password_reset_otp_expires'],
                $_SESSION['password_reset_requested_at']
            );
            $email_Err = 'Unable to send verification code right now. Please try again in a moment.';
        } else {
            $email_Err = 'We could not verify that email address. Please try again.';
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

auth_page_head('Reset password', 'Reset your portal password.');
auth_body_start();
auth_main_open();
auth_module_open();
auth_card_back_link(app_url('index.php'), 'Back to sign in');
auth_card_intro(
    'Reset password',
    'Enter your account email to receive a verification code.'
);
?>
            <form id="forgotPasswordForm" class="login-form" action="" method="POST" novalidate>
                <?= csrf_field() ?>
                <div class="input-group">
                    <label class="input-label" for="email">Email address</label>
                    <input
                        type="email"
                        name="email"
                        id="email"
                        class="form-input no-toggle<?= !empty($email_Err) ? ' input-error' : '' ?>"
                        placeholder="Email address"
                        value="<?= e($email) ?>"
                        autocomplete="email"
                        required
                        <?= !empty($email_Err) ? 'aria-invalid="true" aria-describedby="email_error"' : '' ?>
                    >
                    <?php if (!empty($email_Err)): ?>
                    <p class="field-error visible" id="email_error" role="alert"><?= e($email_Err) ?></p>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn-signin">Continue</button>
            </form>
<?php
auth_module_close();
auth_main_close();
auth_page_end();
