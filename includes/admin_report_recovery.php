<?php
declare(strict_types=1);

/**
 * Recovery vault — captures admin report delete/archive for superadmin restore.
 */

const ADMIN_REPORT_RECOVERY_KINDS = [
    'weekly-activity',
    'daily-activity',
    'dtr',
    'incident',
];

function admin_report_recovery_table_exists(PDO $conn): bool
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $row = db_fetch_one(
        $conn,
        "SELECT 1 AS ok FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = 'admin_report_recovery' LIMIT 1"
    );
    $cache = $row !== null;

    return $cache;
}

function admin_report_recovery_actor_id(): string
{
    return (string) ($_SESSION['company_id'] ?? 'system');
}

/**
 * @param array<string, mixed> $record
 * @param array<string, mixed>|null $dbRow
 */
function admin_report_recovery_log(
    string $kind,
    string $action,
    array $record,
    ?string $actorId = null,
    ?string $previousStatus = null,
    ?array $dbRow = null
): void {
    $kind = trim($kind);
    $action = trim($action);
    if (!in_array($kind, ADMIN_REPORT_RECOVERY_KINDS, true) || !in_array($action, ['deleted', 'archived'], true)) {
        return;
    }

    $recordId = trim((string) ($record['id'] ?? ''));
    if ($recordId === '') {
        return;
    }

    $payload = [
        'record' => $record,
        'previous_status' => $previousStatus,
        'db_row' => $dbRow,
    ];

    $actorId = $actorId !== null && $actorId !== '' ? $actorId : admin_report_recovery_actor_id();
    $ref = trim((string) ($record['ref'] ?? $recordId));

    if (!isset($GLOBALS['conn']) || !$GLOBALS['conn'] instanceof PDO) {
        admin_report_recovery_session_append($kind, $action, $recordId, $ref, $payload, $actorId);

        return;
    }

    $conn = $GLOBALS['conn'];
    if (!admin_report_recovery_table_exists($conn)) {
        admin_report_recovery_session_append($kind, $action, $recordId, $ref, $payload, $actorId);

        return;
    }

    db_execute(
        $conn,
        'INSERT INTO admin_report_recovery (
            report_kind, action_type, record_id, record_ref, payload_json, actor_company_id
        ) VALUES (?, ?, ?, ?, ?, ?)',
        'ssssss',
        [
            $kind,
            $action,
            $recordId,
            $ref,
            json_encode($payload, JSON_THROW_ON_ERROR),
            $actorId !== '' ? $actorId : null,
        ]
    );
}

/** @param array<string, mixed> $payload */
function admin_report_recovery_session_append(
    string $kind,
    string $action,
    string $recordId,
    string $ref,
    array $payload,
    string $actorId
): void {
    $key = 'admin_report_recovery_fallback';
    if (!isset($_SESSION[$key]) || !is_array($_SESSION[$key])) {
        $_SESSION[$key] = [];
    }

    $_SESSION[$key][] = [
        'recovery_id' => 'sess-' . bin2hex(random_bytes(6)),
        'report_kind' => $kind,
        'action_type' => $action,
        'record_id' => $recordId,
        'record_ref' => $ref,
        'payload' => $payload,
        'actor_company_id' => $actorId,
        'created_at' => date('Y-m-d H:i:s'),
        'restored_at' => null,
    ];
}

/**
 * @return list<array<string, mixed>>
 */
