<?php
declare(strict_types=1);

require_once __DIR__ . '/admin_weekly_activity_status.php';
require_once __DIR__ . '/admin_incident_reports.php'; // modal field helpers
require_once __DIR__ . '/admin_daily_activity_reports.php';
require_once __DIR__ . '/guard_daily_activity.php';

const ADMIN_WEEKLY_ACTIVITY_SESSION_KEY = 'admin_weekly_activity_reports_store';

/** Full module name shown in page titles, sidebar, and section headers. */
const ADMIN_WEEKLY_SUMMARY_MODULE_LABEL = 'Weekly Summary Report';

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
    ];
}

/** @return list<array<string, mixed>> */
function admin_weekly_activity_store_raw(): array
{
    if (!isset($_SESSION[ADMIN_WEEKLY_ACTIVITY_SESSION_KEY]) || !is_array($_SESSION[ADMIN_WEEKLY_ACTIVITY_SESSION_KEY])) {
        $_SESSION[ADMIN_WEEKLY_ACTIVITY_SESSION_KEY] = admin_weekly_activity_seed_reports();
    }

    $out = [];
    foreach ($_SESSION[ADMIN_WEEKLY_ACTIVITY_SESSION_KEY] as $row) {
        if (is_array($row)) {
            $out[] = $row;
        }
    }

    return $out;
}

/** @return list<array<string, mixed>> */
function admin_weekly_activity_store_all(): array
{
    $out = [];
    foreach (admin_weekly_activity_store_raw() as $row) {
        if ((string) ($row['status'] ?? '') === ADMIN_WEEKLY_ACTIVITY_STATUS_DRAFT) {
            continue;
        }
        $out[] = admin_weekly_activity_normalize($row);
    }
    usort($out, static fn (array $a, array $b): int => strcmp((string) ($b['submitted_at'] ?? ''), (string) ($a['submitted_at'] ?? '')));

    return $out;
}

function admin_weekly_activity_default_week_start(): string
{
    $ts = strtotime('monday this week');

    return $ts !== false ? date('Y-m-d', $ts) : date('Y-m-d');
}

function admin_weekly_activity_default_week_end(): string
{
    return admin_weekly_activity_week_bounds(admin_weekly_activity_default_week_start())['week_end'];
}

function admin_weekly_activity_week_label_from_range(string $weekStart, string $weekEnd): string
{
    $ts = strtotime($weekStart) ?: time();
    $endTs = strtotime($weekEnd) ?: $ts;

    if (date('M Y', $ts) === date('M Y', $endTs)) {
        return date('j', $ts) . '–' . date('j', $endTs) . ' ' . date('M Y', $ts);
    }

    return date('j M', $ts) . ' – ' . date('j M Y', $endTs);
}

/**
 * @return array{week_start: string, week_end: string, week_label: string}|null
 */
function admin_weekly_activity_resolve_week_range(string $weekStart, string $weekEnd): ?array
{
    $weekStart = trim($weekStart);
    $weekEnd = trim($weekEnd);
    if ($weekStart === '' || $weekEnd === '') {
        return null;
    }

    $startTs = strtotime($weekStart);
    $endTs = strtotime($weekEnd);
    if ($startTs === false || $endTs === false) {
        return null;
    }

    if ($endTs < $startTs) {
        return null;
    }

    $start = date('Y-m-d', $startTs);
    $end = date('Y-m-d', $endTs);

    return [
        'week_start' => $start,
        'week_end' => $end,
        'week_label' => admin_weekly_activity_week_label_from_range($start, $end),
    ];
}

/**
 * @return array{week_start: string, week_end: string, week_label: string}
 */
function admin_weekly_activity_week_bounds(string $weekStart): array
{
    $ts = strtotime($weekStart);
    if ($ts === false) {
        $ts = strtotime(admin_weekly_activity_default_week_start()) ?: time();
    }

    $dow = (int) date('N', $ts);
    if ($dow !== 1) {
        $ts = strtotime('-' . ($dow - 1) . ' days', $ts) ?: $ts;
    }

    $start = date('Y-m-d', $ts);
    $endTs = strtotime('+6 days', $ts) ?: $ts;
    $end = date('Y-m-d', $endTs);

    return [
        'week_start' => $start,
        'week_end' => $end,
        'week_label' => admin_weekly_activity_week_label_from_range($start, $end),
    ];
}

