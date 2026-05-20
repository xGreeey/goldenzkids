<?php
declare(strict_types=1);

function internal_messages_table_exists(mysqli $conn): bool
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $result = $conn->query("SHOW TABLES LIKE 'internal_messages'");
    $cached = $result && $result->num_rows > 0;

    return $cached;
}

function internal_messaging_roles_may_chat(int $roleA, int $roleB): bool
{
    $roleA = auth_normalize_role($roleA);
    $roleB = auth_normalize_role($roleB);

    return ($roleA === AUTH_ROLE_ADMIN && $roleB === AUTH_ROLE_SUPERADMIN)
        || ($roleA === AUTH_ROLE_SUPERADMIN && $roleB === AUTH_ROLE_ADMIN);
}

/**
 * @return list<array{company_id:string,label:string,unread:int}>
 */
function internal_messaging_list_contacts(mysqli $conn, int $viewerRole): array
{
    if (!internal_messages_table_exists($conn)) {
        return [];
    }

    $viewerRole = auth_normalize_role($viewerRole);
    $peerRole = internal_messaging_peer_role($viewerRole);
    if ($peerRole < 0) {
        return [];
    }
    $roleCol = auth_users_role_column($conn);
    $viewerId = (string) ($_SESSION['company_id'] ?? '');

    $sql = "SELECT u.Company_ID AS company_id,
                   COALESCE(NULLIF(TRIM(CONCAT(g.Last_Name, ', ', g.First_Name)), ','), u.Email, u.Company_ID) AS label
            FROM users u
            LEFT JOIN guards g ON g.Company_ID = u.Company_ID
            WHERE u.is_active = 1 AND u.{$roleCol} = ?
            ORDER BY label ASC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('i', $peerRole);
    $stmt->execute();
    $result = $stmt->get_result();

    $contacts = [];
    while ($row = $result->fetch_assoc()) {
        $contacts[] = [
            'company_id' => (string) $row['company_id'],
            'label' => (string) $row['label'],
            'unread' => 0,
        ];
    }
    $stmt->close();

    if ($contacts === [] || $viewerId === '') {
        return $contacts;
    }

    $unreadStmt = $conn->prepare(
        'SELECT sender_company_id, COUNT(*) AS unread
         FROM internal_messages
         WHERE recipient_company_id = ? AND is_read = 0
         GROUP BY sender_company_id'
    );
    if (!$unreadStmt) {
        return $contacts;
    }

    $unreadStmt->bind_param('s', $viewerId);
    $unreadStmt->execute();
    $unreadRows = $unreadStmt->get_result();
    $unreadMap = [];
    while ($u = $unreadRows->fetch_assoc()) {
        $unreadMap[(string) $u['sender_company_id']] = (int) $u['unread'];
    }
    $unreadStmt->close();

    foreach ($contacts as $i => $contact) {
        $contacts[$i]['unread'] = $unreadMap[$contact['company_id']] ?? 0;
    }

    return $contacts;
}

/**
 * @return list<array{message_id:int,sender_company_id:string,recipient_company_id:string,body_text:string,is_mine:bool,created_at:string}>
 */
function internal_messaging_fetch_thread(mysqli $conn, string $viewerId, string $peerId, int $limit = 200): array
{
    if (!internal_messages_table_exists($conn) || $viewerId === '' || $peerId === '') {
        return [];
    }

    $limit = max(1, min(500, $limit));
    $sql = "SELECT message_id, sender_company_id, recipient_company_id, body_text, created_at
            FROM internal_messages
            WHERE (sender_company_id = ? AND recipient_company_id = ?)
               OR (sender_company_id = ? AND recipient_company_id = ?)
            ORDER BY created_at ASC
            LIMIT {$limit}";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('ssss', $viewerId, $peerId, $peerId, $viewerId);
    $stmt->execute();
    $result = $stmt->get_result();

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $senderId = (string) $row['sender_company_id'];
        $messages[] = [
            'message_id' => (int) $row['message_id'],
            'sender_company_id' => $senderId,
            'recipient_company_id' => (string) $row['recipient_company_id'],
            'body_text' => (string) $row['body_text'],
            'is_mine' => $senderId === $viewerId,
            'created_at' => (string) $row['created_at'],
        ];
    }
    $stmt->close();

    internal_messaging_mark_thread_read($conn, $viewerId, $peerId);

    return $messages;
}

function internal_messaging_mark_thread_read(mysqli $conn, string $viewerId, string $peerId): void
{
    if (!internal_messages_table_exists($conn) || $viewerId === '' || $peerId === '') {
        return;
    }

    $stmt = $conn->prepare(
        'UPDATE internal_messages SET is_read = 1
         WHERE recipient_company_id = ? AND sender_company_id = ? AND is_read = 0'
    );
    if (!$stmt) {
        return;
    }

    $stmt->bind_param('ss', $viewerId, $peerId);
    $stmt->execute();
    $stmt->close();
}

/** @return int Peer role constant, or -1 when the viewer cannot use staff messaging. */
function internal_messaging_peer_role(int $viewerRole): int
{
    $viewerRole = auth_normalize_role($viewerRole);

    if ($viewerRole === AUTH_ROLE_SUPERADMIN) {
        return AUTH_ROLE_ADMIN;
    }
    if ($viewerRole === AUTH_ROLE_ADMIN) {
        return AUTH_ROLE_SUPERADMIN;
    }

    return -1;
}

function internal_messaging_validate_peer(mysqli $conn, string $peerId, int $expectedRole): bool
{
    if ($peerId === '' || $expectedRole < 0) {
        return false;
    }

    $roleCol = auth_users_role_column($conn);
    $stmt = $conn->prepare(
        "SELECT {$roleCol} AS role FROM users WHERE Company_ID = ? AND is_active = 1 LIMIT 1"
    );
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('s', $peerId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return false;
    }

    return auth_normalize_role($row['role']) === auth_normalize_role($expectedRole);
}

function internal_messaging_send(mysqli $conn, string $senderId, int $senderRole, string $recipientId, string $body): bool
{
    if (!internal_messages_table_exists($conn)) {
        return false;
    }

    $body = trim($body);
    if ($senderId === '' || $recipientId === '' || $body === '') {
        return false;
    }

    $recipientRole = internal_messaging_peer_role($senderRole);
    if ($recipientRole < 0) {
        return false;
    }
    if (!internal_messaging_validate_peer($conn, $recipientId, $recipientRole)) {
        return false;
    }

    if (!internal_messaging_roles_may_chat($senderRole, $recipientRole)) {
        return false;
    }

    $stmt = $conn->prepare(
        'INSERT INTO internal_messages (sender_company_id, recipient_company_id, body_text, is_read)
         VALUES (?, ?, ?, 0)'
    );
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('sss', $senderId, $recipientId, $body);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}
