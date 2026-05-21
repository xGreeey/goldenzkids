<?php
declare(strict_types=1);

require_once __DIR__ . '/document_ai.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/admin_incident_status.php';

const GUARD_INCIDENT_REF_PREFIX = 'INC';
const GUARD_INCIDENT_CATEGORY_PER_POST = 'per_post';
const GUARD_INCIDENT_CATEGORY_OUTSIDE_POST = 'outside_post';

function guard_incident_table_exists(PDO $conn): bool
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $cached = db_table_exists($conn, 'guard_incident_submissions');

    return $cached;
}

function guard_incident_is_report_type(string $reportType): bool
{
    return $reportType === 'Post incident';
}

function guard_incident_head_guard_display_name(PDO $conn, string $companyId): string
{
    if ($companyId === '') {
        return 'Head guard';
    }

    $roleCol = auth_users_role_column($conn);
    $row = db_fetch_one(
        $conn,
        "SELECT COALESCE(NULLIF(TRIM(u.First_Name), ''), g.First_Name) AS first_name,
                COALESCE(NULLIF(TRIM(u.Last_Name), ''), g.Last_Name) AS last_name,
                u.Email AS email
         FROM users u
         LEFT JOIN guards g ON g.Company_ID = u.Company_ID
         WHERE u.Company_ID = ? AND u.{$roleCol} = ?
         LIMIT 1",
        'si',
        [$companyId, 0]
    );

    if ($row === null) {
        return $companyId;
    }

    $first = trim((string) ($row['first_name'] ?? ''));
    $last = trim((string) ($row['last_name'] ?? ''));
    $label = trim($last . ($last !== '' && $first !== '' ? ', ' : '') . $first);
    if ($label !== '') {
        return $label;
    }

    $email = trim((string) ($row['email'] ?? ''));

    return $email !== '' ? $email : $companyId;
}

function guard_incident_next_reference(PDO $conn): string
{
    $year = date('Y');
    $row = db_fetch_one(
        $conn,
        'SELECT reference_code FROM guard_incident_submissions
         WHERE reference_code LIKE ?
         ORDER BY inc_id DESC LIMIT 1',
        's',
        [GUARD_INCIDENT_REF_PREFIX . '-' . $year . '-%']
    );
    $seq = 1;
    if ($row !== null && preg_match('/-(\d+)$/', (string) ($row['reference_code'] ?? ''), $m)) {
        $seq = (int) $m[1] + 1;
    }

    return GUARD_INCIDENT_REF_PREFIX . '-' . $year . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
}

/**
 * @param array<string, mixed> $structured
 * @return array{category:string,incident_type:string,severity:string,summary:string,incident_description:string,action_taken:string,site_name:string}
 */
function guard_incident_fields_from_ocr(array $structured, string $siteName): array
{
    $description = trim((string) ($structured['incident_description'] ?? ''));
    $action = trim((string) ($structured['action_taken'] ?? ''));
    $name = trim((string) ($structured['name'] ?? ''));
    $date = trim((string) ($structured['date'] ?? ''));

    if ($description === '' && $action !== '') {
        $description = $action;
        $action = '';
    }

    $incidentType = guard_incident_derive_type_label($description);
    $severity = guard_incident_infer_severity($description . ' ' . $action);

    $summaryParts = [];
    if ($date !== '') {
        $summaryParts[] = 'Incident date (form): ' . $date;
    }
    if ($name !== '') {
        $summaryParts[] = 'Subject: ' . $name;
    }
    if ($description !== '') {
        $summaryParts[] = $description;
    }
    if ($action !== '') {
        $summaryParts[] = 'Action taken: ' . $action;
    }
    $summary = implode("\n\n", $summaryParts);
    if ($summary === '') {
        $summary = 'Post incident report submitted from the field.';
    }

    return [
        'category' => GUARD_INCIDENT_CATEGORY_PER_POST,
        'incident_type' => $incidentType,
        'severity' => $severity,
        'summary' => $summary,
        'incident_description' => $description,
        'action_taken' => $action,
        'site_name' => $siteName,
    ];
}

function guard_incident_derive_type_label(string $description): string
{
    $description = trim(preg_replace('/\s+/u', ' ', $description) ?? $description);
    if ($description === '') {
        return 'Post incident report';
    }

    $firstLine = trim((string) (preg_split('/\r\n|\r|\n/u', $description)[0] ?? $description));
    if (strlen($firstLine) > 120) {
        $firstLine = substr($firstLine, 0, 117) . '…';
    }

    return $firstLine !== '' ? $firstLine : 'Post incident report';
}

function guard_incident_infer_severity(string $text): string
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

