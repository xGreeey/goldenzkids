<?php
declare(strict_types=1);

require_once __DIR__ . '/admin_ui_icons.php';

require_once __DIR__ . '/admin_incident_status.php';
require_once __DIR__ . '/admin_incident_guidelines.php';
require_once __DIR__ . '/admin_incident_pipeline.php';
require_once __DIR__ . '/guard_incident.php';

const ADMIN_INCIDENT_SESSION_KEY = 'admin_incident_reports_store';

/** Incident at the guard's assigned duty post or within post jurisdiction. */
const ADMIN_INCIDENT_CATEGORY_PER_POST = 'per_post';
/** Incident outside assigned post — client site, public area, or off-post assignment. */
const ADMIN_INCIDENT_CATEGORY_OUTSIDE_POST = 'outside_post';

/**
 * @return array<string, array{label: string, description: string}>
 */
function admin_incident_category_definitions(): array
{
    return [
        ADMIN_INCIDENT_CATEGORY_PER_POST => [
            'label' => 'On post',
            'description' => 'Guard on assigned duty post — patrol, access control, and post SOP within jurisdiction.',
        ],
        ADMIN_INCIDENT_CATEGORY_OUTSIDE_POST => [
            'label' => 'Off post',
            'description' => 'Guard at client site, perimeter, or off-post assignment — not the guard’s regular duty post.',
        ],
    ];
}

/** @return array<string, string> */
function admin_incident_category_options(): array
{
    $options = [];
    foreach (admin_incident_category_definitions() as $slug => $def) {
        $options[$slug] = $def['label'];
    }

    return $options;
}

function admin_incident_category_normalize(string $category): string
{
    $category = strtolower(trim($category));
    if (in_array($category, ['external', 'outside_post', 'outside'], true)) {
        return ADMIN_INCIDENT_CATEGORY_OUTSIDE_POST;
    }

    return ADMIN_INCIDENT_CATEGORY_PER_POST;
}

function admin_incident_category_label(string $category): string
{
    $slug = admin_incident_category_normalize($category);
    $defs = admin_incident_category_definitions();

    return $defs[$slug]['label'] ?? $defs[ADMIN_INCIDENT_CATEGORY_PER_POST]['label'];
}

function admin_incident_category_description(string $category): string
{
    $slug = admin_incident_category_normalize($category);
    $defs = admin_incident_category_definitions();

    return $defs[$slug]['description'] ?? $defs[ADMIN_INCIDENT_CATEGORY_PER_POST]['description'];
}

function admin_incident_submitter_role_label(): string
{
    return 'Head guard';
}

function admin_incident_table_exists(mysqli $conn, string $table): bool
{
    $table = preg_replace('/[^a-z0-9_]/i', '', $table) ?? '';
    if ($table === '') {
        return false;
    }

    $check = $conn->query("SHOW TABLES LIKE '{$table}'");

    return $check !== false && $check->num_rows > 0;
}

/**
 * @param array<string, mixed> $row
 */
function admin_incident_head_guard_display_name(array $row): string
{
    $display = trim((string) ($row['display_name'] ?? ''));
    if ($display !== '') {
        return $display;
    }

    $last = trim((string) ($row['last_name'] ?? ''));
    $first = trim((string) ($row['first_name'] ?? ''));
    if ($last !== '' || $first !== '') {
        return trim($last . ', ' . $first, ', ');
    }

    $companyId = trim((string) ($row['company_id'] ?? ''));

    return $companyId !== '' ? $companyId : 'Head guard';
}

/**
 * Head guards (role 0 portal accounts) — source for incident submitters.
 *
 * @return list<array{company_id:string, display_name:string, first_name?:string, last_name?:string, post_name?:string|null}>
 */
function admin_incident_head_guards_fetch(mysqli $conn): array
{
    $list = [];

    if (admin_incident_table_exists($conn, 'callout_head_guards')) {
        $result = $conn->query(
            'SELECT company_id, first_name, last_name, display_name
             FROM callout_head_guards
             WHERE is_active = 1
             ORDER BY display_name ASC'
        );
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $companyId = trim((string) ($row['company_id'] ?? ''));
                if ($companyId === '') {
                    $companyId = 'HG-' . substr(md5((string) $row['display_name']), 0, 8);
                }
                $list[] = [
                    'company_id' => $companyId,
                    'first_name' => (string) ($row['first_name'] ?? ''),
                    'last_name' => (string) ($row['last_name'] ?? ''),
                    'display_name' => (string) ($row['display_name'] ?? ''),
                    'post_name' => null,
                ];
            }
        }
    }

    if ($list === []) {
        $roleCol = auth_users_role_column($conn);
        $sql = "SELECT u.Company_ID AS company_id, g.First_Name AS first_name, g.Last_Name AS last_name,
                       g.Post_Assigned AS post_name
                FROM users u
                LEFT JOIN guards g ON g.Company_ID = u.Company_ID
                WHERE u.is_active = 1 AND u.{$roleCol} = 0
                ORDER BY g.Last_Name ASC, g.First_Name ASC, u.Company_ID ASC";

        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $list[] = [
                    'company_id' => (string) $row['company_id'],
                    'first_name' => (string) ($row['first_name'] ?? ''),
                    'last_name' => (string) ($row['last_name'] ?? ''),
                    'display_name' => '',
                    'post_name' => isset($row['post_name']) && $row['post_name'] !== ''
                        ? (string) $row['post_name']
                        : null,
                ];
            }
        }
    }

    return $list !== [] ? $list : admin_incident_head_guards_fallback();
}

/** @return list<array{company_id:string, display_name:string, first_name:string, last_name:string, post_name:?string}> */
function admin_incident_head_guards_fallback(): array
{
    return [
        ['company_id' => 'ABC-2024-0008', 'first_name' => 'Ramon', 'last_name' => 'Dela Cruz', 'display_name' => 'Ramon Dela Cruz', 'post_name' => null],
        ['company_id' => 'ABC-2024-0012', 'first_name' => 'Maria', 'last_name' => 'Santos', 'display_name' => 'Maria Santos', 'post_name' => null],
        ['company_id' => 'ABC-2024-0015', 'first_name' => 'Jose', 'last_name' => 'Reyes', 'display_name' => 'Jose Reyes', 'post_name' => null],
        ['company_id' => 'ABC-2024-0003', 'first_name' => 'Ana', 'last_name' => 'Villanueva', 'display_name' => 'Ana Villanueva', 'post_name' => null],
        ['company_id' => 'ABC-2024-0021', 'first_name' => 'Carlo', 'last_name' => 'Mendoza', 'display_name' => 'Carlo Mendoza', 'post_name' => null],
    ];
}

/**
 * @param array<string, mixed> $template
 * @param array<string, mixed> $headGuard
 */
function admin_incident_apply_head_guard_submitter(array $template, array $headGuard): array
{
    $name = admin_incident_head_guard_display_name($headGuard);
    $companyId = (string) ($headGuard['company_id'] ?? '');

    $template['head_guard_id'] = $companyId;
    $template['head_guard_name'] = $name;
    $template['submitter_id'] = $companyId;
    $template['submitter_name'] = $name;
    $template['submitter_role'] = 'head_guard';
    $template['submitter_role_label'] = admin_incident_submitter_role_label();

    return $template;
}

/**
 * @return list<array<string, mixed>>
 */
