<?php
declare(strict_types=1);

require_once __DIR__ . '/admin_daily_activity_status.php';
require_once __DIR__ . '/admin_incident_reports.php';
require_once __DIR__ . '/guard_daily_activity.php';

const ADMIN_DAILY_ACTIVITY_SESSION_KEY = 'admin_daily_activity_reports_store';

function admin_daily_activity_history_now(): string
{
    return date('j M Y, H:i');
}

function admin_daily_activity_format_display(string $iso): string
{
    if ($iso === '') {
        return '—';
    }
    $ts = strtotime($iso);

    return $ts !== false ? date('j M Y, H:i', $ts) : $iso;
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function admin_daily_activity_sync_dates(array $row): array
{
    $submittedAt = (string) ($row['submitted_at'] ?? '');
    if (trim((string) ($row['submitted_display'] ?? '')) === '' && $submittedAt !== '') {
        $row['submitted_display'] = admin_daily_activity_format_display($submittedAt);
    }
    $updatedAt = trim((string) ($row['updated_at'] ?? ''));
    if ($updatedAt === '') {
        $row['updated_at'] = $submittedAt;
    }
    if (trim((string) ($row['updated_display'] ?? '')) === '') {
        $row['updated_display'] = admin_daily_activity_format_display((string) $row['updated_at']);
    }

    return $row;
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function admin_daily_activity_normalize(array $row): array
{
    $status = (string) ($row['status'] ?? ADMIN_DAILY_ACTIVITY_STATUS_PENDING);
    if (!admin_daily_activity_status_is_valid($status)) {
        $status = ADMIN_DAILY_ACTIVITY_STATUS_PENDING;
    }

    $mode = (string) ($row['activity_mode'] ?? GUARD_DAILY_ACTIVITY_MODE_NORMAL);
    if (!in_array($mode, [GUARD_DAILY_ACTIVITY_MODE_NORMAL, GUARD_DAILY_ACTIVITY_MODE_EVENT], true)) {
        $mode = GUARD_DAILY_ACTIVITY_MODE_NORMAL;
    }

    $row['status'] = $status;
    $row['status_label'] = admin_daily_activity_status_label($status);
    $defs = admin_daily_activity_status_definitions();
    $row['status_description'] = $defs[$status]['description'] ?? '';
    $row['activity_mode'] = $mode;
    $row['activity_mode_label'] = (string) ($row['activity_mode_label'] ?? '')
        ?: ($mode === GUARD_DAILY_ACTIVITY_MODE_EVENT ? 'With event / activity' : 'Normal operation');
    $row['head_guard_name'] = trim((string) ($row['head_guard_name'] ?? '')) ?: 'Head guard';
    $row['site_name'] = trim((string) ($row['site_name'] ?? '')) ?: '—';

    $details = trim((string) ($row['activity_details'] ?? ''));
    if ($mode !== GUARD_DAILY_ACTIVITY_MODE_EVENT) {
        $details = '';
    }
    $row['activity_details'] = $details;
    $row['summary'] = guard_daily_activity_list_summary($mode, $details);
    $row['attachments'] = admin_daily_activity_resolve_attachments($row);
    $row['photo_count'] = count($row['attachments']);
    $row['history'] = is_array($row['history'] ?? null) ? $row['history'] : [];

    $row = admin_daily_activity_sync_dates($row);

    return $row;
}

/**
 * @param array<string, mixed> $row
 * @return list<array{type: string, label: string, url: string, id?: int}>
 */
function admin_daily_activity_resolve_attachments(array $row): array
{
    $existing = $row['attachments'] ?? null;
    if (is_array($existing) && $existing !== []) {
        return $existing;
    }

    if (!isset($GLOBALS['conn']) || !($GLOBALS['conn'] instanceof PDO)) {
        return [];
    }

    return guard_daily_activity_fetch_evidence_attachments($GLOBALS['conn'], $row);
}

/**
 * @return list<array<string, mixed>>
 */
function admin_daily_activity_seed_reports(): array
{
    $now = date('Y-m-d H:i:s');
    $yesterday = date('Y-m-d H:i:s', strtotime('-1 day'));

    return [
        [
            'id' => 'da-seed-1',
            'ref' => GUARD_DAILY_ACTIVITY_REF_PREFIX . '-2026-0001',
            'head_guard_id' => 'HG-DEMO-01',
            'head_guard_name' => 'Santos, Maria L.',
            'site_name' => 'Ayala Tower One — Lobby',
            'activity_mode' => GUARD_DAILY_ACTIVITY_MODE_NORMAL,
            'summary' => 'Routine opening checklist — all posts manned, no exceptions.',
            'activity_details' => '',
            'location_label' => 'Makati CBD',
            'status' => ADMIN_DAILY_ACTIVITY_STATUS_REVIEWED,
            'submitted_at' => $yesterday,
            'updated_at' => $now,
            'history' => [
                ['at' => admin_daily_activity_history_now(), 'event' => 'Submitted by head guard', 'note' => 'Normal operation'],
                ['at' => admin_daily_activity_history_now(), 'event' => 'Registry: Reviewed', 'note' => 'Logged for weekly roll-up'],
            ],
        ],
        [
            'id' => 'da-seed-2',
            'ref' => GUARD_DAILY_ACTIVITY_REF_PREFIX . '-2026-0002',
            'head_guard_id' => 'HG-DEMO-02',
            'head_guard_name' => 'Reyes, Juan P.',
            'site_name' => 'BGC High Street — Retail cluster',
            'activity_mode' => GUARD_DAILY_ACTIVITY_MODE_EVENT,
            'summary' => 'Vendor delivery blocked gate — coordinated with facilities, resolved in 18 minutes.',
            'activity_details' => 'Unauthorized truck at Gate 3; ID verified; escorted after log entry.',
            'location_label' => 'Taguig',
            'status' => ADMIN_DAILY_ACTIVITY_STATUS_PENDING,
            'submitted_at' => $now,
            'updated_at' => $now,
            'history' => [
                ['at' => admin_daily_activity_history_now(), 'event' => 'Submitted by head guard', 'note' => 'With event / activity — photos attached'],
            ],
        ],
        [
            'id' => 'da-seed-3',
            'ref' => GUARD_DAILY_ACTIVITY_REF_PREFIX . '-2026-0003',
            'head_guard_id' => 'HG-DEMO-03',
            'head_guard_name' => 'Cruz, Ana R.',
            'site_name' => 'Ortigas Center — Podium',
            'activity_mode' => GUARD_DAILY_ACTIVITY_MODE_EVENT,
            'summary' => 'Fire drill observed — muster complete, client rep signed log.',
            'activity_details' => 'Scheduled drill 14:00; all guards accounted; no injuries.',
            'location_label' => 'Pasig',
            'status' => ADMIN_DAILY_ACTIVITY_STATUS_ON_HOLD,
            'submitted_at' => $yesterday,
            'updated_at' => $now,
            'history' => [
                ['at' => admin_daily_activity_history_now(), 'event' => 'Submitted by head guard', 'note' => 'Event with attachments'],
                ['at' => admin_daily_activity_history_now(), 'event' => 'Registry: On hold', 'note' => 'Awaiting client sign-off scan'],
            ],
        ],
    ];
}

/** @return list<array<string, mixed>> */
function admin_daily_activity_store_all(): array
{
    if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof PDO && guard_daily_activity_table_exists($GLOBALS['conn'])) {
        $out = [];
        foreach (guard_daily_activity_fetch_admin_records($GLOBALS['conn']) as $row) {
            $out[] = admin_daily_activity_normalize($row);
        }
        usort($out, static fn (array $a, array $b): int => strcmp((string) ($b['submitted_at'] ?? ''), (string) ($a['submitted_at'] ?? '')));

        return $out;
    }

    if (!isset($_SESSION[ADMIN_DAILY_ACTIVITY_SESSION_KEY]) || !is_array($_SESSION[ADMIN_DAILY_ACTIVITY_SESSION_KEY])) {
        $_SESSION[ADMIN_DAILY_ACTIVITY_SESSION_KEY] = array_map(
            static fn (array $r): array => admin_daily_activity_normalize($r),
            admin_daily_activity_seed_reports()
        );
    }

    $out = [];
    foreach ($_SESSION[ADMIN_DAILY_ACTIVITY_SESSION_KEY] as $row) {
        if (is_array($row)) {
            $out[] = admin_daily_activity_normalize($row);
        }
    }
    usort($out, static fn (array $a, array $b): int => strcmp((string) ($b['submitted_at'] ?? ''), (string) ($a['submitted_at'] ?? '')));

    return $out;
}

/** @param list<array<string, mixed>> $reports */
function admin_daily_activity_store_save(array $reports): void
{
    $_SESSION[ADMIN_DAILY_ACTIVITY_SESSION_KEY] = array_map(
        static fn (array $r): array => admin_daily_activity_normalize($r),
        $reports
    );
}

function admin_daily_activity_store_reset(): void
{
    unset($_SESSION[ADMIN_DAILY_ACTIVITY_SESSION_KEY]);
}

function admin_daily_activity_find(string $id): ?array
{
    if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof PDO && preg_match('/^da-(\d+)$/', $id)) {
        $row = guard_daily_activity_find_by_id($GLOBALS['conn'], $id);
        if ($row !== null) {
            return admin_daily_activity_normalize($row);
        }
    }

    foreach (admin_daily_activity_store_all() as $row) {
        if ((string) ($row['id'] ?? '') === $id) {
            return $row;
        }
    }

    return null;
}

