<?php
declare(strict_types=1);

require_once __DIR__ . '/internal_messaging.php';
require_once __DIR__ . '/group_messaging.php';

/**
 * Sort conversations with unread messages first (Messenger-style).
 *
 * @param list<array<string,mixed>> $items
 * @return list<array<string,mixed>>
 */
function messaging_sort_by_unread_desc(array $items, string $labelKey = 'label'): array
{
    if ($items === []) {
        return [];
    }

    usort(
        $items,
        static function (array $a, array $b) use ($labelKey): int {
            $unreadCmp = ((int) ($b['unread'] ?? 0)) <=> ((int) ($a['unread'] ?? 0));
            if ($unreadCmp !== 0) {
                return $unreadCmp;
            }

            $labelA = (string) ($a[$labelKey] ?? $a['group_name'] ?? '');
            $labelB = (string) ($b[$labelKey] ?? $b['group_name'] ?? '');

            return strcasecmp($labelA, $labelB);
        }
    );

    return $items;
}

/**
 * @param list<array{company_id:string,label:string,unread:int}> $contacts
 * @param list<array{group_id:int,group_name:string,unread:int,member_count:int}> $groups
 */
function messaging_unread_sum_lists(array $contacts, array $groups): int
{
    $total = 0;
    foreach ($contacts as $contact) {
        $total += (int) ($contact['unread'] ?? 0);
    }
    foreach ($groups as $group) {
        $total += (int) ($group['unread'] ?? 0);
    }

    return $total;
}

/**
 * Clear unread for the open conversation (already marked read server-side).
 *
 * @param list<array{company_id:string,label:string,unread:int}> $contacts
 * @param list<array{group_id:int,group_name:string,unread:int,member_count:int}> $groups
 * @return array{0:list<array{company_id:string,label:string,unread:int}>,1:list<array{group_id:int,group_name:string,unread:int,member_count:int}>,2:int}
 */
function messaging_apply_open_thread_unread(
    array $contacts,
    array $groups,
    ?string $activePeer,
    ?int $activeGroupId
): array {
    if ($activePeer !== null && $activePeer !== '') {
        foreach ($contacts as $i => $contact) {
            if ($contact['company_id'] === $activePeer) {
                $contacts[$i]['unread'] = 0;
            }
        }
    }

    if ($activeGroupId !== null && $activeGroupId > 0) {
        foreach ($groups as $i => $group) {
            if ($group['group_id'] === $activeGroupId) {
                $groups[$i]['unread'] = 0;
            }
        }
    }

    $contacts = messaging_sort_by_unread_desc($contacts, 'label');
    $groups = messaging_sort_by_unread_desc($groups, 'group_name');
    $total = messaging_unread_sum_lists($contacts, $groups);

    return [$contacts, $groups, $total];
}

/** Total unread direct + group messages for the signed-in user. */
function messaging_unread_total(PDO $conn, string $viewerId, int $viewerRole): int
{
    if ($viewerId === '') {
        return 0;
    }

    $total = 0;
    $viewerRole = auth_normalize_role($viewerRole);

    if (internal_messages_table_exists($conn) && internal_messaging_can_use_direct($viewerRole)) {
        $row = db_fetch_one(
            $conn,
            'SELECT COUNT(*) AS unread
             FROM internal_messages
             WHERE recipient_company_id = ? AND is_read = 0',
            's',
            [$viewerId]
        );
        $total += (int) ($row['unread'] ?? 0);
    }

    if (message_groups_table_exists($conn)) {
        $row = db_fetch_one(
            $conn,
            'SELECT COUNT(*) AS unread
             FROM message_group_messages msg
             INNER JOIN message_group_members mem
                 ON mem.group_id = msg.group_id AND mem.company_id = ?
             INNER JOIN message_groups g
                 ON g.group_id = msg.group_id AND g.is_active = 1
             LEFT JOIN message_group_read_state rs
                 ON rs.group_id = msg.group_id AND rs.company_id = ?
             WHERE msg.sender_company_id != ?
               AND msg.message_id > COALESCE(rs.last_read_message_id, 0)',
            'sss',
            [$viewerId, $viewerId, $viewerId]
        );
        $total += (int) ($row['unread'] ?? 0);
    }

    return $total;
}