/**
 * @param list<array<string, mixed>> $reports
 */
function admin_weekly_activity_next_reference(array $reports): string
{
    $year = date('Y');
    $max = 0;
    foreach ($reports as $row) {
        $ref = (string) ($row['ref'] ?? '');
        if (preg_match('/^' . preg_quote(GUARD_WEEKLY_ACTIVITY_REF_PREFIX, '/') . '-' . $year . '-(\d+)$/', $ref, $m)) {
            $max = max($max, (int) $m[1]);
        }
    }

    return GUARD_WEEKLY_ACTIVITY_REF_PREFIX . '-' . $year . '-' . str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
}

/**
 * @param list<array<string, mixed>> $reports
 */
function admin_weekly_activity_next_id(array $reports): string
{
    $max = 0;
    foreach ($reports as $row) {
        $id = (string) ($row['id'] ?? '');
        if (preg_match('/^war-(\d+)$/', $id, $m)) {
            $max = max($max, (int) $m[1]);
        }
    }

    return 'war-' . ($max + 1);
}

/**
 * @return list<array{key: string, head_guard_id: string, head_guard_name: string, site_name: string}>
 */
function admin_weekly_activity_generate_assignment_options(): array
{
    $seen = [];
    $options = [];

    $add = static function (string $hgId, string $hgName, string $post) use (&$seen, &$options): void {
        $hgId = trim($hgId);
        $hgName = trim($hgName) ?: 'Head guard';
        $post = trim($post) ?: '—';
        if ($post === '—' && $hgId === '') {
            return;
        }
        $key = $post . "\x1e" . $hgId;
        if (isset($seen[$key])) {
            return;
        }
        $seen[$key] = true;
        $options[] = [
            'key' => $key,
            'head_guard_id' => $hgId,
            'head_guard_name' => $hgName,
            'site_name' => $post,
        ];
    };

    foreach (admin_weekly_activity_store_raw() as $row) {
        $add(
            (string) ($row['head_guard_id'] ?? ''),
            (string) ($row['head_guard_name'] ?? ''),
            (string) ($row['site_name'] ?? '')
        );
    }

    foreach (admin_daily_activity_store_all() as $row) {
        $add(
            (string) ($row['head_guard_id'] ?? ''),
            (string) ($row['head_guard_name'] ?? ''),
            (string) ($row['site_name'] ?? '')
        );
    }

    usort(
        $options,
        static fn (array $a, array $b): int => strcasecmp($a['site_name'] . $a['head_guard_name'], $b['site_name'] . $b['head_guard_name'])
    );

    return $options;
}

function admin_weekly_activity_date_in_range(string $submittedAt, string $weekStart, string $weekEnd): bool
{
    $submitted = substr(trim($submittedAt), 0, 10);
    if ($submitted === '') {
        return false;
    }

    return $submitted >= $weekStart && $submitted <= $weekEnd;
}

function admin_weekly_activity_site_matches(string $rowSite, string $targetSite): bool
{
    $rowSite = trim($rowSite);
    $targetSite = trim($targetSite);
    if ($rowSite === '' || $targetSite === '' || $targetSite === '—') {
        return false;
    }
    if (strcasecmp($rowSite, $targetSite) === 0) {
        return true;
    }

    return stripos($rowSite, $targetSite) !== false || stripos($targetSite, $rowSite) !== false;
}

function admin_weekly_activity_matches_assignment(
    string $rowSite,
    string $rowHeadGuardId,
    string $targetSite,
    string $targetHeadGuardId
): bool {
    if (!admin_weekly_activity_site_matches($rowSite, $targetSite)) {
        return false;
    }
    $rowHg = trim($rowHeadGuardId);
    if ($targetHeadGuardId !== '' && $rowHg !== '' && strcasecmp($rowHg, $targetHeadGuardId) !== 0) {
        return false;
    }

    return true;
}

/**
 * @return list<array<string, mixed>>
 */
