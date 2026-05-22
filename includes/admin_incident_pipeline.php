<?php
declare(strict_types=1);

require_once __DIR__ . '/admin_incident_status.php';
require_once __DIR__ . '/admin_incident_categories.php';
require_once __DIR__ . '/admin_incident_guidelines.php';

/** @param array<string, mixed> $entry */
function admin_incident_pipeline_entry_source(array $entry): string
{
    $source = strtolower(trim((string) ($entry['source'] ?? '')));
    if (in_array($source, ['head_guard', 'admin', 'system'], true)) {
        return $source;
    }

    $event = strtolower(trim((string) ($entry['event'] ?? '')));
    if (str_contains($event, 'submitted by head guard') || str_contains($event, 'report filed')) {
        return 'head_guard';
    }
    if (str_contains($event, 'classified') || str_contains($event, 'assigned to operations')) {
        return 'system';
    }

    return 'admin';
}

function admin_incident_pipeline_status_slug_from_event(string $event): ?string
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
 * Keyword hints per incident type (lowercase) for OCR / description classification.
 *
 * @return array<string, list<string>>
 */
function admin_incident_type_keyword_map(): array
{
    return [
        'policy breach — unauthorized access' => ['unauthorized', 'access', 'badge', 'entry', 'breach', 'trespass', 'roster'],
        'client site — trespassing' => ['trespass', 'loiter', 'perimeter', 'client', 'mall', 'loading'],
        'theft / loss prevention' => ['theft', 'shoplift', 'steal', 'loss prevention', 'lp '],
        'equipment failure — radio network' => ['radio', 'repeater', 'comms', 'network', 'outage', 'channel'],
        'medical emergency' => ['medical', 'injury', 'ems', 'ambulance', 'first aid', 'sprain', 'illness'],
        'workplace injury — minor' => ['injury', 'sprain', 'hurt', 'accident', 'training', 'drill'],
        'vandalism' => ['graffiti', 'vandal', 'damage', 'fence', 'broken window'],
        'fire alarm activation' => ['fire alarm', 'alarm', 'evacuation', 'smoke'],
        'attendance / shift dispute' => ['attendance', 'shift', 'roster', 'timekeeping', 'late', 'absent'],
    ];
}

/**
 * @return array{
 *   incident_type: string,
 *   category: string,
 *   category_label: string,
 *   severity: string,
 *   response_sla: string,
 *   initial_status: string
 * }
 */
function admin_incident_classify_from_content(string $description, string $action, string $guardName = ''): array
{
    $text = strtolower(trim($description . ' ' . $action . ' ' . $guardName));
    $best = null;
    $bestScore = 0;

    foreach (admin_incident_types_reference() as $row) {
        $typeKey = strtolower((string) ($row['incident_type'] ?? ''));
        $score = 0;
        foreach (admin_incident_type_keyword_map()[$typeKey] ?? [] as $needle) {
            if ($needle !== '' && str_contains($text, $needle)) {
                $score += 2;
            }
        }
        $typeWords = preg_split('/\s*[—–-]\s*/u', $typeKey) ?: [];
        foreach ($typeWords as $fragment) {
            $fragment = trim($fragment);
            if ($fragment !== '' && strlen($fragment) > 4 && str_contains($text, $fragment)) {
                $score += 3;
            }
        }
        if ($score > $bestScore) {
            $bestScore = $score;
            $best = $row;
        }
    }

    if ($best === null) {
        $best = admin_incident_types_reference()[0] ?? [
            'incident_type' => 'Post incident report',
            'category' => ADMIN_INCIDENT_CATEGORY_PER_POST,
            'category_label' => 'On post',
            'severity' => 'Medium',
            'response_sla' => 'Same business day',
            'initial_status' => 'Ongoing',
        ];
    }

    $severity = trim((string) ($best['severity'] ?? ''));
    if (!in_array($severity, ['High', 'Medium', 'Low'], true)) {
        $severity = admin_incident_pipeline_infer_severity($description . ' ' . $action);
    }

    $category = admin_incident_category_normalize((string) ($best['category'] ?? ADMIN_INCIDENT_CATEGORY_PER_POST));

    return [
        'incident_type' => (string) ($best['incident_type'] ?? 'Post incident report'),
        'category' => $category,
        'category_label' => admin_incident_category_label($category),
        'severity' => $severity,
        'response_sla' => (string) ($best['response_sla'] ?? 'Same business day'),
        'initial_status' => ADMIN_INCIDENT_STATUS_ONGOING,
    ];
}