function admin_incident_seed_templates(): array
{
    return [
        [
            'id' => 'inc-001',
            'ref' => 'INC-2026-0142',
            'category' => 'per_post',
            'incident_type' => 'Policy breach — unauthorized access',
            'site' => 'Golden Z-5 Main Office',
            'submitted_at' => '2026-05-20',
            'submitted_display' => '20 May 2026, 09:14',
            'status' => 'ongoing',
            'summary' => 'Guard under head guard: (field) · Classified: Policy breach — unauthorized access · On post · High',
            'incident_description' => 'At 09:05, an unknown male (approx. 30s, dark jacket) attempted to enter the admin wing via the service elevator lobby without a valid access pass. He was challenged at the desk; claimed to be IT but had no visitor badge or roster match.',
            'action_taken' => 'Denied entry, escorted to main lobby, and CCTV stills saved. Duty supervisor and operations notified immediately via field portal.',
            'person_involved' => 'Unknown male (non-roster)',
            'attachments' => [
                [
                    'type' => 'scan',
                    'label' => 'Incident form',
                    'url' => 'assets/img/report-template-incident.png',
                ],
                [
                    'type' => 'evidence',
                    'label' => 'CCTV still',
                    'url' => 'assets/img/report-template-incident.png',
                ],
            ],
            'has_attachments' => true,
            'history' => [
                [
                    'at' => '20 May 2026, 09:14',
                    'source' => 'head_guard',
                    'kind' => 'field_submission',
                    'event' => 'Report filed',
                    'description' => 'At 09:05, an unknown male (approx. 30s, dark jacket) attempted to enter the admin wing via the service elevator lobby without a valid access pass. He was challenged at the desk; claimed to be IT but had no visitor badge or roster match.',
                    'immediate_action' => 'Denied entry, escorted to main lobby, and CCTV stills saved. Duty supervisor and operations notified immediately via field portal.',
                    'guard_name' => 'Unknown male (non-roster)',
                ],
                [
                    'at' => '20 May 2026, 09:14',
                    'source' => 'system',
                    'kind' => 'classification',
                    'event' => 'Classified',
                    'note' => 'Type: Policy breach — unauthorized access · On post · Severity High',
                ],
                [
                    'at' => '20 May 2026, 09:14',
                    'source' => 'system',
                    'kind' => 'routing',
                    'event' => 'Assigned to operations',
                    'note' => 'Stage 2 — Admin review. Within 1 hour — preserve evidence same shift',
                ],
                [
                    'at' => '20 May 2026, 14:30',
                    'source' => 'admin',
                    'kind' => 'decision',
                    'event' => 'Report accepted',
                    'note' => 'Accepted for operations review. Interview with duty supervisor scheduled; awaiting signed statement.',
                ],
            ],
        ],
        [
            'id' => 'inc-002',
            'ref' => 'INC-2026-0138',
            'category' => 'outside_post',
            'incident_type' => 'Client site — trespassing',
            'site' => 'Ayala Mall Cebu — North Wing',
            'submitted_at' => '2026-05-19',
            'submitted_display' => '19 May 2026, 22:47',
            'status' => 'on_hold',
            'summary' => 'Two unidentified persons loitering near loading bay; mall security coordinated. Pending client statement.',
            'history' => [
                ['at' => '19 May 2026, 22:47', 'event' => 'Submitted by head guard', 'note' => 'Photos and witness names included.'],
                ['at' => '20 May 2026, 08:00', 'event' => 'Status: On hold', 'note' => 'Waiting for mall management incident form.'],
            ],
        ],
        [
            'id' => 'inc-003',
            'ref' => 'INC-2026-0131',
            'category' => 'outside_post',
            'incident_type' => 'Theft / loss prevention',
            'site' => 'SM Seaside — Annex Retail',
            'submitted_at' => '2026-05-17',
            'submitted_display' => '17 May 2026, 16:20',
            'status' => 'accomplished',
            'summary' => 'Shoplifting attempt intercepted; suspect turned over to mall police. Case closed per client instruction.',
            'history' => [
                ['at' => '17 May 2026, 16:20', 'event' => 'Submitted by head guard', 'note' => ''],
                ['at' => '18 May 2026, 11:00', 'event' => 'Status: Open', 'note' => 'Evidence packet compiled.'],
                ['at' => '19 May 2026, 09:45', 'event' => 'Status: Case closed', 'note' => 'Client signed closure memo; archived.'],
            ],
        ],
        [
            'id' => 'inc-004',
            'ref' => 'INC-2026-0124',
            'category' => 'per_post',
            'incident_type' => 'Equipment failure — radio network',
            'site' => 'HQ Communications Room',
            'submitted_at' => '2026-05-15',
            'submitted_display' => '15 May 2026, 07:55',
            'status' => 'accomplished',
            'summary' => 'Wide-area repeater offline during shift change; IT restored backup channel within 2 hours.',
            'history' => [
                ['at' => '15 May 2026, 07:55', 'event' => 'Submitted by head guard', 'note' => 'Automatic escalation to IT.'],
                ['at' => '15 May 2026, 10:12', 'event' => 'Status: Case closed', 'note' => 'Service restored; post-incident checklist completed.'],
            ],
        ],
        [
            'id' => 'inc-005',
            'ref' => 'INC-2026-0119',
            'category' => 'outside_post',
            'incident_type' => 'Medical emergency',
            'site' => 'Landers Superstore Banilad',
            'submitted_at' => '2026-05-12',
            'submitted_display' => '12 May 2026, 13:08',
            'status' => 'denied',
            'summary' => 'Duplicate filing of an event already logged by client EMS; closed as redundant.',
            'history' => [
                ['at' => '12 May 2026, 13:08', 'event' => 'Submitted by head guard', 'note' => ''],
                ['at' => '12 May 2026, 15:40', 'event' => 'Status: Closed — not accepted', 'note' => 'Ops: duplicate of INC-2026-0117. No further action.'],
            ],
        ],
        [
            'id' => 'inc-006',
            'ref' => 'INC-2026-0112',
            'category' => 'per_post',
            'incident_type' => 'Workplace injury — minor',
            'site' => 'Training Facility — Lapu-Lapu',
            'submitted_at' => '2026-05-10',
            'submitted_display' => '10 May 2026, 11:33',
            'status' => 'ongoing',
            'summary' => 'Trainee sprained ankle during drill; first aid applied. HR documentation in progress.',
            'history' => [
                ['at' => '10 May 2026, 11:33', 'event' => 'Submitted by head guard', 'note' => 'Medical officer notified on-site.'],
                ['at' => '11 May 2026, 08:20', 'event' => 'Status: Open', 'note' => 'Awaiting clinic report scan.'],
            ],
        ],
        [
            'id' => 'inc-007',
            'ref' => 'INC-2026-0105',
            'category' => 'outside_post',
            'incident_type' => 'Vandalism',
            'site' => 'University of San Carlos — Gate 2',
            'submitted_at' => '2026-05-08',
            'submitted_display' => '8 May 2026, 03:12',
            'status' => 'on_hold',
            'summary' => 'Graffiti on perimeter fence; campus security requested agency photos before repair.',
            'history' => [
                ['at' => '8 May 2026, 03:12', 'event' => 'Submitted by head guard', 'note' => 'Night shift patrol log reference NG-441.'],
                ['at' => '9 May 2026, 16:00', 'event' => 'Status: On hold', 'note' => 'Pending campus police blotter number.'],
            ],
        ],
        [
            'id' => 'inc-008',
            'ref' => 'INC-2026-0098',
            'category' => 'per_post',
            'incident_type' => 'Attendance / shift dispute',
            'site' => 'Operations Dispatch',
            'submitted_at' => '2026-05-05',
            'submitted_display' => '5 May 2026, 18:40',
            'status' => 'denied',
            'summary' => 'Withdrawn by submitter — scheduling error clarified without disciplinary action.',
            'history' => [
                ['at' => '5 May 2026, 18:40', 'event' => 'Submitted by head guard', 'note' => ''],
                ['at' => '6 May 2026, 09:00', 'event' => 'Status: Cancelled', 'note' => 'Head guard requested withdrawal; approved by ops.'],
            ],
        ],
        [
            'id' => 'inc-009',
            'ref' => 'INC-2026-0091',
            'category' => 'outside_post',
            'incident_type' => 'Fire alarm activation',
            'site' => 'Quest Hotel Mactan — Lobby',
            'submitted_at' => '2026-05-03',
            'submitted_display' => '3 May 2026, 14:02',
            'status' => 'accomplished',
            'summary' => 'False alarm triggered by kitchen smoke; building evacuated 12 minutes; all-clear issued.',
            'history' => [
                ['at' => '3 May 2026, 14:02', 'event' => 'Submitted by head guard', 'note' => 'Coordinated with hotel engineering.'],
                ['at' => '3 May 2026, 15:30', 'event' => 'Status: Case closed', 'note' => 'Incident report filed with hotel GM.'],
            ],
        ],
        [
            'id' => 'inc-010',
            'ref' => 'INC-2026-0084',
            'category' => 'outside_post',
            'incident_type' => 'Traffic / parking incident',
            'site' => 'Cebu IT Park — Tower 1',
            'submitted_at' => '2026-04-28',
            'submitted_display' => '28 Apr 2026, 08:17',
            'status' => 'ongoing',
            'summary' => 'Vehicle collision in basement parking; guard mediated exchange of details. Insurance follow-up open.',
            'history' => [
                ['at' => '28 Apr 2026, 08:17', 'event' => 'Submitted by head guard', 'note' => 'Dashcam footage uploaded.'],
                ['at' => '29 Apr 2026, 10:00', 'event' => 'Status: Open', 'note' => 'Legal reviewing tenant statements.'],
            ],
        ],
    ];
}

/**
 * @return list<array<string, mixed>>
 */
function admin_incident_seed_reports(?mysqli $conn = null): array
{
    $templates = admin_incident_seed_templates();
    $headGuards = $conn instanceof mysqli
        ? admin_incident_head_guards_fetch($conn)
        : admin_incident_head_guards_fallback();

    $reports = [];
    $guardCount = count($headGuards);

    foreach ($templates as $i => $template) {
        $guard = $headGuards[$guardCount > 0 ? $i % $guardCount : 0];
        $reports[] = admin_incident_apply_head_guard_submitter($template, $guard);
    }

    return $reports;
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function admin_incident_normalize(array $row): array
{
    $status = (string) ($row['status'] ?? ADMIN_INCIDENT_STATUS_ONGOING);
    if (!admin_incident_status_is_valid($status)) {
        $status = ADMIN_INCIDENT_STATUS_ONGOING;
    }
    $category = admin_incident_category_normalize((string) ($row['category'] ?? ADMIN_INCIDENT_CATEGORY_PER_POST));

    $row['status'] = $status;
    $row['status_label'] = admin_incident_status_label($status);
    $row['status_description'] = admin_incident_status_description($status);
    $row['category'] = $category;
    $row['category_label'] = admin_incident_category_label($category);
    $row['history'] = is_array($row['history'] ?? null) ? $row['history'] : [];

    $headGuardId = trim((string) ($row['head_guard_id'] ?? $row['submitter_id'] ?? ''));
    $headGuardName = trim((string) ($row['head_guard_name'] ?? $row['submitter_name'] ?? ''));
    $row['head_guard_id'] = $headGuardId;
    $row['head_guard_name'] = $headGuardName !== '' ? $headGuardName : 'Head guard';
    $row['submitter_id'] = $headGuardId;
    $row['submitter_name'] = $row['head_guard_name'];
    $row['submitter_role'] = 'head_guard';
    $row['submitter_role_label'] = admin_incident_submitter_role_label();
    $row['person_involved'] = admin_incident_person_from_report($row);

    $severity = trim((string) ($row['severity'] ?? ''));
    if (!admin_incident_severity_is_valid($severity)) {
        $severity = admin_incident_severity_for_type((string) ($row['incident_type'] ?? ''));
    }
    $row['severity'] = $severity;

    if (!isset($row['attachments']) || !is_array($row['attachments'])) {
        $row['attachments'] = [];
    }
    $row['has_attachments'] = !empty($row['has_attachments'])
        || (is_array($row['attachments']) && $row['attachments'] !== []);
    $row['has_operations_decision'] = admin_incident_report_has_operations_decision($row);

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

    return $row;
}

/** @return list<array<string, mixed>> */
function admin_incident_store_all(): array
{
    if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof PDO && guard_incident_table_exists($GLOBALS['conn'])) {
        $out = [];
        foreach (guard_incident_fetch_admin_records($GLOBALS['conn']) as $row) {
            $out[] = admin_incident_normalize($row);
        }
        if ($out !== []) {
            usort($out, static fn (array $a, array $b): int => strcmp((string) ($b['submitted_at'] ?? ''), (string) ($a['submitted_at'] ?? '')));

            return $out;
        }
    }

    if (!isset($_SESSION[ADMIN_INCIDENT_SESSION_KEY]) || !is_array($_SESSION[ADMIN_INCIDENT_SESSION_KEY])) {
        $seed = admin_incident_seed_reports(null);
        $normalized = [];
        foreach ($seed as $row) {
            $normalized[] = admin_incident_normalize($row);
        }
        $_SESSION[ADMIN_INCIDENT_SESSION_KEY] = $normalized;
    }

    $out = [];
    foreach ($_SESSION[ADMIN_INCIDENT_SESSION_KEY] as $row) {
        if (is_array($row)) {
            $out[] = admin_incident_normalize($row);
        }
    }

    usort($out, static fn (array $a, array $b): int => strcmp((string) ($b['submitted_at'] ?? ''), (string) ($a['submitted_at'] ?? '')));

    return $out;
}

/** @param list<array<string, mixed>> $reports */
function admin_incident_store_save(array $reports): void
{
    $_SESSION[ADMIN_INCIDENT_SESSION_KEY] = array_map(
        static fn (array $r): array => admin_incident_normalize($r),
        $reports
    );
}

