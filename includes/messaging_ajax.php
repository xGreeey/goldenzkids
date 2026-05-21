<?php
declare(strict_types=1);

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
 * @param list<array{company_id:string,label:string,unread:int}> $contacts
 */
function messaging_ajax_find_contact_label(array $contacts, string $companyId): string
{
    foreach ($contacts as $contact) {
        if ($contact['company_id'] === $companyId) {
            return $contact['label'];
        }
    }

    return $companyId;
}

/**
 * @return array<string,mixed>
 */
function messaging_ajax_build_direct_payload(
    mysqli $conn,
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
    $formatted = [];
    foreach ($messages as $message) {
        $formatted[] = [
            'body_text' => $message['body_text'],
            'is_mine' => $message['is_mine'],
            'sender_label' => '',
            'created_at' => $message['created_at'],
            'time_label' => messaging_ajax_format_time($message['created_at']),
        ];
    }

    return [
        'ok' => true,
        'mode' => 'direct',
        'title' => messaging_ajax_find_contact_label($contacts, $peerId),
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
    mysqli $conn,
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
    $formatted = [];
    foreach ($messages as $message) {
        $formatted[] = [
            'body_text' => $message['body_text'],
            'is_mine' => $message['is_mine'],
            'sender_label' => $message['sender_label'],
            'created_at' => $message['created_at'],
            'time_label' => messaging_ajax_format_time($message['created_at']),
        ];
    }

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
