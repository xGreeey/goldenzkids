<?php
declare(strict_types=1);

/**
 * Escape output for HTML context (XSS prevention).
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '" autocomplete="off">';
}

/**
 * Validate CSRF on state-changing requests. Call at the start of POST handlers.
 */
function csrf_verify(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return;
    }

    $submitted = $_POST['_csrf'] ?? '';
    if (!is_string($submitted) || $submitted === '' || !hash_equals(csrf_token(), $submitted)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
        exit('Invalid security token. Please refresh the page and try again.');
    }
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
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

    $csp = implode('; ', [
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline' https://kit.fontawesome.com",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
        "font-src 'self' https://fonts.gstatic.com https://ka-f.fontawesome.com data:",
        "img-src 'self' data: https: blob:",
        "connect-src 'self' https://kit.fontawesome.com",
        "frame-ancestors 'self'",
        "base-uri 'self'",
        "form-action 'self'",
    ]);
    header('Content-Security-Policy: ' . $csp);
}

/**
 * Safe redirect with alert (avoids injecting raw user input into JS).
 */
function redirect_with_alert(string $message, string $redirectUrl): void
{
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
    <meta name="format-detection" content="telephone=no">
HTML;
}

/**
 * Shared mobile/touch CSS (include inside a <style> block).
 */
function mobile_base_css(): string
{
    return <<<'CSS'
        html { -webkit-text-size-adjust: 100%; text-size-adjust: 100%; }
        body { overflow-x: hidden; -webkit-tap-highlight-color: transparent; }
        button, .btn, .btn-signin, .btn-primary, .submit-btn, .btn-portal,
        .nav-link, .delivery-btn, input[type="submit"], input[type="button"] {
            min-height: 44px;
            touch-action: manipulation;
        }
        input, select, textarea { font-size: 16px; }
        @media (max-width: 600px) {
            .login-card, .portal-section { padding-left: 16px; padding-right: 16px; }
        }
CSS;
}
