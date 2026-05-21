<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

date_default_timezone_set('Asia/Taipei');

/** Keep users signed in across tabs until idle timeout (seconds). */
const SESSION_LIFETIME_SECONDS = 28800; // 8 hours

if (session_status() === PHP_SESSION_NONE) {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

    $cookiePath = APP_BASE === '' ? '/' : APP_BASE;

    ini_set('session.gc_maxlifetime', (string) SESSION_LIFETIME_SECONDS);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME_SECONDS,
        'path' => $cookiePath,
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_name('abc_portal_sid');
    session_start();
}

if (isset($company_id) && $company_id !== '') {
    $_SESSION['company_id'] = $company_id;
}

$current = basename($_SERVER['PHP_SELF'] ?? '');
$scriptPath = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? ''));
$public_pages = [
    'forgot-access-code.php',
    'enter-otp.php',
    'reset-password.php',
];

/** Root login index only — not guard/admin/superadmin index.php files. */
$isRootLoginIndex = $current === 'index.php'
    && !preg_match('#/(guard|admin|superadmin)/#', $scriptPath);

if (($isRootLoginIndex || in_array($current, $public_pages, true)) === false && !isset($_SESSION['company_id'])) {
    header('Location: ' . app_url('index.php'));
    exit();
}
