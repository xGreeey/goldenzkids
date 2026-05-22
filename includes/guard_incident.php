<?php
declare(strict_types=1);

require_once __DIR__ . '/document_ai.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/admin_incident_status.php';

const GUARD_INCIDENT_REF_PREFIX = 'INC';
const GUARD_INCIDENT_CATEGORY_PER_POST = 'per_post';
const GUARD_INCIDENT_CATEGORY_OUTSIDE_POST = 'outside_post';
/** Canonical guard portal label for incident report submissions. */
const GUARD_INCIDENT_REPORT_TYPE = 'Incident Report';
/** Legacy value stored on older dgd rows before rename. */
const GUARD_INCIDENT_REPORT_TYPE_LEGACY = 'Post incident';

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
    return in_array(trim($reportType), [GUARD_INCIDENT_REPORT_TYPE, GUARD_INCIDENT_REPORT_TYPE_LEGACY, 'Incident'], true);
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function guard_incident_structured_from_row(array $row): array
{
    global $master_key, $cipher_algo;

    $ivB64 = (string) ($row['iv'] ?? '');
    $iv = base64_decode($ivB64, true) ?: '';
    if ($iv === '' || !isset($master_key, $cipher_algo)) {
        return [];
    }

    $aiCipher = trim((string) ($row['ai_extracted_cipher'] ?? ''));
    if ($aiCipher === '') {
        return [];
    }

    $stored = openssl_decrypt($aiCipher, (string) $cipher_algo, (string) $master_key, 0, $iv) ?: '';
    if ($stored === '') {
        return [];
    }

    $decoded = document_ai_decode_stored($stored);
    $structured = is_array($decoded['structured'] ?? null) ? $decoded['structured'] : [];
    if (($structured['template'] ?? '') === 'incident_report') {
        $structured['raw'] = (string) ($decoded['raw'] ?? $structured['raw'] ?? '');

        return document_ai_enrich_incident_structured($structured);
    }

    return $structured;
}

function guard_incident_subject_from_summary(string $summary): string
{
    if (preg_match('/^Subject:\s*(.+)$/mu', $summary, $m)) {
        return document_ai_sanitize_incident_name(trim((string) $m[1]));
    }

    return '';
}

/**
 * Public URL for the head-guard upload (served via admin API — uploads/guard is not web-accessible).
 */
function guard_incident_scan_url(int $incId): string
{
    return app_url('admin/api/incident-scan.php') . '?inc=' . $incId;
}

/**
 * Resolve on-disk path for a post-incident form scan.
 */
function guard_incident_resolve_scan_absolute_path(PDO $conn, int $incId): ?string
{
    if ($incId <= 0) {
        return null;
    }

    $row = db_fetch_one($conn, 'SELECT * FROM guard_incident_submissions WHERE inc_id = ? LIMIT 1', 'i', [$incId]);
    if ($row === null) {
        return null;
    }

    return guard_incident_resolve_scan_path_from_row($conn, $row);
}

/**
 * Stream uploaded incident form image to the browser (admin-authenticated endpoint).
 */
