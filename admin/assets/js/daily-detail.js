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
        const root = document.querySelector('#reports-module[data-registry-kind="dtr"]');
        if (!root) {
            return;
        }

        const isReinit = root.dataset.dailyBound === '1';
        if (!isReinit) {
            root.dataset.dailyBound = '1';
        }

        if (window.__dailyDetailAbort) {
            window.__dailyDetailAbort.abort();
        }
        window.__dailyDetailAbort = new AbortController();
        const panelSignal = window.__dailyDetailAbort.signal;

        const tableHeadWrap = document.getElementById('reports-table-head-wrap');
        const tableBodyWrap = document.getElementById('reports-table-body-wrap');
        if (!isReinit && tableHeadWrap && tableBodyWrap) {
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

        const STATUS_SORT = { ongoing: 1, on_hold: 2, accomplished: 3, denied: 4 };

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
            const counts = { all: 0, ongoing: 0, on_hold: 0, accomplished: 0, denied: 0 };
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
            const slug = p.status || 'ongoing';
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

        function dadLocCardHtml(title, lat, lng, accuracy, label) {
            if (lat == null && lng == null && !label) {
                return '';
            }
            let html = '<article class="reports-dad-loc-card">';
            html += '<h4 class="reports-dad-loc-card__title">' + escapeHtml(title) + '</h4>';
            if (label) {
                html += '<p class="reports-dad-loc-card__label">' + escapeHtml(label) + '</p>';
            }
            if (lat != null && lng != null) {
                html +=
                    '<p class="reports-dad-loc-card__coords mono">' +
                    escapeHtml(lat.toFixed(6) + ', ' + lng.toFixed(6));
                if (accuracy != null) {
                    html += ' <span class="reports-optional">(±' + escapeHtml(String(Math.round(accuracy))) + ' m)</span>';
                }
                html += '</p>';
                const mapUrl =
                    'https://www.google.com/maps?q=' + encodeURIComponent(lat + ',' + lng);
                html +=
                    '<p class="reports-dad-loc-card__map"><a href="' +
                    escapeHtml(mapUrl) +
                    '" target="_blank" rel="noopener noreferrer">Open in Google Maps</a></p>';
            } else {
                html += '<p class="reports-dad-loc-card__empty">No GPS coordinates captured.</p>';
            }
            html += '</article>';
            return html;
        }

        function dadLocationsSectionHtml(p) {
            const sheet = dadLocCardHtml(
                'Attendance sheet capture',
                p.sheet_latitude != null ? Number(p.sheet_latitude) : null,
                p.sheet_longitude != null ? Number(p.sheet_longitude) : null,
                p.sheet_accuracy_m != null ? Number(p.sheet_accuracy_m) : null,
                p.sheet_location_label || ''
            );
            const evidence = dadLocCardHtml(
                'Site evidence (step 2)',
                p.evidence_latitude != null
                    ? Number(p.evidence_latitude)
                    : p.submit_latitude != null
                      ? Number(p.submit_latitude)
                      : null,
                p.evidence_longitude != null
                    ? Number(p.evidence_longitude)
                    : p.submit_longitude != null
                      ? Number(p.submit_longitude)
                      : null,
                p.evidence_accuracy_m != null
                    ? Number(p.evidence_accuracy_m)
                    : p.submit_accuracy_m != null
                      ? Number(p.submit_accuracy_m)
                      : null,
                p.evidence_location_label || p.location_label || ''
            );
            if (!sheet && !evidence) {
                return '';
            }
            return (
                '<section class="reports-detail-sheet__section" aria-label="Capture locations">' +
                '<h4 class="reports-dad-section-heading">Locations</h4>' +
                '<div class="reports-dad-locations">' +
                sheet +
                evidence +
                '</div></section>'
            );
        }

        function dadSheetFieldHtml(label, value, modifier) {
            const trimmed = String(value || '').trim();
            const mod = modifier ? ' reports-detail-sheet__field--' + modifier : '';
            const empty = trimmed === '' ? ' is-empty' : '';
            return (
                '<div class="reports-detail-sheet__field' +
                mod +
                empty +
                '"><span class="reports-detail-sheet__label">' +
                escapeHtml(label) +
                '</span><span class="reports-detail-sheet__value">' +
                escapeHtml(trimmed !== '' ? trimmed : '—') +
                '</span></div>'
            );
        }

        function dadRecordSheetHtml(p) {
            let guard = (p.guard_name || '').trim();
            const guardId = (p.guard_id || '').trim();
            if (guard && guardId) {
                guard += ' (' + guardId + ')';
            } else if (!guard && guardId) {
                guard = guardId;
            }
            const summary = (p.summary || '').trim();
            let html =
                '<div class="reports-dad-below"><div class="reports-detail-sheet" role="group" aria-label="DTR record details">';
            html += '<section class="reports-detail-sheet__section" aria-label="Assignment">';
            html += '<h4 class="reports-dad-section-heading">Assignment</h4>';
            html += '<div class="reports-detail-sheet__grid reports-detail-sheet__grid--people">';
            html += dadSheetFieldHtml('Guard', guard);
            html += dadSheetFieldHtml('Post', p.post || '');
            html += dadSheetFieldHtml('Head guard', p.head_guard_name || '');
            html += '</div></section>';
            html += '<section class="reports-detail-sheet__section" aria-label="Timekeeping">';
            html += '<h4 class="reports-dad-section-heading">Timekeeping</h4>';
            html += '<div class="reports-detail-sheet__grid reports-dad-sheet__grid--timekeeping">';
            html += dadSheetFieldHtml('Shift', p.shift_display || p.shift_date || '');
            html += dadSheetFieldHtml('Time record', p.time_record || '');
            html += dadSheetFieldHtml('Issue', p.issue_label || '');
            html += dadSheetFieldHtml('Equivalence', p.recorded_label || '');
            html += dadSheetFieldHtml('Status', p.status_label || '');
            html += dadSheetFieldHtml('Submitted', p.submitted_display || '');
            html += dadSheetFieldHtml('Updated', p.updated_display || '—');
            html += '</div></section>';
            html += dadLocationsSectionHtml(p);
            html +=
                '<section class="reports-detail-sheet__section reports-dad-summary' +
                (summary === '' ? ' is-empty' : '') +
                '" aria-label="Summary">';
            html += '<h4 class="reports-dad-section-heading">Summary</h4>';
            html +=
                '<p class="reports-dad-summary__body">' +
                escapeHtml(summary !== '' ? summary : 'No summary provided.') +
                '</p></section></div></div>';
            return html;
        }

        function dadIsNameTimeLabel(label) {
            const base = String(label || '')
                .replace(/\s+\(\d+\)$/, '')
                .trim()
                .toUpperCase();
            return base === 'NAME' || base === 'TIME IN' || base === 'TIME OUT';
        }

        function dadDisplayFields(structured, displayFieldsOverride) {
            if (Array.isArray(displayFieldsOverride) && displayFieldsOverride.length) {
                return displayFieldsOverride.filter(
                    (f) => f && dadIsNameTimeLabel(f.label) && String(f.value || '').trim() !== ''
                );
            }
            if (!structured || typeof structured !== 'object') {
                return [];
            }
            if (Array.isArray(structured.display_fields) && structured.display_fields.length) {
                return structured.display_fields.filter(
                    (f) => f && dadIsNameTimeLabel(f.label) && String(f.value || '').trim() !== ''
                );
            }
            const out = [];
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

        function dadHasOcrExport(record) {
            if (!record) {
                return false;
            }
            const fields = dadDisplayFields(record.ocr_structured, record.ocr_display_fields);
            return fields.length > 0;
        }

        function dadOcrExportUrl(record) {
            const base = root.dataset.ocrExportUrl || '';
            const dadId = record.dad_id != null ? Number(record.dad_id) : 0;
            if (!base || !dadId) {
                return '';
            }
            const sep = base.includes('?') ? '&' : '?';
            return base + sep + 'dad_id=' + encodeURIComponent(String(dadId));
        }

        function dadOcrExportActionsHtml(record) {
            if (!dadHasOcrExport(record)) {
                return '';
            }
            const csvUrl = dadOcrExportUrl(record);
            let html = '<div class="reports-dad-ocr-export" data-dad-ocr-export>';
            html +=
                '<p class="reports-dad-ocr-export__hint">Download a password-protected ZIP containing the CSV extract. A one-time password is emailed to your admin account — use it to open the ZIP, then open the CSV in Excel.</p>';
            html += '<div class="reports-dad-ocr-export__actions">';
            if (csvUrl) {
                html +=
                    '<a class="reports-btn reports-btn--secondary reports-dad-ocr-export-csv" href="' +
                    escapeHtml(csvUrl) +
                    '">Download protected CSV</a>';
            }
            html += '</div></div>';
            return html;
        }

        function dadStep1ExportFooterHtml(record) {
            if (!record || !dadHasOcrExport(record)) {
                return '';
            }
            return (
                '<div class="reports-dad-step1__footer" data-dad-step1-export>' +
                dadOcrExportActionsHtml(record) +
                '</div>'
            );
        }

        function ocrStructuredHtml(structured, formatted, raw, displayFieldsOverride, record) {
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
            const body = (formatted || '').trim();
            if (body) {
                return '<pre class="reports-dad-media__ocr">' + escapeHtml(body) + '</pre>';
            }
            return '<p class="reports-dad-media__hint">Could not read NAME, TIME IN, or TIME OUT from this sheet. Try a clearer photo of the attendance row.</p>';
        }

        function mediaBlockHtml(p) {
            const scanUrl = p.scan_url || '';
            if (!scanUrl) {
                return '';
            }

            return (
                '<div class="reports-dad-step1" data-dad-step1>' +
                '<p class="reports-dad-step1__label">Step 1 — Attendance sheet</p>' +
                '<div class="reports-dad-step1__sheet-only">' +
                '<a href="' +
                escapeHtml(scanUrl) +
                '" target="_blank" rel="noopener noreferrer" class="reports-dad-media__link">' +
                '<img class="reports-dad-media__scan" src="' +
                escapeHtml(scanUrl) +
                '" alt="Uploaded attendance sheet"></a>' +
                '<p class="reports-dad-media__hint">Review the uploaded sheet photo. Times and names are not extracted automatically.</p>' +
                '</div></div>'
            );
        }

        function bindDadStep1() {
            /* DTR admin view: sheet photo only (no OCR). */
        }

        function renderViewDetails(p, options) {
            if (!viewDetails) {
                return;
            }
            const forceClient = options && options.forceClient === true;
            if (!forceClient && p && typeof p.view_html === 'string' && p.view_html.trim() !== '') {
                viewDetails.innerHTML = p.view_html;
                bindDadStep1();
                return;
            }
            let html = mediaBlockHtml(p);
            html += dadRecordSheetHtml(p);
            viewDetails.innerHTML = html;
            bindDadStep1();
        }

        function historyEventLabel(event) {
            const raw = String(event || '').trim();
            if (!raw) {
                return 'Activity logged';
            }
            const registry = raw.match(/^Registry:\s*(.+)$/i);
            if (registry) {
                return 'Review status updated';
            }
            return raw;
        }

        function historyNoteText(event, note) {
            const rawEvent = String(event || '').trim();
            const rawNote = String(note || '').trim();
            const registry = rawEvent.match(/^Registry:\s*(.+)$/i);
            if (registry) {
                const statusLine = 'New status: ' + registry[1].trim();
                return rawNote ? statusLine + ' — ' + rawNote : statusLine;
            }
            return rawNote;
        }

        function historyDatetimeAttr(at) {
            const raw = String(at || '').trim();
            if (!raw) {
                return '';
            }
            const ts = Date.parse(raw);
            if (Number.isNaN(ts)) {
                return '';
            }
            return new Date(ts).toISOString();
        }

        function buildHistoryTimelineItemHtml(entry, isCurrent) {
            const event = String(entry.event || '');
            const title = historyEventLabel(event);
            const noteText = historyNoteText(event, entry.note);
            const at = String(entry.at || '').trim();
            const datetimeAttr = historyDatetimeAttr(at);
            let html =
                '<li class="reports-activity-timeline__item' +
                (isCurrent ? ' is-current' : '') +
                '"><div class="reports-activity-timeline__rail" aria-hidden="true">';
            html += '<span class="reports-activity-timeline__dot"></span></div>';
            html += '<div class="reports-activity-timeline__content">';
            if (at) {
                html += '<header class="reports-activity-timeline__meta"><time class="reports-activity-timeline__when"';
                if (datetimeAttr) {
                    html += ' datetime="' + escapeHtml(datetimeAttr) + '"';
                }
                html += '>' + escapeHtml(at) + '</time></header>';
            }
            html += '<p class="reports-activity-timeline__title">' + escapeHtml(title) + '</p>';
            if (noteText) {
                html += '<p class="reports-activity-timeline__note">' + escapeHtml(noteText) + '</p>';
            }
            html += '</div></li>';
            return html;
        }

        function buildHistoryTimelineHtml(history) {
            const list = Array.isArray(history) ? history.filter((e) => e && typeof e === 'object') : [];
            if (list.length === 0) {
                return '<p class="reports-activity-timeline__empty">No history yet.</p>';
            }
            const items = list
                .map((entry, index) => buildHistoryTimelineItemHtml(entry, index === list.length - 1))
                .join('');
            return '<ol class="reports-activity-timeline" role="list">' + items + '</ol>';
        }

        function renderHistory(history, record) {
            if (!stepperHost) {
                return;
            }
            if (record && typeof record.history_html === 'string' && record.history_html.trim() !== '') {
                stepperHost.innerHTML = record.history_html;
                return;
            }
            stepperHost.innerHTML = buildHistoryTimelineHtml(history);
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
                    '<span class="reports-issue-label">' + escapeHtml(p.issue_label || '—') + '</span>';
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
                window.alert('Could not load this DTR record. Refresh the page and try again.');
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

        function archiveRecord(id, refLabel) {
            const archiveUrl = root.dataset.archiveUrl || window.location.pathname;
            const csrf = root.dataset.csrf || '';
            const label = refLabel || id || 'this record';
            const confirmed = window.confirm(
                'Archive ' +
                    label +
                    '? The case will be marked Closed and removed from open tabs.'
            );
            if (!confirmed) {
                return;
            }

            const fd = new FormData();
            fd.append('action', 'archive_attendance');
            fd.append('record_id', id);
            if (csrf) {
                fd.append('_csrf', csrf);
            }

            fetch(archiveUrl, {
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
                        window.alert(data.error || 'Could not archive record.');
                        return;
                    }
                    const record = data.record;
                    if (record && record.id) {
                        updateRowFromPayload(record);
                        const idx = recordsIndex.findIndex((r) => r.id === record.id);
                        if (idx >= 0) {
                            const archiveBtn = recordsIndex[idx].el.querySelector('[data-action="archive"]');
                            archiveBtn?.remove();
                        }
                        if (dataEl && Array.isArray(allRecords)) {
                            const dataIdx = allRecords.findIndex((r) => r && r.id === record.id);
                            if (dataIdx >= 0) {
                                allRecords[dataIdx] = record;
                                dataEl.textContent = JSON.stringify(allRecords);
                            }
                        }
                        refreshTabCounts();
                        applyFilters();
                        if (currentRecordId === record.id) {
                            recordsById[record.id] = record;
                            openRecord(record.id, currentMode);
                        }
                    }
                    if (data.message && window.appNotify && typeof window.appNotify.success === 'function') {
                        window.appNotify.success(data.message);
                    }
                })
                .catch(() => {
                    window.alert('Could not archive record. Refresh the page and try again.');
                });
        }

        function deleteRecord(id, refLabel) {
            const deleteUrl = root.dataset.deleteUrl || window.location.pathname;
            const csrf = root.dataset.csrf || '';
            const label = refLabel || id || 'this record';
            const confirmed = window.confirm(
                'Delete ' + label + '? This removes the DTR registry entry. The guard report in Reports is not deleted.'
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
            const archiveBtn = e.target.closest('[data-action="archive"]');
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
            if (archiveBtn) {
                e.preventDefault();
                e.stopPropagation();
                const rid = archiveBtn.getAttribute('data-record-id') || archiveBtn.dataset.recordId || '';
                const ref =
                    archiveBtn.getAttribute('data-record-ref') || archiveBtn.dataset.recordRef || '';
                archiveRecord(rid, ref);
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

        document.addEventListener(
            'keydown',
            (e) => {
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
            },
            { signal: panelSignal }
        );

        window.addEventListener(
            'popstate',
            (e) => {
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
            },
            { signal: panelSignal }
        );

        if (isReinit) {
            currentRecordId = document.body.dataset.openRecord || '';
            currentMode = document.body.dataset.openMode || 'view';
            const reinitTab = document.body.dataset.statusTab;
            if (reinitTab) {
                const tabEl = tabs.find((t) => t.dataset.statusTab === reinitTab);
                if (tabEl) {
                    setActiveTab(tabEl);
                } else {
                    applyFilters();
                }
            } else {
                applyFilters();
            }
            if (
                currentRecordId
                && (recordsById[currentRecordId] || recordsIndex.find((r) => r.id === currentRecordId)?.payload)
            ) {
                const modalOverlay = document.getElementById('daily-modal-overlay');
                if (modalOverlay?.classList.contains('is-open')) {
                    openRecord(currentRecordId, currentMode);
                }
            }
            return;
        }

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

    window.initDailyDetailModule = initDailyDetailModule;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDailyDetailModule);
    } else {
        initDailyDetailModule();
    }
})();