/**
 * @param list<array<string, mixed>> $reports
 * @return array<string, int>
 */
function admin_daily_activity_status_counts(array $reports): array
{
    $counts = ['all' => count($reports)];
    foreach (admin_daily_activity_status_slugs() as $slug) {
        $counts[$slug] = 0;
    }
    foreach ($reports as $report) {
        $status = (string) ($report['status'] ?? ADMIN_DAILY_ACTIVITY_STATUS_PENDING);
        if (isset($counts[$status])) {
            ++$counts[$status];
        }
    }

    return $counts;
}

/**
 * @param array<string, mixed> $report
 */
function admin_daily_activity_search_blob(array $report): string
{
    return strtolower(implode(' ', [
        (string) ($report['ref'] ?? ''),
        (string) ($report['head_guard_name'] ?? ''),
        (string) ($report['site_name'] ?? ''),
        (string) ($report['summary'] ?? ''),
        (string) ($report['activity_mode_label'] ?? ''),
        (string) ($report['location_label'] ?? ''),
    ]));
}

/**
 * @param array<string, mixed> $report
 */
function admin_daily_activity_row_attrs(array $report): string
{
    $detailJson = json_encode($report, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);

    return implode(' ', [
        'data-activity-row',
        'data-id="' . e((string) $report['id']) . '"',
        'data-ref="' . e((string) $report['ref']) . '"',
        'data-mode="' . e((string) $report['activity_mode']) . '"',
        'data-status="' . e((string) $report['status']) . '"',
        'data-submitted-at="' . e((string) $report['submitted_at']) . '"',
        'data-updated-at="' . e(substr((string) ($report['updated_at'] ?? ''), 0, 10)) . '"',
        'data-sort-post="' . e(strtolower((string) ($report['site_name'] ?? ''))) . '"',
        'data-sort-hg="' . e(strtolower((string) ($report['head_guard_name'] ?? ''))) . '"',
        'data-search="' . e(admin_daily_activity_search_blob($report)) . '"',
        'data-detail="' . e($detailJson) . '"',
    ]);
}

