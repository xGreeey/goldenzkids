<?php
declare(strict_types=1);

/**
 * Legacy URL — Daily Attendance Detail (DAD) moved to dad.php.
 */
require_once __DIR__ . '/../config/app.php';

$query = (string) ($_SERVER['QUERY_STRING'] ?? '');
$target = 'dad.php' . ($query !== '' ? '?' . $query : '');

header('Location: ' . $target, true, 301);
exit;
