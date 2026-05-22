<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once APP_ROOT . '/includes/admin_incident_reports.php';

auth_require_permission('admin.reports.view');

$actorId = (string) ($_SESSION['company_id'] ?? 'admin');
$openIncidentId = trim((string) ($_GET['incident'] ?? ''));
$drawerMode = trim((string) ($_GET['mode'] ?? 'view'));
if (!in_array($drawerMode, ['view', 'edit'], true)) {
    $drawerMode = 'view';
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();
    $action = trim((string) ($_POST['action'] ?? ''));

    if ($action === 'reset_demo') {
        admin_incident_store_reset();
        redirect_with_alert('Demo incident data has been reset to defaults.', 'reports.php');
    }

    if ($action === 'update_incident') {
        $id = trim((string) ($_POST['incident_id'] ?? ''));
        $updated = admin_incident_update($id, [
            'category' => (string) ($_POST['category'] ?? ''),
            'status' => (string) ($_POST['status'] ?? ''),
            'incident_type' => (string) ($_POST['incident_type'] ?? ''),
            'site' => (string) ($_POST['site'] ?? ''),
            'severity' => (string) ($_POST['severity'] ?? ''),
            'summary' => (string) ($_POST['summary'] ?? ''),
            'ops_note' => (string) ($_POST['ops_note'] ?? ''),
        ], $actorId);

        if ($updated === null) {
            redirect_with_alert('Incident report not found.', 'reports.php');
        }

        $ref = (string) ($updated['ref'] ?? $id);
        redirect_with_alert(
            'Incident ' . $ref . ' saved. Status history updated.',
            'reports.php?incident=' . rawurlencode($id) . '&mode=view'
        );
    }
}

if (isset($_GET['export']) && (string) $_GET['export'] === '1') {
    $exportIncidentId = trim((string) ($_GET['incident'] ?? ''));
    if ($exportIncidentId !== '') {
        $exportReport = admin_incident_find($exportIncidentId);
        if ($exportReport !== null) {
            admin_incident_export_csv([$exportReport], (string) ($exportReport['ref'] ?? $exportIncidentId));
        }
        redirect_with_alert('Incident report not found.', 'reports.php');
    }
    admin_incident_export_csv(admin_incident_store_all());
}

$incidentReports = admin_incident_store_all();
$statusCounts = admin_incident_status_counts($incidentReports);
$openIncident = $openIncidentId !== '' ? admin_incident_find($openIncidentId) : null;
if ($openIncidentId !== '' && $openIncident === null) {
    $openIncidentId = '';
}

