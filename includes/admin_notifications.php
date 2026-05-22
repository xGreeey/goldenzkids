<?php
declare(strict_types=1);

require_once __DIR__ . '/internal_messaging.php';
require_once __DIR__ . '/group_messaging.php';
require_once __DIR__ . '/messaging_unread.php';
require_once __DIR__ . '/admin_incident_status.php';
require_once __DIR__ . '/guard_incident.php';
require_once __DIR__ . '/guard_dad.php';
require_once __DIR__ . '/admin_ui_icons.php';

/**
 * @return array{id:string,type:string,title:string,body:string,href:string,at:string,at_ts:int,icon:string}
 */
function admin_notification_item(
    string $id,
    string $type,
    string $title,
    string $body,
    string $href,
    string $at,
    string $icon = 'bell'
): array {
    $ts = strtotime($at) ?: 0;

    return [
        'id' => $id,
        'type' => $type,
        'title' => $title,
        'body' => $body,
        'href' => $href,
        'at' => $at,
        'at_ts' => $ts,
        'icon' => $icon,
        'icon_markup' => admin_ui_icon_fa_alias($icon, 16),
        'time_label' => admin_notification_time_label($at),
    ];
}

function admin_notification_time_label(string $datetime): string
{
    $ts = strtotime($datetime);
    if ($ts === false) {
        return $datetime;
    }

    $diff = time() - $ts;
    if ($diff < 45) {
        return 'Just now';
    }
    if ($diff < 3600) {
        return (int) floor($diff / 60) . 'm ago';
    }
    if ($diff < 86400) {
        return (int) floor($diff / 3600) . 'h ago';
    }
    if ($diff < 604800) {
        return (int) floor($diff / 86400) . 'd ago';
    }

    return date('M j, g:i A', $ts);
}

function admin_notification_excerpt(string $text, int $max = 96): string
{
    $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    if ($text === '') {
        return '';
    }
    if (mb_strlen($text) <= $max) {
        return $text;
    }

    return rtrim(mb_substr($text, 0, $max - 1)) . '…';
}

function admin_notification_sender_label(PDO $conn, string $companyId): string
{
    $roleCol = auth_users_role_column($conn);
    $row = db_fetch_one(
        $conn,
        "SELECT COALESCE(NULLIF(TRIM(CONCAT(g.Last_Name, ', ', g.First_Name)), ','),
                        NULLIF(TRIM(u.Email), ''),
                        u.Company_ID) AS label
         FROM users u
         LEFT JOIN guards g ON g.Company_ID = u.Company_ID
         WHERE u.Company_ID = ? AND u.is_active = 1
         LIMIT 1",
        's',
        [$companyId]
    );

    return $row !== null ? (string) ($row['label'] ?? $companyId) : $companyId;
}

/**
 * @return list<array<string, mixed>>
 */
function admin_notifications_direct_messages(PDO $conn, string $adminId): array
{
    if (!internal_messages_table_exists($conn) || $adminId === '') {
        return [];
    }

    $sql = 'SELECT im.sender_company_id,
                   COUNT(*) AS unread_count,
                   MAX(im.created_at) AS latest_at,
                   SUBSTRING_INDEX(
                       GROUP_CONCAT(im.body_text ORDER BY im.created_at DESC SEPARATOR "\x1e"),
                       "\x1e",
                       1
                   ) AS preview_body
            FROM internal_messages im
            WHERE im.recipient_company_id = ? AND im.is_read = 0
            GROUP BY im.sender_company_id
            ORDER BY latest_at DESC
            LIMIT 15';

    $stmt = db_query($conn, $sql, 's', [$adminId]);
    if ($stmt === false) {
        return [];
    }

    $items = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $senderId = (string) ($row['sender_company_id'] ?? '');
        $label = admin_notification_sender_label($conn, $senderId);
        $count = (int) ($row['unread_count'] ?? 0);
        $latestAt = (string) ($row['latest_at'] ?? '');
        $preview = admin_notification_excerpt((string) ($row['preview_body'] ?? ''));

        $items[] = admin_notification_item(
            'dm-' . $senderId,
            'message_direct',
            $count > 1 ? $label . ' (' . $count . ' new)' : 'Message from ' . $label,
            $preview !== '' ? $preview : 'New direct message',
            app_url('admin/inbox.php?peer=' . rawurlencode($senderId) . '#messaging-board'),
            $latestAt,
            'comment'
        );
    }

    return $items;
}

/**
 * @return list<array<string, mixed>>
 */
function admin_notifications_group_messages(PDO $conn, string $adminId): array
{
    if (!message_groups_table_exists($conn) || $adminId === '') {
        return [];
    }

    $sql = 'SELECT g.group_id, g.group_name,
                   COUNT(msg.message_id) AS unread_count,
                   MAX(msg.created_at) AS latest_at,
                   SUBSTRING_INDEX(
                       GROUP_CONCAT(msg.body_text ORDER BY msg.created_at DESC SEPARATOR "\x1e"),
                       "\x1e",
                       1
                   ) AS preview_body
            FROM message_group_messages msg
            INNER JOIN message_group_members mem
                ON mem.group_id = msg.group_id AND mem.company_id = ?
            INNER JOIN message_groups g
                ON g.group_id = msg.group_id AND g.is_active = 1
            LEFT JOIN message_group_read_state rs
                ON rs.group_id = msg.group_id AND rs.company_id = ?
            WHERE msg.sender_company_id != ?
              AND msg.message_id > COALESCE(rs.last_read_message_id, 0)
            GROUP BY g.group_id, g.group_name
            ORDER BY latest_at DESC
            LIMIT 15';

    $stmt = db_query($conn, $sql, 'sss', [$adminId, $adminId, $adminId]);
    if ($stmt === false) {
        return [];
    }

    $items = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $groupId = (int) ($row['group_id'] ?? 0);
        $name = (string) ($row['group_name'] ?? 'Group chat');
        $count = (int) ($row['unread_count'] ?? 0);
        $latestAt = (string) ($row['latest_at'] ?? '');
        $preview = admin_notification_excerpt((string) ($row['preview_body'] ?? ''));

        $items[] = admin_notification_item(
            'grp-' . $groupId,
            'message_group',
            $count > 1 ? $name . ' (' . $count . ' new)' : 'New message in ' . $name,
            $preview !== '' ? $preview : 'New group message',
            app_url('admin/inbox.php?group=' . $groupId . '#messaging-board'),
            $latestAt,
            'users'
        );
    }

    return $items;
}