function admin_report_recovery_list(string $kind, ?string $actionFilter = null, bool $includeRestored = false): array
{
    $kind = trim($kind);
    if (!in_array($kind, ADMIN_REPORT_RECOVERY_KINDS, true)) {
        return [];
    }

    $rows = [];

    if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof PDO
        && admin_report_recovery_table_exists($GLOBALS['conn'])) {
        $conn = $GLOBALS['conn'];
        $sql = 'SELECT recovery_id, report_kind, action_type, record_id, record_ref,
                       payload_json, actor_company_id, created_at, restored_at
                FROM admin_report_recovery
                WHERE report_kind = ?';
        $types = 's';
        $params = [$kind];

        if ($actionFilter !== null && $actionFilter !== '' && in_array($actionFilter, ['deleted', 'archived'], true)) {
            $sql .= ' AND action_type = ?';
            $types .= 's';
            $params[] = $actionFilter;
        }
        if (!$includeRestored) {
            $sql .= ' AND restored_at IS NULL';
        }
        $sql .= ' ORDER BY created_at DESC';

        $result = db_fetch_all($conn, $sql, $types, $params);
        foreach ($result as $row) {
            $payload = json_decode((string) ($row['payload_json'] ?? '{}'), true);
            $rows[] = [
                'recovery_id' => (int) ($row['recovery_id'] ?? 0),
                'report_kind' => (string) ($row['report_kind'] ?? $kind),
                'action_type' => (string) ($row['action_type'] ?? ''),
                'record_id' => (string) ($row['record_id'] ?? ''),
                'record_ref' => (string) ($row['record_ref'] ?? ''),
                'payload' => is_array($payload) ? $payload : [],
                'actor_company_id' => (string) ($row['actor_company_id'] ?? ''),
                'created_at' => (string) ($row['created_at'] ?? ''),
                'restored_at' => $row['restored_at'] ?? null,
            ];
        }
    }

    $key = 'admin_report_recovery_fallback';
    if (isset($_SESSION[$key]) && is_array($_SESSION[$key])) {
        foreach ($_SESSION[$key] as $entry) {
            if (!is_array($entry) || (string) ($entry['report_kind'] ?? '') !== $kind) {
                continue;
            }
            if ($actionFilter !== null && $actionFilter !== ''
                && (string) ($entry['action_type'] ?? '') !== $actionFilter) {
                continue;
            }
            if (!$includeRestored && !empty($entry['restored_at'])) {
                continue;
            }
            $rows[] = [
                'recovery_id' => (string) ($entry['recovery_id'] ?? ''),
                'report_kind' => $kind,
                'action_type' => (string) ($entry['action_type'] ?? ''),
                'record_id' => (string) ($entry['record_id'] ?? ''),
                'record_ref' => (string) ($entry['record_ref'] ?? ''),
                'payload' => is_array($entry['payload'] ?? null) ? $entry['payload'] : [],
                'actor_company_id' => (string) ($entry['actor_company_id'] ?? ''),
                'created_at' => (string) ($entry['created_at'] ?? ''),
                'restored_at' => $entry['restored_at'] ?? null,
            ];
        }
    }

    usort($rows, static function (array $a, array $b): int {
        return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
    });

    return $rows;
}

/** Load admin report modules only when restoring (not for vault list pages). */
function admin_report_recovery_ensure_restore_deps(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;
    require_once __DIR__ . '/admin_incident_reports.php';
    require_once __DIR__ . '/admin_attendance_detail.php';
    require_once __DIR__ . '/admin_daily_activity_reports.php';
    require_once __DIR__ . '/admin_weekly_activity_reports.php';
}

/**
 * @return array{ok:bool,message:string}
 */
function admin_report_recovery_restore(int|string $recoveryId, string $actorId): array
{
    admin_report_recovery_ensure_restore_deps();

    $entry = admin_report_recovery_find($recoveryId);
    if ($entry === null) {
        return ['ok' => false, 'message' => 'Recovery entry not found.'];
    }
    if (!empty($entry['restored_at'])) {
        return ['ok' => false, 'message' => 'This entry was already restored.'];
    }

    $kind = (string) ($entry['report_kind'] ?? '');
    $action = (string) ($entry['action_type'] ?? '');
    $payload = is_array($entry['payload'] ?? null) ? $entry['payload'] : [];
    $record = is_array($payload['record'] ?? null) ? $payload['record'] : [];

    $restored = match ($kind) {
        'incident' => admin_report_recovery_restore_incident($action, $record, $payload, $actorId),
        'dtr' => admin_report_recovery_restore_dtr($action, $record, $payload, $actorId),
        'daily-activity' => admin_report_recovery_restore_daily($action, $record, $payload, $actorId),
        'weekly-activity' => admin_report_recovery_restore_weekly($action, $record, $payload, $actorId),
        default => ['ok' => false, 'message' => 'Unknown report type.'],
    };

    if (!($restored['ok'] ?? false)) {
        return $restored;
    }

    admin_report_recovery_mark_restored($recoveryId);

    return [
        'ok' => true,
        'message' => (string) ($restored['message'] ?? 'Record restored.'),
    ];
}

/**
 * @return array{ok:bool,message:string}|null
 */