function admin_incident_store_reset(): void
{
    unset($_SESSION[ADMIN_INCIDENT_SESSION_KEY]);
}

function admin_incident_find(string $id): ?array
{
    if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof PDO && preg_match('/^inc-(\d+)$/', $id)) {
        $row = guard_incident_find_by_id($GLOBALS['conn'], $id);
        if ($row !== null) {
            return admin_incident_normalize($row);
        }
    }

    foreach (admin_incident_store_all() as $row) {
        if ((string) ($row['id'] ?? '') === $id) {
            return $row;
        }
    }

    return null;
}

function admin_incident_history_now(): string
{
    return date('j M Y, H:i');
}

function admin_incident_format_display(string $iso): string
{
    if ($iso === '') {
        return '—';
    }

    $ts = strtotime($iso);
    if ($ts === false) {
        return $iso;
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $iso)) {
        return date('j M Y', $ts);
    }

    return date('j M Y, H:i', $ts);
}

/**
 * @return array{date: string, time: string}
 */
function admin_incident_table_date_parts(string $iso, string $fallbackDisplay = ''): array
{
    $iso = trim($iso);
    $fallbackDisplay = trim($fallbackDisplay);

    $ts = false;
    if ($iso !== '') {
        $ts = strtotime($iso);
    }
    if ($ts === false && $fallbackDisplay !== '' && $fallbackDisplay !== '—') {
        $ts = strtotime($fallbackDisplay);
    }

    if ($ts === false) {
        if ($fallbackDisplay !== '' && $fallbackDisplay !== '—') {
            if (preg_match('/^(.+?),\s*(\d{1,2}:\d{2}(?::\d{2})?)\s*$/u', $fallbackDisplay, $m)) {
                return ['date' => trim($m[1]), 'time' => trim($m[2])];
            }

            return ['date' => $fallbackDisplay, 'time' => ''];
        }

        return ['date' => '—', 'time' => ''];
    }

    $date = date('j M Y', $ts);
    $time = '';

    if (preg_match('/T| \d{1,2}:\d{2}/', $iso)) {
        $time = date('H:i', $ts);
    } elseif (preg_match('/,\s*(\d{1,2}:\d{2}(?::\d{2})?)/', $fallbackDisplay, $m)) {
        $time = $m[1];
    }

    return ['date' => $date, 'time' => $time];
}

function admin_incident_table_date_cell_html(string $iso, string $fallbackDisplay = ''): string
{
    $parts = admin_incident_table_date_parts($iso, $fallbackDisplay);
    $date = htmlspecialchars($parts['date'], ENT_QUOTES, 'UTF-8');
    $timeHtml = $parts['time'] !== ''
        ? '<span class="reports-date-cell__time">' . htmlspecialchars($parts['time'], ENT_QUOTES, 'UTF-8') . '</span>'
        : '<span class="reports-date-cell__time reports-date-cell__time--empty">—</span>';

    return '<div class="reports-date-cell">'
        . '<span class="reports-date-cell__date">' . $date . '</span>'
        . $timeHtml
        . '</div>';
}

/**
 * @return array{at:string, display:string}
 */
