<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/guard_layout.php';
require_once __DIR__ . '/../includes/guard_portal.php';

auth_require_permission('guard.corner.view');

$announcements = guard_portal_announcements($conn);
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
        <header class="page-header">
            <h1 class="page-title">Guard corner</h1>
            <p class="page-subtitle">Announcements, industry feeds, and company policies.</p>
        </header>

        <div class="guard-hub-tabs" data-guard-hub-tabs role="tablist" aria-label="Guard corner sections">
            <button type="button" class="guard-hub-tabs__btn<?= $hubTab === 'announce' ? ' is-active' : '' ?>" data-guard-hub-tab="announce" role="tab" aria-selected="<?= $hubTab === 'announce' ? 'true' : 'false' ?>">Board</button>
            <button type="button" class="guard-hub-tabs__btn<?= $hubTab === 'social' ? ' is-active' : '' ?>" data-guard-hub-tab="social" role="tab" aria-selected="<?= $hubTab === 'social' ? 'true' : 'false' ?>">Feeds</button>
            <button type="button" class="guard-hub-tabs__btn<?= $hubTab === 'policies' ? ' is-active' : '' ?>" data-guard-hub-tab="policies" role="tab" aria-selected="<?= $hubTab === 'policies' ? 'true' : 'false' ?>">Policies</button>
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
