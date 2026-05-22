<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once APP_ROOT . '/includes/admin_weekly_activity_reports.php';

auth_require_permission('admin.reports.view');

$actorId = (string) ($_SESSION['company_id'] ?? 'admin');
$openId = trim((string) ($_GET['weekly'] ?? ''));
$drawerMode = trim((string) ($_GET['mode'] ?? 'view'));
if (!in_array($drawerMode, ['view', 'edit'], true)) {
    $drawerMode = 'view';
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();
    $action = trim((string) ($_POST['action'] ?? ''));

    if ($action === 'update_weekly') {
        $id = trim((string) ($_POST['weekly_id'] ?? ''));
        $updated = admin_weekly_activity_update($id, [
            'status' => (string) ($_POST['status'] ?? ''),
        ], $actorId);

        if ($updated === null) {
            redirect_with_alert(ADMIN_WEEKLY_SUMMARY_MODULE_LABEL . ' not found.', 'weekly-activity.php');
        }

        redirect_with_alert(
            ADMIN_WEEKLY_SUMMARY_MODULE_LABEL . ' ' . (string) ($updated['ref'] ?? $id) . ' saved.',
            'weekly-activity.php?weekly=' . rawurlencode($id) . '&mode=view'
        );
    }
}

$records = admin_weekly_activity_store_all();
$statusCounts = admin_weekly_activity_status_counts($records);
$openRecord = $openId !== '' ? admin_weekly_activity_find($openId) : null;
if ($openId !== '' && $openRecord === null) {
    $openId = '';
}

$statusDefinitions = admin_weekly_activity_status_definitions();
$registryStatusTabs = [
    ['slug' => 'all', 'label' => 'All', 'count' => (int) $statusCounts['all'], 'title' => 'Every ' . ADMIN_WEEKLY_SUMMARY_MODULE_LABEL],
];
foreach ($statusDefinitions as $slug => $def) {
    $registryStatusTabs[] = [
        'slug' => $slug,
        'label' => (string) $def['tab'],
        'count' => (int) ($statusCounts[$slug] ?? 0),
        'title' => (string) $def['description'],
    ];
}
$statusTabFromQuery = trim((string) ($_GET['status'] ?? ''));
$validStatusTabs = ['all', ...admin_weekly_activity_status_slugs()];
$initialStatusTab = in_array($statusTabFromQuery, $validStatusTabs, true) ? $statusTabFromQuery : '';

$adminNavActive = 'weekly-activity';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?= mobile_meta_tags() ?>
    <title><?= e(app_agency_name()) ?> | <?= e(ADMIN_WEEKLY_SUMMARY_MODULE_LABEL) ?></title>
    <script src="https://kit.fontawesome.com/3142eebea3.js" crossorigin="anonymous"></script>
    <?= app_fonts_link() ?>
    <style>
<?php admin_shell_styles(); ?>
<?php readfile(__DIR__ . '/assets/css/dashboard.css'); ?>
<?php readfile(__DIR__ . '/assets/css/reports.css'); ?>
    </style>
</head>
<body class="light-mode page-weekly-activity page-weekly-activity-reports page-activity-registry"
      data-admin-nav="weekly-activity"
      data-open-weekly="<?= e($openId) ?>"
      data-open-mode="<?= e($drawerMode) ?>"
      data-status-tab="<?= e($initialStatusTab) ?>"<?= $openRecord !== null ? ' style="overflow:hidden"' : '' ?>>

