<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/superadmin_accountability.php';

auth_require_permission('superadmin.dashboard.view');

$roleCol = auth_users_role_column($conn);

$userCounts = [
    'total' => 0,
    'headguard' => 0,
    'admin' => 0,
    'superadmin' => 0,
    'active' => 0,
];
$countResult = $conn->query(
    "SELECT COUNT(*) AS total,
            SUM({$roleCol} = 0) AS headguard,
            SUM({$roleCol} = 1) AS admin,
            SUM({$roleCol} = 2) AS superadmin,
            SUM(is_active = 1) AS active
     FROM users"
);
if ($countResult) {
    $row = $countResult->fetch_assoc();
    $userCounts['total'] = (int) ($row['total'] ?? 0);
    $userCounts['headguard'] = (int) ($row['headguard'] ?? 0);
    $userCounts['admin'] = (int) ($row['admin'] ?? 0);
    $userCounts['superadmin'] = (int) ($row['superadmin'] ?? 0);
    $userCounts['active'] = (int) ($row['active'] ?? 0);
}

$loginsToday = 0;
$uniqueLoginsToday = 0;
$loginsResult = $conn->query(
    "SELECT COUNT(*) AS c FROM recording WHERE Event = 'LOGIN' AND DATE(Time_Of_Event) = CURDATE()"
);
if ($loginsResult) {
    $loginsToday = (int) $loginsResult->fetch_assoc()['c'];
}
$uniqueResult = $conn->query(
    "SELECT COUNT(DISTINCT Company_ID) AS c FROM recording
     WHERE Event = 'LOGIN' AND DATE(Time_Of_Event) = CURDATE() AND Company_ID IS NOT NULL"
);
if ($uniqueResult) {
    $uniqueLoginsToday = (int) $uniqueResult->fetch_assoc()['c'];
}

$recentAudit = [];
$auditSql = recording_supports_audit_detail($conn)
    ? 'SELECT Company_ID, Designation, Event, event_detail, Time_Of_Event FROM recording ORDER BY Time_Of_Event DESC LIMIT 10'
    : 'SELECT Company_ID, Designation, Event, Time_Of_Event FROM recording ORDER BY Time_Of_Event DESC LIMIT 10';
$auditResult = $conn->query($auditSql);
if ($auditResult) {
    while ($r = $auditResult->fetch_assoc()) {
        $recentAudit[] = $r;
    }
}

$superadminNavActive = 'dashboard';
$superadminMobileTitle = 'System Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?= mobile_meta_tags() ?>
    <title><?= e(app_agency_name()) ?> | System Dashboard</title>
    <script src="https://kit.fontawesome.com/3142eebea3.js" crossorigin="anonymous"></script>
    <?= app_fonts_link() ?>
    <style>
<?php admin_shell_styles(); ?>
<?php superadmin_page_styles(); ?>
    </style>
</head>
<body class="light-mode superadmin-portal">

