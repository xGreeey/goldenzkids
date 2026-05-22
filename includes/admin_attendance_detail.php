<?php
declare(strict_types=1);

/**
 * DTR — Daily Time Record (registry, NTE, missing/wrong time-in/out).
 */

require_once __DIR__ . '/admin_incident_reports.php';
require_once __DIR__ . '/admin_incident_status.php';
require_once __DIR__ . '/admin_activity_registry_ui.php';
require_once __DIR__ . '/guard_dad.php';

const ADMIN_ATTENDANCE_SESSION_KEY = 'admin_attendance_detail_store';

/** Full module name shown in page titles and section headers. */
const ADMIN_ATTENDANCE_MODULE_LABEL = 'Daily Time Record';

/** Registry reference code prefix (e.g. DTR-2026-0201). */
const ADMIN_ATTENDANCE_REF_CODE = 'DTR';

/** Admin registry page (canonical URL). */
const ADMIN_ATTENDANCE_PAGE = 'dtr.php';

function admin_attendance_page_path(): string
{
    return ADMIN_ATTENDANCE_PAGE;
}

/** @deprecated Use ADMIN_INCIDENT_STATUS_ONGOING */
const ADMIN_ATTENDANCE_STATUS_PENDING = ADMIN_INCIDENT_STATUS_ONGOING;
/** @deprecated Legacy slug; maps to open */
const ADMIN_ATTENDANCE_STATUS_NTE = ADMIN_INCIDENT_STATUS_ONGOING;
const ADMIN_ATTENDANCE_STATUS_ON_HOLD = ADMIN_INCIDENT_STATUS_ON_HOLD;
/** @deprecated Use ADMIN_INCIDENT_STATUS_ACCOMPLISHED */
const ADMIN_ATTENDANCE_STATUS_RESOLVED = ADMIN_INCIDENT_STATUS_ACCOMPLISHED;
/** @deprecated Use ADMIN_INCIDENT_STATUS_DENIED */
const ADMIN_ATTENDANCE_STATUS_DISMISSED = ADMIN_INCIDENT_STATUS_DENIED;

/** Map legacy DTR status slugs to the shared case registry. */
function admin_attendance_status_normalize(string $status): string
{
    $status = strtolower(trim($status));

    return match ($status) {
        'pending', 'nte' => ADMIN_INCIDENT_STATUS_ONGOING,
        'resolved' => ADMIN_INCIDENT_STATUS_ACCOMPLISHED,
        'dismissed' => ADMIN_INCIDENT_STATUS_DENIED,
        'on_hold' => ADMIN_INCIDENT_STATUS_ON_HOLD,
        default => admin_incident_status_is_valid($status)
            ? $status
            : ADMIN_INCIDENT_STATUS_ONGOING,
    };
}

/**
 * Case registry aligned with incident reports (Open, On hold, Closed, Not accepted).
 *
 * @return array<string, array{label: string, tab: string, kpi: string, description: string, closed: bool}>
 */
function admin_attendance_status_definitions(): array
{
    $defs = admin_incident_status_definitions();
    $defs[ADMIN_INCIDENT_STATUS_ONGOING]['description'] =
        'Open DTR case — missing or wrong time-in/out, roster mismatch, or NTE under review.';
    $defs[ADMIN_INCIDENT_STATUS_ON_HOLD]['description'] =
        'Case paused — waiting for timekeeping export, HR, or head guard clarification.';
    $defs[ADMIN_INCIDENT_STATUS_ACCOMPLISHED]['description'] =
        'Case closed — corrected in timekeeping, coaching logged, or client sign-off on file.';
    $defs[ADMIN_INCIDENT_STATUS_DENIED]['description'] =
        'Case closed without action — scheduling error, duplicate flag, or withdrawn filing.';

    return $defs;
}

/** @return list<string> */
function admin_attendance_status_slugs(): array
{
    return admin_incident_status_slugs();
}

/** @return array<string, string> */
function admin_attendance_status_options(): array
{
    return admin_incident_status_options();
}

function admin_attendance_status_label(string $status): string
{
    return admin_incident_status_label(admin_attendance_status_normalize($status));
}

function admin_attendance_status_is_valid(string $status): bool
{
    return admin_incident_status_is_valid(admin_attendance_status_normalize($status));
}

/**
 * @return array<string, string>
 */
function admin_attendance_issue_options(): array
{
    return [
        'missing_time_in' => 'Missing time-in',
        'missing_time_out' => 'Missing time-out',
        'wrong_time_in' => 'Wrong time-in',
        'wrong_time_out' => 'Wrong time-out',
        'late_check_in' => 'Late check-in',
        'absent' => 'Absent / no show',
        'duplicate_punch' => 'Duplicate punch',
        'roster_mismatch' => 'Roster mismatch',
    ];
}

