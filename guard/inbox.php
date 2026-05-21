<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/guard_layout.php';
require_once APP_ROOT . '/includes/group_messaging.php';

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
            exit();
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

$messagingAvailable = false;
$messagingViewerId = $companyId;
$messagingContacts = [];
$messagingActivePeer = null;
$messagingThread = [];
$messagingPostUrl = '';
$messagingReturnUrl = 'inbox.php';
$messagingMode = 'group';
$messagingGroups = [];
$messagingActiveGroupId = null;
$messagingGroupThread = [];
$messagingGroupMeta = null;
$messagingCanCreateGroups = false;
$messagingHeadGuardOptions = [];
$messagingGroupPostUrl = 'send-group-message.php';
$messagingShowDirect = false;
$groupsAvailable = false;

try {
    $groupsAvailable = message_groups_table_exists($conn);
    if ($groupsAvailable) {
        $messagingAvailable = true;
        $messagingGroups = group_messaging_list_groups_for_user($conn, $messagingViewerId);
        $groupParam = isset($_GET['group']) ? (int) $_GET['group'] : 0;
        if ($groupParam > 0 && group_messaging_user_in_group($conn, $groupParam, $messagingViewerId)) {
            $messagingActiveGroupId = $groupParam;
            $messagingGroupMeta = group_messaging_get_group_meta($conn, $groupParam, $messagingViewerId);
            $messagingGroupThread = group_messaging_fetch_messages($conn, $groupParam, $messagingViewerId);
        } elseif ($messagingGroups !== []) {
            $messagingActiveGroupId = $messagingGroups[0]['group_id'];
            $messagingGroupMeta = group_messaging_get_group_meta($conn, $messagingActiveGroupId, $messagingViewerId);
            $messagingGroupThread = group_messaging_fetch_messages($conn, $messagingActiveGroupId, $messagingViewerId);
        }
    }
} catch (Throwable $e) {
    error_log('guard/inbox messaging: ' . $e->getMessage());
}

$guardNavActive = 'inbox';
guard_layout_head('Inbox');
?>
        <header class="page-header">
            <h1 class="page-title">Inbox</h1>
            <p class="page-subtitle">Secured memos and directives from operations. Mark items as read when acknowledged.</p>
        </header>

        <?php if ($error !== null): ?>
            <div class="alert alert--error" role="alert"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> <?= e($error) ?></div>
        <?php endif; ?>

        <section class="card-panel sa-panel" aria-labelledby="guard-inbox-memos-heading">
            <h2 id="guard-inbox-memos-heading" class="panel-title"><i class="fa-solid fa-envelope-open-text" aria-hidden="true"></i> Memos</h2>
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

        <?php if ($groupsAvailable): ?>
            <?php require __DIR__ . '/../includes/messaging_board.php'; ?>
        <?php endif; ?>

        <style>
            .guard-memo-list {
                list-style: none;
                margin: 0;
                padding: 0;
                display: flex;
                flex-direction: column;
                gap: 12px;
            }
            .guard-memo-list__item {
                padding: 14px 16px;
                border-radius: var(--radius-md, 12px);
                background: var(--sa-card-bg, var(--app-card-bg));
                box-shadow: 0 0 0 1px var(--sa-card-border, var(--app-border)) inset, var(--sa-card-shadow, var(--app-shadow-sm));
            }
            .guard-memo-list__item.is-unread {
                border-left: 3px solid var(--brand-accent, var(--app-accent));
            }
            .guard-memo-list__head {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 8px;
                margin-bottom: 10px;
            }
            .guard-memo-list__time {
                font-size: 0.8125rem;
                color: var(--sa-card-ink-soft, var(--app-ink-soft));
            }
            .guard-memo-list__body {
                margin: 0 0 12px;
                font-size: 0.9375rem;
                line-height: 1.55;
                color: var(--sa-card-ink, var(--app-ink));
            }
            .guard-memo-list__action {
                margin: 0;
            }
        </style>
<?php
guard_layout_end();
