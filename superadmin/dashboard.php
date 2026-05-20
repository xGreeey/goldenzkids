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
    ? 'SELECT Company_ID, Designation, Event, event_detail, Time_Of_Event FROM recording ORDER BY Time_Of_Event DESC LIMIT 8'
    : 'SELECT Company_ID, Designation, Event, Time_Of_Event FROM recording ORDER BY Time_Of_Event DESC LIMIT 8';
$auditResult = $conn->query($auditSql);
if ($auditResult) {
    while ($r = $auditResult->fetch_assoc()) {
        $recentAudit[] = $r;
    }
}

$accountChanges = superadmin_recent_account_changes($conn, 6);

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
<?php readfile(__DIR__ . '/../admin/assets/css/dashboard.css'); ?>
    </style>
</head>
<body class="light-mode">

<?php require __DIR__ . '/../includes/superadmin_sidebar.php'; ?>

    <main class="app-main">
        <header class="page-header">
            <h1 class="page-title">System Dashboard</h1>
            <p class="page-subtitle">Overview of portal accounts, sign-in activity, and recent audit events across the agency.</p>
        </header>

        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-icon stat-icon--blue"><i class="fa-solid fa-users" aria-hidden="true"></i></div>
                <div class="stat-body">
                    <div class="stat-label">Portal accounts</div>
                    <div class="stat-value"><?= e((string) $userCounts['total']) ?></div>
                    <p class="stat-hint"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> <?= e((string) $userCounts['active']) ?> active</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon--gold"><i class="fa-solid fa-shield" aria-hidden="true"></i></div>
                <div class="stat-body">
                    <div class="stat-label">Head guards</div>
                    <div class="stat-value"><?= e((string) $userCounts['headguard']) ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon--warn"><i class="fa-solid fa-user-tie" aria-hidden="true"></i></div>
                <div class="stat-body">
                    <div class="stat-label">Administrators</div>
                    <div class="stat-value"><?= e((string) $userCounts['admin']) ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon--green"><i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i></div>
                <div class="stat-body">
                    <div class="stat-label">Logins today</div>
                    <div class="stat-value"><?= e((string) $loginsToday) ?></div>
                    <p class="stat-hint"><?= e((string) $uniqueLoginsToday) ?> unique users</p>
                </div>
            </div>
        </div>

        <section class="card-panel">
            <h2 class="panel-title"><i class="fa-solid fa-bolt" aria-hidden="true"></i> Quick actions</h2>
            <div class="quick-links">
                <a href="create-user.php" class="quick-link">
                    <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                    Create new account
                </a>
                <a href="users.php" class="quick-link">
                    <i class="fa-solid fa-users-gear" aria-hidden="true"></i>
                    Manage user accounts
                </a>
                <a href="audit-log.php" class="quick-link">
                    <i class="fa-solid fa-clipboard-list" aria-hidden="true"></i>
                    Full audit log
                </a>
            </div>
        </section>

        <?php if ($accountChanges !== []): ?>
        <section class="card-panel">
            <h2 class="panel-title"><i class="fa-solid fa-file-signature" aria-hidden="true"></i> Recent account changes</h2>
            <div class="data-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><i class="fa-solid fa-clock th-icon" aria-hidden="true"></i>Time</th>
                            <th><i class="fa-solid fa-id-card th-icon" aria-hidden="true"></i>Account</th>
                            <th><i class="fa-solid fa-user-shield th-icon" aria-hidden="true"></i>Changed by</th>
                            <th><i class="fa-solid fa-bolt th-icon" aria-hidden="true"></i>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($accountChanges as $entry): ?>
                            <?php $ev = (string) ($entry['Event'] ?? ''); ?>
                            <tr>
                                <td class="mono"><?= e((string) ($entry['Time_Of_Event'] ?? '')) ?></td>
                                <td class="mono"><?= e((string) ($entry['Company_ID'] ?? '—')) ?></td>
                                <td class="mono"><?= e(superadmin_audit_actor_label($entry)) ?></td>
                                <td>
                                    <span class="event-cell">
                                        <i class="fa-solid <?= e(superadmin_event_icon($ev)) ?>" aria-hidden="true"></i>
                                        <?= e(superadmin_event_label($ev)) ?>
                                        <?php if (!empty($entry['event_detail'])): ?>
                                            <span class="stat-hint"> — <?= e((string) $entry['event_detail']) ?></span>
                                        <?php endif; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>

        <section class="card-panel">
            <h2 class="panel-title"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Recent portal activity</h2>
            <?php if ($recentAudit === []): ?>
                <p class="empty-state"><i class="fa-solid fa-inbox" aria-hidden="true"></i>No audit events recorded yet.</p>
            <?php else: ?>
                <div class="data-table-wrap">
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
                <p style="margin-top:12px;">
                    <a href="audit-log.php" class="btn-ghost"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i> View complete audit log</a>
                </p>
            <?php endif; ?>
        </section>
    </main>
</div>

<?php admin_shell_scripts(); ?>
</body>
</html>
