<?php
declare(strict_types=1);

require_once __DIR__ . '/admin_incident_status.php';
require_once __DIR__ . '/admin_incident_violation_workflow.php';
require_once __DIR__ . '/admin_incident_outside_post_catalog.php';

/**
 * Incident report types — filing basis and monitoring fields (aligned with registry types).
 *
 * @return list<array{
 *   incident_type: string,
 *   category: string,
 *   category_label: string,
 *   severity: string,
 *   filing_basis: string,
 *   filing_trigger: string,
 *   initial_status: string,
 *   response_sla: string,
 *   responsible: string,
 *   system_action: string,
 *   remarks: string,
 *   steps: list<string>
 * }>
 */
function admin_incident_types_reference(): array
{
    $catalog = [
        [
            'incident_type' => 'Policy breach — unauthorized access',
            'category' => 'per_post',
            'category_label' => 'Per post',
            'severity' => 'High',
            'filing_basis' => 'Security protocol violation',
            'filing_trigger' => 'Unauthorized entry attempt, badge misuse, or access log anomaly',
            'initial_status' => 'Ongoing',
            'response_sla' => 'Within 1 hour — preserve evidence same shift',
            'responsible' => 'Head guard → Operations',
            'system_action' => 'Route to admin queue; flag High severity',
            'remarks' => 'No closure without CCTV/access logs and signed ops memo',
            'steps' => [
                'Preserve CCTV, access logs, and witness statements within 1 hour.',
                'Notify operations manager; assign investigator same business day.',
                'Interview involved guard(s); verbal warning if protocol lapse confirmed.',
                'Written reprimand + mandatory re-training if unauthorized access substantiated.',
                'Close as Accomplished only with signed ops memo and archived evidence.',
            ],
        ],
        [
            'incident_type' => 'Client site — trespassing',
            'category' => 'outside_post',
            'category_label' => 'Outside post',
            'severity' => 'Medium',
            'filing_basis' => 'Perimeter / access breach at client site',
            'filing_trigger' => 'Trespass, loitering, or unauthorized persons on client property',
            'initial_status' => 'Ongoing',
            'response_sla' => 'Immediate report; client coordination same day',
            'responsible' => 'Head guard → Client security',
            'system_action' => 'Mark Ongoing; request client incident reference',
            'remarks' => 'On hold until client statement; sanction only if negligence proven',
            'steps' => [
                'Secure perimeter; coordinate with client security and document persons involved.',
                'File initial report; set status Ongoing while patrol statements are collected.',
                'Request client incident form / blotter reference before escalation.',
                'No guard sanction unless negligence (e.g. open gate); then coaching memo first.',
                'On hold until client statement; accomplish or deny per client instruction.',
            ],
        ],
        [
            'incident_type' => 'Theft / loss prevention',
            'category' => 'outside_post',
            'category_label' => 'Outside post',
            'severity' => 'High',
            'filing_basis' => 'Loss prevention / theft event',
            'filing_trigger' => 'Theft, attempted theft, or LP intervention per client policy',
            'initial_status' => 'Ongoing',
            'response_sla' => 'Immediate; evidence preserved before end of shift',
            'responsible' => 'Head guard → Client LP / Operations',
            'system_action' => 'High severity; attach witness and CCTV references',
            'remarks' => 'Detention only per client policy; document guard SOP compliance',
            'steps' => [
                'Detain only per client policy; otherwise observe and report immediately.',
                'Preserve evidence (CCTV, receipts, witness names); notify ops and client LP.',
                'Document guard actions against use-of-force and detention SOP.',
                'Commendation or coaching based on adherence to client LP protocol.',
                'Accomplished when suspect turnover / client closure memo is on file.',
            ],
        ],
        [
            'incident_type' => 'Equipment failure — radio network',
            'category' => 'per_post',
            'category_label' => 'Per post',
            'severity' => 'Low',
            'filing_basis' => 'Operational equipment / comms failure',
            'filing_trigger' => 'Radio, repeater, or dispatch channel outage affecting post coverage',
            'initial_status' => 'Ongoing',
            'response_sla' => 'Log within 2 hours; IT ticket same business day',
            'responsible' => 'Head guard → IT / Comms',
            'system_action' => 'No personnel sanction for equipment fault',
            'remarks' => 'Accomplished when service restored and ticket closed',
            'steps' => [
                'Log outage time, affected posts, and backup channel activated.',
                'Notify IT / comms lead; no personnel sanction for equipment fault.',
                'Head guard confirms roster checked-in via alternate comms.',
                'Post-incident checklist within 24 hours; training refresh if repeated at same post.',
                'Accomplished when service restored and IT ticket closed.',
            ],
        ],
        [
            'incident_type' => 'Medical emergency',
            'category' => 'outside_post',
            'category_label' => 'Outside post',
            'severity' => 'High',
            'filing_basis' => 'Medical / EMS event on post',
            'filing_trigger' => 'Injury, illness, or EMS response required at assigned site',
            'initial_status' => 'Ongoing',
            'response_sla' => 'Report immediately after EMS notified — do not delay for investigation',
            'responsible' => 'Head guard → Operations / Client',
            'system_action' => 'High severity; timeline of guard response required',
            'remarks' => 'Deny duplicate filings; no sanction unless EMS/post abandonment',
            'steps' => [
                'First aid / EMS per post medical plan; do not delay report for investigation.',
                'Notify ops, client contact, and document timeline of guard response.',
                'No disciplinary sanction unless failure to call EMS or abandon post.',
                'Coaching or reprimand only after ops review of SOP compliance.',
                'Deny duplicate filings; accomplish when client/EMS report number attached.',
            ],
        ],
        [
            'incident_type' => 'Workplace injury — minor',
            'category' => 'per_post',
            'category_label' => 'Per post',
            'severity' => 'Medium',
            'filing_basis' => 'Occupational injury (minor)',
            'filing_trigger' => 'On-duty injury requiring first aid, clinic referral, or HR documentation',
            'initial_status' => 'Ongoing',
            'response_sla' => 'Same shift narrative; HR forms within 24 hours',
            'responsible' => 'Head guard → HR / Operations',
            'system_action' => 'Ongoing until clinic/HR scans attached',
            'remarks' => 'Written warning only for willful safety violation',
            'steps' => [
                'First aid on site; refer to clinic if needed; preserve incident scene photos.',
                'Notify HR and operations; guard completes injury narrative same shift.',
                'Supervisor review for PPE / drill compliance; coaching if lapse found.',
                'Written warning only if willful safety rule violation is documented.',
                'Ongoing until clinic/HR forms scanned; then Accomplished.',
            ],
        ],
        [
            'incident_type' => 'Vandalism',
            'category' => 'outside_post',
            'category_label' => 'Outside post',
            'severity' => 'Medium',
            'filing_basis' => 'Property damage / vandalism',
            'filing_trigger' => 'Graffiti, breakage, or damage on client perimeter or facility',
            'initial_status' => 'Ongoing',
            'response_sla' => 'Photos and patrol log segment within same shift',
            'responsible' => 'Head guard → Client security',
            'system_action' => 'On hold for client blotter when required',
            'remarks' => 'Reprimand only for proven neglect of perimeter checks',
            'steps' => [
                'Photograph damage; notify client security and police if client requires.',
                'Preserve patrol log segment covering the window of occurrence.',
                'Assess guard visibility / lighting rounds; coaching if patrol gap proven.',
                'Written reprimand only for proven neglect of scheduled perimeter check.',
                'On hold for client blotter; accomplish when repair PO or case number filed.',
            ],
        ],
        [
            'incident_type' => 'Attendance / shift dispute',
            'category' => 'per_post',
            'category_label' => 'Per post',
            'severity' => 'Low',
            'filing_basis' => 'Attendance monitoring & evaluation (linked)',
            'filing_trigger' => 'Present/Late/Absent dispute, missing time-in/out, or roster conflict',
            'initial_status' => 'Ongoing',
            'response_sla' => 'Pull timekeeping records before any personnel action',
            'responsible' => 'Head guard → HR / Operations',
            'system_action' => 'Cross-check attendance logs; may link to NTE workflow',
            'remarks' => 'Maps to attendance status: Present (1.00), Late (0.50), Absent (0.00), Pending (N/A)',
            'steps' => [
                'Pull timekeeping / dispatch records before any personnel action.',
                'Interview head guard and affected guard separately; document statements.',
                'Verbal clarification if scheduling error; no sanction.',
                'Written warning for unexcused absence only after HR confirms roster breach.',
                'Deny or withdraw if submitter retracts; ops note required.',
            ],
        ],
        [
            'incident_type' => 'Fire alarm activation',
            'category' => 'outside_post',
            'category_label' => 'Outside post',
            'severity' => 'Medium',
            'filing_basis' => 'Fire safety / evacuation event',
            'filing_trigger' => 'Alarm activation, evacuation, or fire panel event at client site',
            'initial_status' => 'Ongoing',
            'response_sla' => 'Report after all-clear; evacuation checklist same day',
            'responsible' => 'Head guard → Client engineering',
            'system_action' => 'Document cause when known; no sanction for false alarm without neglect',
            'remarks' => 'Accomplished after client sign-off on incident summary',
            'steps' => [
                'Execute client evacuation SOP; account for posts and visitors per checklist.',
                'Notify ops and client engineering; log alarm cause when known.',
                'No guard sanction for false alarm unless failure to respond or abandon post.',
                'Debrief and refresher on fire panel basics if client requests.',
                'Accomplished after all-clear and client sign-off on incident summary.',
            ],
        ],
        [
            'incident_type' => 'Traffic / parking incident',
            'category' => 'outside_post',
            'category_label' => 'Outside post',
            'severity' => 'Low',
            'filing_basis' => 'Traffic / parking mediation',
            'filing_trigger' => 'Collision, dispute, or parking violation on assigned post',
            'initial_status' => 'Ongoing',
            'response_sla' => 'Scene secured and details logged same shift',
            'responsible' => 'Head guard → Operations',
            'system_action' => 'Ongoing while insurance/legal review open',
            'remarks' => 'Coaching if paperwork incomplete; no fault admission by guard',
            'steps' => [
                'Secure scene; exchange details only as client policy allows; call police if injury.',
                'Document vehicles, witnesses, and guard mediation steps without admitting fault.',
                'No sanction unless guard instigated confrontation or left post unattended.',
                'Coaching on parking SOP and incident form completion if paperwork incomplete.',
                'Ongoing while insurance/legal reviews; accomplish when client closure received.',
            ],
        ],
    ];

    $types = array_merge(
        $catalog,
        admin_incident_per_post_violation_types(),
        admin_incident_outside_post_extended_types()
    );

    foreach ($types as $i => $row) {
        $slug = admin_incident_category_normalize((string) ($row['category'] ?? ADMIN_INCIDENT_CATEGORY_PER_POST));
        $types[$i]['category'] = $slug;
        $types[$i]['category_label'] = admin_incident_category_label($slug);
        $types[$i] = admin_incident_apply_status_terminology_to_type_row($types[$i]);
    }

    return $types;
}