function admin_incident_pipeline_infer_severity(string $text): string
{
    $upper = strtoupper($text);
    $high = ['WEAPON', 'GUN', 'KNIFE', 'ASSAULT', 'STABBING', 'SHOOTING', 'FIRE', 'EXPLOSION', 'BOMB', 'DEATH', 'CRITICAL'];
    foreach ($high as $needle) {
        if (str_contains($upper, $needle)) {
            return 'High';
        }
    }
    $low = ['MINOR', 'NO INJURY', 'VERBAL', 'WARNING', 'ROUTINE'];
    foreach ($low as $needle) {
        if (str_contains($upper, $needle)) {
            return 'Low';
        }
    }

    return 'Medium';
}

function admin_incident_build_list_summary(array $classification, string $guardName): string
{
    $parts = [];
    if (trim($guardName) !== '') {
        $parts[] = 'Guard under head guard: ' . trim($guardName);
    }
    $parts[] = 'Classified: ' . ($classification['incident_type'] ?? 'Incident');
    $parts[] = ($classification['category_label'] ?? 'On post') . ' · ' . ($classification['severity'] ?? 'Medium');

    return implode(' · ', $parts);
}

/** Plain incident description for admin modal (not the AI list summary). */
function admin_incident_modal_description_text(array $report): string
{
    $description = trim((string) ($report['incident_description'] ?? ''));
    if ($description !== '') {
        return $description;
    }

    $history = is_array($report['history'] ?? null) ? $report['history'] : [];
    foreach ($history as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        if (admin_incident_pipeline_entry_source($entry) === 'head_guard') {
            $fromHistory = trim((string) ($entry['description'] ?? ''));
            if ($fromHistory !== '') {
                return $fromHistory;
            }
        }
    }

    return trim((string) ($report['summary'] ?? ''));
}

function admin_incident_person_from_report(array $report): string
{
    $person = trim((string) ($report['person_involved'] ?? $report['guard_involved'] ?? ''));
    if ($person !== '') {
        return $person;
    }

    $history = is_array($report['history'] ?? null) ? $report['history'] : [];
    foreach ($history as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        $name = trim((string) ($entry['guard_name'] ?? ''));
        if ($name !== '') {
            return $name;
        }
    }

    return '';
}

/**
 * Operation flow seeded on head-guard submit (system builds steps 2+).
 *
 * @param array<string, mixed> $fields
 * @param array<string, mixed> $classification
 * @return list<array<string, mixed>>
 */
function admin_incident_initial_operation_flow(
    array $fields,
    array $classification,
    string $headGuardName,
    string $guardSubjectName,
    string $locationLabel,
    string $at
): array {
    $description = trim((string) ($fields['incident_description'] ?? ''));
    $immediateAction = trim((string) ($fields['action_taken'] ?? ''));

    $history = [
        [
            'at' => $at,
            'source' => 'head_guard',
            'kind' => 'field_submission',
            'event' => 'Report filed',
            'description' => $description,
            'immediate_action' => $immediateAction,
            'guard_name' => $guardSubjectName,
            'note' => $locationLabel !== '' ? 'Location: ' . $locationLabel : '',
        ],
        [
            'at' => $at,
            'source' => 'system',
            'kind' => 'classification',
            'event' => 'Classified',
            'note' => sprintf(
                'Type: %s · %s · Severity %s',
                (string) ($classification['incident_type'] ?? ''),
                (string) ($classification['category_label'] ?? ''),
                (string) ($classification['severity'] ?? '')
            ),
        ],
        [
            'at' => $at,
            'source' => 'system',
            'kind' => 'routing',
            'event' => 'Assigned to operations',
            'note' => 'Stage 2 — Admin review. ' . (string) ($classification['response_sla'] ?? 'Response per SLA'),
        ],
    ];

    if ($headGuardName !== '') {
        $history[0]['filed_by'] = $headGuardName;
    }

    return $history;
}

/** @param array<string, mixed> $entry */
function admin_incident_history_display_timestamp(array $entry): string
{
    $edited = trim((string) ($entry['edited_at'] ?? ''));
    if ($edited !== '') {
        return $edited;
    }

    return trim((string) ($entry['at'] ?? ''));
}

/**
 * Suggest registry status from admin note keywords (guard guide thresholds).
 */
