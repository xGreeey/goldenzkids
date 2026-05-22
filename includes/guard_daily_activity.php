<?php
declare(strict_types=1);

const GUARD_DAILY_ACTIVITY_TYPE = 'Daily Activity';
const GUARD_DAILY_ACTIVITY_REF_PREFIX = 'GDA';
const GUARD_DAILY_ACTIVITY_MAX_PHOTOS = 5;
const GUARD_DAILY_ACTIVITY_MODE_NORMAL = 'normal';
const GUARD_DAILY_ACTIVITY_MODE_EVENT = 'event';

function guard_daily_activity_table_exists(PDO $conn): bool
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $cached = db_table_exists($conn, 'guard_daily_activity_submissions');

    return $cached;
}

function guard_daily_activity_has_status_column(PDO $conn): bool
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    if (!guard_daily_activity_table_exists($conn)) {
        $cached = false;

        return $cached;
    }
    $row = db_fetch_one(
        $conn,
        "SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'guard_daily_activity_submissions'
           AND COLUMN_NAME = 'status'
         LIMIT 1"
    );
    $cached = $row !== null;

    return $cached;
}

function guard_daily_activity_is_report_type(string $reportType): bool
{
    return trim($reportType) === GUARD_DAILY_ACTIVITY_TYPE;
}

/** Head-guard report history label aligned with admin daily activity registry status. */
function guard_daily_activity_guard_portal_status(string $adminStatus): string
{
    require_once __DIR__ . '/admin_daily_activity_status.php';

    if (!admin_daily_activity_status_is_valid($adminStatus)) {
        return admin_daily_activity_status_label(ADMIN_DAILY_ACTIVITY_STATUS_PENDING);
    }

    return admin_daily_activity_status_label($adminStatus);
}

/** Keep linked dgd row in sync so guard report history reflects registry status. */
function guard_daily_activity_sync_dgd_portal_status(PDO $conn, int $dgdReportNumber, string $adminStatus): void
{
    if ($dgdReportNumber <= 0) {
        return;
    }

    db_execute(
        $conn,
        'UPDATE dgd SET Status = ? WHERE Report_Number = ? LIMIT 1',
        'si',
        [guard_daily_activity_guard_portal_status($adminStatus), $dgdReportNumber]
    );
}

/** Short label for registry table / admin summary (not the full guard textarea). */
function guard_daily_activity_list_summary(string $mode, string $details): string
{
    if ($mode === GUARD_DAILY_ACTIVITY_MODE_NORMAL) {
        return 'Normal operation — no additional details required';
    }

    $details = trim($details);
    if ($details === '') {
        return 'With event / activity';
    }

    $line = trim((string) (preg_split('/\R/u', $details, 2)[0] ?? $details));
    if (function_exists('mb_strlen') && mb_strlen($line) > 120) {
        return mb_substr($line, 0, 117) . '…';
    }
    if (strlen($line) > 120) {
        return substr($line, 0, 117) . '…';
    }

    return $line;
}

function guard_daily_activity_evidence_url(int $daId, int $evidenceId): string
{
    return app_url('admin/api/daily-activity-evidence.php') . '?da=' . $daId . '&ev=' . $evidenceId;
}

/**
 * Supporting photos from guard “Event / activity details” (linked via DGD report number).
 *
 * @param array<string, mixed> $row Needs da_id (or id da-N) and dgd_report_number
 * @return list<array{type: string, label: string, url: string, id?: int}>
 */
function guard_daily_activity_fetch_evidence_attachments(PDO $conn, array $row): array
{
    if (!db_table_exists($conn, 'guard_report_evidence')) {
        return [];
    }

    $daId = (int) ($row['da_id'] ?? 0);
    if ($daId <= 0 && preg_match('/^da-(\d+)$/', (string) ($row['id'] ?? ''), $m)) {
        $daId = (int) $m[1];
    }

    $dgdReportNumber = (int) ($row['dgd_report_number'] ?? 0);
    if ($daId <= 0 || $dgdReportNumber <= 0) {
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
            'label' => $n === 1 ? 'Supporting photo' : 'Supporting photo ' . $n,
            'url' => guard_daily_activity_evidence_url($daId, $evId),
            'id' => $evId,
        ];
    }

    return $out;
}

