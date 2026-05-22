<?php
declare(strict_types=1);

function message_groups_table_exists(PDO $conn): bool
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $cached = db_table_exists($conn, 'message_groups');

    return $cached;
}

function callout_head_guards_table_exists(PDO $conn): bool
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $cached = db_table_exists($conn, 'callout_head_guards');

    return $cached;
}

/**
 * Active head-guard portal accounts (users.role = 0).
 *
 * @return list<array{company_id:string,label:string,head_guard_id:?int}>
 */
function group_messaging_list_head_guard_options(PDO $conn): array
{
    if (!message_groups_table_exists($conn)) {
        return [];
    }

    $roleCol = auth_users_role_column($conn);
    $hgJoin = callout_head_guards_table_exists($conn)
        ? 'LEFT JOIN callout_head_guards hg ON hg.company_id = u.Company_ID AND hg.is_active = 1'
        : '';
    $hgLabel = callout_head_guards_table_exists($conn)
        ? "NULLIF(TRIM(hg.display_name), ''),"
        : '';

    $sql = "SELECT u.Company_ID AS company_id,
                   COALESCE({$hgLabel}
                            NULLIF(TRIM(CONCAT(g.Last_Name, ', ', g.First_Name)), ','),
                            NULLIF(TRIM(u.Email), ''),
                            u.Company_ID) AS label,
                   hg.head_guard_id
            FROM users u
            LEFT JOIN guards g ON g.Company_ID = u.Company_ID
            {$hgJoin}
            WHERE u.is_active = 1 AND u.{$roleCol} = ?
            ORDER BY label ASC";

    $stmt = db_query($conn, $sql, 'i', [AUTH_ROLE_GUARD]);
    if ($stmt === false) {
        return [];
    }

    $options = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $headGuardId = $row['head_guard_id'] ?? null;
        $options[] = [
            'company_id' => (string) $row['company_id'],
            'label' => (string) $row['label'],
            'head_guard_id' => $headGuardId !== null ? (int) $headGuardId : null,
        ];
    }

    return $options;
}

function group_messaging_is_selectable_head_guard(PDO $conn, string $companyId): bool
{
    if ($companyId === '') {
        return false;
    }

    $roleCol = auth_users_role_column($conn);

    return db_fetch_one(
        $conn,
        "SELECT 1 FROM users WHERE Company_ID = ? AND is_active = 1 AND {$roleCol} = ? LIMIT 1",
        'si',
        [$companyId, AUTH_ROLE_GUARD]
    ) !== null;
}

/**
 * @param list<string> $memberCompanyIds Head guard company IDs (creator added automatically).
 */
function group_messaging_create_group(
    PDO $conn,
    string $creatorId,
    string $groupName,
    array $memberCompanyIds
): ?int {
    if (!message_groups_table_exists($conn)) {
        return null;
    }

    $groupName = xss_sanitize_plaintext(trim($groupName), 120);
    if ($creatorId === '' || $groupName === '') {
        return null;
    }

    $memberCompanyIds = array_values(array_unique(array_filter(array_map(
        static fn ($id) => trim((string) $id),
        $memberCompanyIds
    ))));

    $validMembers = [];
    foreach ($memberCompanyIds as $memberId) {
        if ($memberId !== '' && group_messaging_is_selectable_head_guard($conn, $memberId)) {
            $validMembers[] = $memberId;
        }
    }

    if ($validMembers === []) {
        return null;
    }

    $conn->beginTransaction();

    try {
        if (!db_execute(
            $conn,
            'INSERT INTO message_groups (group_name, created_by_company_id) VALUES (?, ?)',
            'ss',
            [$groupName, $creatorId]
        )) {
            throw new RuntimeException('Could not create group');
        }

        $groupId = db_last_insert_id($conn);
        $members = array_values(array_unique([$creatorId, ...$validMembers]));

        foreach ($members as $memberId) {
            if (!db_execute(
                $conn,
                'INSERT INTO message_group_members (group_id, company_id) VALUES (?, ?)',
                'is',
                [$groupId, $memberId]
            )) {
                throw new RuntimeException('Could not add group member');
            }
        }

        $conn->commit();

        require_once __DIR__ . '/portal_audit.php';
        portal_audit_log(
            $conn,
            'GROUP_CREATED',
            $groupName . ' (' . count($members) . ' members)',
            null,
            $creatorId,
            auth_user_role()
        );

        return $groupId;
    } catch (Throwable $e) {
        $conn->rollBack();
        error_log('group_messaging_create_group: ' . $e->getMessage());

        return null;
    }
}