function admin_report_recovery_find(int|string $recoveryId): ?array
{
    if (is_string($recoveryId) && str_starts_with($recoveryId, 'sess-')) {
        $key = 'admin_report_recovery_fallback';
        if (!isset($_SESSION[$key]) || !is_array($_SESSION[$key])) {
            return null;
        }
        foreach ($_SESSION[$key] as $idx => $entry) {
            if (is_array($entry) && (string) ($entry['recovery_id'] ?? '') === $recoveryId) {
                $entry['_session_idx'] = $idx;

                return $entry;
            }
        }

        return null;
    }

    $id = (int) $recoveryId;
    if ($id <= 0 || !isset($GLOBALS['conn']) || !$GLOBALS['conn'] instanceof PDO) {
        return null;
    }
    $conn = $GLOBALS['conn'];
    if (!admin_report_recovery_table_exists($conn)) {
        return null;
    }

    $row = db_fetch_one(
        $conn,
        'SELECT recovery_id, report_kind, action_type, record_id, record_ref,
                payload_json, actor_company_id, created_at, restored_at
         FROM admin_report_recovery WHERE recovery_id = ? LIMIT 1',
        'i',
        [$id]
    );
    if ($row === null) {
        return null;
    }

    $payload = json_decode((string) ($row['payload_json'] ?? '{}'), true);

    return [
        'recovery_id' => (int) ($row['recovery_id'] ?? 0),
        'report_kind' => (string) ($row['report_kind'] ?? ''),
        'action_type' => (string) ($row['action_type'] ?? ''),
        'record_id' => (string) ($row['record_id'] ?? ''),
        'record_ref' => (string) ($row['record_ref'] ?? ''),
        'payload' => is_array($payload) ? $payload : [],
        'actor_company_id' => (string) ($row['actor_company_id'] ?? ''),
        'created_at' => (string) ($row['created_at'] ?? ''),
        'restored_at' => $row['restored_at'] ?? null,
    ];
}

function admin_report_recovery_mark_restored(int|string $recoveryId): void
{
    if (is_string($recoveryId) && str_starts_with($recoveryId, 'sess-')) {
        $key = 'admin_report_recovery_fallback';
        if (!isset($_SESSION[$key]) || !is_array($_SESSION[$key])) {
            return;
        }
        foreach ($_SESSION[$key] as $idx => $entry) {
            if (is_array($entry) && (string) ($entry['recovery_id'] ?? '') === $recoveryId) {
                $_SESSION[$key][$idx]['restored_at'] = date('Y-m-d H:i:s');
            }
        }

        return;
    }

    $id = (int) $recoveryId;
    if ($id <= 0 || !isset($GLOBALS['conn']) || !$GLOBALS['conn'] instanceof PDO) {
        return;
    }
    $conn = $GLOBALS['conn'];
    if (!admin_report_recovery_table_exists($conn)) {
        return;
    }

    db_execute(
        $conn,
        'UPDATE admin_report_recovery SET restored_at = NOW() WHERE recovery_id = ? AND restored_at IS NULL LIMIT 1',
        'i',
        [$id]
    );
}

/**
 * @return array{ok:bool,message:string}
 */
function admin_report_recovery_purge(int|string $recoveryId): array
{
    $entry = admin_report_recovery_find($recoveryId);
    if ($entry === null) {
        return ['ok' => false, 'message' => 'Recovery entry not found.'];
    }

    if (is_string($recoveryId) && str_starts_with($recoveryId, 'sess-')) {
        $key = 'admin_report_recovery_fallback';
        $idx = $entry['_session_idx'] ?? null;
        if (is_int($idx) && isset($_SESSION[$key][$idx])) {
            array_splice($_SESSION[$key], $idx, 1);
        }

        return ['ok' => true, 'message' => 'Recovery entry removed.'];
    }

    $id = (int) $recoveryId;
    if ($id <= 0 || !isset($GLOBALS['conn']) || !$GLOBALS['conn'] instanceof PDO) {
        return ['ok' => false, 'message' => 'Recovery store unavailable.'];
    }
    $conn = $GLOBALS['conn'];
    if (!admin_report_recovery_table_exists($conn)) {
        return ['ok' => false, 'message' => 'Recovery table not installed.'];
    }

    $ok = db_execute($conn, 'DELETE FROM admin_report_recovery WHERE recovery_id = ? LIMIT 1', 'i', [$id]);

    return $ok
        ? ['ok' => true, 'message' => 'Recovery entry permanently removed.']
        : ['ok' => false, 'message' => 'Could not remove entry.'];
}

/**
 * @param array<string, mixed> $record
 * @param array<string, mixed> $payload
 * @return array{ok:bool,message:string}
 */
