<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/guard_layout.php';

auth_require_permission('guard.inbox.view');

$companyId = (string) $_SESSION['company_id'];
$error = null;

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
            header('Location: inbox.php');
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
    while ($r = $memoResult->fetch(PDO::FETCH_ASSOC)) {
        $memos[] = $r;
    }
}

$guardNavActive = 'inbox';
guard_layout_head('Inbox');
?>
        <div class="guard-section-stack">
        <header class="page-header">
            <h1 class="page-title">Inbox</h1>
            <p class="page-subtitle">Secured memos from operations and administration.</p>
        </header>

        <?php if ($error !== null): ?>
            <div class="alert alert--error" role="alert"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> <?= e($error) ?></div>
        <?php endif; ?>

        <section class="guard-card" aria-labelledby="guard-inbox-memos-heading">
            <div class="guard-card__head">
                <h2 id="guard-inbox-memos-heading" class="panel-title">Memos</h2>
            </div>
            <?php if ($memos === []): ?>
                <p class="empty-state">No memos have been sent to you yet.</p>
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
        </div>
<?php
guard_layout_end();
