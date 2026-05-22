<?php
declare(strict_types=1);

require_once __DIR__ . '/messaging_labels.php';
require_once __DIR__ . '/messaging_unread.php';

function messaging_ajax_wants_json(): bool
{
    $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

    return is_string($requestedWith) && strtolower($requestedWith) === 'xmlhttprequest';
}

/**
 * @param array<string,mixed> $payload
 */
function messaging_ajax_json(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_THROW_ON_ERROR);
    exit;
}

function messaging_ajax_format_time(string $createdAt): string
{
    $ts = strtotime($createdAt);

    return $ts ? date('M j, Y g:i A', $ts) : $createdAt;
}

/**
 * @param array{message_id?:int,body_text:string,is_mine:bool,sender_label?:string,created_at:string} $message
 * @return array{message_id:int,body_text:string,is_mine:bool,sender_label:string,created_at:string,time_label:string}
 */
function messaging_ajax_format_message(array $message): array
{
    return [
        'message_id' => (int) ($message['message_id'] ?? 0),
        'body_text' => (string) $message['body_text'],
        'is_mine' => (bool) $message['is_mine'],
        'sender_label' => (string) ($message['sender_label'] ?? ''),
        'created_at' => (string) $message['created_at'],
        'time_label' => messaging_ajax_format_time((string) $message['created_at']),
    ];
}

/**
 * @param list<array{message_id?:int,body_text:string,is_mine:bool,sender_label?:string,created_at:string}> $messages
 * @return list<array{message_id:int,body_text:string,is_mine:bool,sender_label:string,created_at:string,time_label:string}>
 */
function messaging_ajax_format_messages(array $messages): array
{
    $formatted = [];
    foreach ($messages as $message) {
        $formatted[] = messaging_ajax_format_message($message);
    }

    return $formatted;
}

/**
 * @return array{message_id:int,body_text:string,is_mine:bool,sender_label:string,created_at:string,time_label:string}
 */
function messaging_ajax_build_sent_message(string $body, int $messageId, string $senderLabel = ''): array
{
    $createdAt = date('Y-m-d H:i:s');

    return messaging_ajax_format_message([
        'message_id' => $messageId,
        'body_text' => $body,
        'is_mine' => true,
        'sender_label' => $senderLabel,
        'created_at' => $createdAt,
    ]);
}

/**
 * @param list<array{company_id:string,label:string,unread:int}> $contacts
 */
function messaging_ajax_find_contact_label(array $contacts, string $companyId, ?PDO $conn = null): string
{
    foreach ($contacts as $contact) {
        if ($contact['company_id'] === $companyId) {
            return $contact['label'];
        }
    }

    if ($conn instanceof PDO) {
        return messaging_resolve_user_label($conn, $companyId);
    }

    return $companyId;
}

/**
 * @return array<string,mixed>
 */
function messaging_ajax_build_direct_payload(
    PDO $conn,
    string $viewerId,
    int $viewerRole,
    string $peerId,
    array $contacts,
    string $sendUrl
): array {
    if (!internal_messaging_validate_peer_for_viewer($conn, $peerId, $viewerRole)) {
        messaging_ajax_json(['ok' => false, 'error' => 'Invalid contact.'], 403);
    }

    $messages = internal_messaging_fetch_thread($conn, $viewerId, $peerId);
    $formatted = messaging_ajax_format_messages($messages);

    return [
        'ok' => true,
        'mode' => 'direct',
        'title' => messaging_ajax_find_contact_label($contacts, $peerId, $conn),
        'meta' => $peerId,
        'messages' => $formatted,
        'compose' => [
            'action' => $sendUrl,
            'recipient_id' => $peerId,
            'return_peer' => $peerId,
        ],
        'actions' => [
            'clear_history' => true,
        ],
    ];
}

/**
 * @return array<string,mixed>
 */
function messaging_ajax_build_group_payload(
    PDO $conn,
    string $viewerId,
    int $viewerRole,
    int $groupId,
    string $sendUrl
): array {
    if (!group_messaging_user_in_group($conn, $groupId, $viewerId)) {
        messaging_ajax_json(['ok' => false, 'error' => 'You are not a member of this group.'], 403);
    }

    $meta = group_messaging_get_group_meta($conn, $groupId, $viewerId);
    if ($meta === null) {
        messaging_ajax_json(['ok' => false, 'error' => 'Group not found.'], 404);
    }

    $messages = group_messaging_fetch_messages($conn, $groupId, $viewerId);
    $formatted = messaging_ajax_format_messages($messages);

    $memberLabels = array_map(static fn (array $m): string => $m['label'], $meta['members']);

    return [
        'ok' => true,
        'mode' => 'group',
        'title' => $meta['group_name'],
        'meta' => count($meta['members']) . ' members — ' . implode(', ', $memberLabels),
        'messages' => $formatted,
        'compose' => [
            'action' => $sendUrl,
            'group_id' => $groupId,
        ],
        'actions' => [
            'clear_history' => true,
            'leave_group' => true,
            'delete_group' => group_messaging_can_delete_group($conn, $groupId, $viewerId, $viewerRole),
        ],
    ];
}

/**
 * Lightweight inbox poll: unread counts plus new messages for the open thread.
 *
 * @return array<string,mixed>
 */
function messaging_ajax_build_poll_payload(
    PDO $conn,
    string $viewerId,
    int $viewerRole,
    ?string $peerId,
    ?int $groupId,
    int $afterMessageId
): array {
    $contacts = internal_messaging_list_contacts($conn, $viewerRole);
    $groups = message_groups_table_exists($conn)
        ? group_messaging_list_groups_for_user($conn, $viewerId)
        : [];

    $contactUnread = [];
    foreach ($contacts as $contact) {
        $contactUnread[] = [
            'company_id' => $contact['company_id'],
            'unread' => (int) ($contact['unread'] ?? 0),
        ];
    }

    $groupUnread = [];
    foreach ($groups as $group) {
        $groupUnread[] = [
            'group_id' => (int) $group['group_id'],
            'unread' => (int) ($group['unread'] ?? 0),
        ];
    }

    $payload = [
        'ok' => true,
        'unread_total' => messaging_unread_sum_lists($contacts, $groups),
        'contacts' => $contactUnread,
        'groups' => $groupUnread,
        'messages' => [],
        'mode' => null,
    ];

    $afterMessageId = max(0, $afterMessageId);

    if ($groupId !== null && $groupId > 0) {
        if (!group_messaging_user_in_group($conn, $groupId, $viewerId)) {
            messaging_ajax_json(['ok' => false, 'error' => 'You are not a member of this group.'], 403);
        }

        $raw = $afterMessageId > 0
            ? group_messaging_fetch_messages_since($conn, $groupId, $viewerId, $afterMessageId)
            : [];
        $payload['mode'] = 'group';
        $payload['messages'] = messaging_ajax_format_messages($raw);

        return $payload;
    }

    if ($peerId !== null && $peerId !== '') {
        if (!internal_messaging_validate_peer_for_viewer($conn, $peerId, $viewerRole)) {
            messaging_ajax_json(['ok' => false, 'error' => 'Invalid contact.'], 403);
        }

        $raw = $afterMessageId > 0
            ? internal_messaging_fetch_thread_since($conn, $viewerId, $peerId, $afterMessageId)
            : [];
        $payload['mode'] = 'direct';
        $payload['messages'] = messaging_ajax_format_messages($raw);

        return $payload;
    }

    return $payload;
}