/**
 * @param array<string, mixed> $payload
 * @return array{ok:bool,error?:string,inc_id?:int,reference?:string}
 */
function guard_incident_create_submission(
    PDO $conn,
    string $headGuardCompanyId,
    string $siteName,
    int $dgdReportNumber,
    string $ivB64,
    ?string $scanPathCipher,
    ?string $aiCipher,
    array $payload
): array {
    if (!guard_incident_table_exists($conn)) {
        return ['ok' => false, 'error' => 'Incident registry is not available. Run database migrations.'];
    }

    $structured = is_array($payload['structured'] ?? null) ? $payload['structured'] : [];
    if ($structured === [] && isset($payload['ai_raw'])) {
        $decoded = document_ai_decode_stored((string) $payload['ai_raw']);
        $structured = is_array($decoded['structured'] ?? null) ? $decoded['structured'] : [];
    }

    $fields = guard_incident_fields_from_ocr($structured, $siteName);
    $category = trim((string) ($payload['category'] ?? $fields['category']));
    if (!in_array($category, [GUARD_INCIDENT_CATEGORY_PER_POST, GUARD_INCIDENT_CATEGORY_OUTSIDE_POST], true)) {
        $category = GUARD_INCIDENT_CATEGORY_PER_POST;
    }

    $lat = isset($payload['submit_latitude']) && $payload['submit_latitude'] !== '' ? (float) $payload['submit_latitude'] : null;
    $lng = isset($payload['submit_longitude']) && $payload['submit_longitude'] !== '' ? (float) $payload['submit_longitude'] : null;
    $acc = isset($payload['submit_accuracy_m']) && $payload['submit_accuracy_m'] !== '' ? (float) $payload['submit_accuracy_m'] : null;
    $label = trim((string) ($payload['location_label'] ?? $payload['evidence_location_label'] ?? ''));

    $headName = guard_incident_head_guard_display_name($conn, $headGuardCompanyId);
    $reference = guard_incident_next_reference($conn);
    $submittedAt = date('Y-m-d H:i:s');
    $history = [
        [
            'at' => date('j M Y, H:i'),
            'event' => 'Submitted by head guard',
            'note' => $label !== '' ? 'Location: ' . $label : 'Submitted via guard portal',
        ],
    ];

    $ok = db_execute(
        $conn,
        'INSERT INTO guard_incident_submissions (
            reference_code, dgd_report_number, head_guard_company_id, head_guard_name,
            category, incident_type, severity, site_name, status, summary,
            incident_description, action_taken, scan_path_cipher, ai_extracted_cipher, iv,
            submit_latitude, submit_longitude, submit_accuracy_m, location_label,
            history_json, submitted_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        'sisssssssssssssdddsss',
        [
            $reference,
            $dgdReportNumber > 0 ? $dgdReportNumber : null,
            $headGuardCompanyId,
            $headName,
            $category,
            $fields['incident_type'],
            $fields['severity'],
            $fields['site_name'],
            ADMIN_INCIDENT_STATUS_ONGOING,
            $fields['summary'],
            $fields['incident_description'] !== '' ? $fields['incident_description'] : null,
            $fields['action_taken'] !== '' ? $fields['action_taken'] : null,
            $scanPathCipher,
            $aiCipher !== '' ? $aiCipher : null,
            $ivB64,
            $lat,
            $lng,
            $acc,
            $label !== '' ? $label : null,
            json_encode($history, JSON_THROW_ON_ERROR),
            $submittedAt,
        ]
    );

    if (!$ok) {
        error_log('guard_incident_create_submission INSERT failed for ' . $reference);

        return ['ok' => false, 'error' => 'Could not register incident report. Check database logs or run migrations.'];
    }

    return [
        'ok' => true,
        'inc_id' => db_last_insert_id($conn),
        'reference' => $reference,
    ];
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>|null
 */
function guard_incident_map_row_for_admin(array $row): ?array
{
    $incId = (int) ($row['inc_id'] ?? 0);
    if ($incId <= 0) {
        return null;
    }

    $history = [];
    $historyJson = $row['history_json'] ?? null;
    if (is_string($historyJson) && $historyJson !== '') {
        $decoded = json_decode($historyJson, true);
        if (is_array($decoded)) {
            $history = $decoded;
        }
    }

    $submittedAt = (string) ($row['submitted_at'] ?? '');
    $updatedAt = (string) ($row['updated_at'] ?? $submittedAt);

    return [
        'id' => 'inc-' . $incId,
        'inc_id' => $incId,
        'ref' => (string) ($row['reference_code'] ?? ''),
        'category' => (string) ($row['category'] ?? GUARD_INCIDENT_CATEGORY_PER_POST),
        'incident_type' => (string) ($row['incident_type'] ?? ''),
        'severity' => (string) ($row['severity'] ?? 'Medium'),
        'site' => (string) ($row['site_name'] ?? ''),
        'head_guard_id' => (string) ($row['head_guard_company_id'] ?? ''),
        'head_guard_name' => (string) ($row['head_guard_name'] ?? ''),
        'status' => (string) ($row['status'] ?? ADMIN_INCIDENT_STATUS_ONGOING),
        'summary' => (string) ($row['summary'] ?? ''),
        'submitted_at' => substr($submittedAt, 0, 10),
        'submitted_display' => $submittedAt !== '' ? date('j M Y, H:i', strtotime($submittedAt) ?: time()) : '',
        'updated_at' => substr($updatedAt, 0, 10),
        'updated_display' => $updatedAt !== '' ? date('j M Y, H:i', strtotime($updatedAt) ?: time()) : '',
        'history' => $history,
        '_source' => 'database',
    ];
}

/** @return list<array<string, mixed>> */
function guard_incident_fetch_admin_records(PDO $conn): array
{
    if (!guard_incident_table_exists($conn)) {
        return [];
    }

    $stmt = db_query($conn, 'SELECT * FROM guard_incident_submissions ORDER BY submitted_at DESC');
    if ($stmt === false) {
        return [];
    }

    $out = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!is_array($row)) {
            continue;
        }
        $mapped = guard_incident_map_row_for_admin($row);
        if ($mapped !== null) {
            $out[] = $mapped;
        }
    }

    return $out;
}

