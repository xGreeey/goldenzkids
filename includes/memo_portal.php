<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

/**
 * Active head-guard portal accounts (users.role = 0).
 *
 * @return list<string>
 */
function memo_portal_head_guard_recipient_ids(PDO $conn): array
{
    if (!db_table_exists($conn, 'users')) {
        return [];
    }

    $roleCol = auth_users_role_column($conn);
    $rows = db_fetch_all(
        $conn,
        "SELECT Company_ID FROM users
         WHERE is_active = 1
           AND Company_ID IS NOT NULL AND Company_ID != ''
           AND {$roleCol} = ?",
        'i',
        [AUTH_ROLE_GUARD]
    );

    $ids = [];
    foreach ($rows as $row) {
        $id = strtoupper(trim((string) ($row['Company_ID'] ?? '')));
        if ($id !== '') {
            $ids[$id] = $id;
        }
    }

    return array_values($ids);
}

function memo_portal_category_label(string $category): string
{
    return match (strtoupper(trim($category))) {
        'DIRECTIVE' => 'Policy directive',
        'NOTICE' => 'General notice',
        'NTE' => 'Notice to explain',
        'BOLO' => 'Security advisory',
        default => trim($category) !== '' ? trim($category) : 'Memo',
    };
}

function memo_portal_tables_ready(PDO $conn): bool
{
    return db_table_exists($conn, 'memos') && db_table_exists($conn, 'memo_recipients');
}

/**
 * Secured memos addressed to the signed-in head guard (admin announcements).
 *
 * @return list<array<string, mixed>>
 */
function memo_portal_announcements_for_user(PDO $conn, string $companyId, int $limit = 30): array
{
    if ($companyId === '' || !memo_portal_tables_ready($conn)) {
        return [];
    }

    $limit = max(1, min($limit, 50));
    $companyId = strtoupper($companyId);

    $rows = db_fetch_all(
        $conn,
        'SELECT m.Memo_ID AS memo_id,
                m.Category AS category,
                m.Body_Text AS body,
                m.created_at,
                mr.is_read
         FROM memo_recipients mr
         INNER JOIN memos m ON m.Memo_ID = mr.Memo_ID
         WHERE mr.Company_ID = ?
         ORDER BY m.created_at DESC
         LIMIT ' . $limit,
        's',
        [$companyId]
    );

    $items = [];
    foreach ($rows as $row) {
        $createdAt = (string) ($row['created_at'] ?? '');
        $items[] = [
            'id' => 'memo-' . (int) ($row['memo_id'] ?? 0),
            'memo_id' => (int) ($row['memo_id'] ?? 0),
            'title' => memo_portal_category_label((string) ($row['category'] ?? '')),
            'category' => (string) ($row['category'] ?? ''),
            'body' => (string) ($row['body'] ?? ''),
            'created_at' => $createdAt,
            'created_display' => $createdAt !== '' ? date('M j, Y g:i A', strtotime($createdAt)) : '—',
            'is_read' => (int) ($row['is_read'] ?? 0) === 1,
            'source' => 'memo',
        ];
    }

    return $items;
}

/**
 * Legacy static rows from guard_announcements (optional), then memo announcements.
 *
 * @return list<array<string, mixed>>
 */
function guard_portal_announcements(PDO $conn, string $companyId = '', int $limit = 30): array
{
    $limit = max(1, min($limit, 50));
    $items = memo_portal_announcements_for_user($conn, $companyId, $limit);

    if (!db_table_exists($conn, 'guard_announcements')) {
        return $items;
    }

    $static = db_fetch_all(
        $conn,
        'SELECT id, title, body, created_at FROM guard_announcements
         WHERE is_active = 1 ORDER BY created_at DESC LIMIT ' . $limit
    );

    foreach ($static as $row) {
        $createdAt = (string) ($row['created_at'] ?? '');
        $items[] = [
            'id' => 'static-' . (int) ($row['id'] ?? 0),
            'memo_id' => 0,
            'title' => (string) ($row['title'] ?? ''),
            'body' => (string) ($row['body'] ?? ''),
            'created_at' => $createdAt,
            'created_display' => $createdAt !== '' ? date('M j, Y g:i A', strtotime($createdAt)) : '—',
            'is_read' => true,
            'source' => 'static',
        ];
    }

    usort($items, static function (array $a, array $b): int {
        return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
    });

    return array_slice($items, 0, $limit);
}

/**
 * @param list<int> $memoIds
 */
function memo_portal_mark_memos_read(PDO $conn, string $companyId, array $memoIds): void
{
    if ($companyId === '' || $memoIds === [] || !memo_portal_tables_ready($conn)) {
        return;
    }

    $companyId = strtoupper($companyId);
    foreach ($memoIds as $memoId) {
        $memoId = (int) $memoId;
        if ($memoId <= 0) {
            continue;
        }
        db_execute(
            $conn,
            'UPDATE memo_recipients SET is_read = 1, read_at = NOW()
             WHERE Company_ID = ? AND Memo_ID = ? AND is_read = 0',
            'si',
            [$companyId, $memoId]
        );
    }
}
