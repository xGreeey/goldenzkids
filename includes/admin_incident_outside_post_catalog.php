<?php
declare(strict_types=1);

require_once __DIR__ . '/admin_incident_status.php';

/**
 * Outside-post incident types (client site, perimeter, off-post assignments).
 *
 * @return list<array<string, mixed>>
 */
function admin_incident_outside_post_extended_types(): array
{
    $rows = [
        [
            'incident_type' => 'Disturbance / disorderly conduct',
            'severity' => 'Medium',
            'filing_basis' => 'Public order / client conduct event',
            'filing_trigger' => 'Verbal altercation, loitering group, or disruptive behavior on client property',
            'response_sla' => 'Scene stabilized and report filed same shift',
            'steps' => [
                'De-escalate per client SOP; call client security or police if threat level requires.',
                'Separate parties; document names, times, and guard mediation steps taken.',
                'Preserve witness statements and CCTV references before end of shift.',
                'Coaching if guard protocol followed; reprimand only for instigation or post abandonment.',
                'On hold for client blotter when required; accomplish when client closure memo received.',
            ],
        ],
        [
            'incident_type' => 'Suspicious person / unattended package',
            'severity' => 'High',
            'filing_basis' => 'Threat assessment / suspicious activity',
            'filing_trigger' => 'Unidentified person, vehicle, or package requiring client security protocol',
            'response_sla' => 'Immediate notification; cordon and log within 30 minutes',
            'steps' => [
                'Activate client suspicious-activity SOP; do not touch unattended items unless trained.',
                'Notify client security and operations; maintain visual observation from safe distance.',
                'Document description, location, time, and actions taken without speculation.',
                'No guard sanction unless failure to report, abandon post, or breach of cordon SOP.',
                'Accomplished when client all-clear or police case reference is attached.',
            ],
        ],
        [
            'incident_type' => 'Power outage / utility failure',
            'severity' => 'Medium',
            'filing_basis' => 'Facility utility / building systems',
            'filing_trigger' => 'Loss of power, water, HVAC, or elevator affecting post coverage at client site',
            'response_sla' => 'Log within 1 hour; client engineering notified same shift',
            'steps' => [
                'Record outage start time, affected areas, and backup lighting or generator status.',
                'Notify client engineering and operations; adjust patrol plan per client instruction.',
                'Confirm guard roster and post coverage via alternate comms if radios affected.',
                'No personnel sanction for utility fault; coaching only if post left unattended.',
                'Accomplished when utility restored and client sign-off or ticket number on file.',
            ],
        ],
        [
            'incident_type' => 'Severe weather / flood hazard',
            'severity' => 'Medium',
            'filing_basis' => 'Environmental / force majeure at site',
            'filing_trigger' => 'Typhoon signal, flooding, structural risk, or client suspension of operations',
            'response_sla' => 'Safety actions immediate; written report within same shift',
            'steps' => [
                'Execute client emergency weather SOP; prioritize guard and visitor safety.',
                'Document signal level, flooded areas, and any relocation of posts.',
                'Notify operations and confirm headcount after relocation or early release.',
                'No sanction for following client stand-down; review only if post abandoned without order.',
                'On hold during active weather; accomplish when client resumes normal ops memo filed.',
            ],
        ],
        [
            'incident_type' => 'Contractor / vendor access dispute',
            'severity' => 'Low',
            'filing_basis' => 'Access control — non-roster personnel',
            'filing_trigger' => 'Contractor, delivery, or vendor denied or disputed entry at client checkpoint',
            'response_sla' => 'Resolve or escalate to client FM same business day',
            'steps' => [
                'Verify work order, ID, and client authorization per site access matrix.',
                'Document dispute, parties, and client contact consulted.',
                'Do not admit without client approval; log refusal or escorted entry.',
                'Coaching if guard failed to follow access matrix; no sanction for correct denial.',
                'Accomplished when client access log entry or email approval archived.',
            ],
        ],
        [
            'incident_type' => 'Animal / pest incident',
            'severity' => 'Low',
            'filing_basis' => 'Site safety — fauna / pest hazard',
            'filing_trigger' => 'Stray animal, snake, bee swarm, or pest infestation affecting post or public area',
            'response_sla' => 'Area secured and client notified within 2 hours',
            'steps' => [
                'Secure area; warn occupants; do not handle wildlife unless client-trained.',
                'Notify client facilities or pest control per site protocol.',
                'Photograph location and timeline; note guard actions and signage posted.',
                'No sanction unless guard ignored known hazard or left post uncovered.',
                'Accomplished when client pest/animal service ticket or clearance logged.',
            ],
        ],
    ];

    $out = [];
    foreach ($rows as $row) {
        $out[] = array_merge($row, [
            'category' => 'outside_post',
            'category_label' => 'Outside post',
            'initial_status' => admin_incident_status_label(ADMIN_INCIDENT_STATUS_ONGOING),
            'responsible' => 'Head guard → Client security / Operations',
            'system_action' => 'Guard at client site — coordinate with client security before closure',
            'remarks' => 'Security guard off regular post — document patrol, witnesses, client blotter if required',
        ]);
    }

    foreach ($out as $i => $row) {
        $out[$i] = admin_incident_apply_status_terminology_to_type_row($row);
    }

    return $out;
}
