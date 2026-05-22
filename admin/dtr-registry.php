<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

$query = $_SERVER['QUERY_STRING'] ?? '';
$target = 'dtr.php' . ($query !== '' ? '?' . $query : '');

header('Location: ' . $target, true, 301);
exit;