/**
 * @return list<array{
 *   incident_type: string,
 *   category: string,
 *   category_label: string,
 *   severity: string,
 *   steps: list<string>
 * }>
 */
function admin_incident_sanctions_reference(): array
{
    return array_map(
        static fn (array $row): array => [
            'incident_type' => (string) $row['incident_type'],
            'category' => (string) $row['category'],
            'category_label' => (string) $row['category_label'],
            'severity' => (string) $row['severity'],
            'steps' => $row['steps'],
        ],
        admin_incident_types_reference()
    );
}

/**
 * Section title for the unified workflow table (grouped by report scope).
 */
function admin_incident_guard_workflow_group_label(string $categorySlug): string
{
    return match ($categorySlug) {
        ADMIN_INCIDENT_CATEGORY_OUTSIDE_POST => 'External',
        ADMIN_INCIDENT_CATEGORY_PER_POST => 'On post',
        default => admin_incident_category_label($categorySlug),
    };
}

/**
 * Per-incident four-step guard → admin workflow (spreadsheet-style).
 *
 * @return array<string, list<string>>
 */
function admin_incident_guard_workflow_step_overrides(): array
{
    return [
        'theft / loss prevention' => [
            'Guard files report',
            'Admin reviews report',
            'Request CCTV / witnesses',
            'Guard submits evidence',
        ],
        'fire alarm activation' => [
            'Guard files report',
            'Admin reviews report',
            'Request alarm / evacuation log',
            'Guard confirms all-clear',
        ],
        'severe weather / flood hazard' => [
            'Guard files weather report',
            'Admin reviews advisories',
            'Request secured-site photos',
            'Guard confirms shelter status',
        ],
        'client site — trespassing' => [
            'Guard files report',
            'Admin reviews report',
            'Request patrol log / photos',
            'Guard submits trespass evidence',
        ],
        'policy breach — unauthorized access' => [
            'Guard files report',
            'Admin reviews report',
            'Request access logs / CCTV',
            'Guard submits breach evidence',
        ],
        'vandalism' => [
            'Guard files report',
            'Admin reviews report',
            'Request damage photos',
            'Guard submits evidence',
        ],
        'medical emergency' => [
            'Guard files report',
            'Admin reviews report',
            'Request EMS / first-aid log',
            'Guard submits follow-up proof',
        ],
    ];
}