function admin_report_recovery_restore_incident(string $action, array $record, array $payload, string $actorId): array
{
    $id = trim((string) ($record['id'] ?? ''));
    $ref = trim((string) ($record['ref'] ?? $id));

    if ($action === 'archived') {
        $prev = trim((string) ($payload['previous_status'] ?? ADMIN_INCIDENT_STATUS_ONGOING));
        if ($id === '' || admin_incident_find($id) === null) {
            return ['ok' => false, 'message' => 'Incident no longer exists in the registry.'];
        }
        $updated = admin_incident_update($id, ['status' => $prev], $actorId);

        return $updated !== null
            ? ['ok' => true, 'message' => 'Incident ' . $ref . ' un-archived (status reverted).']
            : ['ok' => false, 'message' => 'Could not revert incident status.'];
    }

    if (admin_incident_find($id) !== null) {
        return ['ok' => false, 'message' => 'Incident ' . $ref . ' already exists in the registry.'];
    }

    $dbRow = is_array($payload['db_row'] ?? null) ? $payload['db_row'] : null;
    if ($dbRow !== null && isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof PDO
        && guard_incident_table_exists($GLOBALS['conn'])) {
        $restored = guard_incident_restore_db_row($GLOBALS['conn'], $dbRow);

        return $restored['ok']
            ? ['ok' => true, 'message' => 'Incident ' . $ref . ' restored to the database registry.']
            : ['ok' => false, 'message' => (string) ($restored['error'] ?? 'Database restore failed.')];
    }

    $reports = admin_incident_store_all();
    foreach ($reports as $row) {
        if ((string) ($row['id'] ?? '') === $id) {
            return ['ok' => false, 'message' => 'Incident ' . $ref . ' already exists in demo data.'];
        }
    }
    $reports[] = $record;
    admin_incident_store_save($reports);

    return ['ok' => true, 'message' => 'Incident ' . $ref . ' restored to demo registry.'];
}

/**
 * @param array<string, mixed> $record
 * @param array<string, mixed> $payload
 * @return array{ok:bool,message:string}
 */
function admin_report_recovery_restore_dtr(string $action, array $record, array $payload, string $actorId): array
{
    $id = trim((string) ($record['id'] ?? ''));
    $ref = trim((string) ($record['ref'] ?? $id));

    if ($action === 'archived') {
        $prev = trim((string) ($payload['previous_status'] ?? ADMIN_INCIDENT_STATUS_ONGOING));
        if ($id === '' || admin_attendance_find($id) === null) {
            return ['ok' => false, 'message' => 'DTR record no longer exists in the registry.'];
        }
        $updated = admin_attendance_update($id, ['status' => $prev], $actorId);

        return $updated !== null
            ? ['ok' => true, 'message' => 'DTR ' . $ref . ' un-archived.']
            : ['ok' => false, 'message' => 'Could not revert DTR status.'];
    }

    if (admin_attendance_find($id) !== null) {
        return ['ok' => false, 'message' => 'DTR ' . $ref . ' already exists in the registry.'];
    }

    $dbRow = is_array($payload['db_row'] ?? null) ? $payload['db_row'] : null;
    if ($dbRow !== null && isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof PDO
        && guard_dad_table_exists($GLOBALS['conn'])) {
        return ['ok' => false, 'message' => 'Database DTR rows cannot be fully restored after delete (scan files are removed). Re-submit from the guard portal if needed.'];
    }

    $records = admin_attendance_store_all();
    foreach ($records as $row) {
        if ((string) ($row['id'] ?? '') === $id) {
            return ['ok' => false, 'message' => 'DTR ' . $ref . ' already exists in demo data.'];
        }
    }
    $records[] = $record;
    admin_attendance_store_save($records);

    return ['ok' => true, 'message' => 'DTR ' . $ref . ' restored to demo registry.'];
}

/**
 * @param array<string, mixed> $record
 * @param array<string, mixed> $payload
 * @return array{ok:bool,message:string}
 */
function admin_report_recovery_restore_daily(string $action, array $record, array $payload, string $actorId): array
{
    $id = trim((string) ($record['id'] ?? ''));
    $ref = trim((string) ($record['ref'] ?? $id));

    if ($action !== 'archived') {
        return ['ok' => false, 'message' => 'Daily activity reports are not deleted from admin — only archived.'];
    }

    $prev = trim((string) ($payload['previous_status'] ?? ADMIN_DAILY_ACTIVITY_STATUS_PENDING));
    if ($id === '' || admin_daily_activity_find($id) === null) {
        return ['ok' => false, 'message' => 'Daily activity report no longer exists.'];
    }

    $updated = admin_daily_activity_update($id, ['status' => $prev], $actorId);

    return $updated !== null
        ? ['ok' => true, 'message' => 'Daily activity ' . $ref . ' un-archived.']
        : ['ok' => false, 'message' => 'Could not revert daily activity status.'];
}

