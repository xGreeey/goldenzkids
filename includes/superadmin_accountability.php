<?php
declare(strict_types=1);

require_once __DIR__ . '/superadmin_page.css.php';

function recording_supports_audit_detail(mysqli $conn): bool
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $col = $conn->query("SHOW COLUMNS FROM recording LIKE 'event_detail'");
    $cached = $col && $col->num_rows > 0;

    return $cached;
}

/**
 * Append-only audit entry. Staff cannot delete these from the portal.
 */
function superadmin_log_account_event(
    mysqli $conn,
    string $targetCompanyId,
    string $event,
    ?string $detail = null
): void {
    $allowed = [
        'ACCOUNT_CREATED',
        'ACCOUNT_UPDATED',
        'ACCOUNT_ENABLED',
        'ACCOUNT_DISABLED',
        'ACCOUNT_PASSWORD_RESET',
        'ACCOUNT_ROLE_CHANGED',
    ];
    if (!in_array($event, $allowed, true) || $targetCompanyId === '') {
        return;
    }

    $actor = (string) ($_SESSION['company_id'] ?? '');
    $actorRole = auth_role_label_for_recording(auth_user_role());
    $designation = $actor !== '' ? "{$actorRole}:{$actor}" : $actorRole;
    $time = date('Y-m-d H:i:s');

    if (recording_supports_audit_detail($conn)) {
        db_execute(
            $conn,
            'INSERT INTO recording (Company_ID, actor_company_id, Designation, Event, event_detail, Time_Of_Event)
             VALUES (?, ?, ?, ?, ?, ?)',
            'ssssss',
            [$targetCompanyId, $actor !== '' ? $actor : null, $designation, $event, $detail, $time]
        );
        return;
    }

    db_execute(
        $conn,
        'INSERT INTO recording (Company_ID, Designation, Event, Time_Of_Event) VALUES (?, ?, ?, ?)',
        'ssss',
        [$targetCompanyId, $designation, $event, $time]
    );
}

/**
 * @param array{email?:string,role?:int,is_active?:int,password_changed?:bool} $before
 * @param array{email?:string,role?:int,is_active?:int,password_changed?:bool} $after
 */
function superadmin_log_account_diff(
    mysqli $conn,
    string $targetCompanyId,
    array $before,
    array $after,
    bool $isCreate
): void {
    if ($isCreate) {
        $role = auth_role_name((int) ($after['role'] ?? AUTH_ROLE_HEADGUARD));
        superadmin_log_account_event($conn, $targetCompanyId, 'ACCOUNT_CREATED', "Role: {$role}");
        return;
    }

    if (($before['email'] ?? '') !== ($after['email'] ?? '')) {
        superadmin_log_account_event(
            $conn,
            $targetCompanyId,
            'ACCOUNT_UPDATED',
            'Email updated'
        );
    }

    if (($before['role'] ?? null) !== ($after['role'] ?? null)) {
        $from = auth_role_name((int) ($before['role'] ?? 0));
        $to = auth_role_name((int) ($after['role'] ?? 0));
        superadmin_log_account_event($conn, $targetCompanyId, 'ACCOUNT_ROLE_CHANGED', "{$from} → {$to}");
    }

    if (($before['is_active'] ?? 1) !== ($after['is_active'] ?? 1)) {
        superadmin_log_account_event(
            $conn,
            $targetCompanyId,
            (int) ($after['is_active'] ?? 1) === 1 ? 'ACCOUNT_ENABLED' : 'ACCOUNT_DISABLED',
            null
        );
    }

    if (!empty($after['password_changed'])) {
        superadmin_log_account_event($conn, $targetCompanyId, 'ACCOUNT_PASSWORD_RESET', 'Access code reset by superadmin');
    }
}

/**
 * @return list<array<string,mixed>>
 */
