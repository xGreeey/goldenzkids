<?php
declare(strict_types=1);

/**
 * Escape output for HTML body text (XSS prevention).
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Escape for HTML attribute values (quotes included).
 */
function e_attr(?string $value): string
{
    return e($value);
}

/**
 * Escape for embedding in JavaScript string literals (use inside quoted strings only).
 */
function e_js(?string $value): string
{
    return json_encode($value ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE)
        ?: '""';
}

/**
 * Strip HTML from untrusted user input before storage or plain-text display.
 */
function xss_sanitize_plaintext(string $value, int $maxLength = 10000): string
{
    $value = strip_tags($value);
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? $value;

    if (mb_strlen($value) > $maxLength) {
        $value = mb_substr($value, 0, $maxLength);
    }

    return trim($value);
}

/**
 * CSRF token stored in session; rotated on login via regenerate_session().
 */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

/**
 * Hidden input for POST forms.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e_attr(csrf_token()) . '" autocomplete="off">';
}

/**
 * Read submitted CSRF token from POST body or X-CSRF-Token header (AJAX).
 */
function csrf_submitted_token(): string
{
    $fromPost = $_POST['_csrf'] ?? '';
    if (is_string($fromPost) && $fromPost !== '') {
        return $fromPost;
    }

    $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (is_string($header)) {
        return trim($header);
    }

    return '';
}

/**
 * Validate CSRF on state-changing requests. Call at the start of POST handlers.
 */
function csrf_verify(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return;
    }

    $submitted = csrf_submitted_token();
    if ($submitted === '' || !hash_equals(csrf_token(), $submitted)) {
        http_response_code(403);
        $wantsJson = isset($_SERVER['HTTP_ACCEPT'])
            && str_contains((string) $_SERVER['HTTP_ACCEPT'], 'application/json');
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if ($wantsJson || $isAjax) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['ok' => false, 'error' => 'Invalid security token. Please refresh and try again.'], JSON_THROW_ON_ERROR);
            exit;
        }

        header('Content-Type: text/plain; charset=UTF-8');
        exit('Invalid security token. Please refresh the page and try again.');
    }
}

/**
 * Enforce CSRF on all POST requests except listed script basenames (e.g. webhooks).
 *
 * @param list<string> $exemptScripts Basenames without CSRF check.
 */
function csrf_enforce_post(array $exemptScripts = []): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return;
    }

    $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? ''));
    if ($script !== '' && in_array($script, $exemptScripts, true)) {
        return;
    }

    csrf_verify();
}

/**
 * Regenerate session ID after privilege change (e.g. login).
 */
function regenerate_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
}

/**
 * HTTP security headers (call before any output).
 */
function send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    $script = (string) ($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '');
    $guardPortal = str_contains($script, '/guard/');
    header(
        $guardPortal
            ? 'Permissions-Policy: geolocation=(self), camera=(self), microphone=()'
            : 'Permissions-Policy: geolocation=(), microphone=(), camera=()'
    );
    header('X-XSS-Protection: 0');

    $connectSrc = $guardPortal
        ? "'self' https://kit.fontawesome.com https://nominatim.openstreetmap.org https://maps.googleapis.com"
        : "'self' https://kit.fontawesome.com";

    $csp = implode('; ', [
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline' https://kit.fontawesome.com https://cdn.jsdelivr.net",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
        "font-src 'self' https://fonts.gstatic.com https://ka-f.fontawesome.com data:",
        "img-src 'self' data: https: blob:",
        "connect-src {$connectSrc}",
        "frame-ancestors 'self'",
        "base-uri 'self'",
        "form-action 'self'",
    ]);
    header('Content-Security-Policy: ' . $csp);
}

/**
 * Redirect with a flash message shown in the in-app notification modal.
 */
function redirect_with_alert(string $message, string $redirectUrl, ?string $type = null): void
{
    if (function_exists('flash_set')) {
        flash_set($type ?? flash_guess_type($message), $message);
        header('Location: ' . $redirectUrl);
        exit();
    }

    $msg = json_encode($message, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    $url = json_encode($redirectUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    echo '<script>alert(' . $msg . ');window.location.href=' . $url . ';</script>';
    exit();
}

/**
 * Shared mobile-friendly meta tags for HTML head.
 */
function mobile_meta_tags(): string
{
    return <<<'HTML'
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="format-detection" content="telephone=no">
HTML;
}

/**
 * Shared mobile/touch CSS (include inside a <style> block).
 */
function mobile_base_css(): string
{
    return <<<'CSS'
        html {
            -webkit-text-size-adjust: 100%;
            text-size-adjust: 100%;
            height: 100%;
        }
        body {
            overflow-x: hidden;
            -webkit-tap-highlight-color: transparent;
            overscroll-behavior-x: none;
        }
        button, .btn, .btn-signin, .btn-primary, .submit-btn, .btn-portal,
        .nav-link, .delivery-btn, .btn-back, .forgot-link, .theme-switch,
        input[type="submit"], input[type="button"] {
            touch-action: manipulation;
        }
        button, .btn, .btn-signin, .btn-primary, .submit-btn, .btn-portal,
        .nav-link, .delivery-btn, input[type="submit"], input[type="button"] {
            min-height: 44px;
        }
        button.app-modal__close {
            min-height: 30px;
            min-width: 30px;
        }
        body.superadmin-portal .app-main .btn-primary,
        body.superadmin-portal .app-main .btn-ghost,
        body.superadmin-portal .app-main button.btn-primary,
        body.superadmin-portal .app-main button.btn-ghost,
        body.guard-portal .app-main .btn-primary,
        body.guard-portal .app-main .btn-ghost,
        body.guard-portal .app-main button.btn-primary,
        body.guard-portal .app-main button.btn-ghost {
            min-height: 34px;
        }
        input, select, textarea, .form-input {
            font-size: 16px;
        }
        @media (max-width: 600px) {
            .portal-section { padding-left: 16px; padding-right: 16px; }
        }
CSS;
}
