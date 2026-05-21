<?php
declare(strict_types=1);

require_once __DIR__ . '/document_ai.php';
require_once __DIR__ . '/auth.php';

const GUARD_DAD_REF_PREFIX = 'DAD';
const GUARD_DAD_STATUS_PENDING = 'pending';

function guard_dad_table_exists(PDO $conn): bool
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $cached = db_table_exists($conn, 'guard_dad_submissions');

    return $cached;
}

function guard_dad_is_report_type(string $reportType): bool
{
    return $reportType === 'Daily Attendance Document';
}

function guard_dad_has_dual_location_columns(PDO $conn): bool
{
    return db_column_exists($conn, 'guard_dad_submissions', 'sheet_latitude');
}

/**
 * @return array{lat:?float,lng:?float,accuracy:?float,label:string}
 */
function guard_dad_parse_location_payload(array $payload, string $prefix): array
{
    $latKey = $prefix . '_latitude';
    $lngKey = $prefix . '_longitude';
    $accKey = $prefix . '_accuracy_m';
    $labelKey = $prefix === 'submit' ? 'location_label' : $prefix . '_location_label';

    return [
        'lat' => isset($payload[$latKey]) && $payload[$latKey] !== '' ? (float) $payload[$latKey] : null,
        'lng' => isset($payload[$lngKey]) && $payload[$lngKey] !== '' ? (float) $payload[$lngKey] : null,
        'accuracy' => isset($payload[$accKey]) && $payload[$accKey] !== '' ? (float) $payload[$accKey] : null,
        'label' => trim((string) ($payload[$labelKey] ?? '')),
    ];
}

/**
 * @param array{lat:?float,lng:?float,accuracy:?float,label:string} $sheet
 * @param array{lat:?float,lng:?float,accuracy:?float,label:string} $evidence
 */
function guard_dad_format_location_history_note(array $sheet, array $evidence): string
{
    $parts = [];
    if ($sheet['label'] !== '' || ($sheet['lat'] !== null && $sheet['lng'] !== null)) {
        $parts[] = 'Sheet: ' . ($sheet['label'] !== '' ? $sheet['label'] : guard_dad_format_coords($sheet));
    }
    if ($evidence['label'] !== '' || ($evidence['lat'] !== null && $evidence['lng'] !== null)) {
        $parts[] = 'Evidence: ' . ($evidence['label'] !== '' ? $evidence['label'] : guard_dad_format_coords($evidence));
    }

    return implode(' · ', $parts);
}

/**
 * @param array{lat:?float,lng:?float,accuracy:?float,label:string} $loc
 */
function guard_dad_format_coords(array $loc): string
{
    if ($loc['lat'] === null || $loc['lng'] === null) {
        return '—';
    }
    $text = sprintf('%.6f, %.6f', $loc['lat'], $loc['lng']);
    if ($loc['accuracy'] !== null) {
        $text .= ' (±' . round($loc['accuracy'], 1) . ' m)';
    }

    return $text;
}

function guard_dad_location_block_html(string $title, ?float $lat, ?float $lng, ?float $accuracy, string $label): string
{
    if ($lat === null && $lng === null && $label === '') {
        return '';
    }

    $html = '<div class="reports-detail-about"><h4 class="reports-detail-about__title">' . e($title) . '</h4>';
    if ($label !== '') {
        $html .= '<p>' . e($label) . '</p>';
    }
    if ($lat !== null && $lng !== null) {
        $html .= '<p class="mono">' . e(sprintf('%.6f, %.6f', $lat, $lng));
        if ($accuracy !== null) {
            $html .= ' <span class="reports-optional">(±' . e((string) round($accuracy, 1)) . ' m)</span>';
        }
        $html .= '</p>';
        $mapUrl = 'https://www.google.com/maps?q=' . rawurlencode($lat . ',' . $lng);
        $html .= '<p><a href="' . e($mapUrl) . '" target="_blank" rel="noopener noreferrer">Open in Google Maps</a></p>';
    }
    $html .= '</div>';

    return $html;
}

