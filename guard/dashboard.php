<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/guard_layout.php';

auth_require_permission('guard.dashboard.view');

$companyId = (string) $_SESSION['company_id'];

$postAssigned = '—';
$profile = db_query(
    $conn,
    'SELECT Post_Assigned, First_Name, Last_Name FROM guards WHERE Company_ID = ? LIMIT 1',
    's',
    [$companyId]
);
$row = $profile ? $profile->fetch(PDO::FETCH_ASSOC) : false;
if ($row) {
    $post = trim((string) ($row['Post_Assigned'] ?? ''));
    $postAssigned = $post !== '' ? $post : '—';
}

$unreadMemos = 0;
$unreadQuery = db_query(
    $conn,
    'SELECT COUNT(*) AS total FROM memo_recipients WHERE Company_ID = ? AND is_read = 0',
    's',
    [$companyId]
);
if ($unreadQuery) {
    $unreadMemos = (int) ($unreadQuery->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
}

$reportsToday = 0;
$todayQuery = db_query(
    $conn,
    'SELECT COUNT(*) AS total FROM dgd WHERE Company_ID = ? AND DATE(Time_of_Report) = CURDATE()',
    's',
    [$companyId]
);
if ($todayQuery) {
    $reportsToday = (int) ($todayQuery->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
}

$pendingReports = 0;
$pendingQuery = db_query(
    $conn,
    "SELECT COUNT(*) AS total FROM dgd WHERE Company_ID = ? AND Status = 'Pending'",
    's',
    [$companyId]
);
if ($pendingQuery) {
    $pendingReports = (int) ($pendingQuery->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
}

$totalReports = 0;
$totalQuery = db_query(
    $conn,
    'SELECT COUNT(*) AS total FROM dgd WHERE Company_ID = ?',
    's',
    [$companyId]
);
if ($totalQuery) {
    $totalReports = (int) ($totalQuery->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
}

$recentMemos = [];
$memoSql = 'SELECT m.Memo_ID, m.Category, m.Body_Text, m.created_at, mr.is_read
            FROM memo_recipients mr
            INNER JOIN memos m ON m.Memo_ID = mr.Memo_ID
            WHERE mr.Company_ID = ?
            ORDER BY m.created_at DESC
            LIMIT 5';
$memoResult = db_query($conn, $memoSql, 's', [$companyId]);
if ($memoResult) {
    while ($r = $memoResult->fetch(PDO::FETCH_ASSOC)) {
        $recentMemos[] = $r;
    }
}

$guardNavActive = 'dashboard';
guard_layout_head('Head Guard Dashboard');
?>
        <div class="sa-dashboard">
            <header class="page-header sa-dashboard__hero">
                <h1 class="page-title">Head Guard Dashboard</h1>
                <p class="page-subtitle">Your post assignment, secured memos, and daily guard report activity at a glance.</p>
            </header>

            <section class="sa-dashboard__kpis" aria-labelledby="guard-dashboard-kpi-heading">
                <h2 id="guard-dashboard-kpi-heading" class="sa-sr-only">Key metrics</h2>
                <div class="stat-grid sa-stat-grid">
                    <article class="stat-card">
                        <div class="stat-icon stat-icon--blue" aria-hidden="true">
                            <i class="fa-solid fa-location-dot"></i>
                        </div>
                        <div class="stat-body">
                            <h3 class="stat-label">Post assigned</h3>
                            <p class="stat-value stat-value--text"><?= e($postAssigned) ?></p>
                        </div>
                    </article>
                    <article class="stat-card">
                        <div class="stat-icon stat-icon--gold" aria-hidden="true">
                            <i class="fa-solid fa-envelope"></i>
                        </div>
                        <div class="stat-body">
                            <h3 class="stat-label">Unread memos</h3>
                            <p class="stat-value"><?= e((string) $unreadMemos) ?></p>
                        </div>
                    </article>
                    <article class="stat-card">
                        <div class="stat-icon stat-icon--green" aria-hidden="true">
                            <i class="fa-solid fa-file-circle-check"></i>
                        </div>
                        <div class="stat-body">
                            <h3 class="stat-label">Reports today</h3>
                            <p class="stat-value"><?= e((string) $reportsToday) ?></p>
                        </div>
                    </article>
                    <article class="stat-card">
                        <div class="stat-icon stat-icon--warn" aria-hidden="true">
                            <i class="fa-solid fa-clock"></i>
                        </div>
                        <div class="stat-body">
                            <h3 class="stat-label">Awaiting review</h3>
                            <p class="stat-value"><?= e((string) $pendingReports) ?></p>
                            <p class="stat-hint">
                                <i class="fa-solid fa-folder-open" aria-hidden="true"></i>
                                <?= e((string) $totalReports) ?> total submitted
                            </p>
                        </div>
                    </article>
                </div>
            </section>

            <section class="card-panel sa-panel sa-panel--toolbar" aria-labelledby="guard-dashboard-quick-heading">
                <div class="sa-panel__head">
                    <h2 id="guard-dashboard-quick-heading" class="panel-title sa-panel__title">Quick actions</h2>
                </div>
                <nav class="sa-quick-actions" aria-label="Shortcuts">
                    <a href="inbox.php" class="sa-quick-actions__link">
                        <span class="sa-quick-actions__icon" aria-hidden="true"><i class="fa-solid fa-inbox"></i></span>
                        <span class="sa-quick-actions__label">Open inbox</span>
                        <i class="sa-quick-actions__chev fa-solid fa-chevron-right" aria-hidden="true"></i>
                    </a>
                    <a href="reports.php" class="sa-quick-actions__link">
                        <span class="sa-quick-actions__icon" aria-hidden="true"><i class="fa-solid fa-file-lines"></i></span>
                        <span class="sa-quick-actions__label">View my reports</span>
                        <i class="sa-quick-actions__chev fa-solid fa-chevron-right" aria-hidden="true"></i>
                    </a>
                </nav>
            </section>

            <section class="card-panel sa-panel" aria-labelledby="guard-dashboard-memos-heading">
                <h2 id="guard-dashboard-memos-heading" class="panel-title">Recent memos</h2>
                <?php if ($recentMemos === []): ?>
                    <p class="empty-state"><i class="fa-solid fa-inbox" aria-hidden="true"></i>No memos in your inbox yet.</p>
                <?php else: ?>
                    <div class="data-table-wrap sa-table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Received</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentMemos as $memo): ?>
                                    <tr>
                                        <td><?= e((string) ($memo['Category'] ?? '—')) ?></td>
                                        <td class="mono"><?= e((string) ($memo['created_at'] ?? '—')) ?></td>
                                        <td>
                                            <?php if ((int) ($memo['is_read'] ?? 0) === 1): ?>
                                                <span class="badge badge--admin">Read</span>
                                            <?php else: ?>
                                                <span class="badge badge--guard">New</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="sa-dashboard__footer-cta">
                        <a href="inbox.php" class="btn-ghost"><i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i> View all memos</a>
                    </p>
                <?php endif; ?>
            </section>
        </div>
        <style>
            .stat-value--text {
                font-size: clamp(1rem, 2vw, 1.25rem);
                line-height: 1.3;
                word-break: break-word;
            }
        </style>
<?php
guard_layout_end();
