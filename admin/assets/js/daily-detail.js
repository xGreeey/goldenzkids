(function () {
    'use strict';

    function safeParse(json) {
        try {
            return JSON.parse(json);
        } catch {
            return null;
        }
    }

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function initDailyDetailModule() {
        const root = document.getElementById('daily-detail-module');
        if (!root || root.dataset.dailyBound === '1') {
            return;
        }
        root.dataset.dailyBound = '1';

        const tableHeadWrap = document.getElementById('daily-table-head-wrap');
        const tableBodyWrap = document.getElementById('daily-table-body-wrap');
        if (tableHeadWrap && tableBodyWrap) {
            tableBodyWrap.addEventListener(
                'scroll',
                () => {
                    tableHeadWrap.scrollLeft = tableBodyWrap.scrollLeft;
                },
                { passive: true }
            );
        }

        const dataEl = document.getElementById('daily-detail-data-json');
        const allRecords = dataEl ? safeParse(dataEl.textContent) : [];
        const recordsById = {};
        if (Array.isArray(allRecords)) {
            allRecords.forEach((r) => {
                if (r && r.id) {
                    recordsById[r.id] = r;
                }
            });
        }

        const searchInput = document.getElementById('daily-search');
        const issueSelect = document.getElementById('daily-issue');
        const dateFrom = document.getElementById('daily-date-from');
        const dateTo = document.getElementById('daily-date-to');
        const resetBtn = document.getElementById('daily-reset');
        const emptyEl = document.getElementById('daily-empty');
        const tbody = document.getElementById('daily-tbody');
        const tabs = Array.from(root.querySelectorAll('[data-status-tab]'));
        const sortButtons = Array.from(root.querySelectorAll('.reports-sort[data-sort-key]'));

        const modalOverlay = document.getElementById('daily-modal-overlay');
        const modalEl = document.getElementById('daily-modal');
        const modalClose = document.getElementById('daily-modal-close');
        const modalGotoEdit = document.getElementById('daily-goto-edit');
        const modalCancelEdit = document.getElementById('daily-cancel-edit');
        const modalFooterView = document.getElementById('daily-modal-footer-view');
        const modalFooterEdit = document.getElementById('daily-modal-footer-edit');
        const panelView = document.getElementById('daily-panel-view');
        const panelEdit = document.getElementById('daily-panel-edit');
        const viewDetails = document.getElementById('daily-view-details');
        const stepperHost = document.getElementById('daily-stepper');
        const editForm = document.getElementById('daily-edit-form');

        const guideOpen = document.getElementById('daily-guide-open');
        const guideOverlay = document.getElementById('daily-guide-overlay');
        const guideClose = document.getElementById('daily-guide-close');
        const guideModal = document.getElementById('daily-guide-modal');

        const STATUS_SORT = { pending: 1, nte: 2, on_hold: 3, resolved: 4, dismissed: 5 };

        let activeStatus = document.body.dataset.statusTab || 'all';
        if (!activeStatus) {
            activeStatus = 'all';
        }

        let currentRecordId = document.body.dataset.openRecord || '';
        let currentMode = document.body.dataset.openMode || 'view';
        let sortKey = 'shift';
        let sortDir = 'desc';

        function getRows() {
            return Array.from(root.querySelectorAll('[data-attendance-row]'));
        }

        function buildIndex() {
            return getRows().map((row) => ({
                el: row,
                id: row.dataset.id || '',
                ref: row.dataset.ref || '',
                status: row.dataset.status || '',
                issue: row.dataset.issue || '',
                recorded: row.dataset.recorded || '',
                submittedAt: row.dataset.submittedAt || '',
                shiftDate: row.dataset.shiftDate || '',
                searchBlob: (row.dataset.search || '').toLowerCase(),
                payload: recordsById[row.dataset.id || ''] || safeParse(row.dataset.detail),
                sort: {
                    ref: (row.dataset.ref || '').toLowerCase(),
                    guard: (row.dataset.sortGuard || '').toLowerCase(),
                    post: (row.querySelector('.reports-col-post')?.textContent || '').toLowerCase(),
                    shift: row.dataset.shiftDate || '',
                    issue: (row.dataset.sortIssue || '').toLowerCase(),
                    submitted: row.dataset.submittedAt || '',
                    status: STATUS_SORT[row.dataset.status || ''] ?? 99,
                },
            }));
        }

        let recordsIndex = buildIndex();

        function statusMatchesTab(status, tab) {
            return tab === 'all' || status === tab;
        }

        function inDateRange(iso, from, to) {
            if (!iso) {
                return true;
            }
            if (from && iso < from) {
                return false;
            }
            if (to && iso > to) {
                return false;
            }
            return true;
        }

        function updateKpis() {
            const counts = { all: 0, pending: 0, nte: 0, on_hold: 0, resolved: 0, dismissed: 0 };
            recordsIndex.forEach((r) => {
                if (r.el.classList.contains('is-filtered-out')) {
                    return;
                }
                counts.all += 1;
                if (counts[r.status] !== undefined) {
                    counts[r.status] += 1;
                }
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
                if (!badge) {
                    return;
                }
                if (status === 'all') {
                    badge.textContent = String(recordsIndex.length);
                    return;
                }
                const n = recordsIndex.filter((r) => r.status === status).length;
                badge.textContent = String(n);
            });
        }

        function compareRows(a, b) {
            let cmp = 0;
            const va = a.sort[sortKey];
            const vb = b.sort[sortKey];
            if (sortKey === 'status') {
                cmp = (Number(va) || 99) - (Number(vb) || 99);
            } else {
                cmp = String(va).localeCompare(String(vb));
            }
            return sortDir === 'asc' ? cmp : -cmp;
        }

        function updateSortHeaderUi() {
            const labels = {
                ref: 'Reference',
                guard: 'Guard',
                post: 'Post',
                shift: 'Shift',
                issue: 'Issue',
                submitted: 'Flagged',
                status: 'Status',
            };
            sortButtons.forEach((btn) => {
                const key = btn.dataset.sortKey || '';
                const th = btn.closest('th');
                const icon = btn.querySelector('.reports-sort__icon');
                const label = labels[key] || key;
                const active = key === sortKey;
                btn.classList.toggle('is-active', active);
                if (!th) {
                    return;
                }
                if (!active) {
                    th.setAttribute('aria-sort', 'none');
                    if (icon) {
                        icon.className = 'reports-sort__icon reports-sort__icon--idle';
                    }
                    return;
                }
                const ascending = sortDir === 'asc';
                th.setAttribute('aria-sort', ascending ? 'ascending' : 'descending');
                if (icon) {
                    icon.className =
                        'reports-sort__icon reports-sort__icon--' + (ascending ? 'asc' : 'desc');
                }
            });
        }

        function applySort() {
            if (!tbody) {
                return;
            }
            const sorted = [...recordsIndex].sort(compareRows);
            const frag = document.createDocumentFragment();
            sorted.forEach((r) => frag.appendChild(r.el));
            tbody.appendChild(frag);
            updateSortHeaderUi();
        }

        function setSort(nextKey) {
            if (!nextKey) {
                return;
            }
            if (sortKey === nextKey) {
                sortDir = sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                sortKey = nextKey;
                sortDir = nextKey === 'shift' || nextKey === 'submitted' ? 'desc' : 'asc';
            }
            applySort();
        }

        function applyFilters() {
            const q = (searchInput?.value || '').trim().toLowerCase();
            const issue = issueSelect?.value || 'all';
            const from = dateFrom?.value || '';
            const to = dateTo?.value || '';
            let visible = 0;

            recordsIndex.forEach((r) => {
                let show = true;
                if (activeStatus !== 'all' && !statusMatchesTab(r.status, activeStatus)) {
                    show = false;
                }
                if (show && issue !== 'all' && r.issue !== issue) {
                    show = false;
                }
                if (show && q && !r.searchBlob.includes(q)) {
                    show = false;
                }
                if (show && !inDateRange(r.shiftDate, from, to)) {
                    show = false;
                }

                r.el.classList.toggle('is-hidden', !show);
                r.el.classList.toggle('is-filtered-out', !show);
                if (show) {
                    visible += 1;
                }
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
            window.history.replaceState(null, '', url.pathname + url.search);
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

        function statusBadgeHtml(p) {
            const slug = p.status || 'pending';
            const label = p.status_label || slug;
            return (
                '<span class="reports-badge reports-badge--' +
                escapeHtml(slug) +
                '">' +
                escapeHtml(label) +
                '</span>'
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
            const months = [
                'Jan',
                'Feb',
                'Mar',
                'Apr',
                'May',
                'Jun',
                'Jul',
                'Aug',
                'Sep',
                'Oct',
                'Nov',
                'Dec',
            ];
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

        function recordedBadgeHtml(p) {
            const slug = p.recorded || 'missing';
            const short = { present: '1.00', late: '0.50', absent: '0.00', missing: 'N/A' }[slug] || 'N/A';
            return (
                '<span class="reports-equiv reports-equiv--' +
                escapeHtml(slug) +
                '" title="' +
                escapeHtml(p.recorded_label || '') +
                '">' +
                escapeHtml(short) +
                '</span>'
            );
        }

        function locationBlockHtml(title, lat, lng, accuracy, label) {
            if (lat == null && lng == null && !label) {
                return '';
            }
            let html =
                '<div class="reports-detail-about reports-dad-loc"><h4 class="reports-detail-about__title">' +
                escapeHtml(title) +
                '</h4>';
            if (label) {
                html += '<p>' + escapeHtml(label) + '</p>';
            }
            if (lat != null && lng != null) {
                html +=
                    '<p class="mono">' +
                    escapeHtml(lat.toFixed(6) + ', ' + lng.toFixed(6));
                if (accuracy != null) {
                    html += ' <span class="reports-optional">(±' + escapeHtml(String(Math.round(accuracy))) + ' m)</span>';
                }
                html += '</p>';
                const mapUrl =
                    'https://www.google.com/maps?q=' + encodeURIComponent(lat + ',' + lng);
                html +=
                    '<p><a href="' +
                    escapeHtml(mapUrl) +
                    '" target="_blank" rel="noopener noreferrer">Open in Google Maps</a></p>';
            }
            html += '</div>';
            return html;
        }

        function dadDisplayFields(structured, displayFieldsOverride) {
            if (Array.isArray(displayFieldsOverride) && displayFieldsOverride.length) {
                return displayFieldsOverride.filter((f) => f && String(f.value || '').trim() !== '');
            }
            if (!structured || typeof structured !== 'object') {
                return [];
            }
            if (Array.isArray(structured.display_fields) && structured.display_fields.length) {
                return structured.display_fields.filter((f) => f && String(f.value || '').trim() !== '');
            }
            const out = [];
            const post = String(structured.post || '').trim();
            if (post) {
                out.push({ label: 'POST', value: post });
            }
            const dates = Array.isArray(structured.dates) ? structured.dates : [];
            if (dates.length) {
                out.push({ label: 'DATE', value: dates.join(' · ') });
            }
            const rows = Array.isArray(structured.attendance_rows) ? structured.attendance_rows : [];
            rows.forEach((row, i) => {
                if (!row || typeof row !== 'object') {
                    return;
                }
                const suffix = rows.length > 1 ? ' (' + (i + 1) + ')' : '';
                const name = String(row.name || '').trim();
                const tin = String(row.time_in || '').trim();
                const tout = String(row.time_out || '').trim();
                if (name) {
                    out.push({ label: 'NAME' + suffix, value: name });
                }
                if (tin) {
                    out.push({ label: 'TIME IN' + suffix, value: tin });
                }
                if (tout) {
                    out.push({ label: 'TIME OUT' + suffix, value: tout });
                }
            });
            return out;
        }

        function ocrStructuredHtml(structured, formatted, raw, displayFieldsOverride) {
            const fields = dadDisplayFields(structured, displayFieldsOverride);
            if (fields.length) {
                let html = '<div class="reports-dad-ocr-form" aria-label="Extracted attendance sheet fields">';
                fields.forEach((field) => {
                    html += '<div class="reports-dad-ocr-form__row">';
                    html += '<span class="reports-dad-ocr-form__label">' + escapeHtml(field.label || '') + '</span>';
                    html += '<span class="reports-dad-ocr-form__value">' + escapeHtml(field.value || '') + '</span>';
                    html += '</div>';
                });
                html += '</div>';
                return html;
            }
            const body = (formatted || raw || '').trim();
            if (body) {
                return '<pre class="reports-dad-media__ocr">' + escapeHtml(body) + '</pre>';
            }
            return '<p class="reports-dad-media__hint">Document AI did not return readable text for this scan.</p>';
        }

        function mediaBlockHtml(p) {
            const scanUrl = p.scan_url || '';
            const dadId = p.dad_id != null ? Number(p.dad_id) : 0;
            if (!scanUrl && !dadId) {
                return '';
            }
            const formatted = (p.ocr_formatted || '').trim();
            const raw = (p.ocr_raw || '').trim();
            const structured = p.ocr_structured && typeof p.ocr_structured === 'object' ? p.ocr_structured : {};
            const hasOcr = formatted !== '' || raw !== '';
            const ocrBody = ocrStructuredHtml(structured, formatted, raw, p.ocr_display_fields);

            let html =
                '<div class="reports-dad-step1" data-dad-step1' +
                (dadId > 0 ? ' data-dad-id="' + escapeHtml(String(dadId)) + '"' : '') +
                '>';
            html += '<p class="reports-dad-step1__label">Step 1 — Attendance sheet</p>';
            html += '<div class="reports-dad-step1__tabs" role="tablist" aria-label="Attendance sheet and OCR">';
            html +=
                '<button type="button" class="reports-dad-step1__tab is-active" role="tab" aria-selected="true" data-dad-tab="sheet">Sheet image</button>';
            html +=
                '<button type="button" class="reports-dad-step1__tab" role="tab" aria-selected="false" data-dad-tab="ocr">Extracted text</button>';
            html += '</div><div class="reports-dad-step1__panels">';
            html += '<div class="reports-dad-step1__panel is-active" role="tabpanel" data-dad-panel="sheet">';
            if (scanUrl) {
                html +=
                    '<a href="' +
                    escapeHtml(scanUrl) +
                    '" target="_blank" rel="noopener noreferrer" class="reports-dad-media__link"><img class="reports-dad-media__scan" src="' +
                    escapeHtml(scanUrl) +
                    '" alt="Uploaded attendance sheet"></a>';
            } else {
                html += '<p class="reports-dad-media__hint">No scan image on file.</p>';
            }
            html += '</div>';
            html += '<div class="reports-dad-step1__panel" role="tabpanel" data-dad-panel="ocr" hidden>';
            if (hasOcr) {
                html += ocrBody;
            } else {
                html += '<div class="reports-dad-ocr-empty" data-dad-ocr-empty>';
                html +=
                    '<p class="reports-dad-media__hint">Handwriting is read with Google Document AI. Open this tab to extract text from the sheet.</p>';
                if (dadId > 0 && scanUrl) {
                    html +=
                        '<button type="button" class="reports-btn reports-btn--secondary reports-dad-ocr-run" data-dad-ocr-run>Extract text now</button>';
                }
                html += '<p class="reports-dad-ocr-status" data-dad-ocr-status hidden></p></div>';
            }
            html += '</div></div></div>';
            return html;
        }

        function setDadStep1Tab(step1Root, tabKey) {
            if (!step1Root) {
                return;
            }
            step1Root.querySelectorAll('[data-dad-tab]').forEach((btn) => {
                const active = btn.getAttribute('data-dad-tab') === tabKey;
                btn.classList.toggle('is-active', active);
                btn.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            step1Root.querySelectorAll('[data-dad-panel]').forEach((panel) => {
                const active = panel.getAttribute('data-dad-panel') === tabKey;
                panel.classList.toggle('is-active', active);
                panel.hidden = !active;
            });
        }

        let dadOcrBusy = false;

        function runDadOcr(record, step1Root) {
            const dadId = record.dad_id != null ? Number(record.dad_id) : 0;
            const ocrUrl = root.dataset.ocrUrl || '';
            const csrf = root.dataset.csrf || '';
            if (!dadId || !ocrUrl || dadOcrBusy) {
                return Promise.resolve(null);
            }

            const statusEl = step1Root?.querySelector('[data-dad-ocr-status]');
            const ocrPanel = step1Root?.querySelector('[data-dad-panel="ocr"]');
            dadOcrBusy = true;
            if (statusEl) {
                statusEl.hidden = false;
                statusEl.className = 'reports-dad-ocr-status is-busy';
                statusEl.textContent = 'Reading handwriting with Document AI…';
            }

            const fd = new FormData();
            fd.append('dad_id', String(dadId));
            if (csrf) {
                fd.append('_csrf', csrf);
            }

            return fetch(ocrUrl, {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                    'X-CSRF-Token': csrf,
                },
            })
                .then((r) => r.json().then((data) => ({ ok: r.ok, data })))
                .then(({ ok, data }) => {
                    dadOcrBusy = false;
                    if (!ok || !data.ok) {
                        if (statusEl) {
                            statusEl.className = 'reports-dad-ocr-status is-error';
                            statusEl.textContent = data.error || 'OCR failed.';
                        }
                        return null;
                    }
                    record.ocr_formatted = data.formatted || '';
                    record.ocr_raw = data.raw || '';
                    record.ocr_structured = data.structured || {};
                    record.ocr_display_fields = data.display_fields || [];
                    record.has_ocr = true;
                    if (record.id) {
                        recordsById[record.id] = record;
                    }
                    if (ocrPanel) {
                        ocrPanel.innerHTML = ocrStructuredHtml(
                            record.ocr_structured,
                            record.ocr_formatted,
                            record.ocr_raw,
                            record.ocr_display_fields
                        );
                    }
                    if (statusEl) {
                        statusEl.hidden = true;
                    }
                    return record;
                })
                .catch(() => {
                    dadOcrBusy = false;
                    if (statusEl) {
                        statusEl.className = 'reports-dad-ocr-status is-error';
                        statusEl.textContent = 'Could not reach Document AI. Check credentials and try again.';
                    }
                    return null;
                });
        }

        function bindDadStep1Tabs(container, record) {
            const step1 = container?.querySelector('[data-dad-step1]');
            if (!step1) {
                return;
            }

            step1.addEventListener('click', (e) => {
                const tabBtn = e.target.closest('[data-dad-tab]');
                if (tabBtn && step1.contains(tabBtn)) {
                    e.preventDefault();
                    const key = tabBtn.getAttribute('data-dad-tab') || 'sheet';
                    setDadStep1Tab(step1, key);
                    if (
                        key === 'ocr' &&
                        record &&
                        !(record.ocr_formatted || record.ocr_raw || '').trim() &&
                        record.scan_url
                    ) {
                        runDadOcr(record, step1);
                    }
                    return;
                }
                const runBtn = e.target.closest('[data-dad-ocr-run]');
                if (runBtn && step1.contains(runBtn)) {
                    e.preventDefault();
                    runDadOcr(record, step1);
                }
            });
        }

        function renderViewDetails(p, options) {
            if (!viewDetails) {
                return;
            }
            const forceClient = options && options.forceClient === true;
            if (!forceClient && p && typeof p.view_html === 'string' && p.view_html.trim() !== '') {
                viewDetails.innerHTML = p.view_html;
                bindDadStep1Tabs(viewDetails, p);
                return;
            }
            const pairs = [
                ['Guard', (p.guard_name || '—') + (p.guard_id ? ' (' + p.guard_id + ')' : '')],
                ['Post', p.post || '—'],
                ['Shift', p.shift_display || p.shift_date || '—'],
                ['Issue', p.issue_label || '—'],
                ['Time record', p.time_record || '—'],
                ['Equivalence', p.recorded_label || '—'],
                ['Status', p.status_label || '—'],
                ['Head guard', p.head_guard_name || '—'],
                ['Submitted', p.submitted_display || '—'],
                ['Updated', p.updated_display || '—'],
            ];
            let html = mediaBlockHtml(p);
            html += '<dl class="reports-detail-grid">';
            pairs.forEach(([label, value]) => {
                html +=
                    '<div class="reports-detail-item"><dt class="reports-detail-label">' +
                    escapeHtml(label) +
                    '</dt><dd class="reports-detail-value">' +
                    escapeHtml(value) +
                    '</dd></div>';
            });
            html += '</dl>';
            html += locationBlockHtml(
                'Location — attendance sheet (step 1)',
                p.sheet_latitude != null ? Number(p.sheet_latitude) : null,
                p.sheet_longitude != null ? Number(p.sheet_longitude) : null,
                p.sheet_accuracy_m != null ? Number(p.sheet_accuracy_m) : null,
                p.sheet_location_label || ''
            );
            html += locationBlockHtml(
                'Location — site evidence (step 2)',
                p.evidence_latitude != null ? Number(p.evidence_latitude) : null,
                p.evidence_longitude != null ? Number(p.evidence_longitude) : null,
                p.evidence_accuracy_m != null ? Number(p.evidence_accuracy_m) : null,
                p.evidence_location_label || ''
            );
            html += '<div class="reports-detail-about"><h4 class="reports-detail-about__title">Summary</h4><p>';
            html += escapeHtml(p.summary || '—');
            html += '</p></div>';
            viewDetails.innerHTML = html;
            bindDadStep1Tabs(viewDetails, p);
        }

        function renderHistory(history, record) {
            if (!stepperHost) {
                return;
            }
            if (record && typeof record.history_html === 'string' && record.history_html.trim() !== '') {
                stepperHost.innerHTML = record.history_html;
                return;
            }
            if (!history || !history.length) {
                stepperHost.innerHTML = '<p class="reports-timeline-empty">No history yet.</p>';
                return;
            }
            let html = '<ol class="reports-timeline">';
            history.forEach((entry, i) => {
                const isLast = i === history.length - 1;
                html +=
                    '<li class="reports-timeline__item' +
                    (isLast ? ' is-current' : '') +
                    '"><div class="reports-timeline-detail">';
                html +=
                    '<span class="reports-timeline-detail__time">' +
                    escapeHtml(entry.at || '') +
                    '</span>';
                html +=
                    '<span class="reports-timeline-detail__event">' +
                    escapeHtml(entry.event || '') +
                    '</span>';
                if (entry.note) {
                    html +=
                        '<p class="reports-timeline-detail__note">' +
                        escapeHtml(entry.note) +
                        '</p>';
                }
                html += '</div></li>';
            });
            html += '</ol>';
            stepperHost.innerHTML = html;
        }

        function populateEditForm(p) {
            const set = (id, val) => {
                const el = document.getElementById(id);
                if (el) {
                    el.value = val ?? '';
                }
            };
            set('edit-record-id', p.id);
            set('edit-status', p.status);
            set('edit-recorded', p.recorded);
            set('edit-issue', p.issue);
            set('edit-post', p.post);
            set('edit-time-record', p.time_record);
            set('edit-summary', p.summary);
            set('edit-ops-note', '');
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

        function pushUrl(id, mode) {
            const url = new URL(window.location.href);
            if (id) {
                url.searchParams.set('record', id);
                url.searchParams.set('mode', mode || 'view');
            } else {
                url.searchParams.delete('record');
                url.searchParams.delete('mode');
            }
            window.history.pushState(
                { record: id, mode: mode || 'view', status: activeStatus },
                '',
                url.pathname + url.search
            );
        }

        function updateRowFromPayload(p) {
            const row = recordsIndex.find((r) => r.id === p.id);
            if (!row || !row.el) {
                return;
            }
            row.status = p.status || '';
            row.issue = p.issue || '';
            row.recorded = p.recorded || '';
            row.searchBlob = [
                p.ref,
                p.guard_id,
                p.guard_name,
                p.post,
                p.issue_label,
                p.time_record,
                p.recorded_label,
                p.summary,
                p.head_guard_name,
            ]
                .join(' ')
                .toLowerCase();
            row.payload = p;
            row.sort.status = STATUS_SORT[p.status] ?? 99;
            row.sort.issue = (p.issue_label || '').toLowerCase();
            row.sort.guard = (p.guard_name || '').toLowerCase();
            row.sort.shift = p.shift_date || '';
            row.sort.submitted = p.submitted_at || '';
            row.el.dataset.status = p.status || '';
            row.el.dataset.issue = p.issue || '';
            row.el.dataset.recorded = p.recorded || '';
            row.el.dataset.search = row.searchBlob;
            row.el.dataset.detail = JSON.stringify(p);
            row.el.dataset.shiftDate = p.shift_date || '';
            row.el.dataset.submittedAt = p.submitted_at || '';
            row.el.dataset.updatedAt = (p.updated_at || '').substring(0, 10);

            const cells = row.el.querySelectorAll('td');
            if (cells.length >= 9) {
                cells[1].innerHTML =
                    '<div class="reports-hg-cell"><span class="reports-hg-name">' +
                    escapeHtml(p.guard_name || '') +
                    '</span><span class="reports-hg-username mono" title="Employee ID">' +
                    escapeHtml(p.guard_id || '') +
                    '</span></div>';
                cells[2].textContent = p.post || '';
                cells[3].textContent = p.shift_date || '';
                cells[3].title = p.shift_display || '';
                cells[4].innerHTML =
                    '<span class="reports-issue-label">' + escapeHtml(p.issue_label || '') + '</span>';
                cells[5].innerHTML =
                    '<span class="reports-time-record">' + escapeHtml(p.time_record || '') + '</span>';
                cells[6].innerHTML = recordedBadgeHtml(p);
                cells[7].innerHTML = tableDateCellHtml(p.submitted_at, p.submitted_display);
                cells[8].innerHTML = statusBadgeHtml(p);
            }
            recordsById[p.id] = p;
        }

        function openRecord(id, mode) {
            if (!id || !modalOverlay) {
                return;
            }
            let p = recordsById[id];
            if (!p) {
                const row = recordsIndex.find((r) => r.id === id);
                p = row?.payload || null;
            }
            if (!p) {
                window.alert('Could not load this DAD record. Refresh the page and try again.');
                return;
            }
            recordsById[id] = p;
            currentRecordId = id;
            setModalMode(mode || 'view');

            const refEl = document.getElementById('daily-modal-ref');
            if (refEl) {
                refEl.textContent = p.ref || '—';
            }
            const statusWrap = document.getElementById('daily-modal-status-wrap');
            if (statusWrap) {
                statusWrap.innerHTML = statusBadgeHtml(p);
            }

            renderViewDetails(p);
            renderHistory(p.history, p);
            populateEditForm(p);
            if (editForm) {
                editForm.hidden = false;
            }
            const placeholder = document.getElementById('daily-edit-placeholder');
            if (placeholder) {
                placeholder.hidden = true;
            }
            if (modalGotoEdit) {
                modalGotoEdit.hidden = false;
            }

            modalOverlay.classList.add('is-open');
            modalOverlay.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            pushUrl(id, mode || 'view');

            recordsIndex.forEach((r) => {
                r.el.classList.toggle('is-selected', r.id === id);
            });
        }

        function refreshTabCounts() {
            const counts = { all: 0 };
            tabs.forEach((t) => {
                const slug = t.dataset.statusTab || '';
                if (slug && slug !== 'all') {
                    counts[slug] = 0;
                }
            });
            recordsIndex.forEach((r) => {
                counts.all += 1;
                const slug = r.status || '';
                if (slug && counts[slug] !== undefined) {
                    counts[slug] += 1;
                }
            });
            tabs.forEach((tab) => {
                const slug = tab.dataset.statusTab || '';
                const countEl = tab.querySelector('.reports-status-tab__count');
                if (countEl && counts[slug] !== undefined) {
                    countEl.textContent = String(counts[slug]);
                }
            });
            Object.keys(counts).forEach((slug) => {
                const kpi = root.querySelector('[data-kpi="' + slug + '"]');
                if (kpi) {
                    kpi.textContent = String(counts[slug]);
                }
            });
        }

        function removeRecordFromUi(id) {
            const idx = recordsIndex.findIndex((r) => r.id === id);
            if (idx >= 0) {
                recordsIndex[idx].el.remove();
                recordsIndex.splice(idx, 1);
            }
            delete recordsById[id];
            if (dataEl && Array.isArray(allRecords)) {
                const dataIdx = allRecords.findIndex((r) => r && r.id === id);
                if (dataIdx >= 0) {
                    allRecords.splice(dataIdx, 1);
                    dataEl.textContent = JSON.stringify(allRecords);
                }
            }
            refreshTabCounts();
            applyFilters();
        }

        function deleteRecord(id, refLabel) {
            const deleteUrl = root.dataset.deleteUrl || window.location.pathname;
            const csrf = root.dataset.csrf || '';
            const label = refLabel || id || 'this record';
            const confirmed = window.confirm(
                'Delete ' + label + '? This removes the DAD registry entry. The guard report in Reports is not deleted.'
            );
            if (!confirmed) {
                return;
            }

            const fd = new FormData();
            fd.append('action', 'delete_attendance');
            fd.append('record_id', id);
            if (csrf) {
                fd.append('_csrf', csrf);
            }

            fetch(deleteUrl, {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            })
                .then((r) => r.json().then((data) => ({ ok: r.ok, data })))
                .then(({ ok, data }) => {
                    if (!ok || !data.ok) {
                        window.alert(data.error || 'Could not delete record.');
                        return;
                    }
                    if (currentRecordId === id) {
                        closeModal();
                    }
                    removeRecordFromUi(id);
                    if (data.message && window.appNotify && typeof window.appNotify.success === 'function') {
                        window.appNotify.success(data.message);
                    }
                })
                .catch(() => {
                    window.alert('Could not delete record. Refresh the page and try again.');
                });
        }

        function closeModal() {
            if (!modalOverlay) {
                return;
            }
            modalOverlay.classList.remove('is-open');
            modalOverlay.setAttribute('aria-hidden', 'true');
            if (!guideOverlay?.classList.contains('is-open')) {
                document.body.style.overflow = '';
            }
            currentRecordId = '';
            pushUrl('', '');
            recordsIndex.forEach((r) => r.el.classList.remove('is-selected'));
        }

        function openGuide() {
            if (!guideOverlay) {
                return;
            }
            closeModal();
            guideOverlay.classList.add('is-open');
            guideOverlay.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closeGuide() {
            if (!guideOverlay) {
                return;
            }
            guideOverlay.classList.remove('is-open');
            guideOverlay.setAttribute('aria-hidden', 'true');
            if (!modalOverlay?.classList.contains('is-open')) {
                document.body.style.overflow = '';
            }
        }

        tabs.forEach((tab) => tab.addEventListener('click', () => setActiveTab(tab)));

        [searchInput, issueSelect, dateFrom, dateTo].forEach((el) => {
            if (!el) {
                return;
            }
            el.addEventListener('input', applyFilters);
            el.addEventListener('change', applyFilters);
        });

        resetBtn?.addEventListener('click', () => {
            if (searchInput) {
                searchInput.value = '';
            }
            if (issueSelect) {
                issueSelect.value = 'all';
            }
            if (dateFrom) {
                dateFrom.value = '2026-04-01';
            }
            if (dateTo) {
                dateTo.value = new Date().toISOString().slice(0, 10);
            }
            sortKey = 'shift';
            sortDir = 'desc';
            const allTab = tabs.find((t) => t.dataset.statusTab === 'all');
            if (allTab) {
                setActiveTab(allTab);
            } else {
                applyFilters();
            }
        });

        sortButtons.forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                setSort(btn.dataset.sortKey || '');
            });
        });

        root.addEventListener('click', (e) => {
            const viewBtn = e.target.closest('[data-action="view"]');
            const editBtn = e.target.closest('[data-action="edit"]');
            const deleteBtn = e.target.closest('[data-action="delete"]');
            if (viewBtn) {
                e.preventDefault();
                e.stopPropagation();
                const rid = viewBtn.getAttribute('data-record-id') || viewBtn.dataset.recordId || '';
                openRecord(rid, 'view');
                return;
            }
            if (editBtn) {
                e.preventDefault();
                e.stopPropagation();
                const rid = editBtn.getAttribute('data-record-id') || editBtn.dataset.recordId || '';
                openRecord(rid, 'edit');
                return;
            }
            if (deleteBtn) {
                e.preventDefault();
                e.stopPropagation();
                const rid = deleteBtn.getAttribute('data-record-id') || deleteBtn.dataset.recordId || '';
                const ref =
                    deleteBtn.getAttribute('data-record-ref') || deleteBtn.dataset.recordRef || '';
                deleteRecord(rid, ref);
                return;
            }
            const row = e.target.closest('[data-attendance-row]');
            if (row && !e.target.closest('.reports-actions')) {
                openRecord(row.getAttribute('data-id') || row.dataset.id || '', 'view');
            }
        });

        modalGotoEdit?.addEventListener('click', () => {
            if (currentRecordId) {
                openRecord(currentRecordId, 'edit');
            }
        });
        modalCancelEdit?.addEventListener('click', () => {
            if (currentRecordId) {
                openRecord(currentRecordId, 'view');
            }
        });
        modalClose?.addEventListener('click', closeModal);
        modalEl?.addEventListener('click', (e) => e.stopPropagation());
        modalOverlay?.addEventListener('click', (e) => {
            if (e.target === modalOverlay) {
                closeModal();
            }
        });

        guideOpen?.addEventListener('click', openGuide);
        guideClose?.addEventListener('click', closeGuide);
        guideOverlay?.addEventListener('click', (e) => {
            if (e.target === guideOverlay) {
                closeGuide();
            }
        });
        guideModal?.addEventListener('click', (e) => e.stopPropagation());

        document.addEventListener('keydown', (e) => {
            if (e.key !== 'Escape') {
                return;
            }
            if (guideOverlay?.classList.contains('is-open')) {
                closeGuide();
                return;
            }
            if (modalOverlay?.classList.contains('is-open')) {
                closeModal();
            }
        });

        window.addEventListener('popstate', (e) => {
            const state = e.state || {};
            const url = new URL(window.location.href);
            const statusFromUrl = url.searchParams.get('status');
            const statusKey =
                statusFromUrl !== null
                    ? statusFromUrl || 'all'
                    : state.status
                      ? state.status
                      : null;
            if (statusKey !== null && statusKey !== activeStatus) {
                const tabEl = tabs.find((t) => t.dataset.statusTab === statusKey);
                if (tabEl) {
                    setActiveTab(tabEl);
                }
            }

            const id = state.record || url.searchParams.get('record') || '';
            const mode = state.mode || url.searchParams.get('mode') || 'view';
            if (id && (recordsById[id] || recordsIndex.find((r) => r.id === id)?.payload)) {
                openRecord(id, mode);
            } else {
                closeModal();
            }
        });

        const initialTab = document.body.dataset.statusTab;
        if (initialTab) {
            const tabEl = tabs.find((t) => t.dataset.statusTab === initialTab);
            if (tabEl) {
                setActiveTab(tabEl);
            } else {
                applyFilters();
            }
        } else {
            applyFilters();
        }

        if (currentRecordId && (recordsById[currentRecordId] || recordsIndex.find((r) => r.id === currentRecordId)?.payload)) {
            if (modalOverlay?.classList.contains('is-open')) {
                document.body.style.overflow = 'hidden';
                const p = recordsById[currentRecordId] || recordsIndex.find((r) => r.id === currentRecordId)?.payload;
                if (p) {
                    recordsById[currentRecordId] = p;
                    setModalMode(currentMode);
                    renderViewDetails(p);
                    renderHistory(p.history, p);
                    populateEditForm(p);
                    recordsIndex.forEach((r) => {
                        r.el.classList.toggle('is-selected', r.id === currentRecordId);
                    });
                }
            } else {
                openRecord(currentRecordId, currentMode);
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDailyDetailModule);
    } else {
        initDailyDetailModule();
    }
})();
