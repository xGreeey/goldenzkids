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

    function historyDatetimeParts(at) {
        const raw = String(at || '').trim();
        if (!raw) {
            return { date: '—', time: '' };
        }
        const m = raw.match(/^(.+?),\s*(\d{1,2}:\d{2}(?::\d{2})?)\s*$/);
        if (m) {
            return { date: m[1].trim(), time: m[2].trim() };
        }
        return { date: raw, time: '' };
    }

    function cellText(value) {
        const v = String(value ?? '').trim();
        return escapeHtml(v !== '' ? v : '—');
    }

    function sheetField(label, value, modifier) {
        const trimmed = String(value ?? '').trim();
        const mod = modifier ? ' reports-detail-sheet__field--' + modifier : '';
        const empty = trimmed === '' ? ' is-empty' : '';

        return (
            '<div class="reports-detail-sheet__field' +
            mod +
            empty +
            '">' +
            '<span class="reports-detail-sheet__label">' +
            escapeHtml(label) +
            '</span>' +
            '<span class="reports-detail-sheet__value">' +
            cellText(trimmed) +
            '</span></div>'
        );
    }

    function buildModalDetailsHtml(p) {
        let headGuard = String(p.head_guard_name || p.submitter_name || '').trim();
        const headGuardId = String(p.head_guard_id || p.submitter_id || '').trim();
        if (headGuard && headGuardId) {
            headGuard += ' (' + headGuardId + ')';
        } else if (!headGuard && headGuardId) {
            headGuard = headGuardId;
        }

        const person = String(p.person_involved || p.guard_involved || '').trim();

        return (
            '<div class="reports-detail-sheet" role="group" aria-label="Report summary">' +
            '<section class="reports-detail-sheet__section" aria-label="Assignment">' +
            '<div class="reports-detail-sheet__grid reports-detail-sheet__grid--people">' +
            sheetField('Post', p.site) +
            sheetField('Head guard', headGuard) +
            sheetField('Guard', person) +
            '</div></section>' +
            '<section class="reports-detail-sheet__section" aria-label="Incident">' +
            '<div class="reports-detail-sheet__grid reports-detail-sheet__grid--incident">' +
            sheetField('Incident', p.incident_type, 'incident') +
            sheetField('Description', p.summary, 'description') +
            sheetField('Severity', p.severity, 'severity') +
            '</div></section></div>'
        );
    }

    function buildOperationFlowHtml(history, p) {
        if (!Array.isArray(history) || history.length === 0) {
            return (
                '<p class="reports-op-flow__empty">No operations history yet. Entries are added when a report is submitted or updated by operations.</p>'
            );
        }

        let rows = '';
        history.forEach((entry, index) => {
            const event = String(entry.event || 'Update').trim();
            const note = String(entry.note || '').trim();
            const parts = historyDatetimeParts(entry.at);
            const stepNum = index + 1;
            const title = 'Step ' + stepNum + ' — ' + event;
            const description = note !== '' ? note : '—';

            rows +=
                '<tr class="reports-op-flow__row">' +
                '<td class="reports-op-flow__when">' +
                '<span class="reports-op-flow__date">' +
                escapeHtml(parts.date) +
                '</span>' +
                '<span class="reports-op-flow__time">' +
                escapeHtml(parts.time) +
                '</span></td>' +
                '<td class="reports-op-flow__rule" aria-hidden="true"></td>' +
                '<td class="reports-op-flow__step">' +
                '<span class="reports-op-flow__step-title">' +
                escapeHtml(title) +
                '</span>' +
                '<span class="reports-op-flow__step-desc">' +
                escapeHtml(description) +
                '</span></td>' +
                '<td class="reports-op-flow__action">' +
                escapeHtml(event) +
                '</td></tr>';
        });

        const statusLabel = String(p.status_label || p.status || '—');
        rows +=
            '<tr class="reports-op-flow__row reports-op-flow__row--status">' +
            '<td class="reports-op-flow__when"><span class="reports-op-flow__date">—</span></td>' +
            '<td class="reports-op-flow__rule" aria-hidden="true"></td>' +
            '<td class="reports-op-flow__status" colspan="2">' +
            escapeHtml(statusLabel) +
            '</td></tr>';

        const header =
            '<thead><tr class="reports-op-flow__head">' +
            '<th scope="col" class="reports-op-flow__col-when">Recorded</th>' +
            '<th scope="col" class="reports-op-flow__col-rule" aria-hidden="true"></th>' +
            '<th scope="col" class="reports-op-flow__col-step">Step</th>' +
            '<th scope="col" class="reports-op-flow__col-action">Action</th>' +
            '</tr></thead>';

        return '<table class="reports-op-flow__table">' + header + '<tbody>' + rows + '</tbody></table>';
    }

    function renderHistoryStepper(history, p) {
        if (!stepperHost) {
            return;
        }
        stepperHost.innerHTML = buildOperationFlowHtml(history, p || {});
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
        if (!viewDetails || !p) {
            return;
        }
        viewDetails.innerHTML = buildModalDetailsHtml(p);
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
        renderHistoryStepper(p.history, p);
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
    }
    }

    window.initReportsModule = initReportsModule;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initReportsModule);
    } else {
        initReportsModule();
    }
})();
