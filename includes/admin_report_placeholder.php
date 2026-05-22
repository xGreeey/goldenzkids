<?php
declare(strict_types=1);

require_once __DIR__ . '/admin_report_nav.php';
require_once __DIR__ . '/admin_ui_icons.php';

/**
 * Placeholder panel for report modules not yet wired to a full registry.
 */
function admin_report_placeholder_panel(string $navSlug): void
{
    $item = admin_report_nav_item_by_slug($navSlug);
    if ($item === null) {
        return;
    }
    $title = (string) $item['label'];
    $hints = [
        'weekly-activity' => 'Weekly summaries from head guards will appear here with filters, export, and status tabs.',
        'daily-activity' => 'Shift logs and field activity for the current day will appear here with search and date filters.',
    ];
    $hint = $hints[$navSlug] ?? 'Full registry and filters for this module are coming next.';
    ?>
        <section class="report-hub-placeholder" aria-labelledby="report-hub-placeholder-title">
            <div class="report-hub-placeholder__card">
                <div class="report-hub-placeholder__icon" aria-hidden="true">
                    <?= admin_nav_icon((string) ($item['icon'] ?? 'clipboard-list')) ?>
                </div>
                <h2 id="report-hub-placeholder-title" class="report-hub-placeholder__title"><?= e($title) ?></h2>
                <p class="report-hub-placeholder__text"><?= e($hint) ?></p>
                <p class="report-hub-placeholder__status">
                    <span class="report-hub-placeholder__badge">In development</span>
                </p>
                <nav class="report-hub-placeholder__links" aria-label="Other reports">
                    <?php foreach (admin_report_nav_items() as $link):
                        if ($link['slug'] === $item['slug']) {
                            continue;
                        }
                        ?>
                    <a href="<?= e((string) $link['href']) ?>" class="report-hub-placeholder__link"><?= e((string) $link['label']) ?></a>
                    <?php endforeach; ?>
                </nav>
            </div>
        </section>
    <?php
}