function admin_incident_derive_updated_from_history(array $row): array
{
    $history = is_array($row['history'] ?? null) ? $row['history'] : [];
    if ($history === []) {
        return [
            'at' => (string) ($row['submitted_at'] ?? ''),
            'display' => (string) ($row['submitted_display'] ?? '—'),
        ];
    }

    $last = end($history);
    $display = (string) ($last['at'] ?? '');
    $ts = $display !== '' ? strtotime($display) : false;

    return [
        'at' => $ts !== false ? date('Y-m-d H:i:s', $ts) : (string) ($row['submitted_at'] ?? ''),
        'display' => $display !== '' ? $display : (string) ($row['submitted_display'] ?? '—'),
    ];
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function admin_incident_sync_dates(array $row): array
{
    $submittedAt = (string) ($row['submitted_at'] ?? '');
    if (trim((string) ($row['submitted_display'] ?? '')) === '' && $submittedAt !== '') {
        $row['submitted_display'] = admin_incident_format_display($submittedAt);
    }

    $updatedAt = trim((string) ($row['updated_at'] ?? ''));
    $updatedDisplay = trim((string) ($row['updated_display'] ?? ''));

    if ($updatedAt === '' && $updatedDisplay === '') {
        $derived = admin_incident_derive_updated_from_history($row);
        $row['updated_at'] = $derived['at'];
        $row['updated_display'] = $derived['display'];
    } elseif ($updatedAt !== '' && $updatedDisplay === '') {
        $row['updated_display'] = admin_incident_format_display($updatedAt);
    } elseif ($updatedDisplay !== '' && $updatedAt === '') {
        $ts = strtotime($updatedDisplay);
        $row['updated_at'] = $ts !== false ? date('Y-m-d H:i:s', $ts) : $submittedAt;
    }

    if (trim((string) ($row['updated_at'] ?? '')) === '') {
        $row['updated_at'] = $submittedAt;
        $row['updated_display'] = (string) ($row['submitted_display'] ?? '—');
    }

    return $row;
}

/**
 * @param array<string, mixed> $report
 * @return array<string, mixed>
 */
function admin_incident_touch_updated(array $report): array
{
    $report['updated_at'] = date('Y-m-d H:i:s');
    $report['updated_display'] = admin_incident_history_now();

    return $report;
}

/**
 * @param array<string, mixed> $report
 * @param array<string, string> $changes
 */
/**
 * @param array<string, mixed> $meta Optional: source (head_guard|admin|system), kind (field_submission|status|note|routing|response)
 */
function admin_incident_append_history(array $report, string $event, string $note, string $actorId, array $meta = []): array
{
    $history = is_array($report['history'] ?? null) ? $report['history'] : [];
    $entry = [
        'at' => admin_incident_history_now(),
        'event' => $event,
        'note' => $note,
        'actor' => $actorId,
        'source' => (string) ($meta['source'] ?? 'admin'),
        'kind' => (string) ($meta['kind'] ?? 'response'),
    ];
    if (isset($meta['description']) && trim((string) $meta['description']) !== '') {
        $entry['description'] = trim((string) $meta['description']);
    }
    if (isset($meta['immediate_action']) && trim((string) $meta['immediate_action']) !== '') {
        $entry['immediate_action'] = trim((string) $meta['immediate_action']);
    }
    $history[] = $entry;
    $report['history'] = $history;

    return $report;
}

/** @param array<string, mixed> $entry */
function admin_incident_history_entry_is_editable(array $entry): bool
{
    return admin_incident_history_entry_source($entry) === 'admin';
}

/** @param array<string, mixed> $entry */
function admin_incident_history_entry_is_decision(array $entry): bool
{
    $kind = strtolower(trim((string) ($entry['kind'] ?? '')));
    if ($kind === 'decision') {
        return true;
    }

    $event = strtolower(trim((string) ($entry['event'] ?? '')));

    return in_array($event, ['report accepted', 'report not accepted', 'report on hold'], true);
}

/**
 * @param array<string, mixed> $entry
 * @param array<string, mixed> $report
 */
function admin_incident_history_head_guard_notes_html(array $entry, array $report): string
{
    $content = admin_incident_field_submission_content($entry, $report);
    $guardName = trim((string) ($entry['guard_name'] ?? $report['person_involved'] ?? ''));
    $html = '<div class="reports-op-flow__submission-readonly">';
    if ($guardName !== '') {
        $html .= '<p class="reports-op-flow__submission-line"><strong>Guard (under head guard):</strong> '
            . htmlspecialchars($guardName, ENT_QUOTES, 'UTF-8')
            . '</p>';
    }
    if ($content['description'] !== '') {
        $html .= '<p class="reports-op-flow__submission-line"><strong>Description:</strong> '
            . htmlspecialchars($content['description'], ENT_QUOTES, 'UTF-8')
            . '</p>';
    }
    if ($content['immediate_action'] !== '') {
        $html .= '<p class="reports-op-flow__submission-line"><strong>Immediate action:</strong> '
            . htmlspecialchars($content['immediate_action'], ENT_QUOTES, 'UTF-8')
            . '</p>';
    }
    if ($html === '<div class="reports-op-flow__submission-readonly">') {
        $html .= '<p class="reports-op-flow__submission-line">—</p>';
    }
    $html .= '<p class="reports-form-hint reports-op-flow__submission-hint">To correct OCR text, use the <strong>Report text</strong> section above.</p>';

    return $html . '</div>';
}

function admin_incident_status_slug_from_history_event(string $event): ?string
{
    if (!preg_match('/^(Registry|Status):\s*(.+)$/iu', trim($event), $m)) {
        return null;
    }

    $label = trim($m[2]);
    foreach (admin_incident_status_definitions() as $slug => $def) {
        if (strcasecmp($def['label'], $label) === 0) {
            return $slug;
        }
    }

    return null;
}

/**
 * @param array<string, mixed> $entry
 * @return array<string, mixed>
 */
function admin_incident_apply_history_entry_edit(array $entry, string $status, string $opsNote, string $actorId): array
{
    $entry['note'] = $opsNote;
    $entry['edited_at'] = admin_incident_history_now();
    $entry['edited_by'] = $actorId;

    $kind = strtolower(trim((string) ($entry['kind'] ?? '')));
    $event = trim((string) ($entry['event'] ?? ''));
    $eventLower = strtolower($event);
    $isRegistryEvent = $kind === 'status'
        || str_starts_with($eventLower, 'registry:')
        || str_starts_with($eventLower, 'status:');

    if ($isRegistryEvent) {
        $entry['event'] = 'Registry: ' . admin_incident_status_label($status);
        $entry['kind'] = 'status';
    }

    return $entry;
}

/**
 * @param array<string, mixed> $report
 * @param array<string, mixed> $input
 */
function admin_incident_update_history_entry(array $report, array $input, string $actorId): ?array
{
    $editIdx = (int) ($input['edit_history_index'] ?? -1);
    if ($editIdx < 0) {
        return null;
    }

    $history = is_array($report['history'] ?? null) ? $report['history'] : [];
    if (!isset($history[$editIdx]) || !is_array($history[$editIdx])) {
        return null;
    }

    $entry = $history[$editIdx];
    if (!admin_incident_history_entry_is_editable($entry)) {
        return null;
    }

    $status = (string) ($input['status'] ?? $report['status'] ?? ADMIN_INCIDENT_STATUS_ONGOING);
    if (!admin_incident_status_is_valid($status)) {
        $status = (string) ($report['status'] ?? ADMIN_INCIDENT_STATUS_ONGOING);
    }

    $opsNote = trim((string) ($input['ops_note'] ?? ''));
    $history[$editIdx] = admin_incident_apply_history_entry_edit($entry, $status, $opsNote, $actorId);
    $report['history'] = $history;
    $report['status'] = $status;

    return admin_incident_touch_updated($report);
}

/** @param array<string, mixed> $entry */
function admin_incident_history_entry_is_registry(array $entry): bool
{
    $kind = strtolower(trim((string) ($entry['kind'] ?? '')));
    $event = strtolower(trim((string) ($entry['event'] ?? '')));

    return $kind === 'status'
        || str_starts_with($event, 'registry:')
        || str_starts_with($event, 'status:');
}

function admin_incident_history_status_select_html(string $name, string $selectedSlug, string $id = ''): string
{
    $idAttr = $id !== '' ? ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' : '';
    $html = '<select name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" class="reports-op-flow__input"' . $idAttr . '>';
    foreach (admin_incident_status_definitions() as $slug => $def) {
        $selected = $slug === $selectedSlug ? ' selected' : '';
        $html .= '<option value="' . htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') . '"' . $selected . '>'
            . htmlspecialchars($def['label'], ENT_QUOTES, 'UTF-8')
            . '</option>';
    }

    return $html . '</select>';
}

/**
 * @param array<string, mixed> $report
 * @param array<int|string, array<string, mixed>> $rowsInput
 */
function admin_incident_apply_history_rows_edit(array $report, array $rowsInput, string $actorId): array
{
    $history = is_array($report['history'] ?? null) ? $report['history'] : [];

    foreach ($rowsInput as $idxStr => $rowInput) {
        if (!is_array($rowInput)) {
            continue;
        }
        $idx = (int) $idxStr;
        if (!isset($history[$idx]) || !is_array($history[$idx])) {
            continue;
        }

        $entry = $history[$idx];

        if (admin_incident_history_entry_source($entry) === 'head_guard') {
            continue;
        }

        if (admin_incident_history_entry_is_editable($entry)) {
            $note = trim((string) ($rowInput['note'] ?? ''));
            $actionType = trim((string) ($rowInput['action_type'] ?? ''));
            $registryStatus = trim((string) ($rowInput['registry_status'] ?? ''));

            if ($actionType !== '' && admin_incident_history_entry_is_decision($entry)) {
                $decisionMeta = admin_incident_operations_decision_meta($actionType);
                if ($decisionMeta !== null) {
                    $entry['event'] = $decisionMeta['event'];
                    $entry['kind'] = $decisionMeta['kind'];
                    $entry['note'] = $note;
                    $entry['edited_at'] = admin_incident_history_now();
                    $entry['edited_by'] = $actorId;
                    $report['status'] = $decisionMeta['status'];
                }
            } elseif ($registryStatus !== '' && admin_incident_status_is_valid($registryStatus)) {
                $entry = admin_incident_apply_history_entry_edit($entry, $registryStatus, $note, $actorId);
            } else {
                $entry['note'] = $note;
                $event = trim((string) ($rowInput['event'] ?? ''));
                if ($event !== '') {
                    $entry['event'] = $event;
                }
                $entry['edited_at'] = admin_incident_history_now();
                $entry['edited_by'] = $actorId;
            }
        }

        $history[$idx] = $entry;
    }

    $report['history'] = $history;

    return $report;
}

/**
 * @param array<string, mixed> $input
 */
function admin_incident_update(string $id, array $input, string $actorId): ?array
{
    if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof PDO && preg_match('/^inc-(\d+)$/', $id, $m)) {
        $updated = guard_incident_admin_update($GLOBALS['conn'], (int) $m[1], $input, $actorId);
        if ($updated !== null) {
            return admin_incident_normalize($updated);
        }

        return null;
    }

    $reports = admin_incident_store_all();
    $found = null;
    $idx = null;

    foreach ($reports as $i => $row) {
        if ((string) ($row['id'] ?? '') === $id) {
            $found = $row;
            $idx = $i;
            break;
        }
    }

    if ($found === null || $idx === null) {
        return null;
    }

    $oldStatus = (string) $found['status'];
    $oldCategory = (string) $found['category'];
    $oldType = (string) $found['incident_type'];
    $oldSite = (string) $found['site'];
    $oldSeverity = (string) ($found['severity'] ?? 'Medium');
    $oldSummary = (string) $found['summary'];

    $progressionOnly = !empty($input['progression_only']);

    $category = $oldCategory;
    $incidentType = $oldType;
    $site = $oldSite;
    $summary = $oldSummary;
    $severity = $oldSeverity;

    if (!$progressionOnly) {
        $category = admin_incident_category_normalize((string) ($input['category'] ?? $oldCategory));
        $incidentType = trim((string) ($input['incident_type'] ?? $oldType));
        $site = trim((string) ($input['site'] ?? $oldSite));
        $summary = trim((string) ($input['summary'] ?? $oldSummary));
        $severity = trim((string) ($input['severity'] ?? $oldSeverity));
        if (!admin_incident_severity_is_valid($severity)) {
            $severity = admin_incident_severity_for_type($incidentType);
        }
        $found['category'] = $category;
        $found['incident_type'] = $incidentType;
        $found['site'] = $site;
        $found['severity'] = $severity;
        $found['summary'] = $summary;
    }

    $status = (string) ($input['status'] ?? $oldStatus);
    if (!admin_incident_status_is_valid($status)) {
        $status = $oldStatus;
    }

    $historyRows = is_array($input['history_row'] ?? null) ? $input['history_row'] : [];
    $newRowInput = is_array($historyRows['new'] ?? null) ? $historyRows['new'] : null;
    unset($historyRows['new']);

    if ($progressionOnly) {
        if ($historyRows !== []) {
            $found = admin_incident_apply_history_rows_edit($found, $historyRows, $actorId);
        }
        if ($newRowInput !== null) {
            $found = admin_incident_apply_history_new_row($found, $newRowInput, $actorId);
        }

        $found = admin_incident_apply_report_body_edit($found, $input, $actorId);

        $status = (string) ($input['status'] ?? $found['status'] ?? ADMIN_INCIDENT_STATUS_ONGOING);
        if (!admin_incident_status_is_valid($status)) {
            $status = (string) ($found['status'] ?? ADMIN_INCIDENT_STATUS_ONGOING);
        }
        $statusBefore = (string) $found['status'];
        if ($status !== $statusBefore) {
            $found['status'] = $status;
            $found = admin_incident_append_history(
                $found,
                'Registry: ' . admin_incident_status_label($status),
                'Registry status updated.',
                $actorId,
                ['source' => 'admin', 'kind' => 'status']
            );
        }

        $found = admin_incident_touch_updated($found);
        $found['status'] = admin_incident_reconcile_status($found);
        $found = admin_incident_normalize($found);
        $reports[$idx] = $found;
        admin_incident_store_save($reports);

        return $found;
    }

    $hasHistoryEdits = is_array($input['history_row'] ?? null) && $input['history_row'] !== [];
    if ($hasHistoryEdits) {
        $found = admin_incident_apply_history_rows_edit($found, $input['history_row'], $actorId);
        $found = admin_incident_touch_updated($found);
    }

    $found['status'] = $status;

    $editHistoryIndex = trim((string) ($input['edit_history_index'] ?? ''));
    if ($editHistoryIndex !== '' && $progressionOnly && (!is_array($historyRows) || $historyRows === [])) {
        $updated = admin_incident_update_history_entry($found, [
            'status' => $status,
            'ops_note' => (string) ($input['ops_note'] ?? ''),
            'edit_history_index' => $editHistoryIndex,
        ], $actorId);
        if ($updated === null) {
            return null;
        }
        $found = admin_incident_normalize($updated);
        $reports[$idx] = $found;
        admin_incident_store_save($reports);

        return $found;
    }

    $opsDecision = trim((string) ($input['ops_decision'] ?? ''));
    $decisionAppended = $progressionOnly && $opsDecision !== '';
    $fieldsChanged = !$progressionOnly && (
        $category !== $oldCategory
        || $incidentType !== $oldType
        || $site !== $oldSite
        || $severity !== $oldSeverity
        || $summary !== $oldSummary
    );
    $opsNote = trim((string) ($input['ops_note'] ?? ''));
    $statusChanged = $status !== $oldStatus;

    if (!$statusChanged && !$fieldsChanged && $opsNote === '' && !$hasHistoryEdits && !$decisionAppended) {
        return admin_incident_normalize($found);
    }

    $didChange = $statusChanged || $fieldsChanged || $opsNote !== '' || $hasHistoryEdits || $decisionAppended;

    if (!$decisionAppended && ($statusChanged || $fieldsChanged || $opsNote !== '')) {
        $noteParts = [];
        if ($opsNote !== '') {
            $noteParts[] = $opsNote;
        }
        if ($fieldsChanged && $opsNote === '') {
            $noteParts[] = 'Incident details revised.';
        }
        if ($statusChanged && $opsNote === '' && !$fieldsChanged) {
            $noteParts[] = 'Registry status updated.';
        }
        $note = implode(' ', $noteParts);
        if ($statusChanged) {
            $event = 'Registry: ' . admin_incident_status_label($status);
            $kind = 'status';
        } elseif ($opsNote !== '') {
            $event = 'Operations response';
            $kind = 'note';
        } else {
            $event = 'Operations update';
            $kind = 'response';
        }
        $found = admin_incident_append_history($found, $event, $note, $actorId, [
            'source' => 'admin',
            'kind' => $kind,
        ]);
    }

    $found = admin_incident_touch_updated($found);
    $found['status'] = admin_incident_reconcile_status($found);
    $found = admin_incident_normalize($found);

    $reports[$idx] = $found;
    admin_incident_store_save($reports);

    if ($didChange && isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof PDO) {
        require_once __DIR__ . '/portal_audit.php';
        portal_audit_log(
            $GLOBALS['conn'],
            'INCIDENT_UPDATED',
            'Reference: ' . (string) ($found['ref'] ?? $id)
                . ($statusChanged ? '; status → ' . $status : ''),
            (string) ($found['submitter_id'] ?? ''),
            $actorId,
            auth_user_role()
        );
    }

    return $found;
}

/**
 * Close (archive) an incident — sets registry status to Closed.
 *
 * @return array<string, mixed>|null
 */
