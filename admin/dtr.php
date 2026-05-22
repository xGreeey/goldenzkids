<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once APP_ROOT . '/includes/admin_attendance_detail.php';

auth_require_permission('admin.dtr.view');

$actorId = (string) ($_SESSION['company_id'] ?? 'admin');
$openRecordId = trim((string) ($_GET['record'] ?? ''));
$drawerMode = trim((string) ($_GET['mode'] ?? 'view'));
if (!in_array($drawerMode, ['view', 'edit'], true)) {
    $drawerMode = 'view';
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();
    $action = trim((string) ($_POST['action'] ?? ''));

    if ($action === 'reset_demo') {
        admin_attendance_store_reset();
        redirect_with_alert('Demo ' . ADMIN_ATTENDANCE_REF_CODE . ' data has been reset to defaults.', admin_attendance_page_path());
    }

    if ($action === 'update_attendance') {
        $id = trim((string) ($_POST['record_id'] ?? ''));
        $updated = admin_attendance_update($id, [
            'status' => (string) ($_POST['status'] ?? ''),
            'issue' => (string) ($_POST['issue'] ?? ''),
            'recorded' => (string) ($_POST['recorded'] ?? ''),
            'post' => (string) ($_POST['post'] ?? ''),
            'time_record' => (string) ($_POST['time_record'] ?? ''),
            'summary' => (string) ($_POST['summary'] ?? ''),
            'ops_note' => (string) ($_POST['ops_note'] ?? ''),
        ], $actorId);

        if ($updated === null) {
            redirect_with_alert(ADMIN_ATTENDANCE_REF_CODE . ' record not found.', admin_attendance_page_path());
        }

        $ref = (string) ($updated['ref'] ?? $id);
        redirect_with_alert(
            ADMIN_ATTENDANCE_REF_CODE . ' ' . $ref . ' saved. Status history updated.',
            admin_attendance_page_path() . '?record=' . rawurlencode($id) . '&mode=view'
        );
    }

    if ($action === 'delete_attendance') {
        $id = trim((string) ($_POST['record_id'] ?? ''));
        $deleted = admin_attendance_delete($id);
        $wantsJson = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

        if ($deleted === null) {
            if ($wantsJson) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(404);
                echo json_encode(['ok' => false, 'error' => ADMIN_ATTENDANCE_REF_CODE . ' record not found.']);
                exit;
            }
            redirect_with_alert(ADMIN_ATTENDANCE_REF_CODE . ' record not found.', admin_attendance_page_path());
        }

        $ref = (string) ($deleted['ref'] ?? $id);
        if ($wantsJson) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true, 'message' => ADMIN_ATTENDANCE_REF_CODE . ' ' . $ref . ' deleted.', 'id' => $id]);
            exit;
        }

        redirect_with_alert(ADMIN_ATTENDANCE_REF_CODE . ' ' . $ref . ' deleted.', admin_attendance_page_path());
    }
    if ($action === 'archive_attendance') {
        $id = trim((string) ($_POST['record_id'] ?? ''));
        $archived = admin_attendance_archive($id, $actorId);
        $wantsJson = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

        if ($archived === null) {
            if ($wantsJson) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(404);
                echo json_encode(['ok' => false, 'error' => ADMIN_ATTENDANCE_REF_CODE . ' record not found.']);
                exit;
            }
            redirect_with_alert(ADMIN_ATTENDANCE_REF_CODE . ' record not found.', admin_attendance_page_path());
        }

        $ref = (string) ($archived['ref'] ?? $id);
        $statusLabel = admin_attendance_status_label((string) ($archived['status'] ?? ADMIN_INCIDENT_STATUS_ACCOMPLISHED));
        if ($wantsJson) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => true,
                'message' => ADMIN_ATTENDANCE_REF_CODE . ' ' . $ref . ' archived (' . $statusLabel . ').',
                'record' => $archived,
            ]);
            exit;
        }

        redirect_with_alert(
            ADMIN_ATTENDANCE_REF_CODE . ' ' . $ref . ' archived (' . $statusLabel . ').',
            admin_attendance_page_path() . '?record=' . rawurlencode($id) . '&mode=view'
        );
    }
}

