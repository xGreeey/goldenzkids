<?php
declare(strict_types=1);

function admin_head_guard_posts_ready(PDO $conn): bool
{
    return db_table_exists($conn, 'callout_posts')
        && db_table_exists($conn, 'callout_head_guards')
        && db_table_exists($conn, 'callout_post_assignments');
}

/**
 * @return list<array{post_id:int,post_name:string}>
 */
function admin_head_guard_posts_list_posts(PDO $conn): array
{
    if (!db_table_exists($conn, 'callout_posts')) {
        return [];
    }

    return db_fetch_all(
        $conn,
        'SELECT post_id, post_name FROM callout_posts WHERE is_active = 1 ORDER BY post_name ASC'
    );
}

/**
 * @return list<array<string,mixed>>
 */
function admin_head_guard_posts_list_users(PDO $conn): array
{
    $roleCol = auth_users_role_column($conn);
    $hasUserNames = auth_users_has_profile_names($conn);
    $nameSelect = $hasUserNames
        ? "COALESCE(NULLIF(TRIM(u.First_Name), ''), g.First_Name) AS first_name,
           COALESCE(NULLIF(TRIM(u.Last_Name), ''), g.Last_Name) AS last_name"
        : 'g.First_Name AS first_name, g.Last_Name AS last_name';

    $hgJoin = admin_head_guard_posts_ready($conn)
        ? 'LEFT JOIN callout_head_guards hg ON hg.company_id = u.Company_ID AND hg.is_active = 1'
        : '';
    $assignJoin = admin_head_guard_posts_ready($conn)
        ? 'LEFT JOIN callout_post_assignments a ON a.head_guard_id = hg.head_guard_id AND a.is_active = 1
           LEFT JOIN callout_posts p ON p.post_id = a.post_id AND p.is_active = 1'
        : '';
    $postSelect = admin_head_guard_posts_ready($conn)
        ? 'hg.head_guard_id, p.post_id AS assigned_post_id, p.post_name AS assigned_post_name,'
        : 'NULL AS head_guard_id, NULL AS assigned_post_id, NULL AS assigned_post_name,';

    $sql = "SELECT u.Company_ID AS company_id, u.Email AS email, {$nameSelect},
                   g.Post_Assigned AS roster_post, {$postSelect}
                   u.is_active
            FROM users u
            LEFT JOIN guards g ON g.Company_ID = u.Company_ID
            {$hgJoin}
            {$assignJoin}
            WHERE u.{$roleCol} = ?
            ORDER BY u.is_active DESC, last_name ASC, first_name ASC, u.Company_ID ASC";

    $rows = db_fetch_all($conn, $sql, 'i', [AUTH_ROLE_GUARD]);
    $seen = [];
    $list = [];

    foreach ($rows as $row) {
        $companyId = (string) ($row['company_id'] ?? '');
        if ($companyId === '' || isset($seen[$companyId])) {
            continue;
        }
        $seen[$companyId] = true;

        $first = trim((string) ($row['first_name'] ?? ''));
        $last = trim((string) ($row['last_name'] ?? ''));
        $label = trim($last . ($last !== '' && $first !== '' ? ', ' : '') . $first);
        if ($label === '') {
            $email = (string) ($row['email'] ?? '');
            $label = $email !== '' ? $email : $companyId;
        }

        $assignedName = trim((string) ($row['assigned_post_name'] ?? ''));
        if ($assignedName === '') {
            $assignedName = trim((string) ($row['roster_post'] ?? ''));
        }

        $list[] = [
            'company_id' => $companyId,
            'label' => $label,
            'email' => (string) ($row['email'] ?? ''),
            'is_active' => (int) ($row['is_active'] ?? 0),
            'head_guard_id' => isset($row['head_guard_id']) ? (int) $row['head_guard_id'] : null,
            'assigned_post_id' => isset($row['assigned_post_id']) ? (int) $row['assigned_post_id'] : null,
            'assigned_post_name' => $assignedName !== '' ? $assignedName : null,
        ];
    }

    return $list;
}