function admin_attendance_issue_label(string $issue): string
{
    if ($issue === 'roster_review') {
        return '';
    }

    return admin_attendance_issue_options()[$issue] ?? $issue;
}

/**
 * @return array<string, string>
 */
function admin_attendance_recorded_options(): array
{
    return [
        'present' => 'Present (1.00)',
        'late' => 'Late (0.50)',
        'absent' => 'Absent (0.00)',
        'missing' => 'No value (N/A)',
    ];
}

function admin_attendance_recorded_label(string $recorded): string
{
    return admin_attendance_recorded_options()[$recorded] ?? $recorded;
}

function admin_attendance_recorded_is_valid(string $recorded): bool
{
    return array_key_exists($recorded, admin_attendance_recorded_options());
}

/**
 * @return list<array<string, mixed>>
 */
function admin_attendance_seed_templates(): array
{
    return [
        [
            'id' => 'dad-001',
            'ref' => 'DTR-2026-0201',
            'guard_id' => 'GZ-1042',
            'guard_name' => 'Juan Dela Cruz',
            'head_guard_name' => 'Head Guard Reyes',
            'post' => 'Golden Z-5 Main Office',
            'shift_date' => '2026-05-20',
            'shift_display' => '20 May 2026 — Day shift',
            'issue' => 'missing_time_in',
            'time_record' => 'No time-in logged; time-out 18:04',
            'recorded' => 'missing',
            'submitted_at' => '2026-05-20',
            'submitted_display' => '20 May 2026, 19:10',
            'status' => 'ongoing',
            'summary' => 'Guard forgot morning punch. Head guard flagged before payroll cutoff.',
            'history' => [
                ['at' => '20 May 2026, 19:10', 'event' => 'Flagged by head guard', 'note' => 'Auto-alert from DTR sheet.'],
            ],
        ],
        [
            'id' => 'dad-002',
            'ref' => 'DTR-2026-0198',
            'guard_id' => 'GZ-0877',
            'guard_name' => 'Maria Santos',
            'head_guard_name' => 'Head Guard Cruz',
            'post' => 'Ayala Mall Cebu — North Wing',
            'shift_date' => '2026-05-19',
            'shift_display' => '19 May 2026 — Night shift',
            'issue' => 'wrong_time_out',
            'time_record' => 'Time-out 06:12 (roster ends 06:00)',
            'recorded' => 'late',
            'submitted_at' => '2026-05-19',
            'submitted_display' => '19 May 2026, 08:30',
            'status' => 'ongoing',
            'summary' => 'Twelve-minute overrun; NTE issued for repeated late time-out at same post.',
            'history' => [
                ['at' => '19 May 2026, 08:30', 'event' => 'Flagged by head guard', 'note' => ''],
                ['at' => '19 May 2026, 14:00', 'event' => 'NTE issued', 'note' => 'Guard must submit written explanation within 24h.'],
            ],
        ],
        [
            'id' => 'dad-003',
            'ref' => 'DTR-2026-0194',
            'guard_id' => 'GZ-1203',
            'guard_name' => 'Ramon Garcia',
            'post' => 'SM Seaside — Annex Retail',
            'shift_date' => '2026-05-18',
            'shift_display' => '18 May 2026 — Day shift',
            'issue' => 'absent',
            'time_record' => 'No punches for scheduled shift',
            'recorded' => 'absent',
            'submitted_at' => '2026-05-18',
            'submitted_display' => '18 May 2026, 10:15',
            'status' => 'on_hold',
            'summary' => 'No show on roster; guard claims emergency leave via SMS — HR confirmation pending.',
            'history' => [
                ['at' => '18 May 2026, 10:15', 'event' => 'Marked absent', 'note' => 'Relief guard deployed.'],
                ['at' => '18 May 2026, 16:40', 'event' => 'On hold', 'note' => 'Awaiting HR leave slip scan.'],
            ],
        ],
        [
            'id' => 'dad-004',
            'ref' => 'DTR-2026-0188',
            'guard_id' => 'GZ-0911',
            'guard_name' => 'Elena Reyes',
            'post' => 'Quest Hotel Mactan — Lobby',
            'shift_date' => '2026-05-17',
            'shift_display' => '17 May 2026 — Day shift',
            'issue' => 'late_check_in',
            'time_record' => 'Time-in 08:24 (grace ends 08:15)',
            'recorded' => 'late',
            'submitted_at' => '2026-05-17',
            'submitted_display' => '17 May 2026, 09:00',
            'status' => 'accomplished',
            'summary' => 'Traffic delay documented; coaching note on file. Equivalence set to 0.50.',
            'history' => [
                ['at' => '17 May 2026, 09:00', 'event' => 'Flagged late', 'note' => ''],
                ['at' => '17 May 2026, 11:30', 'event' => 'Resolved', 'note' => 'Timekeeping corrected; verbal coaching logged.'],
            ],
        ],
        [
            'id' => 'dad-005',
            'ref' => 'DTR-2026-0181',
            'guard_id' => 'GZ-1055',
            'guard_name' => 'Carlo Mendoza',
            'post' => 'Cebu IT Park — Tower 1',
            'shift_date' => '2026-05-16',
            'shift_display' => '16 May 2026 — Night shift',
            'issue' => 'missing_time_out',
            'time_record' => 'Time-in 22:01; no time-out',
            'recorded' => 'missing',
            'submitted_at' => '2026-05-16',
            'submitted_display' => '16 May 2026, 23:50',
            'status' => 'ongoing',
            'summary' => 'Forgot logout after handover; patrol log shows guard left post 06:02.',
            'history' => [
                ['at' => '16 May 2026, 23:50', 'event' => 'Flagged by head guard', 'note' => 'Pending DGD cross-check.'],
            ],
        ],
        [
            'id' => 'dad-006',
            'ref' => 'DTR-2026-0175',
            'guard_id' => 'GZ-0788',
            'guard_name' => 'Ana Villanueva',
            'post' => 'Landers Superstore Banilad',
            'shift_date' => '2026-05-15',
            'shift_display' => '15 May 2026 — Day shift',
            'issue' => 'wrong_time_in',
            'time_record' => 'Time-in 07:02 (scheduled 08:00)',
            'recorded' => 'present',
            'submitted_at' => '2026-05-15',
            'submitted_display' => '15 May 2026, 08:20',
            'status' => 'denied',
            'summary' => 'Early punch from previous relief still active — scheduling error, no sanction.',
            'history' => [
                ['at' => '15 May 2026, 08:20', 'event' => 'Flagged', 'note' => ''],
                ['at' => '15 May 2026, 10:00', 'event' => 'Dismissed', 'note' => 'Duplicate session cleared in timekeeping.'],
            ],
        ],
        [
            'id' => 'dad-007',
            'ref' => 'DTR-2026-0169',
            'guard_id' => 'GZ-1120',
            'guard_name' => 'Pedro Navarro',
            'post' => 'University of San Carlos — Gate 2',
            'shift_date' => '2026-05-14',
            'shift_display' => '14 May 2026 — Night shift',
            'issue' => 'duplicate_punch',
            'time_record' => 'Double time-in 22:00 and 22:03',
            'recorded' => 'present',
            'submitted_at' => '2026-05-14',
            'submitted_display' => '14 May 2026, 22:10',
            'status' => 'accomplished',
            'summary' => 'Biometric double tap removed; no pay impact.',
            'history' => [
                ['at' => '14 May 2026, 22:10', 'event' => 'System flag', 'note' => ''],
                ['at' => '15 May 2026, 09:00', 'event' => 'Resolved', 'note' => 'IT removed duplicate punch.'],
            ],
        ],
        [
            'id' => 'dad-008',
            'ref' => 'DTR-2026-0162',
            'guard_id' => 'GZ-0994',
            'guard_name' => 'Liza Fernandez',
            'post' => 'Operations Dispatch',
            'shift_date' => '2026-05-12',
            'shift_display' => '12 May 2026 — Day shift',
            'issue' => 'roster_mismatch',
            'time_record' => 'Punched at IT Park; roster shows Main Office',
            'recorded' => 'missing',
            'submitted_at' => '2026-05-12',
            'submitted_display' => '12 May 2026, 09:45',
            'status' => 'ongoing',
            'summary' => 'Third roster conflict this month; NTE and supervisor interview required.',
            'history' => [
                ['at' => '12 May 2026, 09:45', 'event' => 'Flagged', 'note' => ''],
                ['at' => '12 May 2026, 15:00', 'event' => 'NTE issued', 'note' => 'Explain wrong post punch.'],
            ],
        ],
        [
            'id' => 'dad-009',
            'ref' => 'DTR-2026-0155',
            'guard_id' => 'GZ-0833',
            'guard_name' => 'Miguel Torres',
            'post' => 'Training Facility — Lapu-Lapu',
            'shift_date' => '2026-05-10',
            'shift_display' => '10 May 2026 — Day shift',
            'issue' => 'missing_time_in',
            'time_record' => 'No value entire shift',
            'recorded' => 'missing',
            'submitted_at' => '2026-05-10',
            'submitted_display' => '10 May 2026, 17:00',
            'status' => 'accomplished',
            'summary' => 'Paper DGD signed; backfilled time-in/out after biometric outage.',
            'history' => [
                ['at' => '10 May 2026, 17:00', 'event' => 'Flagged', 'note' => 'Biometric offline 6h.'],
                ['at' => '11 May 2026, 08:00', 'event' => 'Resolved', 'note' => 'Manual entry approved by ops.'],
            ],
        ],
        [
            'id' => 'dad-010',
            'ref' => 'DTR-2026-0148',
            'guard_id' => 'GZ-1066',
            'guard_name' => 'Grace Lim',
            'post' => 'Golden Z-5 Main Office',
            'shift_date' => '2026-05-08',
            'shift_display' => '8 May 2026 — Day shift',
            'issue' => 'late_check_in',
            'time_record' => 'Time-in 08:29',
            'recorded' => 'late',
            'submitted_at' => '2026-05-08',
            'submitted_display' => '8 May 2026, 09:05',
            'status' => 'on_hold',
            'summary' => 'Awaiting client waiver for convoy delay — proof of road closure requested.',
            'history' => [
                ['at' => '8 May 2026, 09:05', 'event' => 'Flagged late', 'note' => ''],
                ['at' => '9 May 2026, 10:00', 'event' => 'On hold', 'note' => 'Pending traffic advisory from post captain.'],
            ],
        ],
    ];
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function admin_attendance_normalize(array $row): array
{
    $id = (string) ($row['id'] ?? '');
    if (preg_match('/^att-(\d{3,})$/i', $id, $m)) {
        $row['id'] = 'dad-' . $m[1];
    }

    $ref = (string) ($row['ref'] ?? '');
    if (preg_match('/^ATT-(.+)$/i', $ref, $m)) {
        $row['ref'] = ADMIN_ATTENDANCE_REF_CODE . '-' . $m[1];
    }

    $status = admin_attendance_status_normalize((string) ($row['status'] ?? ADMIN_INCIDENT_STATUS_ONGOING));

    $issue = (string) ($row['issue'] ?? 'missing_time_in');
    if (!array_key_exists($issue, admin_attendance_issue_options())) {
        $issue = 'missing_time_in';
    }

    $recorded = (string) ($row['recorded'] ?? 'missing');
    if (!admin_attendance_recorded_is_valid($recorded)) {
        $recorded = 'missing';
    }

    $row['status'] = $status;
    $row['status_label'] = admin_attendance_status_label($status);
    $row['status_description'] = admin_attendance_status_definitions()[$status]['description'] ?? '';
    $row['issue'] = $issue;
    $row['issue_label'] = admin_attendance_issue_label($issue);
    $row['recorded'] = $recorded;
    $row['recorded_label'] = admin_attendance_recorded_label($recorded);
    $row['history'] = is_array($row['history'] ?? null) ? $row['history'] : [];

    $row = admin_incident_sync_dates($row);
    $submittedParts = admin_incident_table_date_parts(
        (string) ($row['submitted_at'] ?? ''),
        (string) ($row['submitted_display'] ?? '')
    );
    $row['submitted_table_date'] = $submittedParts['date'];
    $row['submitted_table_time'] = $submittedParts['time'];

    $updatedParts = admin_incident_table_date_parts(
        (string) ($row['updated_at'] ?? ''),
        (string) ($row['updated_display'] ?? '')
    );
    $row['updated_table_date'] = $updatedParts['date'];
    $row['updated_table_time'] = $updatedParts['time'];

    $row['head_guard_id'] = trim((string) ($row['head_guard_id'] ?? ''));
    $headGuardName = trim((string) ($row['head_guard_name'] ?? ''));
    $row['head_guard_name'] = $headGuardName !== '' ? $headGuardName : 'Head guard';

    $history = is_array($row['history'] ?? null) ? $row['history'] : [];
    $row['view_html'] = admin_attendance_modal_details_html($row);
    $row['history_html'] = admin_attendance_history_stepper_html($history, (string) $row['status']);

    return $row;
}

/** @return list<array<string, mixed>> */
function admin_attendance_store_all(): array
{
    if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof PDO && guard_dad_table_exists($GLOBALS['conn'])) {
        $out = [];
        foreach (guard_dad_fetch_admin_records($GLOBALS['conn']) as $row) {
            $out[] = admin_attendance_normalize($row);
        }
        if ($out !== []) {
            usort($out, static fn (array $a, array $b): int => strcmp((string) ($b['submitted_at'] ?? ''), (string) ($a['submitted_at'] ?? '')));

            return $out;
        }
    }

    if (!isset($_SESSION[ADMIN_ATTENDANCE_SESSION_KEY]) || !is_array($_SESSION[ADMIN_ATTENDANCE_SESSION_KEY])) {
        $normalized = [];
        foreach (admin_attendance_seed_templates() as $row) {
            $normalized[] = admin_attendance_normalize($row);
        }
        $_SESSION[ADMIN_ATTENDANCE_SESSION_KEY] = $normalized;
    }

    $out = [];
    foreach ($_SESSION[ADMIN_ATTENDANCE_SESSION_KEY] as $row) {
        if (is_array($row)) {
            $out[] = admin_attendance_normalize($row);
        }
    }

    usort($out, static fn (array $a, array $b): int => strcmp((string) ($b['submitted_at'] ?? ''), (string) ($a['submitted_at'] ?? '')));

    return $out;
}

