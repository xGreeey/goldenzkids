<?php
declare(strict_types=1);
?>
        :root {
            --bg-app: #2c3340;
            --bg-surface: #353d4c;
            --bg-elevated: #3e4758;
            --bg-muted: #464f61;
            --border: rgba(200, 208, 220, 0.12);
            --border-strong: rgba(200, 208, 220, 0.22);
            --text-primary: #e8ebf0;
            --text-secondary: #b8c0cc;
            --text-tertiary: #949dad;
            --brand-accent: #c4b89a;
            --brand-accent-soft: rgba(196, 184, 154, 0.18);
            --accent-blue: #8fa8b8;
            --accent-blue-soft: rgba(143, 168, 184, 0.2);
            --success: #8fb5a0;
            --success-soft: rgba(143, 181, 160, 0.18);
            --warning: #c9b896;
            --warning-soft: rgba(201, 184, 150, 0.18);
            --danger: #c9a0a0;
            --danger-soft: rgba(201, 160, 160, 0.18);
            --info: #9eb8c8;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.12);
            --shadow-md: 0 6px 18px rgba(0, 0, 0, 0.14);
            --shadow-lg: 0 16px 36px rgba(0, 0, 0, 0.16);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --font-sans: 'Plus Jakarta Sans', system-ui, sans-serif;
            --font-mono: 'IBM Plex Mono', ui-monospace, monospace;
            --sidebar-w: 248px;
            --transition: 0.22s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body.light-mode {
            --bg-app: #eef1f5;
            --bg-surface: #f9fafb;
            --bg-elevated: #f3f4f6;
            --bg-muted: #e8ebf0;
            --border: rgba(61, 74, 92, 0.1);
            --border-strong: rgba(61, 74, 92, 0.16);
            --text-primary: #3d4a5c;
            --text-secondary: #6b7a8f;
            --text-tertiary: #8d99ae;
            --brand-accent: #a89b7a;
            --brand-accent-soft: rgba(168, 155, 122, 0.16);
            --accent-blue: #7d9aab;
            --accent-blue-soft: rgba(125, 154, 171, 0.16);
            --success: #7da48e;
            --success-soft: rgba(125, 164, 142, 0.16);
            --warning: #c4a882;
            --warning-soft: rgba(196, 168, 130, 0.16);
            --danger: #c48a8a;
            --danger-soft: rgba(196, 138, 138, 0.16);
            --info: #8aaec0;
            --shadow-sm: 0 1px 2px rgba(61, 74, 92, 0.05);
            --shadow-md: 0 6px 18px rgba(61, 74, 92, 0.07);
            --shadow-lg: 0 16px 36px rgba(61, 74, 92, 0.09);
        }

        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: var(--font-sans);
            background: var(--bg-app);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            flex-direction: row;
            line-height: 1.5;
            transition: background var(--transition), color var(--transition);
            -webkit-font-smoothing: antialiased;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0; right: 0; bottom: 0;
            left: var(--sidebar-w);
            background:
                radial-gradient(ellipse 80% 50% at 100% -10%, rgba(143, 168, 184, 0.06), transparent 50%),
                radial-gradient(ellipse 60% 40% at 0% 100%, rgba(196, 184, 154, 0.04), transparent 45%);
            pointer-events: none;
            z-index: 0;
        }

        @media (max-width: 900px) { body::before { left: 0; } }

        body.light-mode::before {
            background:
                radial-gradient(ellipse 80% 50% at 100% -10%, rgba(125, 154, 171, 0.08), transparent 50%),
                radial-gradient(ellipse 60% 40% at 0% 100%, rgba(168, 155, 122, 0.06), transparent 45%);
        }

        .app-sidebar {
            position: fixed; top: 0; left: 0; z-index: 1100;
            width: var(--sidebar-w); height: 100vh;
            display: flex; flex-direction: column;
            background: var(--bg-surface);
            border-right: 1px solid var(--border);
            transition: transform var(--transition), background var(--transition);
        }

        .sidebar-brand {
            display: flex; align-items: center; gap: 12px;
            padding: 24px 20px 20px;
            border-bottom: 1px solid var(--border);
            cursor: default;
            user-select: none;
        }

        .brand-logo {
            width: 42px; height: 42px; border-radius: 10px;
            object-fit: cover; border: 2px solid var(--brand-accent); flex-shrink: 0;
        }

        .brand-text { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
        .brand-name { font-size: 0.875rem; font-weight: 700; color: var(--text-primary); line-height: 1.25; }
        .brand-tagline { font-size: 0.625rem; font-weight: 500; color: var(--text-tertiary); letter-spacing: 0.05em; text-transform: uppercase; }

        .sidebar-nav { display: flex; flex-direction: column; gap: 4px; padding: 8px 12px; flex: 1; }

        .sidebar-link {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 14px; font-size: 0.875rem; font-weight: 600;
            color: var(--text-secondary); text-decoration: none;
            border-radius: 999px; border: 1px solid transparent;
            transition: color var(--transition), background var(--transition), border-color var(--transition);
        }

        .sidebar-link i { width: 18px; text-align: center; font-size: 0.95rem; }
        .sidebar-link:hover { color: var(--text-primary); background: var(--bg-elevated); }
        .sidebar-link.active { color: var(--brand-accent); background: var(--bg-muted); border-color: var(--border); }
        .sidebar-link.active i { color: var(--brand-accent); }

        .sidebar-footer { padding: 16px 12px 12px; border-top: 1px solid var(--border); margin-top: auto; }
        .sidebar-appearance { padding: 0 12px 20px; }

        .btn-appearance {
            display: flex; align-items: center; gap: 12px; width: 100%;
            padding: 12px 14px; font-family: inherit; font-size: 0.875rem; font-weight: 600;
            color: var(--text-secondary); background: var(--bg-elevated);
            border: 1px solid var(--border); border-radius: var(--radius-sm); cursor: pointer;
            transition: color var(--transition), background var(--transition), border-color var(--transition);
        }

        .btn-appearance i { width: 18px; text-align: center; font-size: 0.95rem; }
        .btn-appearance:hover { color: var(--text-primary); border-color: var(--border-strong); background: var(--bg-muted); }

        .sidebar-link--signout { color: var(--danger); border: 1px solid var(--danger-soft); border-radius: var(--radius-sm); }
        .sidebar-link--signout:hover { background: var(--danger-soft); color: var(--danger); }
        .sidebar-link--signout i { color: var(--danger); }

        .app-shell {
            position: relative; z-index: 1; flex: 1; min-width: 0;
            margin-left: var(--sidebar-w); display: flex; flex-direction: column; min-height: 100vh;
        }

        .mobile-topbar {
            display: none; align-items: center; gap: 12px;
            padding: 12px 16px; background: var(--bg-surface);
            border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100;
        }

        .mobile-topbar-title { font-size: 0.875rem; font-weight: 600; color: var(--text-secondary); }

        .btn-menu {
            display: inline-flex; align-items: center; justify-content: center;
            width: 40px; height: 40px; border: 1px solid var(--border);
            border-radius: var(--radius-sm); background: var(--bg-elevated);
            color: var(--text-secondary); cursor: pointer;
        }

        .sidebar-backdrop {
            display: none; position: fixed; inset: 0;
            background: rgba(0, 0, 0, 0.5); z-index: 1050;
            opacity: 0; pointer-events: none; transition: opacity var(--transition);
        }

        body.sidebar-open .sidebar-backdrop { opacity: 1; pointer-events: auto; }

        .app-main {
            flex: 1; max-width: 960px; margin: 0 auto;
            padding: 32px 28px 48px; width: 100%;
        }

        .page-header { margin-bottom: 28px; }
        .page-eyebrow {
            display: inline-flex; align-items: center; gap: 8px;
            font-size: 0.75rem; font-weight: 600; letter-spacing: 0.08em;
            text-transform: uppercase; color: var(--accent-blue); margin-bottom: 8px;
        }
        .page-title { font-size: clamp(1.5rem, 3vw, 2rem); font-weight: 700; letter-spacing: -0.02em; color: var(--text-primary); }
        .page-subtitle { font-size: 0.9375rem; color: var(--text-secondary); margin-top: 8px; }

        .sidebar-logout-form { margin: 0; padding: 0; width: 100%; }
        .sidebar-logout-form button {
            width: 100%;
            text-align: left;
            border: none;
            background: transparent;
            cursor: pointer;
            font: inherit;
        }

        @media (max-width: 900px) {
            .app-sidebar { transform: translateX(-100%); box-shadow: var(--shadow-lg); }
            body.sidebar-open .app-sidebar { transform: translateX(0); }
            .sidebar-backdrop { display: block; }
            .app-shell { margin-left: 0; }
            .mobile-topbar { display: flex; }
            .brand-tagline { display: none; }
            .app-main { padding: 24px 16px 40px; }
        }
<?= mobile_base_css() ?>