function admin_head_guard_posts_display_label(string $companyId, string $first, string $last, string $email): string
{
    $label = trim($last . ($last !== '' && $first !== '' ? ', ' : '') . $first);
    if ($label !== '') {
        return $label;
    }
    if ($email !== '') {
        return $email;
    }

    return $companyId;
}

/**
 * Ensure a callout_head_guards row exists for a portal account.
 */
function admin_head_guard_posts_ensure_head_guard_row(PDO $conn, string $companyId): ?int
{
    if ($companyId === '' || !db_table_exists($conn, 'callout_head_guards')) {
        return null;
    }

    $existing = db_fetch_one(
        $conn,
        'SELECT head_guard_id FROM callout_head_guards WHERE company_id = ? LIMIT 1',
        's',
        [$companyId]
    );
    if ($existing !== null) {
        $id = (int) ($existing['head_guard_id'] ?? 0);
        if ($id > 0) {
            db_execute(
                $conn,
                'UPDATE callout_head_guards SET is_active = 1 WHERE head_guard_id = ?',
                'i',
                [$id]
            );

            return $id;
        }
    }

    $roleCol = auth_users_role_column($conn);
    $hasUserNames = auth_users_has_profile_names($conn);
    $nameSelect = $hasUserNames
        ? "COALESCE(NULLIF(TRIM(u.First_Name), ''), g.First_Name) AS first_name,
           COALESCE(NULLIF(TRIM(u.Last_Name), ''), g.Last_Name) AS last_name,
           g.Middle_Name AS middle_name"
        : 'g.First_Name AS first_name, g.Last_Name AS last_name, g.Middle_Name AS middle_name';

    $user = db_fetch_one(
        $conn,
        "SELECT u.Email AS email, {$nameSelect}
         FROM users u
         LEFT JOIN guards g ON g.Company_ID = u.Company_ID
         WHERE u.Company_ID = ? AND u.{$roleCol} = ?
         LIMIT 1",
        'si',
        [$companyId, AUTH_ROLE_GUARD]
    );
    if ($user === null) {
        return null;
    }

    $first = trim((string) ($user['first_name'] ?? ''));
    $last = trim((string) ($user['last_name'] ?? ''));
    $middle = trim((string) ($user['middle_name'] ?? ''));
    $email = trim((string) ($user['email'] ?? ''));
    $display = admin_head_guard_posts_display_label($companyId, $first, $last, $email);

    $baseDisplay = $display;
    $suffix = 0;
    while (true) {
        $candidate = $suffix === 0 ? $baseDisplay : $baseDisplay . ' (' . $companyId . ($suffix > 1 ? '-' . $suffix : '') . ')';
        $dup = db_fetch_one(
            $conn,
            'SELECT head_guard_id FROM callout_head_guards WHERE display_name = ? LIMIT 1',
            's',
            [$candidate]
        );
        if ($dup === null) {
            $display = $candidate;
            break;
        }
        if ((string) ($dup['head_guard_id'] ?? '') !== '' && $companyId !== '') {
            $linked = db_fetch_one(
                $conn,
                'SELECT company_id FROM callout_head_guards WHERE head_guard_id = ? LIMIT 1',
                'i',
                [(int) $dup['head_guard_id']]
            );
            if ($linked !== null && (string) ($linked['company_id'] ?? '') === $companyId) {
                return (int) $dup['head_guard_id'];
            }
        }
        ++$suffix;
        if ($suffix > 20) {
            $display = $companyId . ' — ' . $email;
            break;
        }
    }

    if ($first === '' && $last === '') {
        $first = $display;
        $last = $companyId;
    }

    $ok = db_execute(
        $conn,
        'INSERT INTO callout_head_guards (company_id, first_name, middle_name, last_name, display_name, is_active)
         VALUES (?, ?, ?, ?, ?, 1)',
        'sssss',
        [
            $companyId,
            $first !== '' ? $first : $display,
            $middle !== '' ? $middle : null,
            $last !== '' ? $last : $companyId,
            $display,
        ]
    );
    if (!$ok) {
        return null;
    }

    $row = db_fetch_one(
        $conn,
        'SELECT head_guard_id FROM callout_head_guards WHERE company_id = ? ORDER BY head_guard_id DESC LIMIT 1',
        's',
        [$companyId]
    );

    return $row !== null ? (int) ($row['head_guard_id'] ?? 0) : null;
}

