<?php
declare(strict_types=1);

require_once __DIR__ . '/admin_incident_status.php';

/**
 * Per-post internal violation types (discipline, conduct, compliance at assigned post).
 *
 * @return list<array<string, mixed>>
 */
function admin_incident_per_post_violation_types(): array
{
    $rows = [
        [
            'incident_type' => 'Sleeping on duty',
            'severity' => 'High',
            'filing_basis' => 'Internal violation — post conduct',
            'filing_trigger' => 'Supervisor or CCTV confirms guard unresponsive or asleep while on post',
            'response_sla' => 'Same shift documentation; admin review within 24 hours',
            'steps' => [
                'Supervisor documents time window, post, and witness/CCTV reference.',
                'File per-post incident; status Ongoing pending admin review.',
                'Interview guard; compare with patrol log and time-in/out records.',
                '1st offense: verbal warning; repeat: written warning per escalation table.',
                'Accomplished only with signed admin decision and ops history note.',
            ],
        ],
        [
            'incident_type' => 'AWOL / absence without leave',
            'severity' => 'High',
            'filing_basis' => 'Internal violation — attendance',
            'filing_trigger' => 'Confirmed absence from post without approved leave or handover',
            'response_sla' => 'Pull timekeeping same day; HR notified within 24 hours',
            'steps' => [
                'Verify roster, time logs, and relief coverage before any sanction.',
                'Head guard files per-post report linked to attendance records.',
                'Guard narrative required; On hold until statement received.',
                'Apply escalation table by offense count; NTE if disputed or repeated.',
                'Deny duplicate filings; accomplish when HR/admin closure logged.',
            ],
        ],
        [
            'incident_type' => 'Uniform violation',
            'severity' => 'Low',
            'filing_basis' => 'Internal violation — appearance / SOP',
            'filing_trigger' => 'Non-compliant uniform, missing ID, or PPE lapse observed on post',
            'response_sla' => 'Coaching same shift; written warning if repeated within 30 days',
            'steps' => [
                'Supervisor notes specific deficiency (photo if policy allows).',
                'Verbal coaching for first observable lapse same shift.',
                'File incident if repeated or willful non-compliance.',
                'Track per guard; 3rd occurrence triggers written warning.',
                'Accomplished when compliance confirmed on next inspection.',
            ],
        ],
        [
            'incident_type' => 'Data privacy violation',
            'severity' => 'High',
            'filing_basis' => 'Internal violation — policy / legal',
            'filing_trigger' => 'Unauthorized disclosure, device misuse, or client data mishandling on post',
            'response_sla' => 'Contain breach same shift; admin and HR same business day',
            'steps' => [
                'Secure devices/logs; cease further exposure immediately.',
                'Document what data, who, when, and which post — no informal discipline alone.',
                'Admin reviews policy matrix; may require NTE and legal/HR consult.',
                'Written warning minimum; suspension review for confirmed breach.',
                'Accomplished with remediation proof and signed admin memo.',
            ],
        ],
        [
            'incident_type' => 'Document tampering',
            'severity' => 'High',
            'filing_basis' => 'Internal violation — records integrity',
            'filing_trigger' => 'Altered DGD, patrol log, incident form, or time record suspected',
            'response_sla' => 'Preserve originals; admin review within 24 hours',
            'steps' => [
                'Preserve original uploads and system timestamps before confrontation.',
                'Flag for admin; compare OCR / audit trail if available.',
                'Interview guard and supervisor separately; document statements.',
                'NTE required before final sanction; consider suspension pending review.',
                'Accomplished or Denied only after evidence verification logged.',
            ],
        ],
        [
            'incident_type' => 'Operational negligence (post)',
            'severity' => 'Medium',
            'filing_basis' => 'Internal violation — failure to follow post SOP',
            'filing_trigger' => 'Missed rounds, open access point, abandoned post, or failure to follow checklist',
            'response_sla' => 'Supervisor confirmation same shift; admin within 48 hours',
            'steps' => [
                'Document SOP clause breached and factual timeline (no opinion alone).',
                'Request supporting proof: logs, CCTV stills, witness statements.',
                'Coaching if isolated lapse; written warning if negligence substantiated.',
                'Link to recurring escalation if same guard/post pattern exists.',
                'On hold until evidence complete; then Accomplished or escalated.',
            ],
        ],
        [
            'incident_type' => 'Visitor escort violation',
            'severity' => 'Medium',
            'filing_basis' => 'Internal violation — access / visitor control',
            'filing_trigger' => 'Visitor admitted without escort, expired pass, or post visitor log not completed',
            'response_sla' => 'Correct access same shift; admin review within 24 hours',
            'steps' => [
                'Secure area; verify visitor status with client or post access matrix.',
                'Document time, post, visitor details, and guard actions taken.',
                'Coaching for first lapse; file incident if repeat or willful bypass.',
                'Written warning if unauthorized access resulted from guard negligence.',
                'Accomplished when visitor log corrected and supervisor sign-off logged.',
            ],
        ],
        [
            'incident_type' => 'Key or credential custody breach',
            'severity' => 'High',
            'filing_basis' => 'Internal violation — keys / badges / credentials',
            'filing_trigger' => 'Lost key, unreturned badge, shared PIN, or credential not secured at post',
            'response_sla' => 'Report and lock change request same shift',
            'steps' => [
                'Report loss immediately; initiate key/badge inventory and lock change per client policy.',
                'Document last known custody chain and witness statements.',
                'No informal discipline — admin reviews severity and client notification requirements.',
                'Written warning minimum; suspension review if breach enabled unauthorized access.',
                'Accomplished when replacement credentials issued and incident memo signed.',
            ],
        ],
        [
            'incident_type' => 'Personal device use on post',
            'severity' => 'Low',
            'filing_basis' => 'Internal violation — conduct / distraction',
            'filing_trigger' => 'Non-authorized phone, earphones, or personal device use observed during active post',
            'response_sla' => 'Verbal correction same shift; pattern review within 7 days',
            'steps' => [
                'Supervisor documents observation time, post, and device use (photo if policy allows).',
                'Verbal coaching and immediate cessation required on first observation.',
                'File per-post incident if repeated within 7 days or during high-risk window.',
                'Written warning after third documented occurrence per escalation table.',
                'Accomplished when compliance confirmed on follow-up inspection.',
            ],
        ],
        [
            'incident_type' => 'Use of force / restraint incident',
            'severity' => 'High',
            'filing_basis' => 'Internal violation — force continuum review',
            'filing_trigger' => 'Physical intervention, restraint, or force applied during post duties',
            'response_sla' => 'Report immediately after scene safe; admin same business day',
            'steps' => [
                'Secure medical needs first; preserve witness names and CCTV before end of shift.',
                'Document force level, client SOP clause, and alternatives attempted.',
                'No closure without admin and legal/HR review of force continuum compliance.',
                'Commendation, coaching, or sanction based on objective SOP adherence review.',
                'On hold until review complete; Accomplished or Denied with signed admin memo.',
            ],
        ],
    ];

    $out = [];
    foreach ($rows as $row) {
        $out[] = array_merge($row, [
            'category' => ADMIN_INCIDENT_CATEGORY_PER_POST,
            'category_label' => 'On post',
            'initial_status' => admin_incident_status_label(ADMIN_INCIDENT_STATUS_ONGOING),
            'responsible' => 'Head guard (supervisor) → Admin',
            'system_action' => 'Guard conduct / post duty — evidence required before closure',
            'remarks' => 'Security guard on post — validate with DGD, patrol log, or CCTV before discipline',
        ]);
    }

    foreach ($out as $i => $row) {
        $out[$i] = admin_incident_apply_status_terminology_to_type_row($row);
    }

    return $out;
}