function guard_incident_stream_scan(PDO $conn, int $incId): void
{
    require_once __DIR__ . '/guard_dad.php';

    $absolutePath = guard_incident_resolve_scan_absolute_path($conn, $incId);
    if ($absolutePath === null) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Scan not found.';
        exit;
    }

    $mime = guard_dad_mime_for_path($absolutePath);
    $fileSize = filesize($absolutePath);
    if ($fileSize === false) {
        http_response_code(500);
        exit;
    }

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . (string) $fileSize);
    header('Cache-Control: private, max-age=300');
    header('X-Content-Type-Options: nosniff');
    readfile($absolutePath);
    exit;
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
    if (($structured['template'] ?? '') === 'incident_report') {
        $structured = document_ai_enrich_incident_structured($structured);
    }

    $extract = is_array($structured['extraction'] ?? null)
        ? $structured['extraction']
        : document_ai_incident_extraction_json($structured);

    $description = trim((string) ($extract['incident_description'] ?? $structured['incident_description'] ?? ''));
    $action = trim((string) ($extract['action_taken'] ?? $structured['action_taken'] ?? ''));
    $name = trim((string) ($extract['name_of_guard'] ?? $structured['name'] ?? ''));
    $post = trim((string) ($extract['post'] ?? $structured['post'] ?? ''));
    $date = trim((string) ($structured['date'] ?? ''));

    $incidentType = guard_incident_derive_type_label($description);
    $severity = guard_incident_infer_severity($description . ' ' . $action);

    $summaryParts = [];
    if ($date !== '') {
        $summaryParts[] = 'Incident date (form): ' . $date;
    }
    if ($name !== '') {
        $summaryParts[] = 'Subject: ' . $name;
    }
    if ($post !== '') {
        $summaryParts[] = 'Post (form): ' . $post;
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

    require_once __DIR__ . '/admin_incident_pipeline.php';

    $fields = guard_incident_fields_from_ocr($structured, $siteName);
    $guardSubject = trim((string) ($structured['name'] ?? ''));
    $classification = admin_incident_classify_from_content(
        $fields['incident_description'],
        $fields['action_taken'],
        $guardSubject
    );
    $fields['incident_type'] = $classification['incident_type'];
    $fields['category'] = $classification['category'];
    $fields['severity'] = $classification['severity'];
    $fields['summary'] = admin_incident_build_list_summary($classification, $guardSubject);

    $category = trim((string) ($payload['category'] ?? $fields['category']));
    if (!in_array($category, [GUARD_INCIDENT_CATEGORY_PER_POST, GUARD_INCIDENT_CATEGORY_OUTSIDE_POST], true)) {
        $category = admin_incident_category_normalize($category);
    }
    $fields['category'] = $category;

    $lat = isset($payload['submit_latitude']) && $payload['submit_latitude'] !== '' ? (float) $payload['submit_latitude'] : null;
    $lng = isset($payload['submit_longitude']) && $payload['submit_longitude'] !== '' ? (float) $payload['submit_longitude'] : null;
    $acc = isset($payload['submit_accuracy_m']) && $payload['submit_accuracy_m'] !== '' ? (float) $payload['submit_accuracy_m'] : null;
    $label = trim((string) ($payload['location_label'] ?? $payload['evidence_location_label'] ?? ''));

    $headName = guard_incident_head_guard_display_name($conn, $headGuardCompanyId);
    $reference = guard_incident_next_reference($conn);
    $submittedAt = date('Y-m-d H:i:s');
    $at = date('j M Y, H:i');
    $history = admin_incident_initial_operation_flow(
        $fields,
        $classification,
        $headName,
        $guardSubject,
        $label,
        $at
    );

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
            json_encode($history, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?: '[]',
            $submittedAt,
        ]
    );

    if (!$ok) {
        error_log('guard_incident_create_submission INSERT failed for ' . $reference);

        return ['ok' => false, 'error' => 'Could not register incident report. Check database logs or run migrations.'];
    }

    require_once __DIR__ . '/portal_audit.php';
    portal_audit_log(
        $conn,
        'INCIDENT_SUBMITTED',
        'Reference: ' . $reference,
        $headGuardCompanyId,
        $headGuardCompanyId,
        AUTH_ROLE_GUARD
    );

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

    $structured = guard_incident_structured_from_row($row);
    // Prefer stored column text as-is (admin edits); only sanitize OCR fallback.
    $incidentDescription = trim((string) ($row['incident_description'] ?? ''));
    if ($incidentDescription === '') {
        $incidentDescription = document_ai_sanitize_incident_handwriting(
            (string) ($structured['incident_description'] ?? '')
        );
    }
    $actionTaken = trim((string) ($row['action_taken'] ?? ''));
    if ($actionTaken === '') {
        $actionTaken = document_ai_sanitize_incident_handwriting(
            (string) ($structured['action_taken'] ?? '')
        );
    }
    $formName = document_ai_sanitize_incident_name((string) ($structured['name'] ?? ''));
    if ($formName === '') {
        $formName = guard_incident_subject_from_summary((string) ($row['summary'] ?? ''));
    }
    $formDate = document_ai_sanitize_incident_date((string) ($structured['date'] ?? ''));
    $formPost = document_ai_sanitize_dad_post((string) ($structured['post'] ?? ''));

    $conn = isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof PDO ? $GLOBALS['conn'] : null;
    $media = $conn instanceof PDO
        ? guard_incident_resolve_submission_media($conn, $row)
        : ['scan_url' => '', 'attachments' => [], 'has_attachments' => false];
    if ($media['scan_url'] === '' && $conn instanceof PDO && guard_incident_resolve_scan_absolute_path($conn, $incId) !== null) {
        $media['scan_url'] = guard_incident_scan_url($incId);
    }

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
        'incident_description' => $incidentDescription,
        'action_taken' => $actionTaken,
        'form_name' => $formName,
        'form_date' => $formDate,
        'form_post' => $formPost,
        'person_involved' => admin_incident_person_from_report([
            'person_involved' => '',
            'history' => $history,
        ]),
        'has_scan' => $media['scan_url'] !== '',
        'submitted_at' => substr($submittedAt, 0, 10),
        'submitted_display' => $submittedAt !== '' ? date('j M Y, H:i', strtotime($submittedAt) ?: time()) : '',
        'updated_at' => substr($updatedAt, 0, 10),
        'updated_display' => $updatedAt !== '' ? date('j M Y, H:i', strtotime($updatedAt) ?: time()) : '',
        'history' => $history,
        'scan_url' => $media['scan_url'],
        'attachments' => $media['attachments'],
        'has_attachments' => $media['has_attachments'],
        '_source' => 'database',
    ];
}

