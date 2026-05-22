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

/** Run immediately after `<body>` opens so saved dark/light mode applies before paint. */
function admin_theme_body_boot(): void
{
    if (!function_exists('theme_body_boot_script')) {
        require_once __DIR__ . '/theme.php';
    }
    theme_body_boot_script('light-class');
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

        body:not(.light-mode) {
            --bg-app: var(--app-canvas-bg);
            --bg-surface: var(--app-sidebar-surface);
            --bg-panel: var(--app-card-bg);
            --bg-elevated: rgba(255, 255, 255, 0.08);
            --bg-muted: var(--app-accent-soft);
            --border: var(--app-border);
            --border-strong: var(--app-border-strong);
            --text-primary: var(--app-ink);
            --text-on-panel: var(--app-ink);
            --text-secondary: var(--app-ink-muted);
            --text-tertiary: var(--app-ink-soft);
            --brand-accent: var(--app-accent);
            --brand-accent-text: var(--app-accent);
            --accent-blue: var(--app-accent-text);
            --accent-blue-soft: var(--app-accent-soft);
            --shadow-md: 0 6px 18px rgba(0, 0, 0, 0.28);
            --shadow-lg: 0 16px 36px rgba(0, 0, 0, 0.35);
        }

        /*
         * Admin chrome only (.app-sidebar + .app-shell canvas).
         * Neutral system blue-grays — does not override in-page panels (reports, etc.).
         */
        body:has(.app-shell) {
            --admin-chrome-rgb: 96, 108, 120;
            --admin-chrome-accent: #6b7c8f;
            --admin-chrome-ink: #3a424a;
            --admin-chrome-ink-muted: #6b7680;
            --admin-chrome-ink-on-dark: #f3f5f7;
            --admin-chrome-ink-muted-on-dark: #b8c2cc;
            --admin-shell-bg-light: #f0f2f5;
            --admin-shell-bg-dark: #171a1f;
            --admin-sidebar-bg-light: #f8f9fa;
            --admin-sidebar-bg-dark: #252a31;
            --admin-sidebar-border-light: #e4e7eb;
            --admin-sidebar-border-dark: rgba(255, 255, 255, 0.08);
        }

        body.light-mode:has(.app-shell) {
            background: var(--admin-shell-bg-light);
            color: var(--admin-chrome-ink);
        }

        body:not(.light-mode):has(.app-shell) {
            background: var(--admin-shell-bg-dark);
            color: var(--admin-chrome-ink-on-dark);
        }

        body:has(.app-shell)::before {
            background:
                radial-gradient(ellipse 72% 48% at 100% 0%, rgba(var(--admin-chrome-rgb), 0.04), transparent 58%),
                radial-gradient(ellipse 58% 42% at 0% 100%, rgba(var(--admin-chrome-rgb), 0.03), transparent 52%);
        }

        body.light-mode:has(.app-shell) .app-sidebar {
            background: var(--admin-sidebar-bg-light);
            border-right-color: var(--admin-sidebar-border-light);
            box-shadow: 1px 0 0 var(--admin-sidebar-border-light);
        }

        body:not(.light-mode):has(.app-shell) .app-sidebar {
            background: linear-gradient(180deg, #2a2f36 0%, var(--admin-sidebar-bg-dark) 100%);
            border-right-color: var(--admin-sidebar-border-dark);
            box-shadow: none;
        }

        body.light-mode:has(.app-shell) .sidebar-brand,
        body.light-mode:has(.app-shell) .sidebar-footer {
            border-color: var(--admin-sidebar-border-light);
        }

        body:not(.light-mode):has(.app-shell) .sidebar-brand,
        body:not(.light-mode):has(.app-shell) .sidebar-footer {
            border-color: var(--admin-sidebar-border-dark);
        }

        body.light-mode:has(.app-shell) .sidebar-link {
            color: var(--admin-chrome-ink-muted);
        }

        body.light-mode:has(.app-shell) .sidebar-link:hover {
            color: var(--admin-chrome-ink);
            background: rgba(var(--admin-chrome-rgb), 0.08);
        }

        body.light-mode:has(.app-shell) .sidebar-link.active {
            color: var(--admin-chrome-ink);
            background: rgba(var(--admin-chrome-rgb), 0.1);
            box-shadow: inset 3px 0 0 0 var(--admin-chrome-accent);
        }

        body.light-mode:has(.app-shell) .sidebar-link.active i,
        body.light-mode:has(.app-shell) .sidebar-link.active .admin-ui-icon {
            color: var(--admin-chrome-accent);
        }

        body:not(.light-mode):has(.app-shell) .sidebar-link {
            color: var(--admin-chrome-ink-muted-on-dark);
        }

        body:not(.light-mode):has(.app-shell) .sidebar-link:hover {
            color: var(--admin-chrome-ink-on-dark);
            background: rgba(255, 255, 255, 0.06);
        }

        body:not(.light-mode):has(.app-shell) .sidebar-link.active {
            color: var(--admin-chrome-ink-on-dark);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: inset 3px 0 0 0 #8a9aad;
        }

        body:not(.light-mode):has(.app-shell) .sidebar-link.active i,
        body:not(.light-mode):has(.app-shell) .sidebar-link.active .admin-ui-icon {
            color: #c5d0da;
        }

        body:has(.app-shell) .sidebar-footer-label {
            color: var(--admin-chrome-ink-muted);
        }

        body:not(.light-mode):has(.app-shell) .sidebar-footer-label {
            color: var(--admin-chrome-ink-muted-on-dark);
        }

        body.light-mode:has(.app-shell) .sidebar-footer-icon {
            color: var(--admin-chrome-ink);
        }

        body:not(.light-mode):has(.app-shell) .sidebar-footer-icon {
            color: var(--admin-chrome-ink-on-dark);
        }

        body:has(.app-shell) .page-title {
            color: var(--admin-chrome-ink);
        }

        body:not(.light-mode):has(.app-shell) .page-title {
            color: var(--admin-chrome-ink-on-dark);
        }

        body:has(.app-shell) .page-subtitle {
            color: var(--admin-chrome-ink-muted);
        }

        body:not(.light-mode):has(.app-shell) .page-subtitle {
            color: var(--admin-chrome-ink-muted-on-dark);
        }

        body:has(.app-shell) .sidebar-link:focus-visible {
            outline-color: var(--admin-chrome-accent);
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

        body.light-mode:not(:has(.app-shell)) {
            background: var(--app-canvas-bg);
            color: var(--app-ink);
        }

        body::before {
            content: '';
            position: fixed;
            top: 0; right: 0; bottom: 0;
            left: var(--sidebar-w);
            background:
                radial-gradient(ellipse 72% 48% at 100% 0%, rgba(var(--color-primary-rgb), 0.04), transparent 58%),
                radial-gradient(ellipse 58% 42% at 0% 100%, rgba(var(--color-primary-rgb), 0.03), transparent 52%);
            pointer-events: none;
            z-index: 0;
        }

        body.light-mode:not(:has(.app-shell))::before {
            background:
                radial-gradient(ellipse 72% 48% at 100% 0%, rgba(var(--color-primary-rgb), 0.035), transparent 58%),
                radial-gradient(ellipse 58% 42% at 0% 100%, rgba(var(--color-primary-rgb), 0.025), transparent 52%);
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
            box-shadow: 1px 0 0 rgba(0, 0, 0, 0.06);
            transition: background var(--transition), border-color var(--transition), box-shadow var(--transition);
        }

        body:not(.light-mode) .app-sidebar {
            background: linear-gradient(
                180deg,
                color-mix(in srgb, var(--app-sidebar-dark) 96%, #ffffff) 0%,
                var(--app-sidebar-dark) 100%
            );
        }

        body.light-mode .app-sidebar {
            border-right-color: var(--app-border);
            box-shadow: 1px 0 0 rgba(var(--color-primary-rgb), 0.08);
            background: var(--app-sidebar-light);
        }

        .sidebar-brand {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 20px 12px 16px;
            min-height: 0;
            text-align: center;
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

        .sidebar-brand .brand-name {
            margin: 0;
            padding: 0 4px;
            max-width: 100%;
            font-family: var(--font-heading-family);
            font-size: clamp(0.6875rem, 2.4vw, 0.8125rem);
            font-weight: 400;
            letter-spacing: var(--font-heading-letter);
            line-height: 1.35;
            color: var(--app-ink-on-dark);
            white-space: normal;
            overflow: visible;
            text-overflow: unset;
            overflow-wrap: anywhere;
            hyphens: auto;
        }

        body.light-mode:has(.app-shell) .sidebar-brand .brand-name {
            color: var(--color-primary);
        }

        body:not(.light-mode):has(.app-shell) .sidebar-brand .brand-name {
            color: var(--app-ink-on-dark);
        }

        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 2px;
            padding: 12px 8px;
            flex: 1 1 auto;
            min-height: 0;
            min-width: 0;
            overflow-x: hidden;
            overflow-y: auto;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 0;
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

        .sidebar-link i,
        .sidebar-link__icon {
            flex-shrink: 0;
            width: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 1rem;
            opacity: 1;
            color: inherit;
        }

        .sidebar-link__icon .admin-ui-icon {
            display: block;
        }

        .admin-ui-icon {
            display: block;
            flex-shrink: 0;
        }

        .kpi-icon .admin-ui-icon,
        .reports-btn__icon .admin-ui-icon,
        .reports-empty__icon .admin-ui-icon {
            display: block;
        }

        .sidebar-link:hover {
            color: var(--app-ink-on-dark);
            background: var(--bg-elevated);
        }

        .sidebar-link.active {
            color: var(--app-ink-on-dark);
            font-weight: 700;
            background: rgba(255, 255, 255, 0.1);
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
            color: var(--app-ink-deep);
            font-weight: 700;
            background: rgba(var(--color-primary-rgb), 0.08);
            box-shadow: inset 3px 0 0 0 var(--color-primary);
        }

        body.light-mode .sidebar-link.active i {
            color: var(--brand-accent-text);
        }

        .sidebar-link:focus-visible {
            outline: 2px solid var(--brand-accent);
            outline-offset: 2px;
        }

        .sidebar-nav-group {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
            width: 100%;
        }

        .sidebar-nav-group__toggle {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            padding: 10px 14px;
            font-size: var(--font-body-size-sm);
            font-weight: 600;
            font-family: inherit;
            color: var(--app-ink-muted-on-dark);
            text-align: left;
            text-decoration: none;
            background: transparent;
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition:
                background-color 0.08s ease,
                color 0.08s ease,
                box-shadow 0.08s ease;
        }

        .sidebar-nav-group__toggle:hover {
            color: var(--app-ink-on-dark);
            background: var(--bg-elevated);
        }

        .sidebar-nav-group.has-active > .sidebar-nav-group__toggle {
            color: var(--app-ink-on-dark);
            font-weight: 700;
        }

        .sidebar-nav-group__label {
            flex: 1;
            min-width: 0;
        }

        .sidebar-nav-group__chevron {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            opacity: 0.75;
            transition: transform 0.15s ease;
        }

        .sidebar-nav-group.is-open .sidebar-nav-group__chevron {
            transform: rotate(180deg);
        }

        .sidebar-nav-group__menu {
            display: flex;
            flex-direction: column;
            gap: 1px;
            box-sizing: border-box;
            width: calc(100% - 10px);
            margin: 2px 4px 4px 10px;
            padding: 4px 0 4px 8px;
            border-left: 2px solid color-mix(in srgb, var(--app-border-on-dark) 65%, transparent);
        }

        body.light-mode .sidebar-nav-group__menu {
            border-left-color: color-mix(in srgb, var(--app-border) 80%, transparent);
        }

        .sidebar-nav-group:not(.is-open) .sidebar-nav-group__menu {
            display: none;
        }

        .sidebar-link--sub {
            display: block;
            width: 100%;
            max-width: 100%;
            min-width: 0;
            box-sizing: border-box;
            padding: 7px 8px;
            font-size: 0.8125rem;
            font-weight: 600;
            line-height: 1.3;
            white-space: normal;
            overflow-wrap: break-word;
            word-break: break-word;
        }

        .sidebar-link--sub.active {
            box-shadow: inset 3px 0 0 0 var(--brand-accent);
        }

        body.light-mode .sidebar-link--sub.active {
            box-shadow: inset 3px 0 0 0 var(--color-primary);
        }

        .sidebar-link--sub .sidebar-link__icon {
            width: 16px;
        }

        .sidebar-link--sub .sidebar-link__icon .admin-ui-icon {
            width: 16px;
            height: 16px;
        }

        body.light-mode .sidebar-nav-group__toggle {
            color: var(--app-ink-muted);
        }

        body.light-mode .sidebar-nav-group__toggle:hover {
            color: var(--app-ink);
            background: var(--bg-elevated);
        }

        body.light-mode .sidebar-nav-group.has-active > .sidebar-nav-group__toggle {
            color: var(--app-ink-deep);
        }

        .sidebar-nav-group__toggle:focus-visible {
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

        .sidebar-footer-settings {
            display: flex;
            flex-direction: column;
            gap: 10px;
            min-width: 0;
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

        .sidebar-footer-theme-row {
            align-items: center;
        }

        .sidebar-footer-theme {
            display: flex;
            flex: 0 0 auto;
            justify-content: flex-end;
            align-items: center;
            margin-left: auto;
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

        /* Theme switch — visible on light sidebar surface */
        body.light-mode .app-sidebar .sidebar-footer-theme .theme-switch__track {
            background: color-mix(in srgb, var(--app-ink) 6%, #ffffff);
            border-color: var(--app-border-strong);
            box-shadow:
                inset 0 2px 4px rgba(var(--color-primary-rgb), 0.1),
                0 1px 2px rgba(var(--color-primary-rgb), 0.06);
        }

        body.light-mode .app-sidebar .sidebar-footer-theme .theme-switch--show-next:not([aria-checked="true"]) .theme-switch__thumb {
            background: var(--color-primary);
        }

        body.light-mode .app-sidebar .sidebar-footer-theme .theme-switch--show-next:not([aria-checked="true"]) .theme-switch__thumb-icon {
            color: var(--color-secondary);
        }

        body.light-mode .app-sidebar .sidebar-footer-theme .theme-switch--show-next:not([aria-checked="true"]) .theme-switch__icon--moon {
            color: rgba(var(--color-primary-rgb), 0.42);
        }

        /* Theme switch — visible on dark sidebar surface */
        body:not(.light-mode) .app-sidebar .sidebar-footer-theme .theme-switch__track {
            background: rgba(255, 255, 255, 0.14);
            border-color: rgba(255, 255, 255, 0.22);
            box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        body:not(.light-mode) .app-sidebar .sidebar-footer-theme .theme-switch--show-next:not([aria-checked="true"]) .theme-switch__thumb {
            background: var(--color-secondary);
        }

        body:not(.light-mode) .app-sidebar .sidebar-footer-theme .theme-switch--show-next:not([aria-checked="true"]) .theme-switch__thumb-icon {
            color: var(--color-primary);
        }

        body:not(.light-mode) .app-sidebar .sidebar-footer-theme .theme-switch--show-next[aria-checked="true"] .theme-switch__thumb {
            background: #0f172a;
        }

        body:not(.light-mode) .app-sidebar .sidebar-footer-theme .theme-switch--show-next[aria-checked="true"] .theme-switch__thumb-icon {
            color: var(--color-secondary);
        }

        .mobile-topbar {
            display: none !important;
        }

        .app-shell {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            min-width: 0;
            width: calc(100% - var(--sidebar-w));
            margin-left: var(--sidebar-w);
            min-height: 0;
            height: auto;
            box-sizing: border-box;
            overflow: visible;
        }

        /* Content-sized admin pages (dashboard, activity registries). */
        body.page-dashboard .app-main {
            max-width: none;
        }

        /* Full-viewport workspaces (incident registry, DTR). Everything else sizes to content. */
        body.page-incident-reports .app-shell,
        body.page-daily-detail .app-shell,
        body.page-dtr .app-shell {
            display: flex;
            flex-direction: column;
            height: 100dvh;
            min-height: 100dvh;
            max-height: 100dvh;
            overflow: hidden;
        }

        body.page-incident-reports .admin-app__topbar,
        body.page-daily-detail .admin-app__topbar,
        body.page-dtr .admin-app__topbar {
            flex-shrink: 0;
        }

        .app-main {
            flex: 0 0 auto;
            align-self: stretch;
            width: 100%;
            max-width: 1440px;
            margin: 0 auto;
            min-width: 0;
            min-height: 0;
            height: auto;
            font-size: var(--font-body-size);
            line-height: var(--font-body-line-relaxed);
            overflow-x: clip;
            overflow-y: visible;
            padding:
                max(clamp(16px, 2.5vw, 32px), env(safe-area-inset-top, 0px))
                max(clamp(16px, 3vw, 32px), env(safe-area-inset-right, 0px))
                max(clamp(20px, 4vw, 48px), env(safe-area-inset-bottom, 0px))
                max(clamp(16px, 3vw, 32px), env(safe-area-inset-left, 0px));
        }

        .app-main__stage {
            display: block;
            width: 100%;
            min-width: 0;
            min-height: 0;
            height: auto;
            overflow: visible;
        }

        .page-header {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: clamp(8px, 1.25vw, 14px);
            margin-bottom: clamp(24px, 3.5vw, 36px);
        }

        .page-header--inline {
            flex-direction: row;
            flex-wrap: wrap;
            align-items: center;
            gap: clamp(12px, 2vw, 28px);
        }

        .page-header--inline .page-title {
            flex: 0 0 auto;
        }

        .page-header--inline .page-subtitle {
            flex: 1 1 16rem;
            max-width: none;
            margin: 0;
        }

        @media (max-width: 720px) {
            .page-header--inline {
                flex-direction: column;
                align-items: flex-start;
            }

            .page-header--inline .page-subtitle {
                flex: 1 1 auto;
            }
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

        /* Full-height workspaces (incident + DTR only). Activity registries size to content in reports.css. */
        body.page-incident-reports .app-main,
        body.page-daily-detail .app-main,
        body.page-dtr .app-main {
            flex: 1 1 auto;
            align-self: stretch;
            display: flex;
            flex-direction: column;
            width: 100%;
            max-width: none;
            margin: 0;
            min-height: 0;
            overflow: hidden;
            box-sizing: border-box;
        }

        body.page-incident-reports .app-main__stage,
        body.page-daily-detail .app-main__stage,
        body.page-dtr .app-main__stage {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-height: 0;
            min-width: 0;
            width: 100%;
            overflow: hidden;
        }

        .report-hub-placeholder {
            margin-top: 8px;
        }

        .report-hub-placeholder__card {
            max-width: 42rem;
            padding: clamp(1.25rem, 2vw, 1.75rem);
            border-radius: var(--app-radius-lg, 12px);
            border: 1px solid var(--app-border, rgba(0, 0, 0, 0.08));
            background: var(--app-surface-raised, rgba(255, 255, 255, 0.6));
        }

        body:not(.light-mode) .report-hub-placeholder__card {
            border-color: var(--app-border-on-dark, rgba(255, 255, 255, 0.12));
            background: var(--app-surface-dark, rgba(0, 0, 0, 0.2));
        }

        .report-hub-placeholder__icon {
            width: 2.5rem;
            height: 2.5rem;
            margin-bottom: 0.75rem;
            color: var(--app-accent, #c9a227);
        }

        .report-hub-placeholder__title {
            margin: 0 0 0.5rem;
            font-family: var(--font-heading-family);
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--app-ink-deep);
        }

        body:not(.light-mode) .report-hub-placeholder__title {
            color: var(--app-ink-on-dark);
        }

        .report-hub-placeholder__text {
            margin: 0 0 1rem;
            color: var(--app-ink-muted);
            line-height: 1.55;
            max-width: 50ch;
        }

        .report-hub-placeholder__badge {
            display: inline-block;
            padding: 0.2rem 0.55rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            background: rgba(201, 162, 39, 0.15);
            color: var(--app-accent, #9a7b1a);
        }

        .report-hub-placeholder__links {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem 1rem;
            margin-top: 1.25rem;
            padding-top: 1rem;
            border-top: 1px solid var(--app-border, rgba(0, 0, 0, 0.08));
        }

        body:not(.light-mode) .report-hub-placeholder__links {
            border-top-color: var(--app-border-on-dark, rgba(255, 255, 255, 0.1));
        }

        .report-hub-placeholder__link {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--app-accent, #9a7b1a);
            text-decoration: none;
        }

        .report-hub-placeholder__link:hover {
            text-decoration: underline;
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
    foreach (['messaging-board.css', 'inbox.css', 'reports.css', 'admin-notifications.css'] as $file) {
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
<script src="https://kit.fontawesome.com/3142eebea3.js" crossorigin="anonymous"></script>
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

    document.querySelectorAll('[data-sidebar-nav-group]').forEach(function (group) {
        var toggle = group.querySelector('.sidebar-nav-group__toggle');
        var menu = group.querySelector('.sidebar-nav-group__menu');
        if (!toggle || !menu) {
            return;
        }

        toggle.addEventListener('click', function () {
            var open = !group.classList.contains('is-open');
            group.classList.toggle('is-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            menu.hidden = !open;
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
        $reportPrintBrand = [
            'logoUrl' => function_exists('app_logo_url') ? app_logo_url() : app_url('assets/images/goldenz_logo.png'),
            'companyName' => 'Golden Z-5 Security & Investigation, Inc.',
        ];
        echo '<script>window.__reportPrintBrand='
            . json_encode($reportPrintBrand, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
            . ';</script>';
        echo '<script src="' . e(app_url('admin/assets/js/inbox.js')) . '" defer></script>';
        $html2pdfJs = dirname(__DIR__) . '/admin/assets/js/vendor/html2pdf.bundle.min.js';
        if (is_readable($html2pdfJs)) {
            echo '<script src="' . e(app_url('admin/assets/js/vendor/html2pdf.bundle.min.js'))
                . '?v=' . (int) filemtime($html2pdfJs) . '" defer></script>';
        }
        $reportPrintJs = dirname(__DIR__) . '/admin/assets/js/report-print.js';
        if (is_readable($reportPrintJs)) {
            echo '<script src="' . e(app_url('admin/assets/js/report-print.js'))
                . '?v=' . (int) filemtime($reportPrintJs) . '" defer></script>';
        }
        $reportsJs = dirname(__DIR__) . '/admin/assets/js/reports.js';
        echo '<script src="' . e(app_url('admin/assets/js/reports.js'))
            . (is_readable($reportsJs) ? '?v=' . (int) filemtime($reportsJs) : '') . '" defer></script>';
        $dailyDetailJs = dirname(__DIR__) . '/admin/assets/js/daily-detail.js';
        if (is_readable($dailyDetailJs)) {
            echo '<script src="' . e(app_url('admin/assets/js/daily-detail.js'))
                . '?v=' . (int) filemtime($dailyDetailJs) . '" defer></script>';
        }
        $activityRegistryJs = dirname(__DIR__) . '/admin/assets/js/activity-registry.js';
        if (is_readable($activityRegistryJs)) {
            echo '<script src="' . e(app_url('admin/assets/js/activity-registry.js'))
                . '?v=' . (int) filemtime($activityRegistryJs) . '" defer></script>';
        }
        $weeklyWarJs = dirname(__DIR__) . '/admin/assets/js/weekly-war-generate.js';
        if (is_readable($weeklyWarJs)) {
            echo '<script src="' . e(app_url('admin/assets/js/weekly-war-generate.js'))
                . '?v=' . (int) filemtime($weeklyWarJs) . '" defer></script>';
        }
        echo '<script src="' . e(app_url('admin/assets/js/messaging-board.js')) . '" defer></script>';
        echo '<script src="' . e(app_url('admin/assets/js/admin-notifications.js')) . '" defer></script>';
    }
}