/**
 * Violation & incident management workflow (internal / per-post focus).
 *
 * @return list<array{
 *   id: string,
 *   title: string,
 *   intro: string,
 *   columns: list<string>,
 *   rows: list<list<string>>
 * }>
 */
function admin_incident_violation_workflow_sections(): array
{
    $sections = [
        [
            'id' => 'vw-purpose',
            'title' => 'Purpose — security guard incidents',
            'intro' => 'How field guards, head guards, and admin handle incidents on duty — patrol, post SOP, uniform, conduct, and compliance.',
            'columns' => ['Objective'],
            'rows' => [
                ['Every incident properly reported with post, shift, and submitter identified'],
                ['Verified with evidence before validation or discipline'],
                ['Reviewed by management with logged admin decision'],
                ['Documented in the registry with full operations history'],
                ['Resolved with accountability — Accomplished, Denied, or remains On hold / Ongoing'],
                ['Standardized procedures across posts to avoid inconsistent handling'],
            ],
        ],
        [
            'id' => 'vw-lifecycle',
            'title' => 'Core workflow lifecycle',
            'intro' => 'Each per-post violation or incident follows this lifecycle (maps to registry status in parentheses).',
            'columns' => ['Phase', 'Description', 'Registry status'],
            'rows' => [
                ['Step 1 — Incident reporting', 'Initial report submitted by guard or supervisor (head guard)', 'Ongoing'],
                ['Step 2 — Admin review', 'Admin validates report or marks Denied if invalid/duplicate', 'Ongoing / Denied'],
                ['Step 3 — Evidence request', 'Admin requests additional supporting evidence due to insufficient verification', 'On hold'],
                ['Step 4 — Evidence submission', 'Guard/supervisor provides photos, logs, DGD, CCTV, statements', 'On hold / Ongoing'],
                ['Step 5 — Resolution decision', 'Admin evaluates evidence and determines formal resolution or further investigation', 'Ongoing'],
                ['Resolution', 'Case Accomplished (closed) or remains open (Ongoing / On hold)', 'Accomplished / open'],
            ],
        ],
        [
            'id' => 'vw-responsibility',
            'title' => 'Chain of responsibility',
            'intro' => 'Clear ownership at each stage — prevents confusion on who acts next.',
            'columns' => ['Role', 'Responsibility'],
            'rows' => [
                ['Field guard', 'Reports incidents; submits requested evidence; complies with NTE if issued'],
                ['Supervisor / head guard', 'Confirms incidents at post; monitors guards; files or endorses reports'],
                ['Admin', 'Reviews, validates, requests proof, escalates, resolves, logs final action'],
                ['System / AI (when enabled)', 'Flags anomalies, classifies priority, surfaces dashboard alerts for admin'],
            ],
        ],
        [
            'id' => 'vw-evidence',
            'title' => 'Evidence-based validation',
            'intro' => 'No per-post violation is treated as confirmed without supporting proof.',
            'columns' => ['Accepted evidence', 'Purpose'],
            'rows' => [
                ['Photos / videos', 'Document conduct, uniform, scene, or post conditions'],
                ['DGD images & patrol logs', 'Verify rounds, entries, and time coverage'],
                ['Incident reports & prior registry refs', 'Establish pattern and linkage'],
                ['CCTV screenshots', 'Corroborate supervisor allegations'],
                ['Time-in / time-out logs', 'Attendance and AWOL disputes'],
                ['Chat / comms records', 'Context for negligence or policy breaches'],
                ['—', 'Prevent false accusations; support lawful discipline; improve accuracy'],
            ],
        ],
        [
            'id' => 'vw-classification',
            'title' => 'Incident classification (report scope)',
            'intro' => 'On post = guard’s regular duty post. Off post = client site or assignment away from that post.',
            'columns' => ['Report scope', 'Guard focus', 'Examples'],
            'rows' => [
                [
                    'On post',
                    'Guard duty, conduct, patrol, access, uniform, negligence at assigned post',
                    'Sleeping on duty, AWOL, missed rounds, uniform, keys, device use, force incident',
                ],
                [
                    'Off post',
                    'Client site security, perimeter, public areas while on assignment',
                    'Theft, trespass, medical emergency, fire alarm, vandalism at mall/site',
                ],
            ],
        ],
        [
            'id' => 'vw-internal-logic',
            'title' => 'Per-post internal violation workflow',
            'intro' => 'Standard logic for violations discovered or documented at the assigned post.',
            'columns' => ['Step', 'Action'],
            'rows' => [
                ['1', 'Supervisor detects or documents violation on post'],
                ['2', 'Report filed in registry — scope On post, type matched from catalog'],
                ['3', 'Admin or system flags severity; Ongoing until review complete'],
                ['4', 'Admin reviews guard history at post; requests evidence if missing'],
                ['5', 'Appropriate disciplinary step per escalation table; ops note in history'],
                ['6', 'Accomplished only when resolution requirements met (see below)'],
            ],
        ],
        [
            'id' => 'vw-ai-rules',
            'title' => 'AI-assisted detection (when enabled)',
            'intro' => 'Reduces manual monitoring load; admin always confirms before sanction.',
            'columns' => ['Detection type', 'Example', 'Admin action'],
            'rows' => [
                ['OCR / log integrity', 'Suspected altered DGD entry', 'Request originals; open per-post incident'],
                ['Attendance anomaly', 'Missing time-out or incomplete log', 'Link Attendance / shift dispute type'],
                ['Priority classification', 'High-risk conduct flagged', 'Expedite admin review same shift'],
                ['Dashboard alert', 'Threshold or pattern breach', 'Assign reviewer; keep ticket Ongoing'],
            ],
        ],
        [
            'id' => 'vw-resolution',
            'title' => 'Resolution requirements (Accomplished)',
            'intro' => 'Admin evaluates all submitted evidence before marking Accomplished.',
            'columns' => ['Requirement', 'Description'],
            'rows' => [
                ['Evidence verified', 'Submitted proof validated and referenced in history'],
                ['Admin decision logged', 'Final action documented in operations history'],
                ['Penalty recorded', 'Warning / NTE / suspension noted if applicable'],
                ['Follow-up completed', 'Guard/supervisor complied with required corrective action'],
                ['Status updated', 'Registry Accomplished; record available for export/audit'],
            ],
        ],
        [
            'id' => 'vw-pending',
            'title' => 'Pending / unresolved cases',
            'intro' => 'Remain Ongoing or On hold — not Accomplished — when:',
            'columns' => ['Condition', 'Admin action'],
            'rows' => [
                ['Insufficient evidence', 'Request additional supporting evidence due to insufficient verification'],
                ['Guard non-cooperation', 'Escalate to management; document in history'],
                ['Investigation ongoing', 'Keep Ongoing; set review date in ops note'],
                ['Clarification required', 'On hold until supervisor statement received'],
                ['Emergency still active (outside post)', 'On hold — not applicable to pure internal conduct cases'],
            ],
        ],
        [
            'id' => 'vw-nte',
            'title' => 'Notice to explain (NTE) — internal violations',
            'intro' => 'Required for serious or repeated per-post violations.',
            'columns' => ['Trigger', 'Process step'],
            'rows' => [
                ['Confirmed misconduct on post', 'Admin drafts NTE'],
                ['Attendance anomalies tied to post', 'Supervisor relays notice to guard'],
                ['Policy violation substantiated', 'Guard submits written explanation + proof'],
                ['Operational negligence repeat', 'Admin evaluates response'],
                ['Final outcome', 'Disciplinary action recorded; registry updated'],
            ],
        ],
        [
            'id' => 'vw-escalation',
            'title' => 'Escalation thresholds (offense frequency)',
            'intro' => 'Repeated per-post offenses receive progressively stronger action.',
            'columns' => ['Offense frequency', 'Suggested action', 'Severity'],
            'rows' => [
                ['1st offense', 'Verbal warning', 'Low'],
                ['2nd offense', 'Written warning', 'Moderate'],
                ['3rd offense', 'Suspension (pending policy)', 'High'],
                ['4th offense', 'Final written warning', 'Severe'],
                ['5th offense', 'Termination review', 'Critical'],
            ],
        ],
        [
            'id' => 'vw-principles',
            'title' => 'System design principles',
            'intro' => 'How the registry and workflow were designed.',
            'columns' => ['Principle', 'Purpose'],
            'rows' => [
                ['Accountability', 'Every action has an assigned role'],
                ['Traceability', 'Operations history logs status and notes'],
                ['Validation', 'Incidents require proof before closure'],
                ['Standardization', 'Same process across all posts'],
                ['Escalation', 'Serious or repeat cases prioritized'],
                ['Transparency', 'Admin decisions visible in history'],
                ['Automation', 'AI assists repetitive monitoring (optional)'],
                ['Auditability', 'Exports and historical review supported'],
            ],
        ],
        [
            'id' => 'vw-severity',
            'title' => 'Severity levels',
            'intro' => 'Registry uses High / Medium / Low; map operational criticality as follows.',
            'columns' => ['Level', 'Description', 'Registry'],
            'rows' => [
                ['Low', 'Minor operational or uniform issue', 'Low'],
                ['Medium', 'Requires supervisor intervention', 'Medium'],
                ['High', 'Requires admin escalation', 'High'],
                ['Critical', 'Emergency or termination-level conduct', 'High + immediate admin'],
            ],
        ],
        [
            'id' => 'vw-status-map',
            'title' => 'Status tracking (workflow ↔ registry)',
            'intro' => 'Align investigation language with registry statuses.',
            'columns' => ['Workflow term', 'Registry status', 'Meaning'],
            'rows' => [
                ['Pending review', 'Ongoing (new)', 'Awaiting admin validation'],
                ['Under investigation', 'Ongoing', 'Evidence and interviews in progress'],
                ['Awaiting evidence', 'On hold', 'Additional proof requested'],
                ['Escalated', 'Ongoing + ops note', 'Elevated to management'],
                ['Resolved', 'Accomplished', 'Formally closed successfully'],
                ['Rejected', 'Denied', 'Invalid or duplicate report'],
            ],
        ],
        [
            'id' => 'vw-audit-log',
            'title' => 'Timestamp & action logging',
            'intro' => 'Every registry update should capture (via history and export):',
            'columns' => ['Field', 'Source'],
            'rows' => [
                ['Date / time', 'Submitted and updated timestamps on report'],
                ['User / role', 'Head guard submitter; admin editor on save'],
                ['Action performed', 'Status change, ops note, field edits in history'],
                ['Post / site', 'Assigned post on report record'],
            ],
        ],
        [
            'id' => 'vw-evidence-rules',
            'title' => 'Evidence management rules',
            'intro' => 'Minimum standards for per-post violation closure.',
            'columns' => ['Rule', 'Detail'],
            'rows' => [
                ['Minimum proof', 'At least one supporting artifact for substantiated violations'],
                ['Multiple attachments', 'Encourage photos + log excerpt where available'],
                ['Tampering', 'Preserve originals; document tampering as separate type'],
                ['Retention', 'Keep uploads immutable after admin lock / closure'],
            ],
        ],
        [
            'id' => 'vw-wording',
            'title' => 'Admin phrasing standards',
            'intro' => 'Use consistent language in operations history.',
            'columns' => ['Instead of', 'Use'],
            'rows' => [
                ['Admin asks for valid evidence', 'Admin requests additional supporting evidence due to insufficient verification'],
                ['Admin declares if incident is resolved', 'Admin evaluates all submitted evidence and determines whether the incident can be formally resolved or requires further investigation'],
            ],
        ],
        [
            'id' => 'vw-objective',
            'title' => 'Workflow objective',
            'intro' => 'Centralized, traceable, evidence-driven per-post violation management.',
            'columns' => ['Outcome'],
            'rows' => [
                ['Centralized incident management across posts'],
                ['Traceable disciplinary system with offense history'],
                ['Evidence-driven reporting — not opinion-only closures'],
                ['AI-assisted monitoring where deployed'],
                ['Standardized security operations at every assigned post'],
            ],
        ],
    ];

    return admin_incident_apply_status_terminology_sections($sections);
}

/** @return list<string> */
function admin_incident_violation_workflow_section_ids(): array
{
    return array_map(
        static fn (array $s): string => (string) $s['id'],
        admin_incident_violation_workflow_sections()
    );
}
