<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/guard_layout.php';
require_once __DIR__ . '/../includes/guard_portal.php';

auth_require_permission('guard.corner.view');

$companyId = (string) ($_SESSION['company_id'] ?? '');
$announcements = guard_portal_announcements($conn, $companyId);
$policies = guard_portal_policy_sections();
$socialFeeds = guard_portal_social_feeds();

$guardNavActive = 'corner';
$hubTab = trim((string) ($_GET['tab'] ?? 'announce'));
if ($hubTab === 'chat') {
    header('Location: ' . app_url('guard/corner.php?tab=announce'));
    exit;
}
if (!in_array($hubTab, ['announce', 'social', 'policies'], true)) {
    $hubTab = 'announce';
}

guard_layout_head('Guard Corner');
?>
        <div class="guard-section-stack guard-corner-page">
        <div class="guard-hub-tabs" data-guard-hub-tabs role="tablist" aria-label="Guard corner sections">
            <button type="button" class="guard-hub-tabs__btn<?= $hubTab === 'announce' ? ' is-active' : '' ?>" data-guard-hub-tab="announce" role="tab" aria-selected="<?= $hubTab === 'announce' ? 'true' : 'false' ?>">Board</button>
            <button type="button" class="guard-hub-tabs__btn<?= $hubTab === 'social' ? ' is-active' : '' ?>" data-guard-hub-tab="social" role="tab" aria-selected="<?= $hubTab === 'social' ? 'true' : 'false' ?>">Feeds</button>
            <button type="button" class="guard-hub-tabs__btn<?= $hubTab === 'policies' ? ' is-active' : '' ?>" data-guard-hub-tab="policies" role="tab" aria-selected="<?= $hubTab === 'policies' ? 'true' : 'false' ?>">Policies</button>
        </div>

        <div class="guard-hub-panels">
        <section class="guard-hub-panel<?= $hubTab === 'announce' ? ' is-active' : '' ?>" data-guard-hub-panel="announce" role="tabpanel">
            <div class="guard-card">
                <div class="guard-card__head">
                    <h2 class="panel-title">Announcement</h2>
                </div>
                <div class="guard-corner-reminder">
                    <p class="guard-corner-reminder__heading"><strong>DDO Expiry Reminder</strong></p>
                    <p class="guard-corner-reminder__text">If your Daily Duty Order (DDO) is nearing its expiration, please ensure necessary actions are taken before the expiration date.</p>
                </div>
                <div class="guard-corner-memos">
                    <h3 class="guard-corner-memos__title">Published memo</h3>
                    <?php if ($announcements === []): ?>
                        <p class="empty-state">No published memos yet. Admin memos sent from the portal will appear here.</p>
                    <?php else: ?>
                        <div class="guard-feed">
                            <?php foreach ($announcements as $item):
                                $isUnread = empty($item['is_read']);
                                ?>
                                <article class="guard-feed__item<?= $isUnread ? ' guard-feed__item--unread' : '' ?>">
                                    <div class="guard-feed__head">
                                        <div>
                                            <h4 class="guard-feed__title">
                                                <?= e((string) ($item['title'] ?? '')) ?>
                                                <?php if ($isUnread): ?>
                                                    <span class="guard-badge guard-badge--pending">New</span>
                                                <?php endif; ?>
                                            </h4>
                                            <time class="guard-feed__time" datetime="<?= e((string) ($item['created_at'] ?? '')) ?>">
                                                <?= e((string) ($item['created_display'] ?? $item['created_at'] ?? '')) ?>
                                            </time>
                                        </div>
                                    </div>
                                    <p class="guard-feed__body"><?= nl2br(e((string) ($item['body'] ?? ''))) ?></p>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="guard-hub-panel<?= $hubTab === 'social' ? ' is-active' : '' ?>" data-guard-hub-panel="social" role="tabpanel">
            <div class="guard-card guard-card--live-feeds">
                <?php guard_portal_social_feeds_markup($socialFeeds); ?>
                <p class="guard-live-feeds__refresh" data-guard-live-feeds-refresh aria-live="polite"></p>
            </div>
        </section>

        <section class="guard-hub-panel<?= $hubTab === 'policies' ? ' is-active' : '' ?>" data-guard-hub-panel="policies" role="tabpanel">
            <div class="guard-card">
                <div class="guard-card__head">
                    <h2 class="panel-title">Company general policies</h2>
                </div>
                <ul class="guard-policy-list">
                    <?php foreach ($policies as $policy): ?>
                        <?php
                        $sourceId = 'guard-policy-source-' . $policy['slug'];
                        $rawId = $sourceId . '-raw';
                        ?>
                        <li class="guard-policy-list__item">
                            <button
                                type="button"
                                class="guard-policy-list__trigger"
                                data-policy-trigger
                                data-policy-title="<?= e($policy['title']) ?>"
                                data-policy-source="<?= e($rawId) ?>"
                                aria-haspopup="dialog"
                            >
                                <span class="guard-policy-list__label"><?= e($policy['title']) ?></span>
                                <i class="fa-solid fa-expand guard-policy-list__icon" aria-hidden="true"></i>
                            </button>
                            <textarea id="<?= e($rawId) ?>" class="guard-policy-list__raw" hidden readonly><?= e(trim($policy['body'])) ?></textarea>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>
        </div>
        </div>
<?php
guard_layout_end();