function admin_incident_archive(string $id, string $actorId): ?array
{
    $id = trim($id);
    if ($id === '') {
        return null;
    }

    $record = admin_incident_find($id);
    if ($record === null) {
        return null;
    }

    $status = (string) ($record['status'] ?? ADMIN_INCIDENT_STATUS_ONGOING);
    if (!admin_incident_status_is_valid($status)) {
        $status = ADMIN_INCIDENT_STATUS_ONGOING;
    }
    $defs = admin_incident_status_definitions();
    if (($defs[$status]['closed'] ?? false) === true) {
        return admin_incident_normalize($record);
    }

    return admin_incident_update($id, [
        'progression_only' => true,
        'status' => ADMIN_INCIDENT_STATUS_ACCOMPLISHED,
    ], $actorId);
}

/**
 * Delete an incident report (database or demo session).
 *
 * @return array{id:string,ref:string}|null
 */
function admin_incident_delete(string $id): ?array
{
    $id = trim($id);
    if ($id === '') {
        return null;
    }

    if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof PDO && preg_match('/^inc-(\d+)$/', $id, $m)) {
        return guard_incident_admin_delete($GLOBALS['conn'], (int) $m[1]);
    }

    if (!isset($_SESSION[ADMIN_INCIDENT_SESSION_KEY]) || !is_array($_SESSION[ADMIN_INCIDENT_SESSION_KEY])) {
        return null;
    }

    $reports = admin_incident_store_all();
    $deleted = null;
    $remaining = [];

    foreach ($reports as $row) {
        if ((string) ($row['id'] ?? '') === $id) {
            $deleted = $row;
            continue;
        }
        $remaining[] = $row;
    }

    if ($deleted === null) {
        return null;
    }

    admin_incident_store_save($remaining);

    return [
        'id' => $id,
        'ref' => (string) ($deleted['ref'] ?? $id),
    ];
}

/** @param list<array<string, mixed>> $reports */
function admin_incident_status_counts(array $reports): array
{
    $counts = ['all' => count($reports)];
    foreach (admin_incident_status_slugs() as $slug) {
        $counts[$slug] = 0;
    }
    foreach ($reports as $r) {
        $s = (string) ($r['status'] ?? '');
        if (isset($counts[$s])) {
            $counts[$s]++;
        }
    }

    return $counts;
}

/**
 * @param array<string, mixed> $report
 */
/** @return list<string> */
function admin_incident_severity_levels(): array
{
    return ['High', 'Medium', 'Low'];
}

function admin_incident_severity_is_valid(string $severity): bool
{
    return in_array($severity, admin_incident_severity_levels(), true);
}

function admin_incident_severity_for_type(string $incidentType): string
{
    foreach (admin_incident_guard_guide_workflow_rows() as $ref) {
        if ((string) ($ref['incident_type'] ?? '') === $incidentType) {
            $severity = (string) ($ref['severity'] ?? 'Medium');

            return admin_incident_severity_is_valid($severity) ? $severity : 'Medium';
        }
    }

    return 'Medium';
}

/**
 * @param array<string, mixed> $report
 */
function admin_incident_severity_badge_html(array $report): string
{
    $severity = (string) ($report['severity'] ?? 'Medium');
    if (!admin_incident_severity_is_valid($severity)) {
        $severity = 'Medium';
    }
    $slug = strtolower($severity);

    return '<span class="reports-severity reports-severity--' . htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') . '">'
        . htmlspecialchars($severity, ENT_QUOTES, 'UTF-8')
        . '</span>';
}

/**
 * @param array<string, mixed> $report
 */
function admin_incident_head_guard_cell_html(array $report): string
{
    $name = (string) ($report['head_guard_name'] ?? $report['submitter_name'] ?? '');
    $username = (string) ($report['head_guard_id'] ?? $report['submitter_id'] ?? '');
    $post = trim((string) ($report['site'] ?? ''));

    $html = '<div class="reports-hg-cell">';
    $html .= '<span class="reports-hg-name">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</span>';
    $html .= '<span class="reports-hg-username mono" title="Portal username">'
        . htmlspecialchars($username, ENT_QUOTES, 'UTF-8')
        . '</span>';
    if ($post !== '') {
        $html .= '<span class="reports-hg-post" title="Post">'
            . htmlspecialchars($post, ENT_QUOTES, 'UTF-8')
            . '</span>';
    }
    $html .= '</div>';

    return $html;
}

/**
 * @param array<string, mixed> $report
 */
function admin_incident_category_badge_html(array $report): string
{
    $slug = admin_incident_category_normalize((string) ($report['category'] ?? ADMIN_INCIDENT_CATEGORY_PER_POST));
    $label = (string) ($report['category_label'] ?? admin_incident_category_label($slug));

    return '<span class="reports-badge reports-badge--' . htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') . '">'
        . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
        . '</span>';
}

/**
 * @return array{date: string, time: string}
 */
function admin_incident_history_datetime_parts(string $at): array
{
    $at = trim($at);
    if ($at === '') {
        return ['date' => '—', 'time' => ''];
    }

    if (preg_match('/^(.+?),\s*(\d{1,2}:\d{2}(?::\d{2})?)\s*)$/', $at, $m)) {
        return ['date' => trim($m[1]), 'time' => trim($m[2])];
    }

    return ['date' => $at, 'time' => ''];
}

function admin_incident_person_involved_label(array $report): string
{
    $person = trim((string) ($report['person_involved'] ?? $report['guard_involved'] ?? ''));

    return $person !== '' ? $person : '—';
}

function admin_incident_modal_cell_text(string $value): string
{
    $value = trim($value);

    return $value !== '' ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : '—';
}

function admin_incident_modal_handwriting_text(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '—';
    }

    return nl2br(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), false);
}

/**
 * Head-guard upload shown beside extracted text for verification.
 */
function admin_incident_modal_scan_html(array $report): string
{
    $scanUrl = trim((string) ($report['scan_url'] ?? ''));
    $ref = trim((string) ($report['ref'] ?? 'Incident report'));

    if ($scanUrl === '') {
        return '<section class="reports-incident-scan reports-incident-scan--empty" aria-label="Submitted form scan">'
            . '<h4 class="reports-incident-scan__heading">Uploaded form (reference)</h4>'
            . '<p class="reports-incident-scan__empty">No scan image on file for this report.</p>'
            . '</section>';
    }

    $safeUrl = htmlspecialchars($scanUrl, ENT_QUOTES, 'UTF-8');
    $safeRef = htmlspecialchars($ref, ENT_QUOTES, 'UTF-8');

    return '<section class="reports-incident-scan" aria-label="Submitted form scan">'
        . '<h4 class="reports-incident-scan__heading">Uploaded form (reference)</h4>'
        . '<p class="reports-incident-scan__hint">Compare the head guard\'s scan with the extracted handwriting below.</p>'
        . '<a href="' . $safeUrl . '" target="_blank" rel="noopener noreferrer" class="reports-incident-scan__link">'
        . '<img class="reports-incident-scan__img" src="' . $safeUrl . '" alt="Scanned post-incident form for ' . $safeRef . '">'
        . '</a>'
        . '<p class="reports-incident-scan__open">'
        . '<a href="' . $safeUrl . '" target="_blank" rel="noopener noreferrer">Open full size</a>'
        . '</p></section>';
}

/**
 * As-scanned two-column layout: incident description (left), action taken (right).
 */
function admin_incident_modal_as_is_html(string $incidentDescription, string $actionTaken): string
{
    $incidentDescription = trim($incidentDescription);
    $actionTaken = trim($actionTaken);
    $emptyClass = $incidentDescription === '' && $actionTaken === '' ? ' is-empty' : '';

    return '<section id="modal-as-is-view" class="reports-detail-sheet__section" aria-label="Handwritten report (as scanned)">'
        . '<h4 class="reports-incident-as-is__heading">Form handwriting (as written)</h4>'
        . '<div class="reports-incident-as-is' . $emptyClass . '">'
        . '<div class="reports-incident-as-is__col reports-incident-as-is__col--description">'
        . '<span class="reports-incident-as-is__label">Incident description</span>'
        . '<div class="reports-incident-as-is__body">' . admin_incident_modal_handwriting_text($incidentDescription) . '</div>'
        . '</div>'
        . '<div class="reports-incident-as-is__col reports-incident-as-is__col--action">'
        . '<span class="reports-incident-as-is__label">Action taken</span>'
        . '<div class="reports-incident-as-is__body">' . admin_incident_modal_handwriting_text($actionTaken) . '</div>'
        . '</div>'
        . '</div></section>';
}

function admin_incident_modal_sheet_field_html(string $label, string $value, string $modifier = ''): string
{
    $trimmed = trim($value);
    $mod = $modifier !== '' ? ' reports-detail-sheet__field--' . $modifier : '';
    $empty = $trimmed === '' ? ' is-empty' : '';

    return '<div class="reports-detail-sheet__field' . $mod . $empty . '">'
        . '<span class="reports-detail-sheet__label">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>'
        . '<span class="reports-detail-sheet__value">' . admin_incident_modal_cell_text($trimmed) . '</span>'
        . '</div>';
}

/**
 * Thumbnail preview of scan + evidence images (between description and severity).
 *
 * @param array<string, mixed> $report
 */
function admin_incident_modal_attachments_field_html(
    array $report,
    string $label = 'Attachments',
    string $emptyText = 'No images attached'
): string {
    $attachments = is_array($report['attachments'] ?? null) ? $report['attachments'] : [];
    $html = '<div class="reports-detail-sheet__field reports-detail-sheet__field--attachments">';
    $html .= '<span class="reports-detail-sheet__label">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
    $html .= '<div class="reports-detail-sheet__value reports-incident-attachments">';

    if ($attachments === []) {
        $html .= '<span class="reports-incident-attachments__empty">' . htmlspecialchars($emptyText, ENT_QUOTES, 'UTF-8') . '</span>';
    } else {
        $html .= '<div class="reports-incident-attachments__grid" role="list">';
        foreach ($attachments as $attachment) {
            if (!is_array($attachment)) {
                continue;
            }
            $url = trim((string) ($attachment['url'] ?? ''));
            if ($url === '') {
                continue;
            }
            $label = trim((string) ($attachment['label'] ?? 'Attachment'));
            $html .= '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" role="listitem"'
                . ' class="reports-incident-attachments__link" data-reports-attachment-preview'
                . ' target="_blank" rel="noopener noreferrer">';
            $html .= '<img class="reports-incident-attachments__thumb" src="'
                . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" alt="'
                . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '" loading="lazy" decoding="async">';
            $html .= '<span class="reports-incident-attachments__caption">'
                . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
                . '</span></a>';
        }
        $html .= '</div>';
    }

    $html .= '</div></div>';

    return $html;
}

/**
 * Report summary — text-first grid (wireframe layout, readable spacing).
 *
 * @param array<string, mixed> $report
 */