$attendanceRecords = admin_attendance_store_all();
$statusCounts = admin_attendance_status_counts($attendanceRecords);
$openRecord = $openRecordId !== '' ? admin_attendance_find($openRecordId) : null;
if ($openRecordId !== '' && $openRecord === null) {
    $openRecordId = '';
}

$statusDefinitions = admin_attendance_status_definitions();
/** @var list<array{slug: string, label: string, count: int, title: string}> */
$registryStatusTabs = [
    [
        'slug' => 'all',
        'label' => 'All',
        'count' => (int) $statusCounts['all'],
        'title' => 'Every ' . ADMIN_ATTENDANCE_REF_CODE . ' flag in the registry',
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
$validStatusTabs = ['all', ...admin_attendance_status_slugs()];
$initialStatusTab = in_array($statusTabFromQuery, $validStatusTabs, true) ? $statusTabFromQuery : '';

$adminNavActive = 'dtr';

/**
 * @param array<string, mixed> $record
 */
function admin_daily_detail_row_attrs(array $record): string
{
    $detailJson = json_encode($record, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);

    return implode(' ', [
        'data-attendance-row',
        'data-id="' . e((string) $record['id']) . '"',
        'data-ref="' . e((string) $record['ref']) . '"',
        'data-status="' . e((string) $record['status']) . '"',
        'data-issue="' . e((string) $record['issue']) . '"',
        'data-recorded="' . e((string) $record['recorded']) . '"',
        'data-submitted-at="' . e((string) $record['submitted_at']) . '"',
        'data-updated-at="' . e(substr((string) ($record['updated_at'] ?? $record['submitted_at'] ?? ''), 0, 10)) . '"',
        'data-shift-date="' . e((string) ($record['shift_date'] ?? '')) . '"',
        'data-sort-guard="' . e(strtolower((string) ($record['guard_name'] ?? ''))) . '"',
        'data-sort-post="' . e(strtolower((string) ($record['post'] ?? ''))) . '"',
        'data-sort-issue="' . e(strtolower((string) ($record['issue_label'] ?? ''))) . '"',
        'data-search="' . e(admin_attendance_search_blob($record)) . '"',
        'data-detail="' . e($detailJson) . '"',
    ]);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?= mobile_meta_tags() ?>
    <title><?= e(app_agency_name()) ?> | <?= e(ADMIN_ATTENDANCE_MODULE_LABEL) ?> (<?= e(ADMIN_ATTENDANCE_REF_CODE) ?>)</title>
    <script src="https://kit.fontawesome.com/3142eebea3.js" crossorigin="anonymous"></script>
    <?= app_fonts_link() ?>
    <style>
<?php admin_shell_styles(); ?>
<?php readfile(__DIR__ . '/assets/css/dashboard.css'); ?>
<?php readfile(__DIR__ . '/assets/css/reports.css'); ?>
    </style>
</head>
<body class="light-mode page-incident-reports page-dtr"
      data-admin-nav="dtr"
      data-open-record="<?= e($openRecordId) ?>"
      data-open-mode="<?= e($drawerMode) ?>"
      data-status-tab="<?= e($initialStatusTab) ?>"<?= $openRecord !== null ? ' style="overflow:hidden"' : '' ?>>

<?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <main class="app-main">
            <header class="page-header page-header--inline">
                <h1 class="page-title"><?= e(ADMIN_ATTENDANCE_MODULE_LABEL) ?></h1>
                <p class="page-subtitle">Monitor and review daily time records — missing or wrong time-in/out, late check-ins, absences, and NTE — with full status history.</p>
            </header>

            <div id="reports-module"
             class="reports-module"
             data-registry-kind="dtr"
             data-csrf="<?= e_attr(csrf_token()) ?>"
             data-delete-url="<?= e_attr(admin_attendance_page_path()) ?>"
             data-ocr-url="<?= e_attr(app_url('admin/api/dad-ocr.php')) ?>"
             data-ocr-export-url="<?= e_attr(app_url('admin/api/dad-ocr-export.php')) ?>">
                <section class="kpi-grid" aria-label="<?= e(ADMIN_ATTENDANCE_REF_CODE) ?> summary">
                <article class="kpi-card kpi-card--total" title="All <?= e(ADMIN_ATTENDANCE_REF_CODE) ?> records in the registry">
                    <div class="kpi-stat">
                        <span class="kpi-value" data-kpi="all"><?= (int) $statusCounts['all'] ?></span>
                    </div>
                    <p class="kpi-label">Total records</p>
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

                <section class="reports-panel" aria-label="<?= e(ADMIN_ATTENDANCE_MODULE_LABEL) ?> registry">
                    <div class="reports-panel__filters">
                        <div class="reports-toolbar" role="search">
                            <div class="reports-toolbar__fields">
                            <div class="form-field reports-field--search">
                                <label for="daily-search" class="reports-label-with-icon"><?= admin_ui_icon('magnifying-glass', 14) ?> Search</label>
                                <input type="search" id="daily-search" placeholder="<?= e(ADMIN_ATTENDANCE_REF_CODE) ?> reference, guard, post, time record…" autocomplete="off">
                            </div>
                            <div class="form-field reports-field--category">
                                <label for="daily-issue">Issue type</label>
                                <select id="daily-issue">
                                    <option value="all">All issues</option>
                                    <?php foreach (admin_attendance_issue_options() as $slug => $label): ?>
                                    <option value="<?= e($slug) ?>"><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-field reports-field--date">
                                <label for="daily-date-from">Shift from</label>
                                <input type="date" id="daily-date-from" value="2026-04-01">
                            </div>
                            <div class="form-field reports-field--date">
                                <label for="daily-date-to">Shift to</label>
                                <input type="date" id="daily-date-to" value="<?= e(date('Y-m-d')) ?>">
                            </div>
                        </div>
                        <div class="reports-toolbar-actions" role="toolbar" aria-label="<?= e(ADMIN_ATTENDANCE_REF_CODE) ?> filter actions">
                            <div class="reports-button-set">
                                <button type="button" class="reports-btn reports-btn--secondary" id="daily-reset">
                                    <?= admin_btn_icon('rotate-left') ?>
                                    <span class="reports-btn__text">Reset</span>
                                </button>
                                <form method="POST" class="reports-inline-form" id="daily-reset-demo-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="reset_demo">
                                    <button type="submit" class="reports-btn reports-btn--secondary" title="Restore demo <?= e(ADMIN_ATTENDANCE_REF_CODE) ?> records">
                                        <?= admin_btn_icon('database') ?>
                                        <span class="reports-btn__text">Reset demo</span>
                                    </button>
                                </form>
                                <button type="button" class="reports-btn reports-btn--secondary" id="daily-guide-open" title="<?= e(ADMIN_ATTENDANCE_MODULE_LABEL) ?> (<?= e(ADMIN_ATTENDANCE_REF_CODE) ?>) — equivalence, NTE, missing values">
                                    <?= admin_btn_icon('book-open') ?>
                                    <span class="reports-btn__text"><?= e(ADMIN_ATTENDANCE_REF_CODE) ?> guide</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    </div>

                    <nav class="reports-status-tabs reports-panel__tabs" role="tablist" aria-label="Filter <?= e(ADMIN_ATTENDANCE_REF_CODE) ?> by status">
                    <?php foreach ($registryStatusTabs as $tab):
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
                    <div class="reports-registry" role="region" aria-label="<?= e(ADMIN_ATTENDANCE_REF_CODE) ?> registry table">
                        <div class="reports-table-head-wrap" id="reports-table-head-wrap">
                            <table class="reports-table reports-table--head reports-table--attendance">
                                <colgroup>
                                    <col class="reports-col-ref">
                                    <col class="reports-col-guard">
                                    <col class="reports-col-post">
                                    <col class="reports-col-shift">
                                    <col class="reports-col-issue">
                                    <col class="reports-col-time">
                                    <col class="reports-col-equiv">
                                    <col class="reports-col-submitted">
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
                                        <th scope="col" class="reports-col-guard" aria-sort="none">
                                            <button type="button" class="reports-sort" data-sort-key="guard">
                                                <span class="reports-sort__label">Guard</span>
                                                <span class="reports-sort__icon reports-sort__icon--idle" aria-hidden="true"></span>
                                            </button>
                                        </th>
                                        <th scope="col" class="reports-col-post" aria-sort="none">
                                            <button type="button" class="reports-sort" data-sort-key="post">
                                                <span class="reports-sort__label">Post</span>
                                                <span class="reports-sort__icon reports-sort__icon--idle" aria-hidden="true"></span>
                                            </button>
                                        </th>
                                        <th scope="col" class="reports-col-shift" aria-sort="descending">
                                            <button type="button" class="reports-sort is-active" data-sort-key="shift" title="Shift date — sorted descending (newest first)" aria-label="Shift date, sorted descending">
                                                <span class="reports-sort__label">Shift</span>
                                                <span class="reports-sort__icon reports-sort__icon--desc" aria-hidden="true"></span>
                                            </button>
                                        </th>
                                        <th scope="col" class="reports-col-issue" aria-sort="none">
                                            <button type="button" class="reports-sort" data-sort-key="issue">
                                                <span class="reports-sort__label">Issue</span>
                                                <span class="reports-sort__icon reports-sort__icon--idle" aria-hidden="true"></span>
                                            </button>
                                        </th>
                                        <th scope="col" class="reports-col-time">Time record</th>
                                        <th scope="col" class="reports-col-equiv">Equiv.</th>
                                        <th scope="col" class="reports-col-submitted" aria-sort="none">
                                            <button type="button" class="reports-sort" data-sort-key="submitted">
                                                <span class="reports-sort__label">Flagged</span>
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
                            <table class="reports-table reports-table--body reports-table--attendance">
                                <colgroup>
                                    <col class="reports-col-ref">
                                    <col class="reports-col-guard">
                                    <col class="reports-col-post">
                                    <col class="reports-col-shift">
                                    <col class="reports-col-issue">
                                    <col class="reports-col-time">
                                    <col class="reports-col-equiv">
                                    <col class="reports-col-submitted">
                                    <col class="reports-col-status">
                                    <col class="reports-col-actions">
                                </colgroup>
                                <tbody id="daily-tbody">
                                <?php foreach ($attendanceRecords as $record): ?>
                                <tr <?= admin_daily_detail_row_attrs($record) ?>>
                                    <td class="reports-col-ref"><span class="reports-ref mono"><?= e((string) $record['ref']) ?></span></td>
                                    <td class="reports-col-guard"><?= admin_attendance_guard_cell_html($record) ?></td>
                                    <td class="reports-col-post"><?= e((string) $record['post']) ?></td>
                                    <td class="reports-col-shift reports-col-date mono" title="<?= e((string) $record['shift_display']) ?>"><?= e((string) $record['shift_date']) ?></td>
                                    <td class="reports-col-issue">
                                        <span class="reports-issue-label"><?= e((string) (($record['issue_label'] ?? '') !== '' ? $record['issue_label'] : '—')) ?></span>
                                    </td>
                                    <td class="reports-col-time">
                                        <span class="reports-time-record"><?= e((string) $record['time_record']) ?></span>
                                    </td>
                                    <td class="reports-col-equiv"><?= admin_attendance_recorded_badge_html($record) ?></td>
                                    <td class="reports-col-submitted reports-col-date"><?= admin_incident_table_date_cell_html((string) ($record['submitted_at'] ?? ''), (string) ($record['submitted_display'] ?? '')) ?></td>
                                    <td class="reports-col-status"><?= admin_attendance_status_badge_html($record) ?></td>
                                    <td class="reports-col-actions">
                                        <div class="reports-actions" role="group" aria-label="Actions for <?= e((string) $record['ref']) ?>">
                                            <button type="button"
                                                    class="reports-action-btn"
                                                    data-action="view"
                                                    data-record-id="<?= e((string) $record['id']) ?>"
                                                    title="View record"
                                                    aria-label="View <?= e((string) $record['ref']) ?>">
                                                <?= admin_incident_action_icon('view') ?>
                                            </button>
                                            <button type="button"
                                                    class="reports-action-btn reports-action-btn--primary"
                                                    data-action="edit"
                                                    data-record-id="<?= e((string) $record['id']) ?>"
                                                    title="Edit record"
                                                    aria-label="Edit <?= e((string) $record['ref']) ?>">
                                                <?= admin_incident_action_icon('edit') ?>
                                            </button>
                                            <?php
                                            $dtrStatusSlug = admin_attendance_status_normalize((string) $record['status']);
                                            $dtrStatusClosed = (admin_attendance_status_definitions()[$dtrStatusSlug]['closed'] ?? false) === true;
                                            if (!$dtrStatusClosed):
                                            ?>
                                            <button type="button"
                                                    class="reports-action-btn reports-action-btn--archive"
                                                    data-action="archive"
                                                    data-record-id="<?= e((string) $record['id']) ?>"
                                                    data-record-ref="<?= e((string) $record['ref']) ?>"
                                                    title="Archive (close case)"
                                                    aria-label="Archive <?= e((string) $record['ref']) ?>">
                                                <?= admin_incident_action_icon('archive') ?>
                                            </button>
                                            <?php endif; ?>
                                            <button type="button"
                                                    class="reports-action-btn reports-action-btn--danger"
                                                    data-action="delete"
                                                    data-record-id="<?= e((string) $record['id']) ?>"
                                                    data-record-ref="<?= e((string) $record['ref']) ?>"
                                                    title="Delete record"
                                                    aria-label="Delete <?= e((string) $record['ref']) ?>">
                                                <?= admin_incident_action_icon('delete') ?>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="daily-empty" class="reports-empty" role="status" aria-live="polite">
                        <div class="reports-empty__icon" aria-hidden="true"><?= admin_ui_icon('calendar-xmark', 28) ?></div>
                        <p class="reports-empty__title">No records match your filters</p>
                        <p class="reports-empty__hint">Try another issue type, date range, or status tab — or clear search to see all flags.</p>
                    </div>
                </div>

                <footer class="reports-panel__footer">
                    <p class="reports-status-key" id="daily-status-key">
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

        <script type="application/json" id="daily-detail-data-json"><?=
            json_encode($attendanceRecords, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT)
        ?></script>
        <script type="application/json" id="daily-status-labels"><?=
            json_encode(admin_attendance_status_options(), JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT)
        ?></script>
    </main>
</div>

<div id="daily-modal-overlay" class="reports-modal-overlay<?= $openRecord !== null ? ' is-open' : '' ?>"
     role="presentation" aria-hidden="<?= $openRecord !== null ? 'false' : 'true' ?>">
    <div class="reports-modal reports-dad-modal" id="daily-modal" role="dialog" aria-modal="true" aria-labelledby="daily-modal-title">
        <header class="reports-modal__header">
            <div class="reports-modal__identity">
                <span class="reports-modal__eyebrow"><?= e(ADMIN_ATTENDANCE_REF_CODE) ?></span>
                <div class="reports-modal__title-row">
                    <h2 id="daily-modal-title" class="reports-modal__ref">
                        <span id="daily-modal-ref"><?= $openRecord ? e((string) $openRecord['ref']) : '—' ?></span>
                    </h2>
                    <div id="daily-modal-status-wrap" class="reports-modal__status">
                        <?= $openRecord ? admin_attendance_status_badge_html($openRecord) : '<span class="reports-badge">—</span>' ?>
                    </div>
                </div>
            </div>
            <button type="button" class="reports-modal__close" id="daily-modal-close" aria-label="Close dialog">&times;</button>
        </header>

        <div class="reports-modal__content">
            <div class="reports-modal__body-scroll">
                <div class="reports-modal-form">
                    <div class="reports-modal-form__blocks">
                        <div id="daily-panel-view" class="reports-modal-panel reports-modal-form__section reports-modal-form__section--wide<?= $drawerMode === 'view' ? ' is-active' : '' ?>"<?= $drawerMode === 'view' ? '' : ' hidden' ?>>
                            <div id="daily-view-details" class="reports-modal-view-details">
                                <?php if ($openRecord): ?>
                                <?= admin_attendance_modal_details_html($openRecord) ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div id="daily-panel-edit" class="reports-modal-panel reports-modal-form__section reports-modal-form__section--wide<?= $drawerMode === 'edit' ? ' is-active' : '' ?>"<?= $drawerMode === 'edit' ? '' : ' hidden' ?>>
                            <header class="reports-modal-form__section-header">
                                <h3 class="reports-modal-form__section-title">Edit <?= e(ADMIN_ATTENDANCE_REF_CODE) ?> record</h3>
                            </header>
                            <form method="POST" class="reports-edit-form" id="daily-edit-form"<?= $openRecord === null ? ' hidden' : '' ?>>
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="update_attendance">
                                <input type="hidden" name="record_id" id="edit-record-id" value="<?= $openRecord ? e((string) $openRecord['id']) : '' ?>">

                                <div class="reports-form-fields">
                                    <div class="reports-form-group">
                                        <div class="reports-form-row">
                                            <div class="reports-form-field">
                                                <label for="edit-status">Case registry</label>
                                                <select id="edit-status" name="status" required>
                                                    <?php foreach ($statusDefinitions as $val => $def): ?>
                                                    <option value="<?= e($val) ?>"><?= e($def['label']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="reports-form-field">
                                                <label for="edit-recorded">Equivalence</label>
                                                <select id="edit-recorded" name="recorded" required>
                                                    <?php foreach (admin_attendance_recorded_options() as $val => $label): ?>
                                                    <option value="<?= e($val) ?>"><?= e($label) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="reports-form-row">
                                            <div class="reports-form-field">
                                                <label for="edit-issue">Issue type</label>
                                                <select id="edit-issue" name="issue" required>
                                                    <?php foreach (admin_attendance_issue_options() as $val => $label): ?>
                                                    <option value="<?= e($val) ?>"><?= e($label) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="reports-form-field">
                                                <label for="edit-post">Post</label>
                                                <input type="text" id="edit-post" name="post" required maxlength="200" value="">
                                            </div>
                                        </div>
                                        <div class="reports-form-field">
                                            <label for="edit-time-record">Time record</label>
                                            <input type="text" id="edit-time-record" name="time_record" required maxlength="300" value="">
                                        </div>
                                        <div class="reports-form-field">
                                            <label for="edit-summary">Summary</label>
                                            <textarea id="edit-summary" name="summary" rows="4" required maxlength="2000"></textarea>
                                        </div>
                                        <div class="reports-form-field">
                                            <label for="edit-ops-note">Operations note <span class="reports-optional">(appended to history)</span></label>
                                            <textarea id="edit-ops-note" name="ops_note" rows="3" maxlength="1000" placeholder="NTE issued, timekeeping corrected, dismissed as roster error…"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            <p class="reports-modal-placeholder" id="daily-edit-placeholder"<?= $openRecord !== null ? ' hidden' : '' ?>>Select a record from the table to edit.</p>
                        </div>
                    </div>

                    <hr class="reports-modal-form__separator" aria-hidden="true">

                    <section class="reports-modal-form__section reports-modal-form__section--wide reports-modal__history" aria-labelledby="daily-history-heading">
                        <header class="reports-modal-form__section-header reports-modal__history-intro">
                            <h3 id="daily-history-heading" class="reports-modal-form__section-title">Review history</h3>
                            <p class="reports-modal-form__section-desc reports-modal__history-lead">Chronological audit trail for this DTR record — submissions, status changes, and operations notes.</p>
                        </header>
                        <div id="daily-stepper" class="reports-activity-timeline-host" role="region" aria-label="Review history">
                            <?php if ($openRecord): ?>
                            <?= admin_attendance_history_stepper_html(
                                is_array($openRecord['history'] ?? null) ? $openRecord['history'] : [],
                                (string) ($openRecord['status'] ?? ADMIN_ATTENDANCE_STATUS_PENDING)
                            ) ?>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
            </div>

            <footer class="reports-modal__footer">
                <div class="reports-modal-footer__button-set" id="daily-modal-footer-view"<?= $drawerMode === 'edit' ? ' hidden' : '' ?>>
                    <div class="reports-button-set">
                        <button type="button" class="reports-btn reports-btn--primary" id="daily-goto-edit"<?= $openRecord ? '' : ' hidden' ?>>
                            <?= admin_btn_icon('pen-to-square') ?>
                            <span class="reports-btn__text">Edit this record</span>
                        </button>
                    </div>
                </div>
                <div class="reports-modal-footer__button-set" id="daily-modal-footer-edit"<?= $drawerMode === 'view' ? ' hidden' : '' ?>>
                    <div class="reports-button-set">
                        <button type="submit" class="reports-btn reports-btn--primary" form="daily-edit-form" id="daily-save-edit">
                            <?= admin_btn_icon('floppy-disk') ?>
                            <span class="reports-btn__text">Save changes</span>
                        </button>
                        <button type="button" class="reports-btn reports-btn--secondary" id="daily-cancel-edit">
                            <span class="reports-btn__text">Cancel</span>
                        </button>
                    </div>
                </div>
            </footer>
        </div>
    </div>
</div>

<div id="daily-guide-overlay" class="reports-modal-overlay reports-guard-guide-overlay" role="presentation" aria-hidden="true">
    <div class="reports-modal reports-guard-guide-modal reports-guide--simple" id="daily-guide-modal" role="dialog" aria-modal="true" aria-labelledby="daily-guide-title">
        <header class="reports-modal__header">
            <div class="reports-modal__identity">
                <span class="reports-modal__eyebrow"><?= e(ADMIN_ATTENDANCE_REF_CODE) ?></span>
                <h2 id="daily-guide-title" class="reports-modal__ref"><?= e(ADMIN_ATTENDANCE_MODULE_LABEL) ?> guide</h2>
                <p class="reports-modal__lead"><?= e(ADMIN_ATTENDANCE_REF_CODE) ?> reference — equivalence values, review workflow, and when to issue an NTE for missing or wrong time-in/out.</p>
            </div>
            <button type="button" class="reports-modal__close" id="daily-guide-close" aria-label="Close guide">&times;</button>
        </header>
        <div class="reports-modal__content">
            <div class="reports-modal__body-scroll">
                <div class="reports-modal-form reports-guard-guide-modal__form">
                    <div class="reports-guard-guide__body">
                        <?= admin_attendance_monitoring_guide_html() ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php admin_shell_scripts(); ?>
<script src="assets/js/daily-detail.js?v=<?= (int) filemtime(__DIR__ . '/assets/js/daily-detail.js') ?>" defer></script>

<?php require_once __DIR__ . '/../includes/global-alerts.php'; ?>
</body>
</html>