function guard_incident_evidence_url(int $incId, int $evidenceId): string
{
    return app_url('admin/api/incident-evidence.php') . '?inc=' . $incId . '&ev=' . $evidenceId;
}

/**
 * @param array<string, mixed> $row guard_incident_submissions row
 */
function guard_incident_resolve_scan_path_from_row(PDO $conn, array $row): ?string
{
    require_once __DIR__ . '/guard_dad.php';

    global $master_key, $cipher_algo;

    $ivB64 = (string) ($row['iv'] ?? '');
    $iv = base64_decode($ivB64, true) ?: '';
    if ($iv === '' || !isset($master_key, $cipher_algo)) {
        return null;
    }

    $pathCipher = trim((string) ($row['scan_path_cipher'] ?? ''));
    if ($pathCipher !== '') {
        $rel = openssl_decrypt($pathCipher, (string) $cipher_algo, (string) $master_key, 0, $iv) ?: '';
        if ($rel !== '') {
            $abs = APP_ROOT . '/' . ltrim(str_replace('\\', '/', $rel), '/');
            if (guard_dad_is_safe_guard_upload_path($abs)) {
                return realpath($abs) ?: null;
            }
        }
    }

    $dgdId = (int) ($row['dgd_report_number'] ?? 0);
    if ($dgdId <= 0) {
        return null;
    }

    $dgd = db_fetch_one($conn, 'SELECT Template_Path, iv FROM dgd WHERE Report_Number = ? LIMIT 1', 'i', [$dgdId]);
    if ($dgd === null) {
        return null;
    }

    $dgdIv = base64_decode((string) ($dgd['iv'] ?? ''), true) ?: '';
    if ($dgdIv === '') {
        return null;
    }

    $rel = openssl_decrypt((string) ($dgd['Template_Path'] ?? ''), (string) $cipher_algo, (string) $master_key, 0, $dgdIv) ?: '';
    if ($rel === '') {
        return null;
    }

    $abs = APP_ROOT . '/' . ltrim(str_replace('\\', '/', $rel), '/');
    if (!guard_dad_is_safe_guard_upload_path($abs)) {
        return null;
    }

    return realpath($abs) ?: null;
}

/**
 * @return list<array{type: string, label: string, url: string, id?: int}>
 */
function guard_incident_fetch_evidence_attachments(PDO $conn, array $row): array
{
    if (!db_table_exists($conn, 'guard_report_evidence')) {
        return [];
    }

    $incId = (int) ($row['inc_id'] ?? 0);
    $dgdReportNumber = (int) ($row['dgd_report_number'] ?? 0);
    if ($incId <= 0 || $dgdReportNumber <= 0) {
        return [];
    }

    $stmt = db_query(
        $conn,
        'SELECT id FROM guard_report_evidence WHERE report_number = ? ORDER BY id ASC',
        'i',
        [$dgdReportNumber]
    );
    if ($stmt === false) {
        return [];
    }

    $out = [];
    $n = 0;
    while ($evRow = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!is_array($evRow)) {
            continue;
        }
        $evId = (int) ($evRow['id'] ?? 0);
        if ($evId <= 0) {
            continue;
        }
        ++$n;
        $out[] = [
            'type' => 'evidence',
            'label' => $n === 1 ? 'Evidence photo' : 'Evidence photo ' . $n,
            'url' => guard_incident_evidence_url($incId, $evId),
            'id' => $evId,
        ];
    }

    return $out;
}

