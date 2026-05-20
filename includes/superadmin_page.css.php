<?php
declare(strict_types=1);

function superadmin_page_styles(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;
    ?>
        .app-main {
            max-width: 1100px;
            padding:
                max(14px, env(safe-area-inset-top, 0px))
                max(16px, env(safe-area-inset-right, 0px))
                max(20px, env(safe-area-inset-bottom, 0px))
                max(16px, env(safe-area-inset-left, 0px)) !important;
            --sa-card-radius: 12px;
            --sa-card-padding: 14px 16px;
            --sa-gap-xs: 6px;
            --sa-gap-sm: 8px;
            --sa-gap-md: 12px;
            --sa-gap-lg: 16px;
            --sa-control-h: 34px;
            --sa-card-shadow: 0 1px 2px rgba(0, 0, 0, 0.04), 0 2px 8px rgba(0, 0, 0, 0.05);
            --sa-card-shadow-hover: 0 2px 6px rgba(0, 0, 0, 0.06), 0 6px 16px rgba(0, 0, 0, 0.08);
            --sa-card-border: var(--app-border);
            --sa-card-bg: var(--app-card-bg);
            --sa-card-ink: var(--app-ink);
            --sa-card-ink-muted: var(--app-ink-muted);
            --sa-card-ink-soft: var(--app-ink-soft);
            --sa-input-bg: var(--app-card-bg);
            --sa-input-border: var(--app-border);
        }

        body:not(.light-mode) .app-main {
            --sa-card-shadow: 0 1px 2px rgba(0, 0, 0, 0.18), 0 4px 12px rgba(0, 0, 0, 0.16);
            --sa-card-shadow-hover: 0 2px 6px rgba(0, 0, 0, 0.2), 0 8px 20px rgba(0, 0, 0, 0.22);
            --sa-card-border: var(--app-border-on-dark);
            --sa-card-ink: var(--app-ink-on-dark);
            --sa-card-ink-muted: var(--app-ink-muted-on-dark);
            --sa-card-ink-soft: var(--app-ink-soft-on-dark);
            --sa-input-bg: rgba(255, 255, 255, 0.06);
            --sa-input-border: var(--app-border-on-dark);
        }

        .app-main .page-header {
            gap: var(--sa-gap-xs);
            margin-bottom: var(--sa-gap-lg);
        }

        .app-main .page-title {
            font-size: clamp(1.375rem, 2vw + 0.5rem, 1.75rem);
            line-height: 1.2;
        }

        .app-main .page-subtitle {
            font-size: 0.8125rem;
            line-height: 1.45;
        }

        .sa-sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip-path: inset(50%);
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        /* System dashboard — compact enterprise layout */
        .sa-dashboard .page-header {
            margin-bottom: var(--sa-gap-md);
        }

        .sa-dashboard__hero .page-title {
            margin-top: 0;
        }

        .sa-dashboard__eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 0 0 4px;
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--sa-card-ink-soft);
        }

        .sa-dashboard__eyebrow i {
            font-size: 0.75rem;
            opacity: 0.9;
            color: var(--brand-accent);
        }

        .sa-dashboard__kpis {
            margin-bottom: var(--sa-gap-md);
        }

        .sa-stat-grid {
            grid-template-columns: repeat(auto-fit, minmax(148px, 1fr));
            gap: var(--sa-gap-sm);
            margin-bottom: 0;
        }

        .sa-dashboard .stat-card {
            border: none;
            box-shadow:
                0 0 0 1px var(--sa-card-border) inset,
                var(--sa-card-shadow);
            padding: 12px 14px;
            gap: 10px;
        }

        .sa-dashboard .stat-card:hover {
            transform: none;
            box-shadow:
                0 0 0 1px var(--app-border-strong) inset,
                var(--sa-card-shadow-hover);
        }

        .sa-dashboard .stat-icon {
            width: 34px;
            height: 34px;
            font-size: 0.875rem;
            border-radius: 9px;
        }

        .sa-dashboard .stat-icon svg {
            width: 18px;
            height: 18px;
            display: block;
        }

        .sa-dashboard .stat-label {
            font-size: 0.625rem;
            margin: 0;
        }

        .sa-dashboard .stat-value {
            margin: 0;
            font-size: clamp(1.125rem, 1.2vw + 0.45rem, 1.35rem);
        }

        .sa-dashboard .stat-hint {
            font-size: 0.75rem;
            margin: 2px 0 0;
        }

        .sa-dashboard .stat-hint svg {
            width: 12px;
            height: 12px;
            display: block;
            opacity: 0.9;
        }

        .sa-dashboard .card-panel {
            margin-bottom: var(--sa-gap-md);
            padding: 12px 14px;
            border: none;
            box-shadow:
                0 0 0 1px var(--sa-card-border) inset,
                var(--sa-card-shadow);
        }

        .sa-dashboard .card-panel:hover {
            transform: none;
            box-shadow:
                0 0 0 1px var(--app-border-strong) inset,
                var(--sa-card-shadow-hover);
        }

        .sa-dashboard .sa-panel .panel-title {
            margin-bottom: 10px;
        }

        .sa-panel--toolbar .sa-panel__head {
            margin: 0 0 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--sa-card-border);
        }

        .sa-panel--toolbar .sa-panel__title {
            margin-bottom: 0;
        }

        .sa-quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 1px;
            margin: 0;
            padding: 0;
            background: var(--sa-card-border);
            border-radius: 10px;
            overflow: hidden;
        }

        .sa-quick-actions__link {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 44px;
            padding: 8px 10px 8px 12px;
            text-decoration: none;
            color: var(--sa-card-ink);
            font-weight: 600;
            font-size: 0.8125rem;
            line-height: 1.25;
            background: var(--sa-card-bg);
            transition: background 0.15s ease, color 0.15s ease;
        }

        .sa-quick-actions__link:hover {
            background: var(--sa-input-bg);
            color: var(--sa-card-ink);
        }

        .sa-quick-actions__link:focus-visible {
            outline: 2px solid var(--app-accent);
            outline-offset: -2px;
            z-index: 1;
            position: relative;
        }

        .sa-quick-actions__icon {
            width: 32px;
            height: 32px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: transparent;
            color: var(--brand-accent);
            font-size: 0.8125rem;
        }

        .sa-quick-actions__label {
            flex: 1;
            min-width: 0;
        }

        .sa-quick-actions__chev {
            flex-shrink: 0;
            font-size: 0.65rem;
            opacity: 0.45;
        }

        .sa-quick-actions__link:hover .sa-quick-actions__chev {
            opacity: 0.75;
        }

        .sa-dashboard .sa-table-wrap {
            margin: 0;
            border-radius: 8px;
            border: 1px solid var(--sa-card-border);
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
        }

        .sa-dashboard .data-table {
            font-size: 0.8125rem;
        }

        .sa-dashboard .data-table th,
        .sa-dashboard .data-table td {
            padding: 7px 10px;
        }

        .sa-dashboard__footer-cta {
            margin: 10px 0 0;
        }

        @media (max-width: 640px) {
            .sa-stat-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .sa-quick-actions {
                grid-template-columns: 1fr;
            }

            .sa-dashboard .card-panel {
                padding: 10px 12px;
            }

            .sa-dashboard .stat-card {
                padding: 10px 12px;
            }
        }

        @media (max-width: 380px) {
            .sa-stat-grid {
                grid-template-columns: 1fr;
            }
        }

        .panel-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9375rem;
            font-weight: 700;
            margin: 0 0 var(--sa-gap-md);
            letter-spacing: -0.01em;
            color: var(--sa-card-ink);
        }

        .panel-title i {
            width: 30px;
            height: 30px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            background: var(--brand-accent-soft);
            color: var(--brand-accent);
            font-size: 0.8rem;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: var(--sa-gap-md);
            margin-bottom: var(--sa-gap-lg);
        }

        .stat-card {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            background: var(--sa-card-bg);
            border: 1px solid var(--sa-card-border);
            border-radius: var(--sa-card-radius);
            padding: var(--sa-card-padding);
            box-shadow: var(--sa-card-shadow);
            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease,
                border-color 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--sa-card-shadow-hover);
            border-color: var(--app-border-strong);
        }

        .stat-icon {
            width: 38px;
            height: 38px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            font-size: 0.95rem;
        }

        .stat-icon--blue { background: transparent; color: var(--sa-card-ink); }
        .stat-icon--gold { background: transparent; color: var(--sa-card-ink); }
        .stat-icon--green { background: transparent; color: var(--sa-card-ink); }
        .stat-icon--warn { background: transparent; color: var(--sa-card-ink); }
        .stat-icon--danger { background: transparent; color: var(--sa-card-ink); }
        .stat-icon--info { background: transparent; color: var(--sa-card-ink); }

        .stat-body { min-width: 0; flex: 1; display: flex; flex-direction: column; gap: 4px; }

        .stat-label {
            font-size: 0.6875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--sa-card-ink-soft);
            margin: 0;
        }

        .stat-value {
            font-size: clamp(1.25rem, 1.5vw + 0.5rem, 1.5rem);
            font-weight: 700;
            line-height: 1.15;
            color: var(--sa-card-ink);
            font-variant-numeric: tabular-nums;
            letter-spacing: -0.02em;
        }

        .stat-hint {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8125rem;
            color: var(--sa-card-ink-muted);
            margin: 4px 0 0;
        }

        .stat-hint i {
            font-size: 0.75rem;
            opacity: 0.85;
        }

        .card-panel {
            background: var(--sa-card-bg);
            border: 1px solid var(--sa-card-border);
            border-radius: var(--sa-card-radius);
            padding: var(--sa-card-padding);
            box-shadow: var(--sa-card-shadow);
            margin-bottom: var(--sa-gap-lg);
            transition: box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .card-panel:hover {
            box-shadow: var(--sa-card-shadow-hover);
            border-color: var(--app-border-strong);
        }

        .card-panel .data-table-wrap {
            margin: 0 -2px;
            border-radius: 8px;
            overflow: hidden;
        }

        .insight-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .insight-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 14px;
            background: var(--sa-card-bg);
            border: 1px solid var(--sa-card-border);
            border-radius: 10px;
            box-shadow: var(--sa-card-shadow);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .insight-item:hover {
            transform: translateY(-1px);
            box-shadow: var(--sa-card-shadow-hover);
        }

        .insight-icon {
            width: 32px;
            height: 32px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 0.875rem;
        }

        .insight-icon--danger { background: var(--danger-soft); color: var(--danger); }
        .insight-icon--warning { background: var(--warning-soft); color: var(--warning); }
        .insight-icon--info { background: var(--accent-blue-soft); color: var(--accent-blue); }
        .insight-icon--success { background: var(--success-soft); color: var(--success); }

        .insight-text strong {
            display: block;
            font-size: 0.8125rem;
            color: var(--sa-card-ink);
            margin-bottom: 2px;
        }

        .insight-text span {
            font-size: 0.75rem;
            color: var(--sa-card-ink-muted);
        }

        .accountability-panel--compact .accountability-lead { margin-bottom: 10px; }

        .accountability-lead {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: 14px;
            line-height: 1.55;
        }

        .accountability-rules {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .accountability-rules li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 0.8125rem;
            color: var(--text-secondary);
        }

        .accountability-rules li i {
            color: var(--success);
            margin-top: 3px;
            flex-shrink: 0;
        }

        .role-breakdown {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .role-row {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.875rem;
        }

        .role-row i {
            width: 28px;
            text-align: center;
            color: var(--brand-accent);
        }

        .role-row .bar-wrap {
            flex: 1;
            height: 8px;
            background: var(--bg-muted);
            border-radius: 999px;
            overflow: hidden;
        }

        .role-row .bar {
            height: 100%;
            background: var(--accent-blue);
            border-radius: 999px;
        }

        .role-row .count {
            font-family: var(--font-mono);
            font-size: 0.8125rem;
            color: var(--text-tertiary);
            min-width: 2rem;
            text-align: right;
        }

        .th-icon { margin-right: 6px; opacity: 0.75; font-size: 0.75rem; }

        .label-with-icon {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .label-with-icon i { color: var(--text-tertiary); font-size: 0.8rem; }

        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--sa-gap-sm);
        }

        .quick-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            background: var(--sa-card-bg);
            border: 1px solid var(--sa-card-border);
            border-radius: 10px;
            text-decoration: none;
            color: var(--sa-card-ink);
            font-weight: 600;
            font-size: 0.8125rem;
            line-height: 1.3;
            box-shadow: var(--sa-card-shadow);
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .quick-link:hover {
            border-color: var(--app-border-strong);
            transform: translateY(-2px);
            box-shadow: var(--sa-card-shadow-hover);
            color: var(--sa-card-ink);
        }

        .quick-link:focus-visible {
            outline: 2px solid var(--app-accent);
            outline-offset: 2px;
        }

        .quick-link i {
            width: 34px;
            height: 34px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            background: var(--brand-accent-soft);
            color: var(--brand-accent);
            font-size: 0.875rem;
        }

        .data-table-wrap { overflow-x: auto; }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }

        .data-table th,
        .data-table td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid var(--sa-card-border);
        }

        .data-table th {
            font-size: 0.6875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--sa-card-ink-soft);
            background: var(--sa-input-bg);
            white-space: nowrap;
        }

        .event-cell {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .event-cell i { font-size: 0.85rem; opacity: 0.85; }

        .data-table tbody tr:hover { background: var(--sa-input-bg); }

        .data-table td.mono { font-family: var(--font-mono); font-size: 0.8125rem; }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 0.625rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            line-height: 1.2;
        }

        .badge.role-badge {
            background: transparent;
            color: var(--sa-card-ink);
            border: 1px solid var(--sa-card-border);
            box-shadow: none;
        }

        .badge.status-badge {
            background: transparent;
            border: 1px solid transparent;
            box-shadow: none;
        }

        .badge.status-badge--active {
            color: #15803d;
            border-color: #22c55e;
            background: #dcfce7;
        }

        .badge.status-badge--inactive {
            color: #b91c1c;
            border-color: #ef4444;
            background: #fee2e2;
        }

        body:not(.light-mode) .badge.status-badge--active {
            color: #86efac;
            border-color: #22c55e;
            background: rgba(34, 197, 94, 0.18);
        }

        body:not(.light-mode) .badge.status-badge--inactive {
            color: #fca5a5;
            border-color: #ef4444;
            background: rgba(239, 68, 68, 0.18);
        }

        .badge--guard { background: var(--accent-blue-soft); color: var(--accent-blue); }
        .badge--admin { background: var(--warning-soft); color: var(--warning); }
        .badge--super { background: var(--brand-accent-soft); color: var(--brand-accent); }
        .badge--active { background: var(--success-soft); color: var(--success); }
        .badge--inactive { background: var(--danger-soft); color: var(--danger); }
        .badge--login { background: var(--success-soft); color: var(--success); }
        .badge--logout { background: var(--danger-soft); color: var(--danger); }

        .form-grid {
            display: grid;
            gap: var(--sa-gap-md);
            max-width: 480px;
        }

        .form-actions {
            display: flex;
            flex-wrap: wrap;
            gap: var(--sa-gap-sm);
            align-items: center;
        }

        .form-field label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--sa-card-ink-muted);
            margin-bottom: 4px;
        }

        .form-field input:not([type="checkbox"]):not([type="radio"]),
        .form-field select {
            width: 100%;
            min-height: var(--sa-control-h);
            padding: 7px 11px;
            font-family: inherit;
            font-size: 0.875rem;
            color: var(--sa-card-ink);
            background: var(--sa-input-bg);
            border: 1px solid var(--sa-input-border);
            border-radius: 8px;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .form-field--checkbox {
            margin-top: 4px;
        }

        .form-field--checkbox .checkbox-row {
            display: inline-flex;
            flex-direction: row;
            align-items: center;
            justify-content: flex-start;
            gap: 6px;
            width: fit-content;
            max-width: 100%;
            min-height: 0;
            padding: 0;
            margin: 0;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--sa-card-ink-muted);
            background: transparent;
            border: none;
            border-radius: 0;
            box-shadow: none;
            cursor: pointer;
            user-select: none;
        }

        .form-field--checkbox .checkbox-row__label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex: 0 1 auto;
            min-width: 0;
        }

        .form-field--checkbox .checkbox-row__label i {
            color: var(--brand-accent);
            font-size: 0.875rem;
            flex-shrink: 0;
        }

        .form-field--checkbox .checkbox-row input[type="checkbox"] {
            width: 18px;
            height: 18px;
            min-height: 18px;
            margin: 0;
            flex-shrink: 0;
            padding: 0;
            accent-color: var(--app-accent);
            cursor: pointer;
            border-radius: 4px;
            box-shadow:
                0 1px 2px rgba(0, 0, 0, 0.08),
                0 0 0 1px rgba(0, 0, 0, 0.06);
        }

        body:not(.light-mode) .form-field--checkbox .checkbox-row input[type="checkbox"] {
            box-shadow:
                0 1px 3px rgba(0, 0, 0, 0.45),
                0 0 0 1px rgba(255, 255, 255, 0.1);
        }

        .form-field--checkbox .checkbox-row input[type="checkbox"]:focus-visible {
            outline: none;
            box-shadow:
                0 1px 2px rgba(0, 0, 0, 0.1),
                0 0 0 2px var(--brand-accent-soft);
        }

        body:not(.light-mode) .form-field--checkbox .checkbox-row input[type="checkbox"]:focus-visible {
            box-shadow:
                0 1px 3px rgba(0, 0, 0, 0.5),
                0 0 0 2px rgba(255, 255, 255, 0.18);
        }

        .form-field--checkbox .checkbox-row input[type="checkbox"]:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .form-field input:not([type="checkbox"]):not([type="radio"]):focus,
        .form-field select:focus {
            outline: none;
            border-color: var(--app-accent);
            box-shadow: 0 0 0 2px var(--brand-accent-soft);
        }

        .form-hint {
            font-size: 0.6875rem;
            color: var(--sa-card-ink-soft);
            margin-top: 3px;
        }

        body.superadmin-portal .app-main .btn-primary,
        body.superadmin-portal .app-main button.btn-primary,
        body.superadmin-portal .app-main a.btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: auto;
            min-height: var(--sa-control-h);
            padding: 7px 14px;
            margin-top: 0;
            font-family: inherit;
            font-size: 0.8125rem;
            font-weight: 600;
            line-height: 1.2;
            letter-spacing: normal;
            color: #fff;
            background-color: #5c6b7d;
            background-image: none;
            border: 1px solid transparent;
            border-radius: 8px;
            box-shadow: none;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.15s ease, transform 0.15s ease, border-color 0.15s ease;
        }

        body.superadmin-portal.light-mode .app-main .btn-primary,
        body.superadmin-portal.light-mode .app-main button.btn-primary,
        body.superadmin-portal.light-mode .app-main a.btn-primary {
            background-color: #5c6b7d;
            color: #fff;
        }

        body.superadmin-portal:not(.light-mode) .app-main .btn-primary,
        body.superadmin-portal:not(.light-mode) .app-main button.btn-primary,
        body.superadmin-portal:not(.light-mode) .app-main a.btn-primary {
            background-color: rgba(255, 255, 255, 0.12);
            border-color: var(--app-border-on-dark);
            color: var(--app-ink-on-dark);
        }

        body.superadmin-portal .app-main .btn-primary:hover,
        body.superadmin-portal .app-main button.btn-primary:hover,
        body.superadmin-portal .app-main a.btn-primary:hover {
            transform: translateY(-1px);
            filter: none;
        }

        body.superadmin-portal.light-mode .app-main .btn-primary:hover,
        body.superadmin-portal.light-mode .app-main button.btn-primary:hover,
        body.superadmin-portal.light-mode .app-main a.btn-primary:hover {
            background-color: #4a5868;
            background-image: none;
        }

        body.superadmin-portal:not(.light-mode) .app-main .btn-primary:hover,
        body.superadmin-portal:not(.light-mode) .app-main button.btn-primary:hover,
        body.superadmin-portal:not(.light-mode) .app-main a.btn-primary:hover {
            background-color: rgba(255, 255, 255, 0.18);
            background-image: none;
        }

        body.superadmin-portal .app-main .btn-ghost,
        body.superadmin-portal .app-main button.btn-ghost,
        body.superadmin-portal .app-main a.btn-ghost {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            min-height: 30px;
            padding: 5px 10px;
            font-family: inherit;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--sa-card-ink-muted);
            background: transparent;
            border: 1px solid var(--sa-card-border);
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: border-color 0.15s ease, color 0.15s ease, background 0.15s ease;
        }

        body.superadmin-portal .app-main .btn-ghost:hover,
        body.superadmin-portal .app-main button.btn-ghost:hover,
        body.superadmin-portal .app-main a.btn-ghost:hover {
            border-color: var(--app-border-strong);
            color: var(--sa-card-ink);
            background: var(--sa-input-bg);
        }

        .alert {
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 0.8125rem;
            margin-bottom: var(--sa-gap-md);
        }

        .alert--success { background: var(--success-soft); color: var(--success); border: 1px solid var(--success); }
        .alert--error { background: var(--danger-soft); color: var(--danger); border: 1px solid var(--danger); }
        .alert i { margin-right: 8px; }

        .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: var(--sa-gap-sm);
            align-items: flex-end;
            justify-content: space-between;
            margin-bottom: var(--sa-gap-md);
            padding: 0;
            background: transparent;
            border: none;
            border-radius: 0;
            box-shadow: none;
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: var(--sa-gap-sm);
            align-items: flex-end;
        }

        .filter-form .form-field { margin: 0; }
        .filter-form .form-field input,
        .filter-form .form-field select { min-width: 128px; }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: var(--sa-gap-sm);
            text-align: center;
            padding: 28px 16px;
            color: var(--sa-card-ink-muted);
            font-size: 0.8125rem;
            background: var(--sa-input-bg);
            border: 1px dashed var(--sa-card-border);
            border-radius: 10px;
        }

        .empty-state i {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            border-radius: 10px;
            background: var(--brand-accent-soft);
            color: var(--brand-accent);
            opacity: 1;
            margin: 0;
        }

        @media (prefers-reduced-motion: reduce) {
            .stat-card,
            .stat-card .stat-icon,
            .quick-link,
            .quick-link i,
            .insight-item,
            .sa-dashboard .stat-card {
                transition: none;
            }

            .stat-card:hover,
            .quick-link:hover,
            .insight-item:hover,
            .sa-dashboard .stat-card:hover {
                transform: none;
            }
        }

        .pagination {
            display: flex;
            gap: var(--sa-gap-xs);
            margin-top: var(--sa-gap-md);
            flex-wrap: wrap;
        }

        .pagination a,
        .pagination span {
            min-height: 30px;
            padding: 5px 10px;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            border: 1px solid var(--sa-card-border);
            color: var(--sa-card-ink-muted);
            display: inline-flex;
            align-items: center;
        }

        .pagination a:hover {
            background: var(--sa-input-bg);
            color: var(--sa-card-ink);
        }

        .pagination .current {
            background: var(--brand-accent-soft);
            color: var(--brand-accent);
            border-color: var(--app-border-strong);
        }

        .label-with-icon i { color: var(--sa-card-ink-soft); }

        .accountability-lead {
            color: var(--sa-card-ink-muted);
        }

        .accountability-rules li {
            color: var(--sa-card-ink-muted);
        }
    <?php
}