function admin_weekly_activity_collect_daily_for_range(
    string $siteName,
    string $headGuardId,
    string $weekStart,
    string $weekEnd
): array {
    $items = [];

    foreach (admin_daily_activity_store_all() as $row) {
        if (!admin_weekly_activity_matches_assignment(
            (string) ($row['site_name'] ?? ''),
            (string) ($row['head_guard_id'] ?? ''),
            $siteName,
            $headGuardId
        )) {
            continue;
        }
        if (!admin_weekly_activity_date_in_range((string) ($row['submitted_at'] ?? ''), $weekStart, $weekEnd)) {
            continue;
        }

        $items[] = [
            'id' => (string) ($row['id'] ?? ''),
            'ref' => (string) ($row['ref'] ?? ''),
            'submitted_at' => substr((string) ($row['submitted_at'] ?? ''), 0, 10),
            'submitted_display' => (string) ($row['submitted_display'] ?? ''),
            'activity_mode' => (string) ($row['activity_mode'] ?? GUARD_DAILY_ACTIVITY_MODE_NORMAL),
            'activity_mode_label' => (string) ($row['activity_mode_label'] ?? ''),
            'summary' => trim((string) ($row['summary'] ?? '')) ?: '—',
            'status_label' => (string) ($row['status_label'] ?? ''),
        ];
    }

    usort(
        $items,
        static fn (array $a, array $b): int => strcmp((string) ($b['submitted_at'] ?? ''), (string) ($a['submitted_at'] ?? ''))
    );

    return $items;
}

/**
 * @return list<array<string, mixed>>
 */
function admin_weekly_activity_collect_incidents_for_range(
    string $siteName,
    string $headGuardId,
    string $weekStart,
    string $weekEnd
): array {
    $items = [];

    foreach (admin_incident_store_all() as $row) {
        if (!admin_weekly_activity_matches_assignment(
            (string) ($row['site'] ?? ''),
            (string) ($row['head_guard_id'] ?? ''),
            $siteName,
            $headGuardId
        )) {
            continue;
        }
        if (!admin_weekly_activity_date_in_range((string) ($row['submitted_at'] ?? ''), $weekStart, $weekEnd)) {
            continue;
        }

        $summary = trim((string) ($row['summary'] ?? ''));
        if ($summary === '') {
            $summary = trim((string) ($row['incident_description'] ?? ''));
        }
        if (strlen($summary) > 220) {
            $summary = substr($summary, 0, 217) . '…';
        }

        $items[] = [
            'id' => (string) ($row['id'] ?? ''),
            'ref' => (string) ($row['ref'] ?? ''),
            'submitted_at' => substr((string) ($row['submitted_at'] ?? ''), 0, 10),
            'submitted_display' => (string) ($row['submitted_display'] ?? ''),
            'incident_type' => trim((string) ($row['incident_type'] ?? '')) ?: '—',
            'status_label' => (string) ($row['status_label'] ?? ''),
            'summary' => $summary !== '' ? $summary : '—',
            'site' => trim((string) ($row['site'] ?? '')) ?: '—',
        ];
    }

    usort(
        $items,
        static fn (array $a, array $b): int => strcmp((string) ($b['submitted_at'] ?? ''), (string) ($a['submitted_at'] ?? ''))
    );

    return $items;
}

/**
 * @return array{summary: string, highlights: string, daily_count: int, normal_count: int, event_count: int}
 */
