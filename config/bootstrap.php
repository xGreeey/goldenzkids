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
        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        if (preg_match('#^/([^/]+)/(?:guard|admin|superadmin|auth|api)/#', $script, $m)) {
            define('APP_BASE', '/' . $m[1]);
        } else {
            define('APP_BASE', '/goldenzkids');
        }
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

define('APP_AGENCY_NAME', 'Golden Z-5 Security & Investigation Agency, Inc.');

function app_agency_name(): string
{
    return APP_AGENCY_NAME;
}

function app_agency_name_upper(): string
{
    return strtoupper(APP_AGENCY_NAME);
}

function app_logo_url(): string
{
    return app_url('assets/images/goldenz_logo.png');
}
