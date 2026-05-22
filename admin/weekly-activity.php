<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once APP_ROOT . '/includes/admin_weekly_activity_reports.php';
require_once APP_ROOT . '/includes/admin_activity_registry_ui.php';

auth_require_permission('admin.reports.view');

$actorId = (string) ($_SESSION['company_id'] ?? 'admin');
$openId = trim((string) ($_GET['weekly'] ?? ''));

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();
    $action = trim((string) ($_POST['action'] ?? ''));

    if ($action === 'delete_weekly') {
        $id = trim((string) ($_POST['weekly_id'] ?? ''));
        $deleted = admin_weekly_activity_delete($id);
        $wantsJson = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

        if ($deleted === null) {
            if ($wantsJson) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(404);
                echo json_encode(['ok' => false, 'error' => ADMIN_WEEKLY_SUMMARY_MODULE_LABEL . ' not found.'], JSON_THROW_ON_ERROR);
                exit;
            }
            redirect_with_alert(ADMIN_WEEKLY_SUMMARY_MODULE_LABEL . ' not found.', 'weekly-activity.php');
        }

        $ref = (string) ($deleted['ref'] ?? $id);
        if ($wantsJson) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => true,
                'message' => ADMIN_WEEKLY_SUMMARY_MODULE_LABEL . ' ' . $ref . ' deleted.',
                'id' => $id,
            ], JSON_THROW_ON_ERROR);
            exit;
        }

        redirect_with_alert(ADMIN_WEEKLY_SUMMARY_MODULE_LABEL . ' ' . $ref . ' deleted.', 'weekly-activity.php');
    }

    if ($action === 'generate_war') {
        $result = admin_weekly_activity_generate_war($_POST, $actorId);
        if (!$result['ok']) {
            redirect_with_alert((string) ($result['error'] ?? 'Could not generate WAR.'), 'weekly-activity.php');
        }

        $report = $result['report'] ?? null;
        $id = is_array($report) ? (string) ($report['id'] ?? '') : '';
        $ref = is_array($report) ? (string) ($report['ref'] ?? $id) : $id;

        redirect_with_alert(
            'WAR ' . $ref . ' generated from daily activity for the selected week.',
            $id !== '' ? 'weekly-activity.php?weekly=' . rawurlencode($id) : 'weekly-activity.php'
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

$warGenerateOptions = admin_weekly_activity_generate_assignment_options();
$warDefaultWeekStart = admin_weekly_activity_default_week_start();
$warDefaultWeekEnd = admin_weekly_activity_default_week_end();

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
<body class="light-mode page-weekly-activity page-weekly-activity-reports page-activity-registry<?= $openRecord !== null ? ' activity-registry-modal-open' : '' ?>"
      data-admin-nav="weekly-activity"
      data-open-weekly="<?= e($openId) ?>"<?= $openRecord !== null ? ' style="overflow:hidden"' : '' ?>>
<?php admin_theme_body_boot(); ?>

<?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <main class="app-main">
        <header class="page-header page-header--inline">
            <h1 class="page-title"><?= e(ADMIN_WEEKLY_SUMMARY_MODULE_LABEL) ?></h1>
            <p class="page-subtitle">Review weekly summaries from head guards — accomplishments, post coverage, and follow-ups.</p>
        </header>

        <div id="weekly-activity-module" class="reports-module"
             data-registry-kind="weekly-activity"
             data-open-param="weekly"
             data-csrf="<?= e_attr(csrf_token()) ?>"
             data-war-preview-url="<?= e_attr(app_url('admin/api/weekly-war-preview.php')) ?>">
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

            <?= admin_weekly_activity_generate_panel_html($warGenerateOptions, $warDefaultWeekStart, $warDefaultWeekEnd) ?>

            <section class="reports-panel" aria-label="<?= e(ADMIN_WEEKLY_SUMMARY_MODULE_LABEL) ?> registry">
                <div class="reports-panel__filters">
                    <div class="reports-toolbar" role="search">
                        <div class="reports-toolbar__fields">
                            <div class="form-field reports-field--search">
                                <label for="activity-search" class="reports-label-with-icon"><?= admin_ui_icon('magnifying-glass', 14) ?> Search</label>
                                <input type="search" id="activity-search" placeholder="Reference, week, head guard, post…" autocomplete="off">
                            </div>
                            <div class="form-field reports-field--date">
                                <label for="activity-date-from">Submitted from</label>
                                <input type="date" id="activity-date-from" value="<?= e(date('Y-m-d', strtotime('-60 days'))) ?>">
                            </div>
                            <div class="form-field reports-field--date">
                                <label for="activity-date-to">Submitted to</label>
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
                                    <col class="reports-col-actions">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th scope="col"><button type="button" class="reports-sort" data-sort-key="ref"><span class="reports-sort__label">Reference</span></button></th>
                                        <th scope="col"><button type="button" class="reports-sort" data-sort-key="week"><span class="reports-sort__label">Week</span></button></th>
                                        <th scope="col"><button type="button" class="reports-sort" data-sort-key="headGuard"><span class="reports-sort__label">Head guard</span></button></th>
                                        <th scope="col"><button type="button" class="reports-sort" data-sort-key="post"><span class="reports-sort__label">Post</span></button></th>
                                        <th scope="col"><span class="reports-sort__label">Summary</span></th>
                                        <th scope="col"><button type="button" class="reports-sort is-active" data-sort-key="submitted"><span class="reports-sort__label">Submitted</span></button></th>
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
                                        <td class="reports-col-actions">
                                            <div class="reports-actions" role="group" aria-label="Actions for <?= e((string) $report['ref']) ?>">
                                                <button type="button"
                                                        class="reports-action-btn"
                                                        data-action="view"
                                                        data-activity-id="<?= e((string) $report['id']) ?>"
                                                        title="View summary"
                                                        aria-label="View summary <?= e((string) $report['ref']) ?>"><?= admin_weekly_activity_action_icon('view') ?></button>
                                                <button type="button"
                                                        class="reports-action-btn"
                                                        data-action="print"
                                                        data-activity-id="<?= e((string) $report['id']) ?>"
                                                        title="Print summary"
                                                        aria-label="Print <?= e((string) $report['ref']) ?>"><?= admin_weekly_activity_action_icon('print') ?></button>
                                                <button type="button"
                                                        class="reports-action-btn reports-action-btn--danger"
                                                        data-action="delete"
                                                        data-activity-id="<?= e((string) $report['id']) ?>"
                                                        title="Delete summary"
                                                        aria-label="Delete <?= e((string) $report['ref']) ?>"><?= admin_weekly_activity_action_icon('delete') ?></button>
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
                        <p class="reports-empty__hint">Adjust the date range or clear search.</p>
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
            <script type="application/json" id="activity-data-json"><?=
                json_encode($records, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT)
            ?></script>
            <script type="application/json" id="activity-status-labels"><?=
                json_encode(admin_weekly_activity_status_options(), JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT)
            ?></script>
        </div>
    </main>
</div>

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
            <main class="reports-modal__body-scroll" id="activity-modal-main" aria-label="Weekly summary report details">
                <div class="reports-modal-form">
                    <div id="activity-modal-view"
                         class="reports-modal-panel reports-modal-form__section reports-modal-form__section--wide is-active">
                        <div id="activity-modal-details" class="reports-modal-view-details">
                            <?php if ($openRecord): ?>
                                <?= admin_weekly_activity_modal_details_html($openRecord) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>

<?php admin_shell_scripts(); ?>
<?php
$weeklyWarJs = __DIR__ . '/assets/js/weekly-war-generate.js';
if (is_readable($weeklyWarJs)) {
    echo '<script src="' . e(app_url('admin/assets/js/weekly-war-generate.js'))
        . '?v=' . (int) filemtime($weeklyWarJs) . '" defer></script>';
}
?>

<?php require_once __DIR__ . '/../includes/global-alerts.php'; ?>
</body>
</html>
