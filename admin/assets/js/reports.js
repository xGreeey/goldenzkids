(function () {
    'use strict';

    /** @type {AbortController | null} */
    let reportsModalUiAbort = null;

    /** @type {AbortController | null} */
    let reportsTableUiAbort = null;

    /** Shared across init/reinit so modal + table actions stay in sync after panel navigation. */
    const reportsLive = {
        currentIncidentId: '',
        currentMode: 'view',
        reportsById: {},
        handlers: null,
    };

    function openReportsImageViewer(src, alt) {
        const viewer = document.getElementById('reports-image-viewer');
        const img = document.getElementById('reports-image-viewer-img');
        if (!viewer || !img || !src) {
            return;
        }
        img.src = src;
        img.alt = alt || 'Attachment preview';
        viewer.hidden = false;
        viewer.classList.add('is-open');
        viewer.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeReportsImageViewer() {
        const viewer = document.getElementById('reports-image-viewer');
        const img = document.getElementById('reports-image-viewer-img');
        if (!viewer) {
            return;
        }
        viewer.classList.remove('is-open');
        viewer.hidden = true;
        viewer.setAttribute('aria-hidden', 'true');
        if (img) {
            img.removeAttribute('src');
            img.alt = '';
        }
        const modalOpen = document.getElementById('reports-modal-overlay')?.classList.contains('is-open');
        const guideOpen = document.getElementById('reports-guard-guide-overlay')?.classList.contains('is-open');
        const typesOpen = document
            .getElementById('reports-incident-types-overlay')
            ?.classList.contains('is-open');
        if (!modalOpen && !guideOpen && !typesOpen) {
            document.body.style.overflow = '';
        }
    }

    function bindReportsModalUi() {
        const h = reportsLive.handlers;
        if (!h) {
            return;
        }
        reportsModalUiAbort?.abort();
        reportsModalUiAbort = new AbortController();
        const { signal } = reportsModalUiAbort;

        const modalOverlay = document.getElementById('reports-modal-overlay');
        const modalEl = document.getElementById('reports-modal');
        const modalClose = document.getElementById('reports-modal-close');
        const guardGuideOpen = document.getElementById('reports-guard-guide-open');
        const guardGuideOverlay = document.getElementById('reports-guard-guide-overlay');
        const guardGuideModal = document.getElementById('reports-guard-guide-modal');
        const guardGuideClose = document.getElementById('reports-guard-guide-close');
        const guardGuideFilterSearch = document.getElementById('reports-guard-guide-search');
        const guardGuideFilterReset = document.getElementById('reports-guard-guide-reset');
        const incidentTypesOpen = document.getElementById('reports-incident-types-open');
        const incidentTypesOverlay = document.getElementById('reports-incident-types-overlay');
        const incidentTypesModal = document.getElementById('reports-incident-types-modal');
        const incidentTypesClose = document.getElementById('reports-incident-types-close');
        const incidentTypesFilterSearch = document.getElementById('reports-incident-types-search');
        const incidentTypesFilterReset = document.getElementById('reports-incident-types-reset');

        modalOverlay?.addEventListener(
            'click',
            (e) => {
                if (e.target === modalOverlay) {
                    h.closeModal();
                }
            },
            { signal }
        );
        modalClose?.addEventListener('click', () => h.closeModal(), { signal });
        modalEl?.addEventListener(
            'change',
            (e) => {
                const target = e.target;
                if (target && target.id === 'history-row-new-action') {
                    const registryInline = document.querySelector(
                        '#modal-stepper [data-registry-inline]'
                    );
                    if (registryInline) {
                        registryInline.hidden = target.value !== 'registry';
                    }
                }
            },
            { signal }
        );

        modalEl?.addEventListener(
            'click',
            (e) => {
                const attachmentLink = e.target.closest('[data-reports-attachment-preview]');
                if (attachmentLink) {
                    e.preventDefault();
                    const thumb = attachmentLink.querySelector('img');
                    openReportsImageViewer(
                        attachmentLink.getAttribute('href') || thumb?.src || '',
                        thumb?.alt || 'Attachment preview'
                    );
                    return;
                }
                if (e.target.closest('#modal-goto-edit')) {
                    e.preventDefault();
                    h.switchToEditMode();
                    return;
                }
                if (e.target.closest('#modal-cancel-edit')) {
                    e.preventDefault();
                    h.switchToViewMode();
                    return;
                }
                e.stopPropagation();
            },
            { signal }
        );

        const imageViewer = document.getElementById('reports-image-viewer');
        const imageViewerClose = document.getElementById('reports-image-viewer-close');
        imageViewer?.addEventListener(
            'click',
            (e) => {
                if (e.target === imageViewer || e.target.closest('#reports-image-viewer-close')) {
                    closeReportsImageViewer();
                }
            },
            { signal }
        );
        imageViewerClose?.addEventListener('click', () => closeReportsImageViewer(), { signal });
        document.addEventListener(
            'keydown',
            (e) => {
                if (e.key === 'Escape' && imageViewer?.classList.contains('is-open')) {
                    closeReportsImageViewer();
                }
            },
            { signal }
        );

        guardGuideOpen?.addEventListener('click', () => h.openGuardGuide(), { signal });
        guardGuideClose?.addEventListener('click', () => h.closeGuardGuide(), { signal });
        guardGuideOverlay?.addEventListener(
            'click',
            (e) => {
                if (e.target === guardGuideOverlay) {
                    h.closeGuardGuide();
                }
            },
            { signal }
        );
        guardGuideModal?.addEventListener('click', (e) => e.stopPropagation(), { signal });
        guardGuideFilterSearch?.addEventListener('input', () => h.applyGuardGuideSearch(), {
            signal,
        });
        guardGuideFilterReset?.addEventListener('click', () => h.resetGuardGuideFilters(), {
            signal,
        });

        incidentTypesOpen?.addEventListener('click', () => h.openIncidentTypesCatalog(), { signal });
        incidentTypesClose?.addEventListener('click', () => h.closeIncidentTypesCatalog(), { signal });
        incidentTypesOverlay?.addEventListener(
            'click',
            (e) => {
                if (e.target === incidentTypesOverlay) {
                    h.closeIncidentTypesCatalog();
                }
            },
            { signal }
        );
        incidentTypesModal?.addEventListener('click', (e) => e.stopPropagation(), { signal });
        incidentTypesFilterSearch?.addEventListener('input', () => h.applyIncidentTypesSearch(), {
            signal,
        });
        incidentTypesFilterReset?.addEventListener('click', () => h.resetIncidentTypesFilters(), {
            signal,
        });
    }

    function bindReportsTableUi(tableRoot) {
        const h = reportsLive.handlers;
        if (!h || !tableRoot) {
            return;
        }
        reportsTableUiAbort?.abort();
        reportsTableUiAbort = new AbortController();
        const { signal } = reportsTableUiAbort;

        tableRoot.addEventListener(
            'click',
            (e) => {
                if (e.target.closest('.reports-sort')) {
                    return;
                }

                const viewBtn = e.target.closest('[data-action="view"]');
                const archiveBtn = e.target.closest('[data-action="archive"]');
                const deleteBtn = e.target.closest('[data-action="delete"]');
                if (viewBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    h.openIncident(viewBtn.dataset.incidentId || '', 'view');
                    return;
                }
                if (archiveBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    h.archiveIncident?.(
                        archiveBtn.dataset.incidentId || '',
                        archiveBtn.dataset.incidentRef || ''
                    );
                    return;
                }
                if (deleteBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    h.deleteIncident?.(
                        deleteBtn.dataset.incidentId || '',
                        deleteBtn.dataset.incidentRef || ''
                    );
                    return;
                }

                const row = e.target.closest('[data-report-row]');
                if (row && !e.target.closest('.reports-actions')) {
                    h.openIncident(row.dataset.id, 'view');
                }
            },
            { signal }
        );
    }

        function initReportsModule() {
    const root = document.getElementById('reports-module');
    if (!root || root.getAttribute('data-registry-kind')) {
        return;
    }
    if (!document.getElementById('reports-tbody')) {
        return;
    }

    const isReinit = root.dataset.reportsBound === '1';
    if (!isReinit) {
        root.dataset.reportsBound = '1';
    }

    reportsLive.currentIncidentId = document.body.dataset.openIncident || '';
    reportsLive.currentMode = document.body.dataset.openMode || 'view';

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

    const dataEl = document.getElementById('reports-data-json');
    const statusLabelsEl = document.getElementById('reports-status-labels');
    const allReports = dataEl ? safeParse(dataEl.textContent) : [];
    const STATUS_LABELS = statusLabelsEl ? safeParse(statusLabelsEl.textContent) : {};
    const DEFAULT_STATUS_LABEL =
        (STATUS_LABELS && STATUS_LABELS.ongoing) || 'Open';
    reportsLive.reportsById = {};
    if (Array.isArray(allReports)) {
        allReports.forEach((r) => {
            if (r && r.id) {
                reportsLive.reportsById[r.id] = r;
            }
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
            payload: reportsLive.reportsById[row.dataset.id || ''] || safeParse(row.dataset.detail),
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

    function safeParse(json) {
        try {
            return JSON.parse(json || '{}');
        } catch {
            return {};
        }
    }

    function hydrateReportsByIdFromRows() {
        getRows().forEach((row) => {
            const id = row.dataset.id || '';
            if (!id || reportsLive.reportsById[id]) {
                return;
            }
            const parsed = safeParse(row.dataset.detail);
            if (parsed && parsed.id) {
                reportsLive.reportsById[id] = parsed;
            }
        });
    }

    function resolveIncidentById(id) {
        if (!id) {
            return null;
        }
        if (reportsLive.reportsById[id]) {
            return reportsLive.reportsById[id];
        }
        const fromIndex = reportsIndex.find((r) => r.id === id);
        if (fromIndex?.payload?.id) {
            reportsLive.reportsById[id] = fromIndex.payload;
            return fromIndex.payload;
        }
        const row = root.querySelector('[data-report-row][data-id="' + id.replace(/"/g, '\\"') + '"]');
        if (row) {
            const parsed = safeParse(row.dataset.detail);
            if (parsed?.id) {
                reportsLive.reportsById[id] = parsed;
                return parsed;
            }
        }
        return null;
    }

    let reportsIndex = buildIndex();
    hydrateReportsByIdFromRows();
    reportsIndex = buildIndex();

    let sortKey = 'submitted';
    let sortDir = 'desc';

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

    function handwritingHtml(value) {
        const v = String(value ?? '').trim();
        if (!v) {
            return '—';
        }
        return escapeHtml(v).replace(/\n/g, '<br>');
    }

    function buildIncidentScanHtml(p) {
        const scanUrl = String(p.scan_url || '').trim();
        const ref = String(p.ref || 'Incident report').trim();

        if (!scanUrl) {
            return (
                '<section class="reports-incident-scan reports-incident-scan--empty" aria-label="Submitted form scan">' +
                '<h4 class="reports-incident-scan__heading">Uploaded form (reference)</h4>' +
                '<p class="reports-incident-scan__empty">No scan image on file for this report.</p></section>'
            );
        }

        return (
            '<section class="reports-incident-scan" aria-label="Submitted form scan">' +
            '<h4 class="reports-incident-scan__heading">Uploaded form (reference)</h4>' +
            '<p class="reports-incident-scan__hint">Compare the head guard\'s scan with the extracted handwriting below.</p>' +
            '<a href="' +
            escapeHtml(scanUrl) +
            '" target="_blank" rel="noopener noreferrer" class="reports-incident-scan__link">' +
            '<img class="reports-incident-scan__img" src="' +
            escapeHtml(scanUrl) +
            '" alt="Scanned post-incident form for ' +
            escapeHtml(ref) +
            '"></a>' +
            '<p class="reports-incident-scan__open"><a href="' +
            escapeHtml(scanUrl) +
            '" target="_blank" rel="noopener noreferrer">Open full size</a></p></section>'
        );
    }

    function buildIncidentAsIsHtml(incidentDescription, actionTaken) {
        const desc = String(incidentDescription ?? '').trim();
        const action = String(actionTaken ?? '').trim();
        const emptyClass = !desc && !action ? ' is-empty' : '';

        return (
            '<section id="modal-as-is-view" class="reports-detail-sheet__section" aria-label="Handwritten report (as scanned)">' +
            '<h4 class="reports-incident-as-is__heading">Form handwriting (as written)</h4>' +
            '<div class="reports-incident-as-is' +
            emptyClass +
            '">' +
            '<div class="reports-incident-as-is__col reports-incident-as-is__col--description">' +
            '<span class="reports-incident-as-is__label">Incident description</span>' +
            '<div class="reports-incident-as-is__body">' +
            handwritingHtml(desc) +
            '</div></div>' +
            '<div class="reports-incident-as-is__col reports-incident-as-is__col--action">' +
            '<span class="reports-incident-as-is__label">Action taken</span>' +
            '<div class="reports-incident-as-is__body">' +
            handwritingHtml(action) +
            '</div></div></div></section>'
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
        const formName = String(p.form_name || '').trim();
        const formDate = String(p.form_date || '').trim();
        let html =
            '<div class="reports-detail-sheet" role="group" aria-label="Report summary">' +
            buildIncidentScanHtml(p) +
            '<section class="reports-detail-sheet__section" aria-label="Assignment">' +
            '<div class="reports-detail-sheet__grid reports-detail-sheet__grid--people">' +
            sheetField('Post', p.site) +
            sheetField('Head guard', headGuard) +
            sheetField('Guard', person) +
            '</div></section>';

        if (formName || formDate) {
            html +=
                '<section class="reports-detail-sheet__section" aria-label="Form header">' +
                '<div class="reports-detail-sheet__grid reports-detail-sheet__grid--people">' +
                (formName ? sheetField('Subject (form)', formName) : '') +
                (formDate ? sheetField('Date (form)', formDate) : '') +
                '</div></section>';
        }

        html += buildIncidentAsIsHtml(p.incident_description, p.action_taken);
        html +=
            '<section class="reports-detail-sheet__section" aria-label="Classification">' +
            '<div class="reports-detail-sheet__grid reports-detail-sheet__grid--incident">' +
            sheetField('Incident', p.incident_type, 'incident') +
            sheetField('Severity', p.severity, 'severity') +
            '</div></section></div>';

        return html;
    }

    function historyEntrySource(entry) {
        const source = String(entry.source || '').toLowerCase();
        if (source === 'head_guard' || source === 'admin' || source === 'system') {
            return source;
        }
        const event = String(entry.event || '').toLowerCase();
        if (event.includes('submitted by head guard') || event.includes('report filed')) {
            return 'head_guard';
        }
        if (event.includes('classified') || event.includes('assigned to operations')) {
            return 'system';
        }
        return 'admin';
    }

    function historyEntryKind(entry, index) {
        const kind = String(entry.kind || '').toLowerCase();
        if (kind) {
            return kind;
        }
        if (index === 0 && historyEntrySource(entry) === 'head_guard') {
            return 'field_submission';
        }
        return 'response';
    }

    function fieldSubmissionContent(entry, p) {
        let description = String(entry.description || entry.incident_description || '').trim();
        let immediate_action = String(entry.immediate_action || entry.action_taken || '').trim();
        if (!description) {
            description = String(p.incident_description || p.summary || '').trim();
        }
        if (!immediate_action) {
            immediate_action = String(p.action_taken || '').trim();
        }
        const note = String(entry.note || '').trim();
        if (!description && note) {
            description = note;
        }
        return { description, immediate_action };
    }

    function historyActionLabel(entry, index) {
        const kind = historyEntryKind(entry, index);
        if (kind === 'field_submission' || (index === 0 && historyEntrySource(entry) === 'head_guard')) {
            return 'Report filed';
        }
        return String(entry.event || 'Update').trim();
    }

    function historyNotesText(entry, index, p) {
        if (historyEntrySource(entry) === 'system') {
            return String(entry.note || '—').trim() || '—';
        }
        const kind = historyEntryKind(entry, index);
        if (kind === 'field_submission' || (index === 0 && historyEntrySource(entry) === 'head_guard')) {
            const content = fieldSubmissionContent(entry, p);
            const lines = [];
            if (content.description) {
                lines.push('Description: ' + content.description);
            }
            if (content.immediate_action) {
                lines.push('Immediate action: ' + content.immediate_action);
            }
            return lines.length ? lines.join('\n') : '—';
        }
        const note = String(entry.note || '').trim();
        return note || '—';
    }

    function historyByLabel(entry, p) {
        if (historyEntrySource(entry) === 'head_guard') {
            const name = String(p.head_guard_name || p.submitter_name || '').trim();
            return name || 'Head guard';
        }
        if (historyEntrySource(entry) === 'system') {
            return 'System';
        }
        return 'Operations';
    }

    function historyDisplayTimestamp(entry) {
        const edited = String(entry.edited_at || '').trim();
        return edited || String(entry.at || '');
    }

    function modalDescriptionText(p) {
        const desc = String(p.incident_description || '').trim();
        if (desc) {
            return desc;
        }
        const history = Array.isArray(p.history) ? p.history : [];
        for (const entry of history) {
            if (historyEntrySource(entry) === 'head_guard') {
                const fromHistory = String(entry.description || '').trim();
                if (fromHistory) {
                    return fromHistory;
                }
            }
        }
        return String(p.summary || '').trim();
    }

    function personFromReport(p) {
        let person = String(p.person_involved || p.guard_involved || '').trim();
        if (person) {
            return person;
        }
        const history = Array.isArray(p.history) ? p.history : [];
        for (const entry of history) {
            if (String(entry.guard_name || '').trim()) {
                return String(entry.guard_name).trim();
            }
        }
        return '';
    }

    function historyEntryIsEditable(entry) {
        return historyEntrySource(entry) === 'admin';
    }

    function historyEntryIsDecision(entry) {
        const kind = String(entry.kind || '').toLowerCase();
        if (kind === 'decision') {
            return true;
        }
        const event = String(entry.event || '').toLowerCase();
        return (
            event === 'report accepted' ||
            event === 'report not accepted' ||
            event === 'report on hold'
        );
    }

    function buildHeadGuardReadonlyNotesHtml(entry, p) {
        const content = fieldSubmissionContent(entry, p);
        const guardName = String(entry.guard_name || p.person_involved || '').trim();
        let html = '<div class="reports-op-flow__submission-readonly">';
        if (guardName) {
            html +=
                '<p class="reports-op-flow__submission-line"><strong>Guard (under head guard):</strong> ' +
                escapeHtml(guardName) +
                '</p>';
        }
        if (content.description) {
            html +=
                '<p class="reports-op-flow__submission-line"><strong>Description:</strong> ' +
                escapeHtml(content.description) +
                '</p>';
        }
        if (content.immediate_action) {
            html +=
                '<p class="reports-op-flow__submission-line"><strong>Immediate action:</strong> ' +
                escapeHtml(content.immediate_action) +
                '</p>';
        }
        if (!guardName && !content.description && !content.immediate_action) {
            html += '<p class="reports-op-flow__submission-line">—</p>';
        }
        html +=
            '<p class="reports-form-hint reports-op-flow__submission-hint">To correct OCR text, use the <strong>Report text</strong> section above.</p>';
        return html + '</div>';
    }

    function historyEntryIsRegistry(entry) {
        const kind = String(entry.kind || '').toLowerCase();
        const event = String(entry.event || '').toLowerCase();
        return kind === 'status' || event.startsWith('registry:') || event.startsWith('status:');
    }

    function buildStatusSelectHtml(name, selectedValue, id) {
        const tpl = document.getElementById('reports-status-options-template');
        if (!tpl) {
            return '';
        }
        let html =
            '<select class="reports-op-flow__input" name="' + escapeHtml(name) + '"';
        if (id) {
            html += ' id="' + escapeHtml(id) + '"';
        }
        html += '>';
        for (const opt of tpl.options) {
            html +=
                '<option value="' +
                escapeHtml(opt.value) +
                '"' +
                (opt.value === selectedValue ? ' selected' : '') +
                '>' +
                escapeHtml(opt.text) +
                '</option>';
        }
        return html + '</select>';
    }

    function actionTypeFromEntry(entry) {
        if (historyEntryIsDecision(entry)) {
            const event = String(entry.event || '').toLowerCase();
            if (event === 'report accepted') {
                return 'accept';
            }
            if (event === 'report on hold') {
                return 'on_hold';
            }
            if (event === 'report not accepted') {
                return 'denied';
            }
            return 'accept';
        }
        if (historyEntryIsRegistry(entry)) {
            return 'registry';
        }
        return 'response';
    }

    function buildOpsActionSelectHtml(name, selectedValue, id) {
        const options = [
            ['', '— Select action —'],
            ['accept', 'Report accepted'],
            ['on_hold', 'Report on hold'],
            ['denied', 'Report not accepted'],
            ['response', 'Operations note'],
            ['registry', 'Registry status change'],
        ];
        let html =
            '<select name="' + escapeHtml(name) + '" class="reports-op-flow__input reports-op-flow__cell-input"';
        if (id) {
            html += ' id="' + escapeHtml(id) + '"';
        }
        html += '>';
        for (const [value, label] of options) {
            html +=
                '<option value="' +
                escapeHtml(value) +
                '"' +
                (value === selectedValue ? ' selected' : '') +
                '>' +
                escapeHtml(label) +
                '</option>';
        }
        return html + '</select>';
    }

    function buildOperationFlowNewRowHtml() {
        return (
            '<tr class="reports-op-flow__row reports-op-flow__row--editing reports-op-flow__row--new" data-history-index="new">' +
            '<td class="reports-op-flow__when"><span class="reports-op-flow__new-label">New</span></td>' +
            '<td class="reports-op-flow__action">' +
            buildOpsActionSelectHtml('history_row[new][action_type]', '', 'history-row-new-action') +
            '</td>' +
            '<td class="reports-op-flow__notes">' +
            '<textarea class="reports-op-flow__input reports-op-flow__textarea reports-op-flow__cell-input" name="history_row[new][note]" rows="2" maxlength="1000" placeholder="Decision notes, follow-up, evidence request, closure memo…"></textarea>' +
            '<div class="reports-op-flow__registry-inline" data-registry-inline hidden>' +
            buildStatusSelectHtml('history_row[new][registry_status]', 'ongoing', 'history-row-new-registry') +
            '</div></td>' +
            '<td class="reports-op-flow__by">Operations</td></tr>'
        );
    }

    function buildOperationFlowRegistryRowHtml(p) {
        const statusSlug = String(p?.status || 'ongoing');
        return (
            '<tr class="reports-op-flow__row reports-op-flow__row--status reports-op-flow__row--editing">' +
            '<td class="reports-op-flow__when">—</td>' +
            '<td class="reports-op-flow__action">Case registry</td>' +
            '<td class="reports-op-flow__notes">' +
            buildStatusSelectHtml('status', statusSlug, 'edit-registry-status') +
            '</td>' +
            '<td class="reports-op-flow__by"></td></tr>'
        );
    }

    function buildOperationFlowEditableHtml(history, p) {
        const entries = Array.isArray(history) ? history : [];

        let rows = '';
        entries.forEach((entry, index) => {
            if (historyEntrySource(entry) === 'system') {
                const parts = historyDatetimeParts(historyDisplayTimestamp(entry));
                rows +=
                    '<tr class="reports-op-flow__row reports-op-flow__row--system" data-history-index="' +
                    index +
                    '">' +
                    '<td class="reports-op-flow__when">' +
                    '<span class="reports-op-flow__date">' +
                    escapeHtml(parts.date) +
                    '</span>' +
                    '<span class="reports-op-flow__time">' +
                    escapeHtml(parts.time) +
                    '</span></td>' +
                    '<td class="reports-op-flow__action">' +
                    escapeHtml(String(entry.event || 'System')) +
                    '</td>' +
                    '<td class="reports-op-flow__notes">' +
                    escapeHtml(historyNotesText(entry, index, p)) +
                    '</td>' +
                    '<td class="reports-op-flow__by">' +
                    escapeHtml(historyByLabel(entry, p)) +
                    '</td></tr>';
                return;
            }

            const parts = historyDatetimeParts(historyDisplayTimestamp(entry));
            const prefix = 'history_row[' + index + ']';
            const by = historyByLabel(entry, p);
            const editedTag =
                String(entry.edited_at || '').trim() !== ''
                    ? ' <span class="reports-op-flow__edited-tag">Updated</span>'
                    : '';
            let actionCell = '';
            let notesCell = '';

            let rowClass = 'reports-op-flow__row reports-op-flow__row--editing';

            if (historyEntrySource(entry) === 'head_guard') {
                rowClass += ' reports-op-flow__row--head-guard';
                actionCell = '<span class="reports-op-flow__action-label">Report filed</span>';
                notesCell = buildHeadGuardReadonlyNotesHtml(entry, p);
            } else if (historyEntryIsEditable(entry)) {
                const note = historyEntryNoteForEdit(entry);
                if (historyEntryIsDecision(entry)) {
                    actionCell = buildOpsActionSelectHtml(
                        prefix + '[action_type]',
                        actionTypeFromEntry(entry)
                    );
                    notesCell =
                        '<textarea class="reports-op-flow__input reports-op-flow__textarea reports-op-flow__cell-input" name="' +
                        prefix +
                        '[note]" rows="2" maxlength="1000" placeholder="Decision notes…">' +
                        escapeHtml(note) +
                        '</textarea>';
                } else if (historyEntryIsRegistry(entry)) {
                    const statusSlug =
                        statusSlugFromHistoryEvent(entry.event) || p.status || 'ongoing';
                    actionCell =
                        buildOpsActionSelectHtml(prefix + '[action_type]', 'registry') +
                        buildStatusSelectHtml(prefix + '[registry_status]', statusSlug);
                    notesCell =
                        '<textarea class="reports-op-flow__input reports-op-flow__textarea reports-op-flow__cell-input" name="' +
                        prefix +
                        '[note]" rows="2" maxlength="1000" placeholder="Note…">' +
                        escapeHtml(note) +
                        '</textarea>';
                } else {
                    actionCell =
                        '<input type="text" class="reports-op-flow__input reports-op-flow__cell-input" name="' +
                        prefix +
                        '[event]" value="' +
                        escapeHtml(String(entry.event || '').trim()) +
                        '" maxlength="120" placeholder="Action label">';
                    notesCell =
                        '<textarea class="reports-op-flow__input reports-op-flow__textarea reports-op-flow__cell-input" name="' +
                        prefix +
                        '[note]" rows="2" maxlength="1000" placeholder="Notes…">' +
                        escapeHtml(note) +
                        '</textarea>';
                }
            } else {
                actionCell = escapeHtml(historyActionLabel(entry, index));
                notesCell =
                    '<span class="reports-op-flow__notes-text">' +
                    escapeHtml(historyNotesText(entry, index, p)) +
                    '</span>';
            }

            rows +=
                '<tr class="' +
                rowClass +
                '" data-history-index="' +
                index +
                '">' +
                '<td class="reports-op-flow__when">' +
                '<span class="reports-op-flow__date">' +
                escapeHtml(parts.date) +
                '</span>' +
                '<span class="reports-op-flow__time">' +
                escapeHtml(parts.time) +
                editedTag +
                '</span></td>' +
                '<td class="reports-op-flow__action">' +
                actionCell +
                '</td>' +
                '<td class="reports-op-flow__notes">' +
                notesCell +
                '</td>' +
                '<td class="reports-op-flow__by">' +
                escapeHtml(by) +
                '</td></tr>';
        });

        const header =
            '<thead><tr class="reports-op-flow__head">' +
            '<th scope="col" class="reports-op-flow__col-when">Date</th>' +
            '<th scope="col" class="reports-op-flow__col-action">Action</th>' +
            '<th scope="col" class="reports-op-flow__col-notes">Notes</th>' +
            '<th scope="col" class="reports-op-flow__col-by">By</th>' +
            '</tr></thead>';

        rows += buildOperationFlowNewRowHtml();
        rows += buildOperationFlowRegistryRowHtml(p);

        return (
            '<table class="reports-op-flow__table reports-op-flow__table--editing reports-op-flow__table--sheet">' +
            header +
            '<tbody>' +
            rows +
            '</tbody></table>'
        );
    }

    function statusSlugFromHistoryEvent(event) {
        const m = String(event || '').match(/^(Registry|Status):\s*(.+)$/i);
        if (!m) {
            return null;
        }
        const label = m[2].trim().toLowerCase();
        const tpl = document.getElementById('reports-status-options-template');
        if (!tpl) {
            return null;
        }
        for (const opt of tpl.options) {
            if (opt.text.trim().toLowerCase() === label) {
                return opt.value;
            }
        }
        return null;
    }

    function historyEntryNoteForEdit(entry) {
        const note = String(entry.note || '').trim();
        if (
            note.includes('Status set to') ||
            note.includes('Registry status updated') ||
            note === 'Progression updated.'
        ) {
            return '';
        }
        return note;
    }

    function buildOperationFlowHtml(history, p) {
        if (!Array.isArray(history) || history.length === 0) {
            return (
                '<p class="reports-op-flow__empty">No operations history yet. Entries are added when a report is submitted or updated by operations.</p>'
            );
        }

        let rows = '';
        history.forEach((entry, index) => {
            const parts = historyDatetimeParts(historyDisplayTimestamp(entry));
            let rowClass = 'reports-op-flow__row';
            if (historyEntrySource(entry) === 'system') {
                rowClass += ' reports-op-flow__row--system';
            } else if (historyEntrySource(entry) === 'head_guard') {
                rowClass += ' reports-op-flow__row--head-guard';
            } else if (historyEntryIsDecision(entry)) {
                rowClass += ' reports-op-flow__row--decision';
            }

            rows +=
                '<tr class="' +
                rowClass +
                '" data-history-index="' +
                index +
                '">' +
                '<td class="reports-op-flow__when">' +
                '<span class="reports-op-flow__date">' +
                escapeHtml(parts.date) +
                '</span>' +
                '<span class="reports-op-flow__time">' +
                escapeHtml(parts.time) +
                '</span></td>' +
                '<td class="reports-op-flow__action">' +
                escapeHtml(historyActionLabel(entry, index)) +
                '</td>' +
                '<td class="reports-op-flow__notes"><span class="reports-op-flow__notes-text">' +
                escapeHtml(historyNotesText(entry, index, p)) +
                '</span></td>' +
                '<td class="reports-op-flow__by">' +
                escapeHtml(historyByLabel(entry, p)) +
                '</td></tr>';
        });

        const statusLabel = String(p.status_label || p.status || '—');
        rows +=
            '<tr class="reports-op-flow__row reports-op-flow__row--status">' +
            '<td class="reports-op-flow__when">—</td>' +
            '<td class="reports-op-flow__action">Case registry</td>' +
            '<td class="reports-op-flow__notes">' +
            escapeHtml(statusLabel) +
            '</td>' +
            '<td class="reports-op-flow__by"></td></tr>';

        const header =
            '<thead><tr class="reports-op-flow__head">' +
            '<th scope="col" class="reports-op-flow__col-when">Date</th>' +
            '<th scope="col" class="reports-op-flow__col-action">Action</th>' +
            '<th scope="col" class="reports-op-flow__col-notes">Notes</th>' +
            '<th scope="col" class="reports-op-flow__col-by">By</th>' +
            '</tr></thead>';

        return '<table class="reports-op-flow__table">' + header + '<tbody>' + rows + '</tbody></table>';
    }

    function renderHistoryStepper(history, p) {
        const host = document.getElementById('modal-stepper');
        if (!host) {
            return;
        }
        const payload = p || {};
        const editable = reportsLive.currentMode === 'edit';
        host.innerHTML = editable
            ? buildOperationFlowEditableHtml(history, payload)
            : buildOperationFlowHtml(history, payload);
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
        const p = reportsLive.reportsById[id];
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
        const host = document.getElementById('modal-view-details');
        if (!host || !p) {
            return;
        }
        host.innerHTML = buildModalDetailsHtml(p);
    }

    function populateEditForm(p) {
        const editForm = document.getElementById('reports-edit-form');
        const editPlaceholder = document.getElementById('modal-edit-placeholder');
        if (!editForm || !p) {
            return;
        }
        editForm.hidden = false;
        if (editPlaceholder) {
            editPlaceholder.hidden = true;
        }

        const idInput = document.getElementById('edit-incident-id');
        if (idInput) {
            idInput.value = p.id || '';
        }
        const indexInput = document.getElementById('edit-history-index');
        if (indexInput) {
            indexInput.value = '';
        }
        const newNote = editForm.querySelector('[name="history_row[new][note]"]');
        if (newNote) {
            newNote.value = '';
        }
        const newAction = document.getElementById('history-row-new-action');
        if (newAction) {
            newAction.value = '';
        }
        const descInput = document.getElementById('edit-incident-description');
        if (descInput) {
            descInput.value = String(p.incident_description || '').trim();
        }
        const actionInput = document.getElementById('edit-action-taken');
        if (actionInput) {
            actionInput.value = String(p.action_taken || '').trim();
        }
    }

    function setModalMode(mode) {
        reportsLive.currentMode = mode === 'edit' ? 'edit' : 'view';
        const isView = reportsLive.currentMode === 'view';
        const panelView = document.getElementById('modal-panel-view');
        const modalFooterView = document.getElementById('reports-modal-footer-view');
        const modalFooterEdit = document.getElementById('reports-modal-footer-edit');
        if (panelView) {
            panelView.classList.add('is-active');
            panelView.hidden = false;
        }
        if (modalFooterView) {
            modalFooterView.hidden = !isView;
        }
        if (modalFooterEdit) {
            modalFooterEdit.hidden = isView;
        }
        const historySection = document.getElementById('modal-history-section');
        historySection?.classList.toggle('is-editing-progression', !isView);
        const historyViewDesc = document.getElementById('modal-history-view-desc');
        const historyHint = document.getElementById('modal-history-edit-hint');
        if (historyViewDesc) {
            historyViewDesc.hidden = !isView;
        }
        if (historyHint) {
            historyHint.hidden = isView;
        }
        const asIsView = document.getElementById('modal-as-is-view');
        if (asIsView) {
            asIsView.hidden = !isView;
        }
        const bodyEditWrap = document.getElementById('modal-report-body-edit-wrap');
        if (bodyEditWrap) {
            bodyEditWrap.hidden = isView;
        }

        const id = reportsLive.currentIncidentId;
        const payload = id ? resolveIncidentById(id) : null;
        if (payload) {
            renderViewDetails(payload);
            renderHistoryStepper(payload.history, payload);
            if (!isView) {
                populateEditForm(payload);
            }
        }
    }

    function scrollModalToEditPanel() {
        const target =
            document.getElementById('modal-report-body-edit-wrap') ||
            document.getElementById('modal-history-section');
        const scroller = document.querySelector('#reports-modal .reports-modal__body-scroll');
        if (target && scroller) {
            const top = target.offsetTop - 12;
            scroller.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
            return;
        }
        target?.scrollIntoView({ block: 'start', behavior: 'smooth' });
    }

    function focusFirstEditField() {
        const desc = document.getElementById('edit-incident-description');
        if (desc) {
            desc.focus();
            return;
        }
        document
            .querySelector(
                '#modal-stepper .reports-op-flow__row--new .reports-op-flow__cell-input, #modal-stepper .reports-op-flow__row--new textarea'
            )
            ?.focus();
    }

    function switchToEditMode() {
        const id = reportsLive.currentIncidentId;
        if (!id) {
            return;
        }
        const p = resolveIncidentById(id);
        if (!p) {
            return;
        }
        const modalOverlay = document.getElementById('reports-modal-overlay');
        if (!modalOverlay?.classList.contains('is-open')) {
            openIncident(id, 'edit');
            return;
        }
        setModalMode('edit');
        populateEditForm(p);
        pushUrl(id, 'edit');
        scrollModalToEditPanel();
        window.requestAnimationFrame(() => focusFirstEditField());
    }

    function switchToViewMode() {
        const id = reportsLive.currentIncidentId;
        if (!id) {
            return;
        }
        if (!resolveIncidentById(id)) {
            return;
        }
        setModalMode('view');
        pushUrl(id, 'view');
        const scroller = document.querySelector('#reports-modal .reports-modal__body-scroll');
        scroller?.scrollTo({ top: 0, behavior: 'smooth' });
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
        reportsLive.reportsById[p.id] = p;
    }

    function removeIncidentFromUi(id) {
        const idx = reportsIndex.findIndex((r) => r.id === id);
        if (idx >= 0) {
            reportsIndex[idx].el.remove();
            reportsIndex.splice(idx, 1);
        }
        delete reportsLive.reportsById[id];
        if (dataEl && Array.isArray(allReports)) {
            const dataIdx = allReports.findIndex((r) => r && r.id === id);
            if (dataIdx >= 0) {
                allReports.splice(dataIdx, 1);
                dataEl.textContent = JSON.stringify(allReports);
            }
        }
        updateKpis();
        applyFilters();
    }

    function archiveIncident(id, refLabel) {
        const postUrl = root.dataset.postUrl || window.location.pathname;
        const csrf = root.dataset.csrf || '';
        const label = refLabel || id || 'this report';
        if (!window.confirm('Archive ' + label + '? The case will be marked Closed.')) {
            return;
        }

        const fd = new FormData();
        fd.append('action', 'archive_incident');
        fd.append('incident_id', id);
        if (csrf) {
            fd.append('_csrf', csrf);
        }

        fetch(postUrl, {
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
                    window.alert(data.error || 'Could not archive report.');
                    return;
                }
                const record = data.record;
                if (record && record.id) {
                    updateRowFromPayload(record);
                    const row = reportsIndex.find((r) => r.id === record.id);
                    row?.el.querySelector('[data-action="archive"]')?.remove();
                    if (dataEl && Array.isArray(allReports)) {
                        const dataIdx = allReports.findIndex((r) => r && r.id === record.id);
                        if (dataIdx >= 0) {
                            allReports[dataIdx] = record;
                            dataEl.textContent = JSON.stringify(allReports);
                        }
                    }
                    updateKpis();
                    applyFilters();
                    if (reportsLive.currentIncidentId === record.id) {
                        openIncident(record.id, reportsLive.currentMode || 'view');
                    }
                }
                if (data.message && window.appNotify?.success) {
                    window.appNotify.success(data.message);
                }
            })
            .catch(() => {
                window.alert('Could not archive report. Refresh the page and try again.');
            });
    }

    function deleteIncident(id, refLabel) {
        const postUrl = root.dataset.postUrl || window.location.pathname;
        const csrf = root.dataset.csrf || '';
        const label = refLabel || id || 'this report';
        if (
            !window.confirm(
                'Delete ' + label + '? This permanently removes the incident from the registry.'
            )
        ) {
            return;
        }

        const fd = new FormData();
        fd.append('action', 'delete_incident');
        fd.append('incident_id', id);
        if (csrf) {
            fd.append('_csrf', csrf);
        }

        fetch(postUrl, {
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
                    window.alert(data.error || 'Could not delete report.');
                    return;
                }
                if (reportsLive.currentIncidentId === id) {
                    closeModal();
                }
                removeIncidentFromUi(id);
                if (data.message && window.appNotify?.success) {
                    window.appNotify.success(data.message);
                }
            })
            .catch(() => {
                window.alert('Could not delete report. Refresh the page and try again.');
            });
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
        const p = resolveIncidentById(id);
        const modalOverlay = document.getElementById('reports-modal-overlay');
        if (!p || !modalOverlay) {
            if (id) {
                window.alert('Could not load this incident report. Refresh the page and try again.');
            }
            return;
        }

        const nextMode = mode || 'view';
        if (
            reportsLive.currentIncidentId === id &&
            modalOverlay.classList.contains('is-open')
        ) {
            setModalMode(nextMode);
            renderViewDetails(p);
            renderHistoryStepper(p.history, p);
            populateEditForm(p);
            if (nextMode === 'edit') {
                scrollModalToEditPanel();
                window.requestAnimationFrame(() => focusFirstEditField());
            } else {
                const scroller = document.querySelector('#reports-modal .reports-modal__body-scroll');
                scroller?.scrollTo({ top: 0, behavior: 'smooth' });
            }
            pushUrl(id, nextMode);
            return;
        }

        reportsLive.currentIncidentId = id;
        setModalMode(nextMode);

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

        const modalGotoEdit = document.getElementById('modal-goto-edit');
        if (modalGotoEdit) {
            modalGotoEdit.hidden = false;
        }

        modalOverlay.classList.add('is-open');
        modalOverlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        pushUrl(id, nextMode);
        if (nextMode === 'edit') {
            scrollModalToEditPanel();
            window.requestAnimationFrame(() => focusFirstEditField());
        }

        reportsIndex.forEach((r) => {
            r.el.classList.toggle('is-selected', r.id === id);
        });
    }

    function closeModal() {
        closeReportsImageViewer();
        const modalOverlay = document.getElementById('reports-modal-overlay');
        const guardGuideOverlay = document.getElementById('reports-guard-guide-overlay');
        const incidentTypesOverlay = document.getElementById('reports-incident-types-overlay');
        if (!modalOverlay) {
            return;
        }
        modalOverlay.classList.remove('is-open');
        modalOverlay.setAttribute('aria-hidden', 'true');
        if (
            !guardGuideOverlay?.classList.contains('is-open') &&
            !incidentTypesOverlay?.classList.contains('is-open')
        ) {
            document.body.style.overflow = '';
        }
        reportsLive.currentIncidentId = '';
        pushUrl('', '');
        reportsIndex.forEach((r) => r.el.classList.remove('is-selected'));
    }

    function applyGuardGuideSearch() {
        const guardGuideFilterSearch = document.getElementById('reports-guard-guide-search');
        const guardGuideFilterCountVisible = document.getElementById(
            'reports-guard-guide-count-visible'
        );
        const guardGuideFilterCountSuffix = document.getElementById(
            'reports-guard-guide-count-suffix'
        );
        const guardGuideSearchEmpty = document.getElementById('reports-guard-guide-search-empty');
        const guardGuideModal = document.getElementById('reports-guard-guide-modal');
        if (!guardGuideModal) {
            return;
        }

        const workflowRows = Array.from(
            guardGuideModal.querySelectorAll('.reports-workflow-row')
        );
        const workflowCategoryRows = Array.from(
            guardGuideModal.querySelectorAll('.reports-workflow-category')
        );
        const guideSections = Array.from(
            guardGuideModal.querySelectorAll('.reports-guide-section[data-guide-search]')
        );
        const guideBlocks = Array.from(
            guardGuideModal.querySelectorAll('[data-guide-block]')
        );
        const guideSearchTotal = workflowRows.length + guideSections.length;

        const q = (guardGuideFilterSearch?.value || '').trim().toLowerCase();
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
        if (guardGuideSearchEmpty) {
            guardGuideSearchEmpty.hidden = !q || totalVisible > 0;
        }
        if (guardGuideFilterCountVisible) {
            guardGuideFilterCountVisible.textContent = String(q ? totalVisible : guideSearchTotal);
        }
        if (guardGuideFilterCountSuffix) {
            guardGuideFilterCountSuffix.textContent = q
                ? ' matches'
                : ' topics';
        }
    }

    function resetGuardGuideFilters() {
        const guardGuideFilterSearch = document.getElementById('reports-guard-guide-search');
        const guardGuideScroller =
            document
                .getElementById('reports-guard-guide-modal')
                ?.querySelector('.reports-modal__body-scroll') ?? null;
        if (guardGuideFilterSearch) {
            guardGuideFilterSearch.value = '';
        }
        applyGuardGuideSearch();
        guardGuideScroller?.scrollTo(0, 0);
    }

    function openGuardGuide() {
        const guardGuideOverlay = document.getElementById('reports-guard-guide-overlay');
        const guardGuideFilterSearch = document.getElementById('reports-guard-guide-search');
        if (!guardGuideOverlay) {
            return;
        }
        closeModal();
        closeIncidentTypesCatalog();
        guardGuideOverlay.classList.add('is-open');
        guardGuideOverlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        resetGuardGuideFilters();
        guardGuideFilterSearch?.focus();
    }

    function closeGuardGuide() {
        const guardGuideOverlay = document.getElementById('reports-guard-guide-overlay');
        const modalOverlay = document.getElementById('reports-modal-overlay');
        const incidentTypesOverlay = document.getElementById('reports-incident-types-overlay');
        if (!guardGuideOverlay) {
            return;
        }
        guardGuideOverlay.classList.remove('is-open');
        guardGuideOverlay.setAttribute('aria-hidden', 'true');
        if (
            !modalOverlay?.classList.contains('is-open') &&
            !incidentTypesOverlay?.classList.contains('is-open')
        ) {
            document.body.style.overflow = '';
        }
    }

    function applyIncidentTypesSearch() {
        const searchInput = document.getElementById('reports-incident-types-search');
        const countVisible = document.getElementById('reports-incident-types-count-visible');
        const countSuffix = document.getElementById('reports-incident-types-count-suffix');
        const searchEmpty = document.getElementById('reports-incident-types-search-empty');
        const modal = document.getElementById('reports-incident-types-modal');
        if (!modal) {
            return;
        }

        const rows = Array.from(modal.querySelectorAll('.reports-incident-types-row'));
        const total = rows.length;
        const q = (searchInput?.value || '').trim().toLowerCase();
        let visible = 0;

        rows.forEach((row) => {
            const show = !q || (row.dataset.search || '').includes(q);
            row.classList.toggle('is-hidden', !show);
            if (show) {
                visible += 1;
            }
        });

        if (searchEmpty) {
            searchEmpty.hidden = !q || visible > 0;
        }
        if (countVisible) {
            countVisible.textContent = String(q ? visible : total);
        }
        if (countSuffix) {
            countSuffix.textContent = q ? ' matches' : ' types';
        }
    }

    function resetIncidentTypesFilters() {
        const searchInput = document.getElementById('reports-incident-types-search');
        const scroller =
            document
                .getElementById('reports-incident-types-modal')
                ?.querySelector('.reports-modal__body-scroll') ?? null;
        if (searchInput) {
            searchInput.value = '';
        }
        applyIncidentTypesSearch();
        scroller?.scrollTo(0, 0);
    }

    function openIncidentTypesCatalog() {
        const overlay = document.getElementById('reports-incident-types-overlay');
        const searchInput = document.getElementById('reports-incident-types-search');
        if (!overlay) {
            return;
        }
        closeModal();
        closeGuardGuide();
        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        resetIncidentTypesFilters();
        searchInput?.focus();
    }

    function closeIncidentTypesCatalog() {
        const overlay = document.getElementById('reports-incident-types-overlay');
        const modalOverlay = document.getElementById('reports-modal-overlay');
        const guardGuideOverlay = document.getElementById('reports-guard-guide-overlay');
        if (!overlay) {
            return;
        }
        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
        if (
            !modalOverlay?.classList.contains('is-open') &&
            !guardGuideOverlay?.classList.contains('is-open')
        ) {
            document.body.style.overflow = '';
        }
    }

    if (!isReinit) {
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
    }

    reportsLive.handlers = {
        switchToEditMode,
        switchToViewMode,
        openIncident,
        closeModal,
        openGuardGuide,
        closeGuardGuide,
        applyGuardGuideSearch,
        resetGuardGuideFilters,
        openIncidentTypesCatalog,
        closeIncidentTypesCatalog,
        applyIncidentTypesSearch,
        resetIncidentTypesFilters,
        printIncident,
        archiveIncident,
        deleteIncident,
    };
    bindReportsModalUi();
    bindReportsTableUi(root);

    if (isReinit) {
        if (reportsLive.currentIncidentId && resolveIncidentById(reportsLive.currentIncidentId)) {
            const modalOverlay = document.getElementById('reports-modal-overlay');
            if (modalOverlay?.classList.contains('is-open')) {
                openIncident(reportsLive.currentIncidentId, reportsLive.currentMode);
            }
        }
        applyFilters();
        return;
    }

    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') {
            return;
        }
        const incidentTypesOverlay = document.getElementById('reports-incident-types-overlay');
        if (incidentTypesOverlay?.classList.contains('is-open')) {
            closeIncidentTypesCatalog();
            return;
        }
        const guideOverlay = document.getElementById('reports-guard-guide-overlay');
        if (guideOverlay?.classList.contains('is-open')) {
            closeGuardGuide();
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
        if (tabEl) {
            setActiveTab(tabEl);
        } else {
            applyFilters();
        }
    } else {
        applyFilters();
    }

    if (reportsLive.currentIncidentId && resolveIncidentById(reportsLive.currentIncidentId)) {
        openIncident(reportsLive.currentIncidentId, reportsLive.currentMode);
    }
    }

    window.initReportsModule = initReportsModule;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initReportsModule);
    } else {
        initReportsModule();
    }
})();
