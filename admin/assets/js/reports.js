(function () {
    'use strict';

    function initReportsModule() {
    const root = document.getElementById('reports-module');
    if (!root || root.dataset.reportsBound === '1') {
        return;
    }
    root.dataset.reportsBound = '1';

    const tableHeadWrap = document.getElementById('reports-table-head-wrap');
    const tableBodyWrap = document.getElementById('reports-table-body-wrap');
    if (tableHeadWrap && tableBodyWrap) {
        tableBodyWrap.addEventListener(
            'scroll',
            () => {
                tableHeadWrap.scrollLeft = tableBodyWrap.scrollLeft;
            },
            { passive: true }
        );
    }

    const dataEl = document.getElementById('reports-data-json');
    const statusLabelsEl = document.getElementById('reports-status-labels');
    const allReports = dataEl ? safeParse(dataEl.textContent) : [];
    const STATUS_LABELS = statusLabelsEl ? safeParse(statusLabelsEl.textContent) : {};
    const DEFAULT_STATUS_LABEL =
        (STATUS_LABELS && STATUS_LABELS.ongoing) || 'Open';
    const reportsById = {};
    if (Array.isArray(allReports)) {
        allReports.forEach((r) => {
            if (r && r.id) reportsById[r.id] = r;
        });
    }

    const searchInput = document.getElementById('reports-search');
    const categorySelect = document.getElementById('reports-category');
    const dateFrom = document.getElementById('reports-date-from');
    const dateTo = document.getElementById('reports-date-to');
    const resetBtn = document.getElementById('reports-reset');
    const emptyEl = document.getElementById('reports-empty');
    const tbody = document.getElementById('reports-tbody');
    const tabs = Array.from(root.querySelectorAll('[data-status-tab]'));
    const modalOverlay = document.getElementById('reports-modal-overlay');
    const modalEl = document.getElementById('reports-modal');
    const modalClose = document.getElementById('reports-modal-close');
    const modalGotoEdit = document.getElementById('modal-goto-edit');
    const modalCancelEdit = document.getElementById('modal-cancel-edit');
    const modalFooterView = document.getElementById('reports-modal-footer-view');
    const modalFooterEdit = document.getElementById('reports-modal-footer-edit');
    const panelView = document.getElementById('modal-panel-view');
    const panelEdit = document.getElementById('modal-panel-edit');
    const viewDetails = document.getElementById('modal-view-details');
    const stepperHost = document.getElementById('modal-stepper');
    const editForm = document.getElementById('reports-edit-form');
    const sanctionsOpen = document.getElementById('reports-sanctions-open');
    const sanctionsOverlay = document.getElementById('reports-sanctions-overlay');
    const sanctionsModal = document.getElementById('reports-sanctions-modal');
    const sanctionsClose = document.getElementById('reports-sanctions-close');
    const guideFilterSearch = document.getElementById('guide-filter-search');
    const guideFilterReset = document.getElementById('guide-filter-reset');
    const guideFilterCountVisible = document.getElementById('guide-filter-count-visible');
    const guideFilterCountSuffix = document.getElementById('guide-filter-count-suffix');
    const workflowRows = Array.from(document.querySelectorAll('.reports-workflow-row'));
    const workflowCategoryRows = Array.from(document.querySelectorAll('.reports-workflow-category'));
    const guideSections = Array.from(
        document.querySelectorAll('.reports-guide-section[data-guide-search]')
    );
    const guideBlocks = Array.from(document.querySelectorAll('[data-guide-block]'));
    const guideSearchEmpty = document.getElementById('guide-search-empty');
    const guideScroll = document.querySelector('.reports-sanctions__body--guide');
    const workflowTotal = workflowRows.length;
    const guideSearchTotal = workflowTotal + guideSections.length;
    const sortButtons = Array.from(root.querySelectorAll('.reports-sort[data-sort-key]'));

    const LEGACY_STATUS_TABS = { history: 'all', active: 'all' };
    const STATUS_SORT_ORDER = { ongoing: 1, on_hold: 2, accomplished: 3, denied: 4 };
    const SEVERITY_SORT_ORDER = { High: 1, Medium: 2, Low: 3 };

    function normalizeCategory(cat) {
        const c = String(cat || '').toLowerCase();
        if (c === 'external' || c === 'outside_post' || c === 'outside') {
            return 'outside_post';
        }
        return 'per_post';
    }

    let activeStatus = document.body.dataset.statusTab || 'all';
    if (!activeStatus) activeStatus = 'all';
    if (LEGACY_STATUS_TABS[activeStatus]) {
        activeStatus = LEGACY_STATUS_TABS[activeStatus];
    }
    let currentIncidentId = document.body.dataset.openIncident || '';
    let currentMode = document.body.dataset.openMode || 'view';

    function statusMatchesTab(reportStatus, tab) {
        if (tab === 'all') return true;
        return reportStatus === tab;
    }

    function getRows() {
        return Array.from(root.querySelectorAll('[data-report-row]'));
    }

    function buildIndex() {
        return getRows().map((row) => ({
            el: row,
            id: row.dataset.id || '',
            ref: row.dataset.ref || '',
            category: normalizeCategory(row.dataset.category || ''),
            status: row.dataset.status || '',
            submittedAt: row.dataset.submittedAt || '',
            updatedAt: row.dataset.updatedAt || '',
            searchBlob: (row.dataset.search || '').toLowerCase(),
            payload: reportsById[row.dataset.id || ''] || safeParse(row.dataset.detail),
            sort: {
                ref: (row.dataset.ref || '').toLowerCase(),
                category: normalizeCategory(row.dataset.category || ''),
                incident: (row.dataset.sortIncident || '').toLowerCase(),
                severity: SEVERITY_SORT_ORDER[row.dataset.sortSeverity || ''] ?? 99,
                headGuard: (row.dataset.sortHg || '').toLowerCase(),
                submitted: row.dataset.submittedAt || '',
                updated: row.dataset.updatedAt || '',
                status: STATUS_SORT_ORDER[row.dataset.status || ''] ?? 99,
            },
        }));
    }

    let reportsIndex = buildIndex();
    let sortKey = 'submitted';
    let sortDir = 'desc';

    function safeParse(json) {
        try {
            return JSON.parse(json || '{}');
        } catch {
            return {};
        }
    }

    function parseDate(str) {
        if (!str) return null;
        const d = new Date(str + 'T00:00:00');
        return Number.isNaN(d.getTime()) ? null : d;
    }

    function inDateRange(isoDate, fromVal, toVal) {
        const d = parseDate(isoDate);
        if (!d) return true;
        const from = parseDate(fromVal);
        const to = parseDate(toVal);
        if (from && d < from) return false;
        if (to && d > to) return false;
        return true;
    }

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = String(s ?? '');
        return d.innerHTML;
    }

    function severityBadgeHtml(severity) {
        const label = severity || 'Medium';
        const slug = String(label).toLowerCase();
        return (
            '<span class="reports-severity reports-severity--' +
            escapeHtml(slug) +
            '">' +
            escapeHtml(label) +
            '</span>'
        );
    }

    function tableDateCellHtml(iso, fallbackDisplay) {
        const parts = parseTableDateParts(iso, fallbackDisplay);
        const timeClass =
            parts.time !== ''
                ? 'reports-date-cell__time'
                : 'reports-date-cell__time reports-date-cell__time--empty';
        const timeContent = parts.time !== '' ? escapeHtml(parts.time) : '—';

        return (
            '<div class="reports-date-cell">' +
            '<span class="reports-date-cell__date">' +
            escapeHtml(parts.date) +
            '</span>' +
            '<span class="' +
            timeClass +
            '">' +
            timeContent +
            '</span></div>'
        );
    }

    function parseTableDateParts(iso, fallbackDisplay) {
        const isoStr = String(iso || '').trim();
        const fb = String(fallbackDisplay || '').trim();
        let ts = NaN;

        if (isoStr) {
            const normalized = isoStr.includes('T') ? isoStr : isoStr.replace(' ', 'T');
            ts = Date.parse(/^\d{4}-\d{2}-\d{2}$/.test(isoStr) ? isoStr + 'T12:00:00' : normalized);
        }
        if (Number.isNaN(ts) && fb && fb !== '—') {
            ts = Date.parse(fb);
        }

        if (Number.isNaN(ts)) {
            if (fb && fb !== '—') {
                const m = fb.match(/^(.+?),\s*(\d{1,2}:\d{2}(?::\d{2})?)\s*$/);
                if (m) {
                    return { date: m[1].trim(), time: m[2].trim() };
                }
                return { date: fb, time: '' };
            }
            return { date: '—', time: '' };
        }

        const d = new Date(ts);
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const date = d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
        let time = '';

        if (/T| \d{1,2}:\d{2}/.test(isoStr)) {
            const hh = String(d.getHours()).padStart(2, '0');
            const mm = String(d.getMinutes()).padStart(2, '0');
            time = hh + ':' + mm;
        } else {
            const tm = fb.match(/,\s*(\d{1,2}:\d{2}(?::\d{2})?)/);
            if (tm) {
                time = tm[1].length > 5 ? tm[1].slice(0, 5) : tm[1];
            }
        }

        return { date, time };
    }

    function headGuardCellHtml(p) {
        const name = escapeHtml(p.head_guard_name || p.submitter_name || '');
        const user = escapeHtml(p.head_guard_id || p.submitter_id || '');
        const post = String(p.site || '').trim();
        let html =
            '<div class="reports-hg-cell"><span class="reports-hg-name">' +
            name +
            '</span><span class="reports-hg-username mono" title="Portal username">' +
            user +
            '</span>';
        if (post) {
            html +=
                '<span class="reports-hg-post" title="Post">' + escapeHtml(post) + '</span>';
        }
        html += '</div>';
        return html;
    }

    function updateKpis() {
        const counts = { all: 0, ongoing: 0, on_hold: 0, accomplished: 0, denied: 0 };
        reportsIndex.forEach((r) => {
            if (r.el.classList.contains('is-filtered-out')) return;
            counts.all += 1;
            if (counts[r.status] !== undefined) counts[r.status] += 1;
        });
        root.querySelectorAll('[data-kpi]').forEach((el) => {
            const key = el.dataset.kpi;
            if (key && counts[key] !== undefined) {
                el.textContent = String(counts[key]);
            }
        });
        tabs.forEach((tab) => {
            const status = tab.dataset.statusTab;
            const badge = tab.querySelector('[data-tab-count]');
            if (!badge) return;
            if (status === 'all') {
                badge.textContent = String(reportsIndex.length);
                return;
            }
            const n = reportsIndex.filter((r) => r.status === status).length;
            badge.textContent = String(n);
        });
    }

    function compareRows(a, b) {
        const va = a.sort[sortKey];
        const vb = b.sort[sortKey];
        let cmp = 0;
        if (typeof va === 'number' && typeof vb === 'number') {
            cmp = va - vb;
        } else {
            cmp = String(va ?? '').localeCompare(String(vb ?? ''), undefined, {
                numeric: true,
                sensitivity: 'base',
            });
        }
        return sortDir === 'asc' ? cmp : -cmp;
    }

    function updateSortHeaderUi() {
        const sortLabels = {
            ref: 'Reference',
            category: 'Report scope',
            incident: 'Incident',
            severity: 'Severity',
            headGuard: 'Head guard',
            submitted: 'Submitted',
            updated: 'Updated',
            status: 'Status',
        };

        sortButtons.forEach((btn) => {
            const key = btn.dataset.sortKey || '';
            const th = btn.closest('th');
            const icon = btn.querySelector('.reports-sort__icon');
            const label = sortLabels[key] || key;
            const active = key === sortKey;
            btn.classList.toggle('is-active', active);
            if (!th) return;

            if (!active) {
                th.setAttribute('aria-sort', 'none');
                btn.removeAttribute('title');
                btn.setAttribute('aria-label', 'Sort by ' + label);
                if (icon) {
                    icon.className = 'reports-sort__icon reports-sort__icon--idle';
                }
                return;
            }

            const ascending = sortDir === 'asc';
            const isDateCol = key === 'submitted' || key === 'updated';
            const dirHint = isDateCol
                ? ascending
                    ? 'oldest first'
                    : 'newest first'
                : ascending
                  ? 'ascending (A–Z)'
                  : 'descending (Z–A)';
            th.setAttribute('aria-sort', ascending ? 'ascending' : 'descending');
            btn.setAttribute('title', label + ' — sorted ' + dirHint);
            btn.setAttribute(
                'aria-label',
                label + ', sorted ' + (ascending ? 'ascending' : 'descending')
            );
            if (icon) {
                icon.className =
                    'reports-sort__icon reports-sort__icon--' + (ascending ? 'asc' : 'desc');
            }
        });
    }

    function applySort() {
        if (!tbody) return;
        const sorted = [...reportsIndex].sort(compareRows);
        const frag = document.createDocumentFragment();
        sorted.forEach((r) => frag.appendChild(r.el));
        tbody.appendChild(frag);
        updateSortHeaderUi();
    }

    function setSort(nextKey) {
        if (!nextKey) return;
        if (sortKey === nextKey) {
            sortDir = sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            sortKey = nextKey;
            sortDir = nextKey === 'submitted' || nextKey === 'updated' ? 'desc' : 'asc';
        }
        applySort();
    }

    function applyFilters() {
        const q = (searchInput?.value || '').trim().toLowerCase();
        const cat = categorySelect?.value || 'all';
        const from = dateFrom?.value || '';
        const to = dateTo?.value || '';
        let visible = 0;

        reportsIndex.forEach((r) => {
            let show = true;
            if (activeStatus !== 'all' && !statusMatchesTab(r.status, activeStatus)) show = false;
            if (show && cat !== 'all' && normalizeCategory(r.category) !== cat) show = false;
            if (show && q && !r.searchBlob.includes(q)) show = false;
            if (show && !inDateRange(r.submittedAt, from, to)) show = false;

            r.el.classList.toggle('is-hidden', !show);
            r.el.classList.toggle('is-filtered-out', !show);
            if (show) visible += 1;
        });

        if (emptyEl) {
            emptyEl.classList.toggle('is-visible', visible === 0);
        }
        if (tbody) {
            tbody.setAttribute('aria-hidden', visible === 0 ? 'true' : 'false');
        }
        applySort();
        updateKpis();
    }

    function pushStatusTab(status) {
        const url = new URL(window.location.href);
        if (!status || status === 'all') {
            url.searchParams.delete('status');
        } else {
            url.searchParams.set('status', status);
        }
        const incident = url.searchParams.get('incident');
        const mode = url.searchParams.get('mode');
        const state = incident ? { incident, mode: mode || 'view' } : null;
        window.history.replaceState(state, '', url.pathname + url.search + url.hash);
    }

    function setActiveTab(tab) {
        activeStatus = tab.dataset.statusTab || 'all';
        tabs.forEach((t) => {
            const on = t === tab;
            t.classList.toggle('is-active', on);
            t.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        pushStatusTab(activeStatus);
        applyFilters();
    }

    const CLOSED_STATUSES = ['accomplished', 'denied'];

    function historyStepStatus(index, total, reportStatus) {
        if (total <= 0) return 'incomplete';
        if (index < total - 1) return 'complete';
        return CLOSED_STATUSES.includes(reportStatus) ? 'complete' : 'progress';
    }

    function historyEventIcon(event) {
        const lower = String(event || '').toLowerCase();
        if (lower.includes('submitted')) return 'fa-file-lines';
        if (lower.includes('assigned')) return 'fa-user-check';
        if (lower.includes('status')) return 'fa-flag';
        return 'fa-clock-rotate-left';
    }

    function historyStepPhaseLabel(index, total) {
        if (total <= 1) return 'Latest update';
        if (index === 0) return 'Initial filing';
        if (index === total - 1) return 'Latest update';
        return 'Follow-up';
    }

    function historyStepIntentLabel(index, total) {
        if (total <= 1) return 'This is the latest record on file for this incident.';
        if (index === 0) return 'Head guard submitted the incident to operations.';
        if (index === total - 1) {
            return 'Most recent operations update — current point in the audit trail.';
        }
        return 'Operations recorded a follow-up action on this report.';
    }

    function historyStepBadgeLabel(index, total, stepStatus) {
        if (stepStatus === 'progress' || index === total - 1) return 'Current';
        return 'Completed';
    }

    function buildTimelineNoteHtml(note) {
        return note
            ? '<p class="reports-timeline-detail__note-text">' + escapeHtml(note) + '</p>'
            : '<p class="reports-timeline-detail__note-text reports-timeline-detail__note-text--muted">No additional notes for this step.</p>';
    }

    function buildTimelineDetailPanel(panelId, tabId, note) {
        return (
            '<section id="' +
            escapeHtml(panelId) +
            '" class="reports-timeline-detail" role="tabpanel" tabindex="-1" aria-labelledby="' +
            escapeHtml(tabId) +
            '" hidden>' +
            '<div class="reports-timeline-detail__note-inner">' +
            buildTimelineNoteHtml(note) +
            '</div></section>'
        );
    }

    function historyWizardChromeHtml(total, currentStep) {
        const stepLabel = total === 1 ? '1 step' : total + ' steps';
        const progressPct = total > 0 ? Math.round(((currentStep + 1) / total) * 100) : 100;

        return (
            '<div class="reports-ops-wizard__brand-accent" aria-hidden="true"></div>' +
            '<header class="reports-ops-wizard__header">' +
            '<div class="reports-ops-wizard__header-main">' +
            '<span class="reports-ops-wizard__brand-tag">' +
            '<i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Golden Z Kids · Operations</span>' +
            '<p class="reports-ops-wizard__header-title">Incident activity trail</p>' +
            '</div>' +
            '<div class="reports-ops-wizard__header-meta">' +
            '<span class="reports-ops-wizard__meta-pill">' +
            escapeHtml(stepLabel) +
            '</span>' +
            '<span class="reports-ops-wizard__meta-direction">' +
            'Oldest <i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i> Newest' +
            '</span></div></header>' +
            '<div class="reports-ops-wizard__stepper">' +
            '<div class="reports-ops-wizard__progress" aria-hidden="true">' +
            '<div class="reports-ops-wizard__progress-track"></div>' +
            '<div class="reports-ops-wizard__progress-fill" style="width:' +
            progressPct +
            '%"></div></div>'
        );
    }

    function historyContentShellHtml(intent, event, phase, at, badgeLabel, badgeClass, noteHtml) {
        return (
            '<div class="reports-ops-wizard__detail">' +
            '<div class="reports-ops-wizard__detail-head">' +
            '<p class="reports-ops-wizard__intent" data-history-intent>' +
            '<i class="fa-solid fa-circle-info" aria-hidden="true"></i>' +
            '<span>' +
            escapeHtml(intent) +
            '</span></p>' +
            '<h4 class="reports-ops-wizard__content-title" data-history-content-title>' +
            escapeHtml(event) +
            '</h4></div>' +
            '<div class="reports-ops-step-facts" data-history-facts>' +
            '<div class="reports-ops-fact reports-ops-fact--phase">' +
            '<span class="reports-ops-fact__icon" aria-hidden="true"><i class="fa-solid fa-layer-group"></i></span>' +
            '<div class="reports-ops-fact__body">' +
            '<span class="reports-ops-fact__label">Step</span>' +
            '<span class="reports-ops-fact__value" data-history-phase>' +
            escapeHtml(phase) +
            '</span></div></div>' +
            '<div class="reports-ops-fact reports-ops-fact--when">' +
            '<span class="reports-ops-fact__icon" aria-hidden="true"><i class="fa-solid fa-clock"></i></span>' +
            '<div class="reports-ops-fact__body">' +
            '<span class="reports-ops-fact__label">Recorded</span>' +
            '<span class="reports-ops-fact__value mono" data-history-when>' +
            escapeHtml(at || '—') +
            '</span></div></div>' +
            '<div class="reports-ops-fact reports-ops-fact--status">' +
            '<span class="reports-ops-fact__icon" aria-hidden="true"><i class="fa-solid fa-flag-checkered"></i></span>' +
            '<div class="reports-ops-fact__body">' +
            '<span class="reports-ops-fact__label">Outcome</span>' +
            '<span class="reports-ops-fact__value">' +
            '<span class="reports-timeline-detail__status reports-timeline-detail__status--' +
            escapeHtml(badgeClass) +
            '" data-history-status>' +
            escapeHtml(badgeLabel) +
            '</span></span></div></div></div>' +
            '<div class="reports-ops-notes-block">' +
            '<p class="reports-ops-notes-block__label">' +
            '<i class="fa-solid fa-file-lines" aria-hidden="true"></i> What was recorded</p>' +
            '<div class="reports-ops-notes" data-history-notes>' +
            noteHtml +
            '</div></div></div>' +
            '<div class="reports-timeline__detail-area reports-timeline__detail-area--sr" aria-hidden="true">'
        );
    }

    function syncHistoryContent(wrapper, btn, panel) {
        const intentEl = wrapper.querySelector('[data-history-intent]');
        const titleEl = wrapper.querySelector('[data-history-content-title]');
        const phaseEl = wrapper.querySelector('[data-history-phase]');
        const whenEl = wrapper.querySelector('[data-history-when]');
        const statusEl = wrapper.querySelector('[data-history-status]');
        const notesEl = wrapper.querySelector('[data-history-notes]');

        if (intentEl) {
            const intentText = btn.dataset.stepIntent || '';
            const intentSpan = intentEl.querySelector('span');
            if (intentSpan) {
                intentSpan.textContent = intentText;
            } else {
                intentEl.textContent = intentText;
            }
        }
        if (titleEl) {
            titleEl.textContent = btn.dataset.eventTitle || 'Operations update';
        }
        if (phaseEl) {
            phaseEl.textContent = btn.dataset.stepPhase || '';
        }
        if (whenEl) {
            whenEl.textContent = btn.dataset.stepAt || '—';
        }
        if (statusEl) {
            const badgeClass = btn.dataset.stepBadgeClass || 'completed';
            const badge = btn.dataset.stepBadge || 'Completed';
            statusEl.className =
                'reports-timeline-detail__status reports-timeline-detail__status--' + badgeClass;
            statusEl.textContent = badge;
        }
        if (notesEl && panel) {
            const inner = panel.querySelector('.reports-timeline-detail__note-inner');
            notesEl.innerHTML = inner ? inner.innerHTML : buildTimelineNoteHtml('');
        }
    }

    function compactStepMarkerHtml(stepNum, stepStatus) {
        if (stepStatus === 'complete') {
            return (
                '<span class="reports-compact-step__marker reports-compact-step__marker--complete" aria-hidden="true">' +
                '<svg class="reports-compact-step__check" viewBox="0 0 24 24" focusable="false">' +
                '<path fill="currentColor" d="M9.55 16.2 5.35 12l-1.4 1.4 5.6 5.6 12.05-12.05-1.4-1.4-10.65 10.65z"/>' +
                '</svg></span>'
            );
        }
        return (
            '<span class="reports-compact-step__marker reports-compact-step__marker--progress" aria-hidden="true">' +
            '<span class="reports-compact-step__num">' +
            stepNum +
            '</span></span>'
        );
    }

    function updateCompactStepButton(btn, index, total, status, isCurrent) {
        const stepNum = index + 1;

        btn.className =
            'reports-compact-step reports-compact-step--' + status + (isCurrent ? ' is-active' : '');
        btn.setAttribute('aria-selected', isCurrent ? 'true' : 'false');
        btn.tabIndex = isCurrent ? 0 : -1;

        const marker = btn.querySelector('.reports-compact-step__marker');
        if (marker) {
            marker.outerHTML = compactStepMarkerHtml(stepNum, status);
        }

        const labelEl = btn.querySelector('.reports-compact-step__label');
        if (labelEl) {
            labelEl.className =
                'reports-compact-step__label reports-compact-step__label--' +
                status +
                (isCurrent ? ' is-active' : '');
        }
    }

    function buildTimelineStepItem(entry, index, total, reportStatus, currentStep) {
        let stepStatus = historyStepStatus(index, total, reportStatus);
        const isCurrent = index === currentStep;
        if (isCurrent) {
            stepStatus = 'progress';
        } else if (index < currentStep) {
            stepStatus = 'complete';
        }

        const event = entry.event || 'Update';
        const at = entry.at || '';
        const note = (entry.note || '').trim();
        const stepNum = index + 1;
        const panelId = 'reports-history-step-' + stepNum;
        const tabId = panelId + '-tab';
        const phase = historyStepPhaseLabel(index, total);
        const intent = historyStepIntentLabel(index, total);
        const badge = historyStepBadgeLabel(index, total, stepStatus);
        const badgeClass = stepStatus === 'progress' ? 'current' : 'completed';
        const ariaLabel = phase + ': ' + event + (at ? ' — ' + at : '');

        const card =
            '<div class="reports-timeline__item">' +
            '<button type="button" class="reports-compact-step reports-compact-step--' +
            escapeHtml(stepStatus) +
            (isCurrent ? ' is-active' : '') +
            '" role="tab" id="' +
            escapeHtml(tabId) +
            '" aria-controls="' +
            escapeHtml(panelId) +
            '" aria-label="' +
            escapeHtml(ariaLabel) +
            '" title="' +
            escapeHtml(event) +
            '" aria-selected="' +
            (isCurrent ? 'true' : 'false') +
            '" tabindex="' +
            (isCurrent ? '0' : '-1') +
            '" data-step-index="' +
            index +
            '" data-event-title="' +
            escapeHtml(event) +
            '" data-step-intent="' +
            escapeHtml(intent) +
            '" data-step-phase="' +
            escapeHtml(phase) +
            '" data-step-at="' +
            escapeHtml(at) +
            '" data-step-badge="' +
            escapeHtml(badge) +
            '" data-step-badge-class="' +
            escapeHtml(badgeClass) +
            '">' +
            compactStepMarkerHtml(stepNum, stepStatus) +
            '<span class="reports-compact-step__label reports-compact-step__label--' +
            escapeHtml(stepStatus) +
            (isCurrent ? ' is-active' : '') +
            '">' +
            escapeHtml(phase) +
            '</span></button></div>';

        const connector =
            index < total - 1
                ? '<span class="reports-compact-step__connector" aria-hidden="true"><span class="reports-compact-step__connector-line"></span></span>'
                : '';

        const panel = buildTimelineDetailPanel(panelId, tabId, note);

        return { card, connector, panel };
    }

    function initProcessTimeline(host) {
        const root = host || stepperHost;
        if (!root) return;

        const wrapper = root.querySelector('[data-process-timeline]');
        if (!wrapper) return;

        const buttons = Array.from(wrapper.querySelectorAll('.reports-compact-step'));
        const panels = Array.from(wrapper.querySelectorAll('.reports-timeline-detail'));
        const total = buttons.length;
        if (!total) return;

        let current = parseInt(wrapper.dataset.currentStep, 10);
        if (Number.isNaN(current) || current < 0 || current >= total) {
            current = Math.max(0, total - 1);
        }

        function stepStatusForIndex(index) {
            if (index < current) return 'complete';
            if (index === current) return 'progress';
            return 'complete';
        }

        function setStep(index, focusTab) {
            if (index < 0 || index >= total) return;
            current = index;
            wrapper.dataset.currentStep = String(current);

            const progressFill = wrapper.querySelector('.reports-ops-wizard__progress-fill');
            if (progressFill && total > 0) {
                progressFill.style.width = Math.round(((current + 1) / total) * 100) + '%';
            }

            buttons.forEach((btn, i) => {
                const status = stepStatusForIndex(i);
                const isCurrent = i === current;
                const badgeClass = status === 'progress' ? 'current' : 'completed';
                const badge = historyStepBadgeLabel(i, total, status);

                updateCompactStepButton(btn, i, total, status, isCurrent);

                btn.dataset.stepBadge = badge;
                btn.dataset.stepBadgeClass = badgeClass;

                if (panels[i]) {
                    panels[i].hidden = true;
                    panels[i].tabIndex = -1;
                }
            });

            if (buttons[current]) {
                syncHistoryContent(wrapper, buttons[current], panels[current]);
            }

            if (focusTab && buttons[current]) {
                buttons[current].focus();
            }
        }

        buttons.forEach((btn, i) => {
            btn.addEventListener('click', () => setStep(i, false));
        });

        wrapper.addEventListener('keydown', (e) => {
            const onTab = e.target.closest('.reports-compact-step');
            if (!onTab) return;

            if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                e.preventDefault();
                setStep(current > 0 ? current - 1 : total - 1, true);
            } else if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                e.preventDefault();
                setStep(current < total - 1 ? current + 1 : 0, true);
            } else if (e.key === 'Home') {
                e.preventDefault();
                setStep(0, true);
            } else if (e.key === 'End') {
                e.preventDefault();
                setStep(total - 1, true);
            }
        });

        setStep(current, false);
    }

    function renderHistoryStepper(history, reportStatus) {
        if (!stepperHost) return;
        if (!Array.isArray(history) || history.length === 0) {
            stepperHost.innerHTML =
                '<p class="reports-timeline__empty">No operations history yet. Entries are added when a report is submitted or updated by operations.</p>';
            return;
        }
        const status = reportStatus || 'ongoing';
        const total = history.length;
        const currentStep = Math.max(0, total - 1);

        const built = history.map((entry, index) =>
            buildTimelineStepItem(entry, index, total, status, currentStep)
        );

        const trackHtml = built
            .map((b, i) => b.card + (i < built.length - 1 ? b.connector : ''))
            .join('');
        const currentEntry = history[currentStep] || {};
        const currentEvent = currentEntry.event || 'Operations update';
        const currentAt = currentEntry.at || '';
        const currentNote = (currentEntry.note || '').trim();
        const currentPhase = historyStepPhaseLabel(currentStep, total);
        const currentIntent = historyStepIntentLabel(currentStep, total);

        stepperHost.innerHTML =
            '<div class="reports-process-timeline reports-ops-wizard" data-process-timeline data-current-step="' +
            currentStep +
            '" data-total-steps="' +
            total +
            '">' +
            historyWizardChromeHtml(total, currentStep) +
            '<div class="reports-timeline__scroll" role="region" aria-label="Timeline steps" tabindex="0">' +
            '<div class="reports-timeline__track reports-timeline__track--compact" role="tablist">' +
            trackHtml +
            '</div></div></div>' +
            historyContentShellHtml(
                currentIntent,
                currentEvent,
                currentPhase,
                currentAt,
                historyStepBadgeLabel(currentStep, total, 'progress'),
                'current',
                buildTimelineNoteHtml(currentNote)
            ) +
            '<div class="reports-timeline__panels">' +
            built.map((b) => b.panel).join('') +
            '</div></div></div>';

        initProcessTimeline(stepperHost);
    }

    function buildPrintHtml(p) {
        const history = Array.isArray(p.history) ? p.history : [];
        const historyRows = history.length
            ? history
                  .map(
                      (h) =>
                          '<tr><td>' +
                          escapeHtml(h.at) +
                          '</td><td>' +
                          escapeHtml(h.event) +
                          '</td><td>' +
                          escapeHtml(h.note || '—') +
                          '</td></tr>'
                  )
                  .join('')
            : '<tr><td colspan="3">No history entries.</td></tr>';

        return (
            '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>' +
            escapeHtml(p.ref || 'Incident report') +
            '</title><style>' +
            'body{font-family:system-ui,sans-serif;font-size:12pt;line-height:1.45;color:#111;margin:1.25in;}' +
            'h1{font-size:18pt;margin:0 0 4px;} .meta{color:#444;font-size:10pt;margin-bottom:20px;}' +
            'table{width:100%;border-collapse:collapse;margin:12px 0;} th,td{border:1px solid #ccc;padding:8px;text-align:left;vertical-align:top;}' +
            'th{background:#f4f4f4;font-size:9pt;text-transform:uppercase;letter-spacing:.04em;}' +
            'dl{display:grid;grid-template-columns:9rem 1fr;gap:6px 12px;margin:0 0 16px;} dt{font-weight:700;margin:0;} dd{margin:0;}' +
            '.summary{white-space:pre-wrap;}' +
            '@media print{body{margin:0.75in;}}' +
            '</style></head><body>' +
            '<h1>' +
            escapeHtml(p.ref || 'Incident report') +
            '</h1>' +
            '<p class="meta">' +
            escapeHtml(document.title.replace(/\s*\|\s*.*$/, '').trim() || 'Incident report') +
            ' · Printed ' +
            escapeHtml(new Date().toLocaleString()) +
            '</p>' +
            '<dl>' +
            '<dt>Status</dt><dd>' +
            escapeHtml(p.status_label || p.status) +
            '</dd>' +
            '<dt>Category</dt><dd>' +
            escapeHtml(p.category_label || p.category) +
            '</dd>' +
            '<dt>Severity</dt><dd>' +
            escapeHtml(p.severity || 'Medium') +
            '</dd>' +
            '<dt>Head guard</dt><dd>' +
            escapeHtml(p.head_guard_name || p.submitter_name) +
            ' (' +
            escapeHtml(p.head_guard_id || p.submitter_id) +
            ')' +
            (p.site ? ' · ' + escapeHtml(p.site) : '') +
            '</dd>' +
            '<dt>Submitted</dt><dd>' +
            escapeHtml(p.submitted_display) +
            '</dd>' +
            '<dt>Updated</dt><dd>' +
            escapeHtml(p.updated_display || '—') +
            '</dd>' +
            '<dt>Incident</dt><dd>' +
            escapeHtml(p.incident_type) +
            '</dd>' +
            '</dl>' +
            '<h2 style="font-size:12pt;margin:16px 0 8px;">Summary</h2>' +
            '<p class="summary">' +
            escapeHtml(p.summary) +
            '</p>' +
            (p.ops_note
                ? '<h2 style="font-size:12pt;margin:16px 0 8px;">Operations note</h2><p class="summary">' +
                  escapeHtml(p.ops_note) +
                  '</p>'
                : '') +
            '<h2 style="font-size:12pt;margin:16px 0 8px;">Status history</h2>' +
            '<table><thead><tr><th>When</th><th>Event</th><th>Note</th></tr></thead><tbody>' +
            historyRows +
            '</tbody></table></body></html>'
        );
    }

    function printIncident(id) {
        const p = reportsById[id];
        if (!p) return;
        const win = window.open('', '_blank', 'noopener,noreferrer');
        if (!win) return;
        win.document.open();
        win.document.write(buildPrintHtml(p));
        win.document.close();
        win.focus();
        setTimeout(function () {
            win.print();
        }, 200);
    }

    function modalHeadGuardCompactHtml(p) {
        const name = String(p.head_guard_name || p.submitter_name || '').trim();
        const id = String(p.head_guard_id || p.submitter_id || '').trim();
        if (!name && !id) {
            return '—';
        }
        let html = '<span class="reports-detail-hg reports-detail-hg--stacked">';
        if (name) {
            html += '<span class="reports-detail-hg__name">' + escapeHtml(name) + '</span>';
        }
        if (id) {
            html += '<span class="reports-detail-hg__id mono">' + escapeHtml(id) + '</span>';
        }
        html += '</span>';
        return html;
    }

    function categoryBadgeHtml(p) {
        const slug = normalizeCategory(p.category);
        const label =
            p.category_label || (slug === 'outside_post' ? 'Off post' : 'On post');
        return (
            '<span class="reports-badge reports-badge--' +
            escapeHtml(slug) +
            '">' +
            escapeHtml(label) +
            '</span>'
        );
    }

    function renderViewDetails(p) {
        if (!viewDetails || !p) return;
        const gridClass = 'reports-detail-grid reports-detail-grid--modal reports-detail-grid--compact';
        const overview =
            '<div class="reports-detail-item reports-detail-item--chip"><dt>Report scope</dt><dd>' +
            categoryBadgeHtml(p) +
            '</dd></div>' +
            '<div class="reports-detail-item reports-detail-item--chip"><dt>Severity</dt><dd>' +
            severityBadgeHtml(p.severity) +
            '</dd></div>' +
            '<div class="reports-detail-item reports-detail-item--when"><dt>Submitted</dt><dd class="mono">' +
            escapeHtml(p.submitted_display || '—') +
            '</dd></div>' +
            '<div class="reports-detail-item reports-detail-item--when"><dt>Updated</dt><dd class="mono">' +
            escapeHtml(p.updated_display || '—') +
            '</dd></div>';
        const assignment =
            '<div class="reports-detail-item reports-detail-item--hg"><dt>Head guard</dt><dd>' +
            modalHeadGuardCompactHtml(p) +
            '</dd></div>' +
            '<div class="reports-detail-item reports-detail-item--post"><dt>Post</dt><dd class="reports-detail-post">' +
            escapeHtml(String(p.site || '').trim() || '—') +
            '</dd></div>';
        const incidentType = String(p.incident_type || '').trim();
        const summary = String(p.summary || '').trim();
        const narrative =
            '<div class="reports-detail-group reports-detail-group--narrative" role="group" aria-label="Incident description">' +
            '<h4 class="reports-detail-group__title">Incident</h4>' +
            '<dl class="reports-detail-grid reports-detail-grid--modal reports-detail-grid--narrative">' +
            '<div class="reports-detail-item reports-detail-item--about"><dt>What happened</dt>' +
            '<dd class="reports-detail-about">' +
            escapeHtml(incidentType || '—') +
            '</dd></div>' +
            '<div class="reports-detail-item reports-detail-item--notes"><dt>Report notes</dt>' +
            '<dd class="reports-detail-notes' +
            (summary ? '' : ' reports-detail-notes--empty') +
            '">' +
            escapeHtml(summary || '—') +
            '</dd></div></dl></div>';

        viewDetails.innerHTML =
            '<div class="reports-detail-group reports-detail-group--overview" role="group" aria-label="Classification and dates">' +
            '<h4 class="reports-detail-group__title">Overview</h4>' +
            '<dl class="' +
            gridClass +
            '">' +
            overview +
            '</dl></div>' +
            '<hr class="reports-detail-separator" aria-hidden="true">' +
            '<div class="reports-detail-group reports-detail-group--assignment" role="group" aria-label="Assignment">' +
            '<h4 class="reports-detail-group__title">Assignment</h4>' +
            '<dl class="' +
            gridClass +
            '">' +
            assignment +
            '</dl></div>' +
            '<hr class="reports-detail-separator" aria-hidden="true">' +
            narrative;
    }

    const editPlaceholder = document.getElementById('modal-edit-placeholder');

    function populateEditForm(p) {
        if (!editForm || !p) return;
        editForm.hidden = false;
        if (editPlaceholder) editPlaceholder.hidden = true;

        const set = (name, val) => {
            const el = editForm.querySelector('[name="' + name + '"]');
            if (el) el.value = val ?? '';
        };
        const idInput = document.getElementById('edit-incident-id');
        if (idInput) idInput.value = p.id || '';
        set('status', p.status);
        set('category', normalizeCategory(p.category));
        set('incident_type', p.incident_type);
        set('site', p.site);
        set('severity', p.severity);
        set('summary', p.summary);
        const note = editForm.querySelector('[name="ops_note"]');
        if (note) note.value = '';
    }

    function setModalMode(mode) {
        currentMode = mode === 'edit' ? 'edit' : 'view';
        const isView = currentMode === 'view';
        if (panelView) {
            panelView.classList.toggle('is-active', isView);
            panelView.hidden = !isView;
        }
        if (panelEdit) {
            panelEdit.classList.toggle('is-active', !isView);
            panelEdit.hidden = isView;
        }
        if (modalFooterView) {
            modalFooterView.hidden = !isView;
        }
        if (modalFooterEdit) {
            modalFooterEdit.hidden = isView;
        }
    }

    function updateRowFromPayload(p) {
        const row = reportsIndex.find((r) => r.id === p.id);
        if (!row || !row.el) return;
        row.category = normalizeCategory(p.category);
        row.status = p.status;
        row.payload = p;
        row.sort.severity = SEVERITY_SORT_ORDER[p.severity] ?? 99;
        row.searchBlob = [
            p.ref,
            p.category_label,
            p.incident_type,
            p.site,
            p.severity,
            p.head_guard_name || p.submitter_name,
            p.head_guard_id || p.submitter_id,
            'head guard',
            p.summary,
            p.status_label,
        ]
            .join(' ')
            .toLowerCase();
        row.el.dataset.category = normalizeCategory(p.category);
        row.el.dataset.status = p.status;
        row.el.dataset.sortSeverity = p.severity || '';
        row.el.dataset.search = row.searchBlob;
        row.el.dataset.detail = JSON.stringify(p);

        const cells = row.el.querySelectorAll('td');
        if (cells.length >= 9) {
            cells[1].innerHTML =
                '<span class="reports-badge reports-badge--' +
                escapeHtml(p.category) +
                '">' +
                escapeHtml(p.category_label) +
                '</span>';
            const incTitle = escapeHtml(p.incident_type || '');
            const incCtx = escapeHtml(p.summary || '');
            cells[2].innerHTML =
                '<div class="reports-incident-cell" title="' +
                escapeHtml((p.incident_type || '') + ' — ' + (p.summary || '')) +
                '">' +
                '<span class="reports-incident-title">' +
                incTitle +
                '</span>' +
                '<span class="reports-incident-context">' +
                incCtx +
                '</span></div>';
            cells[3].innerHTML = severityBadgeHtml(p.severity);
            cells[4].innerHTML = headGuardCellHtml(p);
            cells[5].innerHTML = tableDateCellHtml(
                p.submitted_at,
                p.submitted_display || p.submitted_table_date
            );
            cells[5].title = p.submitted_display || '';
            cells[6].innerHTML = tableDateCellHtml(
                p.updated_at,
                p.updated_display || p.updated_table_date
            );
            cells[6].title = p.updated_display || '—';
            const tip = p.status_description || '';
            cells[7].innerHTML =
                '<span class="reports-badge reports-badge--' +
                escapeHtml(p.status || 'ongoing') +
                '"' +
                (tip ? ' title="' + escapeHtml(tip) + '"' : '') +
                '>' +
                escapeHtml(p.status_label || '') +
                '</span>';
            row.el.dataset.updatedAt = (p.updated_at || '').substring(0, 10);
        }
        reportsById[p.id] = p;
    }

    function pushUrl(id, mode) {
        const url = new URL(window.location.href);
        if (id) {
            url.searchParams.set('incident', id);
            url.searchParams.set('mode', mode || 'view');
        } else {
            url.searchParams.delete('incident');
            url.searchParams.delete('mode');
        }
        window.history.pushState({ incident: id, mode: mode }, '', url);
    }

    function openIncident(id, mode) {
        const p = reportsById[id];
        if (!p || !modalOverlay) return;

        currentIncidentId = id;
        setModalMode(mode || 'view');

        const refEl = document.getElementById('modal-ref');
        if (refEl) refEl.textContent = p.ref || '—';

        const statusWrap = document.getElementById('modal-status-badge-wrap');
        if (statusWrap) {
            const slug = p.status || 'ongoing';
            const label = p.status_label || DEFAULT_STATUS_LABEL;
            const tip = p.status_description || '';
            statusWrap.innerHTML =
                '<span class="reports-badge reports-badge--' +
                escapeHtml(slug) +
                '"' +
                (tip ? ' title="' + escapeHtml(tip) + '"' : '') +
                '>' +
                escapeHtml(label) +
                '</span>';
        }

        renderViewDetails(p);
        renderHistoryStepper(p.history, p.status);
        populateEditForm(p);

        if (modalGotoEdit) modalGotoEdit.hidden = false;

        modalOverlay.classList.add('is-open');
        modalOverlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        pushUrl(id, mode || 'view');

        reportsIndex.forEach((r) => {
            r.el.classList.toggle('is-selected', r.id === id);
        });
    }

    function closeModal() {
        if (!modalOverlay) return;
        modalOverlay.classList.remove('is-open');
        modalOverlay.setAttribute('aria-hidden', 'true');
        if (!sanctionsOverlay?.classList.contains('is-open')) {
            document.body.style.overflow = '';
        }
        currentIncidentId = '';
        pushUrl('', '');
        reportsIndex.forEach((r) => r.el.classList.remove('is-selected'));
    }

    function applyGuideSearch() {
        const q = (guideFilterSearch?.value || '').trim().toLowerCase();
        let workflowVisible = 0;
        let sectionVisible = 0;

        workflowRows.forEach((row) => {
            const show = !q || (row.dataset.search || '').includes(q);
            row.classList.toggle('is-hidden', !show);
            if (show) {
                workflowVisible += 1;
            }
        });
        workflowCategoryRows.forEach((catRow) => {
            const slug = catRow.dataset.categoryGroup || '';
            const anyVisible = workflowRows.some(
                (r) => r.dataset.category === slug && !r.classList.contains('is-hidden')
            );
            catRow.classList.toggle('is-hidden', !anyVisible);
        });

        guideSections.forEach((section) => {
            const show = !q || (section.dataset.guideSearch || '').includes(q);
            section.classList.toggle('is-hidden', !show);
            if (show) {
                sectionVisible += 1;
            }
        });

        guideBlocks.forEach((block) => {
            const blockId = block.dataset.guideBlock || '';
            let showBlock = true;
            if (q) {
                if (blockId === 'incidents') {
                    showBlock = workflowVisible > 0;
                } else {
                    showBlock = !!block.querySelector(
                        '.reports-guide-section:not(.is-hidden)'
                    );
                }
            }
            block.classList.toggle('is-hidden', !showBlock);
        });

        const totalVisible = workflowVisible + sectionVisible;
        if (guideSearchEmpty) {
            guideSearchEmpty.hidden = !q || totalVisible > 0;
        }
        if (guideFilterCountVisible) {
            guideFilterCountVisible.textContent = String(q ? totalVisible : guideSearchTotal);
        }
        if (guideFilterCountSuffix) {
            guideFilterCountSuffix.textContent = q
                ? ' matches'
                : ' topics';
        }
    }

    function resetGuideFilters() {
        if (guideFilterSearch) {
            guideFilterSearch.value = '';
        }
        applyGuideSearch();
        guideScroll?.scrollTo(0, 0);
    }

    function openSanctionsGuide() {
        if (!sanctionsOverlay) return;
        closeModal();
        sanctionsOverlay.classList.add('is-open');
        sanctionsOverlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        resetGuideFilters();
        guideFilterSearch?.focus();
    }

    function closeSanctionsGuide() {
        if (!sanctionsOverlay) return;
        sanctionsOverlay.classList.remove('is-open');
        sanctionsOverlay.setAttribute('aria-hidden', 'true');
        if (!modalOverlay?.classList.contains('is-open')) {
            document.body.style.overflow = '';
        }
    }

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => setActiveTab(tab));
    });

    const statusTabList = root.querySelector('.reports-status-tabs');
    statusTabList?.addEventListener('keydown', (e) => {
        const tabEls = tabs;
        const currentIndex = tabEls.findIndex((t) => t.classList.contains('is-active'));
        if (currentIndex < 0) return;

        let nextIndex = currentIndex;
        if (e.key === 'ArrowRight') {
            nextIndex = (currentIndex + 1) % tabEls.length;
        } else if (e.key === 'ArrowLeft') {
            nextIndex = (currentIndex - 1 + tabEls.length) % tabEls.length;
        } else if (e.key === 'Home') {
            nextIndex = 0;
        } else if (e.key === 'End') {
            nextIndex = tabEls.length - 1;
        } else {
            return;
        }

        e.preventDefault();
        setActiveTab(tabEls[nextIndex]);
        tabEls[nextIndex].focus();
    });

    [searchInput, categorySelect, dateFrom, dateTo].forEach((el) => {
        if (!el) return;
        el.addEventListener('input', applyFilters);
        el.addEventListener('change', applyFilters);
    });

    resetBtn?.addEventListener('click', () => {
        if (searchInput) searchInput.value = '';
        if (categorySelect) categorySelect.value = 'all';
        if (dateFrom) dateFrom.value = '';
        if (dateTo) dateTo.value = '';
        sortKey = 'submitted';
        sortDir = 'desc';
        const allTab = tabs.find((t) => t.dataset.statusTab === 'all');
        if (allTab) setActiveTab(allTab);
        else applyFilters();
    });

    sortButtons.forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            setSort(btn.dataset.sortKey || '');
        });
    });

    root.addEventListener('click', (e) => {
        if (e.target.closest('.reports-sort')) {
            return;
        }

        const viewBtn = e.target.closest('[data-action="view"]');
        const editBtn = e.target.closest('[data-action="edit"]');
        const printBtn = e.target.closest('[data-action="print"]');
        if (viewBtn) {
            e.preventDefault();
            openIncident(viewBtn.dataset.incidentId, 'view');
            return;
        }
        if (editBtn) {
            e.preventDefault();
            openIncident(editBtn.dataset.incidentId, 'edit');
            return;
        }
        if (printBtn) {
            e.preventDefault();
            e.stopPropagation();
            printIncident(printBtn.dataset.incidentId || '');
            return;
        }

        const row = e.target.closest('[data-report-row]');
        if (row && !e.target.closest('.reports-actions')) {
            openIncident(row.dataset.id, 'view');
        }
    });

    modalGotoEdit?.addEventListener('click', () => {
        if (currentIncidentId) openIncident(currentIncidentId, 'edit');
    });

    modalCancelEdit?.addEventListener('click', () => {
        if (currentIncidentId) openIncident(currentIncidentId, 'view');
    });

    modalClose?.addEventListener('click', closeModal);
    modalOverlay?.addEventListener('click', (e) => {
        if (e.target === modalOverlay) closeModal();
    });
    modalEl?.addEventListener('click', (e) => e.stopPropagation());

    sanctionsOpen?.addEventListener('click', openSanctionsGuide);
    sanctionsClose?.addEventListener('click', closeSanctionsGuide);
    sanctionsOverlay?.addEventListener('click', (e) => {
        if (e.target === sanctionsOverlay) closeSanctionsGuide();
    });
    sanctionsModal?.addEventListener('click', (e) => e.stopPropagation());
    guideFilterSearch?.addEventListener('input', applyGuideSearch);
    guideFilterReset?.addEventListener('click', resetGuideFilters);

    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        if (sanctionsOverlay?.classList.contains('is-open')) {
            closeSanctionsGuide();
            return;
        }
        closeModal();
    });

    window.addEventListener('popstate', (e) => {
        const state = e.state || {};
        if (state.incident) {
            openIncident(state.incident, state.mode || 'view');
        } else {
            closeModal();
        }
    });

    const initialTab = document.body.dataset.statusTab;
    if (initialTab) {
        const tabEl = tabs.find((t) => t.dataset.statusTab === initialTab);
        if (tabEl) setActiveTab(tabEl);
        else applyFilters();
    } else {
        applyFilters();
    }

    if (currentIncidentId && reportsById[currentIncidentId]) {
        document.body.style.overflow = 'hidden';
        populateEditForm(reportsById[currentIncidentId]);
        reportsIndex.forEach((r) => {
            r.el.classList.toggle('is-selected', r.id === currentIncidentId);
        });
        setModalMode(currentMode);
        initProcessTimeline(stepperHost);
    }
    }

    window.initReportsModule = initReportsModule;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initReportsModule);
    } else {
        initReportsModule();
    }
})();
