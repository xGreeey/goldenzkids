<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

date_default_timezone_set('Asia/Taipei');

if (session_status() === PHP_SESSION_NONE) {
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
