<?php
declare(strict_types=1);

/**
 * Legacy URL — Daily Time Record (DTR) registry moved to dtr.php.
 */
require_once __DIR__ . '/../config/app.php';

$query = (string) ($_SERVER['QUERY_STRING'] ?? '');
$target = 'dtr.php' . ($query !== '' ? '?' . $query : '');

header('Location: ' . $target, true, 301);
exit;