/**
 * @param array<string, mixed> $row
 * @return array{scan_url: string, attachments: list<array{type: string, label: string, url: string, id?: int}>, has_attachments: bool}
 */
function guard_incident_resolve_submission_media(PDO $conn, array $row): array
{
    $incId = (int) ($row['inc_id'] ?? 0);
    $scanUrl = '';
    $attachments = [];

    if ($incId > 0 && guard_incident_resolve_scan_path_from_row($conn, $row) !== null) {
        $scanUrl = guard_incident_scan_url($incId);
        $attachments[] = [
            'type' => 'scan',
            'label' => 'Incident form',
            'url' => $scanUrl,
        ];
    }

    foreach (guard_incident_fetch_evidence_attachments($conn, $row) as $evidence) {
        $attachments[] = $evidence;
    }

    return [
        'scan_url' => $scanUrl,
        'attachments' => $attachments,
        'has_attachments' => $attachments !== [],
    ];
}

function guard_incident_stream_evidence(PDO $conn, int $incId, int $evidenceId): void
{
    require_once __DIR__ . '/guard_dad.php';

    global $master_key, $cipher_algo;

    if ($incId <= 0 || $evidenceId <= 0) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Invalid request.';
        exit;
    }

    $incRow = db_fetch_one($conn, 'SELECT * FROM guard_incident_submissions WHERE inc_id = ? LIMIT 1', 'i', [$incId]);
    if ($incRow === null) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Incident not found.';
        exit;
    }

    $dgdReportNumber = (int) ($incRow['dgd_report_number'] ?? 0);
    if ($dgdReportNumber <= 0) {
        http_response_code(404);
        exit;
    }

    $evRow = db_fetch_one(
        $conn,
        'SELECT id, file_name FROM guard_report_evidence WHERE id = ? AND report_number = ? LIMIT 1',
        'ii',
        [$evidenceId, $dgdReportNumber]
    );
    if ($evRow === null) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Evidence not found.';
        exit;
    }

    $ivB64 = (string) ($incRow['iv'] ?? '');
    $iv = base64_decode($ivB64, true) ?: '';
    if ($iv === '' || !isset($master_key, $cipher_algo)) {
        http_response_code(500);
        exit;
    }

    $pathCipher = trim((string) ($evRow['file_name'] ?? ''));
    $rel = openssl_decrypt($pathCipher, (string) $cipher_algo, (string) $master_key, 0, $iv) ?: '';
    if ($rel === '') {
        http_response_code(404);
        exit;
    }

    $abs = APP_ROOT . '/' . ltrim(str_replace('\\', '/', $rel), '/');
    if (!guard_dad_is_safe_guard_upload_path($abs) || !is_file($abs)) {
        http_response_code(404);
        exit;
    }

    $encrypted = file_get_contents($abs);
    if ($encrypted === false || $encrypted === '') {
        http_response_code(404);
        exit;
    }

    $bytes = openssl_decrypt($encrypted, (string) $cipher_algo, (string) $master_key, 0, $iv);
    if ($bytes === false || $bytes === '') {
        http_response_code(500);
        exit;
    }

    $mime = 'image/jpeg';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo !== false) {
            $detected = finfo_buffer($finfo, $bytes);
            finfo_close($finfo);
            if (is_string($detected) && str_starts_with($detected, 'image/')) {
                $mime = $detected;
            }
        }
    }

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . (string) strlen($bytes));
    header('Cache-Control: private, max-age=300');
    header('X-Content-Type-Options: nosniff');
    echo $bytes;
    exit;
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

/** Head-guard history label aligned with admin incident registry status. */
function guard_incident_guard_portal_status(string $adminStatus): string
{
    if (!admin_incident_status_is_valid($adminStatus)) {
        return 'Pending';
    }

    return admin_incident_status_label($adminStatus);
}