/** @param list<array<string, mixed>> $records */
function admin_attendance_store_save(array $records): void
{
    $_SESSION[ADMIN_ATTENDANCE_SESSION_KEY] = array_map(
        static fn (array $r): array => admin_attendance_normalize($r),
        $records
    );
}

function admin_attendance_store_reset(): void
{
    unset($_SESSION[ADMIN_ATTENDANCE_SESSION_KEY]);
}

function admin_attendance_find(string $id): ?array
{
    if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof PDO && preg_match('/^dad-(\d+)$/', $id)) {
        $row = guard_dad_find_by_id($GLOBALS['conn'], $id);
        if ($row !== null) {
            return admin_attendance_normalize($row);
        }
    }

    foreach (admin_attendance_store_all() as $row) {
        if ((string) ($row['id'] ?? '') === $id) {
            return $row;
        }
    }

    return null;
}

/** @param list<array<string, mixed>> $records */
function admin_attendance_status_counts(array $records): array
{
    $counts = ['all' => count($records)];
    foreach (admin_attendance_status_slugs() as $slug) {
        $counts[$slug] = 0;
    }
    foreach ($records as $row) {
        $slug = admin_attendance_status_normalize((string) ($row['status'] ?? ''));
        if (isset($counts[$slug])) {
            $counts[$slug] += 1;
        }
    }

    return $counts;
}

