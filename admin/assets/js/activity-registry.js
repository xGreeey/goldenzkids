(function () {
    'use strict';

    function safeParse(json) {
        try {
            return JSON.parse(json);
        } catch {
            return null;
        }
    }

    function parseRowDetail(el) {
        if (!el) {
            return null;
        }
        const raw = el.getAttribute('data-detail');
        if (!raw) {
            return null;
        }
        return safeParse(raw);
    }

    function buildRecordsCatalog(jsonRecords, rowElements) {
        const byId = {};
        if (Array.isArray(jsonRecords)) {
            jsonRecords.forEach((r) => {
                if (r && r.id) {
                    byId[r.id] = r;
                }
            });
        }
        rowElements.forEach((el) => {
            const id = el.dataset.id || '';
            if (!id || byId[id]) {
                return;
            }
            const fromRow = parseRowDetail(el);
            if (fromRow && fromRow.id) {
                byId[fromRow.id] = fromRow;
            }
        });

        return byId;
    }

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function cellText(value) {
        const v = String(value ?? '').trim();
        return escapeHtml(v !== '' ? v : '—');
    }

    function narrativeHtml(value) {
        const v = String(value ?? '').trim();
        if (!v) {
            return '—';
        }
        return escapeHtml(v).replace(/\n/g, '<br>');
    }

    function sheetField(label, value, modifier) {
        const trimmed = String(value ?? '').trim();
        const mod = modifier ? ' reports-detail-sheet__field--' + modifier : '';
        const empty = trimmed === '' ? ' is-empty' : '';

        return (
            '<div class="reports-detail-sheet__field' +
            mod +
            empty +
            '"><span class="reports-detail-sheet__label">' +
            escapeHtml(label) +
            '</span><span class="reports-detail-sheet__value">' +
            cellText(trimmed) +
            '</span></div>'
        );
    }

    function narrativeField(label, value) {
        const trimmed = String(value ?? '').trim();
        const empty = trimmed === '' ? ' is-empty' : '';

        return (
            '<div class="reports-detail-sheet__field reports-detail-sheet__field--description' +
            empty +
            '"><span class="reports-detail-sheet__label">' +
            escapeHtml(label) +
            '</span><span class="reports-detail-sheet__value">' +
            narrativeHtml(trimmed) +
            '</span></div>'
        );
    }

    function dailyAttachmentsField(p, label, emptyText) {
        const items = Array.isArray(p.attachments) ? p.attachments : [];
        let inner = '<span class="reports-incident-attachments__empty">' + escapeHtml(emptyText) + '</span>';
        if (items.length > 0) {
            inner = '<div class="reports-incident-attachments__grid" role="list">';
            items.forEach((attachment) => {
                if (!attachment || typeof attachment !== 'object') {
                    return;
                }
                const url = String(attachment.url ?? '').trim();
                if (!url) {
                    return;
                }
                const cap = String(attachment.label ?? 'Supporting photo').trim() || 'Supporting photo';
                inner +=
                    '<a href="' +
                    escapeHtml(url) +
                    '" role="listitem" class="reports-incident-attachments__link" data-reports-attachment-preview target="_blank" rel="noopener noreferrer">' +
                    '<img class="reports-incident-attachments__thumb" src="' +
                    escapeHtml(url) +
                    '" alt="' +
                    escapeHtml(cap) +
                    '" loading="lazy" decoding="async">' +
                    '<span class="reports-incident-attachments__caption">' +
                    escapeHtml(cap) +
                    '</span></a>';
            });
            inner += '</div>';
        }

        return (
            '<div class="reports-detail-sheet__field reports-detail-sheet__field--attachments">' +
            '<span class="reports-detail-sheet__label">' +
            escapeHtml(label) +
            '</span>' +
            '<div class="reports-detail-sheet__value reports-incident-attachments">' +
            inner +
            '</div></div>'
        );
    }

    function renderDailySubmission(p) {
        const mode = String(p.activity_mode || 'normal').toLowerCase();
        if (mode === 'normal') {
            return (
                '<section class="reports-detail-sheet__section" aria-label="Head guard submission">' +
                '<p class="reports-daily-submission__intro"><strong>Normal operation</strong> — submitted without the event details step. No activity narrative or supporting photos were required on the guard form.</p>' +
                '</section>'
            );
        }

        return (
            '<section class="reports-detail-sheet__section" aria-label="Head guard submission">' +
            '<div class="reports-detail-sheet__grid reports-detail-sheet__grid--activity-narrative">' +
            narrativeField('Activity details', p.activity_details) +
            dailyAttachmentsField(p, 'Supporting photos', 'No supporting photos attached') +
            '</div></section>'
        );
    }

    function initActivityRegistryModule() {
        const root =
            document.getElementById('daily-activity-module') ||
            document.getElementById('weekly-activity-module');
        if (!root) {
            return;
        }

        const openParam = root.dataset.openParam || 'activity';
        const isWeekly = root.dataset.registryKind === 'weekly-activity';
        const hasModeFilter = !isWeekly;

        const isReinit = root.dataset.activityBound === '1';
        if (!isReinit) {
            root.dataset.activityBound = '1';
        }

        if (window.__activityRegistryAbort) {
            window.__activityRegistryAbort.abort();
        }
        window.__activityRegistryAbort = new AbortController();
        const signal = window.__activityRegistryAbort.signal;

        const tableHeadWrap = document.getElementById('activity-table-head-wrap');
        const tableBodyWrap = document.getElementById('activity-table-body-wrap');
        if (!isReinit && tableHeadWrap && tableBodyWrap) {
            tableBodyWrap.addEventListener(
                'scroll',
                () => {
                    tableHeadWrap.scrollLeft = tableBodyWrap.scrollLeft;
                },
                { passive: true, signal }
            );
        }

        const dataEl = document.getElementById('activity-data-json');
        const statusLabelsEl = document.getElementById('activity-status-labels');
        function getRows() {
            return Array.from(root.querySelectorAll('[data-activity-row]'));
        }

        const allRecords = dataEl ? safeParse(dataEl.textContent?.trim()) : [];
        const STATUS_LABELS = statusLabelsEl ? safeParse(statusLabelsEl.textContent?.trim()) : {};
        const recordsById = buildRecordsCatalog(allRecords, getRows());

        const searchInput = document.getElementById('activity-search');
        const modeSelect = hasModeFilter ? document.getElementById('activity-mode') : null;
        const dateFrom = document.getElementById('activity-date-from');
        const dateTo = document.getElementById('activity-date-to');
        const defaultDateFrom = dateFrom?.value || '';
        const defaultDateTo = dateTo?.value || '';
        const resetBtn = document.getElementById('activity-reset');
        const emptyEl = document.getElementById('activity-empty');
        const tbody = document.getElementById('activity-tbody');
        const tabs = Array.from(root.querySelectorAll('[data-status-tab]'));
        const sortButtons = Array.from(root.querySelectorAll('.reports-sort[data-sort-key]'));

        const modalOverlay = document.getElementById('activity-modal-overlay');
        if (modalOverlay && modalOverlay.parentElement !== document.body) {
            document.body.appendChild(modalOverlay);
        }
        const modalClose = document.getElementById('activity-modal-close');
        const modalRef = document.getElementById('activity-modal-ref');
        const modalStatusBadge = document.getElementById('activity-modal-status-badge');
        const modalDetails = document.getElementById('activity-modal-details');
        const modalHistory = document.getElementById('activity-modal-history');
        const modalView = document.getElementById('activity-modal-view');
        const footerView = document.getElementById('activity-modal-footer-view');
        const footerEdit = document.getElementById('activity-modal-footer-edit');
        const gotoEdit = document.getElementById('activity-goto-edit');
        const cancelEdit = document.getElementById('activity-cancel-edit');
        const editForm = document.getElementById('activity-edit-form');
        const editId = document.getElementById('activity-edit-id');
        const editStatus = document.getElementById('activity-edit-status');

        let activeStatus = document.body.dataset.statusTab || 'all';
        if (!activeStatus) {
            activeStatus = 'all';
        }

        let currentId =
            openParam === 'weekly'
                ? document.body.dataset.openWeekly || ''
                : document.body.dataset.openActivity || '';
        let currentMode = document.body.dataset.openMode || 'view';

        let sortKey = isWeekly ? 'updated' : 'submitted';
        let sortDir = 'desc';

        function buildIndex() {
            return getRows().map((el) => ({
                el,
                id: el.dataset.id || '',
                ref: el.dataset.ref || '',
                mode: el.dataset.mode || '',
                status: el.dataset.status || '',
                submittedAt: el.dataset.submittedAt || '',
                updatedAt: el.dataset.updatedAt || '',
                search: (el.dataset.search || '').toLowerCase(),
                payload: parseRowDetail(el) || recordsById[el.dataset.id || ''] || null,
            }));
        }

        let recordsIndex = buildIndex();

        function statusMatchesTab(status, tab) {
            return tab === 'all' || status === tab;
        }

        function rowVisible(entry) {
            const q = (searchInput?.value || '').trim().toLowerCase();
            if (q && !entry.search.includes(q)) {
                return false;
            }
            if (hasModeFilter && modeSelect) {
                const mode = modeSelect.value || 'all';
                if (mode !== 'all' && entry.mode !== mode) {
                    return false;
                }
            }
            const from = dateFrom?.value || '';
            const to = dateTo?.value || '';
            const rawDate = isWeekly ? entry.updatedAt : entry.submittedAt;
            const dateField = rawDate ? String(rawDate).slice(0, 10) : '';
            if (from && dateField && dateField < from) {
                return false;
            }
            if (to && dateField && dateField > to) {
                return false;
            }
            if (!statusMatchesTab(entry.status, activeStatus)) {
                return false;
            }

            return true;
        }

        function applyFilters() {
            let visible = 0;
            recordsIndex.forEach((entry) => {
                const show = rowVisible(entry);
                entry.el.hidden = !show;
                if (show) {
                    visible += 1;
                }
            });
            if (emptyEl) {
                emptyEl.hidden = visible > 0;
            }
        }

        function setActiveTab(tabEl) {
            tabs.forEach((t) => {
                const on = t === tabEl;
                t.classList.toggle('is-active', on);
                t.setAttribute('aria-selected', on ? 'true' : 'false');
            });
            activeStatus = tabEl.dataset.statusTab || 'all';
            document.body.dataset.statusTab = activeStatus;
            const url = new URL(window.location.href);
            if (activeStatus === 'all') {
                url.searchParams.delete('status');
            } else {
                url.searchParams.set('status', activeStatus);
            }
            window.history.replaceState({}, '', url);
            applyFilters();
        }

        function renderDetails(p) {
            if (!modalDetails || !p) {
                return;
            }

            const sheetLabel = isWeekly ? 'Weekly summary report' : 'Daily activity report';
            let html =
                '<div class="reports-detail-sheet" role="group" aria-label="' +
                escapeHtml(sheetLabel) +
                '">';

            if (isWeekly) {
                html +=
                    '<section class="reports-detail-sheet__section" aria-label="Report identifiers">' +
                    '<div class="reports-detail-sheet__grid reports-detail-sheet__grid--activity-meta">' +
                    sheetField('Reference', p.ref) +
                    sheetField('Week', p.week_label) +
                    '</div></section>' +
                    '<section class="reports-detail-sheet__section" aria-label="Assignment">' +
                    '<div class="reports-detail-sheet__grid reports-detail-sheet__grid--people">' +
                    sheetField('Post', p.site_name) +
                    sheetField('Head guard', p.head_guard_name) +
                    '</div></section>' +
                    '<section class="reports-detail-sheet__section" aria-label="Weekly narrative">' +
                    '<div class="reports-detail-sheet__grid reports-detail-sheet__grid--activity-narrative">' +
                    narrativeField('Summary', p.summary) +
                    narrativeField('Highlights', p.highlights) +
                    '</div></section>';
            } else {
                html +=
                    '<section class="reports-detail-sheet__section" aria-label="Report identifiers">' +
                    '<div class="reports-detail-sheet__grid reports-detail-sheet__grid--activity-meta">' +
                    sheetField('Reference', p.ref) +
                    sheetField('Mode', p.activity_mode_label) +
                    '</div></section>' +
                    '<section class="reports-detail-sheet__section" aria-label="Assignment">' +
                    '<div class="reports-detail-sheet__grid reports-detail-sheet__grid--people">' +
                    sheetField('Post', p.site_name) +
                    sheetField('Head guard', p.head_guard_name) +
                    sheetField('Location', p.location_label) +
                    '</div></section>' +
                    renderDailySubmission(p);
            }

            html +=
                '<section class="reports-detail-sheet__section" aria-label="Timestamps">' +
                '<div class="reports-detail-sheet__grid reports-detail-sheet__grid--incident">' +
                sheetField('Submitted', p.submitted_display) +
                sheetField('Last updated', p.updated_display) +
                '</div></section></div>';

            modalDetails.innerHTML = html;
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
                return '<p class="reports-activity-timeline__empty">No activity logged for this report yet.</p>';
            }
            const items = list
                .map((entry, index) => buildHistoryTimelineItemHtml(entry, index === list.length - 1))
                .join('');
            return '<ol class="reports-activity-timeline" role="list">' + items + '</ol>';
        }

        function renderHistory(history) {
            if (!modalHistory) {
                return;
            }
            modalHistory.innerHTML = buildHistoryTimelineHtml(history);
        }

        function setModalMode(mode) {
            const isEdit = mode === 'edit';
            editForm?.toggleAttribute('hidden', !isEdit);
            footerView?.toggleAttribute('hidden', isEdit);
            footerEdit?.toggleAttribute('hidden', !isEdit);
            currentMode = mode;
        }

        function pushUrl(id, mode) {
            const url = new URL(window.location.href);
            if (id) {
                url.searchParams.set(openParam, id);
                url.searchParams.set('mode', mode || 'view');
            } else {
                url.searchParams.delete(openParam);
                url.searchParams.delete('mode');
            }
            window.history.pushState({ [openParam]: id, mode: mode || 'view' }, '', url);
        }

        function resolveRecord(id) {
            if (!id) {
                return null;
            }
            if (recordsById[id]) {
                return recordsById[id];
            }
            const fromRow = recordsIndex.find((r) => r.id === id)?.payload;
            if (fromRow) {
                return fromRow;
            }
            return null;
        }

        function openRecord(id, mode) {
            const p = resolveRecord(id);
            if (!modalOverlay) {
                return;
            }
            if (!p) {
                return;
            }
            currentId = id;
            setModalMode(mode || 'view');
            if (modalRef) {
                modalRef.textContent = p.ref || '—';
            }
            if (modalStatusBadge) {
                const slug = p.status || 'pending';
                const label = p.status_label || STATUS_LABELS[slug] || slug;
                modalStatusBadge.innerHTML =
                    '<span class="reports-badge reports-badge--' +
                    escapeHtml(slug) +
                    '">' +
                    escapeHtml(label) +
                    '</span>';
            }
            renderDetails(p);
            renderHistory(p.history);
            if (editId) {
                editId.value = id;
            }
            if (editStatus) {
                editStatus.value = p.status || '';
            }
            if (gotoEdit) {
                gotoEdit.hidden = false;
            }
            modalOverlay.classList.add('is-open');
            modalOverlay.setAttribute('aria-hidden', 'false');
            document.body.classList.add('activity-registry-modal-open');
            document.body.style.overflow = 'hidden';
            pushUrl(id, mode || 'view');
            recordsIndex.forEach((r) => {
                r.el.classList.toggle('is-selected', r.id === id);
            });
        }

        function closeModal() {
            if (!modalOverlay) {
                return;
            }
            modalOverlay.classList.remove('is-open');
            modalOverlay.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('activity-registry-modal-open');
            document.body.style.overflow = '';
            currentId = '';
            setModalMode('view');
            pushUrl('', 'view');
            recordsIndex.forEach((r) => r.el.classList.remove('is-selected'));
        }

        function compareRows(a, b) {
            const dir = sortDir === 'asc' ? 1 : -1;
            const val = (key, entry) => {
                if (key === 'ref') {
                    return entry.ref;
                }
                if (key === 'headGuard') {
                    return entry.el.dataset.sortHg || '';
                }
                if (key === 'post') {
                    return entry.el.dataset.sortPost || '';
                }
                if (key === 'week') {
                    return entry.el.dataset.sortWeek || '';
                }
                if (key === 'mode') {
                    return entry.mode;
                }
                if (key === 'status') {
                    return entry.status;
                }
                if (key === 'updated') {
                    return entry.updatedAt;
                }
                return entry.submittedAt;
            };
            const av = val(sortKey, a);
            const bv = val(sortKey, b);
            if (av < bv) {
                return -1 * dir;
            }
            if (av > bv) {
                return 1 * dir;
            }
            return 0;
        }

        function applySort() {
            const sorted = [...recordsIndex].sort(compareRows);
            sorted.forEach((entry) => {
                tbody?.appendChild(entry.el);
            });
        }

        sortButtons.forEach((btn) => {
            btn.addEventListener(
                'click',
                () => {
                    const key = btn.dataset.sortKey || 'submitted';
                    if (sortKey === key) {
                        sortDir = sortDir === 'asc' ? 'desc' : 'asc';
                    } else {
                        sortKey = key;
                        sortDir = 'desc';
                    }
                    sortButtons.forEach((b) => b.classList.remove('is-active'));
                    btn.classList.add('is-active');
                    applySort();
                },
                { signal }
            );
        });

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => setActiveTab(tab), { signal });
        });

        searchInput?.addEventListener('input', applyFilters, { signal });
        modeSelect?.addEventListener('change', applyFilters, { signal });
        dateFrom?.addEventListener('change', applyFilters, { signal });
        dateTo?.addEventListener('change', applyFilters, { signal });
        resetBtn?.addEventListener(
            'click',
            () => {
                if (searchInput) {
                    searchInput.value = '';
                }
                if (modeSelect) {
                    modeSelect.value = 'all';
                }
                if (dateFrom) {
                    dateFrom.value = defaultDateFrom;
                }
                if (dateTo) {
                    dateTo.value = defaultDateTo;
                }
                applyFilters();
            },
            { signal }
        );

        root.addEventListener(
            'click',
            (e) => {
                const viewBtn = e.target.closest('[data-action="view"]');
                if (viewBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    const id = viewBtn.dataset.activityId || viewBtn.closest('[data-activity-row]')?.dataset.id || '';
                    if (id) {
                        openRecord(id, 'view');
                    }
                    return;
                }
                const row = e.target.closest('[data-activity-row]');
                if (row && !e.target.closest('.reports-actions')) {
                    const id = row.dataset.id || '';
                    if (id) {
                        openRecord(id, 'view');
                    }
                }
            },
            { signal }
        );

        modalClose?.addEventListener('click', closeModal, { signal });
        modalOverlay?.addEventListener(
            'click',
            (e) => {
                if (e.target === modalOverlay) {
                    closeModal();
                }
            },
            { signal }
        );
        gotoEdit?.addEventListener('click', () => {
            if (currentId) {
                openRecord(currentId, 'edit');
            }
        }, { signal });
        cancelEdit?.addEventListener('click', () => {
            if (currentId) {
                openRecord(currentId, 'view');
            }
        }, { signal });

        document.addEventListener(
            'keydown',
            (e) => {
                if (e.key === 'Escape' && modalOverlay?.classList.contains('is-open')) {
                    closeModal();
                }
            },
            { signal }
        );

        window.addEventListener(
            'popstate',
            (e) => {
                const state = e.state || {};
                const url = new URL(window.location.href);
                const id = state[openParam] || url.searchParams.get(openParam) || '';
                const mode = state.mode || url.searchParams.get('mode') || 'view';
                if (id && resolveRecord(id)) {
                    openRecord(id, mode);
                } else {
                    closeModal();
                }
            },
            { signal }
        );

        applySort();
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

        if (currentId && resolveRecord(currentId)) {
            openRecord(currentId, currentMode);
        } else if (currentId) {
            closeModal();
        }
    }

    window.initActivityRegistryModule = initActivityRegistryModule;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initActivityRegistryModule);
    } else {
        initActivityRegistryModule();
    }
})();