/** Keep linked dgd row in sync so guard report history reflects registry status. */
function guard_incident_sync_dgd_portal_status(PDO $conn, int $dgdReportNumber, string $adminStatus): void
{
    if ($dgdReportNumber <= 0) {
        return;
    }

    db_execute(
        $conn,
        'UPDATE dgd SET Status = ? WHERE Report_Number = ? LIMIT 1',
        'si',
        [guard_incident_guard_portal_status($adminStatus), $dgdReportNumber]
    );
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
    $progressionOnly = !empty($input['progression_only']);

    $category = (string) $mapped['category'];
    $incidentType = (string) $mapped['incident_type'];
    $site = (string) $mapped['site'];
    $summary = (string) $mapped['summary'];
    $severity = (string) ($mapped['severity'] ?? 'Medium');

    if (!$progressionOnly) {
        $category = trim((string) ($input['category'] ?? $category));
        if (!in_array($category, [GUARD_INCIDENT_CATEGORY_PER_POST, GUARD_INCIDENT_CATEGORY_OUTSIDE_POST], true)) {
            $category = (string) $mapped['category'];
        }
        $incidentType = trim((string) ($input['incident_type'] ?? $incidentType));
        $site = trim((string) ($input['site'] ?? $site));
        $summary = trim((string) ($input['summary'] ?? $summary));
        $severity = trim((string) ($input['severity'] ?? $severity));
        if (!in_array($severity, ['High', 'Medium', 'Low'], true)) {
            $severity = (string) ($mapped['severity'] ?? 'Medium');
        }
    }

    $status = (string) ($input['status'] ?? $oldStatus);
    if (!admin_incident_status_is_valid($status)) {
        $status = $oldStatus;
    }

    $editHistoryIndex = trim((string) ($input['edit_history_index'] ?? ''));
    $historyRows = is_array($input['history_row'] ?? null) ? $input['history_row'] : [];

    if ($progressionOnly) {
        require_once __DIR__ . '/admin_incident_pipeline.php';

        $newRowInput = is_array($historyRows['new'] ?? null) ? $historyRows['new'] : null;
        unset($historyRows['new']);

        if ($historyRows !== []) {
            $mapped = admin_incident_apply_history_rows_edit($mapped, $historyRows, $actorId);
        }
        if ($newRowInput !== null) {
            $mapped = admin_incident_apply_history_new_row($mapped, $newRowInput, $actorId);
        }

        $status = (string) ($input['status'] ?? $mapped['status'] ?? ADMIN_INCIDENT_STATUS_ONGOING);
        if (!admin_incident_status_is_valid($status)) {
            $status = (string) ($mapped['status'] ?? ADMIN_INCIDENT_STATUS_ONGOING);
        }
        $statusBefore = (string) $mapped['status'];
        if ($status !== $statusBefore) {
            $mapped['status'] = $status;
            $mapped = admin_incident_append_history(
                $mapped,
                'Registry: ' . admin_incident_status_label($status),
                'Registry status updated.',
                $actorId,
                ['source' => 'admin', 'kind' => 'status']
            );
        }

        require_once __DIR__ . '/admin_incident_reports.php';
        $mapped = admin_incident_apply_report_body_edit($mapped, $input, $actorId);

        $mapped = admin_incident_touch_updated($mapped);
        $mapped['status'] = admin_incident_reconcile_status($mapped);
        $status = (string) $mapped['status'];
        $incidentDescription = trim((string) ($mapped['incident_description'] ?? ''));
        $actionTaken = trim((string) ($mapped['action_taken'] ?? ''));
        $summary = trim((string) ($mapped['summary'] ?? ''));
        $historyJson = json_encode($mapped['history'] ?? [], JSON_THROW_ON_ERROR);

        $ok = db_execute(
            $conn,
            'UPDATE guard_incident_submissions SET status = ?, incident_description = ?, action_taken = ?, summary = ?, history_json = ?, updated_at = NOW() WHERE inc_id = ? LIMIT 1',
            'sssssi',
            [
                $status,
                $incidentDescription !== '' ? $incidentDescription : null,
                $actionTaken !== '' ? $actionTaken : null,
                $summary !== '' ? $summary : null,
                $historyJson,
                $incId,
            ]
        );
        if (!$ok) {
            return null;
        }

        guard_incident_sync_dgd_portal_status($conn, (int) ($row['dgd_report_number'] ?? 0), $status);

        $ref = (string) ($row['reference_code'] ?? ('inc-' . $incId));
        require_once __DIR__ . '/portal_audit.php';
        portal_audit_log(
            $conn,
            'INCIDENT_UPDATED',
            'Reference: ' . $ref . '; report text or progression saved',
            (string) ($row['head_guard_company_id'] ?? ''),
            $actorId,
            auth_user_role()
        );

        $refreshed = guard_incident_find_by_id($conn, 'inc-' . $incId);

        return $refreshed !== null ? admin_incident_normalize($refreshed) : null;
    }

    if ($editHistoryIndex !== '' && $progressionOnly) {
        $updated = admin_incident_update_history_entry($mapped, [
            'status' => $status,
            'ops_note' => trim((string) ($input['ops_note'] ?? '')),
            'edit_history_index' => $editHistoryIndex,
        ], $actorId);
        if ($updated === null) {
            return null;
        }
        $historyJson = json_encode($updated['history'] ?? [], JSON_THROW_ON_ERROR);
        $ok = db_execute(
            $conn,
            'UPDATE guard_incident_submissions SET status = ?, history_json = ?, updated_at = NOW() WHERE inc_id = ? LIMIT 1',
            'ssi',
            [(string) $updated['status'], $historyJson, $incId]
        );
        if (!$ok) {
            return null;
        }

        guard_incident_sync_dgd_portal_status(
            $conn,
            (int) ($row['dgd_report_number'] ?? 0),
            (string) ($updated['status'] ?? $status)
        );

        $refreshed = guard_incident_find_by_id($conn, 'inc-' . $incId);

        return $refreshed !== null ? admin_incident_normalize($refreshed) : null;
    }

    $opsNote = trim((string) ($input['ops_note'] ?? ''));
    require_once __DIR__ . '/admin_incident_pipeline.php';
    $mapped = admin_incident_apply_progression_save($mapped, $input, $actorId);
    $status = (string) $mapped['status'];
    $statusChanged = $status !== $oldStatus;

    if (!$statusChanged && $opsNote === '') {
        $refreshed = guard_incident_find_by_id($conn, 'inc-' . $incId);

        return $refreshed !== null ? admin_incident_normalize($refreshed) : null;
    }

    $history = is_array($mapped['history'] ?? null) ? $mapped['history'] : [];
    $note = $opsNote;
    if ($note === '' && $statusChanged) {
        $note = 'Registry status updated.';
    }
    if ($note === '') {
        $note = 'Progression updated.';
    }
    $history[] = [
        'at' => date('j M Y, H:i'),
        'source' => 'admin',
        'kind' => $statusChanged ? 'status' : 'note',
        'event' => $statusChanged ? 'Registry: ' . admin_incident_status_label($status) : 'Operations response',
        'note' => $note,
        'actor' => $actorId,
    ];
    $mapped['history'] = $history;
    $mapped['status'] = admin_incident_reconcile_status($mapped);
    $status = (string) $mapped['status'];

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

    guard_incident_sync_dgd_portal_status($conn, (int) ($row['dgd_report_number'] ?? 0), $status);

    $ref = (string) ($row['reference_code'] ?? ('inc-' . $incId));
    require_once __DIR__ . '/portal_audit.php';
    portal_audit_log(
        $conn,
        'INCIDENT_UPDATED',
        'Reference: ' . $ref . ($status !== $oldStatus ? '; status → ' . $status : ''),
        (string) ($row['head_guard_company_id'] ?? ''),
        $actorId,
        auth_user_role()
    );

    $refreshed = guard_incident_find_by_id($conn, 'inc-' . $incId);

    return $refreshed !== null ? admin_incident_normalize($refreshed) : null;
}

/**
 * Permanently remove an incident submission from the registry.
 *
 * @return array{id:string,ref:string}|null
 */
function guard_incident_admin_delete(PDO $conn, int $incId): ?array
{
    if (!guard_incident_table_exists($conn)) {
        return null;
    }

    $row = db_fetch_one(
        $conn,
        'SELECT inc_id, reference_code FROM guard_incident_submissions WHERE inc_id = ? LIMIT 1',
        'i',
        [$incId]
    );
    if ($row === null) {
        return null;
    }

    $id = 'inc-' . $incId;
    $ref = (string) ($row['reference_code'] ?? $id);
    $ok = db_execute(
        $conn,
        'DELETE FROM guard_incident_submissions WHERE inc_id = ? LIMIT 1',
        'i',
        [$incId]
    );
    if (!$ok) {
        return null;
    }

    return ['id' => $id, 'ref' => $ref];
}