/**
 * @param array<string, mixed> $record
 */
function admin_attendance_search_blob(array $record): string
{
    return strtolower(implode(' ', [
        (string) ($record['ref'] ?? ''),
        (string) ($record['guard_id'] ?? ''),
        (string) ($record['guard_name'] ?? ''),
        (string) ($record['post'] ?? ''),
        (string) ($record['issue_label'] ?? ''),
        (string) ($record['time_record'] ?? ''),
        (string) ($record['recorded_label'] ?? ''),
        (string) ($record['summary'] ?? ''),
        (string) ($record['head_guard_name'] ?? ''),
    ]));
}

/**
 * @param array<string, mixed> $record
 */
function admin_attendance_status_badge_html(array $record): string
{
    $slug = admin_attendance_status_normalize((string) ($record['status'] ?? ADMIN_INCIDENT_STATUS_ONGOING));
    $label = (string) ($record['status_label'] ?? admin_attendance_status_label($slug));

    $tip = admin_attendance_status_definitions()[$slug]['description'] ?? '';

    return '<span class="reports-badge reports-badge--' . e($slug) . '" title="' . e($tip) . '">'
        . e($label) . '</span>';
}

/**
 * @param array<string, mixed> $record
 */
function admin_attendance_recorded_badge_html(array $record): string
{
    $slug = (string) ($record['recorded'] ?? 'missing');
    $short = match ($slug) {
        'present' => '1.00',
        'late' => '0.50',
        'absent' => '0.00',
        default => 'N/A',
    };

    return '<span class="reports-equiv reports-equiv--' . e($slug) . '" title="'
        . e((string) ($record['recorded_label'] ?? '')) . '">' . e($short) . '</span>';
}