/**
 * Four workflow columns shown in the operations guide table.
 *
 * @return list<string>
 */
function admin_incident_guard_workflow_four_steps(array $row): array
{
    $key = strtolower((string) ($row['incident_type'] ?? ''));
    $overrides = admin_incident_guard_workflow_step_overrides();
    if (isset($overrides[$key])) {
        return $overrides[$key];
    }

    return [
        'Guard files report',
        'Admin reviews report',
        'Admin requests evidence',
        'Guard submits evidence',
    ];
}

/**
 * Single operations guide table — incident type × four steps, grouped by scope.
 */
function admin_incident_guard_workflow_table_html(): string
{
    $rows = admin_incident_sanctions_reference();
    $byCategory = [];
    foreach ($rows as $row) {
        $slug = admin_incident_category_normalize((string) ($row['category'] ?? ADMIN_INCIDENT_CATEGORY_PER_POST));
        $byCategory[$slug][] = $row;
    }

    $html = '<div class="reports-workflow-table-wrap" tabindex="0" aria-label="Incident workflow by type">';
    $html .= '<table class="reports-workflow-table">';
    $html .= '<thead><tr>';
    $stepLabels = ['1 · File', '2 · Review', '3 · Evidence', '4 · Close'];
    $html .= '<th scope="col" class="reports-workflow-col-incident">Incident</th>';
    foreach ($stepLabels as $label) {
        $html .= '<th scope="col" class="reports-workflow-col-step">' . e($label) . '</th>';
    }
    $html .= '</tr></thead><tbody id="guide-workflow-tbody">';

    foreach (admin_incident_category_definitions() as $slug => $def) {
        if (empty($byCategory[$slug])) {
            continue;
        }
        $groupLabel = admin_incident_guard_workflow_group_label($slug);
        $html .= '<tr class="reports-workflow-category" data-category-group="' . e($slug) . '">';
        $html .= '<th scope="rowgroup" colspan="5">' . e(strtoupper($groupLabel)) . '</th>';
        $html .= '</tr>';

        foreach ($byCategory[$slug] as $row) {
            $steps = admin_incident_guard_workflow_four_steps($row);
            $search = strtolower(
                (string) $row['incident_type'] . ' '
                . $groupLabel . ' '
                . (string) ($def['description'] ?? '') . ' '
                . implode(' ', $steps)
            );
            $html .= '<tr class="reports-workflow-row" data-search="' . e($search) . '" data-category="' . e($slug) . '">';
            $html .= '<th scope="row" class="reports-workflow-col-incident">' . e((string) $row['incident_type']) . '</th>';
            foreach ($steps as $step) {
                $html .= '<td class="reports-workflow-col-step">' . e($step) . '</td>';
            }
            $html .= '</tr>';
        }
    }

    $html .= '</tbody></table></div>';

    return $html;
}

/**
 * Short section titles for the incident-report guard guide modal.
 */
function admin_incident_guide_compact_section_title(string $id, string $fallback): string
{
    return match ($id) {
        'status-basis' => 'Status rules',
        'severity-thresholds' => 'Severity SLAs',
        'missing-handling' => 'Incomplete reports',
        'repeat-case-status' => 'Status quick ref',
        'workflow' => 'Workflow summary',
        'case-progression' => 'Case stages',
        'if-it-happens-again' => 'Same guard again',
        'offense-escalation' => 'Offense count',
        'recurring-escalation' => 'Same post again',
        default => $fallback,
    };
}

/**
 * Full operations guide body: incident workflow table + system progression & registry reference.
 */