function guard_incident_find_by_id(PDO $conn, string $id): ?array
{
    if (!preg_match('/^inc-(\d+)$/', $id, $m)) {
        return null;
    }

    $row = db_fetch_one($conn, 'SELECT * FROM guard_incident_submissions WHERE inc_id = ? LIMIT 1', 'i', [(int) $m[1]]);
    if ($row === null) {
        return null;
    }

    return guard_incident_map_row_for_admin($row);
}

/**
 * @param array<string, mixed> $input
 */
function guard_incident_admin_update(PDO $conn, int $incId, array $input, string $actorId): ?array
{
    if (!guard_incident_table_exists($conn)) {
        return null;
    }

    $row = db_fetch_one($conn, 'SELECT * FROM guard_incident_submissions WHERE inc_id = ? LIMIT 1', 'i', [$incId]);
    if ($row === null) {
        return null;
    }

    $mapped = guard_incident_map_row_for_admin($row);
    if ($mapped === null) {
        return null;
    }

    $oldStatus = (string) $mapped['status'];
    $category = trim((string) ($input['category'] ?? $mapped['category']));
    if (!in_array($category, [GUARD_INCIDENT_CATEGORY_PER_POST, GUARD_INCIDENT_CATEGORY_OUTSIDE_POST], true)) {
        $category = (string) $mapped['category'];
    }
    $status = (string) ($input['status'] ?? $oldStatus);
    if (!admin_incident_status_is_valid($status)) {
        $status = $oldStatus;
    }

    $incidentType = trim((string) ($input['incident_type'] ?? $mapped['incident_type']));
    $site = trim((string) ($input['site'] ?? $mapped['site']));
    $summary = trim((string) ($input['summary'] ?? $mapped['summary']));
    $severity = trim((string) ($input['severity'] ?? $mapped['severity']));
    if (!in_array($severity, ['High', 'Medium', 'Low'], true)) {
        $severity = 'Medium';
    }

    $opsNote = trim((string) ($input['ops_note'] ?? ''));
    $history = is_array($mapped['history'] ?? null) ? $mapped['history'] : [];
    $history[] = [
        'at' => date('j M Y, H:i'),
        'event' => $status !== $oldStatus ? 'Status: ' . admin_incident_status_label($status) : 'Updated by operations',
        'note' => $opsNote !== '' ? $opsNote : 'Incident details revised by ' . $actorId . '.',
        'actor' => $actorId,
    ];
    $mapped['history'] = $history;

    $historyJson = json_encode($mapped['history'] ?? [], JSON_THROW_ON_ERROR);

    $ok = db_execute(
        $conn,
        'UPDATE guard_incident_submissions SET
            category = ?, incident_type = ?, severity = ?, site_name = ?, status = ?,
            summary = ?, history_json = ?, updated_at = NOW()
         WHERE inc_id = ? LIMIT 1',
        'sssssssi',
        [$category, $incidentType, $severity, $site, $status, $summary, $historyJson, $incId]
    );

    if (!$ok) {
        return null;
    }

    return guard_incident_find_by_id($conn, 'inc-' . $incId);
}