/**
 * @param array<string, mixed> $record
 */
function admin_attendance_guard_cell_html(array $record): string
{
    $name = (string) ($record['guard_name'] ?? '—');
    $id = (string) ($record['guard_id'] ?? '');

    return '<div class="reports-hg-cell">'
        . '<span class="reports-hg-name">' . e($name) . '</span>'
        . ($id !== '' ? '<span class="reports-hg-username mono" title="Employee ID">' . e($id) . '</span>' : '')
        . '</div>';
}

function admin_attendance_modal_sheet_field_html(string $label, string $value, string $modifier = ''): string
{
    $trimmed = trim($value);
    $mod = $modifier !== '' ? ' reports-detail-sheet__field--' . $modifier : '';
    $empty = $trimmed === '' ? ' is-empty' : '';

    return '<div class="reports-detail-sheet__field' . $mod . $empty . '">'
        . '<span class="reports-detail-sheet__label">' . e($label) . '</span>'
        . '<span class="reports-detail-sheet__value">' . e($trimmed !== '' ? $trimmed : '—') . '</span>'
        . '</div>';
}

/**
 * @param array<string, mixed> $record
 */
function admin_attendance_modal_record_sheet_html(array $record): string
{
    $guard = trim((string) ($record['guard_name'] ?? ''));
    $guardId = trim((string) ($record['guard_id'] ?? ''));
    if ($guard !== '' && $guardId !== '') {
        $guard .= ' (' . $guardId . ')';
    } elseif ($guard === '' && $guardId !== '') {
        $guard = $guardId;
    }

    $html = '<div class="reports-dad-below"><div class="reports-detail-sheet" role="group" aria-label="DTR record details">';

    $html .= '<section class="reports-detail-sheet__section" aria-label="Assignment">';
    $html .= '<h4 class="reports-dad-section-heading">Assignment</h4>';
    $html .= '<div class="reports-detail-sheet__grid reports-detail-sheet__grid--people">';
    $html .= admin_attendance_modal_sheet_field_html('Guard', $guard);
    $html .= admin_attendance_modal_sheet_field_html('Post', (string) ($record['post'] ?? ''));
    $html .= admin_attendance_modal_sheet_field_html('Head guard', (string) ($record['head_guard_name'] ?? ''));
    $html .= '</div></section>';

    $html .= '<section class="reports-detail-sheet__section" aria-label="Timekeeping">';
    $html .= '<h4 class="reports-dad-section-heading">Timekeeping</h4>';
    $html .= '<div class="reports-detail-sheet__grid reports-dad-sheet__grid--timekeeping">';
    $html .= admin_attendance_modal_sheet_field_html('Shift', (string) ($record['shift_display'] ?? $record['shift_date'] ?? ''));
    $html .= admin_attendance_modal_sheet_field_html('Time record', (string) ($record['time_record'] ?? ''));
    $html .= admin_attendance_modal_sheet_field_html('Issue', (string) ($record['issue_label'] ?? ''));
    $html .= admin_attendance_modal_sheet_field_html('Equivalence', (string) ($record['recorded_label'] ?? ''));
    $html .= admin_attendance_modal_sheet_field_html('Status', (string) ($record['status_label'] ?? ''));
    $html .= admin_attendance_modal_sheet_field_html('Submitted', (string) ($record['submitted_display'] ?? ''));
    $html .= admin_attendance_modal_sheet_field_html('Updated', (string) ($record['updated_display'] ?? '—'));
    $html .= '</div></section>';

    $html .= guard_dad_locations_section_html($record);

    $summary = trim((string) ($record['summary'] ?? ''));
    $html .= '<section class="reports-detail-sheet__section reports-dad-summary' . ($summary === '' ? ' is-empty' : '') . '" aria-label="Summary">';
    $html .= '<h4 class="reports-dad-section-heading">Summary</h4>';
    $html .= '<p class="reports-dad-summary__body">' . e($summary !== '' ? $summary : 'No summary provided.') . '</p>';
    $html .= '</section></div></div>';

    return $html;
}