function admin_weekly_activity_compile_from_daily(
    string $siteName,
    string $headGuardId,
    string $weekStart,
    string $weekEnd
): array {
    $normal = 0;
    $event = 0;
    $eventLines = [];

    foreach (admin_weekly_activity_collect_daily_for_range($siteName, $headGuardId, $weekStart, $weekEnd) as $row) {
        $mode = (string) ($row['activity_mode'] ?? GUARD_DAILY_ACTIVITY_MODE_NORMAL);
        if ($mode === GUARD_DAILY_ACTIVITY_MODE_EVENT) {
            ++$event;
            $line = trim((string) ($row['summary'] ?? ''));
            if ($line !== '' && $line !== '—') {
                $eventLines[] = $line;
            }
        } else {
            ++$normal;
        }
    }

    $total = $normal + $event;
    if ($total === 0) {
        return [
            'summary' => 'No daily activity reports found for this post and date range.',
            'highlights' => 'Add daily activity submissions for the selected range, then generate again.',
            'daily_count' => 0,
            'normal_count' => 0,
            'event_count' => 0,
        ];
    }

    $summary = sprintf(
        'Compiled from %d daily activity report%s (%d normal, %d with event/activity).',
        $total,
        $total === 1 ? '' : 's',
        $normal,
        $event
    );

    $highlights = $eventLines === []
        ? 'Routine period — no event/activity daily submissions in range.'
        : implode("\n", array_slice($eventLines, 0, 8));

    return [
        'summary' => $summary,
        'highlights' => $highlights,
        'daily_count' => $total,
        'normal_count' => $normal,
        'event_count' => $event,
    ];
}

/**
 * @param array<string, mixed> $input
 * @return array{
 *     ok: true,
 *     week_start: string,
 *     week_end: string,
 *     week_label: string,
 *     site_name: string,
 *     head_guard_id: string,
 *     head_guard_name: string,
 *     assignment_key: string
 * }|array{ok: false, error: string}
 */
function admin_weekly_activity_parse_war_generate_input(array $input): array
{
    $weekStart = trim((string) ($input['week_start'] ?? ''));
    $weekEnd = trim((string) ($input['week_end'] ?? ''));
    if ($weekStart === '' && $weekEnd === '') {
        $bounds = admin_weekly_activity_week_bounds(admin_weekly_activity_default_week_start());
    } elseif ($weekEnd === '') {
        $bounds = admin_weekly_activity_week_bounds($weekStart);
    } else {
        $bounds = admin_weekly_activity_resolve_week_range($weekStart, $weekEnd);
        if ($bounds === null) {
            return ['ok' => false, 'error' => 'Enter a valid date range (From must be on or before To).'];
        }
    }

    $assignmentKey = trim((string) ($input['assignment_key'] ?? ''));
    if ($assignmentKey === '') {
        return ['ok' => false, 'error' => 'Select a post and head guard.'];
    }

    $parts = explode("\x1e", $assignmentKey, 2);
    $siteName = trim($parts[0] ?? '');
    $headGuardId = trim($parts[1] ?? '');
    if ($siteName === '') {
        return ['ok' => false, 'error' => 'Invalid post selection.'];
    }

    $headGuardName = 'Head guard';
    foreach (admin_weekly_activity_generate_assignment_options() as $opt) {
        if ($opt['key'] === $assignmentKey) {
            $headGuardName = $opt['head_guard_name'];
            break;
        }
    }

    return [
        'ok' => true,
        'week_start' => $bounds['week_start'],
        'week_end' => $bounds['week_end'],
        'week_label' => $bounds['week_label'],
        'site_name' => $siteName,
        'head_guard_id' => $headGuardId,
        'head_guard_name' => $headGuardName,
        'assignment_key' => $assignmentKey,
    ];
}

function admin_weekly_activity_war_exists(
    string $weekStart,
    string $weekEnd,
    string $siteName,
    string $headGuardId
): bool {
    foreach (admin_weekly_activity_store_raw() as $row) {
        if ((string) ($row['status'] ?? '') === ADMIN_WEEKLY_ACTIVITY_STATUS_DRAFT) {
            continue;
        }
        if ((string) ($row['week_start'] ?? '') !== $weekStart
            || (string) ($row['week_end'] ?? '') !== $weekEnd) {
            continue;
        }
        if (strcasecmp((string) ($row['site_name'] ?? ''), $siteName) !== 0) {
            continue;
        }
        $rowHg = trim((string) ($row['head_guard_id'] ?? ''));
        if ($headGuardId !== '' && $rowHg !== '' && strcasecmp($rowHg, $headGuardId) !== 0) {
            continue;
        }

        return true;
    }

    return false;
}

/**
 * @param array<string, mixed> $input
 * @return array{ok: bool, error?: string, preview?: array<string, mixed>}
 */
