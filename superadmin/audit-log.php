<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/superadmin_accountability.php';

auth_require_permission('superadmin.audit.view');

$perPage = 25;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$eventFilter = strtoupper(trim((string) ($_GET['event'] ?? '')));
$searchId = strtoupper(trim((string) ($_GET['company_id'] ?? '')));

$where = [];
$params = [];
$types = '';

if ($eventFilter === 'LOGIN' || $eventFilter === 'LOGOUT') {
    $where[] = 'Event = ?';
    $params[] = $eventFilter;
    $types .= 's';
} elseif ($eventFilter === 'ACCOUNT') {
    $where[] = "Event LIKE 'ACCOUNT_%'";
}

if ($searchId !== '' && preg_match('/^ABC-2[0-9]{3}-[0-9]{4}$/', $searchId)) {
    $where[] = 'Company_ID = ?';
    $params[] = $searchId;
    $types .= 's';
}

$whereSql = $where !== [] ? ' WHERE ' . implode(' AND ', $where) : '';

$total = 0;
$countSql = 'SELECT COUNT(*) AS c FROM recording' . $whereSql;
if ($params === []) {
    $countResult = $conn->query($countSql);
} else {
    $countResult = db_query($conn, $countSql, $types, $params);
}
if ($countResult) {
    $total = (int) $countResult->fetch_assoc()['c'];
}

$totalPages = max(1, (int) ceil($total / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

$auditCols = recording_supports_audit_detail($conn)
    ? 'id, Company_ID, actor_company_id, Designation, Event, event_detail, Time_Of_Event'
    : 'id, Company_ID, Designation, Event, Time_Of_Event';
$listSql = "SELECT {$auditCols} FROM recording"
    . $whereSql
    . ' ORDER BY Time_Of_Event DESC LIMIT ? OFFSET ?';

$listParams = $params;
$listTypes = $types . 'ii';
$listParams[] = $perPage;
$listParams[] = $offset;

$entries = [];
$listResult = db_query($conn, $listSql, $listTypes, $listParams);
if ($listResult) {
    while ($row = $listResult->fetch_assoc()) {
        $entries[] = $row;
    }
}

$queryBase = [];
if ($eventFilter !== '') {
    $queryBase['event'] = $eventFilter;
}
if ($searchId !== '') {
    $queryBase['company_id'] = $searchId;
}

$superadminNavActive = 'audit';
$superadminMobileTitle = 'Audit Log';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ABC Security | Audit Log</title>
    <script src="https://kit.fontawesome.com/3142eebea3.js" crossorigin="anonymous"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
<?php require __DIR__ . '/../includes/admin_shell.css.php'; ?>
<?php require __DIR__ . '/../includes/superadmin_page.css.php'; ?>
    </style>
</head>
<body class="light-mode">

<?php require __DIR__ . '/../includes/superadmin_sidebar.php'; ?>

    <main class="app-main">
        <header class="page-header">
            <h1 class="page-title">Audit log</h1>
        </header>

        <div class="toolbar">
            <form method="GET" class="filter-form">
                <div class="form-field">
                    <label for="company_id" class="label-with-icon"><i class="fa-solid fa-id-card" aria-hidden="true"></i> Employee ID</label>
                    <input type="text" id="company_id" name="company_id" value="<?= e($searchId) ?>" placeholder="ABC-2024-0001">
                </div>
                <div class="form-field">
                    <label for="event" class="label-with-icon"><i class="fa-solid fa-filter" aria-hidden="true"></i> Event</label>
                    <select id="event" name="event">
                        <option value=""<?= $eventFilter === '' ? ' selected' : '' ?>>All events</option>
                        <option value="LOGIN"<?= $eventFilter === 'LOGIN' ? ' selected' : '' ?>>Login</option>
                        <option value="LOGOUT"<?= $eventFilter === 'LOGOUT' ? ' selected' : '' ?>>Logout</option>
                        <option value="ACCOUNT"<?= $eventFilter === 'ACCOUNT' ? ' selected' : '' ?>>Account changes</option>
                    </select>
                </div>
                <button type="submit" class="btn-primary"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button>
            </form>
        </div>

        <section class="card-panel">
            <h2 class="panel-title"><i class="fa-solid fa-list-check" aria-hidden="true"></i> Event history <span class="stat-hint">(<?= e((string) $total) ?>)</span></h2>

            <?php if ($entries === []): ?>
                <p class="empty-state"><i class="fa-solid fa-inbox" aria-hidden="true"></i>No audit events match your filters.</p>
            <?php else: ?>
                <div class="data-table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th><i class="fa-solid fa-hashtag th-icon" aria-hidden="true"></i>#</th>
                                <th><i class="fa-solid fa-clock th-icon" aria-hidden="true"></i>Time</th>
                                <th><i class="fa-solid fa-id-card th-icon" aria-hidden="true"></i>Employee ID</th>
                                <th><i class="fa-solid fa-user-shield th-icon" aria-hidden="true"></i>Performed by</th>
                                <th><i class="fa-solid fa-bolt th-icon" aria-hidden="true"></i>Event</th>
                                <th><i class="fa-solid fa-align-left th-icon" aria-hidden="true"></i>Detail</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entries as $entry): ?>
                                <?php
                                $ev = strtoupper((string) ($entry['Event'] ?? ''));
                                $badgeClass = match ($ev) {
                                    'LOGOUT' => 'badge--logout',
                                    'LOGIN' => 'badge--login',
                                    default => 'badge--admin',
                                };
                                ?>
                                <tr>
                                    <td class="mono"><?= e((string) ($entry['id'] ?? '')) ?></td>
                                    <td class="mono"><?= e((string) ($entry['Time_Of_Event'] ?? '')) ?></td>
                                    <td class="mono"><?= e((string) ($entry['Company_ID'] ?? '—')) ?></td>
                                    <td class="mono"><?= e(superadmin_audit_actor_label($entry)) ?></td>
                                    <td>
                                        <span class="event-cell">
                                            <i class="fa-solid <?= e(superadmin_event_icon($ev)) ?>" aria-hidden="true"></i>
                                            <span class="badge <?= e($badgeClass) ?>"><?= e(superadmin_event_label($ev)) ?></span>
                                        </span>
                                    </td>
                                    <td><?= e((string) ($entry['event_detail'] ?? '—')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav class="pagination" aria-label="Audit log pages">
                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <?php
                            $pageQuery = array_merge($queryBase, ['page' => $p]);
                            $href = 'audit-log.php?' . http_build_query($pageQuery);
                            ?>
                            <?php if ($p === $page): ?>
                                <span class="current"><?= $p ?></span>
                            <?php else: ?>
                                <a href="<?= e($href) ?>"><?= $p ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </main>
</div>

<script>
<?php require __DIR__ . '/../includes/admin_shell.js.php'; ?>
</script>
</body>
</html>
