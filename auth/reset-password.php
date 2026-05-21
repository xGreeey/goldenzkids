<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once APP_ROOT . '/includes/auth_layout.php';

if (empty($_SESSION['password_reset_verified']) || empty($_SESSION['password_reset_email'])) {
    header('Location: ' . app_url('auth/forgot-access-code.php'));
    exit();
}

$error = null;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();

    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $email = (string) ($_SESSION['password_reset_email'] ?? '');
    $companyId = (string) ($_SESSION['password_reset_company_id'] ?? '');

    if (!auth_password_policy_valid($newPassword)) {
        $error = 'New password must be 8-64 chars with uppercase, lowercase, number, and symbol.';
    } elseif (!hash_equals($newPassword, $confirmPassword)) {
        $error = 'Password confirmation does not match.';
    } else {
        if ($companyId === '') {
            $row = db_fetch_one($conn, 'SELECT Company_ID FROM users WHERE Email = ? LIMIT 1', 's', [$email]);
            if ($row !== null) {
                $companyId = (string) ($row['Company_ID'] ?? '');
            }
        }

        if ($companyId === '') {
            $error = 'Your reset session has expired. Please start again.';
        } else {
            $hr = db_fetch_one($conn, 'SELECT password_hash FROM users WHERE Company_ID = ? LIMIT 1', 's', [$companyId]);
            $existingHash = $hr !== null ? trim((string) ($hr['password_hash'] ?? '')) : '';

            if ($existingHash !== '' && auth_password_matches_existing_hash($newPassword, $existingHash)) {
                $error = 'You cannot reuse your previous password. Choose a different one.';
            } else {
                $newHash = auth_hash_password($newPassword);
                $ok = db_execute(
                    $conn,
                    'UPDATE users SET password_hash = ?, password_changed_at = NOW(), failed_login_attempts = 0, locked_until = NULL WHERE Company_ID = ?',
                    'ss',
                    [$newHash, $companyId]
                );

                if ($ok) {
                    unset(
                        $_SESSION['password_reset_email'],
                        $_SESSION['password_reset_otp'],
                        $_SESSION['password_reset_otp_expires'],
                        $_SESSION['password_reset_requested_at'],
                        $_SESSION['password_reset_verified'],
                        $_SESSION['password_reset_company_id']
                    );
                    header('Location: ' . app_url('index.php?reset=success'));
                    exit();
                }

                $error = 'Unable to update password right now. Please try again.';
            }
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

$headExtra = <<<'CSS'
body.auth-shell { --error-bg: #f9fafb; --error-border: #d1d5db; }
body.auth-shell.dark-mode { --error-bg: #3a3d42; --error-border: #6b7280; }
.strength { margin-top: 8px; }
.strength__bar {
    height: 8px; border-radius: 999px; background: #e5e7eb; overflow: hidden;
}
.strength__fill { height: 100%; width: 0%; transition: width .2s ease; background: #ef4444; }
.strength__label { margin-top: 6px; font-size: .82rem; color: var(--ink-soft, #64748b); }
CSS;

auth_page_head('Reset password', 'Set your new portal password.', $headExtra);
auth_body_start();
auth_main_open();
auth_module_open();
auth_card_intro('Reset password', 'Enter your new password to finish resetting your account.');
if ($error !== null) {
    auth_alert_error($error);
}
?>
            <form method="POST" class="login-form" id="resetPasswordForm" novalidate>
                <?= csrf_field() ?>
                <div class="input-group">
                    <label class="input-label" for="new_password">New password</label>
                    <input type="password" id="new_password" name="new_password" class="form-input" autocomplete="new-password" required>
                    <div class="strength" aria-live="polite">
                        <div class="strength__bar"><div class="strength__fill" id="strengthFill"></div></div>
                        <p class="strength__label" id="strengthLabel">Password strength: weak</p>
                    </div>
                </div>

                <div class="input-group">
                    <label class="input-label" for="confirm_password">Confirm new password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" autocomplete="new-password" required>
                </div>

                <button type="submit" class="btn-signin" id="resetPasswordBtn" disabled>Update password</button>
            </form>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var newPassword = document.getElementById('new_password');
    var confirmPassword = document.getElementById('confirm_password');
    var submitBtn = document.getElementById('resetPasswordBtn');
    var fill = document.getElementById('strengthFill');
    var label = document.getElementById('strengthLabel');

    function scorePassword(value) {
        var score = 0;
        if (value.length >= 8) score++;
        if (/[a-z]/.test(value)) score++;
        if (/[A-Z]/.test(value)) score++;
        if (/\d/.test(value)) score++;
        if (/[^A-Za-z\d]/.test(value)) score++;
        return score;
    }

    function isPolicyMet(value) {
        return value.length >= 8
            && /[a-z]/.test(value)
            && /[A-Z]/.test(value)
            && /\d/.test(value)
            && /[^A-Za-z\d]/.test(value);
    }

    function renderStrength() {
        var val = newPassword.value || '';
        var score = scorePassword(val);
        var pct = Math.min(100, score * 20);
        fill.style.width = pct + '%';

        if (score <= 2) {
            fill.style.background = '#ef4444';
            label.textContent = 'Password strength: weak';
        } else if (score === 3 || score === 4) {
            fill.style.background = '#f59e0b';
            label.textContent = 'Password strength: medium';
        } else {
            fill.style.background = '#22c55e';
            label.textContent = 'Password strength: strong';
        }

        var valid = isPolicyMet(val) && confirmPassword.value === val;
        submitBtn.disabled = !valid;
    }

    newPassword.addEventListener('input', renderStrength);
    confirmPassword.addEventListener('input', renderStrength);
    renderStrength();
});
</script>
<?php
auth_module_close();
auth_main_close();
auth_page_end();
