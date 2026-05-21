<?php
declare(strict_types=1);

/** @return list<array<string,mixed>> */
function guard_portal_announcements(PDO $conn, int $limit = 20): array
{
    if (!db_table_exists($conn, 'guard_announcements')) {
        return [];
    }
    $limit = max(1, min($limit, 50));

    return db_fetch_all(
        $conn,
        'SELECT id, title, body, created_at FROM guard_announcements
         WHERE is_active = 1 ORDER BY created_at DESC LIMIT ' . $limit
    );
}

/** @return list<array<string,mixed>> */
function guard_portal_user_reports(PDO $conn, string $companyId, int $limit = 50): array
{
    if ($companyId === '') {
        return [];
    }
    $limit = max(1, min($limit, 100));
    $res = db_query(
        $conn,
        'SELECT Report_Number, Time_of_Report, Status, Template, Establishment
         FROM dgd WHERE Company_ID = ? ORDER BY Time_of_Report DESC LIMIT ?',
        'si',
        [$companyId, $limit]
    );
    $rows = [];
    if ($res) {
        while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
            $est = (string) ($r['Establishment'] ?? '');
            if ($est !== '' && preg_match('/^[A-Za-z0-9+\/=]+$/', $est)) {
                $est = 'Secured';
            }
            $r['establishment_label'] = $est !== '' ? $est : '—';
            $rows[] = $r;
        }
    }

    return $rows;
}

function guard_portal_status_badge_class(string $status): string
{
    return match (strtoupper(trim($status))) {
        'APPROVED', 'REVIEWED' => 'guard-badge--approved',
        'REJECTED' => 'guard-badge--rejected',
        default => 'guard-badge--pending',
    };
}

/** Post assigned to the logged-in guard (from guards.Post_Assigned). */
function guard_portal_assigned_post(PDO $conn, string $companyId): string
{
    if ($companyId === '') {
        return '';
    }
    $row = db_fetch_one(
        $conn,
        'SELECT Post_Assigned FROM guards WHERE Company_ID = ? LIMIT 1',
        's',
        [$companyId]
    );
    if ($row !== null) {
        return trim((string) ($row['Post_Assigned'] ?? ''));
    }

    return '';
}

/** @return list<array{company_id:string,label:string,unread:int}> */
function guard_portal_admin_contacts(PDO $conn): array
{
    if (!db_table_exists($conn, 'guard_staff_messages')) {
        return [];
    }
    $roleCol = auth_users_role_column($conn);
    $viewerId = (string) ($_SESSION['company_id'] ?? '');
    $res = db_query(
        $conn,
        "SELECT u.Company_ID AS company_id,
                COALESCE(NULLIF(TRIM(CONCAT(g.Last_Name, ', ', g.First_Name)), ','), u.Email, u.Company_ID) AS label
         FROM users u
         LEFT JOIN guards g ON g.Company_ID = u.Company_ID
         WHERE u.is_active = 1 AND u.{$roleCol} = ?
         ORDER BY label ASC",
        'i',
        [AUTH_ROLE_ADMIN]
    );
    $contacts = [];
    if ($res) {
        while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
            $contacts[] = [
                'company_id' => (string) $row['company_id'],
                'label' => (string) $row['label'],
                'unread' => 0,
            ];
        }
    }
    if ($viewerId === '' || $contacts === []) {
        return $contacts;
    }
    $unread = db_query(
        $conn,
        'SELECT sender_company_id, COUNT(*) AS c FROM guard_staff_messages
         WHERE recipient_company_id = ? AND is_read = 0 GROUP BY sender_company_id',
        's',
        [$viewerId]
    );
    $map = [];
    if ($unread) {
        while ($u = $unread->fetch(PDO::FETCH_ASSOC)) {
            $map[(string) $u['sender_company_id']] = (int) $u['c'];
        }
    }
    foreach ($contacts as $i => $c) {
        $contacts[$i]['unread'] = $map[$c['company_id']] ?? 0;
    }

    return $contacts;
}

