<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/guard_layout.php';

auth_require_permission('guard.reports.submit');

$companyId = (string) $_SESSION['company_id'];

$reports = [];
$reportResult = db_query(
    $conn,
    'SELECT Time_of_Report, Status, Template, Establishment
     FROM dgd
     WHERE Company_ID = ?
     ORDER BY Time_of_Report DESC
     LIMIT 50',
    's',
    [$companyId]
);
if ($reportResult) {
    while ($r = $reportResult->fetch_assoc()) {
        $reports[] = $r;
    }
}

$guardNavActive = 'reports';
guard_layout_head('My Reports');
?>
        <header class="page-header">
            <h1 class="page-title">My reports</h1>
            <p class="page-subtitle">Daily guard reports you have submitted. Contact operations if a submission is missing or needs correction.</p>
        </header>

        <section class="card-panel sa-panel" aria-labelledby="guard-reports-heading">
            <h2 id="guard-reports-heading" class="panel-title"><i class="fa-solid fa-file-lines" aria-hidden="true"></i> Submission history</h2>
            <?php if ($reports === []): ?>
                <p class="empty-state"><i class="fa-solid fa-folder-open" aria-hidden="true"></i>You have not submitted any reports yet.</p>
            <?php else: ?>
                <div class="data-table-wrap sa-table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th><i class="fa-solid fa-clock th-icon" aria-hidden="true"></i>Submitted</th>
                                <th><i class="fa-solid fa-building th-icon" aria-hidden="true"></i>Establishment</th>
                                <th><i class="fa-solid fa-file th-icon" aria-hidden="true"></i>Template</th>
                                <th><i class="fa-solid fa-circle-info th-icon" aria-hidden="true"></i>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $report): ?>
                                <?php
                                $status = (string) ($report['Status'] ?? 'Pending');
                                $badgeClass = match (strtoupper($status)) {
                                    'APPROVED', 'REVIEWED' => 'badge--admin',
                                    'REJECTED' => 'badge--logout',
                                    default => 'badge--guard',
                                };
                                $establishment = (string) ($report['Establishment'] ?? '');
                                if ($establishment !== '' && preg_match('/^[A-Za-z0-9+\/=]+$/', $establishment)) {
                                    $establishment = 'Encrypted';
                                }
                                ?>
                                <tr>
                                    <td class="mono"><?= e((string) ($report['Time_of_Report'] ?? '—')) ?></td>
                                    <td><?= e($establishment !== '' ? $establishment : '—') ?></td>
                                    <td><?= e((string) ($report['Template'] ?? '—')) ?></td>
                                    <td><span class="badge <?= e($badgeClass) ?>"><?= e($status) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
<?php
guard_layout_end();
