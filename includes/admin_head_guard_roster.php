<?php
declare(strict_types=1);

require_once __DIR__ . '/admin_head_guard_posts.php';

function admin_head_guard_roster_ready(PDO $conn): bool
{
    return db_table_exists($conn, 'guards');
}

/**
 * Roster guards that are not head-guard portal accounts (role 0).
 *
 * @return list<array{company_id:string,label:string,head_id:?string,head_label:?string}>
 */
function admin_head_guard_roster_list_field_guards(PDO $conn): array
{
    if (!admin_head_guard_roster_ready($conn)) {
        return [];
    }

    $roleCol = auth_users_role_column($conn);
    $rows = db_fetch_all(
        $conn,
        "SELECT g.Company_ID AS company_id, g.First_Name AS first_name, g.Middle_Name AS middle_name,
                g.Last_Name AS last_name, g.Head_ID AS head_id
         FROM guards g
         LEFT JOIN users hg ON hg.Company_ID = g.Company_ID AND hg.{$roleCol} = ? AND hg.is_active = 1
         WHERE hg.Company_ID IS NULL
         ORDER BY g.Last_Name ASC, g.First_Name ASC, g.Company_ID ASC",
        'i',
        [AUTH_ROLE_GUARD]
    );

    $headLabels = admin_head_guard_roster_head_labels_by_company_id($conn);
    $list = [];

    foreach ($rows as $row) {
        $companyId = (string) ($row['company_id'] ?? '');
        if ($companyId === '') {
            continue;
        }

        $headId = trim((string) ($row['head_id'] ?? ''));
        $list[] = [
            'company_id' => $companyId,
            'label' => admin_head_guard_roster_guard_label($row),
            'head_id' => $headId !== '' ? $headId : null,
            'head_label' => $headId !== '' ? ($headLabels[$headId] ?? $headId) : null,
        ];
    }

    return $list;
}

/**
 * @return array<string, string> head guard company_id => display label
 */
function admin_head_guard_roster_head_labels_by_company_id(PDO $conn): array
{
    $map = [];
    foreach (admin_head_guard_posts_list_users($conn) as $hg) {
        $id = (string) ($hg['company_id'] ?? '');
        if ($id === '') {
            continue;
        }
        $map[$id] = (string) ($hg['label'] ?? $id);
    }

    return $map;
}

/**
 * @param array<string, mixed> $row
 */
function admin_head_guard_roster_guard_label(array $row): string
{
    $first = trim((string) ($row['first_name'] ?? ''));
    $middle = trim((string) ($row['middle_name'] ?? ''));
    $last = trim((string) ($row['last_name'] ?? ''));
    $name = trim($first . ($middle !== '' ? ' ' . $middle : '') . ($last !== '' ? ' ' . $last : ''));
    if ($name !== '') {
        return $name;
    }

    return (string) ($row['company_id'] ?? '');
}

/**
 * Guards currently assigned to a head guard (guards.Head_ID).
 *
 * @return list<array{company_id:string,label:string}>
 */
function admin_head_guard_roster_team_for_head(PDO $conn, string $headCompanyId): array
{
    if ($headCompanyId === '' || !admin_head_guard_roster_ready($conn)) {
        return [];
    }

    $rows = db_fetch_all(
        $conn,
        'SELECT Company_ID AS company_id, First_Name AS first_name, Middle_Name AS middle_name, Last_Name AS last_name
         FROM guards
         WHERE Head_ID = ?
         ORDER BY Last_Name ASC, First_Name ASC, Company_ID ASC',
        's',
        [$headCompanyId]
    );

    $team = [];
    foreach ($rows as $row) {
        $companyId = (string) ($row['company_id'] ?? '');
        if ($companyId === '') {
            continue;
        }
        $team[] = [
            'company_id' => $companyId,
            'label' => admin_head_guard_roster_guard_label($row),
        ];
    }

    return $team;
}

function admin_head_guard_roster_is_head_guard_account(PDO $conn, string $companyId): bool
{
    if ($companyId === '') {
        return false;
    }

    $roleCol = auth_users_role_column($conn);
    $row = db_fetch_one(
        $conn,
        "SELECT Company_ID FROM users WHERE Company_ID = ? AND {$roleCol} = ? AND is_active = 1 LIMIT 1",
        'si',
        [$companyId, AUTH_ROLE_GUARD]
    );

    return $row !== null;
}