function admin_incident_guard_operations_guide_html(): string
{
    $registryIds = [
        'status-basis',
        'severity-thresholds',
        'missing-handling',
        'repeat-case-status',
    ];
    $recurrenceIds = [
        'if-it-happens-again',
        'offense-escalation',
        'recurring-escalation',
    ];
    $systemIds = [
        'workflow',
        'case-progression',
    ];

    $html = '<nav class="reports-guide-nav-strip" aria-label="Guide sections">';
    $html .= '<a class="reports-guide-nav-strip__link" href="#guide-block-incidents">Workflow</a>';
    $html .= '<a class="reports-guide-nav-strip__link" href="#guide-block-system">Progression</a>';
    $html .= '<a class="reports-guide-nav-strip__link" href="#guide-block-registry">Status</a>';
    $html .= '<a class="reports-guide-nav-strip__link" href="#guide-block-recurrence">Repeat</a>';
    $html .= '</nav>';

    $html .= '<div class="reports-guide-document">';

    $html .= admin_incident_guard_guide_sheet_open('guide-block-incidents', 'incidents', 'Workflow');
    $html .= admin_incident_guard_workflow_table_html();
    $html .= admin_incident_guard_guide_sheet_close();

    $html .= admin_incident_guard_guide_sheet_open('guide-block-system', 'system', 'Progression');
    $html .= admin_incident_guidelines_sections_html($systemIds, null, true);
    $html .= admin_incident_guard_guide_sheet_close();

    $html .= admin_incident_guard_guide_sheet_open('guide-block-registry', 'registry', 'Status');
    $html .= admin_incident_guidelines_sections_html($registryIds, null, true);
    $html .= admin_incident_guard_guide_sheet_close();

    $html .= admin_incident_guard_guide_sheet_open('guide-block-recurrence', 'recurrence', 'Repeat');
    $html .= admin_incident_guidelines_sections_html($recurrenceIds, null, true);
    $html .= admin_incident_guard_guide_sheet_close();

    $html .= '</div>';

    return $html;
}

function admin_incident_guard_guide_sheet_open(string $id, string $block, string $title): string
{
    return '<section class="reports-guide-sheet" id="' . e($id) . '" data-guide-block="' . e($block) . '">'
        . '<h3 class="reports-modal-form__section-title reports-guide-sheet__title">' . e($title) . '</h3>'
        . '<div class="reports-guide-sheet__body">';
}

function admin_incident_guard_guide_sheet_close(): string
{
    return '</div></section>';
}

/**
 * @return int Number of searchable reference sections in the operations guide (excludes preamble).
 */
function admin_incident_guard_operations_guide_section_count(): int
{
    return 10;
}

/**
 * Reference tables — incident monitoring & evaluation (mirrors attendance guidelines structure).
 *
 * @return list<array{
 *   id: string,
 *   title: string,
 *   intro: string,
 *   columns: list<string>,
 *   rows: list<list<string>>
 * }>
 */
/**
 * Opening context for the security guard incident guide (rules tab).
 */
function admin_incident_guard_guide_preamble_html(): string
{
    $search = 'who this guide for head guard field guard supervisor admin operations registry status roles';

    return '<section class="reports-guide-section reports-guide-section--preamble" id="guide-guard-context" data-guide-section-id="guard-context" data-guide-search="'
        . e($search) . '">'
        . '<h3 class="reports-guide-section__title">Who this guide is for</h3>'
        . '<p class="reports-guide-section__intro">Head guards, field guards, and admin reviewing <strong>security guard</strong> incidents — '
        . 'patrol rounds, post assignments, client sites, uniform and conduct, access control, and guard discipline.</p>'
        . '<div class="reports-guide-table-wrap"><table class="reports-guide-table">'
        . '<thead><tr><th scope="col">Role</th><th scope="col">Typical duty</th></tr></thead><tbody>'
        . '<tr><td>Field guard</td><td>On post or roving patrol; reports events to supervisor</td></tr>'
        . '<tr><td>Head guard / supervisor</td><td>Files incidents, verifies DGD/patrol logs, coordinates with client security</td></tr>'
        . '<tr><td>Admin / operations</td><td>Reviews evidence, sets registry status (' . e(admin_incident_status_workflow_lexicon()['registry_all']) . '), documents guard discipline decisions</td></tr>'
        . '</tbody></table></div></section>';
}

