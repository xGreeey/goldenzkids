<?php
declare(strict_types=1);

require_once __DIR__ . '/panel_navigation.php';
require_once __DIR__ . '/superadmin_user_form.php';

/**
 * Admin layout shell — sidebar, mobile nav, shared variables.
 * Call inside <style>: <?php admin_shell_styles(); ?>
 * Call before </body>: <?php admin_shell_scripts(); ?>
 */

/** @return array{name:string,role:string,email:string} */
function admin_sidebar_profile(): array
{
    $companyId = (string) ($_SESSION['company_id'] ?? '');
    $role = (string) ($_SESSION['role_name'] ?? 'Administrator');
    $name = '';
    $email = '';

    if ($companyId !== '' && isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof PDO) {
        $conn = $GLOBALS['conn'];
        $roleCol = auth_users_role_column($conn);
        $hasUserNames = auth_users_has_profile_names($conn);
        $nameSelect = $hasUserNames
            ? "COALESCE(NULLIF(TRIM(u.First_Name), ''), g.First_Name) AS First_Name,
               COALESCE(NULLIF(TRIM(u.Last_Name), ''), g.Last_Name) AS Last_Name"
            : 'g.First_Name, g.Last_Name';
        $sql = "SELECT u.Email, u.{$roleCol} AS role, {$nameSelect}
                FROM users u
                LEFT JOIN guards g ON g.Company_ID = u.Company_ID
                WHERE u.Company_ID = ?
                LIMIT 1";
        $row = db_fetch_one($conn, $sql, 's', [$companyId]);
        if ($row !== null) {
            $email = (string) ($row['Email'] ?? '');
            $first = trim((string) ($row['First_Name'] ?? ''));
            $last = trim((string) ($row['Last_Name'] ?? ''));
            $name = trim($first . ' ' . $last);
            if (isset($row['role'])) {
                $role = auth_role_name((int) $row['role']);
            }
        }
    }

    if ($name === '' && $email !== '') {
        $local = strstr($email, '@', true);
        $name = $local !== false
            ? ucwords(str_replace(['.', '_', '-'], ' ', $local))
            : $email;
    }

    if ($name === '' && $companyId !== '') {
        $name = $companyId;
    }

    if ($name === '') {
        $name = 'User';
    }

    if ($email === '') {
        $email = $companyId;
    }

    return [
        'name' => $name,
        'role' => $role,
        'email' => $email,
    ];
}

/** Sidebar footer icon SVG (stroke, 24×24). */
function admin_sidebar_icon(string $icon): string
{
    $attrs = 'class="sidebar-footer-icon-svg" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"';

    return match ($icon) {
        'audit' => '<svg ' . $attrs . '>'
            . '<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>'
            . '<rect x="8" y="2" width="8" height="4" rx="1"/>'
            . '<path d="M9 12h6M9 16h4"/>'
            . '</svg>',
        'settings' => '<svg ' . $attrs . '>'
            . '<circle cx="12" cy="12" r="3"/>'
            . '<path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>'
            . '</svg>',
        'logout' => '<svg ' . $attrs . '>'
            . '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>'
            . '<polyline points="16 17 21 12 16 7"/>'
            . '<line x1="21" y1="12" x2="9" y2="12"/>'
            . '</svg>',
        default => '',
    };
}

