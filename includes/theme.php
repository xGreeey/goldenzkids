<?php
declare(strict_types=1);

if (!function_exists('e')) {
    require_once __DIR__ . '/security.php';
}

/**
 * Shared theme CSS (palette, typography, toggle, auth shell, admin layout).
 * Call once inside <style>: <?php theme_styles(); ?>
 */
function theme_styles(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;
    theme_render_css();
}

/** Google Fonts — Bebas Neue (headings) · Inter (body/UI). */
function app_fonts_link(): string
{
    return <<<'HTML'
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
HTML;
}

/** Hover hint attributes [data-tip] (popover styling disabled globally — see theme_styles). */
function ui_tooltip(string $label, string $position = ''): string
{
    $pos = $position !== '' ? sprintf(' data-tip-pos="%s"', e($position)) : '';

    return sprintf(' data-tip="%s"%s', e($label), $pos);
}

/** iOS-style light/dark pill switch markup. */
function theme_toggle_markup(array $options = []): string
{
    $id = $options['id'] ?? 'themeToggle';
    $mode = ($options['mode'] ?? 'dark-class') === 'light-class' ? 'light-class' : 'dark-class';
    $title = $options['title'] ?? 'Toggle light or dark theme';
    $tipPos = isset($options['tipPosition']) && $options['tipPosition'] === 'bottom' ? 'bottom' : '';

    $sun = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 18a6 6 0 1 1 0-12 6 6 0 0 1 0 12Zm0-16a1 1 0 0 1 1 1v2a1 1 0 1 1-2 0V3a1 1 0 0 1 1-1Zm0 18a1 1 0 0 1 1 1v2a1 1 0 1 1-2 0v-2a1 1 0 0 1 1-1ZM3 11a1 1 0 0 1 1-1h2a1 1 0 1 1 0 2H4a1 1 0 0 1-1-1Zm16 0a1 1 0 0 1 1-1h2a1 1 0 1 1 0 2h-2a1 1 0 0 1-1-1ZM5.64 5.64a1 1 0 0 1 1.41 0l1.42 1.42a1 1 0 1 1-1.41 1.41L5.64 7.05a1 1 0 0 1 0-1.41Zm12.7 12.7a1 1 0 0 1 1.42 0l1.41 1.41a1 1 0 0 1-1.41 1.42l-1.42-1.41a1 1 0 0 1 0-1.42ZM18.36 5.64a1 1 0 0 1 0 1.41l-1.42 1.42a1 1 0 1 1-1.41-1.41l1.41-1.42a1 1 0 0 1 1.42 0ZM7.05 18.36a1 1 0 0 1 0 1.42l-1.41 1.41a1 1 0 0 1-1.42-1.41l1.42-1.42a1 1 0 0 1 1.41 0Z"/></svg>';
    $moon = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M21 14.5A8.5 8.5 0 0 1 9.5 3a7 7 0 1 0 11.5 11.5Z"/></svg>';

    return sprintf(
        '<button type="button" id="%s" class="theme-switch" role="switch" aria-checked="false" aria-label="%s"%s data-theme-mode="%s">'
        . '<span class="theme-switch__track"><span class="theme-switch__icon theme-switch__icon--sun">%s</span>'
        . '<span class="theme-switch__icon theme-switch__icon--moon">%s</span><span class="theme-switch__thumb"></span></span></button>',
        e($id),
        e($title),
        ui_tooltip($title, $tipPos),
        e($mode),
        $sun,
        $moon
    );
}

