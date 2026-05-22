<?php
declare(strict_types=1);

/** Active operations review — default for new head-guard submissions. */
const ADMIN_INCIDENT_STATUS_ONGOING = 'ongoing';
/** Paused pending external input, evidence, or client action. */
const ADMIN_INCIDENT_STATUS_ON_HOLD = 'on_hold';
/** Closed — incident resolved and archived. */
const ADMIN_INCIDENT_STATUS_ACCOMPLISHED = 'accomplished';
/** Closed — rejected, withdrawn, duplicate, or cancelled. */
const ADMIN_INCIDENT_STATUS_DENIED = 'denied';

/**
 * Canonical incident workflow statuses (slug => definition).
 *
 * @return array<string, array{label:string, tab:string, kpi:string, description:string, closed:bool}>
 */
function admin_incident_status_definitions(): array
{
    return [
        ADMIN_INCIDENT_STATUS_ONGOING => [
            'label' => 'Open',
            'tab' => 'Open',
            'kpi' => 'Open',
            'description' => 'Open case — under investigation; admin or head guard is reviewing and following up.',
            'closed' => false,
        ],
        ADMIN_INCIDENT_STATUS_ON_HOLD => [
            'label' => 'On hold',
            'tab' => 'On hold',
            'kpi' => 'On hold',
            'description' => 'Case paused — waiting for evidence, client input, guard statement, or NTE.',
            'closed' => false,
        ],
        ADMIN_INCIDENT_STATUS_ACCOMPLISHED => [
            'label' => 'Closed',
            'tab' => 'Closed',
            'kpi' => 'Closed',
            'description' => 'Case closed — incident resolved; discipline or client sign-off recorded.',
            'closed' => true,
        ],
        ADMIN_INCIDENT_STATUS_DENIED => [
            'label' => 'Not accepted',
            'tab' => 'Not accepted',
            'kpi' => 'Not accepted',
            'description' => 'Case closed without action — duplicate, withdrawn, or invalid filing.',
            'closed' => true,
        ],
    ];
}

/** @return list<string> */
function admin_incident_status_slugs(): array
{
    return array_keys(admin_incident_status_definitions());
}

/** @return array<string, string> */
function admin_incident_status_options(): array
{
    $options = [];
    foreach (admin_incident_status_definitions() as $slug => $def) {
        $options[$slug] = $def['label'];
    }

    return $options;
}

function admin_incident_status_label(string $status): string
{
    $defs = admin_incident_status_definitions();

    return $defs[$status]['label'] ?? $defs[ADMIN_INCIDENT_STATUS_ONGOING]['label'];
}

function admin_incident_status_description(string $status): string
{
    $defs = admin_incident_status_definitions();

    return $defs[$status]['description'] ?? $defs[ADMIN_INCIDENT_STATUS_ONGOING]['description'];
}

function admin_incident_status_is_valid(string $status): bool
{
    return array_key_exists($status, admin_incident_status_definitions());
}

/** @return list<string> */
function admin_incident_status_closed_slugs(): array
{
    return [ADMIN_INCIDENT_STATUS_ACCOMPLISHED, ADMIN_INCIDENT_STATUS_DENIED];
}

/**
 * Shared vocabulary for guides, workflows, and ops copy (derived from registry labels).
 *
 * @return array<string, string>
 */
function admin_incident_status_workflow_lexicon(): array
{
    $ongoing = admin_incident_status_label(ADMIN_INCIDENT_STATUS_ONGOING);
    $onHold = admin_incident_status_label(ADMIN_INCIDENT_STATUS_ON_HOLD);
    $closed = admin_incident_status_label(ADMIN_INCIDENT_STATUS_ACCOMPLISHED);
    $notAccepted = admin_incident_status_label(ADMIN_INCIDENT_STATUS_DENIED);

    return [
        'ongoing' => $ongoing,
        'on_hold' => $onHold,
        'closed' => $closed,
        'not_accepted' => $notAccepted,
        'open' => 'Open',
        'open_pair' => $ongoing . ' / ' . $onHold,
        'closed_pair' => $closed . ' / ' . $notAccepted,
        'registry_all' => implode(' · ', [$ongoing, $onHold, $closed, $notAccepted]),
        'mark_ongoing' => 'Mark as ' . $ongoing,
        'mark_on_hold' => 'Mark as ' . $onHold,
        'mark_closed' => 'Mark as ' . $closed,
        'mark_not_accepted' => 'Mark as ' . $notAccepted,
        'registry_closed' => 'Registry ' . $closed,
        'registry_not_accepted' => 'Registry ' . $notAccepted,
        'edit_hint' => $ongoing . ' or ' . $onHold . ' = open case · '
            . $closed . ' or Not accepted = closed',
    ];
}

function admin_incident_status_edit_hint(): string
{
    return admin_incident_status_workflow_lexicon()['edit_hint'];
}

/**
 * Replace legacy status wording in guide/workflow narrative strings.
 */