/**
 * @param array<string, mixed> $structured
 * @return array{issue:string,time_record:string,recorded:string,guard_name:string,guard_id:string,summary:string,shift_date:string,shift_display:string}
 */
function guard_dad_fields_from_ocr(array $structured, string $postName): array
{
    $issue = 'roster_review';
    $timeRecord = '';
    $recorded = 'missing';
    $guardName = '';
    $guardId = '';
    $shiftDate = date('Y-m-d');
    $shiftDisplay = date('j M Y') . ' — Day shift';

    $rows = $structured['attendance_rows'] ?? [];
    if (is_array($rows) && $rows !== []) {
        $first = $rows[0];
        if (is_array($first)) {
            $guardName = trim((string) ($first['name'] ?? ''));
            $tin = trim((string) ($first['time_in'] ?? ''));
            $tout = trim((string) ($first['time_out'] ?? ''));
            if ($tin === '' && $tout !== '') {
                $issue = 'missing_time_in';
                $timeRecord = 'No time-in; time-out ' . $tout;
            } elseif ($tout === '' && $tin !== '') {
                $issue = 'missing_time_out';
                $timeRecord = 'Time-in ' . $tin . '; no time-out';
            } elseif ($tin === '' && $tout === '') {
                $issue = 'missing_time_in';
                $timeRecord = 'No punches logged for listed guard';
            } else {
                $timeRecord = 'Time-in ' . $tin . '; time-out ' . $tout;
                $recorded = 'present';
            }
        }
        if (count($rows) > 1) {
            $timeRecord .= ' (+' . (count($rows) - 1) . ' more on sheet)';
        }
    }

    $dates = $structured['dates'] ?? [];
    if (is_array($dates) && isset($dates[0]) && trim((string) $dates[0]) !== '') {
        $parsed = strtotime((string) $dates[0]);
        if ($parsed !== false) {
            $shiftDate = date('Y-m-d', $parsed);
            $shiftDisplay = date('j M Y', $parsed) . ' — Day shift';
        }
    }

    $ocrPost = trim((string) ($structured['post'] ?? ''));
    if ($ocrPost !== '') {
        $postName = $ocrPost;
    }

    $summary = 'Daily attendance sheet submitted from the field.';
    if ($timeRecord !== '') {
        $summary = $timeRecord;
    }

    return [
        'issue' => $issue,
        'time_record' => $timeRecord !== '' ? $timeRecord : 'See uploaded attendance sheet',
        'recorded' => $recorded,
        'guard_name' => $guardName,
        'guard_id' => $guardId,
        'summary' => $summary,
        'shift_date' => $shiftDate,
        'shift_display' => $shiftDisplay,
        'post_name' => $postName,
    ];
}