function admin_head_guard_roster_is_field_guard(PDO $conn, string $guardCompanyId): bool
{
    if ($guardCompanyId === '') {
        return false;
    }

    $roleCol = auth_users_role_column($conn);
    $row = db_fetch_one(
        $conn,
        "SELECT g.Company_ID
         FROM guards g
         LEFT JOIN users hg ON hg.Company_ID = g.Company_ID AND hg.{$roleCol} = ? AND hg.is_active = 1
         WHERE g.Company_ID = ? AND hg.Company_ID IS NULL
         LIMIT 1",
        'is',
        [AUTH_ROLE_GUARD, $guardCompanyId]
    );

    return $row !== null;
}

/**
 * Replace the field-guard team for a head guard portal account.
 *
 * @param list<string> $guardCompanyIds
 * @return array{ok:bool,error?:string,count?:int}
 */
function admin_head_guard_roster_save_team(PDO $conn, string $headCompanyId, array $guardCompanyIds): array
{
    if (!admin_head_guard_roster_ready($conn)) {
        return ['ok' => false, 'error' => 'Guards roster table is not available.'];
    }

    if (!admin_head_guard_roster_is_head_guard_account($conn, $headCompanyId)) {
        return ['ok' => false, 'error' => 'Invalid head guard account.'];
    }

    $unique = [];
    foreach ($guardCompanyIds as $guardId) {
        $guardId = trim((string) $guardId);
        if ($guardId === '' || isset($unique[$guardId])) {
            continue;
        }
        if (!admin_head_guard_roster_is_field_guard($conn, $guardId)) {
            return ['ok' => false, 'error' => 'One or more selected guards are not on the field roster.'];
        }
        $unique[$guardId] = true;
    }

    $selected = array_keys($unique);

    db_execute(
        $conn,
        'UPDATE guards SET Head_ID = NULL WHERE Head_ID = ?',
        's',
        [$headCompanyId]
    );

    foreach ($selected as $guardId) {
        db_execute(
            $conn,
            'UPDATE guards SET Head_ID = ? WHERE Company_ID = ?',
            'ss',
            [$headCompanyId, $guardId]
        );
    }

    return ['ok' => true, 'count' => count($selected)];
}

/**
 * Head guard self-service: assign from unassigned pool only (does not take guards from other teams).
 *
 * @param list<string> $guardCompanyIds
 * @return array{ok:bool,error?:string,count?:int}
 */
function admin_head_guard_roster_save_team_self(PDO $conn, string $headCompanyId, array $guardCompanyIds): array
{
    $result = admin_head_guard_roster_save_team($conn, $headCompanyId, $guardCompanyIds);
    if (!$result['ok']) {
        return $result;
    }

    return $result;
}

/**
 * Options for a head guard multi-select: own team + unassigned field guards.
 *
 * @return list<array{company_id:string,label:string,group:string,selected:bool}>
 */
function admin_head_guard_roster_select_options_for_head(PDO $conn, string $headCompanyId): array
{
    $teamIds = [];
    foreach (admin_head_guard_roster_team_for_head($conn, $headCompanyId) as $member) {
        $teamIds[(string) $member['company_id']] = true;
    }

    $options = [];
    foreach (admin_head_guard_roster_list_field_guards($conn) as $guard) {
        $id = (string) $guard['company_id'];
        $headId = $guard['head_id'] ?? null;
        $onTeam = isset($teamIds[$id]);
        $unassigned = $headId === null;

        if (!$onTeam && !$unassigned) {
            continue;
        }

        $label = (string) $guard['label'];
        $options[] = [
            'company_id' => $id,
            'label' => $label . ' · ' . $id,
            'group' => $onTeam ? 'Your team' : 'Available',
            'selected' => $onTeam,
        ];
    }

    return $options;
}

/**
 * Admin view: all field guards grouped for assignment to any head guard.
 *
 * @return list<array{company_id:string,label:string,group:string,selected:bool}>
 */
function admin_head_guard_roster_select_options_admin(PDO $conn, string $headCompanyId): array
{
    $teamIds = [];
    foreach (admin_head_guard_roster_team_for_head($conn, $headCompanyId) as $member) {
        $teamIds[(string) $member['company_id']] = true;
    }

    $options = [];
    foreach (admin_head_guard_roster_list_field_guards($conn) as $guard) {
        $id = (string) $guard['company_id'];
        $headId = $guard['head_id'] ?? null;
        $onTeam = isset($teamIds[$id]);
        $group = 'Available';
        if ($onTeam) {
            $group = 'Assigned here';
        } elseif ($headId !== null) {
            $group = 'Other team: ' . (string) ($guard['head_label'] ?? $headId);
        }

        $options[] = [
            'company_id' => $id,
            'label' => (string) $guard['label'] . ' · ' . $id,
            'group' => $group,
            'selected' => $onTeam,
        ];
    }

    return $options;
}