/**
 * @return list<array<string, mixed>>
 */
function admin_notifications_pending_reports(PDO $conn): array
{
    if (!db_table_exists($conn, 'dgd')) {
        return [];
    }

    $stmt = db_query(
        $conn,
        "SELECT d.Report_Number, d.Company_ID, d.Establishment, d.Time_of_Report, d.created_at
         FROM dgd d
         WHERE d.Status = 'Pending'
           AND (d.Template IS NULL OR d.Template NOT IN ('Daily Activity'))
         ORDER BY d.Time_of_Report DESC
         LIMIT 12"
    );
    if ($stmt === false) {
        return [];
    }

    $items = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $reportNo = (int) ($row['Report_Number'] ?? 0);
        $guardId = (string) ($row['Company_ID'] ?? '');
        $est = (string) ($row['Establishment'] ?? 'Post');
        $at = (string) ($row['Time_of_Report'] ?? $row['created_at'] ?? '');

        $items[] = admin_notification_item(
            'dgd-' . $reportNo,
            'report_pending',
            'Daily report awaiting review',
            $guardId !== '' ? $guardId . ' · ' . $est : $est,
            app_url('admin/dashboard.php'),
            $at,
            'file-lines'
        );
    }

    return $items;
}

/**
 * @return list<array<string, mixed>>
 */
function admin_notifications_open_incidents(PDO $conn): array
{
    if (!guard_incident_table_exists($conn)) {
        return [];
    }

    $stmt = db_query(
        $conn,
        'SELECT inc_id, reference_code, incident_type, head_guard_name, site_name, summary, submitted_at
         FROM guard_incident_submissions
         WHERE status = ?
         ORDER BY submitted_at DESC
         LIMIT 12',
        's',
        [ADMIN_INCIDENT_STATUS_ONGOING]
    );
    if ($stmt === false) {
        return [];
    }

    $items = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $incId = (int) ($row['inc_id'] ?? 0);
        $ref = (string) ($row['reference_code'] ?? '');
        $type = (string) ($row['incident_type'] ?? 'Incident');
        $hg = (string) ($row['head_guard_name'] ?? 'Head guard');
        $summary = admin_notification_excerpt((string) ($row['summary'] ?? $type));
        $at = (string) ($row['submitted_at'] ?? '');

        $items[] = admin_notification_item(
            'inc-' . $incId,
            'incident_open',
            'New incident report · ' . $ref,
            $hg . ' — ' . $summary,
            app_url('admin/reports.php?incident=inc-' . $incId . '&mode=view'),
            $at,
            'triangle-exclamation'
        );
    }

    return $items;
}

/**
 * @return list<array<string, mixed>>
 */
function admin_notifications_pending_dad(PDO $conn): array
{
    if (!guard_dad_table_exists($conn)) {
        return [];
    }

    $stmt = db_query(
        $conn,
        'SELECT dad_id, reference_code, post_name, head_guard_name, shift_date, summary, submitted_at
         FROM guard_dad_submissions
         WHERE status = ?
         ORDER BY submitted_at DESC
         LIMIT 12',
        's',
        [GUARD_DAD_STATUS_PENDING]
    );
    if ($stmt === false) {
        return [];
    }

    $items = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $dadId = (int) ($row['dad_id'] ?? 0);
        $ref = (string) ($row['reference_code'] ?? '');
        $post = (string) ($row['post_name'] ?? 'Post');
        $hg = (string) ($row['head_guard_name'] ?? 'Head guard');
        $summary = admin_notification_excerpt((string) ($row['summary'] ?? 'DTR submission pending review'));
        $at = (string) ($row['submitted_at'] ?? '');

        $items[] = admin_notification_item(
            'dad-' . $dadId,
            'dad_pending',
            'DTR submission · ' . $ref,
            $hg . ' · ' . $post . ' — ' . $summary,
            app_url('admin/dtr.php?record=' . rawurlencode('dad-' . $dadId) . '&mode=view'),
            $at,
            'calendar-day'
        );
    }

    return $items;
}

/**
 * @return list<array<string, mixed>>
 */
function admin_notifications_fetch(PDO $conn, string $adminId, int $viewerRole, int $max = 40): array
{
    $items = array_merge(
        admin_notifications_direct_messages($conn, $adminId),
        admin_notifications_group_messages($conn, $adminId),
        admin_notifications_pending_reports($conn),
        admin_notifications_open_incidents($conn),
        admin_notifications_pending_dad($conn)
    );

    usort(
        $items,
        static fn (array $a, array $b): int => ($b['at_ts'] ?? 0) <=> ($a['at_ts'] ?? 0)
    );

    if ($max > 0 && count($items) > $max) {
        $items = array_slice($items, 0, $max);
    }

    return $items;
}

function admin_notifications_count(PDO $conn, string $adminId, int $viewerRole): int
{
    return count(admin_notifications_fetch($conn, $adminId, $viewerRole, 500));
}