function group_messaging_user_in_group(PDO $conn, int $groupId, string $companyId): bool
{
    if (!message_groups_table_exists($conn) || $groupId < 1 || $companyId === '') {
        return false;
    }

    return db_fetch_one(
        $conn,
        'SELECT 1
         FROM message_group_members m
         INNER JOIN message_groups g ON g.group_id = m.group_id AND g.is_active = 1
         WHERE m.group_id = ? AND m.company_id = ?
         LIMIT 1',
        'is',
        [$groupId, $companyId]
    ) !== null;
}

/**
 * @return list<array{group_id:int,group_name:string,unread:int,member_count:int}>
 */
function group_messaging_list_groups_for_user(PDO $conn, string $companyId): array
{
    if (!message_groups_table_exists($conn) || $companyId === '') {
        return [];
    }

    $sql = 'SELECT g.group_id, g.group_name,
                   (SELECT COUNT(*) FROM message_group_members gm WHERE gm.group_id = g.group_id) AS member_count,
                   COALESCE(rs.last_read_message_id, 0) AS last_read_message_id
            FROM message_groups g
            INNER JOIN message_group_members m ON m.group_id = g.group_id AND m.company_id = ?
            LEFT JOIN message_group_read_state rs
                ON rs.group_id = g.group_id AND rs.company_id = ?
            WHERE g.is_active = 1
            ORDER BY g.created_at DESC';

    $stmt = db_query($conn, $sql, 'ss', [$companyId, $companyId]);
    if ($stmt === false) {
        return [];
    }

    $groups = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $groupId = (int) $row['group_id'];
        $lastRead = (int) $row['last_read_message_id'];
        $groups[] = [
            'group_id' => $groupId,
            'group_name' => (string) $row['group_name'],
            'member_count' => (int) $row['member_count'],
            'unread' => group_messaging_unread_count($conn, $groupId, $companyId, $lastRead),
        ];
    }

    return $groups;
}

function group_messaging_unread_count(PDO $conn, int $groupId, string $companyId, int $lastReadMessageId): int
{
    $row = db_fetch_one(
        $conn,
        'SELECT COUNT(*) AS unread
         FROM message_group_messages
         WHERE group_id = ? AND message_id > ? AND sender_company_id != ?',
        'iis',
        [$groupId, $lastReadMessageId, $companyId]
    );

    return (int) ($row['unread'] ?? 0);
}

/**
 * @return array{group_id:int,group_name:string,members:list<array{company_id:string,label:string}>}|null
 */
function group_messaging_get_group_meta(PDO $conn, int $groupId, string $viewerId): ?array
{
    if (!group_messaging_user_in_group($conn, $groupId, $viewerId)) {
        return null;
    }

    $row = db_fetch_one(
        $conn,
        'SELECT group_id, group_name, created_by_company_id
         FROM message_groups WHERE group_id = ? AND is_active = 1 LIMIT 1',
        'i',
        [$groupId]
    );

    if ($row === null) {
        return null;
    }

    $hgJoin = callout_head_guards_table_exists($conn)
        ? 'LEFT JOIN callout_head_guards hg ON hg.company_id = m.company_id AND hg.is_active = 1'
        : '';
    $hgLabel = callout_head_guards_table_exists($conn)
        ? "NULLIF(TRIM(hg.display_name), ''),"
        : '';
    $memberSql = "SELECT m.company_id,
                         COALESCE({$hgLabel}
                                  NULLIF(TRIM(CONCAT(g.Last_Name, ', ', g.First_Name)), ','),
                                  u.Email,
                                  m.company_id) AS label
                  FROM message_group_members m
                  INNER JOIN users u ON u.Company_ID = m.company_id
                  LEFT JOIN guards g ON g.Company_ID = m.company_id
                  {$hgJoin}
                  WHERE m.group_id = ?
                  ORDER BY label ASC";

    $memberStmt = db_query($conn, $memberSql, 'i', [$groupId]);
    if ($memberStmt === false) {
        return null;
    }

    $members = [];
    while ($member = $memberStmt->fetch(PDO::FETCH_ASSOC)) {
        $members[] = [
            'company_id' => (string) $member['company_id'],
            'label' => (string) $member['label'],
        ];
    }

    return [
        'group_id' => (int) $row['group_id'],
        'group_name' => (string) $row['group_name'],
        'created_by_company_id' => (string) $row['created_by_company_id'],
        'members' => $members,
    ];
}

