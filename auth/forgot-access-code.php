<?php
require_once __DIR__ . '/../config/app.php';
require __DIR__ . '/forgot-access-mailer.php';

$isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1'], true)
    || str_contains($_SERVER['HTTP_HOST'] ?? '', 'localhost');
if ($isLocal) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?= mobile_meta_tags() ?>
    <meta name="description" content="Reset your ABC Security Agency portal access code.">
    <title>Reset Access Code | ABC Security Agency</title>
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
            --error-text: #e5e7eb;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--color-bg);
            color: var(--color-text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: background-color 0.25s ease, color 0.25s ease;
        }

        header {
            background: var(--color-header);
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
            color: var(--color-text);
        }

        .header-actions { display: flex; align-items: center; gap: 16px; }

        .btn-theme {
            background: transparent;
            border: 1px solid var(--color-border);
            color: var(--color-text-muted);
            font-size: 1rem;
            cursor: pointer;
            width: 40px;
            height: 40px;
            border-radius: 6px;
        }

        .btn-theme:hover {
            background: var(--color-input);
            color: var(--color-text);
        }

        .btn-back {
            color: var(--color-accent);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back:hover {
            color: var(--color-accent-hover);
            text-decoration: underline;
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
        }

        .login-subtitle {
            font-size: 0.9rem;
            color: var(--color-text-muted);
            line-height: 1.5;
            margin-bottom: 28px;
        }

        .input-group { margin-bottom: 20px; }

        .input-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            margin-bottom: 8px;
            color: var(--color-text);
        }

        .form-input {
            width: 100%;
            padding: 14px;
            background: var(--color-input);
            border: 1px solid var(--color-border);
            font-size: 1rem;
            color: var(--color-text);
            outline: none;
            border-radius: 4px;
        }

        .form-input:focus {
            border-color: var(--color-accent);
            box-shadow: 0 0 0 3px var(--color-focus-ring);
        }

        .form-input::placeholder { color: var(--color-text-muted); }

        .field-error {
            color: var(--error-text);
            font-size: 0.8rem;
            margin-top: 6px;
        }

        .btn-primary {
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
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-primary:hover { background: var(--color-btn-hover); }

        .form-footer {
            margin-top: 20px;
            text-align: center;
        }

        .form-footer a {
            color: var(--color-accent);
            font-size: 0.875rem;
            text-decoration: none;
        }

        .form-footer a:hover {
            color: var(--color-accent-hover);
            text-decoration: underline;
        }

        @media (max-width: 600px) {
            header { padding: 16px 20px; height: auto; flex-wrap: wrap; gap: 12px; }
            .login-card { padding: 28px 22px 22px; }
        }
<?= mobile_base_css() ?>
    </style>
</head>
<body>

<header>
    <div class="nav-left">
        <img src="https://i.imgur.com/uOClOiX.jpeg" alt="ABC Security Agency" class="logo-img" onerror="this.src='https://via.placeholder.com/48/e5e7eb/6b7280?text=ABC'">
        <div class="agency-name">ABC Security Agency</div>
    </div>
    <div class="header-actions">
        <a href="<?= app_url('index.php') ?>" class="btn-back"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to sign in</a>
        <button type="button" id="themeToggle" class="btn-theme" aria-label="Toggle theme">
            <i class="fa-solid fa-sun" aria-hidden="true"></i>
        </button>
    </div>
</header>

<main class="main-content">
    <div class="login-card">
        <h1 class="login-title">Reset Access Code</h1>
        <p class="login-subtitle">Enter the registered email address on your employee file. We will send verification instructions to HR for processing.</p>

        <form id="forgotpin" action="" method="POST">
            <?= csrf_field() ?>
            <div class="input-group">
                <label class="input-label" for="email">Registered Email</label>
                <input
                    type="email"
                    name="email"
                    id="email"
                    class="form-input"
                    placeholder="name@company.com"
                    autocomplete="email"
                    required
                >
                <?php if (!empty($email_Err)): ?>
                <p class="field-error" role="alert"><?= e($email_Err) ?></p>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn-primary">Submit Request</button>

            <p class="form-footer"><a href="<?= app_url('index.php') ?>">Return to sign in</a></p>
        </form>
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
});
</script>

</body>
</html>
