<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

date_default_timezone_set('Asia/Taipei');

if (session_status() === PHP_SESSION_NONE) {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
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
$public_pages = [
    'index.php',
    'forgot-access-code.php',
    'enter-otp.php',
];

if (!in_array($current, $public_pages, true) && !isset($_SESSION['company_id'])) {
    header('Location: ' . app_url('index.php'));
    exit();
}
