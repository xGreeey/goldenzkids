<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

auth_require_permission('admin.dashboard.view');

$company_id = (string) $_SESSION['company_id'];

// --- Operations metrics (live) ---

$guard_count_query = $conn->query('SELECT COUNT(*) AS total FROM guards');
$total_guards = $guard_count_query ? (int) $guard_count_query->fetch_assoc()['total'] : 0;

$reports_today_query = $conn->query('SELECT COUNT(*) AS total FROM dgd WHERE DATE(Time_of_Report) = CURDATE()');
$total_today = $reports_today_query ? (int) $reports_today_query->fetch_assoc()['total'] : 0;

$pending_query = $conn->query("SELECT COUNT(*) AS total FROM dgd WHERE Status = 'Pending'");
$total_pending = $pending_query ? (int) $pending_query->fetch_assoc()['total'] : 0;

$reports_week_query = $conn->query('SELECT COUNT(*) AS total FROM dgd WHERE YEARWEEK(Time_of_Report, 1) = YEARWEEK(CURDATE(), 1)');
$total_weekly = $reports_week_query ? (int) $reports_week_query->fetch_assoc()['total'] : 0;

$roster_query = $conn->query('SELECT Company_ID, First_Name, Last_Name, Post_Assigned FROM guards ORDER BY Last_Name ASC LIMIT 10');
$memo_guards_query = $conn->query('SELECT Company_ID, First_Name, Last_Name FROM guards ORDER BY Last_Name ASC');

