<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/guard_layout.php';
require_once __DIR__ . '/../includes/guard_ui_icons.php';

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

<<<<<<< HEAD
=======
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

>>>>>>> 493ddc0826316fd078ab98e571f6a6efec50cf08
$guardNavActive = 'dashboard';
guard_layout_head('Dashboard');
?>
        <div class="guard-dashboard">
            <section class="guard-ui-block" aria-labelledby="guard-kpi-heading">
                <h2 id="guard-kpi-heading" class="guard-ui-block__heading">
                    <?= guard_ui_icon_badge('grid', 16) ?>
                    <span>Key metrics</span>
                </h2>
                <div class="guard-ui-metrics">
                    <article class="guard-ui-metric-card">
                        <div class="guard-ui-metric-card__head">
                            <span class="guard-ui-metric-card__label">Post assigned</span>
                            <span class="guard-ui-metric-card__icon"><?= guard_ui_icon('map-pin', 18) ?></span>
                        </div>
                        <p class="guard-ui-metric-card__value guard-ui-metric-card__value--text"><?= e($postAssigned) ?></p>
                    </article>
                    <article class="guard-ui-metric-card">
                        <div class="guard-ui-metric-card__head">
                            <span class="guard-ui-metric-card__label">Unread memos</span>
                            <span class="guard-ui-metric-card__icon"><?= guard_ui_icon('bell', 18) ?></span>
                        </div>
                        <p class="guard-ui-metric-card__value"><?= e((string) $unreadMemos) ?></p>
                    </article>
                    <article class="guard-ui-metric-card">
                        <div class="guard-ui-metric-card__head">
                            <span class="guard-ui-metric-card__label">Reports today</span>
                            <span class="guard-ui-metric-card__icon"><?= guard_ui_icon('clipboard', 18) ?></span>
                        </div>
                        <p class="guard-ui-metric-card__value"><?= e((string) $reportsToday) ?></p>
                    </article>
                    <article class="guard-ui-metric-card">
                        <div class="guard-ui-metric-card__head">
                            <span class="guard-ui-metric-card__label">Awaiting review</span>
                            <span class="guard-ui-metric-card__icon"><?= guard_ui_icon('clock', 18) ?></span>
                        </div>
                        <p class="guard-ui-metric-card__value"><?= e((string) $pendingReports) ?></p>
                        <p class="guard-ui-metric-card__hint"><?= e((string) $totalReports) ?> total submitted</p>
                    </article>
                </div>
            </section>

            <section class="guard-ui-block guard-ui-block--actions" aria-labelledby="guard-quick-heading">
                <h2 id="guard-quick-heading" class="guard-ui-block__heading">
                    <?= guard_ui_icon_badge('bolt', 16) ?>
                    <span>Quick actions</span>
                </h2>
                <nav class="guard-ui-actions" aria-label="Shortcuts">
                    <a href="submit-report.php" class="guard-ui-action-card">
                        <span class="guard-ui-action-card__icon"><?= guard_ui_icon('plus-circle', 24) ?></span>
                        <span class="guard-ui-action-card__label">Submit report</span>
                    </a>
                    <a href="inbox.php" class="guard-ui-action-card">
                        <span class="guard-ui-action-card__icon"><?= guard_ui_icon('inbox', 24) ?></span>
                        <span class="guard-ui-action-card__label">Inbox &amp; tracking</span>
                    </a>
                    <a href="corner.php" class="guard-ui-action-card">
                        <span class="guard-ui-action-card__icon"><?= guard_ui_icon('shield', 24) ?></span>
                        <span class="guard-ui-action-card__label">Guard corner</span>
                    </a>
                </nav>
            </section>
        </div>
<?php
guard_layout_end();
