<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/superadmin_accountability.php';

auth_require_permission('superadmin.audit.view');

$perPage = 10;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$eventFilter = strtoupper(trim((string) ($_GET['event'] ?? '')));
$searchId = strtoupper(trim((string) ($_GET['company_id'] ?? '')));
$performedByFilter = strtoupper(trim((string) ($_GET['performed_by'] ?? '')));

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

if ($performedByFilter === 'SUPERADMIN') {
    $where[] = 'Designation LIKE ?';
    $params[] = 'SUPERADMIN%';
    $types .= 's';
} elseif ($performedByFilter === 'ADMIN') {
    $where[] = 'Designation LIKE ?';
    $params[] = 'ADMIN%';
    $types .= 's';
} elseif ($performedByFilter === 'GUARD') {
    $where[] = '(Designation LIKE ? OR Designation LIKE ?)';
    $params[] = 'HEADGUARD%';
    $params[] = 'GUARD%';
    $types .= 'ss';
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
if ($performedByFilter !== '') {
    $queryBase['performed_by'] = $performedByFilter;
}

$superadminNavActive = 'audit';
$superadminMobileTitle = 'Audit Log';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?= mobile_meta_tags() ?>
    <title><?= e(app_agency_name()) ?> | Audit Log</title>
    <script src="https://kit.fontawesome.com/3142eebea3.js" crossorigin="anonymous"></script>
    <?= app_fonts_link() ?>
    <style>
<?php admin_shell_styles(); ?>
<?php superadmin_page_styles(); ?>
    </style>
</head>
<body class="light-mode superadmin-portal">

<?php require __DIR__ . '/../includes/superadmin_sidebar.php'; ?>

    <main class="app-main">
        <header class="page-header">
            <h1 class="page-title">Audit log</h1>
            <p class="page-subtitle">Review sign-ins, sign-outs, and account changes recorded for accountability.</p>
        </header>

        <div class="toolbar">
            <form method="GET" class="filter-form" id="auditFilterForm">
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
                <div class="form-field">
                    <label for="performed_by" class="label-with-icon"><i class="fa-solid fa-user-shield" aria-hidden="true"></i> Performed by</label>
                    <select id="performed_by" name="performed_by">
                        <option value=""<?= $performedByFilter === '' ? ' selected' : '' ?>>All roles</option>
                        <option value="SUPERADMIN"<?= $performedByFilter === 'SUPERADMIN' ? ' selected' : '' ?>>SUPERADMIN</option>
                        <option value="ADMIN"<?= $performedByFilter === 'ADMIN' ? ' selected' : '' ?>>ADMIN</option>
                        <option value="GUARD"<?= $performedByFilter === 'GUARD' ? ' selected' : '' ?>>GUARD</option>
                    </select>
                </div>
                <button type="button" class="btn-primary" id="resetAuditFilters" aria-label="Reset filters">
                    <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Reset
                </button>
            </form>
        </div>

        <section class="card-panel">
            <h2 class="panel-title">Event history <span class="stat-hint">(<?= e((string) $total) ?>)</span></h2>

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
                        <tbody id="auditTableBody">
                            <?php foreach ($entries as $entry): ?>
                                <?php
                                $ev = strtoupper((string) ($entry['Event'] ?? ''));
                                $designation = strtoupper((string) ($entry['Designation'] ?? ''));
                                $actorRole = str_starts_with($designation, 'SUPERADMIN')
                                    ? 'SUPERADMIN'
                                    : (str_starts_with($designation, 'ADMIN')
                                        ? 'ADMIN'
                                        : ((str_contains($designation, 'HEADGUARD') || str_contains($designation, 'GUARD'))
                                            ? 'GUARD'
                                            : ''));
                                $badgeClass = match ($ev) {
                                    'LOGOUT' => 'badge--logout',
                                    'LOGIN' => 'badge--login',
                                    default => 'badge--admin',
                                };
                                ?>
                                <tr data-company="<?= e(strtoupper((string) ($entry['Company_ID'] ?? ''))) ?>" data-event="<?= e($ev) ?>" data-actor-role="<?= e($actorRole) ?>">
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
                <p id="auditLiveNoResults" class="stat-hint" style="margin-top:10px;" hidden>No results found.</p>

                <?php if ($totalPages > 1): ?>
                    <nav class="pagination" id="auditPagination" aria-label="Audit log pages">
                        <?php if ($page > 1): ?>
                            <?php
                            $prevQuery = array_merge($queryBase, ['page' => $page - 1]);
                            $prevHref = 'audit-log.php?' . http_build_query($prevQuery);
                            ?>
                            <a href="<?= e($prevHref) ?>" aria-label="Previous page">Prev</a>
                        <?php endif; ?>

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

                        <?php if ($page < $totalPages): ?>
                            <?php
                            $nextQuery = array_merge($queryBase, ['page' => $page + 1]);
                            $nextHref = 'audit-log.php?' . http_build_query($nextQuery);
                            ?>
                            <a href="<?= e($nextHref) ?>" aria-label="Next page">Next</a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var filterForm = document.getElementById('auditFilterForm');
    var companyInput = document.getElementById('company_id');
    var eventSelect = document.getElementById('event');
    var performedBySelect = document.getElementById('performed_by');
    var resetBtn = document.getElementById('resetAuditFilters');
    var tableBody = document.getElementById('auditTableBody');
    var noResults = document.getElementById('auditLiveNoResults');
    var pagination = document.getElementById('auditPagination');
    if (!filterForm || !companyInput || !eventSelect || !performedBySelect || !resetBtn || !tableBody || !noResults) {
        return;
    }

    var rows = Array.prototype.slice.call(tableBody.querySelectorAll('tr')).map(function (row) {
        return {
            el: row,
            company: (row.getAttribute('data-company') || '').toUpperCase(),
            event: (row.getAttribute('data-event') || '').toUpperCase(),
            actorRole: (row.getAttribute('data-actor-role') || '').toUpperCase(),
            searchable: (row.textContent || '').toUpperCase().replace(/\s+/g, ' ').trim()
        };
    });
    if (rows.length === 0) {
        return;
    }

    function matchesEvent(rowEvent, filterEvent) {
        if (filterEvent === '') {
            return true;
        }
        if (filterEvent === 'ACCOUNT') {
            return rowEvent.indexOf('ACCOUNT_') === 0;
        }
        return rowEvent === filterEvent;
    }

    function applyLiveFilters() {
        var companyNeedle = (companyInput.value || '').toUpperCase().trim();
        var eventNeedle = (eventSelect.value || '').toUpperCase().trim();
        var actorRoleNeedle = (performedBySelect.value || '').toUpperCase().trim();
        var visible = 0;

        rows.forEach(function (row) {
            var companyOk = companyNeedle === '' || row.searchable.indexOf(companyNeedle) !== -1;
            var eventOk = matchesEvent(row.event, eventNeedle);
            var actorOk = actorRoleNeedle === '' || row.actorRole === actorRoleNeedle;
            var show = companyOk && eventOk && actorOk;
            row.el.hidden = !show;
            if (show) {
                visible += 1;
            }
        });
        var hasFilter = companyNeedle !== '' || eventNeedle !== '' || actorRoleNeedle !== '';
        noResults.hidden = !(hasFilter && visible === 0);
        if (pagination) {
            pagination.hidden = hasFilter;
        }
    }

    function resetFilters() {
        companyInput.value = '';
        eventSelect.value = '';
        performedBySelect.value = '';
        applyLiveFilters();
        var cleanUrl = new URL(window.location.href);
        cleanUrl.search = '';
        history.replaceState(history.state, '', cleanUrl.pathname + cleanUrl.search + cleanUrl.hash);
    }

    var queued = false;
    function scheduleLiveFilters() {
        if (queued) {
            return;
        }
        queued = true;
        requestAnimationFrame(function () {
            queued = false;
            applyLiveFilters();
        });
    }

    filterForm.addEventListener('submit', function (e) {
        e.preventDefault();
        applyLiveFilters();
    });
    companyInput.addEventListener('input', scheduleLiveFilters);
    eventSelect.addEventListener('change', applyLiveFilters);
    performedBySelect.addEventListener('change', applyLiveFilters);
    resetBtn.addEventListener('click', resetFilters);
});
</script>

<?php admin_shell_scripts(); ?>
</body>
</html>
