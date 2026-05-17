<?php
declare(strict_types=1);

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

if (!defined('APP_BASE')) {
    $docRoot = str_replace('\\', '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $appRoot = str_replace('\\', '/', APP_ROOT);

    if ($docRoot !== '' && str_starts_with($appRoot, $docRoot)) {
        define('APP_BASE', rtrim(substr($appRoot, strlen($docRoot)), '/') ?: '');
    } else {
        define('APP_BASE', '/abc');
    }
}

/**
 * Build a web path from the application root (works from any subdirectory).
 */
function app_url(string $path = 'index.php'): string
{
    $path = ltrim(str_replace('\\', '/', $path), '/');
    $base = APP_BASE === '' ? '' : APP_BASE;

    return $base . '/' . $path;
}

/**
 * Require a file relative to the application root.
 */
function app_require(string $relativePath): void
{
    require_once APP_ROOT . '/' . ltrim(str_replace('\\', '/', $relativePath), '/');
}

define('UPLOADS_URL', app_url('uploads/'));