function admin_incident_guidelines_sections(): array
{
    $sections = [
        [
            'id' => 'status-basis',
            'title' => 'Guard incident status basis',
            'intro' => 'How registry status is set when a head guard files or updates a security incident report.',
            'columns' => ['Incident status', 'Basis / condition', 'System action', 'Registry equivalent', 'Remarks'],
            'rows' => [
                ['Ongoing / investigation', 'Report filed; investigation or follow-up in progress', 'Mark as Ongoing / investigation', 'Open', 'Default for new head-guard submissions'],
                ['On hold', 'Awaiting client statement, evidence, HR forms, or external input', 'Mark as On hold', 'Open', 'Paused — not closed'],
                ['Case closed', 'Incident resolved; closure memo or client sign-off on file', 'Mark as Case closed', 'Closed (1.00)', 'Archived — complete resolution'],
                ['Closed — not accepted', 'Duplicate filing, withdrawn by submitter, or not accepted', 'Mark as Closed — not accepted', 'Closed (0.00)', 'No further action required'],
                ['Pending verification', 'Missing fields, incomplete summary, or unsubmitted supporting data', 'Flag for manual review', 'N/A', 'Head guard / admin must validate before status change'],
            ],
        ],
        [
            'id' => 'severity-thresholds',
            'title' => 'Severity & response threshold guidelines',
            'intro' => 'Recommended response windows by severity tier (align with incident type default severity).',
            'columns' => ['Severity', 'Response threshold', 'Description'],
            'rows' => [
                ['High', 'Within 1 hour — guard on scene', 'Assault, medical, theft, access breach — preserve CCTV, DGD, witness names same shift'],
                ['Medium', 'Same business day', 'Trespass, vandalism, fire alarm, guard injury, client coordination'],
                ['Low', 'Within 24–48 hours', 'Radio outage, parking mediation, uniform reminder, attendance dispute'],
                ['Pending verification', 'Within 24 hours after shift', 'Head guard validates guard time-in/out or issues NTE'],
            ],
        ],
        [
            'id' => 'missing-handling',
            'title' => 'Missing value / incomplete report handling',
            'intro' => 'When incident data is incomplete, unsubmitted, or pending verification.',
            'columns' => ['Scenario', 'Action required', 'Responsible person', 'Final status'],
            'rows' => [
                ['Missing summary or required fields', 'Return to submitter for correction', 'Head guard / Admin', 'Pending verification'],
                ['No CCTV / evidence attached when required', 'Request supplemental upload or incident supplement', 'Head guard', 'On hold'],
                ['Guard failed to file after known event', 'Head guard files on behalf with narrative', 'Head guard', 'Ongoing / investigation'],
                ['Duplicate or redundant filing', 'Deny with ops note referencing original ref', 'Admin', 'Closed — not accepted'],
                ['Unresolved after validation period (24h)', 'Issue Notice to Explain (NTE) or mark absent/violation', 'Head guard / Admin', 'Escalated / Closed — not accepted'],
            ],
        ],
        [
            'id' => 'case-progression',
            'title' => 'How one case progresses (start to finish)',
            'intro' => 'From the day a head guard files until the case is closed — what status means and what happens next.',
            'columns' => ['Stage', 'Registry status', 'What happens', 'Who acts'],
            'rows' => [
                ['1 — Report filed', 'Ongoing / investigation', 'Guard, post, shift, and summary recorded; response SLA starts', 'Head guard'],
                ['2 — Admin review', 'Ongoing / investigation', 'Admin checks type, scope, duplicates; may return for missing fields', 'Admin'],
                ['3 — Need more proof', 'On hold', 'Case paused until DGD, patrol log, CCTV, photos, or guard statement', 'Head guard + guard'],
                ['4 — Evidence in', 'Ongoing / investigation', 'Admin reviews proof; may interview guard or supervisor', 'Admin'],
                ['5 — Repeat pattern noted', 'Ongoing / investigation', 'Prior similar INC refs linked in ops history; stronger action considered', 'Admin'],
                ['6 — NTE (if required)', 'On hold / Ongoing / investigation', 'Guard must submit written explanation before final decision', 'Admin → guard'],
                ['7 — Closed — resolved', 'Case closed', 'Discipline logged (coaching, warning, etc.); corrective action done', 'Admin'],
                ['8 — Closed — not accepted', 'Closed — not accepted', 'Duplicate, withdrawn filing, or does not meet requirements', 'Admin'],
            ],
        ],
        [
            'id' => 'if-it-happens-again',
            'title' => 'If the same guard does it again (recurrence)',
            'intro' => 'When the same security guard has another incident of the same or similar type — effect on duty, the case, and discipline.',
            'columns' => ['Times (same guard)', 'What happens to the guard', 'What happens to the case', 'Typical close'],
            'rows' => [
                ['1st incident', 'Coaching or verbal warning; usually stays on post', 'Normal steps in Guard actions tab; single INC reference', 'Case closed after evidence'],
                ['2nd similar', 'Written warning; supervisor increases spot checks on post', 'New report filed; ops history links to first INC', 'Case closed; pattern flagged'],
                ['3rd similar', 'NTE required; may limit client site or sensitive post assignment', 'Admin pulls full guard history before decision', 'On hold until NTE answered'],
                ['4th–5th', 'Suspension review; possible reassignment to lower-risk post', 'Management review; case stays open until HR/agency decision', 'Investigation / On hold'],
                ['6th+ or serious', 'Termination review; client notified if off-post/client incident', 'Critical handling; full audit trail', 'Escalated — not routine close'],
            ],
        ],
        [
            'id' => 'offense-escalation',
            'title' => 'Discipline by offense count (same violation type)',
            'intro' => 'Per-post conduct violations (sleeping on duty, AWOL, uniform, negligence, etc.) — stronger action each time.',
            'columns' => ['Offense #', 'Action for the guard', 'Case handling'],
            'rows' => [
                ['1st offense', 'Verbal warning — document in ops history same shift', 'Close as Case closed when evidence and coaching note on file'],
                ['2nd offense', 'Written warning — reference first INC in history', 'Investigation until warning acknowledged; then Case closed'],
                ['3rd offense', 'Suspension review (per agency policy)', 'On hold pending management / HR decision'],
                ['4th offense', 'Final written warning', 'Investigation; may restrict post assignment until review'],
                ['5th offense', 'Termination review', 'Escalated; remains open until formal outcome logged'],
            ],
        ],
        [
            'id' => 'recurring-escalation',
            'title' => 'Many reports at the same post (not always same guard)',
            'intro' => 'When a duty post keeps generating incidents — supervision and roster review, even if guards rotate.',
            'columns' => ['Reports at post', 'What happens', 'Case / post action'],
            'rows' => [
                ['1–2 similar', 'Reminder to head guard; check patrol schedule and SOP briefing', 'Each case closed normally'],
                ['3 at same post', 'Verbal warning to supervisor; audit post orders and coverage', 'Open monitoring note on post'],
                ['4–5 at same post', 'Written warning to head guard; client may be informed if contract post', 'Review roster — replace or add relief'],
                ['6+ at same post', 'Disciplinary review for supervision; post risk assessment', 'Agency may reassign team or change post manning'],
            ],
        ],
        [
            'id' => 'repeat-case-status',
            'title' => 'Status quick reference (investigation vs registry)',
            'intro' => 'Words used in meetings vs what you set in the registry.',
            'columns' => ['Situation', 'Set status to', 'Meaning for the guard'],
            'rows' => [
                ['Just filed, under review', 'Ongoing / investigation', 'Case is active — not final'],
                ['Waiting for guard papers / CCTV', 'On hold', 'Paused — guard or supervisor must submit'],
                ['Repeat offender — management involved', 'Ongoing / investigation + ops note', 'Still open — do not close until escalation done'],
                ['Resolved with warning or NTE', 'Case closed', 'Closed — guard record updated'],
                ['Duplicate or invalid report', 'Closed — not accepted', 'No discipline — filing rejected'],
            ],
        ],
        [
            'id' => 'report-structure',
            'title' => 'Guard incident report fields',
            'intro' => 'What each registry field means for security guard incidents.',
            'columns' => ['Field name', 'Description'],
            'rows' => [
                ['Reference (INC-YYYY-####)', 'Unique incident registry identifier'],
                ['Report scope', 'On post (guard’s duty post) or Off post (client site / off-post assignment)'],
                ['Incident type', 'Guard incident category from this guide'],
                ['Post', 'Duty post name or client site where the guard was assigned'],
                ['Head guard', 'Supervisor who filed the report for the guard/post'],
                ['Submitted date & time', 'When the report entered the registry'],
                ['Status', 'Ongoing / investigation · On hold · Case closed · Closed — not accepted'],
                ['Severity', 'High / Medium / Low'],
                ['Summary', 'Narrative of the event'],
                ['Operations history', 'Status changes, notes, and timeline'],
                ['Verified by', 'Admin validator on closure (when applicable)'],
            ],
        ],
        [
            'id' => 'workflow',
            'title' => 'Guard incident workflow (summary)',
            'intro' => 'From guard report to closure — aligns with Post & conduct rules below.',
            'columns' => ['Step', 'Process'],
            'rows' => [
                ['1', 'Head guard files report — guard, post, and shift identified; usually Ongoing / investigation'],
                ['2', 'Admin reviews — valid filing or Closed — not accepted if duplicate'],
                ['3', 'Evidence requested — On hold until DGD, CCTV, patrol log, or statements'],
                ['4', 'Guard/supervisor submits proof — photos, logs, time records'],
                ['5', 'Resolution — Case closed when guard discipline / client closure requirements met'],
            ],
        ],
        [
            'id' => 'attendance-link',
            'title' => 'Guard attendance incidents',
            'intro' => 'When filing Attendance / shift dispute for a security guard — align with time-in/out records.',
            'columns' => ['Attendance status', 'Basis / condition', 'Equivalent value', 'Incident report action'],
            'rows' => [
                ['Present', 'Guard logged within on-time threshold (0–15 min grace)', '1.00', 'Usually no incident — dispute only if roster conflict'],
                ['Late', 'Check-in after grace, within late threshold (16–30 min)', '0.50', 'File if recurring tardiness or dispute; monitor pattern'],
                ['Absent', 'No record beyond max threshold or confirmed non-attendance', '0.00', 'File incident or link to absence investigation'],
                ['No value / missing', 'System issue, forgot time-in/out, pending verification', 'N/A', 'File Attendance / shift dispute; NTE if unresolved in 24h'],
            ],
        ],
        [
            'id' => 'remarks-examples',
            'title' => 'Remarks examples (operations history)',
            'intro' => 'Suggested ops notes when updating incident status or history.',
            'columns' => ['Situation', 'Suggested remark'],
            'rows' => [
                ['Under review', 'Pending verification due to incomplete incident log.'],
                ['NTE issued', 'Notice to Explain issued for unresolved missing incident data.'],
                ['Corrected', 'Incident validated and corrected upon review.'],
                ['Closed non-compliance', 'Marked closed — not accepted — non-compliance with submission requirements.'],
                ['Client hold', 'On hold — awaiting client incident form or blotter reference.'],
            ],
        ],
    ];

    return admin_incident_apply_status_terminology_sections($sections);
}