/**
 * @param array<string, mixed> $record
 * @param array<string, mixed> $payload
 * @return array{ok:bool,message:string}
 */
function admin_report_recovery_restore_weekly(string $action, array $record, array $payload, string $actorId): array
{
    unset($payload, $actorId);

    if ($action !== 'deleted') {
        return ['ok' => false, 'message' => 'Weekly summaries are only removed via delete.'];
    }

    $id = trim((string) ($record['id'] ?? ''));
    $ref = trim((string) ($record['ref'] ?? $id));
    $raw = admin_weekly_activity_store_raw();
    foreach ($raw as $row) {
        if ((string) ($row['id'] ?? '') === $id) {
            return ['ok' => false, 'message' => 'Weekly summary ' . $ref . ' already exists.'];
        }
    }
    $raw[] = $record;
    admin_weekly_activity_store_save($raw);

    return ['ok' => true, 'message' => 'Weekly summary ' . $ref . ' restored.'];
}

/**
 * @param array<string, mixed> $dbRow
 * @return array{ok:bool,error?:string}
 */
function guard_incident_restore_db_row(PDO $conn, array $dbRow): array
{
    if (!guard_incident_table_exists($conn)) {
        return ['ok' => false, 'error' => 'Incident table unavailable.'];
    }

    $ref = trim((string) ($dbRow['reference_code'] ?? ''));
    if ($ref === '') {
        return ['ok' => false, 'error' => 'Missing reference code in snapshot.'];
    }

    $exists = db_fetch_one(
        $conn,
        'SELECT inc_id FROM guard_incident_submissions WHERE reference_code = ? LIMIT 1',
        's',
        [$ref]
    );
    if ($exists !== null) {
        return ['ok' => false, 'error' => 'Reference already exists in registry.'];
    }

    $history = $dbRow['history_json'] ?? null;
    if (is_array($history)) {
        $history = json_encode($history, JSON_THROW_ON_ERROR);
    }

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
            $ref,
            isset($dbRow['dgd_report_number']) && $dbRow['dgd_report_number'] !== '' ? (int) $dbRow['dgd_report_number'] : null,
            (string) ($dbRow['head_guard_company_id'] ?? ''),
            $dbRow['head_guard_name'] ?? null,
            (string) ($dbRow['category'] ?? 'per_post'),
            (string) ($dbRow['incident_type'] ?? ''),
            (string) ($dbRow['severity'] ?? 'Medium'),
            (string) ($dbRow['site_name'] ?? ''),
            (string) ($dbRow['status'] ?? ADMIN_INCIDENT_STATUS_ONGOING),
            $dbRow['summary'] ?? null,
            $dbRow['incident_description'] ?? null,
            $dbRow['action_taken'] ?? null,
            $dbRow['scan_path_cipher'] ?? null,
            $dbRow['ai_extracted_cipher'] ?? null,
            (string) ($dbRow['iv'] ?? ''),
            $dbRow['submit_latitude'] ?? null,
            $dbRow['submit_longitude'] ?? null,
            $dbRow['submit_accuracy_m'] ?? null,
            $dbRow['location_label'] ?? null,
            $history,
            (string) ($dbRow['submitted_at'] ?? date('Y-m-d H:i:s')),
        ]
    );

    return $ok ? ['ok' => true] : ['ok' => false, 'error' => 'Insert failed.'];
}

/**
 * Fetch full DB row for incident before delete.
 *
 * @return array<string, mixed>|null
 */
function admin_report_recovery_incident_db_row(string $id): ?array
{
    if (!isset($GLOBALS['conn']) || !$GLOBALS['conn'] instanceof PDO || !preg_match('/^inc-(\d+)$/', $id, $m)) {
        return null;
    }
    $conn = $GLOBALS['conn'];
    if (!guard_incident_table_exists($conn)) {
        return null;
    }

    $row = db_fetch_one(
        $conn,
        'SELECT * FROM guard_incident_submissions WHERE inc_id = ? LIMIT 1',
        'i',
        [(int) $m[1]]
    );

    return $row !== null ? $row : null;
}