function admin_weekly_activity_war_preview(array $input): array
{
    $parsed = admin_weekly_activity_parse_war_generate_input($input);
    if (!$parsed['ok']) {
        return ['ok' => false, 'error' => $parsed['error']];
    }

    $weekStart = $parsed['week_start'];
    $weekEnd = $parsed['week_end'];
    $siteName = $parsed['site_name'];
    $headGuardId = $parsed['head_guard_id'];

    $daily = admin_weekly_activity_collect_daily_for_range($siteName, $headGuardId, $weekStart, $weekEnd);
    $incidents = admin_weekly_activity_collect_incidents_for_range($siteName, $headGuardId, $weekStart, $weekEnd);
    $compiled = admin_weekly_activity_compile_from_daily($siteName, $headGuardId, $weekStart, $weekEnd);

    $incidentCount = count($incidents);

    return [
        'ok' => true,
        'preview' => [
            'week_label' => $parsed['week_label'],
            'week_start' => $weekStart,
            'week_end' => $weekEnd,
            'site_name' => $siteName,
            'head_guard_name' => $parsed['head_guard_name'],
            'highlights' => $compiled['highlights'],
            'daily_count' => $compiled['daily_count'],
            'normal_count' => $compiled['normal_count'],
            'event_count' => $compiled['event_count'],
            'incident_count' => $incidentCount,
            'daily_activity' => $daily,
            'incidents' => $incidents,
            'duplicate_war' => admin_weekly_activity_war_exists($weekStart, $weekEnd, $siteName, $headGuardId),
        ],
    ];
}

/**
 * @param array<string, mixed> $input
 * @return array{ok: bool, error?: string, report?: array<string, mixed>}
 */
function admin_weekly_activity_generate_war(array $input, string $actorId): array
{
    $parsed = admin_weekly_activity_parse_war_generate_input($input);
    if (!$parsed['ok']) {
        return ['ok' => false, 'error' => $parsed['error']];
    }

    $weekStart = $parsed['week_start'];
    $weekEnd = $parsed['week_end'];
    $siteName = $parsed['site_name'];
    $headGuardId = $parsed['head_guard_id'];
    $headGuardName = $parsed['head_guard_name'];

    if (admin_weekly_activity_war_exists($weekStart, $weekEnd, $siteName, $headGuardId)) {
        return [
            'ok' => false,
            'error' => 'A weekly summary (WAR) already exists for this post, head guard, and date range.',
        ];
    }

    $raw = admin_weekly_activity_store_raw();
    $compiled = admin_weekly_activity_compile_from_daily($siteName, $headGuardId, $weekStart, $weekEnd);
    $now = date('Y-m-d H:i:s');

    $report = [
        'id' => admin_weekly_activity_next_id($raw),
        'ref' => admin_weekly_activity_next_reference($raw),
        'week_label' => $parsed['week_label'],
        'week_start' => $weekStart,
        'week_end' => $weekEnd,
        'head_guard_id' => $headGuardId,
        'head_guard_name' => $headGuardName,
        'site_name' => $siteName,
        'summary' => $compiled['summary'],
        'highlights' => $compiled['highlights'],
        'status' => ADMIN_WEEKLY_ACTIVITY_STATUS_PENDING,
        'submitted_at' => $now,
        'updated_at' => $now,
        'history' => [
            [
                'at' => admin_weekly_activity_history_now(),
                'event' => 'WAR generated',
                'note' => sprintf(
                    'Generated by admin from %d daily activity report(s) (%d event/activity).',
                    $compiled['daily_count'],
                    $compiled['event_count']
                ),
                'actor' => $actorId,
            ],
        ],
    ];

    $raw[] = $report;
    admin_weekly_activity_store_save($raw);

    return ['ok' => true, 'report' => admin_weekly_activity_normalize($report)];
}

/**
 * @param list<array{key: string, head_guard_id: string, head_guard_name: string, site_name: string}> $options
 */