/**
 * @param array<string, mixed> $record
 */
function admin_attendance_modal_details_html(array $record): string
{
    $html = '';
    if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof PDO) {
        $html .= guard_dad_modal_extras_html($GLOBALS['conn'], $record);
    }

    $html .= admin_attendance_modal_record_sheet_html($record);

    return $html;
}

/**
 * @param array<string, mixed> $input
 */
function guard_dad_admin_update_record(PDO $conn, int $dadId, array $input, string $actorId): ?array
{
    if (!guard_dad_table_exists($conn)) {
        return null;
    }

    $row = db_fetch_one($conn, 'SELECT * FROM guard_dad_submissions WHERE dad_id = ? LIMIT 1', 'i', [$dadId]);
    if ($row === null) {
        return null;
    }

    $mapped = guard_dad_map_row_for_admin($row);
    if ($mapped === null) {
        return null;
    }

    $oldStatus = admin_attendance_status_normalize((string) $mapped['status']);
    $newStatus = admin_attendance_status_normalize((string) ($input['status'] ?? $oldStatus));

    $issue = (string) ($input['issue'] ?? $mapped['issue']);
    if (!array_key_exists($issue, admin_attendance_issue_options())) {
        $issue = (string) $mapped['issue'];
    }

    $recorded = (string) ($input['recorded'] ?? $mapped['recorded']);
    if (!admin_attendance_recorded_is_valid($recorded)) {
        $recorded = (string) $mapped['recorded'];
    }

    $history = is_array($mapped['history'] ?? null) ? $mapped['history'] : [];
    $opsNote = trim((string) ($input['ops_note'] ?? ''));
    if ($opsNote !== '') {
        $mapped = admin_incident_append_history($mapped, 'Ops note', $opsNote, $actorId);
        $history = is_array($mapped['history'] ?? null) ? $mapped['history'] : $history;
    }
    if ($newStatus !== $oldStatus) {
        $mapped = admin_incident_append_history(
            $mapped,
            'Registry: ' . admin_attendance_status_label($newStatus),
            'Registry status updated.',
            $actorId,
            ['source' => 'admin', 'kind' => 'status']
        );
        $history = is_array($mapped['history'] ?? null) ? $mapped['history'] : $history;
    }

    $summary = trim((string) ($input['summary'] ?? $mapped['summary']));
    $post = trim((string) ($input['post'] ?? $mapped['post']));
    $timeRecord = trim((string) ($input['time_record'] ?? $mapped['time_record']));

    db_execute(
        $conn,
        'UPDATE guard_dad_submissions SET
            status = ?, issue = ?, recorded = ?, post_name = ?, time_record = ?, summary = ?,
            history_json = ?, updated_at = NOW()
         WHERE dad_id = ?',
        'sssssssi',
        [
            $newStatus,
            $issue,
            $recorded,
            $post,
            $timeRecord,
            $summary,
            json_encode($history, JSON_THROW_ON_ERROR),
            $dadId,
        ]
    );

    guard_dad_sync_dgd_portal_status($conn, (int) ($row['dgd_report_number'] ?? 0), $newStatus);

    $fresh = db_fetch_one($conn, 'SELECT * FROM guard_dad_submissions WHERE dad_id = ? LIMIT 1', 'i', [$dadId]);
    $mappedFresh = $fresh !== null ? guard_dad_map_row_for_admin($fresh) : null;

    if ($mappedFresh !== null) {
        require_once __DIR__ . '/portal_audit.php';
        portal_audit_log(
            $conn,
            'DTR_UPDATED',
            'Reference: ' . (string) ($mappedFresh['ref'] ?? ('dad-' . $dadId))
                . ($newStatus !== $oldStatus ? '; status → ' . $newStatus : ''),
            (string) ($row['head_guard_company_id'] ?? ''),
            $actorId,
            auth_user_role()
        );
    }

    return $mappedFresh !== null ? admin_attendance_normalize($mappedFresh) : null;
}