function group_messaging_is_group_creator(array $meta, string $companyId): bool
{
    return ($meta['created_by_company_id'] ?? '') === $companyId;
}

function group_messaging_can_delete_group(PDO $conn, int $groupId, string $actorId, int $actorRole): bool
{
    if (!group_messaging_user_in_group($conn, $groupId, $actorId)) {
        return false;
    }

    $role = auth_normalize_role($actorRole);
    if ($role === AUTH_ROLE_ADMIN || $role === AUTH_ROLE_SUPERADMIN) {
        return true;
    }

    $row = db_fetch_one(
        $conn,
        'SELECT created_by_company_id FROM message_groups WHERE group_id = ? AND is_active = 1 LIMIT 1',
        'i',
        [$groupId]
    );

    return $row !== null && (string) $row['created_by_company_id'] === $actorId;
}

/** Delete all messages in a group; members remain. */
function group_messaging_clear_history(PDO $conn, int $groupId, string $actorId): bool
{
    if (!group_messaging_user_in_group($conn, $groupId, $actorId)) {
        return false;
    }

    $conn->beginTransaction();

    try {
        if (!db_execute($conn, 'DELETE FROM message_group_messages WHERE group_id = ?', 'i', [$groupId])) {
            throw new RuntimeException('Could not clear messages');
        }

        db_execute(
            $conn,
            'UPDATE message_group_read_state SET last_read_message_id = NULL WHERE group_id = ?',
            'i',
            [$groupId]
        );

        $conn->commit();

        return true;
    } catch (Throwable $e) {
        $conn->rollBack();
        error_log('group_messaging_clear_history: ' . $e->getMessage());

        return false;
    }
}

/** Remove member from group; deactivates group when empty. */
function group_messaging_leave_group(PDO $conn, int $groupId, string $companyId): bool
{
    if (!group_messaging_user_in_group($conn, $groupId, $companyId)) {
        return false;
    }

    $conn->beginTransaction();

    try {
        if (!db_execute(
            $conn,
            'DELETE FROM message_group_members WHERE group_id = ? AND company_id = ?',
            'is',
            [$groupId, $companyId]
        )) {
            throw new RuntimeException('Could not leave group');
        }

        db_execute(
            $conn,
            'DELETE FROM message_group_read_state WHERE group_id = ? AND company_id = ?',
            'is',
            [$groupId, $companyId]
        );

        $countRow = db_fetch_one(
            $conn,
            'SELECT COUNT(*) AS total FROM message_group_members WHERE group_id = ?',
            'i',
            [$groupId]
        );

        if ((int) ($countRow['total'] ?? 0) === 0) {
            group_messaging_deactivate_group($conn, $groupId);
        }

        $conn->commit();

        return true;
    } catch (Throwable $e) {
        $conn->rollBack();
        error_log('group_messaging_leave_group: ' . $e->getMessage());

        return false;
    }
}

/** Deactivate group and remove all data (admin/creator only). */
function group_messaging_delete_group(PDO $conn, int $groupId, string $actorId, int $actorRole): bool
{
    if (!group_messaging_can_delete_group($conn, $groupId, $actorId, $actorRole)) {
        return false;
    }

    $conn->beginTransaction();

    try {
        foreach (
            [
                'DELETE FROM message_group_messages WHERE group_id = ?',
                'DELETE FROM message_group_read_state WHERE group_id = ?',
                'DELETE FROM message_group_members WHERE group_id = ?',
            ] as $sql
        ) {
            if (!db_execute($conn, $sql, 'i', [$groupId])) {
                throw new RuntimeException('Could not delete group data');
            }
        }

        if (!db_execute($conn, 'UPDATE message_groups SET is_active = 0 WHERE group_id = ?', 'i', [$groupId])) {
            throw new RuntimeException('Could not delete group');
        }

        $conn->commit();

        return true;
    } catch (Throwable $e) {
        $conn->rollBack();
        error_log('group_messaging_delete_group: ' . $e->getMessage());

        return false;
    }
}