function admin_incident_suggest_status_from_note(string $note): ?string
{
    $note = strtolower(trim($note));
    if ($note === '') {
        return null;
    }

    if (preg_match('/\b(duplicate|withdrawn|not accepted|invalid filing|rejected filing)\b/u', $note)) {
        return ADMIN_INCIDENT_STATUS_DENIED;
    }
    if (preg_match('/\b(case closed|resolved|closure memo|archived|accomplished|sign-off|signed off)\b/u', $note)) {
        return ADMIN_INCIDENT_STATUS_ACCOMPLISHED;
    }
    if (preg_match('/\b(on hold|awaiting|pending cctv|pending client|need more proof|paused)\b/u', $note)) {
        return ADMIN_INCIDENT_STATUS_ON_HOLD;
    }

    return null;
}

/**
 * Reconcile report status from latest admin registry row in history.
 *
 * @param array<string, mixed> $report
 */
function admin_incident_reconcile_status(array $report): string
{
    $history = is_array($report['history'] ?? null) ? $report['history'] : [];
    foreach (array_reverse($history) as $entry) {
        if (!is_array($entry) || admin_incident_pipeline_entry_source($entry) !== 'admin') {
            continue;
        }
        $slug = admin_incident_pipeline_status_slug_from_event((string) ($entry['event'] ?? ''));
        if ($slug !== null) {
            return $slug;
        }
    }

    return (string) ($report['status'] ?? ADMIN_INCIDENT_STATUS_ONGOING);
}

/**
 * @param array<string, mixed> $report
 * @param array<string, mixed> $input
 */
/**
 * Admin operations decision when reviewing a head-guard filing (slug => meta).
 *
 * @return array<string, array{event: string, status: string, kind: string, requires_note: bool}>
 */
function admin_incident_operations_decision_definitions(): array
{
    return [
        'accept' => [
            'event' => 'Report accepted',
            'status' => ADMIN_INCIDENT_STATUS_ONGOING,
            'kind' => 'decision',
            'requires_note' => false,
        ],
        'on_hold' => [
            'event' => 'Report on hold',
            'status' => ADMIN_INCIDENT_STATUS_ON_HOLD,
            'kind' => 'decision',
            'requires_note' => true,
        ],
        'denied' => [
            'event' => 'Report not accepted',
            'status' => ADMIN_INCIDENT_STATUS_DENIED,
            'kind' => 'decision',
            'requires_note' => true,
        ],
    ];
}

/** @return array{event: string, status: string, kind: string, requires_note: bool}|null */
function admin_incident_operations_decision_meta(string $decision): ?array
{
    $definitions = admin_incident_operations_decision_definitions();

    return $definitions[$decision] ?? null;
}

/**
 * Append the authoritative admin decision row (accept / on hold / not accepted).
 *
 * @return array<string, mixed>|null null when validation fails (e.g. missing decision notes)
 */
function admin_incident_apply_operations_decision(
    array $report,
    string $decision,
    string $note,
    string $actorId
): ?array {
    $meta = admin_incident_operations_decision_meta($decision);
    if ($meta === null) {
        return $report;
    }

    $note = trim($note);
    if (!empty($meta['requires_note']) && $note === '') {
        return null;
    }

    if ($note === '' && $decision === 'accept') {
        $note = 'Accepted for operations review — case continues.';
    }

    $report = admin_incident_append_history($report, $meta['event'], $note, $actorId, [
        'source' => 'admin',
        'kind' => $meta['kind'],
    ]);
    $report['status'] = $meta['status'];

    return $report;
}

function admin_incident_apply_progression_save(array $report, array $input, string $actorId): array
{
    $opsNote = trim((string) ($input['ops_note'] ?? ''));
    $opsDecision = trim((string) ($input['ops_decision'] ?? ''));

    if ($opsDecision !== '') {
        $withDecision = admin_incident_apply_operations_decision($report, $opsDecision, $opsNote, $actorId);
        if ($withDecision !== null) {
            return admin_incident_touch_updated($withDecision);
        }
    }

    $status = (string) ($input['status'] ?? $report['status'] ?? ADMIN_INCIDENT_STATUS_ONGOING);
    if (!admin_incident_status_is_valid($status)) {
        $status = (string) ($report['status'] ?? ADMIN_INCIDENT_STATUS_ONGOING);
    }

    $suggested = admin_incident_suggest_status_from_note($opsNote);
    if ($suggested !== null && $opsNote !== '') {
        $status = $suggested;
    }

    $report['status'] = $status;

    return $report;
}