/**
 * @param list<string> $sectionIds
 * @return list<array{id: string, title: string}>
 */
function admin_incident_guide_section_options(array $sectionIds, ?array $sections = null): array
{
    $options = [];
    foreach ($sections ?? admin_incident_guidelines_sections() as $section) {
        if ($sectionIds !== [] && !in_array($section['id'], $sectionIds, true)) {
            continue;
        }
        $options[] = [
            'id' => (string) $section['id'],
            'title' => (string) $section['title'],
        ];
    }

    return $options;
}

/**
 * @param list<array{id: string, title: string}> $options
 * @param list<array{label: string, options: list<array{id: string, title: string}>}> $groups
 */
function admin_incident_guide_section_picker_html(
    string $pickerId,
    string $label,
    array $options = [],
    array $groups = []
): string {
    if ($options === [] && $groups === []) {
        return '';
    }

    $html = '<div class="reports-guide-section-picker">';
    $html .= '<label class="reports-guide-section-picker__label" for="' . e($pickerId) . '">' . e($label) . '</label>';
    $html .= '<select id="' . e($pickerId) . '" class="reports-guide-section-picker__select" data-guide-section-picker>';

    if ($groups !== []) {
        $first = true;
        foreach ($groups as $group) {
            $html .= '<optgroup label="' . e((string) $group['label']) . '">';
            foreach ($group['options'] as $opt) {
                $html .= '<option value="' . e((string) $opt['id']) . '"' . ($first ? ' selected' : '') . '>'
                    . e((string) $opt['title']) . '</option>';
                $first = false;
            }
            $html .= '</optgroup>';
        }
    } else {
        foreach ($options as $i => $opt) {
            $html .= '<option value="' . e((string) $opt['id']) . '"' . ($i === 0 ? ' selected' : '') . '>'
                . e((string) $opt['title']) . '</option>';
        }
    }

    $html .= '</select></div>';

    return $html;
}