/**
 * @param array<string, mixed> $report
 */
function admin_daily_activity_modal_submission_section_html(array $report): string
{
    $mode = (string) ($report['activity_mode'] ?? GUARD_DAILY_ACTIVITY_MODE_NORMAL);
    $html = '<section class="reports-detail-sheet__section" aria-label="Head guard submission">';

    if ($mode === GUARD_DAILY_ACTIVITY_MODE_NORMAL) {
        $html .= '<p class="reports-daily-submission__intro"><strong>Normal operation</strong> — submitted without the event details step. No activity narrative or supporting photos were required on the guard form.</p>';

        return $html . '</section>';
    }

    $html .= '<div class="reports-detail-sheet__grid reports-detail-sheet__grid--activity-narrative">';

    $details = trim((string) ($report['activity_details'] ?? ''));
    $detailsValue = $details !== ''
        ? admin_incident_modal_handwriting_text($details)
        : '—';
    $html .= '<div class="reports-detail-sheet__field reports-detail-sheet__field--description'
        . ($details === '' ? ' is-empty' : '')
        . '"><span class="reports-detail-sheet__label">Activity details</span>'
        . '<span class="reports-detail-sheet__value">' . $detailsValue . '</span></div>';
    $html .= admin_incident_modal_attachments_field_html(
        $report,
        'Supporting photos',
        'No supporting photos attached'
    );
    $html .= '</div></section>';

    return $html;
}

/**
 * @param array<string, mixed> $report
 */
