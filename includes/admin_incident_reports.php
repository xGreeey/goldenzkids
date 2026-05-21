<?php
declare(strict_types=1);

require_once __DIR__ . '/admin_incident_status.php';
require_once __DIR__ . '/admin_incident_guidelines.php';
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
            'summary' => 'Head guard reported a non-roster individual attempting to enter the admin wing after hours. CCTV clip attached; awaiting ops review.',
            'history' => [
                ['at' => '20 May 2026, 09:14', 'event' => 'Submitted by head guard', 'note' => 'Initial incident filed from field portal.'],
                ['at' => '20 May 2026, 10:02', 'event' => 'Assigned to operations', 'note' => 'Routed to admin queue for investigation.'],
                ['at' => '20 May 2026, 14:30', 'event' => 'Status: Open', 'note' => 'Interview with duty supervisor scheduled.'],
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

    $severity = trim((string) ($row['severity'] ?? ''));
    if (!admin_incident_severity_is_valid($severity)) {
        $severity = admin_incident_severity_for_type((string) ($row['incident_type'] ?? ''));
    }
    $row['severity'] = $severity;

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
        usort($out, static fn (array $a, array $b): int => strcmp((string) ($b['submitted_at'] ?? ''), (string) ($a['submitted_at'] ?? '')));

        return $out;
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
function admin_incident_append_history(array $report, string $event, string $note, string $actorId): array
{
    $history = $report['history'] ?? [];
    $history[] = [
        'at' => admin_incident_history_now(),
        'event' => $event,
        'note' => $note,
        'actor' => $actorId,
    ];
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

    $category = admin_incident_category_normalize((string) ($input['category'] ?? $oldCategory));

    $status = (string) ($input['status'] ?? $oldStatus);
    if (!admin_incident_status_is_valid($status)) {
        $status = $oldStatus;
    }

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
    $found['status'] = $status;

    $opsNote = trim((string) ($input['ops_note'] ?? ''));
    $fieldsChanged = $category !== $oldCategory
        || $incidentType !== $oldType
        || $site !== $oldSite
        || $severity !== $oldSeverity
        || $summary !== $oldSummary;
    $statusChanged = $status !== $oldStatus;

    if ($statusChanged || $fieldsChanged || $opsNote !== '') {
        $event = $statusChanged
            ? 'Status: ' . admin_incident_status_label($status)
            : 'Updated by operations';
        $noteParts = [];
        if ($opsNote !== '') {
            $noteParts[] = $opsNote;
        }
        if ($fieldsChanged && $opsNote === '') {
            $noteParts[] = 'Incident details revised by ' . $actorId . '.';
        }
        if ($statusChanged && $opsNote === '' && !$fieldsChanged) {
            $noteParts[] = 'Status set to ' . $found['status_label'] . ' by ' . $actorId . '.';
        }
        $found = admin_incident_append_history($found, $event, implode(' ', $noteParts), $actorId);
    }

    $found = admin_incident_touch_updated($found);
    $found = admin_incident_normalize($found);

    $reports[$idx] = $found;
    admin_incident_store_save($reports);

    return $found;
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
    foreach (admin_incident_sanctions_reference() as $ref) {
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
    $description = trim((string) ($report['summary'] ?? ''));
    $severity = trim((string) ($report['severity'] ?? 'Medium'));
    $person = admin_incident_person_involved_label($report);

    return '<div class="reports-detail-sheet" role="group" aria-label="Report summary">'
        . '<section class="reports-detail-sheet__section" aria-label="Assignment">'
        . '<div class="reports-detail-sheet__grid reports-detail-sheet__grid--people">'
        . admin_incident_modal_sheet_field_html('Post', $post)
        . admin_incident_modal_sheet_field_html('Head guard', $headGuard)
        . admin_incident_modal_sheet_field_html('Guard', $person)
        . '</div></section>'
        . '<section class="reports-detail-sheet__section" aria-label="Incident">'
        . '<div class="reports-detail-sheet__grid reports-detail-sheet__grid--incident">'
        . admin_incident_modal_sheet_field_html('Incident', $incident, 'incident')
        . admin_incident_modal_sheet_field_html('Description', $description, 'description')
        . admin_incident_modal_sheet_field_html('Severity', $severity, 'severity')
        . '</div></section></div>';
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
    return '';
}

function admin_incident_timeline_empty_message(): string
{
    return 'No operations history yet. Entries are added when a report is submitted or updated by operations.';
}

/**
 * Operation flow table (oldest → newest).
 *
 * @param list<array<string, mixed>> $history
 * @param array<string, mixed> $report
 */
function admin_incident_history_stepper_html(array $history, array $report): string
{
    if ($history === []) {
        return '<p class="reports-op-flow__empty">' . htmlspecialchars(
            admin_incident_timeline_empty_message(),
            ENT_QUOTES,
            'UTF-8'
        ) . '</p>';
    }

    $rows = [];

    foreach ($history as $index => $entry) {
        $event = trim((string) ($entry['event'] ?? 'Update'));
        $note = trim((string) ($entry['note'] ?? ''));
        $parts = admin_incident_history_datetime_parts((string) ($entry['at'] ?? ''));
        $stepNum = $index + 1;
        $title = 'Step ' . $stepNum . ' — ' . $event;
        $description = $note !== '' ? $note : '—';
        $action = $event;

        $rows[] = '<tr class="reports-op-flow__row">'
            . '<td class="reports-op-flow__when">'
            . '<span class="reports-op-flow__date">' . htmlspecialchars($parts['date'], ENT_QUOTES, 'UTF-8') . '</span>'
            . '<span class="reports-op-flow__time">' . htmlspecialchars($parts['time'], ENT_QUOTES, 'UTF-8') . '</span>'
            . '</td>'
            . '<td class="reports-op-flow__rule" aria-hidden="true"></td>'
            . '<td class="reports-op-flow__step">'
            . '<span class="reports-op-flow__step-title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</span>'
            . '<span class="reports-op-flow__step-desc">' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . '</span>'
            . '</td>'
            . '<td class="reports-op-flow__action">' . htmlspecialchars($action, ENT_QUOTES, 'UTF-8') . '</td>'
            . '</tr>';
    }

    $statusLabel = (string) ($report['status_label'] ?? admin_incident_status_label((string) ($report['status'] ?? '')));
    $rows[] = '<tr class="reports-op-flow__row reports-op-flow__row--status">'
        . '<td class="reports-op-flow__when"><span class="reports-op-flow__date">—</span></td>'
        . '<td class="reports-op-flow__rule" aria-hidden="true"></td>'
        . '<td class="reports-op-flow__status" colspan="2">'
        . htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8')
        . '</td></tr>';

    $header = '<thead><tr class="reports-op-flow__head">'
        . '<th scope="col" class="reports-op-flow__col-when">Recorded</th>'
        . '<th scope="col" class="reports-op-flow__col-rule" aria-hidden="true"></th>'
        . '<th scope="col" class="reports-op-flow__col-step">Step</th>'
        . '<th scope="col" class="reports-op-flow__col-action">Action</th>'
        . '</tr></thead>';

    return '<table class="reports-op-flow__table">' . $header . '<tbody>' . implode('', $rows) . '</tbody></table>';
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

