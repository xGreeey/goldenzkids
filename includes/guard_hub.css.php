<?php
declare(strict_types=1);

function guard_hub_styles(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;
    ?>
        body.guard-portal {
            --guard-card-radius: 12px;
            --guard-card-shadow: 0 1px 2px rgba(15, 23, 42, 0.06), 0 4px 14px rgba(15, 23, 42, 0.06);
            --guard-card-shadow-hover: 0 4px 12px rgba(15, 23, 42, 0.1), 0 8px 24px rgba(15, 23, 42, 0.08);
            --guard-card-border: var(--sa-card-border, var(--app-border));
            --guard-surface: var(--sa-card-bg, var(--app-card-bg));
            --guard-surface-muted: var(--sa-input-bg, var(--app-card-bg));
        }

        body.guard-portal:not(.light-mode) {
            --guard-card-shadow: 0 1px 2px rgba(0, 0, 0, 0.2), 0 4px 14px rgba(0, 0, 0, 0.18);
            --guard-card-shadow-hover: 0 4px 16px rgba(0, 0, 0, 0.28), 0 8px 28px rgba(0, 0, 0, 0.22);
            --guard-surface: var(--guard-ui-surface, #1e293b);
            --guard-surface-muted: rgba(30, 41, 59, 0.85);
            --guard-card-border: var(--guard-ui-border, #334155);
        }

        body.guard-portal .guard-app__scroll .guard-card,
        body.guard-portal .guard-app__scroll .card-panel,
        body.guard-portal .guard-app__scroll .sa-panel {
            padding: 12px 14px;
        }

        /* Page shell — keep scroll on canvas only (do not override overflow-y) */
        body.guard-portal .guard-app__scroll {
            display: flex;
            flex-direction: column;
            gap: var(--guard-gap-md, 12px);
            min-width: 0;
            min-height: 0;
            flex: 1 1 0;
            overflow-x: hidden;
            overflow-y: auto;
            overscroll-behavior: contain;
        }

        body.guard-portal .guard-app__scroll .page-header {
            margin-bottom: 0;
            padding-bottom: 2px;
        }

        /* Unified cards (all tab pages) */
        body.guard-portal .guard-card,
        body.guard-portal .card-panel,
        body.guard-portal .sa-panel {
            position: relative;
            background: var(--guard-surface);
            border: 1px solid var(--guard-card-border);
            border-radius: var(--guard-card-radius);
            padding: 14px 16px;
            margin-bottom: 0;
            box-shadow: var(--guard-card-shadow);
            transition: box-shadow 0.2s ease, transform 0.2s ease, border-color 0.2s ease;
        }

        @media (hover: hover) {
            body.guard-portal .guard-card:hover,
            body.guard-portal .card-panel:hover,
            body.guard-portal .sa-panel:hover {
                box-shadow: var(--guard-card-shadow-hover);
            }

            body.guard-portal .sa-quick-actions__link:hover,
            body.guard-portal .guard-feed__item:hover,
            body.guard-portal .guard-report-list__item:hover {
                transform: translateY(-1px);
                box-shadow: var(--guard-card-shadow-hover);
            }
        }

        body.guard-portal .guard-card__head,
        body.guard-portal .sa-panel__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin: -2px 0 12px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--guard-card-border);
        }

        body.guard-portal .guard-card__head .panel-title,
        body.guard-portal .sa-panel__head .panel-title {
            margin: 0;
            flex: 1;
            min-width: 0;
        }

        body.guard-portal .guard-card__head .btn-primary {
            margin-left: auto;
            flex-shrink: 0;
        }

        body.guard-portal .guard-card__icon {
            width: 32px;
            height: 32px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            background: var(--brand-accent-soft, rgba(0, 0, 0, 0.06));
            color: var(--brand-accent, var(--app-accent));
            font-size: 0.875rem;
        }

        body.guard-portal .guard-app__scroll .guard-card__icon {
            background: var(--guard-ui-cream, #f2efe4);
            color: var(--guard-ui-primary, #0f172a);
        }

        /* Guard corner — plain headers, no accent icon chips */
        body.guard-portal .guard-corner-page .guard-card__icon,
        body.guard-portal .guard-corner-page .guard-feed__icon {
            display: none;
        }

        body.guard-portal .guard-corner-page .panel-title i,
        body.guard-portal .guard-corner-page .guard-hub-tabs__btn i {
            display: none;
        }

        body.guard-portal .guard-corner-page .guard-card__head {
            border-bottom-color: var(--guard-card-border);
        }

        body.guard-portal .guard-corner-page .guard-social-card i {
            color: #1877f2;
            font-size: 1.25rem;
        }

        body.guard-portal .guard-corner-page .guard-social-card span {
            color: var(--guard-ui-primary, #0f172a);
            font-weight: 600;
            font-size: 0.8125rem;
        }

        body.guard-portal .guard-corner-page .guard-social-card small.form-hint {
            color: var(--guard-ui-subtle, #64748b);
        }

        body.guard-portal .guard-corner-page .empty-state i {
            display: none;
        }

        /* Inbox / hub stack — compact headers and empty states without icons */
        body.guard-portal .guard-section-stack .guard-card__icon {
            display: none;
        }

        body.guard-portal .guard-section-stack .guard-card__head {
            margin: -2px 0 10px;
            padding-bottom: 8px;
        }

        body.guard-portal .guard-section-stack .empty-state {
            display: block;
            text-align: left;
            padding: 12px 14px;
        }

        body.guard-portal .guard-section-stack .empty-state i {
            display: none;
        }

        body.guard-portal .guard-submit-card .guard-card__head .guard-report-history-toggle {
            flex-shrink: 0;
            min-height: 36px;
            padding: 6px 12px;
            font-size: 0.8125rem;
            font-weight: 600;
        }

        body.guard-portal .guard-report-history__hint {
            margin: 0 0 10px;
        }

        body.guard-portal .guard-submit-card.is-history-open .guard-wizard {
            display: none;
        }

        /* Responsive page grids */
        body.guard-portal .guard-page-grid {
            display: grid;
            gap: var(--guard-gap-md, 12px);
            min-width: 0;
        }

        body.guard-portal .guard-page-grid--dashboard {
            grid-template-columns: 1fr;
        }

        @media (min-width: 900px) {
            body.guard-portal .guard-page-grid--dashboard {
                grid-template-columns: 1fr minmax(240px, 320px);
                align-items: start;
            }

            body.guard-portal .guard-page-grid--dashboard .guard-page-grid__full {
                grid-column: auto;
            }
        }

        body.guard-portal .guard-page-grid--split {
            grid-template-columns: 1fr;
        }

        @media (min-width: 900px) {
            body.guard-portal .guard-page-grid--split {
                grid-template-columns: 1fr 1fr;
                align-items: start;
            }
        }

        body.guard-portal .guard-page-grid__full {
            grid-column: 1 / -1;
        }

        body.guard-portal .guard-section-stack {
            display: flex;
            flex-direction: column;
            gap: var(--guard-gap-md, 12px);
            min-width: 0;
        }

        body.guard-portal .sa-dashboard .sa-quick-actions {
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        }

        body.guard-portal .sa-dashboard .stat-card {
            border: 1px solid var(--guard-card-border);
            border-radius: 10px;
            box-shadow: none;
            transition: box-shadow 0.2s ease, transform 0.2s ease;
        }

        @media (hover: hover) {
            body.guard-portal .sa-dashboard .stat-card:hover {
                box-shadow: var(--guard-card-shadow);
                transform: translateY(-1px);
            }
        }

        /* Inner hub tabs (Inbox, Guard corner) — enterprise canvas */
        body.guard-portal .guard-app__scroll .guard-hub-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: var(--guard-gap-md, 12px);
            padding: 4px;
            background: var(--sa-input-bg, #f8fafc);
            border-radius: 10px;
            border: 1px solid var(--guard-ui-border, #e2e8f0);
        }

        body.guard-portal .guard-app__scroll .guard-hub-tabs__btn {
            flex: 1 1 auto;
            min-width: 0;
            min-height: 36px;
            padding: 6px 10px;
            font-family: inherit;
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1.25;
            color: var(--guard-ui-secondary, #475569);
            background: transparent;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            -webkit-appearance: none;
            appearance: none;
            transition: background 0.15s ease, color 0.15s ease;
        }

        body.guard-portal .guard-app__scroll .guard-hub-tabs__btn i {
            margin-right: 4px;
            font-size: 0.8125rem;
            color: var(--guard-ui-subtle, #64748b);
        }

        body.guard-portal .guard-app__scroll .guard-hub-tabs__btn.is-active {
            color: var(--guard-ui-primary, #0f172a);
            background: var(--guard-ui-cream, #f2efe4);
            box-shadow: none;
        }

        body.guard-portal .guard-app__scroll .guard-hub-tabs__btn.is-active i {
            color: var(--guard-ui-primary, #0f172a);
        }

        .guard-hub-panels {
            display: flex;
            flex-direction: column;
            gap: var(--guard-gap-md, 12px);
            min-width: 0;
        }

        .guard-hub-panel {
            display: none;
            min-width: 0;
        }

        .guard-hub-panel.is-active {
            display: block;
            animation: guardPanelIn 0.24s cubic-bezier(0.22, 1, 0.36, 1) forwards;
        }

        .guard-hub-panel.is-leaving {
            display: block;
            animation: guardPanelOut 0.16s ease forwards;
        }

        @keyframes guardPanelIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes guardPanelOut {
            from {
                opacity: 1;
                transform: translateY(0);
            }
            to {
                opacity: 0;
                transform: translateY(-4px);
            }
        }

        @keyframes guardHubFade {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Report wizard + scanner + evidence — enterprise canvas */
        body.guard-portal .guard-app__scroll .guard-wizard__steps {
            display: flex;
            align-items: stretch;
            gap: 4px;
            margin-bottom: 12px;
        }

        body.guard-portal .guard-app__scroll .guard-wizard__step {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            min-width: 0;
            padding: 6px 6px;
            font-size: 0.625rem;
            font-weight: 600;
            line-height: 1.2;
            text-align: center;
            color: var(--guard-ui-secondary, #475569);
            background: var(--sa-input-bg, #f8fafc);
            border-radius: 8px;
            border: 1px solid var(--guard-ui-border, #e2e8f0);
            transition: border-color 0.15s ease, color 0.15s ease, background 0.15s ease;
        }

        body.guard-portal .guard-app__scroll .guard-wizard__step-num {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 0.625rem;
            background: var(--guard-ui-border, #e2e8f0);
            color: var(--guard-ui-primary, #0f172a);
        }

        body.guard-portal .guard-app__scroll .guard-wizard__step.is-active {
            color: var(--guard-ui-primary, #0f172a);
            background: var(--guard-ui-cream, #f2efe4);
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
            background: var(--guard-ui-cream, rgba(242, 239, 228, 0.16));
            color: #0f172a;
        }

        body.guard-portal .guard-app__scroll .guard-wizard__step.is-done .guard-wizard__step-num {
            background: #16a34a;
            color: #ffffff;
        }

        body.guard-portal .guard-app__scroll .guard-wizard__step span:not(.guard-wizard__step-num) {
            display: inline;
        }

        body.guard-portal .guard-app__scroll .guard-wizard__pane {
            display: none;
            color: var(--guard-ui-primary, #0f172a);
        }

        body.guard-portal .guard-app__scroll .guard-wizard__pane.is-active {
            display: block;
            animation: guardHubFade 0.22s ease;
        }

        body.guard-portal .guard-app__scroll .guard-wizard__pane .panel-title {
            color: var(--guard-ui-primary, #0f172a);
        }

        body.guard-portal .guard-app__scroll .guard-wizard__pane .form-hint {
            color: var(--guard-ui-subtle, #64748b);
        }

        body.guard-portal .guard-app__scroll .guard-wizard__pane .form-hint strong {
            color: var(--guard-ui-primary, #0f172a);
            font-weight: 600;
        }

        body.guard-portal .guard-app__scroll .guard-wizard__pane-title {
            font-size: 0.8125rem;
            margin: 0 0 10px;
        }

        body.guard-portal .guard-app__scroll .guard-wizard__report-type {
            margin: 0 0 12px;
        }

        body.guard-portal .guard-app__scroll .guard-wizard__review-type {
            margin: 0 0 10px;
        }

        body.guard-portal .guard-app__scroll .guard-select {
            position: relative;
            width: 100%;
        }

        /* Report type — neutral only (no brand/accent tints on field or options) */
        body.guard-portal .guard-app__scroll .guard-wizard__report-type .guard-select__native,
        body.guard-portal .guard-app__scroll .guard-select__native {
            width: 100%;
            min-height: 44px;
            padding: 10px 40px 10px 14px;
            font-family: inherit;
            font-size: 0.875rem;
            font-weight: 500;
            line-height: 1.3;
            color: #111827;
            background-color: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            box-shadow: none;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            accent-color: #6b7280;
            color-scheme: light;
            transition: border-color 0.15s ease;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 18px 18px;
        }

        body.guard-portal .guard-app__scroll .guard-select__native:hover {
            border-color: #d1d5db;
            background-color: #ffffff;
        }

        body.guard-portal .guard-app__scroll .guard-select__native:focus,
        body.guard-portal.superadmin-portal .form-field .guard-select__native:focus {
            outline: none;
            border-color: #9ca3af;
            box-shadow: none;
            background-color: #ffffff;
        }

        body.guard-portal .guard-app__scroll .guard-select__native:invalid {
            color: #111827;
        }

        body.guard-portal .guard-app__scroll .guard-select__native option,
        body.guard-portal .guard-app__scroll .guard-select__native optgroup {
            background-color: #ffffff;
            color: #111827;
        }

        body.guard-portal:not(.light-mode) .guard-app__scroll .guard-wizard__report-type .guard-select__native,
        body.guard-portal:not(.light-mode) .guard-app__scroll .guard-select__native {
            color: #111827;
            background-color: #ffffff;
            border-color: #d1d5db;
            color-scheme: light;
        }

        body.guard-portal:not(.light-mode) .guard-app__scroll .guard-select__native:hover,
        body.guard-portal:not(.light-mode) .guard-app__scroll .guard-select__native:focus,
        body.guard-portal.superadmin-portal:not(.light-mode) .form-field .guard-select__native:focus {
            background-color: #ffffff;
            border-color: #9ca3af;
            box-shadow: none;
        }

        body.guard-portal:not(.light-mode) .guard-app__scroll .guard-select__native option,
        body.guard-portal:not(.light-mode) .guard-app__scroll .guard-select__native optgroup {
            background-color: #ffffff;
            color: #111827;
        }

        body.guard-portal .guard-app__scroll .guard-scanner {
            position: relative;
            width: 100%;
            max-width: min(320px, 100%);
            margin-left: auto;
            margin-right: auto;
            border-radius: 10px;
            overflow: hidden;
            background: #000;
            line-height: 0;
            border: 1px solid var(--guard-ui-border, #e2e8f0);
        }

        body.guard-portal .guard-app__scroll .guard-scanner__video,
        body.guard-portal .guard-app__scroll .guard-scanner__preview {
            position: relative;
            z-index: 0;
            display: block;
            width: 100%;
            height: auto;
            max-height: min(70vh, 560px);
            margin: 0;
            object-fit: none;
            object-position: center center;
            vertical-align: top;
        }

        body.guard-portal .guard-app__scroll .guard-scanner__preview {
            display: none;
        }

        body.guard-portal .guard-app__scroll .guard-scanner.has-capture .guard-scanner__video {
            display: none;
        }

        body.guard-portal .guard-app__scroll .guard-scanner.has-capture .guard-scanner__preview {
            display: block;
        }

        body.guard-portal .guard-app__scroll .guard-scanner__frame {
            display: none;
            pointer-events: none;
        }

        body.guard-portal .guard-app__scroll .guard-scanner.is-capturing .guard-scanner__frame {
            display: block;
            position: absolute;
            inset: 8%;
            z-index: 1;
            border: 2px solid rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            box-shadow: none;
            animation: guardScanPulse 0.8s ease infinite;
        }

        body.guard-portal .guard-app__scroll .guard-scanner__torch {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 3;
            width: 40px;
            height: 40px;
            min-width: 40px;
            min-height: 40px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.55);
            color: #ffffff;
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
            transition: background 0.15s ease, color 0.15s ease, transform 0.12s ease;
        }

        body.guard-portal .guard-app__scroll .guard-scanner__torch[hidden] {
            display: none !important;
        }

        body.guard-portal .guard-app__scroll .guard-scanner__torch.is-on {
            background: #fbbf24;
            color: #0f172a;
        }

        @media (hover: hover) {
            body.guard-portal .guard-app__scroll .guard-scanner__torch:hover {
                background: rgba(15, 23, 42, 0.75);
            }

            body.guard-portal .guard-app__scroll .guard-scanner__torch.is-on:hover {
                background: #f59e0b;
            }
        }

        body.guard-portal .guard-app__scroll .guard-scanner__torch:active {
            transform: scale(0.94);
        }

        body.guard-portal .guard-app__scroll .guard-scanner.has-capture .guard-scanner__torch {
            display: none !important;
        }

        body.guard-portal .guard-app__scroll .guard-scanner__hint {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 2;
            padding: 8px 10px;
            font-size: 0.75rem;
            font-weight: 600;
            text-align: center;
            color: #ffffff;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.75));
        }

        body.guard-portal .guard-app__scroll .guard-scanner__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        body.guard-portal .guard-app__scroll .guard-scanner__actions .btn-primary,
        body.guard-portal .guard-app__scroll .guard-scanner__actions .btn-ghost,
        body.guard-portal .guard-app__scroll .guard-scanner__actions label.btn-ghost,
        body.guard-portal .guard-app__scroll .guard-wizard__pane button,
        body.guard-portal .guard-app__scroll .guard-wizard__submit {
            -webkit-appearance: none;
            appearance: none;
            font-family: inherit;
        }

        body.guard-portal .guard-app__scroll .guard-evidence-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(96px, 1fr));
            gap: 8px;
            margin-top: 10px;
        }

        body.guard-portal .guard-app__scroll .guard-evidence-card {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            background: var(--sa-input-bg, #f8fafc);
            border: 1px solid var(--guard-ui-border, #e2e8f0);
            aspect-ratio: 1;
        }

        body.guard-portal .guard-app__scroll .guard-evidence-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        body.guard-portal .guard-app__scroll .guard-evidence-card__meta {
            padding: 4px 6px;
            font-size: 0.5625rem;
            line-height: 1.3;
            color: var(--guard-ui-subtle, #64748b);
            background: var(--guard-ui-surface, #ffffff);
        }

        body.guard-portal:not(.light-mode) .guard-app__scroll .guard-evidence-card__meta {
            background: var(--guard-ui-surface, #1e293b);
            color: var(--guard-ui-secondary, #94a3b8);
        }

        body.guard-portal .guard-app__scroll .guard-evidence-card__remove {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 24px;
            height: 24px;
            padding: 0;
            border: none;
            border-radius: 6px;
            background: rgba(15, 23, 42, 0.72);
            color: #ffffff;
            cursor: pointer;
            font-size: 0.75rem;
        }

        /* Feed, chat, social, policies — enterprise canvas */
        body.guard-portal .guard-app__scroll .guard-feed {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        body.guard-portal .guard-app__scroll .guard-feed__item {
            padding: 10px 12px;
            border-radius: 10px;
            background: var(--sa-input-bg, #f8fafc);
            border: 1px solid var(--guard-ui-border, #e2e8f0);
            box-shadow: none;
            transition: border-color 0.15s ease;
        }

        body.guard-portal .guard-app__scroll .guard-feed__head {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 6px;
        }

        body.guard-portal .guard-app__scroll .guard-feed__title {
            margin: 0;
            font-size: 0.8125rem;
            font-weight: 700;
            color: var(--guard-ui-primary, #0f172a);
        }

        body.guard-portal .guard-app__scroll .guard-feed__time {
            font-size: 0.6875rem;
            color: var(--guard-ui-subtle, #64748b);
        }

        body.guard-portal .guard-app__scroll .guard-feed__body {
            margin: 0;
            font-size: 0.75rem;
            line-height: 1.45;
            color: var(--guard-ui-secondary, #475569);
        }

        body.guard-portal .guard-app__scroll .guard-chat {
            display: flex;
            flex-direction: column;
            min-height: 240px;
            max-height: min(380px, 50vh);
            border: 1px solid var(--guard-ui-border, #e2e8f0);
            border-radius: 10px;
            overflow: hidden;
            background: var(--sa-input-bg, #f8fafc);
        }

        body.guard-portal .guard-app__scroll .guard-chat__messages {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            -webkit-overflow-scrolling: touch;
        }

        body.guard-portal .guard-app__scroll .guard-chat__bubble {
            max-width: 88%;
            padding: 8px 10px;
            border-radius: 10px;
            font-size: 0.8125rem;
            line-height: 1.4;
        }

        body.guard-portal .guard-app__scroll .guard-chat__bubble--mine {
            align-self: flex-end;
            background: #334155;
            color: #ffffff;
            border-bottom-right-radius: 4px;
        }

        body.guard-portal .guard-app__scroll .guard-chat__bubble--theirs {
            align-self: flex-start;
            background: var(--guard-ui-surface, #ffffff);
            color: var(--guard-ui-primary, #0f172a);
            border: 1px solid var(--guard-ui-border, #e2e8f0);
            border-bottom-left-radius: 4px;
        }

        body.guard-portal:not(.light-mode) .guard-app__scroll .guard-chat__bubble--theirs {
            background: #0f172a;
            color: #f1f5f9;
            border-color: var(--guard-ui-border, #334155);
        }

        body.guard-portal .guard-app__scroll .guard-chat__time {
            display: block;
            margin-top: 4px;
            font-size: 0.625rem;
            opacity: 0.8;
            color: inherit;
        }

        body.guard-portal .guard-app__scroll .guard-chat__compose {
            display: flex;
            gap: 6px;
            padding: 8px;
            border-top: 1px solid var(--guard-ui-border, #e2e8f0);
            background: var(--guard-ui-surface, #ffffff);
        }

        body.guard-portal:not(.light-mode) .guard-app__scroll .guard-chat__compose {
            background: var(--guard-ui-surface, #1e293b);
        }

        body.guard-portal .guard-app__scroll .guard-chat__input {
            flex: 1;
            min-height: 36px;
            max-height: 80px;
            padding: 8px 10px;
            font-family: inherit;
            font-size: 0.8125rem;
            border: 1px solid var(--guard-ui-border, #e2e8f0);
            border-radius: 8px;
            resize: none;
            background: var(--sa-input-bg, #f8fafc);
            color: var(--guard-ui-primary, #0f172a);
            -webkit-appearance: none;
            appearance: none;
        }

        body.guard-portal .guard-app__scroll .guard-chat__send {
            flex-shrink: 0;
            width: 40px;
            height: 36px;
            padding: 0;
            border: none;
            border-radius: 8px;
            background: #334155;
            color: #ffffff;
            cursor: pointer;
            -webkit-appearance: none;
            appearance: none;
        }

        body.guard-portal .guard-app__scroll .guard-chat__send:hover {
            background: #475569;
        }

        body.guard-portal .guard-app__scroll .guard-social-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 8px;
        }

        body.guard-portal .guard-app__scroll .guard-social-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            padding: 12px 10px;
            text-decoration: none;
            color: var(--guard-ui-primary, #0f172a);
            border-radius: 10px;
            background: var(--sa-input-bg, #f8fafc);
            border: 1px solid var(--guard-ui-border, #e2e8f0);
            box-shadow: none;
            transition: border-color 0.15s ease, transform 0.12s ease;
        }

        body.guard-portal .guard-app__scroll .guard-social-card:hover {
            transform: translateY(-1px);
            border-color: #cbd5e1;
            background: var(--guard-ui-cream, #f2efe4);
        }

        body.guard-portal .guard-app__scroll .guard-social-card i {
            font-size: 1.375rem;
            color: #1877f2;
        }

        body.guard-portal .guard-app__scroll .guard-accordion {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        body.guard-portal .guard-app__scroll .guard-accordion__item {
            border-radius: 8px;
            border: 1px solid var(--guard-ui-border, #e2e8f0);
            overflow: hidden;
            background: var(--sa-input-bg, #f8fafc);
        }

        body.guard-portal .guard-app__scroll .guard-accordion__trigger {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 10px 12px;
            font-family: inherit;
            font-size: 0.8125rem;
            font-weight: 600;
            text-align: left;
            color: var(--guard-ui-primary, #0f172a);
            background: transparent;
            border: none;
            cursor: pointer;
            -webkit-appearance: none;
            appearance: none;
        }

        body.guard-portal .guard-app__scroll .guard-accordion__trigger i {
            color: var(--guard-ui-subtle, #64748b);
            font-size: 0.75rem;
        }

        body.guard-portal .guard-app__scroll .guard-accordion__body {
            display: none;
            padding: 0 12px 10px;
            font-size: 0.75rem;
            line-height: 1.5;
            color: var(--guard-ui-secondary, #475569);
        }

        body.guard-portal .guard-app__scroll .guard-accordion__item.is-open .guard-accordion__body {
            display: block;
        }

        body.guard-portal .guard-app__scroll .guard-accordion__item.is-open .guard-accordion__chev {
            transform: rotate(180deg);
        }

        body.guard-portal .guard-app__scroll .guard-accordion__chev {
            transition: transform 0.2s ease;
        }

        body.guard-portal .guard-app__scroll code {
            font-size: 0.6875rem;
            padding: 2px 4px;
            border-radius: 4px;
            background: var(--sa-input-bg, #f8fafc);
            color: var(--guard-ui-primary, #0f172a);
        }

        /* Status badges */
        .guard-badge {
            display: inline-block;
            padding: 2px 8px;
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            border-radius: 6px;
        }

        .guard-badge--pending {
            color: #b45309;
            background: rgba(245, 158, 11, 0.15);
        }

        .guard-badge--approved {
            color: #15803d;
            background: rgba(22, 163, 74, 0.15);
        }

        .guard-badge--rejected {
            color: #b91c1c;
            background: rgba(239, 68, 68, 0.15);
        }

        body:not(.light-mode) .guard-badge--pending { color: #fbbf24; }
        body:not(.light-mode) .guard-badge--approved { color: #4ade80; }
        body:not(.light-mode) .guard-badge--rejected { color: #f87171; }

        /* Report list compact */
        .guard-report-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .guard-report-list__item {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            padding: 10px 12px;
            border-radius: 10px;
            background: var(--guard-surface-muted);
            border: 1px solid var(--guard-card-border);
            box-shadow: var(--guard-card-shadow);
            transition: box-shadow 0.2s ease, transform 0.2s ease;
        }

        .guard-report-list__date {
            font-size: 0.75rem;
            font-family: var(--font-mono, monospace);
            color: var(--sa-card-ink-muted);
        }

        .guard-report-list__meta {
            flex: 1;
            min-width: 0;
            font-size: 0.75rem;
            color: var(--sa-card-ink-soft);
        }

        /* Toast */
        .guard-toast {
            position: fixed;
            bottom: max(16px, env(safe-area-inset-bottom));
            left: 50%;
            transform: translateX(-50%) translateY(120%);
            z-index: 3000;
            max-width: min(360px, calc(100% - 32px));
            padding: 10px 14px;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #fff;
            background: #0f172a;
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
            opacity: 0;
            transition: transform 0.28s ease, opacity 0.28s ease;
            pointer-events: none;
        }

        body:not(.light-mode) .guard-toast {
            background: #1e293b;
            border: 1px solid var(--app-border-on-dark);
        }

        .guard-toast.is-visible {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }

        .guard-toast--success { background: #15803d; }
        .guard-toast--error { background: #b91c1c; }

        body.guard-portal .guard-app__scroll .guard-wizard__submit {
            width: 100%;
            margin-top: 10px;
        }

        body.guard-portal .guard-app__scroll .guard-wizard__submit.is-loading {
            pointer-events: none;
            opacity: 0.75;
        }

        body.guard-portal .guard-app__scroll .guard-wizard__submit .fa-spinner {
            margin-right: 6px;
        }

        body.guard-portal .visually-hidden {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        body.guard-portal .guard-card .btn-primary,
        body.guard-portal .guard-card .btn-ghost,
        body.guard-portal .guard-hub-panel .btn-primary {
            min-height: 36px;
        }

        @media (max-width: 600px) {
            body.guard-portal .guard-card,
            body.guard-portal .card-panel,
            body.guard-portal .sa-panel {
                padding: 12px 14px;
            }

            body.guard-portal .guard-card .btn-primary,
            body.guard-portal .guard-wizard__submit,
            body.guard-portal .guard-hub-panel .btn-primary {
                width: 100%;
            }

            body.guard-portal .sa-quick-actions {
                grid-template-columns: 1fr;
            }

            body.guard-portal .guard-app__scroll .guard-hub-tabs__btn {
                flex: 1 1 calc(50% - 4px);
                font-size: 0.6875rem;
            }

            body.guard-portal .guard-app__scroll .guard-scanner__actions .btn-primary,
            body.guard-portal .guard-app__scroll .guard-scanner__actions .btn-ghost,
            body.guard-portal .guard-app__scroll .guard-scanner__actions label.btn-ghost {
                flex: 1 1 calc(50% - 4px);
                justify-content: center;
            }

            body.guard-portal .guard-app__scroll .guard-evidence-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    <?php
}