$statusOptions = admin_incident_status_options();
$statusDefinitions = admin_incident_status_definitions();
/** @var list<array{slug: string, label: string, count: int, title: string}> */
$registryStatusTabs = [
    [
        'slug' => 'all',
        'label' => 'All',
        'count' => (int) $statusCounts['all'],
        'title' => 'Every incident in the registry',
    ],
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
$validStatusTabs = ['all', ...admin_incident_status_slugs()];
$initialStatusTab = in_array($statusTabFromQuery, $validStatusTabs, true) ? $statusTabFromQuery : '';
$sanctionsReference = admin_incident_sanctions_reference();
$workflowTypeCount = count($sanctionsReference);
$guideReferenceSectionCount = admin_incident_guard_operations_guide_section_count();
$guideSearchItemCount = $workflowTypeCount + $guideReferenceSectionCount;
$adminNavActive = 'reports';

/**
 * @param array<string, mixed> $report
 */
function admin_reports_row_attrs(array $report): string
{
    $detailJson = json_encode($report, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);

    $headGuardSort = (string) ($report['head_guard_name'] ?? $report['submitter_name'] ?? '');

    return implode(' ', [
        'data-report-row',
        'data-id="' . e((string) $report['id']) . '"',
        'data-ref="' . e((string) $report['ref']) . '"',
        'data-category="' . e((string) $report['category']) . '"',
        'data-status="' . e((string) $report['status']) . '"',
        'data-submitted-at="' . e((string) $report['submitted_at']) . '"',
        'data-updated-at="' . e(substr((string) ($report['updated_at'] ?? $report['submitted_at'] ?? ''), 0, 10)) . '"',
        'data-sort-incident="' . e((string) $report['incident_type']) . '"',
        'data-sort-severity="' . e((string) ($report['severity'] ?? '')) . '"',
        'data-sort-hg="' . e($headGuardSort) . '"',
        'data-search="' . e(admin_incident_search_blob($report)) . '"',
        'data-detail="' . e($detailJson) . '"',
    ]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?= mobile_meta_tags() ?>
    <title><?= e(app_agency_name()) ?> | Incident Reports</title>
    <script src="https://kit.fontawesome.com/3142eebea3.js" crossorigin="anonymous"></script>
    <?= app_fonts_link() ?>
    <style>
<?php admin_shell_styles(); ?>
<?php readfile(__DIR__ . '/assets/css/dashboard.css'); ?>
<?php readfile(__DIR__ . '/assets/css/reports.css'); ?>
    </style>
</head>
<body class="light-mode page-incident-reports"
      data-open-incident="<?= e($openIncidentId) ?>"
      data-open-mode="<?= e($drawerMode) ?>"
      data-status-tab="<?= e($initialStatusTab) ?>">

<?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <main class="app-main">
            <header class="page-header page-header--inline">
                <h1 class="page-title">Incident Reports</h1>
                <p class="page-subtitle">Monitor and archive security guard incident reports — on-post duty, client sites, and guard conduct — with full status history.</p>
        </header>

            <div id="reports-module" class="reports-module">
                <?php
                $reportKpiIcons = [
                    'all' => 'clipboard-list',
                    'ongoing' => 'folder-open',
                    'on_hold' => 'clock',
                    'accomplished' => 'circle-check',
                    'denied' => 'ban',
                ];
                ?>
                <section class="kpi-grid" aria-label="Report summary">
                    <article class="kpi-card kpi-card--total" title="All incident reports in the registry">
                        <div class="kpi-stat">
                            <?= admin_kpi_icon($reportKpiIcons['all']) ?>
                            <span class="kpi-value" data-kpi="all"><?= (int) $statusCounts['all'] ?></span>
                        </div>
                        <p class="kpi-label">Total reports</p>
                    </article>
                    <?php foreach ($statusDefinitions as $slug => $def):
                        $icon = $reportKpiIcons[$slug] ?? 'file-lines';
                        ?>
                    <article class="kpi-card kpi-card--<?= e($slug) ?>" title="<?= e((string) $def['description']) ?>">
                        <div class="kpi-stat">
                            <?= admin_kpi_icon($icon) ?>
                            <span class="kpi-value" data-kpi="<?= e($slug) ?>"><?= (int) ($statusCounts[$slug] ?? 0) ?></span>
                        </div>
                        <p class="kpi-label"><?= e((string) $def['kpi']) ?></p>
                    </article>
                    <?php endforeach; ?>
                </section>

                <section class="reports-panel" aria-label="Incident reports registry">
                    <div class="reports-panel__filters">
                        <div class="reports-toolbar" role="search">
                            <div class="reports-toolbar__fields">
                                <div class="form-field reports-field--search">
                                    <label for="reports-search" class="reports-label-with-icon"><?= admin_ui_icon('magnifying-glass', 14) ?> Search</label>
                                    <input type="search" id="reports-search" placeholder="Reference, head guard, post, summary…" autocomplete="off">
                                </div>
                                <div class="form-field reports-field--category">
                                    <label for="reports-category">Report scope</label>
                                    <select id="reports-category">
                                        <option value="all">All scopes</option>
                                        <?php foreach (admin_incident_category_options() as $slug => $label): ?>
                                        <option value="<?= e($slug) ?>"><?= e($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-field reports-field--date">
                                    <label for="reports-date-from">Submitted from</label>
                                    <input type="date" id="reports-date-from" value="2026-04-01">
                                </div>
                                <div class="form-field reports-field--date">
                                    <label for="reports-date-to">Submitted to</label>
                                    <input type="date" id="reports-date-to" value="<?= e(date('Y-m-d')) ?>">
                                </div>
                            </div>
                            <div class="reports-toolbar-actions" role="toolbar" aria-label="Report filter actions">
                                <div class="reports-button-set">
                                    <button type="button" class="reports-btn reports-btn--secondary" id="reports-reset">
                                        <?= admin_btn_icon('rotate-left') ?>
                                        <span class="reports-btn__text">Reset</span>
                                    </button>
                                    <a href="reports.php?export=1" class="reports-btn reports-btn--primary" id="reports-export">
                                        <?= admin_btn_icon('file-export') ?>
                                        <span class="reports-btn__text">Export</span>
                                    </a>
                                    <button type="button" class="reports-btn reports-btn--secondary" id="reports-sanctions-open" title="Guard guide — workflow and status reference">
                                        <?= admin_btn_icon('book-open') ?>
                                        <span class="reports-btn__text">Guard guide</span>
                                    </button>
                                </div>
                            </div>
                    </div>
                    </div>

                    <nav class="reports-status-tabs reports-panel__tabs" role="tablist" aria-label="Filter reports by status">
                        <?php foreach ($registryStatusTabs as $tab): ?>
                    <?php
                            $tabSlug = (string) $tab['slug'];
                            $isActive = $initialStatusTab === ''
                                ? $tabSlug === 'all'
                                : $initialStatusTab === $tabSlug;
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
                        <div class="reports-registry" role="region" aria-label="Incident reports table">
                            <div class="reports-table-head-wrap" id="reports-table-head-wrap">
                                <table class="reports-table reports-table--head">
                                    <colgroup>
                                        <col class="reports-col-ref">
                                        <col class="reports-col-cat">
                                        <col class="reports-col-incident">
                                        <col class="reports-col-severity">
                                        <col class="reports-col-hg">
                                        <col class="reports-col-submitted">
                                        <col class="reports-col-updated">
                                        <col class="reports-col-status">
                                        <col class="reports-col-actions">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th scope="col" class="reports-col-ref" aria-sort="none">
                                                <button type="button" class="reports-sort" data-sort-key="ref">
                                                    <span class="reports-sort__label">Reference</span>
                                                    <span class="reports-sort__icon reports-sort__icon--idle" aria-hidden="true"></span>
                                                </button>
                                            </th>
                                            <th scope="col" class="reports-col-cat" aria-sort="none">
                                                <button type="button" class="reports-sort reports-sort--center" data-sort-key="category">
                                                    <span class="reports-sort__label">Scope</span>
                                                    <span class="reports-sort__icon reports-sort__icon--idle" aria-hidden="true"></span>
                                                </button>
                                            </th>
                                            <th scope="col" class="reports-col-incident" aria-sort="none">
                                                <button type="button" class="reports-sort" data-sort-key="incident">
                                                    <span class="reports-sort__label">Incident</span>
                                                    <span class="reports-sort__icon reports-sort__icon--idle" aria-hidden="true"></span>
                                                </button>
                                            </th>
                                            <th scope="col" class="reports-col-severity" aria-sort="none">
                                                <button type="button" class="reports-sort reports-sort--center" data-sort-key="severity">
                                                    <span class="reports-sort__label">Severity</span>
                                                    <span class="reports-sort__icon reports-sort__icon--idle" aria-hidden="true"></span>
                                                </button>
                                            </th>
                                            <th scope="col" class="reports-col-hg" aria-sort="none">
                                                <button type="button" class="reports-sort" data-sort-key="headGuard">
                                                    <span class="reports-sort__label">Head guard</span>
                                                    <span class="reports-sort__icon reports-sort__icon--idle" aria-hidden="true"></span>
                                                </button>
                                            </th>
                                            <th scope="col" class="reports-col-submitted" aria-sort="descending">
                                                <button type="button" class="reports-sort is-active" data-sort-key="submitted" title="Submitted — sorted descending (newest first)" aria-label="Submitted, sorted descending">
                                                    <span class="reports-sort__label">Submitted</span>
                                                    <span class="reports-sort__icon reports-sort__icon--desc" aria-hidden="true"></span>
                                                </button>
                                            </th>
                                            <th scope="col" class="reports-col-updated" aria-sort="none">
                                                <button type="button" class="reports-sort" data-sort-key="updated">
                                                    <span class="reports-sort__label">Updated</span>
                                                    <span class="reports-sort__icon reports-sort__icon--idle" aria-hidden="true"></span>
                                                </button>
                                            </th>
                                            <th scope="col" class="reports-col-status" aria-sort="none">
                                                <button type="button" class="reports-sort reports-sort--center" data-sort-key="status">
                                                    <span class="reports-sort__label">Status</span>
                                                    <span class="reports-sort__icon reports-sort__icon--idle" aria-hidden="true"></span>
                                                </button>
                                            </th>
                                            <th scope="col" class="reports-col-actions">Actions</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                            <div class="reports-table-body-wrap" id="reports-table-body-wrap" tabindex="0">
                                <table class="reports-table reports-table--body">
                                    <colgroup>
                                        <col class="reports-col-ref">
                                        <col class="reports-col-cat">
                                        <col class="reports-col-incident">
                                        <col class="reports-col-severity">
                                        <col class="reports-col-hg">
                                        <col class="reports-col-submitted">
                                        <col class="reports-col-updated">
                                        <col class="reports-col-status">
                                        <col class="reports-col-actions">
                                    </colgroup>
                                    <tbody id="reports-tbody">
                                    <?php foreach ($incidentReports as $report): ?>
                                    <tr <?= admin_reports_row_attrs($report) ?>>
                                        <td class="reports-col-ref"><span class="reports-ref mono"><?= e((string) $report['ref']) ?></span></td>
                                        <td class="reports-col-cat">
                                            <span class="reports-badge reports-badge--<?= e((string) $report['category']) ?>">
                                                <?= e((string) $report['category_label']) ?>
                                            </span>
                                        </td>
                                        <td class="reports-col-incident">
                                            <div class="reports-incident-cell" title="<?= e((string) $report['incident_type'] . ' — ' . $report['summary']) ?>">
                                                <span class="reports-incident-title"><?= e((string) $report['incident_type']) ?></span>
                                                <span class="reports-incident-context"><?= e((string) $report['summary']) ?></span>
                                            </div>
                                        </td>
                                        <td class="reports-col-severity"><?= admin_incident_severity_badge_html($report) ?></td>
                                        <td class="reports-col-hg"><?= admin_incident_head_guard_cell_html($report) ?></td>
                                        <td class="reports-col-submitted reports-col-date mono"
                                            title="<?= e((string) $report['submitted_display']) ?>"><?= admin_incident_table_date_cell_html((string) ($report['submitted_at'] ?? ''), (string) ($report['submitted_display'] ?? '')) ?></td>
                                        <td class="reports-col-updated reports-col-date mono"
                                            title="<?= e((string) ($report['updated_display'] ?? '—')) ?>"><?= admin_incident_table_date_cell_html((string) ($report['updated_at'] ?? ''), (string) ($report['updated_display'] ?? '')) ?></td>
                                        <td class="reports-col-status"><?= admin_incident_status_badge_html($report) ?></td>
                                        <td class="reports-col-actions">
                                            <div class="reports-actions" role="group" aria-label="Actions for <?= e((string) $report['ref']) ?>">
                                                <a href="reports.php?incident=<?= rawurlencode((string) $report['id']) ?>&amp;mode=view"
                                                   class="reports-action-btn"
                                                   data-action="view"
                                                   data-incident-id="<?= e((string) $report['id']) ?>"
                                                   title="View report"
                                                   aria-label="View <?= e((string) $report['ref']) ?>">
                                                    <?= admin_incident_action_icon('view') ?>
                                                </a>
                                                <a href="reports.php?incident=<?= rawurlencode((string) $report['id']) ?>&amp;mode=edit"
                                                   class="reports-action-btn reports-action-btn--primary"
                                                   data-action="edit"
                                                   data-incident-id="<?= e((string) $report['id']) ?>"
                                                   title="Edit report"
                                                   aria-label="Edit <?= e((string) $report['ref']) ?>">
                                                    <?= admin_incident_action_icon('edit') ?>
                                                </a>
                                                <button type="button"
                                                        class="reports-action-btn"
                                                        data-action="print"
                                                        data-incident-id="<?= e((string) $report['id']) ?>"
                                                        title="Print report"
                                                        aria-label="Print <?= e((string) $report['ref']) ?>">
                                                    <?= admin_incident_action_icon('print') ?>
                                                </button>
                                                <a href="reports.php?export=1&amp;incident=<?= rawurlencode((string) $report['id']) ?>"
                                                   class="reports-action-btn"
                                                   data-action="export"
                                                   title="Export CSV"
                                                   aria-label="Export <?= e((string) $report['ref']) ?>">
                                                    <?= admin_incident_action_icon('download') ?>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div id="reports-empty" class="reports-empty" role="status" aria-live="polite">
                            <div class="reports-empty__icon" aria-hidden="true"><?= admin_ui_icon('folder-open', 28) ?></div>
                            <p class="reports-empty__title">No reports match your filters</p>
                            <p class="reports-empty__hint">Try adjusting the date range, category, or status tab — or clear search to see the full archive.</p>
                        </div>
                    </div>

                    <footer class="reports-panel__footer">
                        <p class="reports-status-key" id="reports-status-key">
                            <span class="reports-status-key__label">Status key</span>
                            <?php foreach ($statusDefinitions as $slug => $def): ?>
                            <span class="reports-status-key__item" title="<?= e($def['description']) ?>">
                                <span class="reports-status-dot reports-status-dot--<?= e($slug) ?>" aria-hidden="true"></span>
                                <span class="reports-status-key__name"><?= e($def['label']) ?></span><span class="reports-status-key__desc"> — <?= e($def['description']) ?></span>
                            </span>
                            <?php endforeach; ?>
                        </p>
                    </footer>
                </section>
        </div>
    </main>
</div>

<div id="reports-modal-overlay" class="reports-modal-overlay<?= $openIncident !== null ? ' is-open' : '' ?>"
     role="presentation" aria-hidden="<?= $openIncident !== null ? 'false' : 'true' ?>">
    <div class="reports-modal" id="reports-modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
        <header class="reports-modal__header">
            <div class="reports-modal__identity">
                <span class="reports-modal__eyebrow">Incident report</span>
                <div class="reports-modal__title-row">
                    <h2 id="modal-title" class="reports-modal__ref">
                        <span id="modal-ref"><?= $openIncident ? e((string) $openIncident['ref']) : '—' ?></span>
                    </h2>
                    <div id="modal-status-badge-wrap" class="reports-modal__status">
                        <?= $openIncident ? admin_incident_status_badge_html($openIncident) : '<span class="reports-badge">—</span>' ?>
                    </div>
        </div>
            </div>
            <button type="button" class="reports-modal__close" id="reports-modal-close" aria-label="Close dialog">&times;</button>
        </header>

        <div class="reports-modal__content">
            <div class="reports-modal__body-scroll">
                <div class="reports-modal-form">
                    <div class="reports-modal-form__blocks">
                    <div id="modal-panel-view" class="reports-modal-panel reports-modal-form__section reports-modal-form__section--wide<?= $drawerMode === 'view' ? ' is-active' : '' ?>"<?= $drawerMode === 'view' ? '' : ' hidden' ?>>
                        <div class="reports-detail-groups reports-detail-groups--modal" id="modal-view-details">
                            <?php if ($openIncident): ?>
                            <?= admin_incident_modal_details_html($openIncident) ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div id="modal-panel-edit" class="reports-modal-panel reports-modal-form__section reports-modal-form__section--wide<?= $drawerMode === 'edit' ? ' is-active' : '' ?>"<?= $drawerMode === 'edit' ? '' : ' hidden' ?>>
                        <header class="reports-modal-form__section-header">
                            <h3 id="modal-edit-heading" class="reports-modal-form__section-title">Edit report</h3>
                        </header>
                        <form method="POST" class="reports-edit-form" id="reports-edit-form"<?= $openIncident === null ? ' hidden' : '' ?>>
                <?= csrf_field() ?>
                            <input type="hidden" name="action" value="update_incident">
                            <input type="hidden" name="incident_id" id="edit-incident-id" value="<?= $openIncident ? e((string) $openIncident['id']) : '' ?>">

                            <div class="reports-form-fields">
                                <div class="reports-form-group" role="group" aria-label="Status and classification">
                                    <div class="reports-form-row">
                                        <div class="reports-form-field">
                                            <label for="edit-status">Status</label>
                                            <select id="edit-status" name="status" required aria-describedby="edit-status-hint">
                                                <?php foreach ($statusDefinitions as $val => $def): ?>
                                                <option value="<?= e($val) ?>" title="<?= e($def['description']) ?>"><?= e($def['label']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <p id="edit-status-hint" class="reports-field-hint"><?= e(admin_incident_status_edit_hint()) ?></p>
                                        </div>
                                        <div class="reports-form-field">
                                            <label for="edit-category">Report scope</label>
                                            <select id="edit-category" name="category" required>
                                                <?php foreach (admin_incident_category_options() as $slug => $label): ?>
                                                <option value="<?= e($slug) ?>" title="<?= e(admin_incident_category_description($slug)) ?>"><?= e($label) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="reports-form-row">
                                        <div class="reports-form-field">
                                            <label for="edit-severity">Severity</label>
                                            <select id="edit-severity" name="severity" required>
                                                <?php foreach (admin_incident_severity_levels() as $level): ?>
                                                <option value="<?= e($level) ?>"><?= e($level) ?></option>
                                                <?php endforeach; ?>
                    </select>
                                        </div>
                                        <div class="reports-form-field">
                                            <label for="edit-site">Post</label>
                                            <input type="text" id="edit-site" name="site" required maxlength="200" value="">
                                        </div>
                                    </div>
                                </div>
                                <div class="reports-form-group" role="group" aria-label="Incident description">
                                    <div class="reports-form-field">
                                        <label for="edit-incident-type">Incident title</label>
                                        <input type="text" id="edit-incident-type" name="incident_type" required maxlength="200" value="">
                                    </div>
                                    <div class="reports-form-field">
                                        <label for="edit-summary">Report context / summary</label>
                                        <textarea id="edit-summary" name="summary" rows="4" required maxlength="2000"></textarea>
                                    </div>
                                </div>
                                <div class="reports-form-group" role="group" aria-label="Operations note">
                                    <div class="reports-form-field">
                                        <label for="edit-ops-note">Operations note <span class="reports-optional">(appended to history)</span></label>
                                        <textarea id="edit-ops-note" name="ops_note" rows="3" maxlength="1000" placeholder="Reason for status change, follow-up, or archive note…"></textarea>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <p class="reports-modal-placeholder" id="modal-edit-placeholder"<?= $openIncident !== null ? ' hidden' : '' ?>>Select a report from the table to edit.</p>
                    </div>
                    </div>

                    <hr class="reports-modal-form__separator" aria-hidden="true">

                    <section class="reports-modal-form__section reports-modal-form__section--wide reports-modal__history" aria-labelledby="modal-history-heading">
                        <header class="reports-modal-form__section-header reports-modal__history-intro">
                            <h3 id="modal-history-heading" class="reports-modal-form__section-title">
                                <?= e(admin_incident_timeline_section_title()) ?>
                            </h3>
                            <?php if (admin_incident_timeline_section_description() !== ''): ?>
                            <p class="reports-modal-form__section-desc reports-modal__history-lead"><?= e(admin_incident_timeline_section_description()) ?></p>
                            <?php endif; ?>
                        </header>
                        <div id="modal-stepper" class="reports-timeline-host" role="region" aria-label="<?= e(admin_incident_timeline_section_title()) ?>">
                            <?php if ($openIncident): ?>
                                <?= admin_incident_history_stepper_html(
                                    is_array($openIncident['history'] ?? null) ? $openIncident['history'] : [],
                                    $openIncident
                                ) ?>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
            </div>

            <footer class="reports-modal__footer">
                <div class="reports-modal-footer__button-set" id="reports-modal-footer-view"<?= $drawerMode === 'edit' ? ' hidden' : '' ?>>
                    <div class="reports-button-set">
                        <button type="button" class="reports-btn reports-btn--primary" id="modal-goto-edit"<?= $openIncident ? '' : ' hidden' ?>>
                            <?= admin_btn_icon('pen-to-square') ?>
                            <span class="reports-btn__text">Edit this report</span>
                        </button>
                    </div>
                </div>
                <div class="reports-modal-footer__button-set" id="reports-modal-footer-edit"<?= $drawerMode === 'view' ? ' hidden' : '' ?>>
                    <div class="reports-button-set">
                        <button type="submit" class="reports-btn reports-btn--primary" form="reports-edit-form" id="modal-save-edit">
                            <?= admin_btn_icon('floppy-disk') ?>
                            <span class="reports-btn__text">Save changes</span>
                        </button>
                        <button type="button" class="reports-btn reports-btn--secondary" id="modal-cancel-edit">
                            <span class="reports-btn__text">Cancel</span>
                        </button>
                    </div>
                </div>
            </footer>
        </div>
    </div>
</div>

<div id="reports-sanctions-overlay" class="reports-modal-overlay reports-sanctions-overlay"
     role="presentation" aria-hidden="true">
    <div class="reports-modal reports-sanctions-modal reports-guide--simple" id="reports-sanctions-modal" role="dialog"
         aria-modal="true" aria-labelledby="sanctions-modal-title">
        <header class="reports-modal__header">
            <div class="reports-modal__identity">
                <span class="reports-modal__eyebrow">Incident report</span>
                <div class="reports-modal__title-row">
                    <h2 id="sanctions-modal-title" class="reports-modal__ref">Guard guide</h2>
                </div>
            </div>
            <button type="button" class="reports-modal__close" id="reports-sanctions-close" aria-label="Close guard guide">&times;</button>
        </header>

        <div class="reports-modal__content">
            <div class="reports-sanctions-modal__toolbar">
                <div class="reports-guide-filters" id="reports-guide-filters" data-guide-filters-mode="search">
                    <label for="guide-filter-search" class="reports-guide-filters__search-label">Search</label>
                    <input type="search" id="guide-filter-search" class="reports-guide-filters__search-input"
                           placeholder="Search workflow or rules…" autocomplete="off"
                           aria-describedby="guide-filter-count">
                    <button type="button" class="reports-btn reports-btn--secondary reports-btn--sm" id="guide-filter-reset">Reset</button>
                    <p id="guide-filter-count" class="reports-guide-filters__count" aria-live="polite">
                        <span id="guide-filter-count-visible"><?= (int) $guideSearchItemCount ?></span>
                        <span id="guide-filter-count-suffix"> topics</span>
                    </p>
                </div>
            </div>

            <div class="reports-modal__body-scroll">
                <div class="reports-modal-form reports-sanctions-modal__form">
                    <div class="reports-sanctions__body reports-sanctions__body--guide">
                        <?= admin_incident_guard_operations_guide_html() ?>
                        <p id="guide-search-empty" class="reports-guide-empty" role="status" hidden>
                            No matches. Try another search or reset.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="application/json" id="reports-data-json"><?=
    json_encode($incidentReports, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT)
?></script>
<script type="application/json" id="reports-status-labels"><?=
    json_encode(admin_incident_status_options(), JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT)
?></script>
<?php admin_shell_scripts(); ?>
<script src="assets/js/reports.js?v=<?= (int) filemtime(__DIR__ . '/assets/js/reports.js') ?>" defer></script>

<?php require_once __DIR__ . '/../includes/global-alerts.php'; ?>
</body>
</html>