function admin_incident_apply_status_terminology(string $text): string
{
    if ($text === '') {
        return $text;
    }

    static $replacements = null;
    if ($replacements === null) {
        $t = admin_incident_status_workflow_lexicon();
        $ongoing = $t['ongoing'];
        $onHold = $t['on_hold'];
        $closed = $t['closed'];
        $notAccepted = $t['not_accepted'];

        $replacements = [
            'Ongoing / investigation' => $ongoing,
            'Under investigation' => $ongoing,
            'Case closed' => $closed,
            'Closed — not accepted' => $notAccepted,
            'Registry Accomplished' => $t['registry_closed'],
            'Registry Denied' => $t['registry_not_accepted'],
            'Mark as Accomplished' => $t['mark_closed'],
            'Mark as Denied' => $t['mark_not_accepted'],
            'Mark as Ongoing / investigation' => $t['mark_ongoing'],
            'Mark Ongoing / investigation' => $t['mark_ongoing'],
            'Close as Accomplished' => 'Close as ' . $closed,
            'Close Accomplished' => 'Close ' . $closed,
            'Case Accomplished' => $closed,
            'then Accomplished' => 'then ' . $closed,
            'Accomplished when' => $closed . ' when',
            'Accomplished with' => $closed . ' with',
            'Accomplished or' => $closed . ' or',
            'Accomplished only' => $closed . ' only',
            'Accomplished after' => $closed . ' after',
            'Accomplished;' => $closed . ';',
            'Accomplished,' => $closed . ',',
            'Accomplished.' => $closed . '.',
            'Accomplished' => $closed,
            'marks Denied' => 'marks ' . $notAccepted,
            'Mark as Denied' => $t['mark_not_accepted'],
            'Marked denied' => 'Marked ' . strtolower($notAccepted),
            ' / Denied' => ' / ' . $notAccepted,
            'or Denied' => 'or ' . $notAccepted,
            'Denied' => $notAccepted,
            'Deny duplicate' => $notAccepted . ' duplicate',
            'Deny or' => $notAccepted . ' or',
            'Mark Ongoing' => $t['mark_ongoing'],
            'set status Ongoing' => 'set status to ' . $ongoing,
            'status Ongoing' => 'status ' . $ongoing,
            'ticket Ongoing' => 'ticket ' . $ongoing,
            'Keep Ongoing' => 'Keep ' . $ongoing,
            'remain Ongoing' => 'remain ' . $ongoing,
            'not Ongoing' => 'not ' . $ongoing,
            ' / Ongoing' => ' / ' . $ongoing,
            'Ongoing +' => $ongoing . ' +',
            'Ongoing (' => $ongoing . ' (',
            'Ongoing until' => $ongoing . ' until',
            'Ongoing while' => $ongoing . ' while',
            'Ongoing pending' => $ongoing . ' pending',
            'Ongoing or' => $ongoing . ' or',
            'Ongoing' => $ongoing,
            'On Hold' => $onHold,
            'not Accomplished' => 'not ' . $closed,
            'accomplish when' => 'close as ' . $closed . ' when',
            'accomplish or deny' => 'close as ' . $closed . ' or mark ' . $notAccepted,
        ];

        uksort(
            $replacements,
            static fn (string $a, string $b): int => strlen($b) <=> strlen($a)
        );
    }

    return str_replace(array_keys($replacements), array_values($replacements), $text);
}

/**
 * @param list<list<string>> $rows
 * @return list<list<string>>
 */
function admin_incident_apply_status_terminology_rows(array $rows): array
{
    return array_map(
        static fn (array $row): array => array_map(
            static fn (string $cell): string => admin_incident_apply_status_terminology($cell),
            $row
        ),
        $rows
    );
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function admin_incident_apply_status_terminology_to_type_row(array $row): array
{
    $stringFields = [
        'initial_status',
        'system_action',
        'remarks',
        'filing_basis',
        'filing_trigger',
        'response_sla',
        'responsible',
    ];

    foreach ($stringFields as $field) {
        if (isset($row[$field]) && is_string($row[$field])) {
            $row[$field] = admin_incident_apply_status_terminology((string) $row[$field]);
        }
    }

    if (isset($row['initial_status']) && in_array($row['initial_status'], ['Ongoing', 'ongoing'], true)) {
        $row['initial_status'] = admin_incident_status_label(ADMIN_INCIDENT_STATUS_ONGOING);
    }

    if (isset($row['steps']) && is_array($row['steps'])) {
        $row['steps'] = array_map(
            static fn ($step): string => admin_incident_apply_status_terminology((string) $step),
            $row['steps']
        );
    }

    return $row;
}

/**
 * @param list<array<string, mixed>> $sections
 * @return list<array<string, mixed>>
 */
function admin_incident_apply_status_terminology_sections(array $sections): array
{
    return array_map(
        static function (array $section): array {
            if (isset($section['rows']) && is_array($section['rows'])) {
                $section['rows'] = admin_incident_apply_status_terminology_rows($section['rows']);
            }
            if (isset($section['intro']) && is_string($section['intro'])) {
                $section['intro'] = admin_incident_apply_status_terminology($section['intro']);
            }

            return $section;
        },
        $sections
    );
}