function admin_shell_styles(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;
    theme_styles();
    ?>
        /* Admin aliases → shared app tokens in theme.php (:root) */
        :root {
            --bg-app: var(--app-canvas-bg);
            --bg-surface: var(--app-sidebar-surface);
            --bg-panel: var(--app-card-bg);
            --bg-elevated: rgba(255, 255, 255, 0.08);
            --bg-muted: var(--app-accent-glow);
            --border: var(--app-border-on-dark);
            --border-strong: rgba(255, 255, 255, 0.2);
            --text-primary: var(--app-ink-on-dark);
            --text-on-panel: var(--app-ink);
            --text-secondary: var(--app-ink-muted-on-dark);
            --text-tertiary: var(--app-ink-soft-on-dark);
            --brand-accent: var(--app-accent);
            --brand-accent-text: var(--app-accent);
            --brand-accent-soft: var(--app-accent-glow);
            --accent-blue: var(--app-accent-text);
            --accent-blue-soft: var(--app-accent-soft);
            --shadow-sm: var(--app-shadow-sm);
            --shadow-md: 0 6px 18px rgba(var(--color-primary-rgb), 0.1);
            --shadow-lg: 0 16px 36px rgba(var(--color-primary-rgb), 0.14);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --font-sans: var(--font-body-family);
            --font-mono: var(--font-body-family);
            --sidebar-w: 248px;
            --transition: 0.22s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body.light-mode {
            --bg-app: var(--app-canvas-bg);
            --bg-surface: var(--app-sidebar-surface);
            --bg-panel: var(--app-card-bg);
            --bg-elevated: var(--app-border-subtle);
            --bg-muted: var(--app-accent-soft);
            --border: var(--app-border);
            --border-strong: var(--app-border-strong);
            --text-primary: var(--app-ink);
            --text-on-panel: var(--app-ink);
            --text-secondary: var(--app-ink-muted);
            --text-tertiary: var(--app-ink-soft);
            --brand-accent: var(--app-accent);
            --brand-accent-text: var(--app-accent-text);
            --brand-accent-soft: var(--app-accent-soft);
            --accent-blue: var(--app-accent-text);
            --accent-blue-soft: var(--app-accent-soft);
            --shadow-sm: var(--app-shadow-sm);
            --shadow-md: 0 6px 18px rgba(var(--color-primary-rgb), 0.08);
            --shadow-lg: 0 16px 36px rgba(var(--color-primary-rgb), 0.1);
        }

        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        html {
            height: 100%;
        }

        body {
            font-family: var(--font-sans);
            background: var(--app-canvas-bg);
            color: var(--text-primary);
            min-height: 100vh;
            min-height: 100dvh;
            line-height: var(--font-body-line-relaxed);
            overflow-x: hidden;
            transition: background var(--transition), color var(--transition);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
        }

        body.light-mode {
            background: var(--app-canvas-bg);
            color: var(--app-ink);
        }

        body::before {
            content: '';
            position: fixed;
            top: 0; right: 0; bottom: 0;
            left: var(--sidebar-w);
            background:
                radial-gradient(ellipse 72% 48% at 100% 0%, rgba(var(--color-primary-rgb), 0.06), transparent 58%),
                radial-gradient(ellipse 58% 42% at 0% 100%, rgba(var(--color-primary-rgb), 0.05), transparent 52%);
            pointer-events: none;
            z-index: 0;
        }

        body.light-mode::before {
            background:
                radial-gradient(ellipse 72% 48% at 100% 0%, rgba(var(--color-primary-rgb), 0.05), transparent 58%),
                radial-gradient(ellipse 58% 42% at 0% 100%, rgba(var(--color-primary-rgb), 0.04), transparent 52%);
        }

        .app-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1100;
            width: var(--sidebar-w);
            height: 100vh;
            height: 100dvh;
            display: flex;
            flex-direction: column;
            background: var(--app-sidebar-surface);
            border-right: 1px solid var(--app-border-on-dark);
            box-shadow: 2px 0 14px rgba(0, 0, 0, 0.12);
            transition: background var(--transition), border-color var(--transition), box-shadow var(--transition);
        }

        body.light-mode .app-sidebar {
            border-right-color: var(--app-border);
            box-shadow: 1px 0 0 rgba(var(--color-primary-rgb), 0.06);
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 28px 16px;
            min-height: 140px;
            border-bottom: 1px solid var(--app-border-on-dark);
        }

        body.light-mode .sidebar-brand {
            border-bottom-color: var(--app-border);
        }

        .brand-logo {
            width: 104px;
            height: 104px;
            max-width: calc(100% - 8px);
            object-fit: contain;
            flex-shrink: 0;
            display: block;
        }

        .brand-text {
            min-width: 0;
        }

        .brand-name {
            display: block;
            font-family: var(--font-body-family);
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--color-white);
            line-height: 1.3;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 2px;
            padding: 12px 8px;
            flex: 0 0 auto;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            font-size: var(--font-body-size-sm);
            font-weight: 600;
            color: var(--app-ink-muted-on-dark);
            text-decoration: none;
            background: transparent;
            border: none;
            border-radius: var(--radius-sm);
            position: relative;
            transition:
                background-color 0.08s ease,
                color 0.08s ease,
                box-shadow 0.08s ease;
        }

        .sidebar-link i {
            width: 18px;
            text-align: center;
            font-size: 1rem;
            opacity: 1;
            color: inherit;
        }

        .sidebar-link:hover {
            color: var(--app-ink-on-dark);
            background: var(--bg-elevated);
        }

        .sidebar-link.active {
            color: var(--app-ink-on-dark);
            font-weight: 700;
            background: rgba(255, 255, 255, 0.12);
            box-shadow: inset 3px 0 0 0 var(--brand-accent);
        }

        .sidebar-link.active i {
            color: var(--brand-accent-text);
            opacity: 1;
        }

        body.light-mode .brand-name {
            color: var(--app-ink);
        }

        body.light-mode .sidebar-link {
            color: var(--app-ink-muted);
        }

        body.light-mode .sidebar-link:hover {
            color: var(--app-ink);
            background: var(--bg-elevated);
        }

        body.light-mode .sidebar-link.active {
            color: var(--app-ink);
            font-weight: 700;
            background: var(--brand-accent-soft);
            box-shadow: inset 3px 0 0 0 var(--brand-accent);
        }

        body.light-mode .sidebar-link.active i {
            color: var(--brand-accent-text);
        }

        .sidebar-link:focus-visible {
            outline: 2px solid var(--brand-accent);
            outline-offset: 2px;
        }

        .sidebar-footer {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 14px 12px 16px;
            border-top: 1px solid var(--app-border-on-dark);
            margin-top: auto;
        }

        body.light-mode .sidebar-footer {
            border-top-color: var(--app-border);
        }

        .sidebar-footer-user {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 0;
            padding: 0 4px 2px;
        }

        .sidebar-footer-name {
            display: block;
            font-family: var(--font-body-family);
            font-size: 1rem;
            font-weight: 700;
            line-height: 1.25;
            letter-spacing: -0.01em;
            color: var(--app-ink-on-dark);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        body.light-mode .sidebar-footer-name {
            color: var(--app-ink-deep);
        }

        .sidebar-footer-meta {
            display: flex;
            flex-direction: column;
            gap: 3px;
            min-width: 0;
        }

        .sidebar-footer-role {
            display: block;
            font-family: var(--font-body-family);
            font-size: 0.8125rem;
            font-weight: 600;
            line-height: 1.3;
            color: var(--app-ink-muted-on-dark);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        body.light-mode .sidebar-footer-role {
            color: var(--app-ink-muted);
        }

        .sidebar-footer-email {
            display: block;
            font-family: var(--font-body-family);
            font-size: 0.75rem;
            font-weight: 400;
            line-height: 1.35;
            color: var(--app-ink-soft-on-dark);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        body.light-mode .sidebar-footer-email {
            color: var(--app-ink-soft);
        }

        .sidebar-footer-settings {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding-top: 10px;
            border-top: 1px solid var(--app-border-on-dark);
        }

        body.light-mode .sidebar-footer-settings {
            border-top-color: var(--app-border);
        }

        .sidebar-footer-settings-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            min-width: 0;
        }

        .sidebar-footer-label {
            flex: 0 0 auto;
            margin: 0;
            padding: 0;
            font-size: var(--font-body-size-xs);
            font-weight: 600;
            line-height: 1;
            letter-spacing: 0;
            text-transform: none;
            color: var(--app-ink-muted-on-dark);
        }

        body.light-mode .sidebar-footer-label {
            color: var(--app-ink-muted);
        }

        .sidebar-footer-actions {
            display: flex;
            flex-direction: row;
            flex-wrap: nowrap;
            align-items: center;
            justify-content: flex-end;
            gap: 2px;
            flex: 1 1 auto;
            min-width: 0;
        }

        .sidebar-footer-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            width: 36px;
            height: 36px;
            min-height: 36px;
            padding: 0;
            line-height: 0;
            font-family: inherit;
            color: var(--app-ink-on-dark);
            background: none;
            border: none;
            border-radius: 0;
            box-shadow: none;
            cursor: pointer;
            text-decoration: none;
            transition: color var(--transition), opacity var(--transition);
        }

        .sidebar-footer-icon svg,
        .sidebar-footer-icon-svg {
            display: block;
            flex-shrink: 0;
        }

        .sidebar-footer-logout .sidebar-footer-icon {
            width: 36px;
            height: 36px;
            min-height: 36px;
        }

        body.light-mode .sidebar-footer-icon {
            color: var(--app-ink);
        }

        .sidebar-footer-icon:hover,
        .sidebar-footer-icon:focus-visible {
            color: var(--app-ink-on-dark);
        }

        body.light-mode .sidebar-footer-icon:hover,
        body.light-mode .sidebar-footer-icon:focus-visible {
            color: var(--app-ink);
        }

        .sidebar-footer-icon:focus {
            outline: none;
        }

        .sidebar-footer-icon:focus-visible {
            outline: 2px solid var(--app-accent);
            outline-offset: 2px;
        }

        .sidebar-footer-logout {
            display: flex;
            flex: 0 0 auto;
            margin: 0;
            padding: 0;
        }

        .sidebar-footer-theme {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            padding: 2px 0 0;
        }

        .sidebar-footer-theme .theme-switch {
            flex: 0 0 auto;
            padding: 0;
            margin: 0;
        }

        .sidebar-footer-theme .theme-switch__track {
            width: 56px;
            height: 32px;
        }

        .sidebar-footer-theme .theme-switch__thumb {
            width: 28px;
            height: 28px;
        }

        .sidebar-footer-theme .theme-switch[aria-checked="true"] .theme-switch__thumb {
            transform: translateX(24px);
        }

        .mobile-topbar {
            display: none !important;
        }

        .app-shell {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            width: calc(100% - var(--sidebar-w));
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            min-height: 100dvh;
            box-sizing: border-box;
        }

        .app-main {
            flex: 1 1 auto;
            width: 100%;
            max-width: 1440px;
            margin: 0 auto;
            min-width: 0;
            min-height: 0;
            font-size: var(--font-body-size);
            line-height: var(--font-body-line-relaxed);
            overflow-x: clip;
            padding:
                max(clamp(16px, 2.5vw, 32px), env(safe-area-inset-top, 0px))
                max(clamp(16px, 3vw, 32px), env(safe-area-inset-right, 0px))
                max(clamp(20px, 4vw, 48px), env(safe-area-inset-bottom, 0px))
                max(clamp(16px, 3vw, 32px), env(safe-area-inset-left, 0px));
        }

        .page-header {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: clamp(8px, 1.25vw, 14px);
            margin-bottom: clamp(24px, 3.5vw, 36px);
        }

        .page-title {
            margin: 0;
            font-family: var(--font-heading-family);
            font-size: clamp(2rem, 3vw + 0.75rem, 2.5rem);
            font-weight: 400;
            letter-spacing: var(--font-heading-letter);
            line-height: var(--font-heading-line);
            color: var(--app-ink-deep);
        }

        body:not(.light-mode) .page-title {
            color: var(--app-ink-on-dark);
        }

        .page-subtitle {
            margin: 0;
            font-family: var(--font-body-family);
            font-size: clamp(0.9375rem, 0.85vw + 0.55rem, 1.03125rem);
            font-weight: 400;
            color: var(--app-ink-muted);
            max-width: min(48ch, 100%);
            line-height: 1.5;
            letter-spacing: 0.01em;
        }

        body:not(.light-mode) .page-subtitle {
            color: var(--app-ink-muted-on-dark);
        }

        @media (max-width: 600px) {
            .app-main {
                padding-left: max(16px, env(safe-area-inset-left, 0px));
                padding-right: max(16px, env(safe-area-inset-right, 0px));
            }
        }

        @media (orientation: landscape) and (max-height: 520px) {
            .app-main {
                padding-top: max(12px, env(safe-area-inset-top, 0px));
                padding-bottom: max(16px, env(safe-area-inset-bottom, 0px));
            }

            .page-header {
                margin-bottom: 16px;
            }
        }
    <?php
    /* Ensure create-account modal CSS exists even after SPA panel swaps. */
    superadmin_modal_styles();
    panel_navigation_styles();
    echo mobile_base_css();
    admin_panel_asset_styles();
}

