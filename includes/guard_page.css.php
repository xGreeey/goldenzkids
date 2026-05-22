<?php
declare(strict_types=1);

/**
 * Head guard portal — compact web + mobile layout. Scoped to body.guard-portal only.
 */
function guard_page_styles(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;
    ?>
        body.guard-portal {
            --sidebar-w: 220px;
            --guard-gap-xs: 4px;
            --guard-gap-sm: 8px;
            --guard-gap-md: 10px;
            --guard-gap-lg: 14px;
        }

        /* Sidebar brand — logo visible, slightly tighter than default */
        body.guard-portal .sidebar-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 16px 12px;
            min-height: 112px;
            overflow: visible;
            flex-shrink: 0;
        }

        body.guard-portal .brand-logo {
            width: 96px;
            height: 96px;
            max-width: calc(100% - 8px);
            max-height: 96px;
            object-fit: contain;
            flex-shrink: 0;
            display: block;
            margin: 0 auto;
        }

        body.guard-portal .sidebar-nav {
            padding: 8px 6px;
            gap: 1px;
        }

        body.guard-portal .sidebar-link {
            padding: 8px 12px;
            font-size: 0.8125rem;
            gap: 8px;
        }

        body.guard-portal .sidebar-link i {
            width: 16px;
            font-size: 0.9375rem;
        }

        /* Compact footer */
        body.guard-portal .sidebar-footer {
            gap: 8px;
            padding: 10px 10px 12px;
        }

        body.guard-portal .sidebar-footer-user {
            gap: 4px;
            padding: 0 2px;
        }

        body.guard-portal .sidebar-footer-name {
            font-size: 0.875rem;
            line-height: 1.2;
        }

        body.guard-portal .sidebar-footer-role {
            font-size: 0.75rem;
        }

        body.guard-portal .sidebar-footer-email {
            font-size: 0.6875rem;
        }

        body.guard-portal .sidebar-footer-settings {
            gap: 8px;
            padding-top: 8px;
        }

        body.guard-portal .sidebar-footer-icon {
            width: 32px;
            height: 32px;
            min-height: 32px;
        }

        body.guard-portal .sidebar-footer-theme .theme-switch__track {
            width: 48px;
            height: 28px;
        }

        body.guard-portal .sidebar-footer-theme .theme-switch__thumb {
            width: 24px;
            height: 24px;
        }

        body.guard-portal .sidebar-footer-theme .theme-switch[aria-checked="true"] .theme-switch__thumb {
            transform: translateX(20px);
        }

        /* Main content */
        body.guard-portal .app-main {
            max-width: 960px;
            padding:
                max(12px, env(safe-area-inset-top, 0px))
                max(14px, env(safe-area-inset-right, 0px))
                max(16px, env(safe-area-inset-bottom, 0px))
                max(14px, env(safe-area-inset-left, 0px)) !important;
        }

        body.guard-portal .app-main .page-header {
            gap: var(--guard-gap-xs);
            margin-bottom: var(--guard-gap-lg);
        }

        body.guard-portal .app-main .page-title {
            font-size: clamp(1.25rem, 2vw + 0.35rem, 1.5rem);
            line-height: 1.2;
        }

        body.guard-portal .app-main .page-subtitle {
            font-size: 0.75rem;
            line-height: 1.4;
            max-width: 42ch;
        }

        body.guard-portal .card-panel,
        body.guard-portal .sa-panel {
            padding: 10px 12px;
            margin-bottom: var(--guard-gap-md);
        }

        body.guard-portal .panel-title {
            font-size: 0.875rem;
            margin-bottom: var(--guard-gap-sm);
            gap: 8px;
        }

        body.guard-portal .panel-title i {
            display: none;
        }

        /* Dashboard */
        body.guard-portal .sa-dashboard .page-header {
            margin-bottom: var(--guard-gap-md);
        }

        body.guard-portal .sa-dashboard__kpis {
            margin-bottom: var(--guard-gap-md);
        }

        body.guard-portal .sa-stat-grid {
            grid-template-columns: repeat(auto-fit, minmax(132px, 1fr));
            gap: var(--guard-gap-sm);
        }

        body.guard-portal .sa-dashboard .stat-card {
            padding: 10px 12px;
            gap: 8px;
        }

        body.guard-portal .sa-dashboard .stat-icon {
            width: 30px;
            height: 30px;
            font-size: 0.8125rem;
            border-radius: 8px;
        }

        body.guard-portal .sa-dashboard .stat-label {
            font-size: 0.5625rem;
            letter-spacing: 0.04em;
        }

        body.guard-portal .sa-dashboard .stat-value {
            font-size: clamp(1rem, 1.1vw + 0.4rem, 1.2rem);
        }

        body.guard-portal .sa-dashboard .stat-hint {
            font-size: 0.6875rem;
            margin-top: 1px;
        }

        body.guard-portal .stat-value--text {
            font-size: clamp(0.875rem, 1.8vw, 1.0625rem) !important;
            line-height: 1.25;
            word-break: break-word;
        }

        body.guard-portal .sa-quick-actions {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        body.guard-portal .sa-quick-actions__link {
            min-height: 40px;
            padding: 6px 10px;
            font-size: 0.75rem;
            gap: 8px;
        }

        body.guard-portal .sa-quick-actions__icon {
            width: 28px;
            height: 28px;
            font-size: 0.75rem;
        }

        body.guard-portal .sa-dashboard .data-table th,
        body.guard-portal .sa-dashboard .data-table td,
        body.guard-portal .data-table th,
        body.guard-portal .data-table td {
            padding: 6px 8px;
            font-size: 0.75rem;
        }

        body.guard-portal .empty-state {
            padding: 20px 12px;
            font-size: 0.75rem;
        }

        body.guard-portal .empty-state i {
            width: 32px;
            height: 32px;
            font-size: 1rem;
        }

        body.guard-portal .sa-dashboard__footer-cta {
            margin: 8px 0 0;
        }

        /* Inbox memo list */
        body.guard-portal .guard-memo-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: var(--guard-gap-sm);
        }

        body.guard-portal .guard-memo-list__item {
            padding: 10px 12px;
            border-radius: 10px;
            background: var(--sa-card-bg, var(--app-card-bg));
            box-shadow: 0 0 0 1px var(--sa-card-border, var(--app-border)) inset, var(--sa-card-shadow, var(--app-shadow-sm));
        }

        body.guard-portal .guard-memo-list__item.is-unread {
            border-left: 3px solid var(--brand-accent, var(--app-accent));
        }

        body.guard-portal .guard-memo-list__head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
            margin-bottom: 6px;
        }

        body.guard-portal .guard-memo-list__time {
            font-size: 0.75rem;
            color: var(--sa-card-ink-soft, var(--app-ink-soft));
        }

        body.guard-portal .guard-memo-list__body {
            margin: 0 0 8px;
            font-size: 0.8125rem;
            line-height: 1.45;
            color: var(--sa-card-ink, var(--app-ink));
        }

        body.guard-portal .guard-memo-list__action {
            margin: 0;
        }

        /* Tablet: icon rail */
        @media (max-width: 900px) {
            body.guard-portal {
                --sidebar-w: 68px;
            }

            body.guard-portal .sidebar-brand {
                padding: 10px 6px;
                min-height: 68px;
            }

            body.guard-portal .brand-logo {
                width: 52px;
                height: 52px;
                max-width: calc(100% - 4px);
                max-height: 52px;
            }

            body.guard-portal .sidebar-link {
                justify-content: center;
                padding: 10px 6px;
                font-size: 0;
                gap: 0;
            }

            body.guard-portal .sidebar-link i {
                width: auto;
                font-size: 1.0625rem;
            }

            body.guard-portal .sidebar-footer-user,
            body.guard-portal .sidebar-footer-label,
            body.guard-portal .sidebar-footer-email,
            body.guard-portal .sidebar-footer-role,
            body.guard-portal .sidebar-footer-name {
                display: none;
            }

            body.guard-portal .sidebar-footer {
                padding: 8px 4px 10px;
                align-items: center;
            }

            body.guard-portal .sidebar-footer-settings {
                border-top: none;
                padding-top: 0;
                width: 100%;
            }

            body.guard-portal .sidebar-footer-settings-row {
                justify-content: center;
            }

            body.guard-portal .sidebar-footer-actions {
                justify-content: center;
                flex-wrap: wrap;
                gap: 0;
            }

            body.guard-portal .sa-stat-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            body.guard-portal .app-main {
                padding-left: max(10px, env(safe-area-inset-left, 0px)) !important;
                padding-right: max(10px, env(safe-area-inset-right, 0px)) !important;
            }

            body.guard-portal .app-main .page-subtitle {
                font-size: 0.6875rem;
            }

            body.guard-portal .sa-quick-actions {
                grid-template-columns: 1fr;
            }

            body.guard-portal .sa-stat-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 6px;
            }

            body.guard-portal .sa-dashboard .stat-card {
                padding: 8px 10px;
            }

            body.guard-portal .card-panel,
            body.guard-portal .sa-panel {
                padding: 8px 10px;
            }
        }

        @media (max-width: 380px) {
            body.guard-portal .sa-stat-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (orientation: landscape) and (max-height: 520px) {
            body.guard-portal .app-main {
                padding-top: max(8px, env(safe-area-inset-top, 0px)) !important;
                padding-bottom: max(10px, env(safe-area-inset-bottom, 0px)) !important;
            }

            body.guard-portal .app-main .page-header {
                margin-bottom: 10px;
            }

            body.guard-portal .sidebar-brand {
                min-height: 60px;
                padding: 8px 6px;
            }

            body.guard-portal .brand-logo {
                width: 48px;
                height: 48px;
                max-width: calc(100% - 4px);
                max-height: 48px;
            }
        }
    <?php
}
