<?php
declare(strict_types=1);

function message_groups_table_exists(mysqli $conn): bool
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $result = $conn->query("SHOW TABLES LIKE 'message_groups'");
    $cached = $result && $result->num_rows > 0;

    return $cached;
}

function callout_head_guards_table_exists(mysqli $conn): bool
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $result = $conn->query("SHOW TABLES LIKE 'callout_head_guards'");
    $cached = $result && $result->num_rows > 0;

    return $cached;
}

/**
 * @return list<array{company_id:string,label:string,head_guard_id:?int}>
 */
function group_messaging_list_head_guard_options(mysqli $conn): array
{
    if (!message_groups_table_exists($conn)) {
        return [];
    }

    $roleCol = auth_users_role_column($conn);

    if (callout_head_guards_table_exists($conn)) {
        $sql = "SELECT hg.head_guard_id, hg.display_name, hg.company_id
                FROM callout_head_guards hg
                INNER JOIN users u ON u.Company_ID = hg.company_id
                WHERE hg.is_active = 1
                  AND hg.company_id IS NOT NULL
                  AND TRIM(hg.company_id) != ''
                  AND u.is_active = 1
                  AND u.{$roleCol} = ?
                ORDER BY hg.display_name ASC";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $guardRole = AUTH_ROLE_GUARD;
        $stmt->bind_param('i', $guardRole);
        $stmt->execute();
        $result = $stmt->get_result();
        $options = [];
        while ($row = $result->fetch_assoc()) {
            $options[] = [
                'company_id' => (string) $row['company_id'],
                'label' => (string) $row['display_name'],
                'head_guard_id' => (int) $row['head_guard_id'],
            ];
        }
        $stmt->close();

        return $options;
    }

    $sql = "SELECT u.Company_ID AS company_id,
                   COALESCE(NULLIF(TRIM(CONCAT(g.Last_Name, ', ', g.First_Name)), ','), u.Company_ID) AS label
            FROM users u
            LEFT JOIN guards g ON g.Company_ID = u.Company_ID
            WHERE u.is_active = 1 AND u.{$roleCol} = ?
            ORDER BY label ASC";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }
    $guardRole = AUTH_ROLE_GUARD;
    $stmt->bind_param('i', $guardRole);
    $stmt->execute();
    $result = $stmt->get_result();
    $options = [];
    while ($row = $result->fetch_assoc()) {
        $options[] = [
            'company_id' => (string) $row['company_id'],
            'label' => (string) $row['label'],
            'head_guard_id' => null,
        ];
    }
    $stmt->close();

    return $options;
}

function group_messaging_is_selectable_head_guard(mysqli $conn, string $companyId): bool
{
    if ($companyId === '') {
        return false;
    }

    foreach (group_messaging_list_head_guard_options($conn) as $option) {
        if ($option['company_id'] === $companyId) {
            return true;
        }
    }

    return false;
}

/**
 * @param list<string> $memberCompanyIds Head guard company IDs (creator added automatically).
 */
function group_messaging_create_group(
    mysqli $conn,
    string $creatorId,
    string $groupName,
    array $memberCompanyIds
): ?int {
    if (!message_groups_table_exists($conn)) {
        return null;
    }

    $groupName = trim($groupName);
    if ($creatorId === '' || $groupName === '' || mb_strlen($groupName) > 120) {
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

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare(
            'INSERT INTO message_groups (group_name, created_by_company_id) VALUES (?, ?)'
        );
        if (!$stmt) {
            throw new RuntimeException('Could not prepare group insert');
        }
        $stmt->bind_param('ss', $groupName, $creatorId);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Could not create group');
        }
        $groupId = (int) $conn->insert_id;
        $stmt->close();

        $members = array_values(array_unique([$creatorId, ...$validMembers]));
        $memberStmt = $conn->prepare(
            'INSERT INTO message_group_members (group_id, company_id) VALUES (?, ?)'
        );
        if (!$memberStmt) {
            throw new RuntimeException('Could not prepare member insert');
        }
        foreach ($members as $memberId) {
            $memberStmt->bind_param('is', $groupId, $memberId);
            if (!$memberStmt->execute()) {
                $memberStmt->close();
                throw new RuntimeException('Could not add group member');
            }
        }
        $memberStmt->close();

        $conn->commit();

        return $groupId;
    } catch (Throwable $e) {
        $conn->rollback();
        error_log('group_messaging_create_group: ' . $e->getMessage());

        return null;
    }
}

function group_messaging_user_in_group(mysqli $conn, int $groupId, string $companyId): bool
{
    if (!message_groups_table_exists($conn) || $groupId < 1 || $companyId === '') {
        return false;
    }

    $stmt = $conn->prepare(
        'SELECT 1
         FROM message_group_members m
         INNER JOIN message_groups g ON g.group_id = m.group_id AND g.is_active = 1
         WHERE m.group_id = ? AND m.company_id = ?
         LIMIT 1'
    );
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('is', $groupId, $companyId);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $exists;
}