function admin_daily_activity_modal_details_html(array $report): string
{
    $html = '<div class="reports-detail-sheet" role="group" aria-label="Daily activity report">';
    $html .= '<section class="reports-detail-sheet__section" aria-label="Report identifiers">';
    $html .= '<div class="reports-detail-sheet__grid reports-detail-sheet__grid--activity-meta">';
    $html .= admin_incident_modal_sheet_field_html('Reference', (string) ($report['ref'] ?? ''));
    $html .= admin_incident_modal_sheet_field_html('Mode', (string) ($report['activity_mode_label'] ?? ''));
    $html .= '</div></section>';
    $html .= '<section class="reports-detail-sheet__section" aria-label="Assignment">';
    $html .= '<div class="reports-detail-sheet__grid reports-detail-sheet__grid--people">';
    $html .= admin_incident_modal_sheet_field_html('Post', (string) ($report['site_name'] ?? ''));
    $html .= admin_incident_modal_sheet_field_html('Head guard', (string) ($report['head_guard_name'] ?? ''));
    $html .= admin_incident_modal_sheet_field_html('Location', (string) ($report['location_label'] ?? ''));
    $html .= '</div></section>';
    $html .= admin_daily_activity_modal_submission_section_html($report);
    $html .= '<section class="reports-detail-sheet__section" aria-label="Timestamps">';
    $html .= '<div class="reports-detail-sheet__grid reports-detail-sheet__grid--incident">';
    $html .= admin_incident_modal_sheet_field_html('Submitted', (string) ($report['submitted_display'] ?? ''));
    $html .= admin_incident_modal_sheet_field_html('Last updated', (string) ($report['updated_display'] ?? ''));
    $html .= '</div></section>';

    return $html . '</div>';
}

/**
 * @param array<string, mixed> $report
 * @param array<string, mixed> $input
 */
function admin_daily_activity_update(string $id, array $input, string $actorId): ?array
{
    if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof PDO && preg_match('/^da-(\d+)$/', $id, $m)) {
        $updated = guard_daily_activity_admin_update($GLOBALS['conn'], (int) $m[1], $input, $actorId);
        if ($updated !== null) {
            return admin_daily_activity_normalize($updated);
        }

        return null;
    }

    $reports = admin_daily_activity_store_all();
    $index = null;
    foreach ($reports as $i => $row) {
        if ((string) ($row['id'] ?? '') === $id) {
            $index = $i;
            break;
        }
    }
    if ($index === null) {
        return null;
    }

    $report = $reports[$index];
    $oldStatus = (string) ($report['status'] ?? ADMIN_DAILY_ACTIVITY_STATUS_PENDING);
    $status = (string) ($input['status'] ?? $oldStatus);
    if (!admin_daily_activity_status_is_valid($status) || $status === ADMIN_DAILY_ACTIVITY_STATUS_ARCHIVED) {
        $status = $oldStatus;
    }

    if ($status !== $oldStatus) {
        $report['status'] = $status;
        $history = is_array($report['history'] ?? null) ? $report['history'] : [];
        $history[] = [
            'at' => admin_daily_activity_history_now(),
            'event' => 'Registry: ' . admin_daily_activity_status_label($status),
            'note' => 'Status updated by admin.',
            'actor' => $actorId,
        ];
        $report['history'] = $history;
        $report['updated_at'] = date('Y-m-d H:i:s');
        $report['updated_display'] = admin_daily_activity_history_now();
    }

    $reports[$index] = admin_daily_activity_normalize($report);
    admin_daily_activity_store_save($reports);

    return $reports[$index];
}

/**
 * Mark a daily activity report as archived (closed).
 *
 * @return array<string, mixed>|null
 */
function admin_daily_activity_archive(string $id, string $actorId): ?array
{
    $id = trim($id);
    if ($id === '') {
        return null;
    }

    $report = admin_daily_activity_find($id);
    if ($report === null) {
        return null;
    }

    if (admin_daily_activity_is_archived((string) ($report['status'] ?? ''))) {
        return admin_daily_activity_normalize($report);
    }

    $previousStatus = (string) ($report['status'] ?? ADMIN_DAILY_ACTIVITY_STATUS_PENDING);
    $updated = admin_daily_activity_update($id, [
        'status' => ADMIN_DAILY_ACTIVITY_STATUS_ARCHIVED,
    ], $actorId);

    if ($updated !== null) {
        require_once __DIR__ . '/admin_report_recovery.php';
        admin_report_recovery_log('daily-activity', 'archived', $updated, $actorId, $previousStatus);
    }

    return $updated;
}

function admin_daily_activity_action_icon(string $action): string
{
    $kind = match ($action) {
        'print' => 'print',
        'archive' => 'archive',
        default => 'view',
    };

    return admin_incident_action_icon($kind);
}
