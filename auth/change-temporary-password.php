<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once APP_ROOT . '/includes/auth_layout.php';

if (!auth_is_logged_in()) {
    header('Location: ' . app_url('index.php'));
    exit();
}

if (!auth_must_change_password()) {
    header('Location: ' . auth_login_redirect_url(auth_user_role()));
    exit();
}

$error = null;
$success = null;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();

    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $companyId = (string) ($_SESSION['company_id'] ?? '');

    $user = $companyId !== '' ? auth_find_user_by_company_id($conn, strtoupper($companyId)) : null;

    if ($user === null || !auth_verify_password($currentPassword, (string) ($user['password_hash'] ?? ''))) {
        $error = 'Current password is incorrect.';
    } elseif (!auth_password_policy_valid($newPassword)) {
        $error = 'New password must be 8-64 chars with uppercase, lowercase, number, and symbol.';
    } elseif (!hash_equals($newPassword, $confirmPassword)) {
        $error = 'Password confirmation does not match.';
    } elseif (hash_equals($currentPassword, $newPassword)) {
        $error = 'New password must be different from your temporary password.';
    } else {
        $newHash = auth_hash_password($newPassword);
        $ok = db_execute(
            $conn,
            'UPDATE users SET password_hash = ?, password_changed_at = NOW(), failed_login_attempts = 0, locked_until = NULL WHERE Company_ID = ?',
            'ss',
            [$newHash, strtoupper($companyId)]
        );

        if ($ok) {
            $_SESSION['must_change_password'] = 0;
            $success = 'Password updated successfully.';
            header('Location: ' . auth_login_redirect_url(auth_user_role()));
            exit();
        }
        $error = 'Unable to update password right now. Please try again.';
    }
}

$headExtra = <<<'CSS'
body.auth-shell { --error-bg: #f9fafb; --error-border: #d1d5db; }
body.auth-shell.dark-mode { --error-bg: #3a3d42; --error-border: #6b7280; }
.forced-wrap { min-height: 100vh; display: grid; place-items: center; padding: 18px; }
.forced-modal {
    width: min(100%, 520px);
    background: var(--card-bg, #fff);
    border: 1px solid var(--line-color, #d8e1ec);
    border-radius: 14px;
    box-shadow: 0 18px 50px rgba(0,0,0,.18);
    padding: 18px;
}
.strength { margin-top: 8px; }
.strength__bar {
    height: 8px; border-radius: 999px; background: #e5e7eb; overflow: hidden;
}
.strength__fill { height: 100%; width: 0%; transition: width .2s ease; background: #ef4444; }
.strength__label { margin-top: 6px; font-size: .82rem; color: var(--ink-soft, #64748b); }
CSS;

auth_page_head('Change password', 'Set your new password to continue.', $headExtra);
auth_body_start();
?>
<main class="forced-wrap">
    <section class="forced-modal" role="dialog" aria-modal="true" aria-labelledby="forcedPasswordHeading">
        <h1 id="forcedPasswordHeading" class="login-title">Change your password</h1>
        <p class="login-subtitle">You must change your temporary password before continuing.</p>

        <?php if ($error !== null): ?>
            <?php auth_alert_error($error); ?>
        <?php endif; ?>
        <?php if ($success !== null): ?>
            <?php auth_alert_success($success); ?>
        <?php endif; ?>

        <form method="POST" class="login-form" id="forcedPasswordForm" novalidate>
            <?= csrf_field() ?>

            <div class="input-group">
                <label class="input-label" for="current_password">Current temporary password</label>
                <input type="password" id="current_password" name="current_password" class="form-input" autocomplete="current-password" required>
            </div>

            <div class="input-group">
                <label class="input-label" for="new_password">New password</label>
                <input type="password" id="new_password" name="new_password" class="form-input" autocomplete="new-password" required>
                <div class="strength" aria-live="polite">
                    <div class="strength__bar"><div class="strength__fill" id="strengthFill"></div></div>
                    <p class="strength__label" id="strengthLabel">Password strength: very weak</p>
                </div>
            </div>

            <div class="input-group">
                <label class="input-label" for="confirm_password">Confirm new password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-input" autocomplete="new-password" required>
            </div>

            <button type="submit" class="btn-signin" id="changePasswordBtn" disabled>Update password</button>
        </form>
    </section>
</main>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var newPassword = document.getElementById('new_password');
    var confirmPassword = document.getElementById('confirm_password');
    var submitBtn = document.getElementById('changePasswordBtn');
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
<?php auth_page_end(); ?>