/**
 * @return list<array{group_id:int,group_name:string,unread:int,member_count:int}>
 */
function group_messaging_list_groups_for_user(mysqli $conn, string $companyId): array
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

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('ss', $companyId, $companyId);
    $stmt->execute();
    $result = $stmt->get_result();

    $groups = [];
    while ($row = $result->fetch_assoc()) {
        $groupId = (int) $row['group_id'];
        $lastRead = (int) $row['last_read_message_id'];
        $groups[] = [
            'group_id' => $groupId,
            'group_name' => (string) $row['group_name'],
            'member_count' => (int) $row['member_count'],
            'unread' => group_messaging_unread_count($conn, $groupId, $companyId, $lastRead),
        ];
    }
    $stmt->close();

    return $groups;
}

function group_messaging_unread_count(mysqli $conn, int $groupId, string $companyId, int $lastReadMessageId): int
{
    $stmt = $conn->prepare(
        'SELECT COUNT(*) AS unread
         FROM message_group_messages
         WHERE group_id = ? AND message_id > ? AND sender_company_id != ?'
    );
    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param('iis', $groupId, $lastReadMessageId, $companyId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (int) ($row['unread'] ?? 0);
}

/**
 * @return array{group_id:int,group_name:string,members:list<array{company_id:string,label:string}>}|null
 */
function group_messaging_get_group_meta(mysqli $conn, int $groupId, string $viewerId): ?array
{
    if (!group_messaging_user_in_group($conn, $groupId, $viewerId)) {
        return null;
    }

    $stmt = $conn->prepare(
        'SELECT group_id, group_name FROM message_groups WHERE group_id = ? AND is_active = 1 LIMIT 1'
    );
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('i', $groupId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
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
    $memberStmt = $conn->prepare($memberSql);
    if (!$memberStmt) {
        return null;
    }

    $memberStmt->bind_param('i', $groupId);
    $memberStmt->execute();
    $memberResult = $memberStmt->get_result();
    $members = [];
    while ($member = $memberResult->fetch_assoc()) {
        $members[] = [
            'company_id' => (string) $member['company_id'],
            'label' => (string) $member['label'],
        ];
    }
    $memberStmt->close();

    return [
        'group_id' => (int) $row['group_id'],
        'group_name' => (string) $row['group_name'],
        'members' => $members,
    ];
}

/**
 * @return list<array{message_id:int,sender_company_id:string,sender_label:string,body_text:string,is_mine:bool,created_at:string}>
 */
function group_messaging_fetch_messages(mysqli $conn, int $groupId, string $viewerId, int $limit = 200): array
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

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('i', $groupId);
    $stmt->execute();
    $result = $stmt->get_result();

    $messages = [];
    $lastMessageId = 0;
    while ($row = $result->fetch_assoc()) {
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
    $stmt->close();

    if ($lastMessageId > 0) {
        group_messaging_mark_read($conn, $groupId, $viewerId, $lastMessageId);
    }

    return $messages;
}

function group_messaging_mark_read(mysqli $conn, int $groupId, string $companyId, int $lastMessageId): void
{
    if ($groupId < 1 || $companyId === '' || $lastMessageId < 1) {
        return;
    }

    $stmt = $conn->prepare(
        'INSERT INTO message_group_read_state (group_id, company_id, last_read_message_id)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE
            last_read_message_id = GREATEST(COALESCE(last_read_message_id, 0), VALUES(last_read_message_id)),
            updated_at = CURRENT_TIMESTAMP'
    );
    if (!$stmt) {
        return;
    }

    $stmt->bind_param('isi', $groupId, $companyId, $lastMessageId);
    $stmt->execute();
    $stmt->close();
}

function group_messaging_send(mysqli $conn, int $groupId, string $senderId, string $body): bool
{
    if (!message_groups_table_exists($conn)) {
        return false;
    }

    $body = trim($body);
    if ($groupId < 1 || $senderId === '' || $body === '') {
        return false;
    }

    if (!group_messaging_user_in_group($conn, $groupId, $senderId)) {
        return false;
    }

    $stmt = $conn->prepare(
        'INSERT INTO message_group_messages (group_id, sender_company_id, body_text)
         VALUES (?, ?, ?)'
    );
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('iss', $groupId, $senderId, $body);
    $ok = $stmt->execute();
    $messageId = (int) $conn->insert_id;
    $stmt->close();

    if ($ok && $messageId > 0) {
        group_messaging_mark_read($conn, $groupId, $senderId, $messageId);
    }

    return $ok;
}
