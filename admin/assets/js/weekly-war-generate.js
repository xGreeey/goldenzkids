/**
 * Weekly WAR generate — preview daily activity + incidents, then confirm POST.
 */
(function () {
    'use strict';

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    function initWeeklyWarGenerate() {
        const form = document.getElementById('weekly-generate-war-form');
        const previewBtn = document.getElementById('weekly-war-preview-btn');
        const module = document.getElementById('weekly-activity-module');
        const overlay = document.getElementById('war-preview-overlay');
        const body = document.getElementById('war-preview-body');
        const confirmBtn = document.getElementById('war-preview-confirm');
        const cancelBtn = document.getElementById('war-preview-cancel');
        const closeBtn = document.getElementById('war-preview-close');

        if (!form || !previewBtn || !overlay || !body || !confirmBtn) {
            return;
        }

        if (form.dataset.warGenerateBound === '1') {
            return;
        }
        form.dataset.warGenerateBound = '1';

        if (window.__weeklyWarAbort) {
            window.__weeklyWarAbort.abort();
        }
        window.__weeklyWarAbort = new AbortController();
        const signal = window.__weeklyWarAbort.signal;

        if (overlay.parentElement !== document.body) {
            document.body.appendChild(overlay);
        }

        const previewUrl = module?.dataset.warPreviewUrl || 'api/weekly-war-preview.php';
        const csrf = module?.dataset.csrf || form.querySelector('input[name="_csrf"]')?.value || '';

        function closePreview() {
            overlay.classList.remove('is-open');
            overlay.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('activity-registry-modal-open');
            if (!document.getElementById('activity-modal-overlay')?.classList.contains('is-open')) {
                document.body.style.overflow = '';
            }
            confirmBtn.disabled = true;
        }

        function openPreview() {
            overlay.classList.add('is-open');
            overlay.setAttribute('aria-hidden', 'false');
            document.body.classList.add('activity-registry-modal-open');
            document.body.style.overflow = 'hidden';
        }

        function renderTable(headers, rows) {
            if (!rows.length) {
                return '';
            }
            const headHtml = headers
                .map((h) => '<th scope="col">' + escapeHtml(h) + '</th>')
                .join('');
            const bodyHtml = rows
                .map((cells) => {
                    const tds = cells
                        .map((c) => '<td>' + escapeHtml(c) + '</td>')
                        .join('');
                    return '<tr>' + tds + '</tr>';
                })
                .join('');
            return (
                '<div class="reports-war-preview__table-wrap">' +
                '<table class="reports-war-preview__table">' +
                '<thead><tr>' +
                headHtml +
                '</tr></thead><tbody>' +
                bodyHtml +
                '</tbody></table></div>'
            );
        }

        function renderPreviewStats(preview) {
            const dailyCount = Number(preview.daily_count) || 0;
            const normalCount = Number(preview.normal_count) || 0;
            const eventCount = Number(preview.event_count) || 0;
            const incidentCount = Number(preview.incident_count) || 0;

            const stats = [
                { key: 'daily', label: 'Daily reports', value: dailyCount },
                { key: 'normal', label: 'Normal', value: normalCount },
                { key: 'event', label: 'Event / activity', value: eventCount },
                { key: 'incidents', label: 'Incidents', value: incidentCount },
            ];

            const items = stats
                .map(
                    (stat) =>
                        '<article class="reports-war-preview__stat reports-war-preview__stat--' +
                        escapeHtml(stat.key) +
                        '">' +
                        '<span class="reports-war-preview__stat-value">' +
                        escapeHtml(String(stat.value)) +
                        '</span>' +
                        '<span class="reports-war-preview__stat-label">' +
                        escapeHtml(stat.label) +
                        '</span></article>'
                )
                .join('');

            return (
                '<div class="reports-war-preview__stats" role="group" aria-label="Summary statistics">' +
                items +
                '</div>'
            );
        }

        function renderPreview(preview) {
            const duplicate = !!preview.duplicate_war;
            let html = '<div class="reports-war-preview__content">';

            html +=
                '<section class="reports-war-preview__meta reports-detail-sheet__section">' +
                '<div class="reports-detail-sheet__grid reports-detail-sheet__grid--incident">' +
                '<div class="reports-detail-sheet__field"><span class="reports-detail-sheet__label">Date range</span>' +
                '<span class="reports-detail-sheet__value">' +
                escapeHtml(preview.week_label || preview.week_start + ' – ' + preview.week_end) +
                '</span></div>' +
                '<div class="reports-detail-sheet__field"><span class="reports-detail-sheet__label">Post</span>' +
                '<span class="reports-detail-sheet__value">' +
                escapeHtml(preview.site_name) +
                '</span></div>' +
                '<div class="reports-detail-sheet__field"><span class="reports-detail-sheet__label">Head guard</span>' +
                '<span class="reports-detail-sheet__value">' +
                escapeHtml(preview.head_guard_name) +
                '</span></div></div></section>';

            if (duplicate) {
                html +=
                    '<p class="reports-war-preview__alert" role="alert">' +
                    'A weekly summary already exists for this post, head guard, and date range. Choose another range or assignment.</p>';
            }

            html +=
                '<section class="reports-war-preview__section">' +
                '<h3 class="reports-war-preview__section-title">Summary</h3>' +
                renderPreviewStats(preview);
            const highlights = (preview.highlights || '').trim();
            if (
                highlights &&
                highlights !== 'Routine period — no event/activity daily submissions in range.'
            ) {
                html +=
                    '<div class="reports-war-preview__highlights"><span class="reports-war-preview__highlights-label">Highlights</span>' +
                    '<p class="reports-war-preview__highlights-text">' +
                    escapeHtml(highlights) +
                    '</p></div>';
            }
            html += '</section>';

            const daily = Array.isArray(preview.daily_activity) ? preview.daily_activity : [];
            html += '<section class="reports-war-preview__section">';
            html += '<h3 class="reports-war-preview__section-title">Daily activity (' + daily.length + ')</h3>';
            if (daily.length === 0) {
                html += '<p class="reports-war-preview__empty">No daily activity reports in this range for the selected post.</p>';
            } else {
                html += renderTable(
                    ['Reference', 'Submitted', 'Mode', 'Summary', 'Status'],
                    daily.map((row) => [
                        row.ref || '—',
                        row.submitted_display || row.submitted_at || '—',
                        row.activity_mode_label || row.activity_mode || '—',
                        row.summary || '—',
                        row.status_label || '—',
                    ])
                );
            }
            html += '</section>';

            const incidents = Array.isArray(preview.incidents) ? preview.incidents : [];
            html += '<section class="reports-war-preview__section">';
            html += '<h3 class="reports-war-preview__section-title">Incident reports (' + incidents.length + ')</h3>';
            if (incidents.length === 0) {
                html += '<p class="reports-war-preview__empty">No incident reports in this range for the selected post.</p>';
            } else {
                html += renderTable(
                    ['Reference', 'Submitted', 'Type', 'Summary', 'Status'],
                    incidents.map((row) => [
                        row.ref || '—',
                        row.submitted_display || row.submitted_at || '—',
                        row.incident_type || '—',
                        row.summary || '—',
                        row.status_label || '—',
                    ])
                );
            }
            html += '</section></div>';

            body.innerHTML = html;
            confirmBtn.disabled = duplicate;
        }

        function focusFirstInvalidField() {
            const invalid = form.querySelector(':invalid');
            if (invalid && typeof invalid.focus === 'function') {
                invalid.focus();
                if (typeof invalid.reportValidity === 'function') {
                    invalid.reportValidity();
                }
            }
        }

        async function loadPreview() {
            if (!form.reportValidity()) {
                focusFirstInvalidField();
                return;
            }

            body.innerHTML = '<p class="reports-war-preview__loading">Loading preview…</p>';
            confirmBtn.disabled = true;
            openPreview();

            const fd = new FormData(form);
            try {
                const headers = { Accept: 'application/json' };
                if (csrf) {
                    headers['X-CSRF-Token'] = csrf;
                }
                const res = await fetch(previewUrl, {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin',
                    headers,
                });
                const data = await res.json();
                if (!data.ok) {
                    body.innerHTML =
                        '<p class="reports-war-preview__error" role="alert">' +
                        escapeHtml(data.error || 'Could not load preview.') +
                        '</p>';
                    return;
                }
                renderPreview(data.preview || {});
            } catch (err) {
                body.innerHTML =
                    '<p class="reports-war-preview__error" role="alert">Could not load preview. Check your connection and try again.</p>';
            }
        }

        previewBtn.addEventListener('click', loadPreview, { signal });

        form.addEventListener(
            'submit',
            (e) => {
                e.preventDefault();
                loadPreview();
            },
            { signal }
        );

        confirmBtn.addEventListener(
            'click',
            () => {
                if (confirmBtn.disabled || !form.reportValidity()) {
                    focusFirstInvalidField();
                    return;
                }
                closePreview();
                form.submit();
            },
            { signal }
        );

        cancelBtn?.addEventListener('click', closePreview, { signal });
        closeBtn?.addEventListener('click', closePreview, { signal });
        overlay.addEventListener(
            'click',
            (e) => {
                if (e.target === overlay) {
                    closePreview();
                }
            },
            { signal }
        );
        document.addEventListener(
            'keydown',
            (e) => {
                if (e.key === 'Escape' && overlay.classList.contains('is-open')) {
                    closePreview();
                }
            },
            { signal }
        );
    }

    window.initWeeklyWarGenerate = initWeeklyWarGenerate;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initWeeklyWarGenerate);
    } else {
        initWeeklyWarGenerate();
    }
})();