function superadmin_account_audit_trail(mysqli $conn, string $companyId, int $limit = 25): array
{
    if ($companyId === '') {
        return [];
    }

    $limit = max(1, min($limit, 100));

    if (recording_supports_audit_detail($conn)) {
        $result = db_query(
            $conn,
            'SELECT id, Company_ID, actor_company_id, Designation, Event, event_detail, Time_Of_Event
             FROM recording
             WHERE Company_ID = ?
             ORDER BY Time_Of_Event DESC
             LIMIT ?',
            'si',
            [$companyId, $limit]
        );
    } else {
        $result = db_query(
            $conn,
            'SELECT id, Company_ID, Designation, Event, Time_Of_Event
             FROM recording
             WHERE Company_ID = ?
             ORDER BY Time_Of_Event DESC
             LIMIT ?',
            'si',
            [$companyId, $limit]
        );
    }

    $rows = [];
    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $rows[] = $r;
        }
    }

    return $rows;
}

/**
 * @return list<array<string,mixed>>
 */
function superadmin_recent_account_changes(mysqli $conn, int $limit = 10): array
{
    $events = [
        'ACCOUNT_CREATED', 'ACCOUNT_UPDATED', 'ACCOUNT_ENABLED', 'ACCOUNT_DISABLED',
        'ACCOUNT_PASSWORD_RESET', 'ACCOUNT_ROLE_CHANGED',
    ];
    $placeholders = implode(',', array_fill(0, count($events), '?'));
    $types = str_repeat('s', count($events)) . 'i';
    $params = [...$events, max(1, min($limit, 50))];

    $detailCol = recording_supports_audit_detail($conn) ? ', event_detail' : '';
    $actorCol = recording_supports_audit_detail($conn) ? ', actor_company_id' : '';

    $sql = "SELECT id, Company_ID, Designation, Event{$detailCol}{$actorCol}, Time_Of_Event
            FROM recording
            WHERE Event IN ({$placeholders})
            ORDER BY Time_Of_Event DESC
            LIMIT ?";

    $result = db_query($conn, $sql, $types, $params);
    $rows = [];
    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $rows[] = $r;
        }
    }

    return $rows;
}

function superadmin_audit_actor_label(array $entry): string
{
    if (!empty($entry['actor_company_id'])) {
        return (string) $entry['actor_company_id'];
    }

    $designation = (string) ($entry['Designation'] ?? '');
    if (str_contains($designation, ':')) {
        return substr($designation, (int) strrpos($designation, ':') + 1);
    }
    if (str_starts_with($designation, 'SUPERADMIN:')) {
        return substr($designation, 11);
    }

    return $designation !== '' ? $designation : '—';
}

function superadmin_event_label(string $event): string
{
    return match (strtoupper($event)) {
        'ACCOUNT_CREATED' => 'Account created',
        'ACCOUNT_UPDATED' => 'Account updated',
        'ACCOUNT_ENABLED' => 'Account activated',
        'ACCOUNT_DISABLED' => 'Account deactivated',
        'ACCOUNT_PASSWORD_RESET' => 'Access code reset',
        'ACCOUNT_ROLE_CHANGED' => 'Role changed',
        'LOGIN' => 'Signed in',
        'LOGOUT' => 'Signed out',
        default => $event,
    };
}

function superadmin_event_icon(string $event): string
{
    return match (strtoupper($event)) {
        'ACCOUNT_CREATED' => 'fa-user-plus',
        'ACCOUNT_UPDATED' => 'fa-pen-to-square',
        'ACCOUNT_ENABLED' => 'fa-user-check',
        'ACCOUNT_DISABLED' => 'fa-user-slash',
        'ACCOUNT_PASSWORD_RESET' => 'fa-key',
        'ACCOUNT_ROLE_CHANGED' => 'fa-user-shield',
        'LOGIN' => 'fa-right-to-bracket',
        'LOGOUT' => 'fa-right-from-bracket',
        default => 'fa-circle-info',
    };
}

function superadmin_accountability_rules(): array
{
    return [
        'Only superadmin can create or change portal accounts.',
        'Admins and head guards cannot edit their own role, email, or access code in the portal.',
        'Every sign-in, sign-out, and account change is recorded with who did it and when.',
        'Audit records cannot be removed or edited from this console.',
    ];
}