<?php require __DIR__ . '/../includes/superadmin_sidebar.php'; ?>

    <main class="app-main" id="main-content">
        <div class="sa-dashboard">
            <header class="page-header sa-dashboard__hero">
                <h1 class="page-title">System Dashboard</h1>
                <p class="page-subtitle">Overview of portal accounts, sign-in activity, and recent audit events across the agency.</p>
            </header>

            <section class="sa-dashboard__kpis" aria-labelledby="sa-dashboard-kpi-heading">
                <h2 id="sa-dashboard-kpi-heading" class="sa-sr-only">Key metrics</h2>
                <div class="stat-grid sa-stat-grid">
                    <article class="stat-card">
                        <div class="stat-icon stat-icon--blue" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="3"></circle>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a3 3 0 0 1 0 5.74"></path>
                            </svg>
                        </div>
                        <div class="stat-body">
                            <h3 class="stat-label">Portal accounts</h3>
                            <p class="stat-value"><?= e((string) $userCounts['total']) ?></p>
                            <p class="stat-hint">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="12" cy="12" r="9"></circle>
                                    <path d="m8.5 12.5 2.2 2.2 4.8-5.2"></path>
                                </svg>
                                <?= e((string) $userCounts['active']) ?> active
                            </p>
                        </div>
                    </article>
                    <article class="stat-card">
                        <div class="stat-icon stat-icon--gold" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            </svg>
                        </div>
                        <div class="stat-body">
                            <h3 class="stat-label">Head guards</h3>
                            <p class="stat-value"><?= e((string) $userCounts['headguard']) ?></p>
                        </div>
                    </article>
                    <article class="stat-card">
                        <div class="stat-icon stat-icon--warn" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="7" r="3"></circle>
                                <path d="M5.5 21a6.5 6.5 0 0 1 13 0"></path>
                                <path d="M12 10v4"></path>
                                <path d="m10.5 14 1.5 2 1.5-2"></path>
                            </svg>
                        </div>
                        <div class="stat-body">
                            <h3 class="stat-label">Administrators</h3>
                            <p class="stat-value"><?= e((string) $userCounts['admin']) ?></p>
                        </div>
                    </article>
                    <article class="stat-card">
                        <div class="stat-icon stat-icon--green" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <path d="M16 17l5-5-5-5"></path>
                                <path d="M21 12H9"></path>
                            </svg>
                        </div>
                        <div class="stat-body">
                            <h3 class="stat-label">Logins today</h3>
                            <p class="stat-value"><?= e((string) $loginsToday) ?></p>
                            <p class="stat-hint">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="12" cy="7" r="3"></circle>
                                    <path d="M5.5 21a6.5 6.5 0 0 1 13 0"></path>
                                </svg>
                                <?= e((string) $uniqueLoginsToday) ?> unique users
                            </p>
                        </div>
                    </article>
                </div>
            </section>

            <section class="card-panel sa-panel sa-panel--toolbar" aria-labelledby="sa-dashboard-quick-heading">
                <div class="sa-panel__head">
                    <h2 id="sa-dashboard-quick-heading" class="panel-title sa-panel__title">Quick actions</h2>
                </div>
                <nav class="sa-quick-actions" aria-label="Shortcuts">
                    <a href="users.php?create=1" class="sa-quick-actions__link">
                        <span class="sa-quick-actions__icon" aria-hidden="true"><i class="fa-solid fa-user-plus"></i></span>
                        <span class="sa-quick-actions__label">Create account</span>
                        <i class="sa-quick-actions__chev fa-solid fa-chevron-right" aria-hidden="true"></i>
                    </a>
                    <a href="users.php" class="sa-quick-actions__link">
                        <span class="sa-quick-actions__icon" aria-hidden="true"><i class="fa-solid fa-users-gear"></i></span>
                        <span class="sa-quick-actions__label">Manage accounts</span>
                        <i class="sa-quick-actions__chev fa-solid fa-chevron-right" aria-hidden="true"></i>
                    </a>
                    <a href="audit-log.php" class="sa-quick-actions__link">
                        <span class="sa-quick-actions__icon" aria-hidden="true"><i class="fa-solid fa-clipboard-list"></i></span>
                        <span class="sa-quick-actions__label">Audit log</span>
                        <i class="sa-quick-actions__chev fa-solid fa-chevron-right" aria-hidden="true"></i>
                    </a>
                </nav>
            </section>

        <section class="card-panel sa-panel" aria-labelledby="sa-dashboard-activity-heading">
            <h2 id="sa-dashboard-activity-heading" class="panel-title">Recent portal activity</h2>
            <?php if ($recentAudit === []): ?>
                <p class="empty-state"><i class="fa-solid fa-inbox" aria-hidden="true"></i>No audit events recorded yet.</p>
            <?php else: ?>
                <div class="data-table-wrap sa-table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th><i class="fa-solid fa-clock th-icon" aria-hidden="true"></i>Time</th>
                                <th><i class="fa-solid fa-id-card th-icon" aria-hidden="true"></i>Employee ID</th>
                                <th><i class="fa-solid fa-bolt th-icon" aria-hidden="true"></i>Event</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentAudit as $entry): ?>
                                <?php
                                $ev = strtoupper((string) ($entry['Event'] ?? ''));
                                $badgeClass = match ($ev) {
                                    'LOGOUT' => 'badge--logout',
                                    'LOGIN' => 'badge--login',
                                    default => 'badge--admin',
                                };
                                ?>
                                <tr>
                                    <td class="mono"><?= e((string) $entry['Time_Of_Event']) ?></td>
                                    <td class="mono"><?= e((string) ($entry['Company_ID'] ?? '—')) ?></td>
                                    <td>
                                        <span class="event-cell">
                                            <i class="fa-solid <?= e(superadmin_event_icon($ev)) ?>" aria-hidden="true"></i>
                                            <span class="badge <?= e($badgeClass) ?>"><?= e(superadmin_event_label($ev)) ?></span>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="sa-dashboard__footer-cta">
                    <a href="audit-log.php" class="btn-ghost"><i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i> View complete audit log</a>
                </p>
            <?php endif; ?>
        </section>
        </div>
    </main>
</div>

<?php admin_shell_scripts(); ?>
</body>
</html>
