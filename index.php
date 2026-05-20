<?php
require_once __DIR__ . '/config/app.php';
require_once APP_ROOT . '/auth/login-handler.php';
require_once APP_ROOT . '/includes/auth_layout.php';

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
CSS;

auth_page_head('Sign in', 'Sign in to the ' . app_agency_name() . ' employee portal.', $headExtra);
auth_body_start();
auth_main_open();
auth_module_open();
auth_card_intro('Sign in', 'Enter your username and password to continue.');
$passwordResetSuccess = (($_GET['reset'] ?? '') === 'success');

if ($passwordResetSuccess) {
    auth_alert_success('Password reset successful. You can now sign in with your new password.', 7000);
}

if (!empty($error)) {
    auth_alert_error($error, 6000);
}
?>
            <form id="loginForm" class="login-form" action="" method="POST" novalidate>
                <?= csrf_field() ?>
                <div class="input-group">
                    <label class="input-label" for="username">Username</label>
                    <input
                        type="text"
                        name="company_id"
                        id="username"
                        class="form-input no-toggle<?= !empty($company_idErr) ? ' input-error' : '' ?>"
                        placeholder="Username"
                        value="<?= e($company_id) ?>"
                        pattern="[A-Za-z0-9]{1,20}"
                        maxlength="20"
                        autocomplete="username"
                        autocapitalize="off"
                        spellcheck="false"
                        required
                        aria-describedby="<?= !empty($company_idErr) ? 'company_id_error' : '' ?>"
                        <?= !empty($company_idErr) ? 'aria-invalid="true"' : '' ?>
                    >
                    <?php if (!empty($company_idErr)): ?>
                    <p class="field-error visible" id="company_id_error" role="alert"><?= e($company_idErr) ?></p>
                    <?php endif; ?>
                </div>

                <div class="input-group">
                    <div class="label-row">
                        <label class="input-label" for="password">Password</label>
                    </div>
                    <div class="input-wrap">
                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="form-input<?= !empty($passwordErr) ? ' input-error' : '' ?>"
                            placeholder="Password"
                            autocomplete="current-password"
                            required
                            aria-describedby="<?= !empty($passwordErr) ? 'password_error' : '' ?>"
                            <?= !empty($passwordErr) ? 'aria-invalid="true"' : '' ?>
                        >
                        <button type="button" class="btn-toggle-pin" id="togglePin" aria-label="Show password"<?= ui_tooltip('Show password') ?>>
                            <i class="fa-regular fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                    <?php if (!empty($passwordErr)): ?>
                    <p class="field-error visible" id="password_error" role="alert"><?= e($passwordErr) ?></p>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn-signin" id="submitBtn">
                    <span id="submitBtnText">Sign in</span>
                </button>
                <p class="form-footer">
                    <a href="<?= e(app_url('auth/forgot-access-code.php')) ?>" class="forgot-link">Forgot password?</a>
                </p>
            </form>
<?php
auth_card_support_footer('Need assistance?', 'Contact your site supervisor.');
auth_module_close();
auth_main_close();
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const pinInput = document.getElementById('password');
    const togglePin = document.getElementById('togglePin');
    togglePin.addEventListener('click', function () {
        const isHidden = pinInput.type === 'password';
        pinInput.type = isHidden ? 'text' : 'password';
        const icon = togglePin.querySelector('i');
        icon.classList.toggle('fa-eye', !isHidden);
        icon.classList.toggle('fa-eye-slash', isHidden);
        const tip = isHidden ? 'Hide password' : 'Show password';
        togglePin.setAttribute('aria-label', tip);
        togglePin.dataset.tip = tip;
    });

    const loginForm = document.getElementById('loginForm');
    const submitBtn = document.getElementById('submitBtn');
    const submitBtnText = document.getElementById('submitBtnText');

    loginForm.addEventListener('submit', function () {
        submitBtn.disabled = true;
        submitBtnText.textContent = 'Signing in…';
    });
});
</script>
<?php
auth_page_end();
