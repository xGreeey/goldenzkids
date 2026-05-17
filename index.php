<?php
require_once __DIR__ . '/config/app.php';
require_once APP_ROOT . '/auth/login-handler.php';

$isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1'], true)
    || str_contains($_SERVER['HTTP_HOST'] ?? '', 'localhost');
if ($isLocal) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sign in to the ABC Security Agency employee portal.">
    <title>Sign In | ABC Security Agency</title>
    <script src="https://kit.fontawesome.com/3142eebea3.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Oswald:wght@500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-bg: #eceef1;
            --color-header: #ffffff;
            --color-surface: #ffffff;
            --color-input: #f9fafb;
            --color-border: #d1d5db;
            --color-border-subtle: #e5e7eb;
            --color-text: #1f2937;
            --color-text-muted: #6b7280;
            --color-accent: #4b5563;
            --color-accent-hover: #374151;
            --color-btn: #4b5563;
            --color-btn-hover: #374151;
            --color-btn-text: #ffffff;
            --color-focus-ring: rgba(75, 85, 99, 0.25);
            --error-bg: #f9fafb;
            --error-border: #d1d5db;
            --error-text: #7f1d1d;
        }

        body.dark-mode {
            --color-bg: #2f3236;
            --color-header: #26282c;
            --color-surface: #36393f;
            --color-input: #40444b;
            --color-border: #4e5359;
            --color-border-subtle: #43474d;
            --color-text: #e8eaed;
            --color-text-muted: #9ca3af;
            --color-accent: #b0b6bf;
            --color-accent-hover: #d1d5db;
            --color-btn: #5c6370;
            --color-btn-hover: #6b7280;
            --color-btn-text: #f9fafb;
            --color-focus-ring: rgba(156, 163, 175, 0.2);
            --error-bg: #3a3d42;
            --error-border: #6b7280;
            --error-text: #e5e7eb;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background-color: var(--color-bg);
            color: var(--color-text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: background-color 0.25s ease, color 0.25s ease;
        }

        header {
            background-color: var(--color-header);
            padding: 0 5%;
            height: 72px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--color-border-subtle);
        }

        .nav-left { display: flex; align-items: center; gap: 16px; }

        .logo-img {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid var(--color-border);
        }

        .agency-name {
            font-family: 'Oswald', sans-serif;
            font-size: 1.05rem;
            font-weight: 500;
            letter-spacing: 0.03em;
            line-height: 1.2;
            color: var(--color-text);
        }

        .btn-theme {
            background: transparent;
            border: 1px solid var(--color-border);
            color: var(--color-text-muted);
            font-size: 1rem;
            cursor: pointer;
            width: 40px;
            height: 40px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s, border-color 0.2s;
        }

        .btn-theme:hover {
            background: var(--color-input);
            border-color: var(--color-border);
            color: var(--color-text);
        }

        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 20px;
        }

        .login-card {
            background: var(--color-surface);
            border: 1px solid var(--color-border-subtle);
            width: 100%;
            max-width: 440px;
            padding: 36px 32px 28px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06), 0 8px 24px rgba(0, 0, 0, 0.04);
            border-radius: 6px;
        }

        body.dark-mode .login-card {
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.2);
        }

        .login-title {
            font-family: 'Oswald', sans-serif;
            font-size: 1.65rem;
            font-weight: 500;
            color: var(--color-text);
            margin-bottom: 8px;
            letter-spacing: 0.01em;
        }

        .login-subtitle {
            font-size: 0.9rem;
            color: var(--color-text-muted);
            line-height: 1.5;
            margin-bottom: 28px;
        }

        .alert-error {
            display: <?= !empty($error) ? 'flex' : 'none' ?>;
            align-items: flex-start;
            gap: 10px;
            background: var(--error-bg);
            border: 1px solid var(--error-border);
            color: var(--error-text);
            padding: 12px 14px;
            margin-bottom: 20px;
            font-size: 0.875rem;
            line-height: 1.45;
            border-radius: 4px;
        }


        .input-group { margin-bottom: 20px; }

        .input-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .label-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .label-row .input-label { margin-bottom: 0; }

        .forgot-link {
            color: var(--color-accent);
            font-size: 0.8rem;
            font-weight: 500;
            text-decoration: none;
        }

        .forgot-link:hover {
            color: var(--color-accent-hover);
            text-decoration: underline;
        }

        .input-wrap { position: relative; }

        .form-input {
            width: 100%;
            padding: 14px 44px 14px 14px;
            background: var(--color-input);
            border: 1px solid var(--color-border);
            font-size: 1rem;
            color: var(--color-text);
            outline: none;
            border-radius: 4px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-input:focus {
            border-color: var(--color-accent);
            box-shadow: 0 0 0 3px var(--color-focus-ring);
        }

        .form-input::placeholder { color: var(--color-text-muted); }

        .form-input.input-error { border-color: var(--error-border); }

        .form-input.no-toggle { padding-right: 14px; }

        .btn-toggle-pin {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--color-text-muted);
            cursor: pointer;
            padding: 6px;
            font-size: 1rem;
        }

        .btn-toggle-pin:hover { color: var(--color-text); }

        .field-error {
            display: none;
            color: var(--error-text);
            font-size: 0.8rem;
            margin-top: 6px;
            line-height: 1.4;
        }

        .field-error.visible { display: block; }

        .form-hint {
            font-size: 0.78rem;
            color: var(--color-text-muted);
            margin-top: 6px;
        }

        .btn-signin {
            width: 100%;
            padding: 14px;
            margin-top: 8px;
            background: var(--color-btn);
            color: var(--color-btn-text);
            border: none;
            border-radius: 4px;
            font-family: 'Inter', system-ui, sans-serif;
            font-size: 0.95rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            cursor: pointer;
            transition: background 0.2s, opacity 0.2s;
        }

        .btn-signin:hover:not(:disabled) { background: var(--color-btn-hover); }

        .btn-signin:disabled {
            opacity: 0.72;
            cursor: not-allowed;
        }

        .login-footer {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--color-border-subtle);
            font-size: 0.8rem;
            color: var(--color-text-muted);
            line-height: 1.5;
            text-align: center;
        }

        @media (max-width: 600px) {
            header { padding: 16px 20px; height: auto; }
            .login-card { padding: 28px 22px 22px; }
        }
    </style>
