<?php
declare(strict_types=1);

function recording_supports_audit_detail(PDO $conn): bool
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $cached = db_column_exists($conn, 'recording', 'event_detail');

    return $cached;
}

/**
 * Append-only portal audit entry (login, logout, account changes, and portal actions).
 */
function portal_audit_log(
    PDO $conn,
    string $event,
    ?string $detail = null,
    ?string $subjectCompanyId = null,
    ?string $actorCompanyId = null,
    ?int $actorRole = null
): void {
    $event = strtoupper(trim($event));
    if ($event === '' || strlen($event) > 64) {
        return;
    }

    $actorCompanyId = $actorCompanyId ?? (string) ($_SESSION['company_id'] ?? '');
    $actorCompanyId = trim($actorCompanyId);
    $role = $actorRole ?? auth_user_role();
    $roleLabel = auth_role_label_for_recording($role);
    $designation = $actorCompanyId !== '' ? "{$roleLabel}:{$actorCompanyId}" : $roleLabel;
    $time = date('Y-m-d H:i:s');
    $subject = $subjectCompanyId !== null && trim($subjectCompanyId) !== ''
        ? trim($subjectCompanyId)
        : ($actorCompanyId !== '' ? $actorCompanyId : null);

    if (recording_supports_audit_detail($conn)) {
        db_execute(
            $conn,
            'INSERT INTO recording (Company_ID, actor_company_id, Designation, Event, event_detail, Time_Of_Event)
             VALUES (?, ?, ?, ?, ?, ?)',
            'ssssss',
            [
                $subject,
                $actorCompanyId !== '' ? $actorCompanyId : null,
                $designation,
                $event,
                $detail !== null && $detail !== '' ? $detail : null,
                $time,
            ]
        );

        return;
    }

    db_execute(
        $conn,
        'INSERT INTO recording (Company_ID, Designation, Event, Time_Of_Event) VALUES (?, ?, ?, ?)',
        'ssss',
        [$subject, $designation, $event, $time]
    );
}

function portal_audit_auth_event(PDO $conn, string $event, string $companyId, int $role): void
{
    if ($companyId === '') {
        return;
    }

    portal_audit_log(
        $conn,
        $event,
        null,
        $companyId,
        $companyId,
        $role
    );
}

function portal_audit_actor_label(array $entry): string
{
    if (!empty($entry['actor_company_id'])) {
        return (string) $entry['actor_company_id'];
    }

    $event = strtoupper((string) ($entry['Event'] ?? ''));
    $companyId = trim((string) ($entry['Company_ID'] ?? ''));
    if (($event === 'LOGIN' || $event === 'LOGOUT') && $companyId !== '') {
        return $companyId;
    }

    $designation = (string) ($entry['Designation'] ?? '');
    if (str_contains($designation, ':')) {
        return substr($designation, (int) strrpos($designation, ':') + 1);
    }

    return $designation !== '' ? $designation : '—';
}

function portal_audit_event_label(string $event): string
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
        'INCIDENT_SUBMITTED' => 'Incident submitted',
        'INCIDENT_UPDATED' => 'Incident updated',
        'DTR_SUBMITTED' => 'DTR submitted',
        'DTR_UPDATED' => 'DTR updated',
        'DAD_SUBMITTED' => 'DTR submitted',
        'DAD_UPDATED' => 'DTR updated',
        'MEMO_SENT' => 'Memo sent',
        'MESSAGE_SENT' => 'Message sent',
        'GROUP_MESSAGE_SENT' => 'Group message sent',
        'GROUP_CREATED' => 'Group created',
        'MSG_THREAD_CLEARED' => 'Message history cleared',
        'GROUP_LEFT' => 'Left group chat',
        'GROUP_DELETED' => 'Group chat deleted',
        'POST_ASSIGNED' => 'Post assignment changed',
        'PROFILE_UPDATED' => 'Profile updated',
        default => str_replace('_', ' ', strtolower($event)),
    };
}

function portal_audit_event_icon(string $event): string
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
        'INCIDENT_SUBMITTED', 'INCIDENT_UPDATED' => 'fa-triangle-exclamation',
        'DTR_SUBMITTED', 'DTR_UPDATED', 'DAD_SUBMITTED', 'DAD_UPDATED' => 'fa-clipboard-list',
        'MEMO_SENT' => 'fa-bullhorn',
        'MESSAGE_SENT', 'GROUP_MESSAGE_SENT' => 'fa-envelope',
        'GROUP_CREATED' => 'fa-users',
        'MSG_THREAD_CLEARED' => 'fa-eraser',
        'GROUP_LEFT' => 'fa-person-walking-arrow-right',
        'GROUP_DELETED' => 'fa-trash',
        'POST_ASSIGNED' => 'fa-map-location-dot',
        'PROFILE_UPDATED' => 'fa-id-card',
        default => 'fa-circle-info',
    };
}

function portal_audit_designation_role(string $designation): string
{
    $designation = strtoupper(trim($designation));
    if ($designation === '') {
        return '';
    }
    if (str_starts_with($designation, 'SUPERADMIN')) {
        return 'SUPERADMIN';
    }
    if (str_starts_with($designation, 'ADMIN')) {
        return 'ADMIN';
    }
    if (str_starts_with($designation, 'HEADGUARD') || str_starts_with($designation, 'GUARD')) {
        return 'GUARD';
    }

    return '';
}