/** Inbox/messaging CSS — loaded on all admin shell pages so sidebar panel navigation keeps layout. */
function admin_panel_asset_styles(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;

    $cssDir = dirname(__DIR__) . '/admin/assets/css';
    foreach (['messaging-board.css', 'inbox.css'] as $file) {
        $path = $cssDir . '/' . $file;
        if (is_readable($path)) {
            readfile($path);
        }
    }
}

function admin_shell_scripts(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;
    ?>
<script>
(function () {
    document.addEventListener(
        'submit',
        function (e) {
            var form = e.target;
            if (!form || form.nodeName !== 'FORM' || !form.classList.contains('js-confirm-submit')) {
                return;
            }
            var msg = form.getAttribute('data-confirm');
            if (!msg) {
                return;
            }
            if (!window.confirm(msg)) {
                e.preventDefault();
                e.stopImmediatePropagation();
            }
        },
        true
    );
})();
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.sidebar-footer-icon[href="#"]').forEach(function (link) {
        link.addEventListener('click', function (event) {
            event.preventDefault();
        });
    });
});
</script>
    <?php
    panel_navigation_script();
    superadmin_modal_script();
    theme_toggle_script();
    if (function_exists('app_notify_footer')) {
        app_notify_footer();
    }
    if (function_exists('app_url')) {
        echo '<script src="' . e(app_url('admin/assets/js/inbox.js')) . '" defer></script>';
        echo '<script src="' . e(app_url('admin/assets/js/messaging-board.js')) . '" defer></script>';
    }
}