function admin_head_guard_posts_sync_roster_post(PDO $conn, string $companyId, string $postName): void
{
    if ($companyId === '' || !db_table_exists($conn, 'guards')) {
        return;
    }

    $exists = db_fetch_one(
        $conn,
        'SELECT Company_ID FROM guards WHERE Company_ID = ? LIMIT 1',
        's',
        [$companyId]
    );
    if ($exists === null) {
        return;
    }

    db_execute(
        $conn,
        'UPDATE guards SET Post_Assigned = ? WHERE Company_ID = ?',
        'ss',
        [$postName !== '' ? $postName : null, $companyId]
    );
}

/**
 * Assign or clear the duty post for a head-guard portal account.
 *
 * @return array{ok:bool,error?:string,post_name?:string}
 */
function admin_head_guard_posts_assign(PDO $conn, string $companyId, int $postId): array
{
    if (!admin_head_guard_posts_ready($conn)) {
        return ['ok' => false, 'error' => 'Post assignment tables are not available. Run database migrations first.'];
    }

    $roleCol = auth_users_role_column($conn);
    $user = db_fetch_one(
        $conn,
        "SELECT Company_ID FROM users WHERE Company_ID = ? AND {$roleCol} = ? LIMIT 1",
        'si',
        [$companyId, AUTH_ROLE_GUARD]
    );
    if ($user === null) {
        return ['ok' => false, 'error' => 'Invalid head guard account.'];
    }

    if ($postId <= 0) {
        $headGuardId = admin_head_guard_posts_ensure_head_guard_row($conn, $companyId);
        if ($headGuardId !== null && $headGuardId > 0) {
            db_execute(
                $conn,
                'UPDATE callout_post_assignments SET is_active = 0 WHERE head_guard_id = ?',
                'i',
                [$headGuardId]
            );
        }
        admin_head_guard_posts_sync_roster_post($conn, $companyId, '');

        return ['ok' => true, 'post_name' => ''];
    }

    $post = db_fetch_one(
        $conn,
        'SELECT post_id, post_name FROM callout_posts WHERE post_id = ? AND is_active = 1 LIMIT 1',
        'i',
        [$postId]
    );
    if ($post === null) {
        return ['ok' => false, 'error' => 'Selected post is not available.'];
    }

    $postName = trim((string) ($post['post_name'] ?? ''));
    $headGuardId = admin_head_guard_posts_ensure_head_guard_row($conn, $companyId);
    if ($headGuardId === null || $headGuardId <= 0) {
        return ['ok' => false, 'error' => 'Could not link this account to the head guard roster.'];
    }

    db_execute(
        $conn,
        'UPDATE callout_post_assignments SET is_active = 0 WHERE head_guard_id = ?',
        'i',
        [$headGuardId]
    );

    $existing = db_fetch_one(
        $conn,
        'SELECT assignment_id FROM callout_post_assignments WHERE post_id = ? AND head_guard_id = ? LIMIT 1',
        'ii',
        [$postId, $headGuardId]
    );
    if ($existing !== null) {
        db_execute(
            $conn,
            'UPDATE callout_post_assignments SET is_active = 1, assigned_at = CURRENT_TIMESTAMP WHERE assignment_id = ?',
            'i',
            [(int) $existing['assignment_id']]
        );
    } else {
        db_execute(
            $conn,
            'INSERT INTO callout_post_assignments (post_id, head_guard_id, is_active) VALUES (?, ?, 1)',
            'ii',
            [$postId, $headGuardId]
        );
    }

    admin_head_guard_posts_sync_roster_post($conn, $companyId, $postName);

    return ['ok' => true, 'post_name' => $postName];
}