function admin_incident_modal_details_html(array $report): string
{
    $post = trim((string) ($report['site'] ?? ''));
    $headGuard = trim((string) ($report['head_guard_name'] ?? $report['submitter_name'] ?? ''));
    $headGuardId = trim((string) ($report['head_guard_id'] ?? $report['submitter_id'] ?? ''));
    if ($headGuard !== '' && $headGuardId !== '') {
        $headGuard .= ' (' . $headGuardId . ')';
    } elseif ($headGuard === '' && $headGuardId !== '') {
        $headGuard = $headGuardId;
    }

    $incident = trim((string) ($report['incident_type'] ?? ''));
    $severity = trim((string) ($report['severity'] ?? 'Medium'));
    $person = admin_incident_person_involved_label($report);
    if ($person === '') {
        $person = admin_incident_person_from_report($report);
    }
    $incidentDescription = trim((string) ($report['incident_description'] ?? ''));
    $actionTaken = trim((string) ($report['action_taken'] ?? ''));
    $formName = trim((string) ($report['form_name'] ?? ''));
    $formDate = trim((string) ($report['form_date'] ?? ''));

    $html = '<div class="reports-detail-sheet" role="group" aria-label="Report summary">'
        . admin_incident_modal_scan_html($report)
        . '<section class="reports-detail-sheet__section" aria-label="Assignment">'
        . '<div class="reports-detail-sheet__grid reports-detail-sheet__grid--people">'
        . admin_incident_modal_sheet_field_html('Post', $post)
        . admin_incident_modal_sheet_field_html('Head guard', $headGuard)
        . admin_incident_modal_sheet_field_html('Guard', $person)
        . '</div></section>';

    if ($formName !== '' || $formDate !== '') {
        $html .= '<section class="reports-detail-sheet__section" aria-label="Form header">'
            . '<div class="reports-detail-sheet__grid reports-detail-sheet__grid--people">'
            . admin_incident_modal_sheet_field_html('Subject (form)', $formName)
            . admin_incident_modal_sheet_field_html('Date (form)', $formDate)
            . '</div></section>';
    }

    $html .= admin_incident_modal_as_is_html($incidentDescription, $actionTaken);

    $html .= '<section class="reports-detail-sheet__section" aria-label="Classification">'
        . '<div class="reports-detail-sheet__grid reports-detail-sheet__grid--incident">'
        . admin_incident_modal_sheet_field_html('Incident', $incident, 'incident')
        . admin_incident_modal_attachments_field_html($report)
        . admin_incident_modal_sheet_field_html('Severity', $severity, 'severity')
        . '</div></section></div>';

    return $html;
}

function admin_incident_status_badge_html(array $report): string
{
    $slug = (string) ($report['status'] ?? ADMIN_INCIDENT_STATUS_ONGOING);
    if (!admin_incident_status_is_valid($slug)) {
        $slug = ADMIN_INCIDENT_STATUS_ONGOING;
    }
    $label = (string) ($report['status_label'] ?? admin_incident_status_label($slug));
    $tip = admin_incident_status_description($slug);

    return '<span class="reports-badge reports-badge--' . htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') . '"'
        . ' title="' . htmlspecialchars($tip, ENT_QUOTES, 'UTF-8') . '">'
        . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
        . '</span>';
}

/** Inline SVG for table row action buttons (no Font Awesome dependency). */
function admin_incident_action_icon(string $kind): string
{
    $attrs = 'class="reports-action-icon" viewBox="0 0 24 24" fill="none" '
        . 'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" '
        . 'aria-hidden="true" focusable="false"';

    $svg = match ($kind) {
        'view' => '<svg ' . $attrs . '>'
            . '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>'
            . '<circle cx="12" cy="12" r="3"/>'
            . '</svg>',
        'edit' => '<svg ' . $attrs . '>'
            . '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>'
            . '<path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>'
            . '</svg>',
        'print' => '<svg ' . $attrs . '>'
            . '<polyline points="6 9 6 2 18 2 18 9"/>'
            . '<path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>'
            . '<rect x="6" y="14" width="12" height="8"/>'
            . '</svg>',
        'download' => '<svg ' . $attrs . '>'
            . '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>'
            . '<polyline points="7 10 12 15 17 10"/>'
            . '<line x1="12" y1="15" x2="12" y2="3"/>'
            . '</svg>',
        'delete' => '<svg ' . $attrs . '>'
            . '<polyline points="3 6 5 6 21 6"/>'
            . '<path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>'
            . '<path d="M10 11v6M14 11v6"/>'
            . '<path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>'
            . '</svg>',
        'archive' => '<svg ' . $attrs . '>'
            . '<polyline points="21 8 21 21 3 21 3 8"/>'
            . '<rect x="1" y="3" width="22" height="5"/>'
            . '<line x1="10" y1="12" x2="14" y2="12"/>'
            . '</svg>',
        default => '',
    };

    if ($svg === '') {
        return '';
    }

    return '<span class="reports-action-btn__icon">' . $svg . '</span>';
}

/** Modal copy — operation flow (audit trail for a single incident). */
function admin_incident_timeline_section_title(): string
{
    return 'Operation flow';
}

function admin_incident_timeline_section_description(): string
{
    return 'Chronological audit trail: head guard filing, automatic classification, operations decisions, and registry updates.';
}

function admin_incident_progression_edit_intro(): string
{
    return 'Correct the incident description and action taken if the scanned text is wrong, then update the operation flow below and save.';
}

/**
 * Editable incident description + action taken (operations correction of OCR / handwriting).
 *
 * @param array<string, mixed> $report
 */
/**
 * @param array<string, mixed> $report
 * @param array<string, mixed> $input
 * @return array<string, mixed>
 */
function admin_incident_apply_report_body_edit(array $report, array $input, string $actorId): array
{
    if (!array_key_exists('incident_description', $input) && !array_key_exists('action_taken', $input)) {
        return $report;
    }

    $incidentDescription = trim((string) ($input['incident_description'] ?? ''));
    $actionTaken = trim((string) ($input['action_taken'] ?? ''));
    $oldDesc = trim((string) ($report['incident_description'] ?? ''));
    $oldAction = trim((string) ($report['action_taken'] ?? ''));

    if ($incidentDescription === $oldDesc && $actionTaken === $oldAction) {
        return $report;
    }

    $report['incident_description'] = $incidentDescription;
    $report['action_taken'] = $actionTaken;
    if ($incidentDescription !== '') {
        $report['summary'] = $incidentDescription;
    }

    $history = is_array($report['history'] ?? null) ? $report['history'] : [];
    if ($history !== [] && is_array($history[0])) {
        $history[0]['description'] = $incidentDescription;
        $history[0]['incident_description'] = $incidentDescription;
        $history[0]['immediate_action'] = $actionTaken;
        $history[0]['action_taken'] = $actionTaken;
        $history[0]['edited_at'] = admin_incident_history_now();
        $history[0]['edited_by'] = $actorId;
        $report['history'] = $history;
    }

    return $report;
}

function admin_incident_modal_report_body_edit_html(array $report): string
{
    $incidentDescription = trim((string) ($report['incident_description'] ?? ''));
    $actionTaken = trim((string) ($report['action_taken'] ?? ''));

    return '<section class="reports-report-body-edit" aria-labelledby="modal-report-body-edit-heading">'
        . '<header class="reports-modal-form__section-header">'
        . '<h3 id="modal-report-body-edit-heading" class="reports-modal-form__section-title">Report text</h3>'
        . '<p class="reports-modal-form__section-desc">Fix wording from the uploaded form. Changes are saved with the report and shown in the operation flow.</p>'
        . '</header>'
        . '<div class="reports-incident-as-is reports-incident-as-is--editable">'
        . '<div class="reports-incident-as-is__col reports-incident-as-is__col--description">'
        . '<label class="reports-incident-as-is__label" for="edit-incident-description">Incident description</label>'
        . '<textarea id="edit-incident-description" name="incident_description" class="reports-op-flow__input reports-op-flow__textarea reports-report-body-edit__input" rows="5" maxlength="8000" placeholder="Describe what happened…">'
        . htmlspecialchars($incidentDescription, ENT_QUOTES, 'UTF-8')
        . '</textarea>'
        . '</div>'
        . '<div class="reports-incident-as-is__col reports-incident-as-is__col--action">'
        . '<label class="reports-incident-as-is__label" for="edit-action-taken">Action taken</label>'
        . '<textarea id="edit-action-taken" name="action_taken" class="reports-op-flow__input reports-op-flow__textarea reports-report-body-edit__input" rows="5" maxlength="8000" placeholder="Immediate action by the head guard…">'
        . htmlspecialchars($actionTaken, ENT_QUOTES, 'UTF-8')
        . '</textarea>'
        . '</div>'
        . '</div></section>';
}

/** @return array<string, string> */
function admin_incident_history_ops_action_options(): array
{
    return [
        '' => '— Select action —',
        'accept' => 'Report accepted',
        'on_hold' => 'Report on hold',
        'denied' => 'Report not accepted',
        'response' => 'Operations note',
        'registry' => 'Registry status change',
    ];
}

function admin_incident_history_ops_action_select_html(string $name, string $selected = '', string $id = ''): string
{
    $idAttr = $id !== '' ? ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' : '';
    $html = '<select name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" class="reports-op-flow__input reports-op-flow__cell-input"' . $idAttr . '>';
    foreach (admin_incident_history_ops_action_options() as $value => $label) {
        $sel = $value === $selected ? ' selected' : '';
        $html .= '<option value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"' . $sel . '>'
            . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
            . '</option>';
    }

    return $html . '</select>';
}

/** @param array<string, mixed> $entry */
function admin_incident_action_type_from_entry(array $entry): string
{
    if (admin_incident_history_entry_is_decision($entry)) {
        $event = strtolower(trim((string) ($entry['event'] ?? '')));
        return match ($event) {
            'report accepted' => 'accept',
            'report on hold' => 'on_hold',
            'report not accepted' => 'denied',
            default => 'accept',
        };
    }

    if (admin_incident_history_entry_is_registry($entry)) {
        return 'registry';
    }

    return 'response';
}

/**
 * @param array<string, mixed> $rowInput
 */
function admin_incident_validate_new_history_row(array $rowInput): ?string
{
    $actionType = trim((string) ($rowInput['action_type'] ?? ''));
    if ($actionType === '') {
        return null;
    }

    $note = trim((string) ($rowInput['note'] ?? ''));
    $meta = admin_incident_operations_decision_meta($actionType);
    if ($meta !== null && !empty($meta['requires_note']) && $note === '') {
        return 'Notes are required for on hold or not accepted.';
    }

    if ($actionType === 'registry') {
        $status = trim((string) ($rowInput['registry_status'] ?? ''));
        if ($status === '' || !admin_incident_status_is_valid($status)) {
            return 'Select a registry status for the new row.';
        }
    }

    return null;
}