function guard_daily_activity_stream_evidence(PDO $conn, int $daId, int $evidenceId): void
{
    require_once __DIR__ . '/guard_dad.php';

    global $master_key, $cipher_algo;

    if ($daId <= 0 || $evidenceId <= 0) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Invalid request.';
        exit;
    }

    $daRow = db_fetch_one(
        $conn,
        'SELECT * FROM guard_daily_activity_submissions WHERE da_id = ? LIMIT 1',
        'i',
        [$daId]
    );
    if ($daRow === null) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Daily activity report not found.';
        exit;
    }

    $dgdReportNumber = (int) ($daRow['dgd_report_number'] ?? 0);
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

    $ivB64 = (string) ($daRow['iv'] ?? '');
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
    header('Cache-Control: private, max-age=3600');
    echo $bytes;
    exit;
}

/** @return list<array{name:string,tmp_name:string,error:int,size:int}> */
function guard_daily_activity_valid_photo_uploads(): array
{
    $rows = [];
    foreach (guard_portal_normalized_upload_files('daily_activity_photos') as $file) {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            continue;
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            continue;
        }
        $rows[] = $file;
    }

    return $rows;
}

function guard_daily_activity_head_guard_display_name(PDO $conn, string $companyId): string
{
    $row = db_fetch_one(
        $conn,
        'SELECT First_Name, Last_Name FROM users WHERE Company_ID = ? LIMIT 1',
        's',
        [$companyId]
    );
    if ($row === null) {
        return $companyId;
    }
    $name = trim((string) ($row['First_Name'] ?? '') . ' ' . (string) ($row['Last_Name'] ?? ''));

    return $name !== '' ? $name : $companyId;
}

function guard_daily_activity_next_reference(PDO $conn): string
{
    $year = date('Y');
    $row = db_fetch_one(
        $conn,
        'SELECT reference_code FROM guard_daily_activity_submissions
         WHERE reference_code LIKE ?
         ORDER BY da_id DESC LIMIT 1',
        's',
        [GUARD_DAILY_ACTIVITY_REF_PREFIX . '-' . $year . '-%']
    );
    $seq = 1;
    if ($row !== null && preg_match('/-(\d+)$/', (string) ($row['reference_code'] ?? ''), $m)) {
        $seq = (int) $m[1] + 1;
    }

    return GUARD_DAILY_ACTIVITY_REF_PREFIX . '-' . $year . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
}

/**
 * @return array{relative:string,absolute:string}|null
 */
function guard_daily_activity_placeholder_scan(string $uploadRoot, string $uploadsRelativePrefix): ?array
{
    $name = 'daily_activity_placeholder.jpg';
    $absolute = rtrim($uploadRoot, '/\\') . '/' . $name;
    if (!is_file($absolute)) {
        $bytes = base64_decode(
            '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDAREAAhEBAxEB/8QAFwABAQEBAAAAAAAAAAAAAAAABgcICf/EABUBAQEAAAAAAAAAAAAAAAAAAAAB/8QAFwEBAQEBAAAAAAAAAAAAAAAAAAECA//EABQBAQAAAAAAAAAAAAAAAAAAAAD/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCdA//Z',
            true
        );
        if ($bytes === false || file_put_contents($absolute, $bytes) === false) {
            return null;
        }
    }

    return [
        'relative' => rtrim($uploadsRelativePrefix, '/') . '/' . $name,
        'absolute' => $absolute,
    ];
}

/**
 * @return array{ok:bool,error?:string,reference?:string,report_number?:int,evidence_saved?:int}
 */