/**
 * @param array<string, mixed> $section
 */
function admin_incident_guide_section_search_blob(array $section): string
{
    $parts = [(string) ($section['title'] ?? ''), (string) ($section['intro'] ?? '')];
    foreach ($section['columns'] ?? [] as $column) {
        $parts[] = (string) $column;
    }
    foreach ($section['rows'] ?? [] as $row) {
        foreach ($row as $cell) {
            $parts[] = (string) $cell;
        }
    }

    return strtolower(implode(' ', $parts));
}

/**
 * @param list<string> $sectionIds Empty = all sections
 */
function admin_incident_guidelines_sections_html(array $sectionIds = [], ?array $sections = null, bool $compact = false): string
{
    $html = '';
    foreach ($sections ?? admin_incident_guidelines_sections() as $section) {
        if ($sectionIds !== [] && !in_array($section['id'], $sectionIds, true)) {
            continue;
        }

        $sectionId = (string) $section['id'];
        $title = $compact
            ? admin_incident_guide_compact_section_title($sectionId, (string) $section['title'])
            : (string) $section['title'];
        $searchBlob = admin_incident_guide_section_search_blob($section);
        $html .= '<section class="reports-guide-section' . ($compact ? ' reports-guide-section--compact' : '') . '" id="guide-' . e($sectionId) . '" data-guide-section-id="'
            . e($sectionId) . '" data-guide-search="' . e($searchBlob) . '">';
        $html .= '<h4 class="reports-guide-section__title">' . e($title) . '</h4>';
        if (!$compact && ($section['intro'] ?? '') !== '') {
            $html .= '<p class="reports-guide-section__intro">' . e((string) $section['intro']) . '</p>';
        }
        $html .= '<div class="reports-guide-table-wrap"><table class="reports-guide-table">';
        $html .= '<thead><tr>';
        foreach ($section['columns'] as $column) {
            $html .= '<th scope="col">' . e((string) $column) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($section['rows'] as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . e((string) $cell) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table></div></section>';
    }

    return $html;
}

/**
 * Jump navigation for guidelines panel.
 */
/**
 * @param list<array{id: string, title: string}>|null $sections
 */
function admin_incident_guidelines_nav_html(?array $sections = null): string
{
    $links = '';
    $list = $sections ?? admin_incident_guidelines_sections();
    foreach ($list as $section) {
        $links .= '<a href="#guide-' . e((string) $section['id']) . '" class="reports-guide-nav__link">'
            . e((string) $section['title']) . '</a>';
    }

    return '<nav class="reports-guide-nav" aria-label="Guidelines sections">' . $links . '</nav>';
}

/**
 * Sortable column header for operations guide tables.
 */
function admin_incident_guide_sort_th(string $sortKey, string $label, bool $center = false, string $thClass = ''): string
{
    $btnClass = 'reports-sort reports-guide-sort' . ($center ? ' reports-sort--center' : '');
    $thAttr = $thClass !== '' ? ' class="' . e($thClass) . '"' : '';

    return '<th scope="col"' . $thAttr . ' aria-sort="none">'
        . '<button type="button" class="' . $btnClass . '" data-guide-sort-key="' . e($sortKey) . '">'
        . '<span class="reports-sort__label">' . e($label) . '</span>'
        . '<span class="reports-sort__icon reports-sort__icon--idle" aria-hidden="true"></span>'
        . '</button></th>';
}

/**
 * When to file — simplified incident type catalog (classification only).
 */
function admin_incident_types_table_html(): string
{
    $html = '<div class="reports-guide-panel-stack" data-guide-panel-stack="filing" data-guide-topic-mode="rows">';
    $html .= '<div class="reports-guide-section-view" data-guide-section-view>';
    $html .= '<div class="reports-guide-table-wrap" tabindex="0" aria-label="When to file reference">';
    $html .= '<table class="reports-guide-table reports-guide-table--filing">';
    $html .= '<thead><tr>';
    $html .= admin_incident_guide_sort_th('type', 'Incident');
    $html .= admin_incident_guide_sort_th('category', 'Post / site', true);
    $html .= admin_incident_guide_sort_th('severity', 'Severity', true);
    $html .= '<th scope="col">When a guard should file</th>';
    $html .= '<th scope="col">Guard response time</th>';
    $html .= '</tr></thead><tbody id="guide-types-tbody">';

    foreach (admin_incident_types_reference() as $row) {
        $sev = strtolower((string) $row['severity']);
        $html .= '<tr class="reports-guide-type-row reports-guide-data-row" data-search="'
            . e(strtolower(
                (string) $row['incident_type'] . ' '
                . (string) $row['category_label'] . ' '
                . (string) $row['severity'] . ' '
                . (string) $row['filing_trigger'] . ' '
                . (string) $row['response_sla']
            )) . '"'
            . ' data-category="' . e((string) $row['category']) . '"'
            . ' data-severity="' . e($sev) . '"'
            . ' data-incident-type="' . e(strtolower((string) $row['incident_type'])) . '">';
        $html .= '<td><span class="reports-guide-type-name">' . e((string) $row['incident_type']) . '</span></td>';
        $html .= '<td><span class="reports-badge reports-badge--' . e((string) $row['category']) . '">'
            . e((string) $row['category_label']) . '</span></td>';
        $html .= '<td><span class="reports-guide-severity reports-guide-severity--' . e($sev) . '">'
            . e((string) $row['severity']) . '</span></td>';
        $html .= '<td>' . e((string) $row['filing_trigger']) . '</td>';
        $html .= '<td>' . e((string) $row['response_sla']) . '</td>';
        $html .= '</tr>';
    }

    $html .= '</tbody></table></div>';
    $html .= '</div></div>';

    return $html;
}

/**
 * Incident-type options for guide row pickers (grouped by report scope).
 *
 * @param list<array{incident_type: string, category: string}> $rows
 * @return list<array{label: string, options: list<array{id: string, title: string}>}>
 */
function admin_incident_guide_incident_type_option_groups(array $rows): array
{
    $byCategory = [];
    foreach ($rows as $row) {
        $slug = admin_incident_category_normalize((string) ($row['category'] ?? ADMIN_INCIDENT_CATEGORY_PER_POST));
        $title = (string) ($row['incident_type'] ?? '');
        if ($title === '') {
            continue;
        }
        $byCategory[$slug][] = [
            'id' => strtolower($title),
            'title' => $title,
        ];
    }

    $groups = [];
    foreach (admin_incident_category_definitions() as $slug => $def) {
        if (empty($byCategory[$slug])) {
            continue;
        }
        $groups[] = [
            'label' => (string) $def['label'],
            'options' => $byCategory[$slug],
        ];
    }

    return $groups;
}

/**
 * Topic picker config per guide tab (JSON for toolbar select).
 *
 * @return array<string, array{label: string, mode: string, options?: list<array{id: string, title: string}>, groups?: list<array{label: string, options: list<array{id: string, title: string}>}>}>
 */
function admin_incident_guide_topics_config(): array
{
    $monitoringIds = [
        'status-basis',
        'severity-thresholds',
        'workflow',
        'missing-handling',
    ];

    return [
        'steps' => [
            'label' => 'Choose incident type',
            'mode' => 'rows',
            'groups' => admin_incident_guide_incident_type_option_groups(admin_incident_sanctions_reference()),
        ],
        'filing' => [
            'label' => 'Choose incident type',
            'mode' => 'rows',
            'groups' => admin_incident_guide_incident_type_option_groups(admin_incident_types_reference()),
        ],
        'progression' => [
            'label' => 'Choose topic to read',
            'mode' => 'sections',
            'options' => admin_incident_guide_section_options(
                admin_incident_case_progression_section_ids(),
                admin_incident_guidelines_sections()
            ),
        ],
        'rules' => [
            'label' => 'Choose topic to read',
            'mode' => 'sections',
            'groups' => [
                [
                    'label' => 'Overview',
                    'options' => [
                        ['id' => 'guard-context', 'title' => 'Who this guide is for'],
                    ],
                ],
                [
                    'label' => 'Registry & status',
                    'options' => admin_incident_guide_section_options($monitoringIds, admin_incident_guidelines_sections()),
                ],
                [
                    'label' => 'Per-post conduct',
                    'options' => admin_incident_guide_section_options(
                        admin_incident_violation_workflow_section_ids(),
                        admin_incident_violation_workflow_sections()
                    ),
                ],
            ],
        ],
    ];
}

/**
 * Workflow rules — status, thresholds, and on-post violation handling (single scroll).
 */
function admin_incident_operations_rules_html(): string
{
    $monitoringIds = [
        'status-basis',
        'severity-thresholds',
        'workflow',
        'missing-handling',
    ];

    $html = '<div class="reports-guide-panel-stack" data-guide-panel-stack="rules" data-guide-topic-mode="sections">';
    $html .= '<div class="reports-guide-section-view" data-guide-section-view>';
    $html .= admin_incident_guard_guide_preamble_html();
    $html .= admin_incident_guidelines_sections_html(
        $monitoringIds,
        admin_incident_guidelines_sections()
    );
    $html .= '<div class="reports-guide-rules-divider" role="separator" aria-hidden="true"></div>';
    $html .= admin_incident_guidelines_sections_html(
        admin_incident_violation_workflow_section_ids(),
        admin_incident_violation_workflow_sections()
    );
    $html .= '</div></div>';

    return $html;
}

/**
 * Case progression and recurrence — how incidents advance and what happens if they repeat.
 *
 * @return list<string>
 */
function admin_incident_case_progression_section_ids(): array
{
    return [
        'case-progression',
        'if-it-happens-again',
        'offense-escalation',
        'recurring-escalation',
        'repeat-case-status',
    ];
}

function admin_incident_case_progression_html(): string
{
    $sectionIds = admin_incident_case_progression_section_ids();

    $html = '<div class="reports-guide-panel-stack" data-guide-panel-stack="progression" data-guide-topic-mode="sections">';
    $html .= '<div class="reports-guide-section-view" data-guide-section-view>';
    $html .= admin_incident_guidelines_sections_html(
        $sectionIds,
        admin_incident_guidelines_sections()
    );
    $html .= '</div></div>';

    return $html;
}

/**
 * @return array{category: string, severity: string, incident_type: string, category_label: string, severity_label: string, steps: list<string>}
 */
function admin_incident_guide_row_attrs(array $row): array
{
    return [
        'category' => admin_incident_category_normalize((string) ($row['category'] ?? ADMIN_INCIDENT_CATEGORY_PER_POST)),
        'severity' => strtolower((string) ($row['severity'] ?? 'medium')),
        'incident_type' => strtolower((string) ($row['incident_type'] ?? '')),
        'category_label' => admin_incident_category_label((string) ($row['category'] ?? ADMIN_INCIDENT_CATEGORY_PER_POST)),
        'severity_label' => (string) ($row['severity'] ?? ''),
        'steps' => is_array($row['steps'] ?? null) ? $row['steps'] : [],
    ];
}