/** @return list<array<string,mixed>> */
function guard_portal_message_thread(PDO $conn, string $viewerId, string $peerId): array
{
    if (!db_table_exists($conn, 'guard_staff_messages') || $viewerId === '' || $peerId === '') {
        return [];
    }
    $res = db_query(
        $conn,
        'SELECT message_id, sender_company_id, body_text, created_at
         FROM guard_staff_messages
         WHERE (sender_company_id = ? AND recipient_company_id = ?)
            OR (sender_company_id = ? AND recipient_company_id = ?)
         ORDER BY created_at ASC LIMIT 200',
        'ssss',
        [$viewerId, $peerId, $peerId, $viewerId]
    );
    $rows = [];
    if ($res) {
        while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
            $r['is_mine'] = (string) $r['sender_company_id'] === $viewerId;
            $rows[] = $r;
        }
    }
    db_execute(
        $conn,
        'UPDATE guard_staff_messages SET is_read = 1
         WHERE sender_company_id = ? AND recipient_company_id = ? AND is_read = 0',
        'ss',
        [$peerId, $viewerId]
    );

    return $rows;
}

function guard_portal_send_message(PDO $conn, string $senderId, string $recipientId, string $body): bool
{
    if (!db_table_exists($conn, 'guard_staff_messages')) {
        return false;
    }
    $body = trim($body);
    if ($senderId === '' || $recipientId === '' || $body === '') {
        return false;
    }
    $roleCol = auth_users_role_column($conn);
    $row = db_fetch_one(
        $conn,
        "SELECT {$roleCol} AS role FROM users WHERE Company_ID = ? AND is_active = 1 LIMIT 1",
        's',
        [$recipientId]
    );
    if ($row === null) {
        return false;
    }
    $role = auth_normalize_role($row['role'] ?? AUTH_ROLE_ADMIN);
    if ($role !== AUTH_ROLE_ADMIN) {
        return false;
    }

    return db_execute(
        $conn,
        'INSERT INTO guard_staff_messages (sender_company_id, recipient_company_id, body_text, is_read)
         VALUES (?, ?, ?, 0)',
        'sss',
        [$senderId, $recipientId, $body]
    );
}

/**
 * @return array{cipher:string,iv:string}|null
 */
function guard_portal_encrypt(string $plain, string $masterKey, string $cipherAlgo): ?array
{
    if ($plain === '' || $masterKey === '') {
        return null;
    }
    $ivLen = openssl_cipher_iv_length($cipherAlgo);
    if ($ivLen === false) {
        return null;
    }
    $iv = openssl_random_pseudo_bytes($ivLen);
    $enc = openssl_encrypt($plain, $cipherAlgo, $masterKey, 0, $iv);
    if ($enc === false) {
        return null;
    }

    return ['cipher' => $enc, 'iv' => base64_encode($iv)];
}

/** @return list<array{title:string,slug:string,body:string}> */
function guard_portal_policy_sections(): array
{
    return [
        [
            'title' => '11 General Orders',
            'slug' => 'general-orders',
            'body' => '1. Know your post orders. 2. Report all incidents immediately. 3. Maintain professional conduct at all times. 4. Coordinate with head guard and operations. (Summary — refer to official handbook.)',
        ],
        [
            'title' => 'Code of Ethics',
            'slug' => 'ethics',
            'body' => 'Guards shall act with integrity, respect, and accountability. Confidentiality of client information must be preserved. Use of force only as authorized by law and agency policy.',
        ],
        [
            'title' => 'Firearm Safety',
            'slug' => 'firearm',
            'body' => 'Treat every firearm as loaded. Finger off trigger until ready to fire. Never point at anything you do not intend to shoot. Secure weapons before and after duty per post protocol.',
        ],
    ];
}

/** @return list<array{label:string,url:string,icon:string}> */
function guard_portal_social_feeds(): array
{
    return [
        [
            'label' => 'PADPAO',
            'url' => 'https://www.facebook.com/search/top?q=PADPAO',
            'icon' => 'fa-facebook',
        ],
        [
            'label' => 'SOSIA',
            'url' => 'https://www.facebook.com/search/top?q=SOSIA',
            'icon' => 'fa-facebook',
        ],
    ];
}