/**
 * @param array<string, mixed> $report
 * @param array<string, mixed> $rowInput
 */
function admin_incident_apply_history_new_row(array $report, array $rowInput, string $actorId): array
{
    $actionType = trim((string) ($rowInput['action_type'] ?? ''));
    if ($actionType === '') {
        return $report;
    }

    $note = trim((string) ($rowInput['note'] ?? ''));
    $meta = admin_incident_operations_decision_meta($actionType);

    if ($meta !== null) {
        if (!empty($meta['requires_note']) && $note === '') {
            return $report;
        }
        if ($note === '' && $actionType === 'accept') {
            $note = 'Accepted for operations review — case continues.';
        }

        $report = admin_incident_append_history($report, $meta['event'], $note, $actorId, [
            'source' => 'admin',
            'kind' => $meta['kind'],
        ]);
        $report['status'] = $meta['status'];

        return admin_incident_touch_updated($report);
    }

    if ($actionType === 'registry') {
        $status = trim((string) ($rowInput['registry_status'] ?? ''));
        if (!admin_incident_status_is_valid($status)) {
            return $report;
        }
        $registryNote = $note !== '' ? $note : 'Registry status updated.';
        $report = admin_incident_append_history(
            $report,
            'Registry: ' . admin_incident_status_label($status),
            $registryNote,
            $actorId,
            ['source' => 'admin', 'kind' => 'status']
        );
        $report['status'] = $status;

        return admin_incident_touch_updated($report);
    }

    $event = trim((string) ($rowInput['event'] ?? ''));
    if ($event === '') {
        $event = 'Operations response';
    }
    $responseNote = $note !== '' ? $note : 'Progression updated.';

    $report = admin_incident_append_history($report, $event, $responseNote, $actorId, [
        'source' => 'admin',
        'kind' => 'response',
    ]);

    return admin_incident_touch_updated($report);
}

function admin_incident_history_new_row_editable_html(): string
{
    return '<tr class="reports-op-flow__row reports-op-flow__row--editing reports-op-flow__row--new" data-history-index="new">'
        . '<td class="reports-op-flow__when"><span class="reports-op-flow__new-label">New</span></td>'
        . '<td class="reports-op-flow__action">'
        . admin_incident_history_ops_action_select_html('history_row[new][action_type]', '', 'history-row-new-action')
        . '</td>'
        . '<td class="reports-op-flow__notes">'
        . '<textarea class="reports-op-flow__input reports-op-flow__textarea reports-op-flow__cell-input" name="history_row[new][note]" rows="2" maxlength="1000" placeholder="Decision notes, follow-up, evidence request, closure memo…"></textarea>'
        . '<div class="reports-op-flow__registry-inline" data-registry-inline hidden>'
        . admin_incident_history_status_select_html('history_row[new][registry_status]', ADMIN_INCIDENT_STATUS_ONGOING, 'history-row-new-registry')
        . '</div></td>'
        . '<td class="reports-op-flow__by">Operations</td>'
        . '</tr>';
}

/**
 * @param array<string, mixed> $report
 */
function admin_incident_history_registry_status_row_editable_html(array $report): string
{
    $statusSlug = (string) ($report['status'] ?? ADMIN_INCIDENT_STATUS_ONGOING);
    if (!admin_incident_status_is_valid($statusSlug)) {
        $statusSlug = ADMIN_INCIDENT_STATUS_ONGOING;
    }

    return '<tr class="reports-op-flow__row reports-op-flow__row--status reports-op-flow__row--editing">'
        . '<td class="reports-op-flow__when">—</td>'
        . '<td class="reports-op-flow__action">Case registry</td>'
        . '<td class="reports-op-flow__notes">'
        . admin_incident_history_status_select_html('status', $statusSlug, 'edit-registry-status')
        . '</td>'
        . '<td class="reports-op-flow__by"></td>'
        . '</tr>';
}

/** @param array<string, mixed> $report */
function admin_incident_report_has_operations_decision(array $report): bool
{
    $history = is_array($report['history'] ?? null) ? $report['history'] : [];
    foreach ($history as $entry) {
        if (is_array($entry) && admin_incident_history_entry_is_decision($entry)) {
            return true;
        }
    }

    return false;
}

function admin_incident_operations_decision_select_html(string $name = 'ops_decision', string $selected = ''): string
{
    $html = '<select name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" id="edit-ops-decision" class="reports-op-flow__input">';
    $html .= '<option value="">— Select decision —</option>';
    $options = [
        'accept' => 'Accept report (continue review)',
        'on_hold' => 'On hold',
        'denied' => 'Not accepted',
    ];
    foreach ($options as $value => $label) {
        $sel = $value === $selected ? ' selected' : '';
        $html .= '<option value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"' . $sel . '>'
            . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
            . '</option>';
    }

    return $html . '</select>';
}

/**
 * Progression edit panel — registry status, first decision, or follow-up.
 *
 * @param array<string, mixed> $report
 */
function admin_incident_progression_edit_panel_html(array $report): string
{
    $statusSlug = (string) ($report['status'] ?? ADMIN_INCIDENT_STATUS_ONGOING);
    if (!admin_incident_status_is_valid($statusSlug)) {
        $statusSlug = ADMIN_INCIDENT_STATUS_ONGOING;
    }
    $statusLabel = admin_incident_status_label($statusSlug);
    $hasDecision = admin_incident_report_has_operations_decision($report);

    $html = '<div class="reports-progression-edit__fields">';
    $html .= '<div class="reports-progression-edit__row reports-progression-edit__row--registry">';
    $html .= '<div class="reports-form-field">';
    $html .= '<label for="edit-registry-status">Registry status</label>';
    $html .= admin_incident_history_status_select_html('status', $statusSlug, 'edit-registry-status');
    $html .= '<p class="reports-form-hint">Current: <strong>' . htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8')
        . '</strong>. Changing this adds a registry step when you save (if it differs).</p>';
    $html .= '</div></div>';

    $html .= '<div id="progression-first-decision" class="reports-progression-edit__block"'
        . ($hasDecision ? ' hidden' : '')
        . ' data-progression-block="first-decision">';
    $html .= '<h4 class="reports-progression-edit__block-title">First operations review</h4>';
    $html .= '<p class="reports-form-hint">Required response to the head guard filing — accept to continue, or record on hold / not accepted with notes.</p>';
    $html .= '<div class="reports-progression-edit__row">';
    $html .= '<div class="reports-form-field"><label for="edit-ops-decision">Decision</label>';
    $html .= admin_incident_operations_decision_select_html();
    $html .= '</div>';
    $html .= '<div class="reports-form-field reports-progression-edit__note">';
    $html .= '<label for="edit-ops-note">Decision notes</label>';
    $html .= '<textarea id="edit-ops-note" name="ops_note" rows="3" maxlength="1000"'
        . ' placeholder="Required for not accepted or on hold. Summarize terms, reason, or next steps."></textarea>';
    $html .= '</div></div></div>';

    $html .= '<div id="progression-follow-up" class="reports-progression-edit__block"'
        . ($hasDecision ? '' : ' hidden')
        . ' data-progression-block="follow-up">';
    $html .= '<h4 class="reports-progression-edit__block-title">Follow-up</h4>';
    $html .= '<p class="reports-form-hint">Add investigation notes, evidence requests, or closure details. Combine with a registry change above when closing or pausing the case.</p>';
    $html .= '<div class="reports-form-field reports-progression-edit__note">';
    $html .= '<label for="edit-ops-followup">Follow-up note (optional)</label>';
    $html .= '<textarea id="edit-ops-followup" name="ops_followup" rows="3" maxlength="1000"'
        . ' placeholder="e.g. Awaiting CCTV from head guard; interview scheduled; case closed per client memo."></textarea>';
    $html .= '</div></div>';

    $html .= '</div>';

    return $html;
}

function admin_incident_timeline_empty_message(): string
{
    return 'No operations history yet. Entries are added when a report is submitted or updated by operations.';
}

/** @param array<string, mixed> $entry */
function admin_incident_history_entry_source(array $entry): string
{
    return admin_incident_pipeline_entry_source($entry);
}

/** @param array<string, mixed> $entry */
function admin_incident_history_entry_kind(array $entry, int $index): string
{
    $kind = strtolower(trim((string) ($entry['kind'] ?? '')));
    if ($kind !== '') {
        return $kind;
    }

    if ($index === 0 && admin_incident_history_entry_source($entry) === 'head_guard') {
        return 'field_submission';
    }

    $event = strtolower(trim((string) ($entry['event'] ?? '')));
    if (str_starts_with($event, 'status:') || str_starts_with($event, 'registry:')) {
        return 'status';
    }
    if (str_contains($event, 'assigned')) {
        return 'routing';
    }

    return 'response';
}

/**
 * @param array<string, mixed> $entry
 * @param array<string, mixed> $report
 * @return array{description: string, immediate_action: string}
 */
function admin_incident_field_submission_content(array $entry, array $report): array
{
    $description = trim((string) ($entry['description'] ?? $entry['incident_description'] ?? ''));
    $action = trim((string) ($entry['immediate_action'] ?? $entry['action_taken'] ?? ''));

    if ($description === '') {
        $description = trim((string) ($report['incident_description'] ?? $report['summary'] ?? ''));
    }
    if ($action === '') {
        $action = trim((string) ($report['action_taken'] ?? ''));
    }

    $note = trim((string) ($entry['note'] ?? ''));
    if ($description === '' && $note !== '') {
        $description = $note;
    }

    return ['description' => $description, 'immediate_action' => $action];
}

/** @param array<string, mixed> $entry */
function admin_incident_history_action_label(array $entry, int $index): string
{
    $kind = admin_incident_history_entry_kind($entry, $index);
    if ($kind === 'field_submission' || ($index === 0 && admin_incident_history_entry_source($entry) === 'head_guard')) {
        return 'Report filed';
    }

    return trim((string) ($entry['event'] ?? 'Update'));
}

/**
 * @param array<string, mixed> $entry
 * @param array<string, mixed> $report
 */
function admin_incident_history_notes_text(array $entry, int $index, array $report): string
{
    $kind = admin_incident_history_entry_kind($entry, $index);
    if ($kind === 'field_submission' || ($index === 0 && admin_incident_history_entry_source($entry) === 'head_guard')) {
        $content = admin_incident_field_submission_content($entry, $report);
        $lines = [];
        if ($content['description'] !== '') {
            $lines[] = 'Description: ' . $content['description'];
        }
        if ($content['immediate_action'] !== '') {
            $lines[] = 'Immediate action: ' . $content['immediate_action'];
        }

        return $lines !== [] ? implode("\n", $lines) : '—';
    }

    $note = trim((string) ($entry['note'] ?? ''));

    return $note !== '' ? $note : '—';
}