/** Theme toggle script â€” output before </body>. */
function theme_toggle_script(): void
{
    ?>
<script>
(function () {
    function initThemeSwitches() {
        const toggles = document.querySelectorAll('.theme-switch');
        if (!toggles.length) return;

        const body = document.body;
        const storageKey = 'abc_theme';
        const mode = toggles[0].dataset.themeMode === 'light-class' ? 'light-class' : 'dark-class';

        function isDark() {
            return mode === 'dark-class'
                ? body.classList.contains('dark-mode')
                : !body.classList.contains('light-mode');
        }

        function syncToggles(dark) {
            const tip = dark ? 'Switch to light mode' : 'Switch to dark mode';
            toggles.forEach(function (toggle) {
                toggle.setAttribute('aria-checked', dark ? 'true' : 'false');
                toggle.dataset.tip = tip;
            });
        }

        function applyTheme(dark) {
            if (mode === 'dark-class') {
                body.classList.toggle('dark-mode', dark);
            } else {
                body.classList.toggle('light-mode', !dark);
            }
            localStorage.setItem(storageKey, dark ? 'dark' : 'light');
            syncToggles(dark);
        }

        const saved = localStorage.getItem(storageKey);
        if (saved === 'dark') {
            applyTheme(true);
        } else if (saved === 'light') {
            applyTheme(false);
        } else if (mode === 'light-class') {
            applyTheme(true);
        } else {
            applyTheme(false);
        }

        toggles.forEach(function (toggle) {
            if (toggle.dataset.themeBound === '1') return;
            toggle.addEventListener('click', function () {
                applyTheme(!isDark());
            });
            toggle.dataset.themeBound = '1';
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initThemeSwitches);
    } else {
        initThemeSwitches();
    }
})();
</script>
    <?php
}

/** @internal Base CSS for theme_styles() */
function theme_render_css(): void
{
    ?>
/* --- Brand: primary navy #003049 · secondary gold #ffdd00 --- */
        :root {
            --color-primary: #003049;
            --color-secondary: #ffdd00;
            --color-secondary-text: #9a8200;
            --color-secondary-text-hover: #7a6600;
            --color-white: #ffffff;
            --color-primary-rgb: 0, 48, 73;
            --color-secondary-rgb: 255, 221, 0;
            --color-secondary-text-rgb: 154, 130, 0;
            --color-secondary-text-soft: rgba(154, 130, 0, 0.14);
            --gradient-accent-bar-text: linear-gradient(90deg, #003049 0%, #9a8200 50%, #003049 100%);
            --gradient-brand-bg: linear-gradient(
                155deg,
                #003049 0%,
                #003049 38%,
                color-mix(in srgb, #003049 82%, #ffdd00) 72%,
                color-mix(in srgb, #003049 62%, #ffdd00) 100%
            );
            --gradient-brand-deep: linear-gradient(
                140deg,
                #003049 0%,
                color-mix(in srgb, #003049 78%, #ffdd00) 55%,
                color-mix(in srgb, #003049 58%, #ffdd00) 100%
            );
            --gradient-light-surface: linear-gradient(
                165deg,
                #ffffff 0%,
                color-mix(in srgb, #ffffff 86%, #ffdd00) 32%,
                #ffffff 62%,
                color-mix(in srgb, #ffffff 94%, #003049) 100%
            );
            --gradient-primary-btn: linear-gradient(
                165deg,
                color-mix(in srgb, #003049 88%, #ffffff) 0%,
                #003049 42%,
                color-mix(in srgb, #003049 70%, #ffdd00) 100%
            );
            --gradient-secondary-btn: linear-gradient(
                165deg,
                #ffdd00 0%,
                color-mix(in srgb, #ffdd00 82%, #003049) 100%
            );
            --gradient-signin-btn: linear-gradient(
                135deg,
                #f5d208 0%,
                #ffdd00 28%,
                #ffe566 52%,
                #ffdd00 76%,
                #f0cc00 100%
            );
            --gradient-signin-btn-hover: linear-gradient(
                135deg,
                #f8d800 0%,
                #ffe033 32%,
                #ffeb66 54%,
                #ffdd00 78%,
                #f5d208 100%
            );
            --gradient-auth-blue-light: linear-gradient(
                165deg,
                #ffffff 0%,
                #f5f9fc 40%,
                #ecf3f8 72%,
                color-mix(in srgb, #ffffff 90%, #003049) 100%
            );
            --gradient-auth-blue-dark: linear-gradient(
                155deg,
                #003049 0%,
                #002a42 48%,
                #001e30 100%
            );
            --gradient-accent-bar: linear-gradient(90deg, #003049 0%, #ffdd00 48%, #ffdd00 52%, #003049 100%);
            --gradient-sidebar: linear-gradient(
                185deg,
                #003049 0%,
                color-mix(in srgb, #003049 84%, #ffdd00) 100%
            );
            --gradient-ring: linear-gradient(140deg, #003049 0%, #ffdd00 100%);
            --gradient-panel: linear-gradient(
                180deg,
                #ffffff 0%,
                color-mix(in srgb, #ffffff 96%, #ffdd00) 100%
            );
            --trivium-primary: var(--color-primary);
            --trivium-secondary: var(--color-secondary);
            --trivium-paper: var(--color-white);
            --trivium-ink: var(--color-primary);
            --trivium-ink-muted: rgba(var(--color-primary-rgb), 0.78);
            --trivium-charcoal: var(--color-primary);
            --trivium-gold: var(--color-secondary);
            --trivium-gold-hover: var(--color-secondary);
            --trivium-gold-soft: rgba(var(--color-secondary-rgb), 0.22);
            --trivium-surface: var(--color-white);
            --trivium-btn: var(--color-primary);
            --trivium-btn-hover: var(--color-primary);
            --trivium-btn-text: var(--color-white);
            --trivium-btn-on-dark: var(--color-secondary);
            --trivium-btn-on-dark-text: var(--color-primary);
            --trivium-btn-on-dark-hover: var(--color-secondary);
            --trivium-link: var(--color-secondary-text);
            --trivium-focus: rgba(var(--color-secondary-rgb), 0.35);
            --trivium-ops-btn: var(--color-primary);
            --trivium-ops-btn-hover: var(--color-primary);
            --trivium-ops-btn-text: var(--color-white);
            --trivium-ops-btn-light: var(--color-primary);
            --trivium-ops-btn-light-hover: var(--color-primary);

            /* App surfaces — canvas (main) · sidebar · panels */
            --app-canvas-light: #e3ecf3;
            --app-canvas-light-gradient: linear-gradient(
                168deg,
                #e9f1f8 0%,
                #e2ebf3 42%,
                #d8e5ef 100%
            );
            --app-canvas-dark: #000e16;
            --app-canvas-dark-gradient: linear-gradient(
                168deg,
                #001320 0%,
                #000c14 52%,
                #000810 100%
            );
            --app-sidebar-light: #ffffff;
            --app-sidebar-dark: #003a5c;
            --app-panel-light: #ffffff;
            --app-panel-dark: #002534;
            --app-canvas-bg: var(--app-canvas-dark-gradient);
            --app-sidebar-surface: var(--app-sidebar-dark);
            --app-page-bg-light: var(--app-canvas-light-gradient);
            --app-page-bg-dark: var(--app-canvas-dark-gradient);
            --app-ink-deep: #001e30;
            --app-ink-mid: #002a42;
            --app-card-bg: var(--color-white);
            --app-ink: var(--color-primary);
            --app-ink-muted: var(--trivium-ink-muted);
            --app-ink-soft: rgba(var(--color-primary-rgb), 0.76);
            --app-ink-on-dark: var(--color-white);
            --app-ink-muted-on-dark: rgba(255, 255, 255, 0.9);
            --app-ink-soft-on-dark: rgba(255, 255, 255, 0.76);
            --app-border: rgba(var(--color-primary-rgb), 0.12);
            --app-border-subtle: rgba(var(--color-primary-rgb), 0.08);
            --app-border-strong: rgba(var(--color-primary-rgb), 0.2);
            --app-border-on-dark: rgba(255, 255, 255, 0.1);
            --app-accent: var(--color-secondary);
            --app-accent-text: var(--color-secondary-text);
            --app-accent-soft: var(--color-secondary-text-soft);
            --app-accent-glow: var(--trivium-gold-soft);
            --app-shadow-sm: 0 1px 3px rgba(var(--color-primary-rgb), 0.06);
            --app-btn-gold: var(--gradient-signin-btn);
            --app-btn-gold-hover: var(--gradient-signin-btn-hover);
            --app-sidebar-bg: var(--app-sidebar-dark);
            --app-sidebar-bg-light: var(--app-sidebar-light);
        }

        /* Active theme surfaces (admin · auth) */
        body.light-mode {
            --app-canvas-bg: var(--app-canvas-light-gradient);
            --app-sidebar-surface: var(--app-sidebar-light);
            --app-card-bg: var(--app-panel-light);
            --app-sidebar-bg: var(--app-sidebar-light);
            --app-page-bg-light: var(--app-canvas-light-gradient);
        }

        body:not(.light-mode) {
            --app-canvas-bg: var(--app-canvas-dark-gradient);
            --app-sidebar-surface: var(--app-sidebar-dark);
            --app-card-bg: var(--app-panel-dark);
            --app-sidebar-bg: var(--app-sidebar-dark);
            --app-page-bg-dark: var(--app-canvas-dark-gradient);
            --app-border: rgba(255, 255, 255, 0.14);
            --app-border-subtle: rgba(255, 255, 255, 0.08);
            --app-border-strong: rgba(255, 255, 255, 0.22);
            --app-shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.22);
        }

        /* Auth uses dark-mode class on body */
        body.auth-shell.auth-sign-in.dark-mode,
        body.auth-shell.dark-mode {
            --app-canvas-bg: var(--app-canvas-dark-gradient);
            --app-page-bg-dark: var(--app-canvas-dark-gradient);
        }

        body.auth-shell.auth-sign-in:not(.dark-mode) {
            --app-canvas-bg: var(--app-canvas-light-gradient);
            --app-page-bg-light: var(--app-canvas-light-gradient);
        }

        /* --- Hover tooltips ([data-tip]) — disabled: no cursor/focus popovers site-wide --- */
        [data-tip]::after {
            content: none !important;
            display: none !important;
        }

        /* --- Typography (Bebas Neue · Inter) --- */
        :root {
            --font-heading-family: 'Bebas Neue', system-ui, sans-serif;
            --font-body-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --font-mono: var(--font-body-family);
            --font-primary-family: var(--font-heading-family);
            --font-secondary-family: var(--font-body-family);
            --font-body-weight: 400;
            --font-body-weight-medium: 500;
            --font-body-weight-semibold: 600;
            --font-body-weight-bold: 700;
            --font-body-size: 1.0625rem;
            --font-body-size-sm: 0.9375rem;
            --font-body-size-xs: 0.8125rem;
            --font-body-line: 1.55;
            --font-body-line-relaxed: 1.65;
            --font-heading-line: 1.2;
            --font-heading-line-tight: 1.15;
            --font-heading-letter: 0.01em;
            --font-label-letter: 0.01em;
            --font-eyebrow-size: 0.8125rem;
            --font-eyebrow-weight: 600;
            --font-eyebrow-letter: 0.06em;
            --font-h1-size: clamp(1.75rem, 4vw, 2.25rem);
            --font-h2-size: clamp(1.35rem, 3vw, 1.65rem);
            --font-h3-size: 1.125rem;
        }

        body {
            font-family: var(--font-body-family);
            font-size: var(--font-body-size);
            font-weight: var(--font-body-weight);
            line-height: var(--font-body-line);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: var(--font-heading-family);
            font-weight: 400;
            letter-spacing: var(--font-heading-letter);
            line-height: var(--font-heading-line);
        }

        h1 { font-size: var(--font-h1-size); }
        h2 { font-size: var(--font-h2-size); }
        h3 { font-size: var(--font-h3-size); }

        button, input, select, textarea {
            font-family: inherit;
        }

        /* --- Theme toggle (pill switch) --- */
        .theme-switch {
            flex-shrink: 0;
            padding: 4px;
            margin: 0;
            border: none;
            background: transparent;
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
        }

        .theme-switch:focus { outline: none; }

        .theme-switch:focus-visible .theme-switch__track {
            box-shadow:
                inset 0 2px 5px rgba(var(--color-primary-rgb), 0.16),
                inset 0 1px 2px rgba(var(--color-primary-rgb), 0.1),
                inset 0 -1px 1px rgba(255, 255, 255, 0.85),
                0 0 0 3px var(--trivium-focus);
        }

        .theme-switch__track {
            position: relative;
            display: block;
            width: 56px;
            height: 32px;
            border-radius: 999px;
            background: var(--color-white);
            border: 1px solid rgba(var(--color-primary-rgb), 0.12);
            box-shadow:
                inset 0 2px 5px rgba(var(--color-primary-rgb), 0.14),
                inset 0 1px 2px rgba(var(--color-primary-rgb), 0.1),
                inset 0 -1px 1px rgba(255, 255, 255, 0.9);
            transition:
                background 0.28s cubic-bezier(0.4, 0, 0.2, 1),
                border-color 0.28s ease,
                box-shadow 0.28s ease;
        }

        .theme-switch[aria-checked="true"] .theme-switch__track {
            background: var(--gradient-brand-deep);
            border-color: transparent;
            box-shadow:
                inset 0 3px 7px rgba(0, 0, 0, 0.38),
                inset 0 1px 3px rgba(0, 0, 0, 0.28),
                inset 0 -1px 1px rgba(255, 255, 255, 0.08);
        }

        .theme-switch[aria-checked="true"]:focus-visible .theme-switch__track {
            box-shadow:
                inset 0 3px 7px rgba(0, 0, 0, 0.38),
                inset 0 1px 3px rgba(0, 0, 0, 0.28),
                inset 0 -1px 1px rgba(255, 255, 255, 0.08),
                0 0 0 3px var(--trivium-focus);
        }

        .theme-switch__icon {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            transition: opacity 0.2s ease;
        }

        .theme-switch__icon svg { width: 14px; height: 14px; display: block; }
        .theme-switch__icon--sun { left: 9px; color: var(--color-secondary); opacity: 1; }
        .theme-switch__icon--moon { right: 9px; color: rgba(var(--color-primary-rgb), 0.45); opacity: 1; }

        .theme-switch[aria-checked="true"] .theme-switch__icon--sun { opacity: 0.4; color: rgba(255, 255, 255, 0.7); }
        .theme-switch[aria-checked="true"] .theme-switch__icon--moon { opacity: 1; color: var(--color-secondary); }

        .theme-switch__thumb {
            position: absolute;
            top: 2px;
            left: 2px;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #ffffff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.18), 0 1px 2px rgba(0, 0, 0, 0.12);
            transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .theme-switch[aria-checked="true"] .theme-switch__thumb { transform: translateX(24px); }

/* --- Auth shell (sign-in, forgot password, OTP) â€” use <body class="auth-shell"> --- */
        body.auth-shell,
        body.auth-shell *,
        body.auth-shell *::before,
        body.auth-shell *::after {
            box-sizing: border-box;
        }

        body.auth-shell {
            --color-bg: var(--color-white);
            --color-header: var(--color-white);
            --color-surface: var(--color-white);
            --color-input: var(--color-white);
            --color-border: rgba(var(--color-primary-rgb), 0.14);
            --color-border-subtle: rgba(var(--color-primary-rgb), 0.08);
            --color-text: var(--color-primary);
            --color-text-muted: var(--trivium-ink-muted);
            --color-accent: var(--color-secondary-text);
            --color-accent-hover: var(--color-secondary-text-hover);
            --color-btn: var(--color-primary);
            --color-btn-hover: var(--color-primary);
            --color-btn-text: var(--color-white);
            --color-focus-ring: var(--trivium-gold-soft);
            --error-text: #7f1d1d;
            --success-text: #2d6a4f;
            background: var(--gradient-light-surface);
            color: var(--color-text);
            min-height: 100dvh;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding-left: env(safe-area-inset-left, 0);
            padding-right: env(safe-area-inset-right, 0);
            padding-bottom: env(safe-area-inset-bottom, 0);
            transition: background 0.25s ease, color 0.25s ease;
        }

        html:has(body.auth-sign-in) {
            margin: 0;
            height: 100%;
        }

        body.auth-shell.auth-sign-in {
            margin: 0;
            padding: env(safe-area-inset-top, 0) env(safe-area-inset-right, 0)
                env(safe-area-inset-bottom, 0) env(safe-area-inset-left, 0);
            background: var(--app-canvas-bg);
        }

        body.auth-shell.auth-sign-in.dark-mode {
            background: var(--app-canvas-bg);
        }

        body.auth-shell.auth-sign-in.dark-mode::before {
            background:
                radial-gradient(ellipse 60% 42% at 88% 10%, rgba(255, 255, 255, 0.07), transparent 55%),
                radial-gradient(ellipse 48% 38% at 12% 90%, rgba(0, 0, 0, 0.18), transparent 52%);
        }

        body.auth-shell.auth-sign-in main.main-content {
            margin: 0;
            background: transparent;
        }

        body.auth-shell.dark-mode {
            --color-bg: transparent;
            --color-header: transparent;
            --color-surface: var(--color-white);
            --color-input: var(--color-white);
            --color-border: rgba(var(--color-primary-rgb), 0.12);
            --color-border-subtle: rgba(var(--color-secondary-rgb), 0.35);
            --color-text: var(--color-primary);
            --color-text-muted: var(--trivium-ink-muted);
            --color-accent: var(--color-secondary);
            --color-accent-hover: var(--color-secondary);
            --color-btn: var(--color-secondary);
            --color-btn-hover: var(--color-secondary);
            --color-btn-text: var(--color-primary);
            --color-focus-ring: var(--trivium-gold-soft);
            --error-text: #fecaca;
            --success-text: #8fb5a0;
            background: var(--app-canvas-bg);
        }

        body.auth-shell.dark-mode::before {
            content: '';
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            background:
                radial-gradient(ellipse 65% 45% at 92% 8%, rgba(var(--color-secondary-rgb), 0.16), transparent 58%),
                radial-gradient(ellipse 50% 40% at 8% 92%, rgba(var(--color-secondary-rgb), 0.08), transparent 55%);
        }

        body.auth-shell.dark-mode .main-content,
        body.auth-shell.dark-mode header {
            position: relative;
            z-index: 1;
        }

        body.auth-shell header {
            background: transparent;
            padding: 0 5%;
            height: 72px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--color-border-subtle);
        }

        body.auth-shell .nav-left { display: flex; align-items: center; gap: 16px; }
        body.auth-shell .logo-img {
            width: 48px; height: 48px; border-radius: 50%; object-fit: cover;
            border: 1px solid var(--color-border);
        }
        body.auth-shell .agency-name {
            font-family: var(--font-heading-family);
            font-size: 1.125rem; font-weight: 400;
            letter-spacing: var(--font-heading-letter); color: var(--color-text);
        }
        body.auth-shell .header-actions { display: flex; align-items: center; gap: 16px; }
        body.auth-shell .btn-back, body.auth-shell .forgot-link {
            color: var(--color-accent); text-decoration: none; font-size: 0.875rem; font-weight: 500;
        }
        body.auth-shell .btn-back:hover, body.auth-shell .forgot-link:hover {
            color: var(--color-accent-hover); text-decoration: underline;
        }
        body.auth-shell .main-content {
            flex: 1; display: flex; align-items: center; justify-content: center;
            width: 100%; min-width: 0; max-width: 100%;
            padding: max(24px, env(safe-area-inset-top, 0px)) max(20px, env(safe-area-inset-right, 0px))
                max(24px, env(safe-area-inset-bottom, 0px)) max(20px, env(safe-area-inset-left, 0px));
        }

        body.auth-shell.auth-sign-in .main-content {
            flex: 1;
            min-height: 0;
            padding: 20px 16px;
            align-items: center;
            justify-content: center;
        }

        body.auth-shell .login-card-toolbar {
            display: flex; justify-content: flex-end; align-items: center;
            margin-bottom: 8px; min-height: 44px;
        }
        body.auth-shell .login-card {
            background: var(--gradient-panel, var(--color-surface));
            border: 1px solid var(--color-border-subtle);
            width: 100%; max-width: 440px; min-width: 0; padding: 36px 32px 28px; border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06), 0 8px 24px rgba(0, 0, 0, 0.04);
        }
        body.auth-shell .login-card form,
        body.auth-shell .login-form {
            width: 100%; min-width: 0;
        }
        body.auth-shell.dark-mode .login-card { box-shadow: 0 4px 24px rgba(0, 0, 0, 0.2); }

        /* Sign-in layout (index) — logo above card */
        body.auth-shell.auth-sign-in .login-module {
            width: 100%;
            max-width: 440px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        body.auth-shell.auth-sign-in .login-logo-above {
            text-align: center;
            width: 100%;
            margin: 0 0 16px;
            background: transparent;
            border: none;
            box-shadow: none;
        }
        body.auth-shell.auth-sign-in .login-logo-mark {
            display: block;
            width: 72px;
            height: 72px;
            margin: 0 auto;
            border-radius: 50%;
            object-fit: cover;
            border: none;
            background: transparent;
            box-shadow: none;
        }
        body.auth-shell.dark-mode.auth-sign-in .login-logo-mark {
            box-shadow: none;
        }
        body.auth-shell.auth-sign-in .login-logo-caption {
            margin: 12px 0 0;
            padding: 0 8px;
            box-sizing: content-box;
            font-size: 26px;
            font-weight: 600;
            line-height: 1.35;
            letter-spacing: var(--font-heading-letter);
            color: var(--color-text);
            overflow-wrap: anywhere;
        }
        body.auth-shell.auth-sign-in .login-card {
            position: relative;
            width: 100%;
            max-width: none;
            padding: 24px 24px 28px;
            border-radius: 12px;
            background: var(--color-white);
            box-shadow:
                0 1px 2px rgba(var(--color-primary-rgb), 0.07),
                0 6px 18px rgba(var(--color-primary-rgb), 0.09),
                0 16px 40px rgba(var(--color-primary-rgb), 0.11);
        }
        body.auth-shell.auth-sign-in.dark-mode .login-card {
            background: var(--color-white);
            box-shadow:
                0 2px 6px rgba(0, 0, 0, 0.1),
                0 10px 28px rgba(0, 0, 0, 0.14),
                0 20px 48px rgba(0, 0, 0, 0.12);
        }
        body.auth-shell.auth-sign-in .login-card-toolbar {
            position: absolute;
            top: 20px;
            right: 20px;
            margin: 0;
            min-height: 0;
            z-index: 1;
        }
        body.auth-shell.auth-sign-in .theme-switch:focus-visible .theme-switch__track,
        body.auth-shell.auth-sign-in .theme-switch[aria-checked="true"]:focus-visible .theme-switch__track {
            box-shadow:
                inset 0 2px 5px rgba(var(--color-primary-rgb), 0.14),
                inset 0 1px 2px rgba(var(--color-primary-rgb), 0.1);
        }
        body.auth-shell.auth-sign-in .login-title,
        body.auth-shell.auth-sign-in .login-subtitle,
        body.auth-shell.auth-sign-in .login-support-title,
        body.auth-shell.auth-sign-in .login-support-text {
            text-shadow: none;
        }
        body.auth-shell.auth-sign-in .login-card-intro {
            margin: 0 0 20px;
            padding: 0 36px 0 0;
        }
        body.auth-shell.auth-sign-in .login-title {
            font-family: var(--font-heading-family);
            font-size: 1.5rem;
            font-weight: 400;
            letter-spacing: var(--font-heading-letter);
            color: var(--trivium-ink);
            margin: 0 0 6px;
            line-height: 1.25;
        }
        body.auth-shell.auth-sign-in.dark-mode .login-logo-caption {
            color: var(--color-white);
            text-shadow: none;
        }
        body.auth-shell.auth-sign-in .login-logo-caption {
            font-family: var(--font-heading-family);
            font-weight: 400;
            color: var(--color-primary);
            text-shadow: none;
        }
        body.auth-shell.auth-sign-in .login-subtitle {
            margin: 0;
            font-size: 0.875rem;
            color: var(--color-text-muted);
            line-height: 1.5;
        }
        body.auth-shell.auth-sign-in .input-label {
            color: var(--trivium-ink);
        }
        body.auth-shell.auth-sign-in.dark-mode .input-label {
            color: var(--color-primary);
        }
        body.auth-shell.auth-sign-in .form-input:focus {
            border-color: var(--color-primary);
            box-shadow: none;
            outline: none;
        }
        body.auth-shell.auth-sign-in .login-form {
            display: flex;
            flex-direction: column;
            gap: 0;
        }
        body.auth-shell.auth-sign-in .input-group:last-of-type {
            margin-bottom: 24px;
        }
        body.auth-shell.auth-sign-in .field-hint {
            margin: 8px 0 0;
            font-size: 0.8125rem;
            line-height: 1.45;
            color: var(--color-text-muted);
        }
        body.auth-shell.auth-sign-in .input-group:has(.field-error.visible) .field-hint {
            display: none;
        }
        body.auth-shell.auth-sign-in .btn-signin {
            margin-top: 0;
            font-family: var(--font-body-family);
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            font-size: 0.8125rem;
            background: var(--gradient-signin-btn);
            color: var(--color-primary);
            border: 1px solid rgba(var(--color-secondary-text-rgb), 0.35);
            box-shadow: none;
            filter: none;
            transform: none;
            transition: background 0.2s ease, border-color 0.2s ease;
        }
        body.auth-shell.auth-sign-in .btn-signin:hover:not(:disabled) {
            background: var(--gradient-signin-btn-hover);
            color: var(--color-primary);
            box-shadow: none;
            filter: none;
            transform: none;
        }
        body.auth-shell.auth-sign-in.dark-mode .btn-signin {
            background: var(--gradient-signin-btn);
            color: var(--color-primary);
            border-color: rgba(var(--color-secondary-rgb), 0.45);
            box-shadow: none;
            filter: none;
            transform: none;
        }
        body.auth-shell.auth-sign-in.dark-mode .btn-signin:hover:not(:disabled) {
            background: var(--gradient-signin-btn-hover);
            color: var(--color-primary);
            box-shadow: none;
            filter: none;
            transform: none;
        }
        body.auth-shell.auth-sign-in .login-card-support {
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid var(--color-border-subtle);
            text-align: center;
        }
        body.auth-shell.auth-sign-in .login-support-title {
            margin: 0 0 6px;
            font-size: 0.8125rem;
            font-weight: 600;
            letter-spacing: var(--font-label-letter);
            color: var(--trivium-ink);
        }
        body.auth-shell.auth-sign-in.dark-mode .login-support-title {
            color: var(--color-primary);
        }
        body.auth-shell.auth-sign-in .login-support-text {
            margin: 0;
            font-size: 0.8125rem;
            line-height: 1.5;
            color: var(--color-text-muted);
            max-width: 32ch;
            margin-left: auto;
            margin-right: auto;
        }
        body.auth-shell.auth-sign-in .label-row {
            align-items: center;
            margin-bottom: 8px;
        }
        body.auth-shell.auth-sign-in .forgot-link {
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--color-primary);
            text-decoration: none;
            min-height: 44px;
            padding: 10px 0;
            display: inline-flex;
            align-items: center;
        }
        body.auth-shell.auth-sign-in .forgot-link:hover {
            color: var(--color-primary);
            text-decoration: underline;
        }
        body.auth-shell.auth-sign-in.dark-mode .forgot-link,
        body.auth-shell.auth-sign-in.dark-mode .forgot-link:hover {
            color: var(--color-primary);
        }
        body.auth-shell.auth-sign-in .auth-card-back {
            margin: 0 0 12px;
            padding-right: 36px;
        }
        body.auth-shell.auth-sign-in .auth-card-back .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8125rem;
            font-weight: 600;
            text-decoration: none;
            color: var(--color-primary);
            min-height: 44px;
            padding: 10px 0;
        }
        body.auth-shell.auth-sign-in .auth-card-back .btn-back__icon {
            display: inline-flex;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
            width: 1.125rem;
            height: 1.125rem;
            color: inherit;
        }
        body.auth-shell.auth-sign-in .auth-card-back .btn-back__icon svg {
            display: block;
            width: 1rem;
            height: 1rem;
        }
        body.auth-shell.auth-sign-in .auth-card-back .btn-back__label {
            line-height: 1.25;
        }
        body.auth-shell.auth-sign-in .auth-card-back .btn-back:hover {
            color: var(--color-primary);
            text-decoration: underline;
        }
        body.auth-shell.auth-sign-in.dark-mode .auth-card-back .btn-back,
        body.auth-shell.auth-sign-in.dark-mode .auth-card-back .btn-back:hover {
            color: var(--color-primary);
        }
        body.auth-shell.auth-sign-in .alert-success {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: rgba(125, 164, 142, 0.12);
            border: 1px solid rgba(125, 164, 142, 0.35);
            color: var(--success-text);
            padding: 12px 14px;
            margin-bottom: 20px;
            font-size: 0.875rem;
            line-height: 1.45;
            border-radius: 4px;
        }
        body.auth-shell.auth-sign-in.dark-mode .alert-success {
            background: rgba(143, 181, 160, 0.12);
            border-color: rgba(143, 181, 160, 0.35);
            color: var(--success-text);
        }
        body.auth-shell.auth-sign-in .form-footer {
            margin-top: 20px;
            text-align: center;
            font-size: 0.875rem;
        }
        body.auth-shell.auth-sign-in .form-footer a {
            color: var(--color-secondary-text);
            font-weight: 600;
            text-decoration: none;
        }
        body.auth-shell.auth-sign-in.dark-mode .form-footer a {
            color: var(--color-secondary);
        }
        body.auth-shell.auth-sign-in .form-footer a:hover {
            color: var(--color-secondary-text-hover);
            text-decoration: underline;
        }
        body.auth-shell.auth-sign-in.dark-mode .form-footer a:hover {
            color: var(--color-secondary);
        }
        body.auth-shell.auth-sign-in .auth-dev-notice {
            margin-bottom: 16px;
            padding: 10px 12px;
            font-size: 0.75rem;
            font-family: var(--font-body-family);
            color: var(--color-text-muted);
            background: var(--color-input);
            border: 1px dashed var(--color-border);
            border-radius: 4px;
        }
        body.auth-shell.auth-sign-in .btn-primary {
            width: 100%;
            padding: 14px;
            margin-top: 8px;
            background: var(--gradient-primary-btn);
            color: var(--color-btn-text);
            border: none;
            border-radius: 6px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: none;
            filter: none;
            transform: none;
            transition: background 0.2s ease;
        }
        body.auth-shell.auth-sign-in .btn-primary:hover {
            filter: none;
            transform: none;
            box-shadow: none;
        }

        body.auth-shell .login-title {
            font-size: var(--font-h2-size); font-weight: 600; color: var(--color-text); margin-bottom: 8px;
        }
        body.auth-shell .login-subtitle {
            font-size: 0.9rem; color: var(--color-text-muted); line-height: 1.5; margin-bottom: 28px;
        }
        body.auth-shell .input-group {
            margin-bottom: 20px; width: 100%; min-width: 0;
        }
        body.auth-shell .input-label {
            display: block; font-size: var(--font-body-size-sm); font-weight: 600;
            letter-spacing: var(--font-label-letter); margin-bottom: 8px; color: var(--color-text);
        }
        body.auth-shell .label-row {
            display: flex; justify-content: space-between; align-items: baseline;
            flex-wrap: wrap; gap: 4px 12px; margin-bottom: 8px; width: 100%;
        }
        body.auth-shell .label-row .input-label { margin-bottom: 0; }
        body.auth-shell .forgot-link {
            white-space: nowrap;
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            padding: 4px 0;
        }
        body.auth-shell .input-wrap {
            position: relative; width: 100%; max-width: 100%;
        }
        body.auth-shell .form-input {
            display: block; width: 100%; max-width: 100%; padding: 14px;
            background: var(--color-input); border: 1px solid var(--color-border);
            font-size: 1rem; color: var(--color-text); outline: none; border-radius: 4px;
        }
        body.auth-shell .form-input.no-toggle { padding-right: 14px; }
        body.auth-shell .input-wrap .form-input:not(.no-toggle) { padding-right: 44px; }
        body.auth-shell .btn-toggle-pin {
            position: absolute; right: 4px; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: var(--color-text-muted);
            cursor: pointer; padding: 0; line-height: 1;
            min-width: 44px; min-height: 44px;
            display: inline-flex; align-items: center; justify-content: center;
            transition: color 0.2s ease, background 0.2s ease;
            border-radius: 6px;
        }
        body.auth-shell .btn-toggle-pin .toggle-pin-icon {
            width: 19px;
            height: 19px;
            display: block;
            transition: opacity 0.18s ease, transform 0.18s ease;
            opacity: 1;
            transform: scale(1);
        }
        body.auth-shell .btn-toggle-pin .toggle-pin-icon.is-hidden {
            opacity: 0;
            transform: scale(0.9);
            position: absolute;
            pointer-events: none;
        }
        body.auth-shell .btn-toggle-pin.is-animating .toggle-pin-icon:not(.is-hidden) {
            opacity: 0.86;
            transform: scale(0.96);
        }
        body.auth-shell .btn-toggle-pin:hover {
            color: var(--color-text);
        }
        body.auth-shell .btn-toggle-pin:focus-visible {
            outline: 2px solid var(--color-focus-ring);
            outline-offset: 2px;
        }
        body.auth-shell .form-input.input-error { border-color: var(--error-border, var(--color-border)); }
        body.auth-shell .alert-error {
            display: flex; align-items: flex-start; gap: 10px;
            background: var(--error-bg, var(--color-input)); border: 1px solid var(--error-border, var(--color-border));
            padding: 12px 14px; margin-bottom: 20px; font-size: 0.875rem; line-height: 1.45; border-radius: 4px;
        }
        body.auth-shell .field-error { display: none; font-size: 0.8rem; margin-top: 6px; }
        body.auth-shell .field-error.visible { display: block; }
        body.auth-shell .btn-signin:disabled { opacity: 0.72; cursor: not-allowed; }
        body.auth-shell .login-footer {
            margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--color-border-subtle);
            text-align: center;
        }
        body.auth-shell .form-input:focus {
            border-color: var(--color-accent); box-shadow: 0 0 0 3px var(--color-focus-ring);
        }
        body.auth-shell .form-input::placeholder { color: var(--color-text-muted); }
        body.auth-shell .field-error, body.auth-shell .alert-error { color: var(--error-text); font-size: 0.8rem; }
        body.auth-shell .field-success { color: var(--success-text); font-size: 0.875rem; margin-bottom: 1rem; }
        body.auth-shell .btn-signin, body.auth-shell .btn-primary {
            width: 100%; padding: 14px; margin-top: 8px;
            background: var(--gradient-primary-btn);
            color: var(--color-btn-text); border: none; border-radius: 6px;
            font-size: 0.95rem; font-weight: 600; cursor: pointer;
            box-shadow: 0 2px 10px rgba(var(--color-primary-rgb), 0.22);
            transition: filter 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
        }
        body.auth-shell.dark-mode .btn-signin,
        body.auth-shell.dark-mode .btn-primary {
            background: var(--gradient-secondary-btn);
            box-shadow: 0 2px 12px rgba(var(--color-secondary-rgb), 0.35);
        }
        body.auth-shell .btn-signin:hover:not(:disabled), body.auth-shell .btn-primary:hover {
            filter: brightness(1.05);
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(var(--color-primary-rgb), 0.28);
        }
        body.auth-shell.dark-mode .btn-signin:hover:not(:disabled),
        body.auth-shell.dark-mode .btn-primary:hover {
            box-shadow: 0 4px 16px rgba(var(--color-secondary-rgb), 0.45);
        }
        body.auth-shell .btn-signin:active:not(:disabled), body.auth-shell .btn-primary:active {
            transform: translateY(0);
        }
        body.auth-shell .form-footer, body.auth-shell .login-footer {
            margin-top: 20px; text-align: center; font-size: 0.8rem; color: var(--color-text-muted);
        }
        body.auth-shell.auth-sign-in .login-footer {
            margin-top: 0;
        }
        body.auth-shell .form-footer a { color: var(--color-accent); text-decoration: none; }
        body.auth-shell .form-footer a:hover { color: var(--color-accent-hover); text-decoration: underline; }
        @media (max-width: 600px) {
            body.auth-shell header {
                padding: max(12px, env(safe-area-inset-top, 0px)) max(16px, env(safe-area-inset-right, 0px)) 12px
                    max(16px, env(safe-area-inset-left, 0px));
                height: auto; min-height: 56px;
                flex-wrap: wrap; gap: 12px;
            }
            body.auth-shell .nav-left { min-width: 0; flex: 1 1 auto; }
            body.auth-shell .agency-name {
                font-size: 0.95rem;
                line-height: 1.25;
                overflow-wrap: anywhere;
            }
            body.auth-shell .logo-img { width: 40px; height: 40px; flex-shrink: 0; }
            body.auth-shell .header-actions {
                width: 100%; justify-content: space-between; flex-wrap: wrap; gap: 10px;
            }
            body.auth-shell .btn-back {
                min-height: 44px;
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }
            body.auth-shell .main-content {
                align-items: flex-start;
                padding-top: max(16px, env(safe-area-inset-top, 0px));
                padding-bottom: max(20px, env(safe-area-inset-bottom, 0px));
            }
            body.auth-shell.auth-sign-in .main-content {
                padding: 16px 12px;
            }
            body.auth-shell .login-card {
                padding: 24px max(18px, env(safe-area-inset-right, 0px)) 20px
                    max(18px, env(safe-area-inset-left, 0px));
                border-radius: 8px;
                max-width: 100%;
            }
            body.auth-shell .login-title { font-size: 1.35rem; }
            body.auth-shell .login-subtitle { font-size: 0.875rem; margin-bottom: 22px; }
            body.auth-shell .form-input {
                min-height: 48px;
                padding-top: 12px;
                padding-bottom: 12px;
            }
            body.auth-shell .form-input.no-toggle { padding-right: 14px; }
            body.auth-shell .input-wrap .form-input:not(.no-toggle) { padding-right: 48px; }
            body.auth-shell .label-row { flex-direction: column; align-items: stretch; gap: 6px; }
            body.auth-shell.auth-sign-in .label-row {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
                gap: 8px;
            }
            body.auth-shell .label-row .input-label { margin-bottom: 0; }
            body.auth-shell .forgot-link {
                align-self: flex-start;
                white-space: normal;
            }
            body.auth-shell.auth-sign-in .forgot-link {
                align-self: center;
                white-space: nowrap;
            }
            body.auth-shell.auth-sign-in .login-card-toolbar {
                top: 16px;
                right: 16px;
            }
            body.auth-shell.auth-sign-in .login-logo-above {
                margin-bottom: 12px;
            }
            body.auth-shell.auth-sign-in .login-logo-mark {
                width: 64px;
                height: 64px;
            }
            body.auth-shell.auth-sign-in .login-logo-caption {
                font-size: 0.875rem;
                margin-top: 12px;
            }
            body.auth-shell.auth-sign-in .login-card-intro {
                margin-bottom: 16px;
                padding-right: 32px;
            }
            body.auth-shell.auth-sign-in .login-card {
                padding: 20px 18px 24px;
            }
            body.auth-shell .login-footer {
                font-size: 0.8125rem;
                line-height: 1.5;
            }
        }

        @media (hover: none) and (pointer: coarse) {
            body.auth-shell .btn-signin:hover:not(:disabled),
            body.auth-shell .btn-primary:hover {
                transform: none;
            }
        }
<?php
}