$adminNavActive = 'dashboard';
$adminMobileTitle = 'Operations Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ABC Security Agency | Operations Dashboard</title>
    <script src="https://kit.fontawesome.com/3142eebea3.js" crossorigin="anonymous"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
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
            --brand-navy: #3d4a5c;
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
            top: 0;
            right: 0;
            bottom: 0;
            left: var(--sidebar-w);
            background:
                radial-gradient(ellipse 80% 50% at 100% -10%, rgba(143, 168, 184, 0.06), transparent 50%),
                radial-gradient(ellipse 60% 40% at 0% 100%, rgba(196, 184, 154, 0.04), transparent 45%);
            pointer-events: none;
            z-index: 0;
        }

        @media (max-width: 900px) {
            body::before { left: 0; }
        }

        body.light-mode::before {
            background:
                radial-gradient(ellipse 80% 50% at 100% -10%, rgba(125, 154, 171, 0.08), transparent 50%),
                radial-gradient(ellipse 60% 40% at 0% 100%, rgba(168, 155, 122, 0.06), transparent 45%);
        }

        /* Sidebar */
        .app-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1100;
            width: var(--sidebar-w);
            height: 100vh;
            display: flex;
            flex-direction: column;
            background: var(--bg-surface);
            border-right: 1px solid var(--border);
            transition: transform var(--transition), background var(--transition);
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 24px 20px 20px;
            border-bottom: 1px solid var(--border);
            cursor: default;
            user-select: none;
        }

        .brand-logo {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            object-fit: cover;
            border: 2px solid var(--brand-accent);
            flex-shrink: 0;
        }

        .brand-text {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
        }

        .brand-name {
            font-size: 0.875rem;
            font-weight: 700;
            letter-spacing: 0.01em;
            color: var(--text-primary);
            line-height: 1.25;
        }

        .brand-tagline {
            font-size: 0.625rem;
            font-weight: 500;
            color: var(--text-tertiary);
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: 8px 12px;
            flex: 1;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 999px;
            border: 1px solid transparent;
            transition: color var(--transition), background var(--transition), border-color var(--transition);
        }

        .sidebar-link i {
            width: 18px;
            text-align: center;
            font-size: 0.95rem;
        }

        .sidebar-link:hover {
            color: var(--text-primary);
            background: var(--bg-elevated);
        }

        .sidebar-link.active {
            color: var(--brand-accent);
            background: var(--bg-muted);
            border-color: var(--border);
        }

        .sidebar-link.active i {
            color: var(--brand-accent);
        }

        .sidebar-footer {
            padding: 16px 12px 12px;
            border-top: 1px solid var(--border);
            margin-top: auto;
        }

        .sidebar-appearance {
            padding: 0 12px 20px;
        }

        .btn-appearance {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            padding: 12px 14px;
            font-family: inherit;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-secondary);
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: color var(--transition), background var(--transition), border-color var(--transition);
        }

        .btn-appearance i {
            width: 18px;
            text-align: center;
            font-size: 0.95rem;
        }

        .btn-appearance:hover {
            color: var(--text-primary);
            border-color: var(--border-strong);
            background: var(--bg-muted);
        }

        .sidebar-link--signout {
            color: var(--danger);
            border: 1px solid var(--danger-soft);
            border-radius: var(--radius-sm);
        }

        .sidebar-link--signout:hover {
            background: var(--danger-soft);
            color: var(--danger);
        }

        .sidebar-link--signout i {
            color: var(--danger);
        }

        /* Content shell */
        .app-shell {
            position: relative;
            z-index: 1;
            flex: 1;
            min-width: 0;
            margin-left: var(--sidebar-w);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .mobile-topbar {
            display: none;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: var(--bg-surface);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .btn-menu {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            background: var(--bg-elevated);
            color: var(--text-secondary);
            cursor: pointer;
        }

        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1050;
            opacity: 0;
            pointer-events: none;
            transition: opacity var(--transition);
        }

        body.sidebar-open .sidebar-backdrop {
            opacity: 1;
            pointer-events: auto;
        }

        .app-main {
            flex: 1;
            max-width: 1440px;
            margin: 0 auto;
            padding: 32px 28px 48px;
            width: 100%;
        }

        .page-header {
            margin-bottom: 32px;
            animation: fadeUp 0.5s ease both;
        }

        .page-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--accent-blue);
            margin-bottom: 10px;
        }

        .page-eyebrow::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--success);
            box-shadow: 0 0 0 4px var(--success-soft);
            animation: pulse 2s ease infinite;
        }

        .page-title {
            font-size: clamp(1.75rem, 4vw, 2.25rem);
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .page-subtitle {
            font-size: 0.9375rem;
            color: var(--text-secondary);
            max-width: 560px;
        }

        /* KPI cards */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }

        .kpi-card {
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 20px 22px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            box-shadow: var(--shadow-sm);
            transition: transform var(--transition), box-shadow var(--transition), border-color var(--transition);
            animation: fadeUp 0.5s ease both;
        }

        .kpi-card:nth-child(1) { animation-delay: 0.05s; }
        .kpi-card:nth-child(2) { animation-delay: 0.1s; }
        .kpi-card:nth-child(3) { animation-delay: 0.15s; }

        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: var(--border-strong);
        }

        .kpi-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            flex-shrink: 0;
        }

        .kpi-icon--personnel { background: var(--success-soft); color: var(--success); }
        .kpi-icon--reports { background: var(--accent-blue-soft); color: var(--accent-blue); }
        .kpi-icon--pending { background: var(--danger-soft); color: var(--danger); }

        .kpi-body { min-width: 0; }

        .kpi-value {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.03em;
            line-height: 1.1;
            color: var(--text-primary);
        }

        .kpi-label {
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--text-secondary);
            margin-top: 4px;
        }

        .kpi-meta {
            font-family: var(--font-mono);
            font-size: 0.6875rem;
            color: var(--text-tertiary);
            margin-top: 8px;
        }

        /* Content grid */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            align-items: start;
        }

        @media (max-width: 1024px) {
            .content-grid { grid-template-columns: 1fr; }
        }

        .panel {
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            animation: fadeUp 0.55s ease both;
            animation-delay: 0.2s;
        }

        .panel + .panel { margin-top: 24px; }

        .panel-head {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .panel-title {
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .panel-title i {
            color: var(--accent-blue);
            font-size: 0.9rem;
        }

        .panel-badge {
            font-family: var(--font-mono);
            font-size: 0.6875rem;
            font-weight: 500;
            padding: 4px 10px;
            border-radius: 999px;
            background: var(--bg-muted);
            color: var(--text-tertiary);
            border: 1px solid var(--border);
        }

        .panel-body {
            padding: 24px;
        }

        .chart-wrap {
            position: relative;
            height: 280px;
            width: 100%;
        }

        /* Table */
        .table-wrap {
            overflow-x: auto;
            margin: -4px 0;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8125rem;
        }

        .data-table th {
            font-family: var(--font-mono);
            font-size: 0.6875rem;
            font-weight: 500;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--text-tertiary);
            text-align: left;
            padding: 12px 16px;
            background: var(--bg-elevated);
            border-bottom: 1px solid var(--border);
        }

        .data-table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
            color: var(--text-secondary);
        }

        .data-table tbody tr {
            transition: background var(--transition);
        }

        .data-table tbody tr:hover td {
            background: var(--bg-elevated);
            color: var(--text-primary);
        }

        .data-table td:first-child {
            font-family: var(--font-mono);
            font-size: 0.75rem;
            color: var(--text-tertiary);
        }

        .table-empty {
            text-align: center;
            padding: 32px 16px !important;
            color: var(--text-tertiary);
            font-style: italic;
        }

        /* Compose panel */
        .panel--compose .panel-head {
            background: linear-gradient(135deg, var(--brand-accent-soft), transparent 60%);
        }

        .panel--compose .panel-title i {
            color: var(--brand-accent);
        }

        .form-section-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--text-secondary);
            margin-bottom: 12px;
        }

        .required-mark {
            color: var(--danger);
            margin-left: 2px;
        }

        .delivery-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 8px;
        }

        @media (max-width: 520px) {
            .delivery-options { grid-template-columns: 1fr; }
        }

        .delivery-btn {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 6px;
            padding: 16px;
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all var(--transition);
            text-align: left;
            color: var(--text-primary);
            font-family: inherit;
        }

        .delivery-btn:hover {
            border-color: var(--accent-blue);
            background: var(--accent-blue-soft);
        }

        .delivery-btn.active {
            border-color: var(--brand-accent);
            background: var(--brand-accent-soft);
            box-shadow: 0 0 0 1px var(--brand-accent);
        }

        .delivery-btn i {
            font-size: 1.1rem;
            color: var(--accent-blue);
        }

        .delivery-btn.active i { color: var(--brand-accent); }

        .delivery-btn-title {
            font-size: 0.875rem;
            font-weight: 700;
        }

        .delivery-btn-desc {
            font-size: 0.75rem;
            color: var(--text-tertiary);
            line-height: 1.4;
        }

        .form-details {
            display: none;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid var(--border);
        }

        .form-details.is-visible { display: block; }

        .recipient-block {
            display: none;
            margin-bottom: 24px;
            padding: 18px;
            background: var(--danger-soft);
            border: 1px solid var(--danger-soft);
            border-radius: var(--radius-md);
            border-left: 4px solid var(--danger);
        }

        .recipient-block.is-visible { display: block; }

        .field {
            margin-bottom: 18px;
        }

        .field-label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }

        .field-label--alert { color: var(--danger); }

        .field-input,
        .field-select,
        .field-textarea {
            width: 100%;
            padding: 12px 14px;
            font-family: inherit;
            font-size: 0.875rem;
            color: var(--text-primary);
            background: var(--bg-elevated);
            border: 1px solid var(--border-strong);
            border-radius: var(--radius-sm);
            outline: none;
            transition: border-color var(--transition), box-shadow var(--transition);
        }

        .field-input:focus,
        .field-select:focus,
        .field-textarea:focus {
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px var(--accent-blue-soft);
        }

        .field-textarea {
            resize: vertical;
            min-height: 160px;
            line-height: 1.6;
        }

        .field-hint {
            font-size: 0.75rem;
            color: var(--text-tertiary);
            margin-top: 6px;
        }

        .btn-primary {
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 24px;
            margin-top: 8px;
            font-family: inherit;
            font-size: 0.875rem;
            font-weight: 700;
            color: #f9fafb;
            background: #8a9bab;
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: transform var(--transition), box-shadow var(--transition), background var(--transition);
            box-shadow: 0 2px 8px rgba(61, 74, 92, 0.12);
        }

        body.light-mode .btn-primary {
            color: #f9fafb;
            background: #7d8fa3;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            background: #7d8fa3;
            box-shadow: 0 4px 12px rgba(61, 74, 92, 0.16);
        }

        body.light-mode .btn-primary:hover {
            background: #6b7d92;
        }

        .btn-primary:active { transform: translateY(0); }

        .security-note {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-top: 16px;
            padding: 12px 14px;
            background: var(--bg-elevated);
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            color: var(--text-tertiary);
        }

        .security-note i {
            color: var(--success);
            margin-top: 2px;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        @media (max-width: 900px) {
            .app-sidebar {
                transform: translateX(-100%);
                box-shadow: var(--shadow-lg);
            }

            body.sidebar-open .app-sidebar {
                transform: translateX(0);
            }

            .sidebar-backdrop {
                display: block;
            }

            .app-shell {
                margin-left: 0;
            }

            .mobile-topbar {
                display: flex;
            }

            .brand-tagline { display: none; }
            .app-main { padding: 24px 16px 40px; }
        }
    </style>
</head>
<body class="light-mode">

    <div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>

    <aside class="app-sidebar" id="appSidebar" aria-label="Main navigation">
        <div class="sidebar-brand">
            <img src="https://i.imgur.com/uOClOiX.jpeg" alt="ABC Security" class="brand-logo" onerror="this.src='https://via.placeholder.com/42/0f2744/c9a227?text=ABC'">
            <div class="brand-text">
                <span class="brand-name">ABC Security Agency</span>
                <span class="brand-tagline">Enterprise Operations Portal</span>
            </div>
        </div>

        <nav class="sidebar-nav" aria-label="Workspace">
            <a href="dashboard.php" class="sidebar-link active" aria-current="page">
                <i class="fa-solid fa-chart-line" aria-hidden="true"></i>
                Dashboard
            </a>
            <a href="inbox.php" class="sidebar-link">
                <i class="fa-solid fa-inbox" aria-hidden="true"></i>
                Report Inbox
            </a>
        </nav>

        <div class="sidebar-footer">
            <form method="POST" action="../auth/logout-admin.php" class="sidebar-logout-form">
                <?= csrf_field() ?>
                <button type="submit" class="sidebar-link sidebar-link--signout">
                    <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                    Sign Out
                </button>
            </form>
        </div>
        <div class="sidebar-appearance">
            <button type="button" id="themeToggle" class="btn-appearance" title="Switch to dark mode" aria-label="Toggle light or dark appearance">
                <i class="fa-solid fa-moon" aria-hidden="true"></i>
                Appearance
            </button>
        </div>
    </aside>

    <div class="app-shell">
        <div class="mobile-topbar">
            <button type="button" class="btn-menu" id="sidebarToggle" aria-label="Open navigation menu" aria-expanded="false" aria-controls="appSidebar">
                <i class="fa-solid fa-bars" aria-hidden="true"></i>
            </button>
            <span style="font-size: 0.875rem; font-weight: 600; color: var(--text-secondary);">Operations Dashboard</span>
        </div>

        <main class="app-main">
        <header class="page-header">
            <p class="page-eyebrow">Real-time operations</p>
            <h1 class="page-title">Operations Dashboard</h1>
            <p class="page-subtitle">Monitor field personnel, daily guard reports, and pending review items. Compose secured internal communications from this workspace.</p>
        </header>

        <section class="kpi-grid" aria-label="Key performance indicators">
            <article class="kpi-card">
                <div class="kpi-icon kpi-icon--personnel" aria-hidden="true">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <div class="kpi-body">
                    <div class="kpi-value"><?= $total_guards ?></div>
                    <div class="kpi-label">Personnel on roster</div>
                    <div class="kpi-meta">Active security officers</div>
                </div>
            </article>
            <article class="kpi-card">
                <div class="kpi-icon kpi-icon--reports" aria-hidden="true">
                    <i class="fa-solid fa-file-lines"></i>
                </div>
                <div class="kpi-body">
                    <div class="kpi-value"><?= $total_today ?></div>
                    <div class="kpi-label">Daily guard reports</div>
                    <div class="kpi-meta">Submitted today</div>
                </div>
            </article>
            <article class="kpi-card">
                <div class="kpi-icon kpi-icon--pending" aria-hidden="true">
                    <i class="fa-solid fa-clock"></i>
                </div>
                <div class="kpi-body">
                    <div class="kpi-value"><?= $total_pending ?></div>
                    <div class="kpi-label">Awaiting review</div>
                    <div class="kpi-meta">Reports pending administrator action</div>
                </div>
            </article>
        </section>

        <div class="content-grid">
            <div class="analytics-col">
                <section class="panel" aria-labelledby="chart-heading">
                    <div class="panel-head">
                        <h2 id="chart-heading" class="panel-title">
                            <i class="fa-solid fa-chart-pie" aria-hidden="true"></i>
                            Report activity overview
                        </h2>
                        <span class="panel-badge">This week</span>
                    </div>
                    <div class="panel-body">
                        <div class="chart-wrap">
                            <canvas id="reportsChart" role="img" aria-label="Doughnut chart of report statuses"></canvas>
                        </div>
                    </div>
                </section>

                <section class="panel" aria-labelledby="roster-heading">
                    <div class="panel-head">
                        <h2 id="roster-heading" class="panel-title">
                            <i class="fa-solid fa-users" aria-hidden="true"></i>
                            Security personnel roster
                        </h2>
                        <span class="panel-badge">Latest 10</span>
                    </div>
                    <div class="panel-body" style="padding: 0;">
                        <div class="table-wrap">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th scope="col">Employee ID</th>
                                        <th scope="col">Last name</th>
                                        <th scope="col">First name</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($roster_query && $roster_query->num_rows > 0) {
                                        while ($guard = $roster_query->fetch_assoc()) {
                                            echo '<tr>'
                                                . '<td>' . htmlspecialchars((string) $guard['Company_ID']) . '</td>'
                                                . '<td>' . htmlspecialchars((string) $guard['Last_Name']) . '</td>'
                                                . '<td>' . htmlspecialchars((string) $guard['First_Name']) . '</td>'
                                                . '</tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="3" class="table-empty">No personnel records found.</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>

            <section class="panel panel--compose" aria-labelledby="compose-heading">
                <div class="panel-head">
                    <h2 id="compose-heading" class="panel-title">
                        <i class="fa-solid fa-envelope-open-text" aria-hidden="true"></i>
                        Internal communications
                    </h2>
                </div>
                <div class="panel-body">
                    <form action="send-memo.php" method="POST" id="memoForm" novalidate>
                        <?= csrf_field() ?>
                        <input type="hidden" name="distribution_type" id="distTypeValue" value="">

                        <span class="form-section-label">Delivery scope<span class="required-mark">*</span></span>
                        <div class="delivery-options" role="group" aria-label="Delivery scope">
                            <button type="button" class="delivery-btn" id="btnBroadcast" data-protocol="broadcast">
                                <i class="fa-solid fa-bullhorn" aria-hidden="true"></i>
                                <span class="delivery-btn-title">Company-wide</span>
                                <span class="delivery-btn-desc">Send to all personnel on roster</span>
                            </button>
                            <button type="button" class="delivery-btn" id="btnTargeted" data-protocol="targeted">
                                <i class="fa-solid fa-user-pen" aria-hidden="true"></i>
                                <span class="delivery-btn-title">Individual recipient</span>
                                <span class="delivery-btn-desc">Directed memo, including notice to explain</span>
                            </button>
                        </div>

                        <div id="memoDetailsContainer" class="form-details">
                            <div id="targetGuardContainer" class="recipient-block">
                                <div class="field">
                                    <label for="targetGuardInput" class="field-label field-label--alert">Select recipient<span class="required-mark">*</span></label>
                                    <select name="target_guard" id="targetGuardInput" class="field-select">
                                        <option value="" disabled selected>Choose an employee…</option>
                                        <?php
                                        if ($memo_guards_query && $memo_guards_query->num_rows > 0) {
                                            $memo_guards_query->data_seek(0);
                                            while ($row = $memo_guards_query->fetch_assoc()) {
                                                $label = htmlspecialchars((string) $row['Last_Name'])
                                                    . ', ' . htmlspecialchars((string) $row['First_Name'])
                                                    . ' (ID: ' . htmlspecialchars((string) $row['Company_ID']) . ')';
                                                echo '<option value="' . htmlspecialchars((string) $row['Company_ID']) . '">' . $label . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="field">
                                    <label for="deadlineDate" class="field-label field-label--alert">Response due date</label>
                                    <input type="date" name="deadline_date" id="deadlineDate" class="field-input">
                                    <p class="field-hint">Optional — required for compliance-related notices</p>
                                </div>
                            </div>

                            <div class="field">
                                <label for="memoTypeInput" class="field-label">Message category<span class="required-mark">*</span></label>
                                <select name="memo_type" id="memoTypeInput" class="field-select" required>
                                    <option value="" disabled selected>Select a category…</option>
                                    <option value="DIRECTIVE">Policy directive — rules and procedure updates</option>
                                    <option value="NOTICE">General notice — informational updates</option>
                                    <option value="NTE">Notice to explain — formal compliance request</option>
                                    <option value="BOLO">Security advisory — threat or watch notice</option>
                                </select>
                            </div>

                            <div class="field">
                                <label for="memoContentInput" class="field-label">Memo body<span class="required-mark">*</span></label>
                                <textarea name="content" id="memoContentInput" class="field-textarea" rows="8" placeholder="Enter the official memo text. Content is encrypted (AES-256) before storage."></textarea>
                            </div>

                            <button type="submit" name="generate_memo" class="btn-primary">
                                <i class="fa-solid fa-lock" aria-hidden="true"></i>
                                Publish secured memo
                            </button>

                            <p class="security-note">
                                <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                                <span>All memo content is encrypted at rest. Distribution is logged for audit compliance.</span>
                            </p>
                        </div>
                    </form>
                </div>
            </section>
        </div>
        </main>
    </div>

    <script>
        function setProtocol(type) {
            const distTypeInput = document.getElementById('distTypeValue');
            const detailsContainer = document.getElementById('memoDetailsContainer');
            const targetContainer = document.getElementById('targetGuardContainer');
            const targetInput = document.getElementById('targetGuardInput');
            const btnBroadcast = document.getElementById('btnBroadcast');
            const btnTargeted = document.getElementById('btnTargeted');

            distTypeInput.value = type;
            detailsContainer.classList.add('is-visible');

            if (type === 'broadcast') {
                btnBroadcast.classList.add('active');
                btnTargeted.classList.remove('active');
                targetContainer.classList.remove('is-visible');
                targetInput.value = '';
            } else if (type === 'targeted') {
                btnTargeted.classList.add('active');
                btnBroadcast.classList.remove('active');
                targetContainer.classList.add('is-visible');
            }
        }

        document.getElementById('btnBroadcast').addEventListener('click', () => setProtocol('broadcast'));
        document.getElementById('btnTargeted').addEventListener('click', () => setProtocol('targeted'));

        document.getElementById('memoForm').addEventListener('submit', function (event) {
            const errors = [];
            const distType = document.getElementById('distTypeValue').value;
            const memoType = document.getElementById('memoTypeInput').value;
            const content = document.getElementById('memoContentInput').value.trim();

            if (!distType) {
                errors.push('Delivery scope (company-wide or individual)');
            } else if (distType === 'targeted' && !document.getElementById('targetGuardInput').value) {
                errors.push('Recipient employee');
            }

            if (!memoType) errors.push('Message category');
            if (content === '') errors.push('Memo body');

            if (errors.length > 0) {
                event.preventDefault();
                alert('Please complete the required fields before publishing:\n\n• ' + errors.join('\n• '));
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            const body = document.body;
            const pendingCount = <?= $total_pending ?>;
            const todayCount = <?= $total_today ?>;
            const weeklyCount = <?= $total_weekly ?>;

            const chartColors = {
                dark: {
                    pending: '#c9a0a0',
                    today: '#8fa8b8',
                    weekly: '#c4b89a',
                    border: '#3e4758',
                    labels: '#b8c0cc'
                },
                light: {
                    pending: '#c48a8a',
                    today: '#7d9aab',
                    weekly: '#a89b7a',
                    border: '#f9fafb',
                    labels: '#6b7a8f'
                }
            };

            const ctx = document.getElementById('reportsChart').getContext('2d');
            let dssChart;

            function getPalette() {
                return body.classList.contains('light-mode') ? chartColors.light : chartColors.dark;
            }

            function initChart() {
                const p = getPalette();
                if (dssChart) dssChart.destroy();

                dssChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Awaiting review', 'Received today', 'Total this week'],
                        datasets: [{
                            data: [pendingCount, todayCount, weeklyCount],
                            backgroundColor: [p.pending, p.today, p.weekly],
                            borderColor: p.border,
                            borderWidth: 3,
                            hoverOffset: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    color: p.labels,
                                    font: { family: "'IBM Plex Mono', monospace", size: 11 },
                                    padding: 16,
                                    usePointStyle: true
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(15, 23, 42, 0.92)',
                                titleFont: { family: "'Plus Jakarta Sans', sans-serif" },
                                bodyFont: { family: "'IBM Plex Mono', monospace", size: 12 },
                                padding: 12,
                                cornerRadius: 8,
                                callbacks: {
                                    label: (ctx) => (ctx.label ? ctx.label + ': ' : '') + ctx.raw
                                }
                            }
                        },
                        cutout: '68%'
                    }
                });
            }

            initChart();

            document.getElementById('themeToggle')?.addEventListener('click', () => {
                setTimeout(initChart, 50);
            });
        });
    </script>
    <script>
<?php require __DIR__ . '/../includes/admin_shell.js.php'; ?>
    </script>

<?php require_once __DIR__ . '/../includes/global-alerts.php'; ?>
</body>
</html>