/**
 * @param array<string, mixed> $entry
 * @param array<string, mixed> $report
 */
function admin_incident_history_by_label(array $entry, array $report): string
{
    if (admin_incident_history_entry_source($entry) === 'head_guard') {
        $name = trim((string) ($report['head_guard_name'] ?? $report['submitter_name'] ?? ''));

        return $name !== '' ? $name : 'Head guard';
    }

    return 'Operations';
}

/**
 * @param array<string, mixed> $entry
 * @param array<string, mixed> $report
 */
function admin_incident_history_row_html(array $entry, int $index, array $report): string
{
    $parts = admin_incident_history_datetime_parts(admin_incident_history_display_timestamp($entry));
    $action = admin_incident_history_action_label($entry, $index);
    $notes = admin_incident_history_notes_text($entry, $index, $report);
    $by = admin_incident_history_by_label($entry, $report);

    return '<tr class="reports-op-flow__row" data-history-index="' . $index . '">'
        . '<td class="reports-op-flow__when">'
        . '<span class="reports-op-flow__date">' . htmlspecialchars($parts['date'], ENT_QUOTES, 'UTF-8') . '</span>'
        . '<span class="reports-op-flow__time">' . htmlspecialchars($parts['time'], ENT_QUOTES, 'UTF-8') . '</span>'
        . '</td>'
        . '<td class="reports-op-flow__action">' . htmlspecialchars($action, ENT_QUOTES, 'UTF-8') . '</td>'
        . '<td class="reports-op-flow__notes"><span class="reports-op-flow__notes-text">' . htmlspecialchars($notes, ENT_QUOTES, 'UTF-8') . '</span></td>'
        . '<td class="reports-op-flow__by">' . htmlspecialchars($by, ENT_QUOTES, 'UTF-8') . '</td>'
        . '</tr>';
}

/**
 * @param array<string, mixed> $entry
 * @param array<string, mixed> $report
 */
function admin_incident_history_row_editable_html(array $entry, int $index, array $report): string
{
    if (admin_incident_history_entry_source($entry) === 'system') {
        return admin_incident_history_row_html($entry, $index, $report);
    }

    $parts = admin_incident_history_datetime_parts(admin_incident_history_display_timestamp($entry));
    $by = admin_incident_history_by_label($entry, $report);
    $prefix = 'history_row[' . $index . ']';
    $rowClass = 'reports-op-flow__row reports-op-flow__row--editing';
    $editedMark = trim((string) ($entry['edited_at'] ?? '')) !== ''
        ? ' <span class="reports-op-flow__edited-tag">Updated</span>'
        : '';

    if (admin_incident_history_entry_source($entry) === 'head_guard') {
        $rowClass .= ' reports-op-flow__row--head-guard';
        $actionCell = '<span class="reports-op-flow__action-label">Report filed</span>';
        $notesCell = admin_incident_history_head_guard_notes_html($entry, $report);
    } elseif (admin_incident_history_entry_is_editable($entry)) {
        $note = historyEntryNoteForEditPhp($entry);
        if (admin_incident_history_entry_is_decision($entry)) {
            $actionType = admin_incident_action_type_from_entry($entry);
            $actionCell = admin_incident_history_ops_action_select_html($prefix . '[action_type]', $actionType);
            $notesCell = '<textarea class="reports-op-flow__input reports-op-flow__textarea reports-op-flow__cell-input" name="' . $prefix . '[note]" rows="2" maxlength="1000" placeholder="Decision notes…">'
                . htmlspecialchars($note, ENT_QUOTES, 'UTF-8')
                . '</textarea>';
        } elseif (admin_incident_history_entry_is_registry($entry)) {
            $statusSlug = admin_incident_status_slug_from_history_event((string) ($entry['event'] ?? ''))
                ?? (string) ($report['status'] ?? ADMIN_INCIDENT_STATUS_ONGOING);
            $actionCell = admin_incident_history_ops_action_select_html($prefix . '[action_type]', 'registry')
                . admin_incident_history_status_select_html($prefix . '[registry_status]', $statusSlug);
            $notesCell = '<textarea class="reports-op-flow__input reports-op-flow__textarea reports-op-flow__cell-input" name="' . $prefix . '[note]" rows="2" maxlength="1000" placeholder="Note…">'
                . htmlspecialchars($note, ENT_QUOTES, 'UTF-8')
                . '</textarea>';
        } else {
            $actionCell = '<input type="text" class="reports-op-flow__input reports-op-flow__cell-input" name="' . $prefix . '[event]" value="'
                . htmlspecialchars(trim((string) ($entry['event'] ?? '')), ENT_QUOTES, 'UTF-8')
                . '" maxlength="120" placeholder="Action label">';
            $notesCell = '<textarea class="reports-op-flow__input reports-op-flow__textarea reports-op-flow__cell-input" name="' . $prefix . '[note]" rows="2" maxlength="1000" placeholder="Notes…">'
                . htmlspecialchars($note, ENT_QUOTES, 'UTF-8')
                . '</textarea>';
        }
    } else {
        return admin_incident_history_row_html($entry, $index, $report);
    }

    return '<tr class="' . $rowClass . '" data-history-index="' . $index . '">'
        . '<td class="reports-op-flow__when">'
        . '<span class="reports-op-flow__date">' . htmlspecialchars($parts['date'], ENT_QUOTES, 'UTF-8') . '</span>'
        . '<span class="reports-op-flow__time">' . htmlspecialchars($parts['time'], ENT_QUOTES, 'UTF-8') . $editedMark . '</span>'
        . '</td>'
        . '<td class="reports-op-flow__action">' . $actionCell . '</td>'
        . '<td class="reports-op-flow__notes">' . $notesCell . '</td>'
        . '<td class="reports-op-flow__by">' . htmlspecialchars($by, ENT_QUOTES, 'UTF-8') . '</td>'
        . '</tr>';
}

/** @param array<string, mixed> $entry */
function historyEntryNoteForEditPhp(array $entry): string
{
    $note = trim((string) ($entry['note'] ?? ''));
    if (
        str_contains($note, 'Status set to')
        || str_contains($note, 'Registry status updated')
        || $note === 'Progression updated.'
    ) {
        return '';
    }

    return $note;
}

/**
 * Operation flow table (oldest → newest).
 *
 * @param list<array<string, mixed>> $history
 * @param array<string, mixed> $report
 */
function admin_incident_history_stepper_html(array $history, array $report, bool $editable = false): string
{
    $rows = [];
    foreach ($history as $index => $entry) {
        if (!is_array($entry)) {
            continue;
        }
        $rows[] = $editable
            ? admin_incident_history_row_editable_html($entry, $index, $report)
            : admin_incident_history_row_html($entry, $index, $report);
    }

    if ($history === [] && !$editable) {
        return '<p class="reports-op-flow__empty">' . htmlspecialchars(
            admin_incident_timeline_empty_message(),
            ENT_QUOTES,
            'UTF-8'
        ) . '</p>';
    }

    if ($editable) {
        $rows[] = admin_incident_history_new_row_editable_html();
        $rows[] = admin_incident_history_registry_status_row_editable_html($report);
    } else {
        $statusSlug = (string) ($report['status'] ?? ADMIN_INCIDENT_STATUS_ONGOING);
        $statusLabel = (string) ($report['status_label'] ?? admin_incident_status_label($statusSlug));
        $rows[] = '<tr class="reports-op-flow__row reports-op-flow__row--status">'
            . '<td class="reports-op-flow__when">—</td>'
            . '<td class="reports-op-flow__action">Case registry</td>'
            . '<td class="reports-op-flow__notes">' . htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') . '</td>'
            . '<td class="reports-op-flow__by"></td>'
            . '</tr>';
    }

    $tableClass = 'reports-op-flow__table' . ($editable ? ' reports-op-flow__table--editing' : '');
    $header = '<thead><tr class="reports-op-flow__head">'
        . '<th scope="col" class="reports-op-flow__col-when">Date</th>'
        . '<th scope="col" class="reports-op-flow__col-action">Action</th>'
        . '<th scope="col" class="reports-op-flow__col-notes">Notes</th>'
        . '<th scope="col" class="reports-op-flow__col-by">By</th>'
        . '</tr></thead>';

    return '<table class="' . $tableClass . '">' . $header . '<tbody>' . implode('', $rows) . '</tbody></table>';
}

/**
 * @param array<string, mixed> $report
 */
function admin_incident_search_blob(array $report): string
{
    return strtolower(implode(' ', [
        (string) ($report['ref'] ?? ''),
        (string) ($report['category_label'] ?? ''),
        (string) ($report['incident_type'] ?? ''),
        (string) ($report['site'] ?? ''),
        (string) ($report['severity'] ?? ''),
        (string) ($report['head_guard_name'] ?? ''),
        (string) ($report['head_guard_id'] ?? ''),
        'head guard',
        (string) ($report['summary'] ?? ''),
        (string) ($report['status_label'] ?? ''),
        (string) ($report['updated_display'] ?? ''),
    ]));
}

/** @param list<array<string, mixed>> $reports */
function admin_incident_export_csv(array $reports, ?string $filenameStem = null): void
{
    $stem = $filenameStem ?? ('incident-reports-' . date('Y-m-d'));
    $safeStem = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $stem) ?: 'incident-export';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $safeStem . '.csv"');

    $out = fopen('php://output', 'w');
    if ($out === false) {
        return;
    }

    fputcsv($out, ['Reference', 'Category', 'Incident type', 'Severity', 'Post', 'Head guard', 'Head guard ID', 'Date submitted', 'Date updated', 'Status', 'Summary']);
    foreach ($reports as $r) {
        fputcsv($out, [
            (string) ($r['ref'] ?? ''),
            (string) ($r['category_label'] ?? ''),
            (string) ($r['incident_type'] ?? ''),
            (string) ($r['severity'] ?? ''),
            (string) ($r['site'] ?? ''),
            (string) ($r['head_guard_name'] ?? ''),
            (string) ($r['head_guard_id'] ?? ''),
            (string) ($r['submitted_display'] ?? ''),
            (string) ($r['updated_display'] ?? ''),
            (string) ($r['status_label'] ?? ''),
            (string) ($r['summary'] ?? ''),
        ]);
    }
    fclose($out);
    exit;
}