function admin_weekly_activity_generate_form_html(array $options, string $defaultWeekStart, string $defaultWeekEnd): string
{
    $html = '<form method="post" class="reports-war-form" id="weekly-generate-war-form">';
    $html .= csrf_field();
    $html .= '<input type="hidden" name="action" value="generate_war">';
    $html .= '<div class="reports-toolbar" role="group" aria-label="Generate weekly activity report">';
    $html .= '<div class="reports-toolbar__fields">';
    $html .= '<div class="form-field reports-field--date">';
    $html .= '<label for="war-week-start">From</label>';
    $html .= '<input type="date" name="week_start" id="war-week-start" value="' . e($defaultWeekStart) . '" required>';
    $html .= '</div>';
    $html .= '<div class="form-field reports-field--date">';
    $html .= '<label for="war-week-end">To</label>';
    $html .= '<input type="date" name="week_end" id="war-week-end" value="' . e($defaultWeekEnd) . '" required>';
    $html .= '</div>';
    $html .= '<div class="form-field reports-field--assignment">';
    $html .= '<label for="war-assignment">Post / head guard</label>';
    $html .= '<select name="assignment_key" id="war-assignment" required>';
    $html .= '<option value="">Select assignment…</option>';
    foreach ($options as $opt) {
        $label = $opt['site_name'] . ' — ' . $opt['head_guard_name'];
        $html .= '<option value="' . e($opt['key']) . '">' . e($label) . '</option>';
    }
    $html .= '</select>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="reports-toolbar-actions" role="toolbar" aria-label="Generate WAR actions">';
    $html .= '<div class="reports-button-set">';
    $html .= '<button type="button" class="reports-btn reports-btn--primary" id="weekly-war-preview-btn">';
    $html .= admin_btn_icon('file-lines');
    $html .= '<span class="reports-btn__text">Preview &amp; generate</span>';
    $html .= '</button>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</form>';

    return $html;
}

/**
 * Standalone “Generate WAR” panel (separate from the registry table card).
 *
 * @param list<array{key: string, head_guard_id: string, head_guard_name: string, site_name: string}> $options
 */
function admin_weekly_activity_generate_panel_html(array $options, string $defaultWeekStart, string $defaultWeekEnd): string
{
    if ($options === []) {
        return '<section class="reports-war-panel reports-war-panel--empty" aria-label="Generate WAR">'
            . '<p class="reports-war-panel__empty">Add daily activity or weekly seed data before generating a WAR.</p>'
            . '</section>';
    }

    $html = '<section class="reports-war-panel" aria-labelledby="weekly-war-panel-heading">';
    $html .= '<header class="reports-war-panel__header">';
    $html .= '<div class="reports-war-panel__intro">';
    $html .= '<h2 id="weekly-war-panel-heading" class="reports-war-panel__title">Generate WAR</h2>';
    $html .= '<p class="reports-war-panel__desc">Preview daily activity and incident reports for the selected range, then confirm to generate the WAR.</p>';
    $html .= '</div></header>';
    $html .= '<div class="reports-war-panel__body">';
    $html .= admin_weekly_activity_generate_form_html($options, $defaultWeekStart, $defaultWeekEnd);
    $html .= '</div>';
    $html .= admin_weekly_activity_war_preview_modal_html();
    $html .= '</section>';

    return $html;
}

function admin_weekly_activity_war_preview_modal_html(): string
{
    return '<div id="war-preview-overlay" class="reports-modal-overlay reports-war-preview-overlay" role="presentation" aria-hidden="true">'
        . '<div class="reports-modal reports-war-preview-modal" id="war-preview-modal" role="dialog" aria-modal="true" aria-labelledby="war-preview-title">'
        . '<header class="reports-modal__header">'
        . '<div class="reports-modal__identity">'
        . '<span class="reports-modal__eyebrow">Generate WAR</span>'
        . '<h2 id="war-preview-title" class="reports-modal__ref">Preview before generate</h2>'
        . '</div>'
        . '<button type="button" class="reports-modal__close" id="war-preview-close" aria-label="Close preview">&times;</button>'
        . '</header>'
        . '<div class="reports-modal__content">'
        . '<main class="reports-modal__body-scroll reports-war-preview__body" id="war-preview-body" aria-live="polite">'
        . '<p class="reports-war-preview__loading">Loading preview…</p>'
        . '</main>'
        . '<footer class="reports-modal__footer reports-war-preview__footer">'
        . '<div class="reports-modal-footer__button-set">'
        . '<div class="reports-button-set">'
        . '<button type="button" class="reports-btn reports-btn--secondary" id="war-preview-cancel">'
        . '<span class="reports-btn__text">Cancel</span></button>'
        . '<button type="button" class="reports-btn reports-btn--primary" id="war-preview-confirm" disabled>'
        . '<span class="reports-btn__text">Generate WAR</span></button>'
        . '</div></div></footer>'
        . '</div></div></div>';
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
        'data-sort-post="' . e(strtolower((string) ($report['site_name'] ?? ''))) . '"',
        'data-search="' . e(admin_weekly_activity_search_blob($report)) . '"',
        'data-detail="' . e($detailJson) . '"',
    ]);
}