function group_messaging_deactivate_group(PDO $conn, int $groupId): void
{
    db_execute($conn, 'UPDATE message_groups SET is_active = 0 WHERE group_id = ?', 'i', [$groupId]);
}

/**
 * @return list<array{message_id:int,sender_company_id:string,sender_label:string,body_text:string,is_mine:bool,created_at:string}>
 */
function group_messaging_fetch_messages(PDO $conn, int $groupId, string $viewerId, int $limit = 200): array
{
    if (!group_messaging_user_in_group($conn, $groupId, $viewerId)) {
        return [];
    }

    $limit = max(1, min(500, $limit));
    $hgJoin = callout_head_guards_table_exists($conn)
        ? 'LEFT JOIN callout_head_guards hg ON hg.company_id = msg.sender_company_id AND hg.is_active = 1'
        : '';
    $hgLabel = callout_head_guards_table_exists($conn)
        ? "NULLIF(TRIM(hg.display_name), ''),"
        : '';
    $sql = "SELECT msg.message_id, msg.sender_company_id, msg.body_text, msg.created_at,
                   COALESCE({$hgLabel}
                            NULLIF(TRIM(CONCAT(g.Last_Name, ', ', g.First_Name)), ','),
                            u.Email,
                            msg.sender_company_id) AS sender_label
            FROM message_group_messages msg
            INNER JOIN users u ON u.Company_ID = msg.sender_company_id
            LEFT JOIN guards g ON g.Company_ID = msg.sender_company_id
            {$hgJoin}
            WHERE msg.group_id = ?
            ORDER BY msg.created_at ASC
            LIMIT {$limit}";

    $stmt = db_query($conn, $sql, 'i', [$groupId]);
    if ($stmt === false) {
        return [];
    }

    $messages = [];
    $lastMessageId = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $messageId = (int) $row['message_id'];
        $lastMessageId = $messageId;
        $senderId = (string) $row['sender_company_id'];
        $messages[] = [
            'message_id' => $messageId,
            'sender_company_id' => $senderId,
            'sender_label' => (string) $row['sender_label'],
            'body_text' => (string) $row['body_text'],
            'is_mine' => $senderId === $viewerId,
            'created_at' => (string) $row['created_at'],
        ];
    }

    if ($lastMessageId > 0) {
        group_messaging_mark_read($conn, $groupId, $viewerId, $lastMessageId);
    }

    return $messages;
}

function group_messaging_mark_read(PDO $conn, int $groupId, string $companyId, int $lastMessageId): void
{
    if ($groupId < 1 || $companyId === '' || $lastMessageId < 1) {
        return;
    }

    db_execute(
        $conn,
        'INSERT INTO message_group_read_state (group_id, company_id, last_read_message_id)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE
            last_read_message_id = GREATEST(COALESCE(last_read_message_id, 0), VALUES(last_read_message_id)),
            updated_at = CURRENT_TIMESTAMP',
        'isi',
        [$groupId, $companyId, $lastMessageId]
    );
}

function group_messaging_send(PDO $conn, int $groupId, string $senderId, string $body): bool
{
    if (!message_groups_table_exists($conn)) {
        return false;
    }

    $body = xss_sanitize_plaintext(trim($body), 8000);
    if ($groupId < 1 || $senderId === '' || $body === '') {
        return false;
    }

    if (!group_messaging_user_in_group($conn, $groupId, $senderId)) {
        return false;
    }

    $ok = db_execute(
        $conn,
        'INSERT INTO message_group_messages (group_id, sender_company_id, body_text)
         VALUES (?, ?, ?)',
        'iss',
        [$groupId, $senderId, $body]
    );

    $messageId = db_last_insert_id($conn);

    if ($ok && $messageId > 0) {
        group_messaging_mark_read($conn, $groupId, $senderId, $messageId);
        require_once __DIR__ . '/portal_audit.php';
        $preview = strlen($body) > 80 ? substr($body, 0, 77) . '…' : $body;
        portal_audit_log(
            $conn,
            'GROUP_MESSAGE_SENT',
            'Group #' . $groupId . ': ' . $preview,
            null,
            $senderId,
            auth_user_role()
        );
    }

    return $ok;
}