function guard_daily_activity_handle_submit(
    PDO $conn,
    string $companyId,
    string $establishment,
    string $reportType
): array {
    require_once __DIR__ . '/admin_daily_activity_status.php';

    if (!guard_daily_activity_is_report_type($reportType)) {
        return ['ok' => false, 'error' => 'Invalid report type.'];
    }

    $mode = trim((string) ($_POST['daily_activity_mode'] ?? ''));
    if (!in_array($mode, [GUARD_DAILY_ACTIVITY_MODE_NORMAL, GUARD_DAILY_ACTIVITY_MODE_EVENT], true)) {
        return ['ok' => false, 'error' => 'Select Normal Operation or With Event / Activity.'];
    }

    $details = trim((string) ($_POST['daily_activity_details'] ?? ''));
    if ($mode === GUARD_DAILY_ACTIVITY_MODE_EVENT) {
        if ($details === '') {
            return ['ok' => false, 'error' => 'Activity details are required for an event submission.'];
        }
        $photos = guard_daily_activity_valid_photo_uploads();
        if ($photos === []) {
            return ['ok' => false, 'error' => 'Add at least one supporting photo (up to 5).'];
        }
        if (count($photos) > GUARD_DAILY_ACTIVITY_MAX_PHOTOS) {
            return ['ok' => false, 'error' => 'You can upload at most 5 photos.'];
        }
    }

    global $master_key, $cipher_algo;

    $encEst = guard_portal_encrypt($establishment, (string) $master_key, (string) $cipher_algo);
    if ($encEst === null) {
        return ['ok' => false, 'error' => 'Could not secure report data.'];
    }

    $ivB64 = $encEst['iv'];
    $ivBinary = base64_decode($ivB64, true);
    if ($ivBinary === false || $ivBinary === '') {
        return ['ok' => false, 'error' => 'Could not secure report data.'];
    }

    $uploadRoot = APP_ROOT . '/uploads/guard/' . preg_replace('/[^A-Za-z0-9_-]/', '', $companyId);
    $uploadsRelativePrefix = 'uploads/guard/' . basename($uploadRoot);
    if (!is_dir($uploadRoot) && !mkdir($uploadRoot, 0755, true) && !is_dir($uploadRoot)) {
        return ['ok' => false, 'error' => 'Upload directory unavailable.'];
    }

    $placeholder = guard_daily_activity_placeholder_scan($uploadRoot, $uploadsRelativePrefix);
    if ($placeholder === null) {
        return ['ok' => false, 'error' => 'Could not prepare daily activity record.'];
    }

    $pathCipher = guard_portal_encrypt($placeholder['relative'], (string) $master_key, (string) $cipher_algo, $ivBinary);
    if ($pathCipher === null) {
        return ['ok' => false, 'error' => 'Could not secure file path.'];
    }

    $aiPayload = [
        'template' => 'daily_activity',
        'mode' => $mode,
        'activity_details' => $mode === GUARD_DAILY_ACTIVITY_MODE_EVENT ? $details : null,
    ];
    try {
        $aiStored = json_encode($aiPayload, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        return ['ok' => false, 'error' => 'Could not encode activity data.'];
    }

    $aiCipher = openssl_encrypt($aiStored, (string) $cipher_algo, (string) $master_key, 0, $ivBinary) ?: '';

    $time = date('Y-m-d H:i:s');
    $ok = db_execute(
        $conn,
        'INSERT INTO dgd (Company_ID, Establishment, Template_Path, Template, Time_of_Report, AI_Extracted_Text, iv, Status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        'ssssssss',
        [
            $companyId,
            $encEst['cipher'],
            $pathCipher['cipher'],
            GUARD_DAILY_ACTIVITY_TYPE,
            $time,
            $aiCipher,
            $ivB64,
            guard_daily_activity_guard_portal_status(ADMIN_DAILY_ACTIVITY_STATUS_PENDING),
        ]
    );

    if (!$ok) {
        return ['ok' => false, 'error' => 'Could not save report. Please try again.'];
    }

    $reportNumber = db_last_insert_id($conn);

    if (db_table_exists($conn, 'guard_duty_status')) {
        db_execute(
            $conn,
            'INSERT INTO guard_duty_status (company_id, duty_status) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE duty_status = VALUES(duty_status), updated_at = NOW()',
            'ss',
            [$companyId, 'on_report']
        );
    }

    $evidenceMeta = $_POST['daily_activity_photo_meta'] ?? [];
    if (!is_array($evidenceMeta)) {
        $evidenceMeta = $evidenceMeta !== '' ? [$evidenceMeta] : [];
    }

    $evidenceSaved = 0;
    if ($mode === GUARD_DAILY_ACTIVITY_MODE_EVENT) {
        $evidenceSaved = guard_portal_store_report_evidence(
            $conn,
            $reportNumber,
            $companyId,
            $uploadRoot,
            $uploadsRelativePrefix,
            $ivB64,
            (string) $master_key,
            (string) $cipher_algo,
            $evidenceMeta,
            'daily_activity_photos'
        );
        if ($evidenceSaved < 1) {
            return ['ok' => false, 'error' => 'Could not save activity photos. Try again.'];
        }
    }

    $lat = isset($_POST['submit_latitude']) && $_POST['submit_latitude'] !== '' ? (float) $_POST['submit_latitude'] : null;
    $lng = isset($_POST['submit_longitude']) && $_POST['submit_longitude'] !== '' ? (float) $_POST['submit_longitude'] : null;
    $acc = isset($_POST['submit_accuracy_m']) && $_POST['submit_accuracy_m'] !== '' ? (float) $_POST['submit_accuracy_m'] : null;
    $label = trim((string) ($_POST['location_label'] ?? ''));

    $detailsCipher = null;
    if ($mode === GUARD_DAILY_ACTIVITY_MODE_EVENT && $details !== '') {
        $detailsCipher = openssl_encrypt($details, (string) $cipher_algo, (string) $master_key, 0, $ivBinary) ?: null;
        if ($detailsCipher === null) {
            return ['ok' => false, 'error' => 'Could not secure activity details.'];
        }
    }

    $registry = guard_daily_activity_create_submission(
        $conn,
        $companyId,
        $establishment,
        $reportNumber,
        $ivB64,
        $pathCipher['cipher'],
        $aiCipher !== '' ? $aiCipher : null,
        $mode,
        $detailsCipher,
        $lat,
        $lng,
        $acc,
        $label !== '' ? $label : null
    );

    if (!$registry['ok']) {
        return ['ok' => false, 'error' => (string) ($registry['error'] ?? 'Could not register daily activity.')];
    }

    guard_daily_activity_sync_dgd_portal_status($conn, $reportNumber, ADMIN_DAILY_ACTIVITY_STATUS_PENDING);

    $reference = (string) ($registry['reference'] ?? 'pending');
    $message = $mode === GUARD_DAILY_ACTIVITY_MODE_NORMAL
        ? 'Daily activity (normal operation) submitted. Reference: ' . $reference . '.'
        : 'Daily activity with event submitted. Reference: ' . $reference . '.';
    if ($evidenceSaved > 0) {
        $message .= ' ' . $evidenceSaved . ' photo(s) saved (encrypted).';
    }

    return [
        'ok' => true,
        'message' => $message,
        'report_number' => $reportNumber,
        'evidence_saved' => $evidenceSaved,
        'daily_activity_reference' => $reference,
        'redirect' => 'submit-report.php?view=history',
    ];
}

/**
 * @return array{ok:bool,error?:string,da_id?:int,reference?:string}
 */
function guard_daily_activity_create_submission(
    PDO $conn,
    string $headGuardCompanyId,
    string $siteName,
    int $dgdReportNumber,
    string $ivB64,
    ?string $scanPathCipher,
    ?string $aiCipher,
    string $mode,
    ?string $activityDetailsCipher,
    ?float $lat,
    ?float $lng,
    ?float $accuracy,
    ?string $locationLabel
): array {
    require_once __DIR__ . '/admin_daily_activity_status.php';

    if (!guard_daily_activity_table_exists($conn)) {
        return ['ok' => false, 'error' => 'Daily activity registry is not available. Run database migrations.'];
    }

    $headName = guard_daily_activity_head_guard_display_name($conn, $headGuardCompanyId);
    $reference = guard_daily_activity_next_reference($conn);
    $submittedAt = date('Y-m-d H:i:s');
    $modeLabel = $mode === GUARD_DAILY_ACTIVITY_MODE_EVENT ? 'With event / activity' : 'Normal operation';
    $history = [
        [
            'at' => date('j M Y, H:i'),
            'event' => 'Submitted by head guard',
            'note' => 'Daily activity — ' . $modeLabel,
        ],
    ];

    try {
        $historyJson = json_encode($history, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        return ['ok' => false, 'error' => 'Could not save activity history.'];
    }

    $hasStatus = guard_daily_activity_has_status_column($conn);
    if ($hasStatus) {
        $ok = db_execute(
            $conn,
            'INSERT INTO guard_daily_activity_submissions (
                reference_code, dgd_report_number, head_guard_company_id, head_guard_name,
                site_name, activity_mode, status, activity_details_cipher, scan_path_cipher, ai_extracted_cipher, iv,
                submit_latitude, submit_longitude, submit_accuracy_m, location_label,
                history_json, submitted_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            'sisssssssssdddsss',
            [
                $reference,
                $dgdReportNumber > 0 ? $dgdReportNumber : null,
                $headGuardCompanyId,
                $headName,
                $siteName,
                $mode,
                ADMIN_DAILY_ACTIVITY_STATUS_PENDING,
                $activityDetailsCipher,
                $scanPathCipher,
                $aiCipher !== '' && $aiCipher !== null ? $aiCipher : null,
                $ivB64,
                $lat,
                $lng,
                $accuracy,
                $locationLabel,
                $historyJson,
                $submittedAt,
            ]
        );
    } else {
        $ok = db_execute(
            $conn,
            'INSERT INTO guard_daily_activity_submissions (
                reference_code, dgd_report_number, head_guard_company_id, head_guard_name,
                site_name, activity_mode, activity_details_cipher, scan_path_cipher, ai_extracted_cipher, iv,
                submit_latitude, submit_longitude, submit_accuracy_m, location_label,
                history_json, submitted_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            'sissssssssdddsss',
            [
                $reference,
                $dgdReportNumber > 0 ? $dgdReportNumber : null,
                $headGuardCompanyId,
                $headName,
                $siteName,
                $mode,
                $activityDetailsCipher,
                $scanPathCipher,
                $aiCipher !== '' && $aiCipher !== null ? $aiCipher : null,
                $ivB64,
                $lat,
                $lng,
                $accuracy,
                $locationLabel,
                $historyJson,
                $submittedAt,
            ]
        );
    }

    if (!$ok) {
        error_log('guard_daily_activity_create_submission INSERT failed for ' . $reference);

        return ['ok' => false, 'error' => 'Could not save daily activity record. Run database migrations (022 / 027).'];
    }

    return [
        'ok' => true,
        'da_id' => db_last_insert_id($conn),
        'reference' => $reference,
    ];
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>|null
 */
function guard_daily_activity_map_row_for_admin(array $row): ?array
{
    require_once __DIR__ . '/admin_daily_activity_status.php';

    global $master_key, $cipher_algo;

    $daId = (int) ($row['da_id'] ?? 0);
    if ($daId <= 0) {
        return null;
    }

    $ivB64 = (string) ($row['iv'] ?? '');
    $iv = base64_decode($ivB64, true);
    if ($iv === false || $iv === '') {
        return null;
    }

    $details = '';
    $detailsCipher = (string) ($row['activity_details_cipher'] ?? '');
    if ($detailsCipher !== '') {
        $details = openssl_decrypt($detailsCipher, (string) $cipher_algo, (string) $master_key, 0, $iv) ?: '';
    }

    $mode = (string) ($row['activity_mode'] ?? GUARD_DAILY_ACTIVITY_MODE_NORMAL);
    $modeLabel = $mode === GUARD_DAILY_ACTIVITY_MODE_EVENT ? 'With event / activity' : 'Normal operation';
    $details = trim($details);
    if ($mode !== GUARD_DAILY_ACTIVITY_MODE_EVENT) {
        $details = '';
    }
    $summary = guard_daily_activity_list_summary($mode, $details);

    $aiPayload = [];
    $aiCipher = (string) ($row['ai_extracted_cipher'] ?? '');
    if ($aiCipher !== '') {
        $aiRaw = openssl_decrypt($aiCipher, (string) $cipher_algo, (string) $master_key, 0, $iv) ?: '';
        if ($aiRaw !== '') {
            try {
                $decoded = json_decode($aiRaw, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    $aiPayload = $decoded;
                }
            } catch (JsonException) {
                $aiPayload = [];
            }
        }
    }

    $history = [];
    $historyJson = (string) ($row['history_json'] ?? '');
    if ($historyJson !== '') {
        try {
            $parsed = json_decode($historyJson, true, 512, JSON_THROW_ON_ERROR);
            if (is_array($parsed)) {
                $history = $parsed;
            }
        } catch (JsonException) {
            $history = [];
        }
    }

    $status = trim((string) ($row['status'] ?? ''));
    if ($status === '' || !admin_daily_activity_status_is_valid($status)) {
        $status = ADMIN_DAILY_ACTIVITY_STATUS_PENDING;
        foreach (array_reverse($history) as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $event = (string) ($entry['event'] ?? '');
            if (preg_match('/^Registry:\s*(.+)$/iu', $event, $m)) {
                $label = trim($m[1]);
                foreach (admin_daily_activity_status_definitions() as $slug => $def) {
                    if (strcasecmp((string) $def['label'], $label) === 0) {
                        $status = $slug;
                        break 2;
                    }
                }
            }
        }
    }

    $submittedAt = (string) ($row['submitted_at'] ?? '');
    $updatedAt = (string) ($row['updated_at'] ?? $submittedAt);

    return [
        'id' => 'da-' . $daId,
        'da_id' => $daId,
        'ref' => (string) ($row['reference_code'] ?? ''),
        'head_guard_id' => (string) ($row['head_guard_company_id'] ?? ''),
        'head_guard_name' => trim((string) ($row['head_guard_name'] ?? '')) ?: (string) ($row['head_guard_company_id'] ?? ''),
        'site_name' => (string) ($row['site_name'] ?? ''),
        'activity_mode' => $mode,
        'activity_mode_label' => $modeLabel,
        'summary' => $summary,
        'activity_details' => $mode === GUARD_DAILY_ACTIVITY_MODE_EVENT ? $details : '',
        'location_label' => (string) ($row['location_label'] ?? ''),
        'dgd_report_number' => $row['dgd_report_number'] ?? null,
        'status' => $status,
        'history' => $history,
        'submitted_at' => $submittedAt,
        'updated_at' => $updatedAt,
        'ai_payload' => $aiPayload,
    ];
}

/** @return list<array<string, mixed>> */
function guard_daily_activity_fetch_admin_records(PDO $conn): array
{
    if (!guard_daily_activity_table_exists($conn)) {
        return [];
    }

    require_once __DIR__ . '/admin_daily_activity_status.php';

    $stmt = db_query($conn, 'SELECT * FROM guard_daily_activity_submissions ORDER BY submitted_at DESC');
    if ($stmt === false) {
        return [];
    }

    $out = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!is_array($row)) {
            continue;
        }
        $mapped = guard_daily_activity_map_row_for_admin($row);
        if ($mapped !== null) {
            $out[] = $mapped;
        }
    }

    return $out;
}

function guard_daily_activity_find_by_id(PDO $conn, string $id): ?array
{
    if (!preg_match('/^da-(\d+)$/', $id, $m)) {
        return null;
    }

    $row = db_fetch_one(
        $conn,
        'SELECT * FROM guard_daily_activity_submissions WHERE da_id = ? LIMIT 1',
        'i',
        [(int) $m[1]]
    );
    if ($row === null) {
        return null;
    }

    return guard_daily_activity_map_row_for_admin($row);
}

/**
 * @param array<string, mixed> $input
 * @return array<string, mixed>|null
 */
function guard_daily_activity_admin_update(PDO $conn, int $daId, array $input, string $actorId): ?array
{
    require_once __DIR__ . '/admin_daily_activity_status.php';

    if (!guard_daily_activity_table_exists($conn) || $daId <= 0) {
        return null;
    }

    $row = db_fetch_one(
        $conn,
        'SELECT * FROM guard_daily_activity_submissions WHERE da_id = ? LIMIT 1',
        'i',
        [$daId]
    );
    if ($row === null) {
        return null;
    }

    $mapped = guard_daily_activity_map_row_for_admin($row);
    if ($mapped === null) {
        return null;
    }

    $oldStatus = (string) ($mapped['status'] ?? ADMIN_DAILY_ACTIVITY_STATUS_PENDING);
    $status = (string) ($input['status'] ?? $oldStatus);
    if (!admin_daily_activity_status_is_valid($status)) {
        $status = $oldStatus;
    }

    if ($status === $oldStatus) {
        return $mapped;
    }

    $history = is_array($mapped['history'] ?? null) ? $mapped['history'] : [];
    $history[] = [
        'at' => date('j M Y, H:i'),
        'event' => 'Registry: ' . admin_daily_activity_status_label($status),
        'note' => 'Status updated by admin.',
        'actor' => $actorId,
    ];
    $mapped['history'] = $history;
    $mapped['status'] = $status;

    try {
        $historyJson = json_encode($history, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        return null;
    }

    $hasStatus = guard_daily_activity_has_status_column($conn);
    if ($hasStatus) {
        $ok = db_execute(
            $conn,
            'UPDATE guard_daily_activity_submissions SET status = ?, history_json = ?, updated_at = NOW() WHERE da_id = ? LIMIT 1',
            'ssi',
            [$status, $historyJson, $daId]
        );
    } else {
        $ok = db_execute(
            $conn,
            'UPDATE guard_daily_activity_submissions SET history_json = ?, updated_at = NOW() WHERE da_id = ? LIMIT 1',
            'si',
            [$historyJson, $daId]
        );
    }
    if (!$ok) {
        return null;
    }

    guard_daily_activity_sync_dgd_portal_status(
        $conn,
        (int) ($row['dgd_report_number'] ?? 0),
        $status
    );

    $refreshed = guard_daily_activity_find_by_id($conn, 'da-' . $daId);

    return $refreshed;
}