/**
 * @param list<array<string, mixed>> $history
 */
function admin_attendance_history_stepper_html(array $history, string $currentStatus): string
{
    unset($currentStatus);

    if ($history === []) {
        return '<p class="reports-activity-timeline__empty">No history yet.</p>';
    }

    return admin_activity_registry_history_timeline_html($history);
}

/**
 * @param array<string, mixed> $input
 */
function admin_attendance_update(string $id, array $input, string $actorId): ?array
{
    if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof PDO && preg_match('/^dad-(\d+)$/', $id, $m)) {
        return guard_dad_admin_update_record($GLOBALS['conn'], (int) $m[1], $input, $actorId);
    }

    $records = admin_attendance_store_all();
    $found = null;
    $idx = null;

    foreach ($records as $i => $row) {
        if ((string) ($row['id'] ?? '') === $id) {
            $found = $row;
            $idx = $i;
            break;
        }
    }

    if ($found === null || $idx === null) {
        return null;
    }

    $oldStatus = admin_attendance_status_normalize((string) $found['status']);
    $newStatus = admin_attendance_status_normalize((string) ($input['status'] ?? $oldStatus));

    $found['status'] = $newStatus;

    $issue = (string) ($input['issue'] ?? $found['issue']);
    if (!array_key_exists($issue, admin_attendance_issue_options())) {
        $issue = (string) $found['issue'];
    }
    $found['issue'] = $issue;

    $recorded = (string) ($input['recorded'] ?? $found['recorded']);
    if (!admin_attendance_recorded_is_valid($recorded)) {
        $recorded = (string) $found['recorded'];
    }
    $found['recorded'] = $recorded;
    $found['post'] = trim((string) ($input['post'] ?? $found['post']));
    $found['time_record'] = trim((string) ($input['time_record'] ?? $found['time_record']));
    $found['summary'] = trim((string) ($input['summary'] ?? $found['summary']));

    $opsNote = trim((string) ($input['ops_note'] ?? ''));
    if ($opsNote !== '') {
        $found = admin_incident_append_history($found, 'Ops note', $opsNote, $actorId);
    }
    if ($newStatus !== $oldStatus) {
        $found = admin_incident_append_history(
            $found,
            'Registry: ' . admin_attendance_status_label($newStatus),
            'Registry status updated.',
            $actorId,
            ['source' => 'admin', 'kind' => 'status']
        );
    }

    $found = admin_incident_touch_updated($found);
    $records[$idx] = admin_attendance_normalize($found);
    admin_attendance_store_save($records);

    if (($newStatus !== $oldStatus || $opsNote !== '') && isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof PDO) {
        require_once __DIR__ . '/portal_audit.php';
        portal_audit_log(
            $GLOBALS['conn'],
            'DTR_UPDATED',
            'Reference: ' . (string) ($records[$idx]['ref'] ?? $id)
                . ($newStatus !== $oldStatus ? '; status → ' . $newStatus : ''),
            (string) ($records[$idx]['submitter_id'] ?? ''),
            $actorId,
            auth_user_role()
        );
    }

    return $records[$idx];
}

/**
 * Delete a DTR registry record (database or demo session).
 *
 * @return array{id:string,ref:string}|null
 */