/**
 * @param array<string, mixed> $report
 */
function admin_weekly_activity_modal_details_html(array $report): string
{
    $summaryValue = admin_incident_modal_handwriting_text((string) ($report['summary'] ?? ''));
    $highlightsValue = admin_incident_modal_handwriting_text((string) ($report['highlights'] ?? ''));

    $html = '<div class="reports-detail-sheet" role="group" aria-label="Weekly summary report">';
    $html .= '<section class="reports-detail-sheet__section" aria-label="Report identifiers">';
    $html .= '<div class="reports-detail-sheet__grid reports-detail-sheet__grid--activity-meta">';
    $html .= admin_incident_modal_sheet_field_html('Reference', (string) ($report['ref'] ?? ''));
    $html .= admin_incident_modal_sheet_field_html('Week', (string) ($report['week_label'] ?? ''));
    $html .= '</div></section>';
    $html .= '<section class="reports-detail-sheet__section" aria-label="Assignment">';
    $html .= '<div class="reports-detail-sheet__grid reports-detail-sheet__grid--people">';
    $html .= admin_incident_modal_sheet_field_html('Post', (string) ($report['site_name'] ?? ''));
    $html .= admin_incident_modal_sheet_field_html('Head guard', (string) ($report['head_guard_name'] ?? ''));
    $html .= '</div></section>';
    $html .= '<section class="reports-detail-sheet__section" aria-label="Weekly narrative">';
    $html .= '<div class="reports-detail-sheet__grid reports-detail-sheet__grid--activity-narrative">';
    $html .= '<div class="reports-detail-sheet__field reports-detail-sheet__field--description'
        . (trim((string) ($report['summary'] ?? '')) === '' ? ' is-empty' : '')
        . '"><span class="reports-detail-sheet__label">Summary</span>'
        . '<span class="reports-detail-sheet__value">' . $summaryValue . '</span></div>';
    $html .= '<div class="reports-detail-sheet__field reports-detail-sheet__field--description'
        . (trim((string) ($report['highlights'] ?? '')) === '' ? ' is-empty' : '')
        . '"><span class="reports-detail-sheet__label">Highlights</span>'
        . '<span class="reports-detail-sheet__value">' . $highlightsValue . '</span></div>';
    $html .= '</div></section>';
    $html .= '<section class="reports-detail-sheet__section" aria-label="Timestamps">';
    $html .= '<div class="reports-detail-sheet__grid reports-detail-sheet__grid--incident">';
    $html .= admin_incident_modal_sheet_field_html('Submitted', (string) ($report['submitted_display'] ?? ''));
    $html .= admin_incident_modal_sheet_field_html('Last updated', (string) ($report['updated_display'] ?? ''));
    $html .= '</div></section>';

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
    $kind = match ($action) {
        'delete' => 'delete',
        'print' => 'print',
        default => 'view',
    };

    return admin_incident_action_icon($kind);
}

function admin_weekly_activity_delete(string $id): ?array
{
    $id = trim($id);
    if ($id === '') {
        return null;
    }

    $raw = admin_weekly_activity_store_raw();
    $deleted = null;
    $remaining = [];

    foreach ($raw as $row) {
        if ((string) ($row['id'] ?? '') === $id) {
            $deleted = $row;
            continue;
        }
        $remaining[] = $row;
    }

    if ($deleted === null) {
        return null;
    }

    admin_weekly_activity_store_save($remaining);

    return admin_weekly_activity_normalize($deleted);
}
