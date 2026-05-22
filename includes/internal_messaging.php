<?php
declare(strict_types=1);

function internal_messages_table_exists(PDO $conn): bool
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $cached = db_table_exists($conn, 'internal_messages');

    return $cached;
}

/**
 * Which roles the viewer may direct-message.
 *
 * @return list<int>
 */
function internal_messaging_allowed_peer_roles(int $viewerRole): array
{
    $viewerRole = auth_normalize_role($viewerRole);

    return match ($viewerRole) {
        AUTH_ROLE_SUPERADMIN => [AUTH_ROLE_ADMIN],
        AUTH_ROLE_ADMIN => [AUTH_ROLE_SUPERADMIN, AUTH_ROLE_GUARD],
        AUTH_ROLE_GUARD => [AUTH_ROLE_ADMIN],
        default => [],
    };
}

function internal_messaging_roles_may_chat(int $roleA, int $roleB): bool
{
    $roleA = auth_normalize_role($roleA);
    $roleB = auth_normalize_role($roleB);

    return in_array($roleB, internal_messaging_allowed_peer_roles($roleA), true);
}

/**
 * @return list<array{company_id:string,label:string,unread:int}>
 */
function internal_messaging_list_contacts(PDO $conn, int $viewerRole): array
{
    if (!internal_messages_table_exists($conn)) {
        return [];
    }

    $viewerRole = auth_normalize_role($viewerRole);
    $peerRoles = internal_messaging_allowed_peer_roles($viewerRole);
    if ($peerRoles === []) {
        return [];
    }

    $roleCol = auth_users_role_column($conn);
    $viewerId = (string) ($_SESSION['company_id'] ?? '');
    $placeholders = implode(', ', array_fill(0, count($peerRoles), '?'));

    $sql = "SELECT u.Company_ID AS company_id,
                   COALESCE(NULLIF(TRIM(CONCAT(g.Last_Name, ', ', g.First_Name)), ','),
                            NULLIF(TRIM(u.Email), ''),
                            u.Company_ID) AS label
            FROM users u
            LEFT JOIN guards g ON g.Company_ID = u.Company_ID
            WHERE u.is_active = 1 AND u.{$roleCol} IN ({$placeholders})
            ORDER BY u.{$roleCol} ASC, label ASC";

    $types = str_repeat('i', count($peerRoles));
    $stmt = db_query($conn, $sql, $types, $peerRoles);
    if ($stmt === false) {
        return [];
    }

    $contacts = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $contacts[] = [
            'company_id' => (string) $row['company_id'],
            'label' => (string) $row['label'],
            'unread' => 0,
        ];
    }

    if ($contacts === [] || $viewerId === '') {
        return $contacts;
    }

    $unreadStmt = db_query(
        $conn,
        'SELECT sender_company_id, COUNT(*) AS unread
         FROM internal_messages
         WHERE recipient_company_id = ? AND is_read = 0
         GROUP BY sender_company_id',
        's',
        [$viewerId]
    );
    if ($unreadStmt === false) {
        return $contacts;
    }

    $unreadMap = [];
    while ($u = $unreadStmt->fetch(PDO::FETCH_ASSOC)) {
        $unreadMap[(string) $u['sender_company_id']] = (int) $u['unread'];
    }

    foreach ($contacts as $i => $contact) {
        $contacts[$i]['unread'] = $unreadMap[$contact['company_id']] ?? 0;
    }

    return $contacts;
}

/**
 * @return list<array{message_id:int,sender_company_id:string,recipient_company_id:string,body_text:string,is_mine:bool,created_at:string}>
 */
function internal_messaging_fetch_thread(PDO $conn, string $viewerId, string $peerId, int $limit = 200): array
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

    $stmt = db_query($conn, $sql, 'ssss', [$viewerId, $peerId, $peerId, $viewerId]);
    if ($stmt === false) {
        return [];
    }

    $messages = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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

    internal_messaging_mark_thread_read($conn, $viewerId, $peerId);

    return $messages;
}

