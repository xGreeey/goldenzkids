<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/guard_layout.php';
require_once __DIR__ . '/../includes/guard_portal.php';

auth_require_permission('guard.inbox.view');

$companyId = (string) $_SESSION['company_id'];
$error = null;
$inboxTab = trim((string) ($_GET['tab'] ?? 'memos'));
if (!in_array($inboxTab, ['memos', 'reports'], true)) {
    $inboxTab = 'memos';
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['mark_read'])) {
    csrf_verify();

    $memoId = (int) ($_POST['memo_id'] ?? 0);
    if ($memoId > 0) {
        $ok = db_execute(
            $conn,
            'UPDATE memo_recipients SET is_read = 1, read_at = NOW()
             WHERE Memo_ID = ? AND Company_ID = ?',
            'is',
            [$memoId, $companyId]
        );
        if ($ok) {
            header('Location: inbox.php?tab=memos');
            exit;
        }
        $error = 'Could not update memo status.';
    }
}

$memos = [];
$memoSql = 'SELECT m.Memo_ID, m.Category, m.Body_Text, m.Distribution_Protocol, m.created_at,
                   mr.is_read, mr.read_at
            FROM memo_recipients mr
            INNER JOIN memos m ON m.Memo_ID = mr.Memo_ID
            WHERE mr.Company_ID = ?
            ORDER BY mr.is_read ASC, m.created_at DESC';
$memoResult = db_query($conn, $memoSql, 's', [$companyId]);
if ($memoResult) {
    while ($r = $memoResult->fetch_assoc()) {
        $memos[] = $r;
    }
}

$reports = guard_portal_user_reports($conn, $companyId);

$guardNavActive = 'inbox';
guard_layout_head('Inbox');
?>
        <div class="guard-section-stack">
        <header class="page-header">
            <h1 class="page-title">Inbox</h1>
            <p class="page-subtitle">Secured memos and submitted report tracking synced with admin review status.</p>
        </header>

        <?php if ($error !== null): ?>
            <div class="alert alert--error" role="alert"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> <?= e($error) ?></div>
        <?php endif; ?>

        <div class="guard-hub-tabs" data-guard-hub-tabs role="tablist" aria-label="Inbox sections">
            <button type="button" class="guard-hub-tabs__btn<?= $inboxTab === 'memos' ? ' is-active' : '' ?>" data-guard-hub-tab="memos" role="tab" aria-selected="<?= $inboxTab === 'memos' ? 'true' : 'false' ?>">
                <i class="fa-solid fa-envelope" aria-hidden="true"></i> Memos
            </button>
            <button type="button" class="guard-hub-tabs__btn<?= $inboxTab === 'reports' ? ' is-active' : '' ?>" data-guard-hub-tab="reports" role="tab">
                <i class="fa-solid fa-file-lines" aria-hidden="true"></i> Reports
            </button>
        </div>

        <div class="guard-hub-panels">
        <section class="guard-hub-panel<?= $inboxTab === 'memos' ? ' is-active' : '' ?>" data-guard-hub-panel="memos" role="tabpanel">
            <section class="guard-card" aria-labelledby="guard-inbox-memos-heading">
                <div class="guard-card__head">
                    <span class="guard-card__icon" aria-hidden="true"><i class="fa-solid fa-envelope-open-text"></i></span>
                    <h2 id="guard-inbox-memos-heading" class="panel-title">Memos</h2>
                </div>
                <?php if ($memos === []): ?>
                    <p class="empty-state"><i class="fa-solid fa-inbox" aria-hidden="true"></i>No memos have been sent to you yet.</p>
                <?php else: ?>
                    <ul class="guard-memo-list">
                        <?php foreach ($memos as $memo): ?>
                            <?php
                            $isRead = (int) ($memo['is_read'] ?? 0) === 1;
                            $memoId = (int) ($memo['Memo_ID'] ?? 0);
                            ?>
                            <li class="guard-memo-list__item<?= $isRead ? ' is-read' : ' is-unread' ?>">
                                <div class="guard-memo-list__head">
                                    <span class="badge <?= $isRead ? 'badge--admin' : 'badge--guard' ?>">
                                        <?= e((string) ($memo['Category'] ?? 'MEMO')) ?>
                                    </span>
                                    <time class="guard-memo-list__time mono" datetime="<?= e((string) ($memo['created_at'] ?? '')) ?>">
                                        <?= e((string) ($memo['created_at'] ?? '')) ?>
                                    </time>
                                </div>
                                <p class="guard-memo-list__body"><?= nl2br(e((string) ($memo['Body_Text'] ?? ''))) ?></p>
                                <?php if (!$isRead && $memoId > 0): ?>
                                    <form method="POST" class="guard-memo-list__action">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="mark_read" value="1">
                                        <input type="hidden" name="memo_id" value="<?= $memoId ?>">
                                        <button type="submit" class="btn-primary">
                                            <i class="fa-solid fa-check" aria-hidden="true"></i>
                                            Mark as read
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </section>

        <section class="guard-hub-panel<?= $inboxTab === 'reports' ? ' is-active' : '' ?>" data-guard-hub-panel="reports" role="tabpanel">
            <section class="guard-card" aria-labelledby="guard-inbox-reports-heading">
                <div class="guard-card__head">
                    <span class="guard-card__icon" aria-hidden="true"><i class="fa-solid fa-clipboard-list"></i></span>
                    <h2 id="guard-inbox-reports-heading" class="panel-title">Report tracking</h2>
                    <a href="submit-report.php" class="btn-primary">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i> New report
                    </a>
                </div>
                <p class="form-hint">
                    <i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i>
                    Status matches the admin dashboard. Refresh to see updates.
                </p>
                <?php if ($reports === []): ?>
                    <p class="empty-state"><i class="fa-solid fa-folder-open" aria-hidden="true"></i>No reports submitted yet.</p>
                <?php else: ?>
                    <ul class="guard-report-list">
                        <?php foreach ($reports as $report): ?>
                            <?php $status = (string) ($report['Status'] ?? 'Pending'); ?>
                            <li class="guard-report-list__item">
                                <span class="guard-badge <?= e(guard_portal_status_badge_class($status)) ?>"><?= e($status) ?></span>
                                <time class="guard-report-list__date"><?= e((string) ($report['Time_of_Report'] ?? '—')) ?></time>
                                <span class="guard-report-list__meta">
                                    <?= e((string) ($report['establishment_label'] ?? '—')) ?>
                                    · <?= e((string) ($report['Template'] ?? 'Report')) ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </section>
        </div>
        </div>
<?php
guard_layout_end();