function admin_attendance_delete(string $id): ?array
{
    $id = trim($id);
    if ($id === '') {
        return null;
    }

    if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof PDO && preg_match('/^dad-(\d+)$/', $id, $m)) {
        if (guard_dad_table_exists($GLOBALS['conn'])) {
            $result = guard_dad_delete_submission($GLOBALS['conn'], (int) $m[1]);
            if (!$result['ok']) {
                return null;
            }

            return [
                'id' => $id,
                'ref' => (string) ($result['ref'] ?? $id),
            ];
        }
    }

    if (!isset($_SESSION[ADMIN_ATTENDANCE_SESSION_KEY]) || !is_array($_SESSION[ADMIN_ATTENDANCE_SESSION_KEY])) {
        return null;
    }

    $records = admin_attendance_store_all();
    $deleted = null;
    $remaining = [];

    foreach ($records as $row) {
        if ((string) ($row['id'] ?? '') === $id) {
            $deleted = $row;
            continue;
        }
        $remaining[] = $row;
    }

    if ($deleted === null) {
        return null;
    }

    admin_attendance_store_save($remaining);

    return [
        'id' => $id,
        'ref' => (string) ($deleted['ref'] ?? $id),
    ];
}

/**
 * DTR monitoring reference (single scroll, like operations guide).
 */
function admin_attendance_monitoring_guide_html(): string
{
    $html = '<nav class="reports-guide-jump" aria-label="Jump to DTR guide section">';
    $html .= '<a class="reports-guide-jump__link" href="#dad-guide-values">Equivalence values</a>';
    $html .= '<a class="reports-guide-jump__link" href="#dad-guide-flow">Review workflow</a>';
    $html .= '<a class="reports-guide-jump__link" href="#dad-guide-nte">NTE &amp; missing</a>';
    $html .= '</nav><div class="reports-guide-document">';

    $html .= '<div class="reports-guide-block" id="dad-guide-values">';
    $html .= '<h2 class="reports-guide-block__title">' . e(ADMIN_ATTENDANCE_REF_CODE) . ' equivalence values</h2>';
    $html .= '<p class="reports-guide-block__lead">How head guards and payroll interpret each shift day.</p>';
    $html .= '<div class="reports-guide-table-wrap"><table class="reports-guide-table">';
    $html .= '<thead><tr><th>Status</th><th>Basis</th><th>Value</th><th>Typical action</th></tr></thead><tbody>';
    $html .= '<tr><td>Present</td><td>Time-in within grace (0–15 min)</td><td>1.00</td><td>Usually no case — monitor only</td></tr>';
    $html .= '<tr><td>Late</td><td>Check-in after grace (16–30 min) or wrong time-out</td><td>0.50</td><td>Flag; coaching if pattern repeats</td></tr>';
    $html .= '<tr><td>Absent</td><td>No punch or confirmed no-show</td><td>0.00</td><td>File DTR case; relief roster update</td></tr>';
    $html .= '<tr><td>No value</td><td>Missing time-in/out or system gap</td><td>N/A</td><td>Pending review → NTE if not fixed in 24h</td></tr>';
    $html .= '</tbody></table></div></div>';

    $html .= '<div class="reports-guide-block" id="dad-guide-flow">';
    $html .= '<h2 class="reports-guide-block__title">Review workflow</h2>';
    $html .= '<div class="reports-guide-table-wrap"><table class="reports-guide-table">';
    $html .= '<thead><tr><th>Step</th><th>Who</th><th>Action</th></tr></thead><tbody>';
    $html .= '<tr><td>1</td><td>System / head guard</td><td>Flag missing, wrong, or late punch on daily sheet</td></tr>';
    $html .= '<tr><td>2</td><td>Admin</td><td>Validate against roster and timekeeping export</td></tr>';
    $html .= '<tr><td>3</td><td>Admin</td><td>Request correction, DGD, or guard statement — set On hold if waiting</td></tr>';
    $html .= '<tr><td>4</td><td>Guard / head guard</td><td>Submit proof or corrected punches</td></tr>';
    $html .= '<tr><td>5</td><td>Admin</td><td>Resolve, issue NTE, or Dismiss if scheduling error</td></tr>';
    $html .= '</tbody></table></div></div>';

    $html .= '<div class="reports-guide-block" id="dad-guide-nte">';
    $html .= '<h2 class="reports-guide-block__title">NTE &amp; missing values</h2>';
    $html .= '<div class="reports-guide-table-wrap"><table class="reports-guide-table">';
    $html .= '<thead><tr><th>Situation</th><th>Action</th><th>Registry status</th></tr></thead><tbody>';
    $html .= '<tr><td>Missing time-in or time-out</td><td>Head guard verifies shift; backfill or paper DGD</td><td>Pending → Resolved</td></tr>';
    $html .= '<tr><td>Wrong time-in/out</td><td>Compare roster vs biometric; correct or NTE</td><td>Pending or NTE</td></tr>';
    $html .= '<tr><td>Unresolved after 24h</td><td>Issue Notice to Explain</td><td>NTE issued</td></tr>';
    $html .= '<tr><td>Duplicate or roster error</td><td>No discipline — fix timekeeping</td><td>Dismissed</td></tr>';
    $html .= '</tbody></table></div></div>';

    $html .= '</div>';

    return $html;
}