<?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <main class="app-main">
        <header class="page-header page-header--inline">
            <h1 class="page-title"><?= e(ADMIN_WEEKLY_SUMMARY_MODULE_LABEL) ?></h1>
            <p class="page-subtitle">Review weekly summaries from head guards — accomplishments, post coverage, and follow-ups.</p>
        </header>

        <div id="weekly-activity-module" class="reports-module"
             data-registry-kind="weekly-activity"
             data-open-param="weekly"
             data-csrf="<?= e_attr(csrf_token()) ?>">
            <section class="kpi-grid" aria-label="<?= e(ADMIN_WEEKLY_SUMMARY_MODULE_LABEL) ?> summary">
                <article class="kpi-card kpi-card--total" title="All weekly summaries">
                    <div class="kpi-stat">
                        <span class="kpi-value" data-kpi="all"><?= (int) $statusCounts['all'] ?></span>
                    </div>
                    <p class="kpi-label">Total summaries</p>
                </article>
                <?php foreach ($statusDefinitions as $slug => $def): ?>
                <article class="kpi-card kpi-card--<?= e($slug) ?>" title="<?= e((string) $def['description']) ?>">
                    <div class="kpi-stat">
                        <span class="kpi-value" data-kpi="<?= e($slug) ?>"><?= (int) ($statusCounts[$slug] ?? 0) ?></span>
                    </div>
                    <p class="kpi-label"><?= e((string) $def['kpi']) ?></p>
                </article>
                <?php endforeach; ?>
            </section>

            <section class="reports-panel" aria-label="<?= e(ADMIN_WEEKLY_SUMMARY_MODULE_LABEL) ?> registry">
                <div class="reports-panel__filters">
                    <div class="reports-toolbar" role="search">
                        <div class="reports-toolbar__fields">
                            <div class="form-field reports-field--search">
                                <label for="activity-search" class="reports-label-with-icon"><?= admin_ui_icon('magnifying-glass', 14) ?> Search</label>
                                <input type="search" id="activity-search" placeholder="Reference, week, head guard, post…" autocomplete="off">
                            </div>
                            <div class="form-field reports-field--date">
                                <label for="activity-date-from">Updated from</label>
                                <input type="date" id="activity-date-from" value="<?= e(date('Y-m-d', strtotime('-60 days'))) ?>">
                            </div>
                            <div class="form-field reports-field--date">
                                <label for="activity-date-to">Updated to</label>
                                <input type="date" id="activity-date-to" value="<?= e(date('Y-m-d')) ?>">
                            </div>
                        </div>
                        <div class="reports-toolbar-actions" role="toolbar" aria-label="<?= e(ADMIN_WEEKLY_SUMMARY_MODULE_LABEL) ?> filter actions">
                            <div class="reports-button-set">
                                <button type="button" class="reports-btn reports-btn--secondary" id="activity-reset">
                                    <span class="reports-btn__text">Reset</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <nav class="reports-status-tabs reports-panel__tabs" role="tablist" aria-label="Filter by status">
                    <?php foreach ($registryStatusTabs as $tab):
                        $tabSlug = (string) $tab['slug'];
                        $isActive = $initialStatusTab === '' ? $tabSlug === 'all' : $initialStatusTab === $tabSlug;
                        ?>
                    <button type="button"
                            class="reports-status-tab<?= $isActive ? ' is-active' : '' ?>"
                            role="tab"
                            aria-selected="<?= $isActive ? 'true' : 'false' ?>"
                            data-status-tab="<?= e($tabSlug) ?>"
                            title="<?= e((string) $tab['title']) ?>">
                        <?= e((string) $tab['label']) ?>
                        <span class="reports-status-tab__count" data-tab-count><?= (int) $tab['count'] ?></span>
                    </button>
                    <?php endforeach; ?>
                </nav>

                <div class="reports-panel__body">
                    <div class="reports-registry" role="region" aria-label="<?= e(ADMIN_WEEKLY_SUMMARY_MODULE_LABEL) ?> table">
                        <div class="reports-table-head-wrap" id="activity-table-head-wrap">
                            <table class="reports-table reports-table--head reports-table--weekly-activity">
                                <colgroup>
                                    <col class="reports-col-ref">
                                    <col class="reports-col-week">
                                    <col class="reports-col-hg">
                                    <col class="reports-col-post">
                                    <col class="reports-col-summary">
                                    <col class="reports-col-submitted">
                                    <col class="reports-col-updated">
                                    <col class="reports-col-status">
                                    <col class="reports-col-actions">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th scope="col"><button type="button" class="reports-sort" data-sort-key="ref"><span class="reports-sort__label">Reference</span></button></th>
                                        <th scope="col"><button type="button" class="reports-sort" data-sort-key="week"><span class="reports-sort__label">Week</span></button></th>
                                        <th scope="col"><button type="button" class="reports-sort" data-sort-key="headGuard"><span class="reports-sort__label">Head guard</span></button></th>
                                        <th scope="col"><button type="button" class="reports-sort" data-sort-key="post"><span class="reports-sort__label">Post</span></button></th>
                                        <th scope="col"><span class="reports-sort__label">Summary</span></th>
                                        <th scope="col"><button type="button" class="reports-sort" data-sort-key="submitted"><span class="reports-sort__label">Submitted</span></button></th>
                                        <th scope="col"><button type="button" class="reports-sort is-active" data-sort-key="updated"><span class="reports-sort__label">Updated</span></button></th>
                                        <th scope="col"><button type="button" class="reports-sort reports-sort--center" data-sort-key="status"><span class="reports-sort__label">Status</span></button></th>
                                        <th scope="col">Actions</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                        <div class="reports-table-body-wrap" id="activity-table-body-wrap" tabindex="0">
                            <table class="reports-table reports-table--body reports-table--weekly-activity">
                                <colgroup>
                                    <col class="reports-col-ref">
                                    <col class="reports-col-week">
                                    <col class="reports-col-hg">
                                    <col class="reports-col-post">
                                    <col class="reports-col-summary">
                                    <col class="reports-col-submitted">
                                    <col class="reports-col-updated">
                                    <col class="reports-col-status">
                                    <col class="reports-col-actions">
                                </colgroup>
                                <tbody id="activity-tbody">
                                <?php foreach ($records as $report): ?>
                                    <tr <?= admin_weekly_activity_row_attrs($report) ?>>
                                        <td class="reports-col-ref"><span class="reports-ref mono"><?= e((string) $report['ref']) ?></span></td>
                                        <td class="reports-col-week mono"><?= e((string) $report['week_label']) ?></td>
                                        <td class="reports-col-hg"><?= e((string) $report['head_guard_name']) ?></td>
                                        <td class="reports-col-post"><?= e((string) $report['site_name']) ?></td>
                                        <td class="reports-col-summary">
                                            <div class="reports-incident-cell" title="<?= e((string) $report['summary']) ?>">
                                                <span class="reports-incident-context"><?= e((string) $report['summary']) ?></span>
                                            </div>
                                        </td>
                                        <td class="reports-col-submitted reports-col-date mono"><?= admin_incident_table_date_cell_html((string) ($report['submitted_at'] ?? ''), (string) ($report['submitted_display'] ?? '')) ?></td>
                                        <td class="reports-col-updated reports-col-date mono"><?= admin_incident_table_date_cell_html((string) ($report['updated_at'] ?? ''), (string) ($report['updated_display'] ?? '')) ?></td>
                                        <td class="reports-col-status"><?= admin_weekly_activity_status_badge_html($report) ?></td>
                                        <td class="reports-col-actions">
                                            <div class="reports-actions" role="group" aria-label="Actions for <?= e((string) $report['ref']) ?>">
                                                <a href="weekly-activity.php?weekly=<?= rawurlencode((string) $report['id']) ?>&amp;mode=view"
                                                   class="reports-action-btn"
                                                   data-action="view"
                                                   data-activity-id="<?= e((string) $report['id']) ?>"
                                                   title="View summary"><?= admin_weekly_activity_action_icon('view') ?></a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div id="activity-empty" class="reports-empty" role="status" aria-live="polite" hidden>
                        <div class="reports-empty__icon" aria-hidden="true"><?= admin_ui_icon('folder-open', 28) ?></div>
                        <p class="reports-empty__title">No summaries match your filters</p>
                        <p class="reports-empty__hint">Adjust the date range or status tab — or clear search.</p>
                    </div>
                </div>

                <footer class="reports-panel__footer">
                    <p class="reports-status-key">
                        <span class="reports-status-key__label">Status key</span>
                        <?php foreach ($statusDefinitions as $slug => $def): ?>
                        <span class="reports-status-key__item" title="<?= e($def['description']) ?>">
                            <span class="reports-status-dot reports-status-dot--<?= e($slug) ?>" aria-hidden="true"></span>
                            <span class="reports-status-key__name"><?= e($def['label']) ?></span>
                        </span>
                        <?php endforeach; ?>
                    </p>
                </footer>
            </section>
        </div>
    </main>

