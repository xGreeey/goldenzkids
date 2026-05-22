<?php
declare(strict_types=1);

function guard_ui_styles(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;
    ?>
        /* Guard enterprise UI — mobile 390px, desktop max 440px centered canvas */
        html:has(body.guard-portal),
        body.guard-portal {
            height: 100%;
            overflow: hidden;
        }

        body.guard-portal {
            --guard-ui-primary: #0f172a;
            --guard-ui-secondary: #475569;
            --guard-ui-subtle: #64748b;
            --guard-ui-faint: #94a3b8;
            --guard-ui-border: #e2e8f0;
            --guard-ui-cream: #f2efe4;
            --guard-ui-surface: #ffffff;
            --guard-ui-radius: 12px;
            --guard-ui-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            --guard-ui-max: 440px;
            --guard-ui-gap: 12px;
            --guard-ui-pad: 16px;
            --guard-ui-touch: 44px;
            --guard-ui-gradient: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            --guard-ui-font: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        body.guard-portal .app-sidebar {
            display: none !important;
        }

        body.guard-portal .app-shell {
            display: flex;
            flex-direction: column;
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            padding: 0 !important;
            height: 100dvh;
            max-height: 100dvh;
            min-height: 0;
            background: var(--guard-ui-gradient);
            box-sizing: border-box;
            overflow: hidden;
            font-family: var(--guard-ui-font);
            color: var(--guard-ui-primary);
        }

        /* Fixed header — only .guard-app__scroll scrolls */
        body.guard-portal .guard-app__topbar {
            position: relative;
            z-index: 50;
            flex: 0 0 auto;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: var(--guard-ui-gap);
            padding: max(12px, env(safe-area-inset-top, 0px)) var(--guard-ui-pad) 12px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--guard-ui-border);
        }

        body.guard-portal .guard-app__brand {
            margin: 0;
            flex: 1;
            min-width: 0;
            font-size: 0.875rem;
            font-weight: 700;
            letter-spacing: -0.025em;
            line-height: 1.25;
            color: var(--guard-ui-primary);
        }

        body.guard-portal .guard-app__menu-btn {
            flex-shrink: 0;
            width: var(--guard-ui-touch);
            height: var(--guard-ui-touch);
            min-width: var(--guard-ui-touch);
            min-height: var(--guard-ui-touch);
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--guard-ui-primary);
            background: transparent;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
            transition: background 0.15s ease, transform 0.12s ease;
        }

        @media (hover: hover) {
            body.guard-portal .guard-app__menu-btn:hover {
                background: #f1f5f9;
            }
        }

        body.guard-portal .guard-app__menu-btn:active {
            transform: scale(0.95);
        }

        body.guard-portal .guard-app__menu-btn.is-open {
            background: var(--guard-ui-cream);
        }

        body.guard-portal .guard-app__menu-btn:focus-visible {
            outline: 2px solid #2563eb;
            outline-offset: 2px;
        }

        body.guard-portal .guard-ui-svg {
            display: block;
            flex-shrink: 0;
        }

        body.guard-portal .guard-app__main {
            flex: 1 1 0;
            display: flex;
            flex-direction: column;
            min-height: 0;
            max-width: none !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden;
            background: transparent;
        }

        body.guard-portal .guard-app__scroll {
            flex: 1 1 0;
            width: 100%;
            min-height: 0;
            height: 100%;
            overflow-x: hidden;
            overflow-y: auto;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
            padding: var(--guard-ui-pad);
            padding-bottom: max(var(--guard-ui-pad), env(safe-area-inset-bottom, 0px));
            display: flex;
            flex-direction: column;
            gap: var(--guard-ui-gap);
            color: var(--guard-ui-primary);
            /* Map hub/superadmin tokens → enterprise palette inside canvas */
            --sa-card-ink: var(--guard-ui-primary);
            --sa-card-ink-muted: var(--guard-ui-secondary);
            --sa-card-ink-soft: var(--guard-ui-subtle);
            --sa-card-bg: var(--guard-ui-surface);
            --sa-card-border: var(--guard-ui-border);
            --sa-input-bg: #f8fafc;
            --sa-input-border: var(--guard-ui-border);
            --guard-surface: var(--guard-ui-surface);
            --guard-surface-muted: #f8fafc;
            --guard-card-border: var(--guard-ui-border);
        }

        body.guard-portal:not(.light-mode) .guard-app__scroll {
            --sa-input-bg: #0f172a;
            --guard-surface-muted: #334155;
        }

        /* Drawer (rendered on body — isolated palette, above app shell) */
        body.guard-portal .guard-app__drawer {
            position: fixed;
            inset: 0;
            z-index: 10000;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        body.guard-portal .guard-app__drawer.is-open {
            pointer-events: auto;
            opacity: 1;
        }

        body.guard-portal .guard-app__drawer-backdrop {
            position: absolute;
            inset: 0;
            z-index: 1;
            background: rgba(15, 23, 42, 0.45);
        }

        body.guard-portal .guard-app__drawer-panel {
            position: absolute;
            top: 0;
            right: 0;
            z-index: 2;
            width: min(280px, 86vw);
            height: 100%;
            max-height: 100dvh;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
            font-family: var(--guard-ui-font);
            background: #ffffff;
            color: #0f172a;
            box-shadow: -8px 0 24px rgba(15, 23, 42, 0.14);
            transform: translateX(100%);
            transition: transform 0.24s cubic-bezier(0.22, 1, 0.36, 1);
            --guard-drawer-ink: #0f172a;
            --guard-drawer-muted: #475569;
            --guard-drawer-soft: #64748b;
            --guard-drawer-border: #e2e8f0;
            --guard-drawer-cream: #f2efe4;
            --guard-drawer-hover: #f8fafc;
        }

        body.guard-portal:not(.light-mode) .guard-app__drawer-panel {
            background: #1e293b;
            color: #f1f5f9;
            box-shadow: -8px 0 28px rgba(0, 0, 0, 0.45);
            --guard-drawer-ink: #f1f5f9;
            --guard-drawer-muted: #cbd5e1;
            --guard-drawer-soft: #94a3b8;
            --guard-drawer-border: #334155;
            --guard-drawer-cream: rgba(242, 239, 228, 0.16);
            --guard-drawer-hover: rgba(51, 65, 85, 0.55);
        }

        body.guard-portal .guard-app__drawer.is-open .guard-app__drawer-panel {
            transform: translateX(0);
        }

        body.guard-portal .guard-app__drawer-head {
            flex-shrink: 0;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 8px;
            padding: max(12px, env(safe-area-inset-top, 0px)) 14px 10px;
            border-bottom: 1px solid var(--guard-drawer-border);
        }

        body.guard-portal .guard-app__drawer-brand {
            margin: 0;
            font-size: 0.75rem;
            font-weight: 700;
            line-height: 1.3;
            color: var(--guard-drawer-ink);
        }

        body.guard-portal .guard-app__drawer-close {
            width: 40px;
            height: 40px;
            min-width: 40px;
            min-height: 40px;
            border: none;
            border-radius: 8px;
            background: var(--guard-drawer-cream);
            color: var(--guard-drawer-ink);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        body.guard-portal .guard-app__drawer-close .guard-ui-svg {
            color: var(--guard-drawer-ink);
        }

        body.guard-portal .guard-app__drawer-nav {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            padding: 8px 10px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        body.guard-portal .guard-app__drawer-link {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 40px;
            padding: 8px 10px;
            font-size: 0.8125rem;
            font-weight: 600;
            line-height: 1.25;
            text-decoration: none;
            color: var(--guard-drawer-muted);
            border-radius: 8px;
            transition: background 0.15s ease, color 0.15s ease;
        }

        body.guard-portal .guard-app__drawer-link-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            min-width: 20px;
            min-height: 20px;
            background: transparent;
            color: var(--guard-drawer-muted);
            flex-shrink: 0;
        }

        body.guard-portal .guard-app__drawer-link-icon .guard-ui-svg {
            color: currentColor;
        }

        body.guard-portal .guard-app__drawer-link.is-active .guard-app__drawer-link-icon {
            color: var(--guard-drawer-ink);
        }

        @media (hover: hover) {
            body.guard-portal .guard-app__drawer-link:hover .guard-app__drawer-link-icon {
                color: var(--guard-drawer-ink);
            }

            body.guard-portal .guard-app__drawer-link:hover {
                color: var(--guard-drawer-ink);
                background: var(--guard-drawer-hover);
            }
        }

        body.guard-portal .guard-app__drawer-link.is-active {
            color: var(--guard-drawer-ink);
            background: var(--guard-drawer-cream);
        }

        body.guard-portal .guard-app__drawer-footer {
            flex-shrink: 0;
            padding: 8px 14px max(10px, env(safe-area-inset-bottom, 0px));
            border-top: 1px solid var(--guard-drawer-border);
            background: inherit;
            color: var(--guard-drawer-ink);
        }

        body.guard-portal .guard-app__drawer-footer .guard-app__profile {
            margin-bottom: 8px;
        }

        body.guard-portal .guard-app__drawer-footer .guard-app__profile-name {
            color: var(--guard-drawer-ink);
        }

        body.guard-portal .guard-app__drawer-footer .guard-app__profile-role {
            color: var(--guard-drawer-muted);
        }

        body.guard-portal .guard-app__drawer-footer .guard-app__profile-email {
            color: var(--guard-drawer-soft);
        }

        body.guard-portal .guard-app__drawer-footer .guard-app__profile-avatar {
            background: var(--guard-drawer-cream);
            color: var(--guard-drawer-ink);
        }

        body.guard-portal .guard-app__drawer-footer .guard-app__settings {
            flex-direction: row;
            align-items: center;
            justify-content: flex-end;
            gap: 0;
            border-top-color: var(--guard-drawer-border);
            padding-top: 8px;
            margin: 0;
        }

        body.guard-portal .guard-app__drawer-footer .guard-app__settings-tools {
            gap: 6px;
            flex-wrap: nowrap;
        }

        body.guard-portal .guard-app__drawer-footer .guard-app__icon-btn {
            border-color: var(--guard-drawer-border);
            background: var(--guard-drawer-cream);
            color: var(--guard-drawer-ink);
        }

        body.guard-portal .guard-app__drawer-footer .guard-app__toolbar-btn {
            width: auto;
            min-width: auto;
            height: 38px;
            min-height: 38px;
            padding: 0 12px 0 10px;
            gap: 8px;
            font-family: inherit;
            font-size: 0.8125rem;
            font-weight: 600;
            line-height: 1;
            white-space: nowrap;
        }

        body.guard-portal .guard-app__drawer-footer .guard-app__toolbar-btn.is-active {
            background: var(--guard-drawer-hover);
            border-color: var(--guard-drawer-border);
        }

        body.guard-portal .guard-app__drawer-footer .guard-app__icon-btn .guard-ui-svg {
            color: var(--guard-drawer-ink);
        }

        @media (hover: hover) {
            body.guard-portal .guard-app__drawer-footer .guard-app__icon-btn:hover {
                background: var(--guard-drawer-hover);
                border-color: var(--guard-drawer-border);
                color: var(--guard-drawer-ink);
            }
        }

        body.guard-portal.guard-app-nav-open {
            overflow: hidden;
        }

        /* Dashboard layout */
        body.guard-portal .guard-dashboard {
            display: flex;
            flex-direction: column;
            gap: var(--guard-ui-gap);
            min-width: 0;
            flex: 1 1 auto;
        }

        body.guard-portal .guard-ui-block {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        body.guard-portal .guard-ui-block--actions {
            margin-top: 4px;
        }

        body.guard-portal .guard-ui-block__heading {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0 0 12px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--guard-ui-subtle);
            line-height: 1.2;
        }

        body.guard-portal .guard-ui-icon-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 26px;
            height: 26px;
            border-radius: 6px;
            background: var(--guard-ui-cream);
            color: var(--guard-ui-primary);
            flex-shrink: 0;
        }

        /* Metric cards — isolated elevated surfaces */
        body.guard-portal .guard-ui-metrics {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: var(--guard-ui-gap);
        }

        body.guard-portal .guard-ui-metric-card {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 12px;
            background: var(--guard-ui-surface);
            border: 1px solid var(--guard-ui-border);
            border-radius: var(--guard-ui-radius);
            box-shadow: var(--guard-ui-shadow);
            min-width: 0;
        }

        body.guard-portal .guard-ui-metric-card__head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 6px;
        }

        body.guard-portal .guard-ui-metric-card__label {
            margin: 0;
            font-size: 0.625rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--guard-ui-secondary);
            line-height: 1.25;
        }

        body.guard-portal .guard-ui-metric-card__icon {
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--guard-ui-primary);
            flex-shrink: 0;
        }

        body.guard-portal .guard-ui-metric-card__value {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1.1;
            color: var(--guard-ui-primary);
            letter-spacing: -0.02em;
        }

        /* Quick action command grid */
        body.guard-portal .guard-ui-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: var(--guard-ui-gap);
        }

        body.guard-portal .guard-ui-action-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 96px;
            padding: 16px 12px;
            text-align: center;
            text-decoration: none;
            color: var(--guard-ui-primary);
            background: var(--guard-ui-surface);
            border: 1px solid var(--guard-ui-border);
            border-radius: var(--guard-ui-radius);
            box-shadow: var(--guard-ui-shadow);
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
            transition: border-color 0.15s ease, transform 0.12s ease, box-shadow 0.15s ease;
        }

        @media (hover: hover) {
            body.guard-portal .guard-ui-action-card:hover {
                border-color: #cbd5e1;
                box-shadow: 0 6px 10px -2px rgba(0, 0, 0, 0.06);
            }
        }

        body.guard-portal .guard-ui-action-card:active {
            transform: scale(0.95);
        }

        body.guard-portal .guard-ui-action-card:focus-visible {
            outline: 2px solid #2563eb;
            outline-offset: 2px;
        }

        body.guard-portal .guard-ui-action-card__icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            border-radius: 8px;
            background: var(--guard-ui-cream);
            color: var(--guard-ui-primary);
        }

        body.guard-portal .guard-ui-action-card__label {
            font-size: 0.8125rem;
            font-weight: 600;
            line-height: 1.25;
            color: var(--guard-ui-primary);
        }

        /* Profile & settings footer */
        body.guard-portal .guard-app__footer {
            flex-shrink: 0;
            margin-top: auto;
            padding: var(--guard-ui-pad);
            background: var(--guard-ui-surface);
            border: 1px solid var(--guard-ui-border);
            border-radius: var(--guard-ui-radius);
            box-shadow: var(--guard-ui-shadow);
        }

        body.guard-portal .guard-app__profile {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        body.guard-portal .guard-app__profile-avatar {
            width: 40px;
            height: 40px;
            min-width: 40px;
            min-height: 40px;
            border-radius: 50%;
            background: var(--guard-ui-cream);
            color: var(--guard-ui-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        body.guard-portal .guard-app__profile-text {
            min-width: 0;
            flex: 1;
        }

        body.guard-portal .guard-app__profile-name {
            margin: 0;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--guard-ui-primary);
            line-height: 1.3;
            word-break: break-word;
        }

        body.guard-portal .guard-app__profile-role {
            margin: 2px 0 0;
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--guard-ui-secondary);
            line-height: 1.3;
        }

        body.guard-portal .guard-app__profile-email {
            margin: 2px 0 0;
            font-size: 0.6875rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            color: var(--guard-ui-faint);
            line-height: 1.35;
            word-break: break-all;
        }

        body.guard-portal .guard-app__settings {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding-top: 12px;
            border-top: 1px solid var(--guard-ui-border);
        }

        body.guard-portal .guard-app__settings-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--guard-ui-faint);
        }

        body.guard-portal .guard-app__settings-tools {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        body.guard-portal .guard-app__icon-btn {
            width: var(--guard-ui-touch);
            height: var(--guard-ui-touch);
            min-width: var(--guard-ui-touch);
            min-height: var(--guard-ui-touch);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0;
            border-radius: 8px;
            border: 1px solid var(--guard-ui-border);
            background: var(--guard-ui-surface);
            color: var(--guard-ui-primary);
            text-decoration: none;
            cursor: pointer;
            transition: background 0.15s ease, border-color 0.15s ease, transform 0.12s ease;
        }

        @media (hover: hover) {
            body.guard-portal .guard-app__icon-btn:hover {
                background: var(--guard-ui-cream);
                border-color: #cbd5e1;
            }
        }

        body.guard-portal .guard-app__icon-btn:active {
            transform: scale(0.96);
        }

        body.guard-portal .guard-app__logout-form {
            margin: 0;
            display: inline-flex;
        }

        body.guard-portal .guard-app__toolbar-btn,
        body.guard-portal .guard-app__logout-btn {
            width: auto;
            min-width: auto;
            padding: 0 12px 0 10px;
            gap: 8px;
            font-family: inherit;
            font-size: 0.8125rem;
            font-weight: 600;
            line-height: 1;
            white-space: nowrap;
        }

        body.guard-portal .guard-app__toolbar-btn-label,
        body.guard-portal .guard-app__logout-btn-label {
            color: inherit;
        }

        body.guard-portal .guard-app__theme-toggle {
            display: flex;
            align-items: center;
            min-height: var(--guard-ui-touch);
        }

        body.guard-portal .guard-app__theme-toggle .theme-toggle,
        body.guard-portal .guard-app__theme-toggle label {
            min-height: var(--guard-ui-touch);
            display: inline-flex;
            align-items: center;
        }

        /* Inner hub pages inside scroll — compact, high-contrast light/dark */
        body.guard-portal .guard-app__scroll .page-header {
            margin: 0 0 8px;
            padding: 0;
            gap: 4px;
        }

        body.guard-portal .guard-app__scroll .page-title {
            margin: 0;
            font-family: var(--guard-ui-font);
            font-size: 1.125rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            line-height: 1.25;
            color: var(--guard-ui-primary);
        }

        body.guard-portal .guard-app__scroll .page-subtitle {
            margin: 0;
            font-family: var(--guard-ui-font);
            font-size: 0.75rem;
            font-weight: 400;
            line-height: 1.4;
            color: var(--guard-ui-subtle);
            max-width: none;
        }

        body.guard-portal .guard-app__scroll .panel-title {
            font-family: var(--guard-ui-font);
            font-size: 0.875rem;
            font-weight: 700;
            color: var(--guard-ui-primary);
            margin: 0 0 10px;
        }

        body.guard-portal .guard-app__scroll .panel-title i {
            color: var(--guard-ui-secondary);
            background: var(--guard-ui-cream);
        }

        body.guard-portal .guard-app__scroll .form-field label,
        body.guard-portal .guard-app__scroll .label-with-icon {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--guard-ui-secondary);
        }

        body.guard-portal .guard-app__scroll .label-with-icon i {
            color: var(--guard-ui-subtle);
        }

        body.guard-portal .guard-app__scroll .form-field input:not([type="checkbox"]):not([type="radio"]),
        body.guard-portal .guard-app__scroll .form-field select:not(.guard-select__native),
        body.guard-portal .guard-app__scroll .form-field textarea {
            min-height: 36px;
            padding: 7px 10px;
            font-size: 0.8125rem;
            color: var(--guard-ui-primary);
            background: var(--sa-input-bg);
            border: 1px solid var(--guard-ui-border);
            border-radius: 8px;
        }

        body.guard-portal .guard-app__scroll .form-field input::placeholder {
            color: var(--guard-ui-faint);
        }

        body.guard-portal .guard-app__scroll .form-hint {
            font-size: 0.6875rem;
            color: var(--guard-ui-subtle);
        }

        body.guard-portal .guard-app__scroll .guard-card,
        body.guard-portal .guard-app__scroll .card-panel,
        body.guard-portal .guard-app__scroll .sa-panel {
            background: var(--guard-ui-surface);
            border: 1px solid var(--guard-ui-border);
            border-radius: var(--guard-ui-radius);
            box-shadow: var(--guard-ui-shadow);
            color: var(--guard-ui-primary);
        }

        body.guard-portal .guard-app__scroll .guard-card__head {
            border-bottom-color: var(--guard-ui-border);
        }

        body.guard-portal .guard-app__scroll .guard-wizard__step {
            color: var(--guard-ui-secondary);
            background: var(--sa-input-bg);
            border-color: var(--guard-ui-border);
        }

        body.guard-portal .guard-app__scroll .guard-wizard__step-num {
            background: var(--guard-ui-border);
            color: var(--guard-ui-primary);
        }

        body.guard-portal .guard-app__scroll .guard-wizard__step.is-active {
            color: var(--guard-ui-primary);
            background: var(--guard-ui-cream);
            border-color: #cbd5e1;
        }

        body.guard-portal:not(.light-mode) .guard-app__scroll .guard-wizard__step.is-active {
            border-color: #475569;
        }

        body.guard-portal .guard-app__scroll .guard-wizard__step.is-active .guard-wizard__step-num {
            background: #475569;
            color: #ffffff;
        }

        body.guard-portal:not(.light-mode) .guard-app__scroll .guard-wizard__step.is-active .guard-wizard__step-num {
            background: var(--guard-ui-cream);
            color: #0f172a;
        }

        body.guard-portal .guard-app__scroll .guard-hub-tabs {
            background: var(--sa-input-bg);
            border: 1px solid var(--guard-ui-border);
        }

        body.guard-portal .guard-app__scroll .guard-hub-tabs__btn {
            color: var(--guard-ui-secondary);
        }

        body.guard-portal .guard-app__scroll .guard-hub-tabs__btn.is-active {
            background: var(--guard-ui-cream);
            color: var(--guard-ui-primary);
            box-shadow: none;
        }

        body.guard-portal .guard-app__scroll .guard-section-stack {
            gap: var(--guard-ui-gap);
        }

        /* Legacy class aliases (SPA-loaded pages) */
        body.guard-portal .guard-ui-section {
            display: flex;
            flex-direction: column;
        }

        body.guard-portal .guard-ui-section__title {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0 0 12px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--guard-ui-subtle);
        }

        body.guard-portal .guard-ui-action {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: var(--guard-ui-touch);
            padding: 12px;
            font-size: 0.8125rem;
            font-weight: 600;
            text-decoration: none;
            color: var(--guard-ui-primary);
            background: var(--guard-ui-surface);
            border: 1px solid var(--guard-ui-border);
            border-radius: var(--guard-ui-radius);
            box-shadow: var(--guard-ui-shadow);
        }

        /* --- Dark mode: same compact density, enterprise slate palette --- */
        body.guard-portal:not(.light-mode) {
            --guard-ui-primary: #f1f5f9;
            --guard-ui-secondary: #94a3b8;
            --guard-ui-subtle: #cbd5e1;
            --guard-ui-faint: #64748b;
            --guard-ui-border: #334155;
            --guard-ui-cream: rgba(242, 239, 228, 0.14);
            --guard-ui-surface: #1e293b;
            --guard-ui-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.25), 0 2px 4px -1px rgba(0, 0, 0, 0.18);
            --guard-ui-gradient: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        }

        body.guard-portal:not(.light-mode) .app-shell {
            background: var(--guard-ui-gradient);
            color: var(--guard-ui-primary);
        }

        body.guard-portal:not(.light-mode) .guard-app__topbar {
            background: rgba(30, 41, 59, 0.92);
            border-bottom-color: var(--guard-ui-border);
        }

        body.guard-portal:not(.light-mode) .guard-app__brand,
        body.guard-portal:not(.light-mode) .guard-app__menu-btn {
            color: var(--guard-ui-primary);
        }

        body.guard-portal:not(.light-mode) .guard-app__menu-btn .guard-ui-svg {
            color: var(--guard-ui-primary);
        }

        body.guard-portal:not(.light-mode) .guard-app__menu-btn:hover {
            background: rgba(51, 65, 85, 0.6);
        }

        body.guard-portal:not(.light-mode) .guard-app__menu-btn.is-open {
            background: var(--guard-ui-cream);
            color: #0f172a;
        }

        body.guard-portal:not(.light-mode) .guard-app__menu-btn.is-open .guard-ui-svg {
            color: #0f172a;
        }

        /* Primary / ghost buttons in canvas (light + dark) */
        body.guard-portal.light-mode .guard-app__scroll .btn-primary,
        body.guard-portal.light-mode .guard-app__scroll button.btn-primary,
        body.guard-portal.light-mode .guard-app__scroll a.btn-primary {
            background-color: #334155;
            border: 1px solid #334155;
            color: #ffffff;
            background-image: none;
        }

        body.guard-portal.light-mode .guard-app__scroll .btn-primary:hover,
        body.guard-portal.light-mode .guard-app__scroll button.btn-primary:hover,
        body.guard-portal.light-mode .guard-app__scroll a.btn-primary:hover {
            background-color: #475569;
            border-color: #475569;
            color: #ffffff;
            transform: none;
        }

        body.guard-portal.light-mode .guard-app__scroll .btn-ghost,
        body.guard-portal.light-mode .guard-app__scroll button.btn-ghost,
        body.guard-portal.light-mode .guard-app__scroll a.btn-ghost,
        body.guard-portal.light-mode .guard-app__scroll label.btn-ghost {
            background: var(--guard-ui-surface);
            border: 1px solid var(--guard-ui-border);
            color: var(--guard-ui-primary);
        }

        body.guard-portal.light-mode .guard-app__scroll .btn-ghost:hover,
        body.guard-portal.light-mode .guard-app__scroll button.btn-ghost:hover,
        body.guard-portal.light-mode .guard-app__scroll a.btn-ghost:hover,
        body.guard-portal.light-mode .guard-app__scroll label.btn-ghost:hover {
            background: var(--guard-ui-cream);
            border-color: #cbd5e1;
            color: var(--guard-ui-primary);
            transform: none;
        }

        body.guard-portal:not(.light-mode) .guard-ui-action-card:hover {
            border-color: #475569;
        }

        body.guard-portal:not(.light-mode) .guard-app__icon-btn:hover {
            background: var(--guard-ui-cream);
            border-color: #475569;
            color: #0f172a;
        }

        body.guard-portal:not(.light-mode) .guard-app__scroll .guard-hub-tabs__btn.is-active {
            color: var(--guard-ui-primary);
        }

        /* Compact theme switch — 44px hit area, 48×28 track (light + dark) */
        body.guard-portal .guard-app__theme-toggle {
            flex-shrink: 0;
            width: 40px;
            min-width: 40px;
            min-height: 40px;
            justify-content: center;
        }

        body.guard-portal .guard-app__drawer-footer .guard-app__theme-toggle {
            width: 40px;
            min-width: 40px;
            min-height: 40px;
        }

        body.guard-portal .guard-app__theme-toggle .theme-switch {
            padding: 0;
            margin: 0;
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        body.guard-portal .guard-app__theme-toggle .theme-switch__track {
            width: 48px;
            height: 28px;
        }

        body.guard-portal .guard-app__theme-toggle .theme-switch__thumb {
            width: 24px;
            height: 24px;
            top: 2px;
            left: 2px;
        }

        body.guard-portal .guard-app__theme-toggle .theme-switch[aria-checked="true"] .theme-switch__thumb {
            transform: translateX(20px);
        }

        body.guard-portal .guard-app__theme-toggle .theme-switch__icon {
            width: 14px;
            height: 14px;
        }

        body.guard-portal .guard-app__theme-toggle .theme-switch__icon svg {
            width: 12px;
            height: 12px;
        }

        body.guard-portal .guard-app__theme-toggle .theme-switch__icon--sun {
            left: 7px;
        }

        body.guard-portal .guard-app__theme-toggle .theme-switch__icon--moon {
            right: 7px;
        }

        body.guard-portal:not(.light-mode) .guard-app__theme-toggle .theme-switch__track {
            background: #334155;
            border-color: #475569;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.35);
        }

        body.guard-portal:not(.light-mode) .guard-app__theme-toggle .theme-switch__thumb {
            background: #f1f5f9;
        }

        /* Settings row: icon buttons + toggle stay on one compact line */
        body.guard-portal .guard-app__settings-tools {
            gap: 6px;
            flex-wrap: nowrap;
        }

        body.guard-portal .guard-app__settings-tools .guard-app__icon-btn,
        body.guard-portal .guard-app__settings-tools .guard-app__logout-form {
            flex-shrink: 0;
        }

        @media (max-width: 380px) {
            body.guard-portal .guard-app__settings-tools {
                flex-wrap: wrap;
            }
        }

        /* Hub / SPA buttons: compact in light and dark inside guard canvas */
        body.guard-portal .guard-app__scroll .btn-primary,
        body.guard-portal .guard-app__scroll button.btn-primary,
        body.guard-portal .guard-app__scroll a.btn-primary,
        body.guard-portal .guard-app__scroll .btn-ghost,
        body.guard-portal .guard-app__scroll button.btn-ghost,
        body.guard-portal .guard-app__scroll a.btn-ghost {
            min-height: 36px;
            padding: 6px 12px;
            font-size: 0.8125rem;
            line-height: 1.25;
            border-radius: 8px;
        }

        body.guard-portal:not(.light-mode) .guard-app__scroll .btn-primary,
        body.guard-portal:not(.light-mode) .guard-app__scroll button.btn-primary,
        body.guard-portal:not(.light-mode) .guard-app__scroll a.btn-primary {
            background-color: #334155;
            border: 1px solid #475569;
            color: #f1f5f9;
            background-image: none;
        }

        body.guard-portal:not(.light-mode) .guard-app__scroll .btn-primary:hover,
        body.guard-portal:not(.light-mode) .guard-app__scroll button.btn-primary:hover,
        body.guard-portal:not(.light-mode) .guard-app__scroll a.btn-primary:hover {
            background-color: #475569;
            background-image: none;
            transform: none;
        }

        body.guard-portal:not(.light-mode) .guard-app__scroll .btn-ghost,
        body.guard-portal:not(.light-mode) .guard-app__scroll button.btn-ghost,
        body.guard-portal:not(.light-mode) .guard-app__scroll a.btn-ghost {
            background: transparent;
            border: 1px solid var(--guard-ui-border);
            color: var(--guard-ui-subtle);
        }

        body.guard-portal:not(.light-mode) .guard-app__scroll .btn-ghost:hover,
        body.guard-portal:not(.light-mode) .guard-app__scroll button.btn-ghost:hover,
        body.guard-portal:not(.light-mode) .guard-app__scroll a.btn-ghost:hover {
            background: rgba(51, 65, 85, 0.5);
            color: var(--guard-ui-primary);
            transform: none;
        }

        body.guard-portal .guard-app__scroll .guard-hub-tabs__btn {
            min-height: 36px;
            padding: 6px 10px;
            font-size: 0.75rem;
        }

        body.guard-portal:not(.light-mode) .guard-app__scroll .guard-hub-tabs {
            background: var(--guard-ui-surface);
            border-color: var(--guard-ui-border);
        }

        body.guard-portal:not(.light-mode) .guard-app__scroll .guard-hub-tabs__btn {
            color: var(--guard-ui-secondary);
        }

        body.guard-portal:not(.light-mode) .guard-app__scroll .guard-hub-tabs__btn.is-active {
            background: var(--guard-ui-cream);
            color: var(--guard-ui-primary);
            box-shadow: none;
        }

        /* Inbox, lists, empty states — explicit contrast (light + dark) */
        body.guard-portal .guard-app__scroll .guard-hub-tabs__btn,
        body.guard-portal .guard-app__scroll .guard-hub-tabs__btn:is(:hover, :focus-visible) {
            color: var(--guard-ui-secondary);
        }

        body.guard-portal .guard-app__scroll .guard-hub-tabs__btn.is-active,
        body.guard-portal .guard-app__scroll .guard-hub-tabs__btn.is-active:is(:hover, :focus-visible) {
            color: var(--guard-ui-primary);
            background: var(--guard-ui-cream);
        }

        body.guard-portal .guard-app__scroll .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin: 0;
            padding: 20px 12px;
            font-size: 0.8125rem;
            font-weight: 500;
            line-height: 1.4;
            text-align: center;
            color: var(--guard-ui-secondary);
            background: var(--sa-input-bg);
            border: 1px dashed var(--guard-ui-border);
            border-radius: var(--guard-ui-radius);
        }

        body.guard-portal .guard-app__scroll .empty-state i {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            border-radius: 8px;
            background: var(--guard-ui-cream);
            color: var(--guard-ui-primary);
            opacity: 1;
        }

        body.guard-portal .guard-app__scroll .guard-memo-list__item {
            background: var(--sa-input-bg);
            border: 1px solid var(--guard-ui-border);
            box-shadow: none;
            color: var(--guard-ui-primary);
        }

        body.guard-portal .guard-app__scroll .guard-memo-list__body {
            color: var(--guard-ui-primary);
        }

        body.guard-portal .guard-app__scroll .guard-memo-list__time {
            color: var(--guard-ui-subtle);
        }

        body.guard-portal .guard-app__scroll .guard-memo-list__item.is-unread {
            border-left: 3px solid #475569;
        }

        body.guard-portal:not(.light-mode) .guard-app__scroll .guard-memo-list__item.is-unread {
            border-left-color: var(--guard-ui-cream);
        }

        body.guard-portal .guard-app__scroll .guard-report-list__item {
            background: var(--sa-input-bg);
            border: 1px solid var(--guard-ui-border);
            color: var(--guard-ui-primary);
        }

        body.guard-portal .guard-app__scroll .guard-report-list__date {
            color: var(--guard-ui-subtle);
        }

        body.guard-portal .guard-app__scroll .guard-report-list__meta {
            color: var(--guard-ui-secondary);
        }

        body.guard-portal .guard-app__scroll .guard-badge {
            color: inherit;
        }

        body.guard-portal .guard-app__scroll .badge {
            font-size: 0.625rem;
            font-weight: 700;
        }

        body.guard-portal .guard-app__scroll .badge--guard {
            background: rgba(71, 85, 105, 0.12);
            color: #334155;
        }

        body.guard-portal .guard-app__scroll .badge--admin {
            background: var(--guard-ui-cream);
            color: #334155;
        }

        body.guard-portal:not(.light-mode) .guard-app__scroll .badge--guard,
        body.guard-portal:not(.light-mode) .guard-app__scroll .badge--admin {
            background: rgba(242, 239, 228, 0.2);
            color: #f1f5f9;
        }

        body.guard-portal .guard-app__scroll .alert {
            font-size: 0.8125rem;
            border-radius: 8px;
        }

        body.guard-portal .guard-app__scroll .alert--error {
            background: rgba(239, 68, 68, 0.12);
            color: #b91c1c;
            border: 1px solid rgba(239, 68, 68, 0.35);
        }

        body.guard-portal:not(.light-mode) .guard-app__scroll .alert--error {
            background: rgba(239, 68, 68, 0.2);
            color: #fecaca;
            border-color: rgba(248, 113, 113, 0.45);
        }

        body.guard-portal .guard-app__scroll .mono,
        body.guard-portal .guard-app__scroll .guard-memo-list__time.mono {
            color: var(--guard-ui-subtle);
        }

        body.guard-portal .guard-app__scroll .guard-card__head .btn-primary {
            min-height: 32px;
            padding: 5px 10px;
            font-size: 0.75rem;
        }

        /* Guard corner — compact hub panels */
        body.guard-portal .guard-app__scroll .guard-corner-page,
        body.guard-portal .guard-app__scroll .guard-corner-page {
            gap: var(--guard-ui-gap);
        }

        body.guard-portal .guard-app__scroll .guard-corner-page .guard-card__head {
            border-bottom: 1px solid var(--guard-ui-border);
            margin-bottom: 10px;
            padding-bottom: 10px;
        }

        body.guard-portal .guard-app__scroll .guard-corner-page .guard-hub-panels {
            gap: var(--guard-ui-gap);
        }

        body.guard-portal .guard-app__scroll .guard-corner-page .guard-chat__input::placeholder {
            color: var(--guard-ui-faint);
        }

        body.guard-portal .guard-app__scroll .guard-corner-page select {
            color: var(--guard-ui-primary);
            background: var(--sa-input-bg);
            border: 1px solid var(--guard-ui-border);
        }

        body.guard-portal .guard-app__scroll .guard-corner-page .guard-feed__item + .guard-feed__item {
            margin-top: 0;
        }

        @media (hover: hover) {
            body.guard-portal .guard-app__scroll .guard-feed__item:hover {
                border-color: #cbd5e1;
            }

            body.guard-portal:not(.light-mode) .guard-app__scroll .guard-feed__item:hover {
                border-color: #475569;
            }
        }

        /* Canvas buttons — override superadmin .app-main (submit report + all hub pages) */
        body.superadmin-portal.guard-portal .guard-app__scroll .btn-primary,
        body.superadmin-portal.guard-portal .guard-app__scroll button.btn-primary,
        body.superadmin-portal.guard-portal .guard-app__scroll a.btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-height: 36px;
            padding: 6px 12px;
            font-size: 0.8125rem;
            font-weight: 600;
            line-height: 1.25;
            border-radius: 8px;
            background-color: #334155;
            border: 1px solid #334155;
            color: #ffffff;
            background-image: none;
            box-shadow: none;
            cursor: pointer;
            -webkit-appearance: none;
            appearance: none;
        }

        body.superadmin-portal.guard-portal .guard-app__scroll .btn-primary:hover,
        body.superadmin-portal.guard-portal .guard-app__scroll button.btn-primary:hover,
        body.superadmin-portal.guard-portal .guard-app__scroll a.btn-primary:hover {
            background-color: #475569;
            border-color: #475569;
            color: #ffffff;
            transform: none;
            filter: none;
        }

        body.superadmin-portal.guard-portal .guard-app__scroll .btn-ghost,
        body.superadmin-portal.guard-portal .guard-app__scroll button.btn-ghost,
        body.superadmin-portal.guard-portal .guard-app__scroll a.btn-ghost,
        body.superadmin-portal.guard-portal .guard-app__scroll label.btn-ghost {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-height: 36px;
            padding: 6px 12px;
            font-size: 0.8125rem;
            font-weight: 600;
            line-height: 1.25;
            border-radius: 8px;
            background: var(--guard-ui-surface);
            border: 1px solid var(--guard-ui-border);
            color: var(--guard-ui-primary);
            cursor: pointer;
            -webkit-appearance: none;
            appearance: none;
        }

        body.superadmin-portal.guard-portal .guard-app__scroll .btn-ghost:hover,
        body.superadmin-portal.guard-portal .guard-app__scroll button.btn-ghost:hover,
        body.superadmin-portal.guard-portal .guard-app__scroll a.btn-ghost:hover,
        body.superadmin-portal.guard-portal .guard-app__scroll label.btn-ghost:hover {
            background: var(--guard-ui-cream);
            border-color: #cbd5e1;
            color: var(--guard-ui-primary);
            transform: none;
        }

        body.superadmin-portal.guard-portal:not(.light-mode) .guard-app__scroll .btn-ghost,
        body.superadmin-portal.guard-portal:not(.light-mode) .guard-app__scroll button.btn-ghost,
        body.superadmin-portal.guard-portal:not(.light-mode) .guard-app__scroll a.btn-ghost,
        body.superadmin-portal.guard-portal:not(.light-mode) .guard-app__scroll label.btn-ghost {
            background: var(--guard-ui-surface);
            border-color: var(--guard-ui-border);
            color: var(--guard-ui-primary);
        }

        body.superadmin-portal.guard-portal:not(.light-mode) .guard-app__scroll .btn-ghost:hover,
        body.superadmin-portal.guard-portal:not(.light-mode) .guard-app__scroll button.btn-ghost:hover,
        body.superadmin-portal.guard-portal:not(.light-mode) .guard-app__scroll a.btn-ghost:hover,
        body.superadmin-portal.guard-portal:not(.light-mode) .guard-app__scroll label.btn-ghost:hover {
            background: rgba(51, 65, 85, 0.55);
            border-color: #475569;
        }

        body.superadmin-portal.guard-portal .guard-app__scroll .btn-ghost i,
        body.superadmin-portal.guard-portal .guard-app__scroll .btn-primary i,
        body.superadmin-portal.guard-portal .guard-app__scroll label.btn-ghost i {
            color: inherit;
        }

    <?php
}