</head>
<body>

<header>
    <div class="nav-left">
        <img src="https://i.imgur.com/uOClOiX.jpeg" alt="ABC Security Agency" class="logo-img" onerror="this.src='https://via.placeholder.com/48/e5e7eb/6b7280?text=ABC'">
        <div class="agency-name">ABC Security Agency</div>
    </div>
    <button type="button" id="themeToggle" class="btn-theme" title="Toggle light or dark theme" aria-label="Toggle theme">
        <i class="fa-solid fa-sun" aria-hidden="true"></i>
    </button>
</header>

<main class="main-content">
    <div class="login-card">
        <h1 class="login-title">Sign In</h1>
        <p class="login-subtitle">Enter your employee credentials to access the operations portal.</p>

        <?php if (!empty($error)): ?>
        <div class="alert-error" role="alert">
            <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
            <span><?= e($error) ?></span>
        </div>
        <?php endif; ?>

        <form id="loginForm" action="" method="POST" novalidate>
            <div class="input-group">
                <label class="input-label" for="company_id">Employee ID</label>
                <input
                    type="text"
                    name="company_id"
                    id="company_id"
                    class="form-input no-toggle<?= !empty($company_idErr) ? ' input-error' : '' ?>"
                    placeholder="ABC-2001-0042"
                    value="<?= e($company_id) ?>"
                    autocomplete="username"
                    autocapitalize="characters"
                    spellcheck="false"
                    required
                    aria-describedby="company_id_hint company_id_error"
                    <?= !empty($company_idErr) ? 'aria-invalid="true"' : '' ?>
                >
                <p class="form-hint" id="company_id_hint">Format: ABC-2###-#### (assigned by HR)</p>
                <p class="field-error<?= !empty($company_idErr) ? ' visible' : '' ?>" id="company_id_error" role="alert"><?= e($company_idErr) ?></p>
            </div>

            <div class="input-group">
                <div class="label-row">
                    <label class="input-label" for="pin">Access Code</label>
                    <a href="auth/forgot-access-code.php" class="forgot-link">Forgot access code?</a>
                </div>
                <div class="input-wrap">
                    <input
                        type="password"
                        name="pin"
                        id="pin"
                        class="form-input<?= !empty($pin_Err) ? ' input-error' : '' ?>"
                        placeholder="6-digit code"
                        maxlength="6"
                        inputmode="numeric"
                        pattern="[0-9]{6}"
                        autocomplete="current-password"
                        required
                        aria-describedby="pin_error"
                        <?= !empty($pin_Err) ? 'aria-invalid="true"' : '' ?>
                    >
                    <button type="button" class="btn-toggle-pin" id="togglePin" aria-label="Show access code">
                        <i class="fa-regular fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
                <p class="field-error<?= !empty($pin_Err) ? ' visible' : '' ?>" id="pin_error" role="alert"><?= e($pin_Err) ?></p>
            </div>

            <button type="submit" class="btn-signin" id="submitBtn">
                <span id="submitBtnText">Sign In</span>
            </button>
        </form>

        <p class="login-footer">Need assistance? Contact your site supervisor or the HR department.</p>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const themeToggleBtn = document.getElementById('themeToggle');
    const themeIcon = themeToggleBtn.querySelector('i');
    const body = document.body;
    const savedTheme = localStorage.getItem('abc_theme');

    if (savedTheme === 'dark') {
        body.classList.add('dark-mode');
        themeIcon.classList.replace('fa-sun', 'fa-moon');
    }

    themeToggleBtn.addEventListener('click', function () {
        body.classList.toggle('dark-mode');
        if (body.classList.contains('dark-mode')) {
            localStorage.setItem('abc_theme', 'dark');
            themeIcon.classList.replace('fa-sun', 'fa-moon');
        } else {
            localStorage.setItem('abc_theme', 'light');
            themeIcon.classList.replace('fa-moon', 'fa-sun');
        }
    });

    const pinInput = document.getElementById('pin');
    pinInput.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 6);
    });

    const togglePin = document.getElementById('togglePin');
    togglePin.addEventListener('click', function () {
        const isHidden = pinInput.type === 'password';
        pinInput.type = isHidden ? 'text' : 'password';
        const icon = togglePin.querySelector('i');
        icon.classList.toggle('fa-eye', !isHidden);
        icon.classList.toggle('fa-eye-slash', isHidden);
        togglePin.setAttribute('aria-label', isHidden ? 'Hide access code' : 'Show access code');
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

</body>
</html>
