<?php
declare(strict_types=1);

require_once __DIR__ . '/admin_weekly_activity_status.php';
require_once __DIR__ . '/admin_incident_reports.php'; // modal field helpers

const ADMIN_WEEKLY_ACTIVITY_SESSION_KEY = 'admin_weekly_activity_reports_store';
const GUARD_WEEKLY_ACTIVITY_REF_PREFIX = 'GWA';

function admin_weekly_activity_history_now(): string
{
    return date('j M Y, H:i');
}

function admin_weekly_activity_format_display(string $iso): string
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
function admin_weekly_activity_sync_dates(array $row): array
{
    $submittedAt = (string) ($row['submitted_at'] ?? '');
    if (trim((string) ($row['submitted_display'] ?? '')) === '' && $submittedAt !== '') {
        $row['submitted_display'] = admin_weekly_activity_format_display($submittedAt);
    }
    $updatedAt = trim((string) ($row['updated_at'] ?? ''));
    if ($updatedAt === '') {
        $row['updated_at'] = $submittedAt;
    }
    if (trim((string) ($row['updated_display'] ?? '')) === '') {
        $row['updated_display'] = admin_weekly_activity_format_display((string) $row['updated_at']);
    }

    return $row;
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function admin_weekly_activity_normalize(array $row): array
{
    $status = (string) ($row['status'] ?? ADMIN_WEEKLY_ACTIVITY_STATUS_PENDING);
    if (!admin_weekly_activity_status_is_valid($status)) {
        $status = ADMIN_WEEKLY_ACTIVITY_STATUS_PENDING;
    }

    $row['status'] = $status;
    $row['status_label'] = admin_weekly_activity_status_label($status);
    $defs = admin_weekly_activity_status_definitions();
    $row['status_description'] = $defs[$status]['description'] ?? '';
    $row['head_guard_name'] = trim((string) ($row['head_guard_name'] ?? '')) ?: 'Head guard';
    $row['site_name'] = trim((string) ($row['site_name'] ?? '')) ?: '—';
    $row['week_label'] = trim((string) ($row['week_label'] ?? '')) ?: '—';
    $row['summary'] = trim((string) ($row['summary'] ?? '')) ?: '—';
    $row['history'] = is_array($row['history'] ?? null) ? $row['history'] : [];

    $row = admin_weekly_activity_sync_dates($row);

    return $row;
}

/**
 * @return list<array<string, mixed>>
 */
function admin_weekly_activity_seed_reports(): array
{
    return [
        [
            'id' => 'war-seed-1',
            'ref' => GUARD_WEEKLY_ACTIVITY_REF_PREFIX . '-2026-0001',
            'week_label' => '12–18 May 2026',
            'week_start' => '2026-05-12',
            'week_end' => '2026-05-18',
            'head_guard_id' => 'HG-DEMO-01',
            'head_guard_name' => 'Santos, Maria L.',
            'site_name' => 'Ayala Tower One — Lobby',
            'summary' => 'All shifts covered; one client walk-through; zero open incidents.',
            'highlights' => 'Completed fire drill coordination; trained 2 relievers on access control SOP.',
            'status' => ADMIN_WEEKLY_ACTIVITY_STATUS_APPROVED,
            'submitted_at' => '2026-05-18 17:30:00',
            'updated_at' => '2026-05-19 09:15:00',
            'history' => [
                ['at' => '18 May 2026, 17:30', 'event' => 'Submitted by head guard', 'note' => 'Weekly summary filed'],
                ['at' => '19 May 2026, 09:15', 'event' => 'Registry: Approved', 'note' => 'Filed for client weekly pack'],
            ],
        ],
        [
            'id' => 'war-seed-2',
            'ref' => GUARD_WEEKLY_ACTIVITY_REF_PREFIX . '-2026-0002',
            'week_label' => '12–18 May 2026',
            'week_start' => '2026-05-12',
            'week_end' => '2026-05-18',
            'head_guard_id' => 'HG-DEMO-02',
            'head_guard_name' => 'Reyes, Juan P.',
            'site_name' => 'BGC High Street — Retail cluster',
            'summary' => 'Two vendor incidents escalated; both closed same day. Manning at 98%.',
            'highlights' => 'Gate 3 delivery policy briefing with facilities; overtime approved for weekend cover.',
            'status' => ADMIN_WEEKLY_ACTIVITY_STATUS_PENDING,
            'submitted_at' => '2026-05-18 18:05:00',
            'updated_at' => '2026-05-18 18:05:00',
            'history' => [
                ['at' => '18 May 2026, 18:05', 'event' => 'Submitted by head guard', 'note' => 'Pending operations review'],
            ],
        ],
        [
            'id' => 'war-seed-3',
            'ref' => GUARD_WEEKLY_ACTIVITY_REF_PREFIX . '-2026-0003',
            'week_label' => '5–11 May 2026',
            'week_start' => '2026-05-05',
            'week_end' => '2026-05-11',
            'head_guard_id' => 'HG-DEMO-03',
            'head_guard_name' => 'Cruz, Ana R.',
            'site_name' => 'Ortigas Center — Podium',
            'summary' => 'Returned for missing drill attendance sheet — resubmit requested.',
            'highlights' => 'Draft accomplishments; drill log attachment pending.',
            'status' => ADMIN_WEEKLY_ACTIVITY_STATUS_RETURNED,
            'submitted_at' => '2026-05-11 16:20:00',
            'updated_at' => '2026-05-12 10:00:00',
            'history' => [
                ['at' => '11 May 2026, 16:20', 'event' => 'Submitted by head guard', 'note' => 'Initial submission'],
                ['at' => '12 May 2026, 10:00', 'event' => 'Registry: Returned', 'note' => 'Attach signed drill log'],
            ],
        ],
        [
            'id' => 'war-seed-4',
            'ref' => GUARD_WEEKLY_ACTIVITY_REF_PREFIX . '-2026-0004',
            'week_label' => '19–25 May 2026',
            'week_start' => '2026-05-19',
            'week_end' => '2026-05-25',
            'head_guard_id' => 'HG-DEMO-01',
            'head_guard_name' => 'Santos, Maria L.',
            'site_name' => 'Ayala Tower One — Lobby',
            'summary' => 'In progress — draft not yet submitted.',
            'highlights' => '',
            'status' => ADMIN_WEEKLY_ACTIVITY_STATUS_DRAFT,
            'submitted_at' => '',
            'updated_at' => '2026-05-20 08:00:00',
            'history' => [
                ['at' => '20 May 2026, 08:00', 'event' => 'Draft saved', 'note' => 'Head guard workspace'],
            ],
        ],
    ];
}

/** @return list<array<string, mixed>> */
function admin_weekly_activity_store_all(): array
{
    if (!isset($_SESSION[ADMIN_WEEKLY_ACTIVITY_SESSION_KEY]) || !is_array($_SESSION[ADMIN_WEEKLY_ACTIVITY_SESSION_KEY])) {
        $_SESSION[ADMIN_WEEKLY_ACTIVITY_SESSION_KEY] = array_map(
            static fn (array $r): array => admin_weekly_activity_normalize($r),
            admin_weekly_activity_seed_reports()
        );
    }

    $out = [];
    foreach ($_SESSION[ADMIN_WEEKLY_ACTIVITY_SESSION_KEY] as $row) {
        if (is_array($row)) {
            $out[] = admin_weekly_activity_normalize($row);
        }
    }
    usort($out, static fn (array $a, array $b): int => strcmp((string) ($b['updated_at'] ?? ''), (string) ($a['updated_at'] ?? '')));

    return $out;
}

/** @param list<array<string, mixed>> $reports */
function admin_weekly_activity_store_save(array $reports): void
{
    $_SESSION[ADMIN_WEEKLY_ACTIVITY_SESSION_KEY] = array_map(
        static fn (array $r): array => admin_weekly_activity_normalize($r),
        $reports
    );
}

function admin_weekly_activity_store_reset(): void
{
    unset($_SESSION[ADMIN_WEEKLY_ACTIVITY_SESSION_KEY]);
}

function admin_weekly_activity_find(string $id): ?array
{
    foreach (admin_weekly_activity_store_all() as $row) {
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
function admin_weekly_activity_status_counts(array $reports): array
{
    $counts = ['all' => count($reports)];
    foreach (admin_weekly_activity_status_slugs() as $slug) {
        $counts[$slug] = 0;
    }
    foreach ($reports as $report) {
        $status = (string) ($report['status'] ?? ADMIN_WEEKLY_ACTIVITY_STATUS_PENDING);
        if (isset($counts[$status])) {
            ++$counts[$status];
        }
    }

    return $counts;
}

/**
 * @param array<string, mixed> $report
 */
function admin_weekly_activity_search_blob(array $report): string
{
    return strtolower(implode(' ', [
        (string) ($report['ref'] ?? ''),
        (string) ($report['week_label'] ?? ''),
        (string) ($report['head_guard_name'] ?? ''),
        (string) ($report['site_name'] ?? ''),
        (string) ($report['summary'] ?? ''),
        (string) ($report['highlights'] ?? ''),
    ]));
}

/**
 * @param array<string, mixed> $report
 */
function admin_weekly_activity_row_attrs(array $report): string
{
    $detailJson = json_encode($report, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);

    return implode(' ', [
        'data-activity-row',
        'data-id="' . e((string) $report['id']) . '"',
        'data-ref="' . e((string) $report['ref']) . '"',
        'data-status="' . e((string) $report['status']) . '"',
        'data-submitted-at="' . e((string) $report['submitted_at']) . '"',
        'data-updated-at="' . e(substr((string) ($report['updated_at'] ?? ''), 0, 10)) . '"',
        'data-sort-week="' . e(strtolower((string) ($report['week_label'] ?? ''))) . '"',
        'data-sort-hg="' . e(strtolower((string) ($report['head_guard_name'] ?? ''))) . '"',
        'data-search="' . e(admin_weekly_activity_search_blob($report)) . '"',
        'data-detail="' . e($detailJson) . '"',
    ]);
}

/**
 * @param array<string, mixed> $report
 */
function admin_weekly_activity_modal_details_html(array $report): string
{
    $html = '<div class="reports-detail-sheet">';
    $html .= admin_incident_modal_sheet_field_html('Reference', (string) ($report['ref'] ?? ''));
    $html .= admin_incident_modal_sheet_field_html('Week', (string) ($report['week_label'] ?? ''));
    $html .= admin_incident_modal_sheet_field_html('Head guard', (string) ($report['head_guard_name'] ?? ''));
    $html .= admin_incident_modal_sheet_field_html('Site / post', (string) ($report['site_name'] ?? ''));
    $html .= admin_incident_modal_sheet_field_html('Summary', (string) ($report['summary'] ?? ''), 'wide');
    $html .= admin_incident_modal_sheet_field_html('Highlights', (string) ($report['highlights'] ?? ''), 'wide');
    $html .= admin_incident_modal_sheet_field_html('Submitted', (string) ($report['submitted_display'] ?? ''));
    $html .= admin_incident_modal_sheet_field_html('Last updated', (string) ($report['updated_display'] ?? ''));

    return $html . '</div>';
}

/**
 * @param array<string, mixed> $input
 */
function admin_weekly_activity_update(string $id, array $input, string $actorId): ?array
{
    $reports = admin_weekly_activity_store_all();
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
    $oldStatus = (string) ($report['status'] ?? ADMIN_WEEKLY_ACTIVITY_STATUS_PENDING);
    $status = (string) ($input['status'] ?? $oldStatus);
    if (!admin_weekly_activity_status_is_valid($status)) {
        $status = $oldStatus;
    }

    if ($status !== $oldStatus) {
        $report['status'] = $status;
        $history = is_array($report['history'] ?? null) ? $report['history'] : [];
        $history[] = [
            'at' => admin_weekly_activity_history_now(),
            'event' => 'Registry: ' . admin_weekly_activity_status_label($status),
            'note' => 'Status updated by admin.',
            'actor' => $actorId,
        ];
        $report['history'] = $history;
        $report['updated_at'] = date('Y-m-d H:i:s');
        $report['updated_display'] = admin_weekly_activity_history_now();
    }

    $reports[$index] = admin_weekly_activity_normalize($report);
    admin_weekly_activity_store_save($reports);

    return $reports[$index];
}

function admin_weekly_activity_action_icon(string $action): string
{
    return admin_ui_icon('eye', 16);
}