function guard_dad_head_guard_display_name(PDO $conn, string $companyId): string
{
    if ($companyId === '') {
        return 'Head guard';
    }
    $roleCol = auth_users_role_column($conn);
    $hasUserNames = auth_users_has_profile_names($conn);
    $nameSelect = $hasUserNames
        ? "COALESCE(NULLIF(TRIM(u.First_Name), ''), g.First_Name) AS first_name,
           COALESCE(NULLIF(TRIM(u.Last_Name), ''), g.Last_Name) AS last_name"
        : 'g.First_Name AS first_name, g.Last_Name AS last_name';

    $row = db_fetch_one(
        $conn,
        "SELECT u.Email AS email, {$nameSelect}
         FROM users u
         LEFT JOIN guards g ON g.Company_ID = u.Company_ID
         WHERE u.Company_ID = ? AND u.{$roleCol} = ?
         LIMIT 1",
        'si',
        [$companyId, AUTH_ROLE_GUARD]
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

function guard_dad_next_reference(PDO $conn): string
{
    $year = date('Y');
    $row = db_fetch_one(
        $conn,
        "SELECT reference_code FROM guard_dad_submissions
         WHERE reference_code LIKE ?
         ORDER BY dad_id DESC LIMIT 1",
        's',
        [GUARD_DAD_REF_PREFIX . '-' . $year . '-%']
    );
    $seq = 1;
    if ($row !== null && preg_match('/-(\d+)$/', (string) ($row['reference_code'] ?? ''), $m)) {
        $seq = (int) $m[1] + 1;
    }

    return GUARD_DAD_REF_PREFIX . '-' . $year . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
}

/**
 * @param array<string, mixed> $payload
 * @return array{ok:bool,error?:string,dad_id?:int,reference?:string}
 */
function guard_dad_create_submission(
    PDO $conn,
    string $headGuardCompanyId,
    string $postName,
    int $dgdReportNumber,
    string $ivB64,
    ?string $scanPathCipher,
    ?string $aiCipher,
    array $payload
): array {
    if (!guard_dad_table_exists($conn)) {
        return ['ok' => false, 'error' => 'DAD registry is not available. Run database migrations.'];
    }

    $structured = is_array($payload['structured'] ?? null) ? $payload['structured'] : [];
    $fields = guard_dad_fields_from_ocr($structured, $postName);

    $sheet = guard_dad_parse_location_payload($payload, 'sheet');
    $evidence = guard_dad_parse_location_payload($payload, 'evidence');
    if ($evidence['lat'] === null && isset($payload['submit_latitude'])) {
        $evidence = guard_dad_parse_location_payload($payload, 'submit');
        $evidence['label'] = trim((string) ($payload['location_label'] ?? $evidence['label']));
    }

    $headName = guard_dad_head_guard_display_name($conn, $headGuardCompanyId);
    $reference = guard_dad_next_reference($conn);
    $submittedAt = date('Y-m-d H:i:s');
    $historyNote = guard_dad_format_location_history_note($sheet, $evidence);
    $history = [
        [
            'at' => date('j M Y, H:i'),
            'event' => 'Submitted by head guard',
            'note' => $historyNote !== '' ? $historyNote : 'Submitted via guard portal',
        ],
    ];

    $dualLocation = guard_dad_has_dual_location_columns($conn);
    if ($dualLocation) {
        $ok = db_execute(
            $conn,
            'INSERT INTO guard_dad_submissions (
                reference_code, dgd_report_number, head_guard_company_id, head_guard_name,
                post_name, shift_date, shift_display, guard_id, guard_name, issue, time_record,
                recorded, status, summary, scan_path_cipher, ai_extracted_cipher, iv,
                submit_latitude, submit_longitude, submit_accuracy_m, location_label,
                sheet_latitude, sheet_longitude, sheet_accuracy_m, sheet_location_label,
                evidence_latitude, evidence_longitude, evidence_accuracy_m, evidence_location_label,
                history_json, submitted_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            'sissssssssssssssdddsdddsdddss',
            [
                $reference,
                $dgdReportNumber > 0 ? $dgdReportNumber : null,
                $headGuardCompanyId,
                $headName,
                (string) $fields['post_name'],
                (string) $fields['shift_date'],
                (string) $fields['shift_display'],
                (string) $fields['guard_id'] !== '' ? (string) $fields['guard_id'] : null,
                (string) $fields['guard_name'] !== '' ? (string) $fields['guard_name'] : null,
                (string) $fields['issue'],
                (string) $fields['time_record'],
                (string) $fields['recorded'],
                GUARD_DAD_STATUS_PENDING,
                (string) $fields['summary'],
                $scanPathCipher,
                $aiCipher !== '' ? $aiCipher : null,
                $ivB64,
                $evidence['lat'],
                $evidence['lng'],
                $evidence['accuracy'],
                $evidence['label'] !== '' ? $evidence['label'] : null,
                $sheet['lat'],
                $sheet['lng'],
                $sheet['accuracy'],
                $sheet['label'] !== '' ? $sheet['label'] : null,
                $evidence['lat'],
                $evidence['lng'],
                $evidence['accuracy'],
                $evidence['label'] !== '' ? $evidence['label'] : null,
                json_encode($history, JSON_THROW_ON_ERROR),
                $submittedAt,
            ]
        );
    } else {
        $ok = db_execute(
            $conn,
            'INSERT INTO guard_dad_submissions (
                reference_code, dgd_report_number, head_guard_company_id, head_guard_name,
                post_name, shift_date, shift_display, guard_id, guard_name, issue, time_record,
                recorded, status, summary, scan_path_cipher, ai_extracted_cipher, iv,
                submit_latitude, submit_longitude, submit_accuracy_m, location_label,
                history_json, submitted_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            'sissssssssssssssdddsss',
            [
                $reference,
                $dgdReportNumber > 0 ? $dgdReportNumber : null,
                $headGuardCompanyId,
                $headName,
                (string) $fields['post_name'],
                (string) $fields['shift_date'],
                (string) $fields['shift_display'],
                (string) $fields['guard_id'] !== '' ? (string) $fields['guard_id'] : null,
                (string) $fields['guard_name'] !== '' ? (string) $fields['guard_name'] : null,
                (string) $fields['issue'],
                (string) $fields['time_record'],
                (string) $fields['recorded'],
                GUARD_DAD_STATUS_PENDING,
                (string) $fields['summary'],
                $scanPathCipher,
                $aiCipher !== '' ? $aiCipher : null,
                $ivB64,
                $evidence['lat'],
                $evidence['lng'],
                $evidence['accuracy'],
                $evidence['label'] !== '' ? $evidence['label'] : null,
                json_encode($history, JSON_THROW_ON_ERROR),
                $submittedAt,
            ]
        );
    }

    if (!$ok) {
        error_log('guard_dad_create_submission INSERT failed for ' . $reference);

        return ['ok' => false, 'error' => 'Could not register DAD submission. Check database logs or run migrations.'];
    }

    $dadId = db_last_insert_id($conn);

    return [
        'ok' => true,
        'dad_id' => $dadId,
        'reference' => $reference,
    ];
}

/**
 * @return list<array<string, mixed>>
 */
function guard_dad_fetch_admin_records(PDO $conn): array
{
    if (!guard_dad_table_exists($conn)) {
        return [];
    }

    $rows = db_fetch_all(
        $conn,
        'SELECT * FROM guard_dad_submissions ORDER BY submitted_at DESC'
    );
    $out = [];
    foreach ($rows as $row) {
        $mapped = guard_dad_map_row_for_admin($row);
        if ($mapped !== null) {
            $out[] = $mapped;
        }
    }

    return $out;
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>|null
 */
function guard_dad_map_row_for_admin(array $row): ?array
{
    $dadId = (int) ($row['dad_id'] ?? 0);
    if ($dadId <= 0) {
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
    $media = guard_dad_resolve_submission_media($row);

    return [
        'id' => 'dad-' . $dadId,
        'dad_id' => $dadId,
        'dgd_report_number' => (int) ($row['dgd_report_number'] ?? 0),
        'ref' => (string) ($row['reference_code'] ?? ''),
        'guard_id' => (string) ($row['guard_id'] ?? ''),
        'guard_name' => (string) ($row['guard_name'] ?? '—'),
        'head_guard_id' => (string) ($row['head_guard_company_id'] ?? ''),
        'head_guard_name' => (string) ($row['head_guard_name'] ?? ''),
        'post' => (string) ($row['post_name'] ?? ''),
        'shift_date' => (string) ($row['shift_date'] ?? ''),
        'shift_display' => (string) ($row['shift_display'] ?? ''),
        'issue' => (string) ($row['issue'] ?? 'roster_review'),
        'time_record' => (string) ($row['time_record'] ?? ''),
        'recorded' => (string) ($row['recorded'] ?? 'missing'),
        'status' => (string) ($row['status'] ?? GUARD_DAD_STATUS_PENDING),
        'summary' => (string) ($row['summary'] ?? ''),
        'submitted_at' => substr($submittedAt, 0, 10),
        'submitted_display' => $submittedAt !== '' ? date('j M Y, H:i', strtotime($submittedAt) ?: time()) : '',
        'updated_at' => substr($updatedAt, 0, 10),
        'updated_display' => $updatedAt !== '' ? date('j M Y, H:i', strtotime($updatedAt) ?: time()) : '',
        'location_label' => (string) ($row['location_label'] ?? ''),
        'submit_latitude' => $row['submit_latitude'] ?? null,
        'submit_longitude' => $row['submit_longitude'] ?? null,
        'submit_accuracy_m' => $row['submit_accuracy_m'] ?? null,
        'sheet_latitude' => $row['sheet_latitude'] ?? null,
        'sheet_longitude' => $row['sheet_longitude'] ?? null,
        'sheet_accuracy_m' => $row['sheet_accuracy_m'] ?? null,
        'sheet_location_label' => (string) ($row['sheet_location_label'] ?? ''),
        'evidence_latitude' => $row['evidence_latitude'] ?? $row['submit_latitude'] ?? null,
        'evidence_longitude' => $row['evidence_longitude'] ?? $row['submit_longitude'] ?? null,
        'evidence_accuracy_m' => $row['evidence_accuracy_m'] ?? $row['submit_accuracy_m'] ?? null,
        'evidence_location_label' => (string) ($row['evidence_location_label'] ?? $row['location_label'] ?? ''),
        'has_scan' => trim((string) ($row['scan_path_cipher'] ?? '')) !== '' || $media['scan_url'] !== '',
        'scan_url' => $media['scan_url'],
        'ocr_formatted' => $media['ocr_formatted'],
        'ocr_raw' => $media['ocr_raw'],
        'history' => $history,
        '_source' => 'database',
    ];
}

/**
 * Public URL for an attendance sheet image (served via admin API — uploads/guard is not web-accessible).
 */
function guard_dad_scan_url(int $dadId): string
{
    return app_url('admin/api/dad-scan.php') . '?dad=' . $dadId;
}

function guard_dad_guard_uploads_root(): string
{
    $root = realpath(APP_ROOT . '/uploads/guard');

    return $root !== false ? str_replace('\\', '/', $root) : '';
}

function guard_dad_is_safe_guard_upload_path(string $absolutePath): bool
{
    $uploadsRoot = guard_dad_guard_uploads_root();
    if ($uploadsRoot === '') {
        return false;
    }

    $real = realpath($absolutePath);
    if ($real === false || !is_file($real)) {
        return false;
    }

    $normalized = str_replace('\\', '/', $real);
    $prefix = rtrim($uploadsRoot, '/') . '/';

    return str_starts_with($normalized, $prefix);
}

function guard_dad_mime_for_path(string $absolutePath): string
{
    if (function_exists('mime_content_type')) {
        $detected = mime_content_type($absolutePath);
        if (is_string($detected) && $detected !== '' && str_starts_with($detected, 'image/')) {
            return $detected;
        }
    }

    $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

    return match ($ext) {
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'heic', 'heif' => 'image/heic',
        default => 'image/jpeg',
    };
}

/**
 * Resolve on-disk path for a DAD attendance sheet scan.
 */
function guard_dad_resolve_scan_absolute_path(PDO $conn, int $dadId): ?string
{
    global $master_key, $cipher_algo;

    if ($dadId <= 0) {
        return null;
    }

    $row = db_fetch_one($conn, 'SELECT * FROM guard_dad_submissions WHERE dad_id = ? LIMIT 1', 'i', [$dadId]);
    if ($row === null) {
        return null;
    }

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

    $dgd = db_fetch_one(
        $conn,
        'SELECT Template_Path, iv FROM dgd WHERE Report_Number = ? LIMIT 1',
        'i',
        [$dgdId]
    );
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
 * Stream attendance sheet image to the browser (admin-authenticated endpoint).
 */
function guard_dad_stream_scan(PDO $conn, int $dadId): void
{
    $absolutePath = guard_dad_resolve_scan_absolute_path($conn, $dadId);
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

/**
 * Decrypt scan path and OCR text for admin display.
 *
 * @param array<string, mixed> $row
 * @return array{scan_url:string,ocr_formatted:string,ocr_raw:string}
 */
function guard_dad_resolve_submission_media(array $row): array
{
    global $master_key, $cipher_algo;

    $dadId = (int) ($row['dad_id'] ?? 0);
    $scanUrl = '';
    $ocrFormatted = '';
    $ocrRaw = '';
    $conn = isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof PDO ? $GLOBALS['conn'] : null;

    if ($dadId > 0 && $conn !== null && guard_dad_resolve_scan_absolute_path($conn, $dadId) !== null) {
        $scanUrl = guard_dad_scan_url($dadId);
    }

    $ivB64 = (string) ($row['iv'] ?? '');
    $iv = base64_decode($ivB64, true) ?: '';

    if ($iv !== '' && isset($master_key, $cipher_algo)) {
        $aiCipher = trim((string) ($row['ai_extracted_cipher'] ?? ''));
        if ($aiCipher !== '') {
            $stored = openssl_decrypt($aiCipher, (string) $cipher_algo, (string) $master_key, 0, $iv) ?: '';
            if ($stored !== '') {
                $decoded = document_ai_decode_stored($stored);
                $ocrFormatted = (string) ($decoded['formatted'] ?? '');
                $ocrRaw = (string) ($decoded['raw'] ?? '');
                if ($ocrFormatted === '' && $ocrRaw !== '') {
                    $ocrFormatted = $ocrRaw;
                }
            }
        }
    }

    $dgdId = (int) ($row['dgd_report_number'] ?? 0);
    if ($ocrFormatted === '' && $dgdId > 0 && $conn !== null) {
        $dgd = db_fetch_one(
            $conn,
            'SELECT AI_Extracted_Text, iv FROM dgd WHERE Report_Number = ? LIMIT 1',
            'i',
            [$dgdId]
        );
        if ($dgd !== null && isset($master_key, $cipher_algo)) {
            $dgdIv = base64_decode((string) ($dgd['iv'] ?? ''), true) ?: '';
            if ($dgdIv !== '') {
                $aiCipher = trim((string) ($dgd['AI_Extracted_Text'] ?? ''));
                if ($aiCipher !== '') {
                    $stored = openssl_decrypt($aiCipher, (string) $cipher_algo, (string) $master_key, 0, $dgdIv) ?: '';
                    if ($stored !== '') {
                        $decoded = document_ai_decode_stored($stored);
                        $ocrFormatted = (string) ($decoded['formatted'] ?? '');
                        $ocrRaw = (string) ($decoded['raw'] ?? '');
                        if ($ocrFormatted === '' && $ocrRaw !== '') {
                            $ocrFormatted = $ocrRaw;
                        }
                    }
                }
            }
        }
    }

    return [
        'scan_url' => $scanUrl,
        'ocr_formatted' => $ocrFormatted,
        'ocr_raw' => $ocrRaw,
    ];
}

/**
 * HTML block for scan + OCR in admin modal / JS view.
 *
 * @param array<string, mixed> $record
 */
function guard_dad_submission_media_html(array $record): string
{
    $html = '<div class="reports-dad-media">';
    $scanUrl = (string) ($record['scan_url'] ?? '');
    if ($scanUrl !== '') {
        $html .= '<section class="reports-dad-media__section">';
        $html .= '<h4 class="reports-dad-media__title"><i class="fa-solid fa-file-image" aria-hidden="true"></i> Attendance sheet (step 1)</h4>';
        $html .= '<a href="' . e($scanUrl) . '" target="_blank" rel="noopener noreferrer" class="reports-dad-media__link">';
        $html .= '<img class="reports-dad-media__scan" src="' . e($scanUrl) . '" alt="Uploaded attendance sheet">';
        $html .= '</a></section>';
    }

    $ocr = trim((string) ($record['ocr_formatted'] ?? $record['ocr_raw'] ?? ''));
    if ($ocr !== '') {
        $html .= '<section class="reports-dad-media__section">';
        $html .= '<h4 class="reports-dad-media__title"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> Extracted handwriting (Document AI)</h4>';
        $html .= '<pre class="reports-dad-media__ocr">' . e($ocr) . '</pre>';
        $html .= '</section>';
    } elseif ($scanUrl !== '') {
        $html .= '<p class="reports-dad-media__hint">No OCR text stored yet. Re-run Document AI from the scan if needed.</p>';
    }

    $html .= '</div>';

    return $html;
}

function guard_dad_find_by_id(PDO $conn, string $id): ?array
{
    if (!preg_match('/^dad-(\d+)$/', $id, $m)) {
        return null;
    }
    $row = db_fetch_one($conn, 'SELECT * FROM guard_dad_submissions WHERE dad_id = ? LIMIT 1', 'i', [(int) $m[1]]);
    if ($row === null) {
        return null;
    }

    return guard_dad_map_row_for_admin($row);
}

/**
 * Remove encrypted scan file from disk when present.
 *
 * @param array<string, mixed> $row
 */
function guard_dad_unlink_scan_file(array $row): void
{
    global $master_key, $cipher_algo;

    $ivB64 = (string) ($row['iv'] ?? '');
    $iv = base64_decode($ivB64, true) ?: '';
    if ($iv === '' || !isset($master_key, $cipher_algo)) {
        return;
    }

    $pathCipher = trim((string) ($row['scan_path_cipher'] ?? ''));
    if ($pathCipher === '') {
        return;
    }

    $rel = openssl_decrypt($pathCipher, (string) $cipher_algo, (string) $master_key, 0, $iv) ?: '';
    if ($rel === '') {
        return;
    }

    $abs = APP_ROOT . '/' . ltrim(str_replace('\\', '/', $rel), '/');
    if (is_file($abs)) {
        @unlink($abs);
    }
}

/**
 * @return array{ok:bool,error?:string,ref?:string}
 */
function guard_dad_delete_submission(PDO $conn, int $dadId): array
{
    if (!guard_dad_table_exists($conn) || $dadId <= 0) {
        return ['ok' => false, 'error' => 'DAD registry unavailable.'];
    }

    $row = db_fetch_one(
        $conn,
        'SELECT reference_code, scan_path_cipher, iv FROM guard_dad_submissions WHERE dad_id = ? LIMIT 1',
        'i',
        [$dadId]
    );
    if ($row === null) {
        return ['ok' => false, 'error' => 'Record not found.'];
    }

    $ref = (string) ($row['reference_code'] ?? '');
    guard_dad_unlink_scan_file($row);

    if (!db_execute($conn, 'DELETE FROM guard_dad_submissions WHERE dad_id = ? LIMIT 1', 'i', [$dadId])) {
        return ['ok' => false, 'error' => 'Could not delete record.'];
    }

    return ['ok' => true, 'ref' => $ref];
}

/**
 * @param array<string, mixed> $record
 */
function guard_dad_modal_extras_html(PDO $conn, array $record): string
{
    $html = guard_dad_submission_media_html($record);
    $html .= guard_dad_location_block_html(
        'Location — attendance sheet (step 1)',
        isset($record['sheet_latitude']) ? (float) $record['sheet_latitude'] : null,
        isset($record['sheet_longitude']) ? (float) $record['sheet_longitude'] : null,
        isset($record['sheet_accuracy_m']) ? (float) $record['sheet_accuracy_m'] : null,
        (string) ($record['sheet_location_label'] ?? '')
    );
    $html .= guard_dad_location_block_html(
        'Location — site evidence (step 2)',
        isset($record['evidence_latitude']) ? (float) $record['evidence_latitude'] : (isset($record['submit_latitude']) ? (float) $record['submit_latitude'] : null),
        isset($record['evidence_longitude']) ? (float) $record['evidence_longitude'] : (isset($record['submit_longitude']) ? (float) $record['submit_longitude'] : null),
        isset($record['evidence_accuracy_m']) ? (float) $record['evidence_accuracy_m'] : (isset($record['submit_accuracy_m']) ? (float) $record['submit_accuracy_m'] : null),
        (string) ($record['evidence_location_label'] ?? $record['location_label'] ?? '')
    );

    return $html;
}
