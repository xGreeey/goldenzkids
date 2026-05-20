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
        .app-main { max-width: 1100px; }

        .panel-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 16px;
            color: var(--text-primary);
        }

        .panel-title i {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-sm);
            background: var(--brand-accent-soft);
            color: var(--brand-accent);
            font-size: 0.9rem;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }

        .stat-card {
            display: flex;
            gap: 14px;
            align-items: flex-start;
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 20px;
            box-shadow: var(--shadow-sm);
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-sm);
            font-size: 1.1rem;
        }

        .stat-icon--blue { background: var(--accent-blue-soft); color: var(--accent-blue); }
        .stat-icon--gold { background: var(--brand-accent-soft); color: var(--brand-accent); }
        .stat-icon--green { background: var(--success-soft); color: var(--success); }
        .stat-icon--warn { background: var(--warning-soft); color: var(--warning); }
        .stat-icon--danger { background: var(--danger-soft); color: var(--danger); }
        .stat-icon--info { background: var(--accent-blue-soft); color: var(--accent-blue); }

        .stat-body { min-width: 0; flex: 1; }

        .stat-label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-tertiary);
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-primary);
            font-variant-numeric: tabular-nums;
        }

        .stat-hint {
            font-size: 0.8125rem;
            color: var(--text-secondary);
            margin-top: 6px;
        }

        .card-panel {
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 24px;
        }

        .insight-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .insight-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 16px;
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
        }

        .insight-icon {
            width: 36px;
            height: 36px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-sm);
            font-size: 0.95rem;
        }

        .insight-icon--danger { background: var(--danger-soft); color: var(--danger); }
        .insight-icon--warning { background: var(--warning-soft); color: var(--warning); }
        .insight-icon--info { background: var(--accent-blue-soft); color: var(--accent-blue); }
        .insight-icon--success { background: var(--success-soft); color: var(--success); }

        .insight-text strong {
            display: block;
            font-size: 0.875rem;
            color: var(--text-primary);
            margin-bottom: 2px;
        }

        .insight-text span {
            font-size: 0.8125rem;
            color: var(--text-secondary);
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

        .empty-state i {
            display: block;
            font-size: 2rem;
            margin-bottom: 12px;
            color: var(--text-tertiary);
            opacity: 0.6;
        }

        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
        }

        .quick-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            text-decoration: none;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.875rem;
            transition: border-color var(--transition), transform var(--transition);
        }

        .quick-link:hover {
            border-color: var(--border-strong);
            transform: translateY(-1px);
        }

        .quick-link i {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-sm);
            background: var(--brand-accent-soft);
            color: var(--brand-accent);
        }

        .data-table-wrap { overflow-x: auto; }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }

        .data-table th,
        .data-table td {
            padding: 12px 14px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .data-table th {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-tertiary);
            background: var(--bg-elevated);
            white-space: nowrap;
        }

        .event-cell {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .event-cell i { font-size: 0.85rem; opacity: 0.85; }

        .data-table tbody tr:hover { background: var(--bg-elevated); }

        .data-table td.mono { font-family: var(--font-mono); font-size: 0.8125rem; }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
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
            gap: 18px;
            max-width: 520px;
        }

        .form-field label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 6px;
        }

        .form-field input,
        .form-field select {
            width: 100%;
            padding: 12px 14px;
            font-family: inherit;
            font-size: 1rem;
            color: var(--text-primary);
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
        }

        .form-field input:focus,
        .form-field select:focus {
            outline: none;
            border-color: var(--accent-blue);
        }

        .form-hint {
            font-size: 0.75rem;
            color: var(--text-tertiary);
            margin-top: 4px;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 20px;
            font-family: inherit;
            font-size: 0.875rem;
            font-weight: 600;
            color: #f9fafb;
            background: #5c6b7d;
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            text-decoration: none;
            transition: transform var(--transition), background var(--transition);
        }

        .btn-primary:hover { background: #4a5868; transform: translateY(-1px); }

        .btn-ghost {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            font-family: inherit;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text-secondary);
            background: transparent;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            cursor: pointer;
            text-decoration: none;
        }

        .btn-ghost:hover { border-color: var(--border-strong); color: var(--text-primary); }

        .alert {
            padding: 14px 16px;
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            margin-bottom: 20px;
        }

        .alert--success { background: var(--success-soft); color: var(--success); border: 1px solid var(--success); }
        .alert--error { background: var(--danger-soft); color: var(--danger); border: 1px solid var(--danger); }
        .alert i { margin-right: 8px; }

        .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: flex-end;
        }

        .filter-form .form-field { margin: 0; }
        .filter-form .form-field input,
        .filter-form .form-field select { min-width: 140px; }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-tertiary);
            font-size: 0.9375rem;
        }

        .pagination {
            display: flex;
            gap: 8px;
            margin-top: 16px;
            flex-wrap: wrap;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            font-size: 0.8125rem;
            font-weight: 600;
            border-radius: var(--radius-sm);
            text-decoration: none;
            border: 1px solid var(--border);
            color: var(--text-secondary);
        }

        .pagination a:hover { background: var(--bg-elevated); }
        .pagination .current { background: var(--bg-muted); color: var(--brand-accent); border-color: var(--border-strong); }
    <?php
}
