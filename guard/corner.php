<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/guard_layout.php';
require_once __DIR__ . '/../includes/guard_portal.php';

auth_require_permission('guard.corner.view');

$companyId = (string) $_SESSION['company_id'];
$announcements = guard_portal_announcements($conn);
$policies = guard_portal_policy_sections();
$socialFeeds = guard_portal_social_feeds();
$contacts = guard_portal_admin_contacts($conn);
$peer = trim((string) ($_GET['peer'] ?? ''));
if ($peer === '' && $contacts !== []) {
    $peer = $contacts[0]['company_id'];
}
$thread = $peer !== '' ? guard_portal_message_thread($conn, $companyId, $peer) : [];
$msgError = null;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['guard_message'])) {
    csrf_verify();
    $recipient = trim((string) ($_POST['recipient_id'] ?? ''));
    $body = trim((string) ($_POST['body'] ?? ''));
    if ($recipient === '' || $body === '') {
        $msgError = 'Message cannot be empty.';
    } elseif (!guard_portal_send_message($conn, $companyId, $recipient, $body)) {
        $msgError = 'Could not send message.';
    } else {
        header('Location: corner.php?tab=chat&peer=' . rawurlencode($recipient) . '#guard-chat');
        exit;
    }
    $peer = $recipient;
    $thread = guard_portal_message_thread($conn, $companyId, $peer);
}

$guardNavActive = 'corner';
$hubTab = trim((string) ($_GET['tab'] ?? 'announce'));
if (!in_array($hubTab, ['announce', 'chat', 'social', 'policies'], true)) {
    $hubTab = 'announce';
}

guard_layout_head('Guard Corner');
?>
        <div class="guard-section-stack guard-corner-page">
        <header class="page-header">
            <h1 class="page-title">Guard corner</h1>
            <p class="page-subtitle">Announcements, direct messaging, industry feeds, and company policies.</p>
        </header>

        <?php if ($msgError !== null): ?>
            <div class="alert alert--error" role="alert"><?= e($msgError) ?></div>
        <?php endif; ?>

        <div class="guard-hub-tabs" data-guard-hub-tabs role="tablist" aria-label="Guard corner sections">
            <button type="button" class="guard-hub-tabs__btn<?= $hubTab === 'announce' ? ' is-active' : '' ?>" data-guard-hub-tab="announce" role="tab" aria-selected="<?= $hubTab === 'announce' ? 'true' : 'false' ?>">Board</button>
            <button type="button" class="guard-hub-tabs__btn<?= $hubTab === 'chat' ? ' is-active' : '' ?>" data-guard-hub-tab="chat" role="tab">Messages</button>
            <button type="button" class="guard-hub-tabs__btn<?= $hubTab === 'social' ? ' is-active' : '' ?>" data-guard-hub-tab="social" role="tab">Feeds</button>
            <button type="button" class="guard-hub-tabs__btn<?= $hubTab === 'policies' ? ' is-active' : '' ?>" data-guard-hub-tab="policies" role="tab">Policies</button>
        </div>

        <div class="guard-hub-panels">
        <section class="guard-hub-panel<?= $hubTab === 'announce' ? ' is-active' : '' ?>" data-guard-hub-panel="announce" role="tabpanel">
            <div class="guard-card">
                <div class="guard-card__head">
                    <h2 class="panel-title">Messaging board</h2>
                </div>
                <?php if ($announcements === []): ?>
                    <p class="empty-state">No announcements yet.</p>
                <?php else: ?>
                    <div class="guard-feed">
                        <?php foreach ($announcements as $item): ?>
                            <article class="guard-feed__item">
                                <div class="guard-feed__head">
                                    <div>
                                        <h3 class="guard-feed__title"><?= e((string) ($item['title'] ?? '')) ?></h3>
                                        <time class="guard-feed__time" datetime="<?= e((string) ($item['created_at'] ?? '')) ?>">
                                            <?= e((string) ($item['created_at'] ?? '')) ?>
                                        </time>
                                    </div>
                                </div>
                                <p class="guard-feed__body"><?= nl2br(e((string) ($item['body'] ?? ''))) ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="guard-hub-panel<?= $hubTab === 'chat' ? ' is-active' : '' ?>" data-guard-hub-panel="chat" role="tabpanel" id="guard-chat">
            <div class="guard-card">
                <div class="guard-card__head">
                    <h2 class="panel-title">Direct messaging</h2>
                </div>
                <?php if ($contacts === []): ?>
                    <p class="empty-state">Messaging requires database setup. Run migration <code>013_guard_portal_features.sql</code>.</p>
                <?php else: ?>
                    <div class="form-field" style="margin-bottom:10px;">
                        <label for="peer_select">Contact (administrator)</label>
                        <select id="peer_select" data-guard-peer-select>
                            <?php foreach ($contacts as $c): ?>
                                <option value="<?= e($c['company_id']) ?>"<?= $peer === $c['company_id'] ? ' selected' : '' ?>>
                                    <?= e($c['label']) ?><?= $c['unread'] > 0 ? ' (' . (int) $c['unread'] . ' new)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="guard-chat">
                        <div class="guard-chat__messages">
                            <?php if ($thread === []): ?>
                                <p class="form-hint">No messages yet. Send the first message below.</p>
                            <?php else: ?>
                                <?php foreach ($thread as $msg): ?>
                                    <div class="guard-chat__bubble<?= !empty($msg['is_mine']) ? ' guard-chat__bubble--mine' : ' guard-chat__bubble--theirs' ?>">
                                        <?= nl2br(e((string) ($msg['body_text'] ?? ''))) ?>
                                        <time class="guard-chat__time"><?= e((string) ($msg['created_at'] ?? '')) ?></time>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <form method="POST" class="guard-chat__compose">
                            <?= csrf_field() ?>
                            <input type="hidden" name="guard_message" value="1">
                            <input type="hidden" name="recipient_id" value="<?= e($peer) ?>">
                            <textarea name="body" class="guard-chat__input" rows="2" maxlength="4000" required placeholder="Type a message…"></textarea>
                            <button type="submit" class="guard-chat__send" aria-label="Send">
                                <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="guard-hub-panel<?= $hubTab === 'social' ? ' is-active' : '' ?>" data-guard-hub-panel="social" role="tabpanel">
            <div class="guard-card">
                <div class="guard-card__head">
                    <h2 class="panel-title">Industry feeds</h2>
                </div>
                <p class="form-hint">Open official Facebook pages in a new tab (lightweight cards).</p>
                <div class="guard-social-grid">
                    <?php foreach ($socialFeeds as $feed): ?>
                        <a href="<?= e($feed['url']) ?>" class="guard-social-card" target="_blank" rel="noopener noreferrer">
                            <i class="fa-brands <?= e($feed['icon']) ?>" aria-hidden="true"></i>
                            <span><?= e($feed['label']) ?></span>
                            <small class="form-hint">Facebook</small>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="guard-hub-panel<?= $hubTab === 'policies' ? ' is-active' : '' ?>" data-guard-hub-panel="policies" role="tabpanel">
            <div class="guard-card">
                <div class="guard-card__head">
                    <h2 class="panel-title">Company general policies</h2>
                </div>
                <div class="guard-accordion">
                    <?php foreach ($policies as $policy): ?>
                        <div class="guard-accordion__item">
                            <button type="button" class="guard-accordion__trigger">
                                <?= e($policy['title']) ?>
                                <i class="fa-solid fa-chevron-down guard-accordion__chev" aria-hidden="true"></i>
                            </button>
                            <div class="guard-accordion__body"><?= nl2br(e($policy['body'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        </div>
        </div>
<?php
guard_layout_end();