function internal_messaging_mark_thread_read(PDO $conn, string $viewerId, string $peerId): void
{
    if (!internal_messages_table_exists($conn) || $viewerId === '' || $peerId === '') {
        return;
    }

    db_execute(
        $conn,
        'UPDATE internal_messages SET is_read = 1
         WHERE recipient_company_id = ? AND sender_company_id = ? AND is_read = 0',
        'ss',
        [$viewerId, $peerId]
    );
}

/** @return int Peer role constant, or -1 when the viewer cannot use staff messaging. */
function internal_messaging_peer_role(int $viewerRole): int
{
    $roles = internal_messaging_allowed_peer_roles($viewerRole);

    return $roles[0] ?? -1;
}

function internal_messaging_user_role(PDO $conn, string $companyId): ?int
{
    if ($companyId === '') {
        return null;
    }

    $roleCol = auth_users_role_column($conn);
    $row = db_fetch_one(
        $conn,
        "SELECT {$roleCol} AS role FROM users WHERE Company_ID = ? AND is_active = 1 LIMIT 1",
        's',
        [$companyId]
    );

    if ($row === null) {
        return null;
    }

    return auth_normalize_role($row['role']);
}

function internal_messaging_validate_peer(PDO $conn, string $peerId, int $expectedRole): bool
{
    if ($peerId === '' || $expectedRole < 0) {
        return false;
    }

    $peerRole = internal_messaging_user_role($conn, $peerId);

    return $peerRole !== null && $peerRole === auth_normalize_role($expectedRole);
}

function internal_messaging_validate_peer_for_viewer(PDO $conn, string $peerId, int $viewerRole): bool
{
    if ($peerId === '') {
        return false;
    }

    $peerRole = internal_messaging_user_role($conn, $peerId);
    if ($peerRole === null) {
        return false;
    }

    return in_array($peerRole, internal_messaging_allowed_peer_roles($viewerRole), true);
}

function internal_messaging_send(PDO $conn, string $senderId, int $senderRole, string $recipientId, string $body): bool
{
    if (!internal_messages_table_exists($conn)) {
        return false;
    }

    $body = xss_sanitize_plaintext(trim($body), 8000);
    if ($senderId === '' || $recipientId === '' || $body === '') {
        return false;
    }

    $senderRole = auth_normalize_role($senderRole);
    $recipientRole = internal_messaging_user_role($conn, $recipientId);
    if ($recipientRole === null) {
        return false;
    }

    if (!internal_messaging_roles_may_chat($senderRole, $recipientRole)) {
        return false;
    }

    $ok = db_execute(
        $conn,
        'INSERT INTO internal_messages (sender_company_id, recipient_company_id, body_text, is_read)
         VALUES (?, ?, ?, 0)',
        'sss',
        [$senderId, $recipientId, $body]
    );

    if ($ok) {
        require_once __DIR__ . '/portal_audit.php';
        $preview = strlen($body) > 80 ? substr($body, 0, 77) . '…' : $body;
        portal_audit_log(
            $conn,
            'MESSAGE_SENT',
            'To ' . $recipientId . ': ' . $preview,
            $recipientId,
            $senderId,
            $senderRole
        );
    }

    return $ok;
}

function internal_messaging_can_use_direct(int $viewerRole): bool
{
    return internal_messaging_allowed_peer_roles($viewerRole) !== [];
}

/** Remove all direct messages between two users (both sides). */
function internal_messaging_delete_thread(PDO $conn, string $viewerId, int $viewerRole, string $peerId): bool
{
    if (!internal_messages_table_exists($conn) || $viewerId === '' || $peerId === '') {
        return false;
    }

    if (!internal_messaging_validate_peer_for_viewer($conn, $peerId, $viewerRole)) {
        return false;
    }

    return db_execute(
        $conn,
        'DELETE FROM internal_messages
         WHERE (sender_company_id = ? AND recipient_company_id = ?)
            OR (sender_company_id = ? AND recipient_company_id = ?)',
        'ssss',
        [$viewerId, $peerId, $peerId, $viewerId]
    );
}