<div id="activity-modal-overlay" class="reports-modal-overlay<?= $openRecord !== null ? ' is-open' : '' ?>"
     role="presentation" aria-hidden="<?= $openRecord !== null ? 'false' : 'true' ?>">
    <div class="reports-modal" id="activity-modal" role="dialog" aria-modal="true" aria-labelledby="activity-modal-title">
        <header class="reports-modal__header">
            <div class="reports-modal__identity">
                <span class="reports-modal__eyebrow"><?= e(ADMIN_WEEKLY_SUMMARY_MODULE_LABEL) ?></span>
                <div class="reports-modal__title-row">
                    <h2 id="activity-modal-title" class="reports-modal__ref">
                        <span id="activity-modal-ref"><?= $openRecord ? e((string) $openRecord['ref']) : '—' ?></span>
                    </h2>
                    <div id="activity-modal-status-badge" class="reports-modal__status">
                        <?= $openRecord ? admin_weekly_activity_status_badge_html($openRecord) : '<span class="reports-badge">—</span>' ?>
                    </div>
                </div>
            </div>
            <button type="button" class="reports-modal__close" id="activity-modal-close" aria-label="Close dialog">&times;</button>
        </header>
        <div class="reports-modal__content">
            <div class="reports-modal__body-scroll">
                <div id="activity-modal-view" class="reports-modal-panel is-active">
                    <div id="activity-modal-details">
                        <?php if ($openRecord): ?>
                            <?= admin_weekly_activity_modal_details_html($openRecord) ?>
                        <?php endif; ?>
                    </div>
                    <section class="reports-modal-form__section reports-modal__history" aria-label="Weekly history">
                        <h3 class="reports-modal-form__section-title">History</h3>
                        <ol id="activity-modal-history" class="reports-timeline">
                            <?php if ($openRecord):
                                foreach (is_array($openRecord['history'] ?? null) ? $openRecord['history'] : [] as $entry): ?>
                            <li class="reports-timeline__item">
                                <span class="reports-timeline__time"><?= e((string) ($entry['at'] ?? '')) ?></span>
                                <strong><?= e((string) ($entry['event'] ?? '')) ?></strong>
                                <?php if (trim((string) ($entry['note'] ?? '')) !== ''): ?>
                                <p><?= e((string) $entry['note']) ?></p>
                                <?php endif; ?>
                            </li>
                            <?php endforeach;
                            endif; ?>
                        </ol>
                    </section>
                </div>
                <form method="POST" id="activity-edit-form" class="reports-edit-form"<?= $drawerMode === 'edit' && $openRecord ? '' : ' hidden' ?>>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_weekly">
                    <input type="hidden" name="weekly_id" id="activity-edit-id" value="<?= $openRecord ? e((string) $openRecord['id']) : '' ?>">
                    <div class="form-field">
                        <label for="activity-edit-status">Review status</label>
                        <select name="status" id="activity-edit-status">
                            <?php foreach (admin_weekly_activity_status_options() as $val => $label): ?>
                            <option value="<?= e($val) ?>"<?= $openRecord && (string) $openRecord['status'] === $val ? ' selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <footer class="reports-modal__footer">
                <div class="reports-button-set" id="activity-modal-footer-view"<?= $drawerMode === 'edit' ? ' hidden' : '' ?>>
                    <button type="button" class="reports-btn reports-btn--primary" id="activity-goto-edit"<?= $openRecord ? '' : ' hidden' ?>>
                        <?= admin_btn_icon('pen-to-square') ?>
                        <span class="reports-btn__text">Update status</span>
                    </button>
                </div>
                <div class="reports-button-set" id="activity-modal-footer-edit"<?= $drawerMode === 'view' ? ' hidden' : '' ?>>
                    <button type="submit" class="reports-btn reports-btn--primary" form="activity-edit-form">
                        <?= admin_btn_icon('floppy-disk') ?>
                        <span class="reports-btn__text">Save</span>
                    </button>
                    <button type="button" class="reports-btn reports-btn--secondary" id="activity-cancel-edit">Cancel</button>
                </div>
            </footer>
        </div>
    </div>
</div>

<script type="application/json" id="activity-data-json"><?=
    json_encode($records, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT)
?></script>
<script type="application/json" id="activity-status-labels"><?=
    json_encode(admin_weekly_activity_status_options(), JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT)
?></script>

<?php admin_shell_scripts(); ?>

<?php require_once __DIR__ . '/../includes/global-alerts.php'; ?>
</body>
</html>
