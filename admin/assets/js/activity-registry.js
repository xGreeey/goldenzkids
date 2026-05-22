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
        const allRecords = dataEl ? safeParse(dataEl.textContent) : [];
        const STATUS_LABELS = statusLabelsEl ? safeParse(statusLabelsEl.textContent) : {};

        const recordsById = {};
        if (Array.isArray(allRecords)) {
            allRecords.forEach((r) => {
                if (r && r.id) {
                    recordsById[r.id] = r;
                }
            });
        }

        const searchInput = document.getElementById('activity-search');
        const modeSelect = hasModeFilter ? document.getElementById('activity-mode') : null;
        const dateFrom = document.getElementById('activity-date-from');
        const dateTo = document.getElementById('activity-date-to');
        const resetBtn = document.getElementById('activity-reset');
        const emptyEl = document.getElementById('activity-empty');
        const tbody = document.getElementById('activity-tbody');
        const tabs = Array.from(root.querySelectorAll('[data-status-tab]'));
        const sortButtons = Array.from(root.querySelectorAll('.reports-sort[data-sort-key]'));

        const modalOverlay = document.getElementById('activity-modal-overlay');
        const modalClose = document.getElementById('activity-modal-close');
        const modalRef = document.getElementById('activity-modal-ref');
        const modalStatusBadge = document.getElementById('activity-modal-status-badge');
        const modalDetails = document.getElementById('activity-modal-details');
        const modalHistory = document.getElementById('activity-modal-history');
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

        function getRows() {
            return Array.from(root.querySelectorAll('[data-activity-row]'));
        }

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
                payload: safeParse(el.dataset.detail || '') || recordsById[el.dataset.id || ''] || null,
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
            const dateField = isWeekly ? entry.updatedAt : entry.submittedAt;
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
            const fields = isWeekly
                ? [
                      ['Reference', p.ref],
                      ['Week', p.week_label],
                      ['Head guard', p.head_guard_name],
                      ['Site / post', p.site_name],
                      ['Summary', p.summary],
                      ['Highlights', p.highlights || '—'],
                      ['Submitted', p.submitted_display || '—'],
                      ['Last updated', p.updated_display || '—'],
                  ]
                : [
                      ['Reference', p.ref],
                      ['Head guard', p.head_guard_name],
                      ['Site', p.site_name],
                      ['Mode', p.activity_mode_label],
                      ['Summary', p.summary],
                      ['Activity details', p.activity_details || '—'],
                      ['Location', p.location_label || '—'],
                      ['Submitted', p.submitted_display || '—'],
                      ['Last updated', p.updated_display || '—'],
                  ];
            let html = '<div class="reports-detail-sheet">';
            fields.forEach(([label, value]) => {
                const v = String(value ?? '').trim() || '—';
                html +=
                    '<div class="reports-detail-sheet__field"><span class="reports-detail-sheet__label">' +
                    escapeHtml(label) +
                    '</span><span class="reports-detail-sheet__value">' +
                    escapeHtml(v) +
                    '</span></div>';
            });
            html += '</div>';
            modalDetails.innerHTML = html;
        }

        function renderHistory(history) {
            if (!modalHistory) {
                return;
            }
            const list = Array.isArray(history) ? history : [];
            if (list.length === 0) {
                modalHistory.innerHTML = '<li class="reports-timeline__item"><em>No history yet.</em></li>';
                return;
            }
            modalHistory.innerHTML = list
                .map((entry) => {
                    const note = entry.note ? '<p>' + escapeHtml(entry.note) + '</p>' : '';
                    return (
                        '<li class="reports-timeline__item"><span class="reports-timeline__time">' +
                        escapeHtml(entry.at || '') +
                        '</span><strong>' +
                        escapeHtml(entry.event || '') +
                        '</strong>' +
                        note +
                        '</li>'
                    );
                })
                .join('');
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

        function openRecord(id, mode) {
            const p = recordsById[id] || recordsIndex.find((r) => r.id === id)?.payload;
            if (!p || !modalOverlay) {
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
                if (key === 'site') {
                    return entry.el.dataset.sortSite || '';
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
                    const id = viewBtn.dataset.activityId || '';
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
                if (id && recordsById[id]) {
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

        if (currentId && recordsById[currentId]) {
            openRecord(currentId, currentMode);
        }
    }

    window.initActivityRegistryModule = initActivityRegistryModule;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initActivityRegistryModule);
    } else {
        initActivityRegistryModule();
    }
})();
