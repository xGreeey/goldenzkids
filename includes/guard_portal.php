<?php
declare(strict_types=1);

require_once __DIR__ . '/memo_portal.php';
require_once __DIR__ . '/guard_dad.php';
require_once __DIR__ . '/guard_incident.php';
require_once __DIR__ . '/guard_daily_activity.php';

const GUARD_REPORT_HISTORY_PER_PAGE = 10;

function guard_portal_user_reports_count(PDO $conn, string $companyId): int
{
    if ($companyId === '') {
        return 0;
    }
    $row = db_fetch_one(
        $conn,
        'SELECT COUNT(*) AS total FROM dgd WHERE Company_ID = ?',
        's',
        [$companyId]
    );

    return $row !== null ? max(0, (int) ($row['total'] ?? 0)) : 0;
}

/**
 * @return array{page:int,per_page:int,total:int,total_pages:int,offset:int}
 */
function guard_portal_report_history_pagination_state(
    int $total,
    int $requestedPage,
    int $perPage = GUARD_REPORT_HISTORY_PER_PAGE
): array {
    $perPage = max(1, $perPage);
    $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;
    $page = max(1, min($requestedPage, $totalPages));

    return [
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'total_pages' => $totalPages,
        'offset' => ($page - 1) * $perPage,
    ];
}

function guard_portal_report_history_page_url(int $page): string
{
    $params = ['view' => 'history'];
    if ($page > 1) {
        $params['page'] = (string) $page;
    }

    return 'submit-report.php?' . http_build_query($params);
}

/** @return list<int> */
function guard_portal_report_history_page_numbers(int $currentPage, int $totalPages): array
{
    if ($totalPages <= 1) {
        return $totalPages >= 1 ? [1] : [];
    }
    if ($totalPages <= 9) {
        return range(1, $totalPages);
    }

    $pages = [1];
    $start = max(2, $currentPage - 1);
    $end = min($totalPages - 1, $currentPage + 1);
    if ($start > 2) {
        $pages[] = 0;
    }
    for ($p = $start; $p <= $end; $p++) {
        $pages[] = $p;
    }
    if ($end < $totalPages - 1) {
        $pages[] = 0;
    }
    $pages[] = $totalPages;

    return array_values(array_unique($pages));
}

/** @return list<array<string,mixed>> */
function guard_portal_user_reports(PDO $conn, string $companyId, int $limit = 50, int $offset = 0): array
{
    if ($companyId === '') {
        return [];
    }
    $limit = max(1, min($limit, 100));
    $offset = max(0, $offset);
    $joinIncident = guard_incident_table_exists($conn);
    $joinDad = guard_dad_table_exists($conn);
    $joinDaily = guard_daily_activity_table_exists($conn);
    if ($joinIncident || $joinDad || $joinDaily) {
        $sql = 'SELECT d.Report_Number, d.Time_of_Report, d.Status, d.Template, d.Establishment';
        if ($joinIncident) {
            $sql .= ', i.status AS incident_registry_status';
        }
        if ($joinDad) {
            $sql .= ', dad.status AS dad_registry_status';
        }
        if ($joinDaily) {
            $sql .= ', da.status AS daily_activity_registry_status';
        }
        $sql .= ' FROM dgd d';
        if ($joinIncident) {
            $sql .= ' LEFT JOIN guard_incident_submissions i ON i.dgd_report_number = d.Report_Number';
        }
        if ($joinDad) {
            $sql .= ' LEFT JOIN guard_dad_submissions dad ON dad.dgd_report_number = d.Report_Number';
        }
        if ($joinDaily) {
            $sql .= ' LEFT JOIN guard_daily_activity_submissions da ON da.dgd_report_number = d.Report_Number';
        }
        $sql .= ' WHERE d.Company_ID = ? ORDER BY d.Time_of_Report DESC LIMIT ? OFFSET ?';
    } else {
        $sql = 'SELECT Report_Number, Time_of_Report, Status, Template, Establishment
           FROM dgd WHERE Company_ID = ? ORDER BY Time_of_Report DESC LIMIT ? OFFSET ?';
    }
    $res = db_query($conn, $sql, 'sii', [$companyId, $limit, $offset]);
    $rows = [];
    if ($res) {
        while ($r = $res->fetch(PDO::FETCH_ASSOC)) {
            $est = (string) ($r['Establishment'] ?? '');
            if ($est !== '' && preg_match('/^[A-Za-z0-9+\/=]+$/', $est)) {
                $est = 'Secured';
            }
            $r['establishment_label'] = $est !== '' ? $est : '—';
            $template = (string) ($r['Template'] ?? '');
            $incidentStatus = trim((string) ($r['incident_registry_status'] ?? ''));
            if ($joinIncident && $incidentStatus !== '' && guard_incident_is_report_type($template)) {
                $r['Status'] = guard_incident_guard_portal_status($incidentStatus);
            }
            $dadStatus = trim((string) ($r['dad_registry_status'] ?? ''));
            if ($joinDad && $dadStatus !== '' && guard_dad_is_report_type($template)) {
                $r['Status'] = guard_dad_guard_portal_status($dadStatus);
            }
            $dailyStatus = trim((string) ($r['daily_activity_registry_status'] ?? ''));
            if ($joinDaily && $dailyStatus !== '' && guard_daily_activity_is_report_type($template)) {
                $r['Status'] = guard_daily_activity_guard_portal_status($dailyStatus);
            }
            unset($r['incident_registry_status'], $r['dad_registry_status'], $r['daily_activity_registry_status']);
            $rows[] = $r;
        }
    }

    return $rows;
}

/** @return list<string> */
function guard_portal_report_types(): array
{
    return [
        GUARD_INCIDENT_REPORT_TYPE,
        GUARD_DTR_REPORT_TYPE,
        'Daily Activity',
    ];
}

function guard_portal_report_type_label(string $template): string
{
    $template = trim($template);
    if ($template === '') {
        return 'Guard report';
    }
    if ($template === GUARD_DTR_REPORT_TYPE_LEGACY) {
        return GUARD_DTR_REPORT_TYPE;
    }
    if ($template === GUARD_INCIDENT_REPORT_TYPE_LEGACY || $template === 'Incident') {
        return GUARD_INCIDENT_REPORT_TYPE;
    }
    if (in_array($template, guard_portal_report_types(), true)) {
        return $template;
    }

    return $template;
}

function guard_portal_report_type_icon(string $label): string
{
    $label = guard_portal_report_type_label($label);

    return match ($label) {
        GUARD_INCIDENT_REPORT_TYPE => 'fa-triangle-exclamation',
        GUARD_DTR_REPORT_TYPE, GUARD_DTR_REPORT_TYPE_LEGACY => 'fa-calendar-day',
        'Daily Activity' => 'fa-clipboard-list',
        default => 'fa-file-lines',
    };
}

function guard_portal_report_history_pagination_markup(int $currentPage, int $totalPages, int $total): void
{
    if ($totalPages <= 1) {
        return;
    }

    $pageNumbers = guard_portal_report_history_page_numbers($currentPage, $totalPages);
    $from = (($currentPage - 1) * GUARD_REPORT_HISTORY_PER_PAGE) + 1;
    $to = min($currentPage * GUARD_REPORT_HISTORY_PER_PAGE, $total);
    ?>
    <nav class="guard-report-history-pagination" aria-label="Report history pages">
        <p class="guard-report-history-pagination__summary form-hint">
            Showing <?= e((string) $from) ?>–<?= e((string) $to) ?> of <?= e((string) $total) ?>
        </p>
        <div class="guard-report-history-pagination__controls">
            <?php if ($currentPage > 1): ?>
                <a class="guard-report-history-pagination__btn" href="<?= e(guard_portal_report_history_page_url($currentPage - 1)) ?>" aria-label="Previous page" title="Previous page">
                    <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                    <span class="guard-report-history-pagination__btn-label">Prev</span>
                </a>
            <?php else: ?>
                <span class="guard-report-history-pagination__btn is-disabled" aria-disabled="true" title="Previous page">
                    <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                    <span class="guard-report-history-pagination__btn-label">Prev</span>
                </span>
            <?php endif; ?>
            <div class="guard-report-history-pagination__pages" role="group" aria-label="Page numbers">
                <?php foreach ($pageNumbers as $pageNum): ?>
                    <?php if ($pageNum === 0): ?>
                        <span class="guard-report-history-pagination__ellipsis" aria-hidden="true">…</span>
                    <?php elseif ($pageNum === $currentPage): ?>
                        <span class="guard-report-history-pagination__page is-current" aria-current="page"><?= e((string) $pageNum) ?></span>
                    <?php else: ?>
                        <a class="guard-report-history-pagination__page" href="<?= e(guard_portal_report_history_page_url($pageNum)) ?>"><?= e((string) $pageNum) ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php if ($currentPage < $totalPages): ?>
                <a class="guard-report-history-pagination__btn" href="<?= e(guard_portal_report_history_page_url($currentPage + 1)) ?>" aria-label="Next page" title="Next page">
                    <span class="guard-report-history-pagination__btn-label">Next</span>
                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </a>
            <?php else: ?>
                <span class="guard-report-history-pagination__btn is-disabled" aria-disabled="true" title="Next page">
                    <span class="guard-report-history-pagination__btn-label">Next</span>
                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </span>
            <?php endif; ?>
        </div>
    </nav>
    <?php
}

/** @param list<array<string,mixed>> $reports */
function guard_portal_report_history_markup(
    array $reports,
    int $currentPage = 1,
    int $totalPages = 1,
    int $total = 0
): void {
    ?>
    <p class="form-hint guard-report-history__hint">Status matches the admin dashboard. Refresh to see updates.</p>
    <?php if ($total === 0): ?>
        <p class="empty-state">No reports submitted yet.</p>
    <?php else: ?>
        <ul class="guard-report-list">
            <?php foreach ($reports as $report): ?>
                <?php $status = (string) ($report['Status'] ?? 'Pending'); ?>
                <li class="guard-report-list__item">
                    <span class="guard-badge <?= e(guard_portal_status_badge_class($status)) ?>"><?= e($status) ?></span>
                    <time class="guard-report-list__date"><?= e((string) ($report['Time_of_Report'] ?? '—')) ?></time>
                    <span class="guard-report-list__meta">
                        <?= e((string) ($report['establishment_label'] ?? '—')) ?>
                        · <?= e(guard_portal_report_type_label((string) ($report['Template'] ?? 'Report'))) ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php guard_portal_report_history_pagination_markup($currentPage, $totalPages, $total); ?>
    <?php endif;
}

function guard_portal_status_badge_class(string $status): string
{
    return match (strtoupper(trim($status))) {
        'CLOSED', 'APPROVED', 'REVIEWED', 'OPEN', 'ARCHIVED' => 'guard-badge--approved',
        'NOT ACCEPTED', 'REJECTED' => 'guard-badge--rejected',
        'PENDING REVIEW', 'PENDING', 'ON HOLD' => 'guard-badge--pending',
        default => 'guard-badge--pending',
    };
}

/** Post assigned to the logged-in head guard (callout assignment, then guards roster). */
function guard_portal_assigned_post(PDO $conn, string $companyId): string
{
    if ($companyId === '') {
        return '';
    }

    if (
        db_table_exists($conn, 'callout_posts')
        && db_table_exists($conn, 'callout_head_guards')
        && db_table_exists($conn, 'callout_post_assignments')
    ) {
        $callout = db_fetch_one(
            $conn,
            'SELECT p.post_name
             FROM callout_post_assignments a
             INNER JOIN callout_posts p ON p.post_id = a.post_id AND p.is_active = 1
             INNER JOIN callout_head_guards hg ON hg.head_guard_id = a.head_guard_id AND hg.is_active = 1
             WHERE hg.company_id = ? AND a.is_active = 1
             ORDER BY a.assigned_at DESC
             LIMIT 1',
            's',
            [$companyId]
        );
        if ($callout !== null) {
            $name = trim((string) ($callout['post_name'] ?? ''));
            if ($name !== '') {
                return $name;
            }
        }
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
function guard_portal_encrypt(string $plain, string $masterKey, string $cipherAlgo, ?string $ivBinary = null): ?array
{
    if ($plain === '' || $masterKey === '') {
        return null;
    }
    $ivLen = openssl_cipher_iv_length($cipherAlgo);
    if ($ivLen === false) {
        return null;
    }
    if ($ivBinary === null) {
        $ivBinary = openssl_random_pseudo_bytes($ivLen);
    } elseif (strlen($ivBinary) !== $ivLen) {
        return null;
    }
    $enc = openssl_encrypt($plain, $cipherAlgo, $masterKey, 0, $ivBinary);
    if ($enc === false) {
        return null;
    }

    return ['cipher' => $enc, 'iv' => base64_encode($ivBinary)];
}

function guard_portal_decrypt(string $cipher, string $ivB64, string $masterKey, string $cipherAlgo): string
{
    if ($cipher === '' || $ivB64 === '') {
        return '';
    }
    $iv = base64_decode($ivB64, true);
    if ($iv === false || $iv === '') {
        return '';
    }

    return openssl_decrypt($cipher, $cipherAlgo, $masterKey, 0, $iv) ?: '';
}

/** @return list<array{name: string, tmp_name: string, error: int, size: int}> */
function guard_portal_normalized_upload_files(string $field): array
{
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
        return [];
    }

    $upload = $_FILES[$field];
    $names = $upload['name'] ?? null;
    if (!is_array($names)) {
        return [[
            'name' => (string) ($names ?? ''),
            'tmp_name' => (string) ($upload['tmp_name'] ?? ''),
            'error' => (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE),
            'size' => (int) ($upload['size'] ?? 0),
        ]];
    }

    $rows = [];
    foreach ($names as $i => $name) {
        $rows[] = [
            'name' => (string) $name,
            'tmp_name' => (string) ($upload['tmp_name'][$i] ?? ''),
            'error' => (int) ($upload['error'][$i] ?? UPLOAD_ERR_NO_FILE),
            'size' => (int) ($upload['size'][$i] ?? 0),
        ];
    }

    return $rows;
}

/**
 * Persist encrypted evidence photos for a submitted DGD report.
 *
 * @param list<string> $evidenceMeta parallel metadata lines (date/GPS)
 */
function guard_portal_store_report_evidence(
    PDO $conn,
    int $reportNumber,
    string $companyId,
    string $uploadRoot,
    string $uploadsRelativePrefix,
    string $ivB64,
    string $masterKey,
    string $cipherAlgo,
    array $evidenceMeta = [],
    string $uploadField = 'evidence'
): int {
    if (!db_table_exists($conn, 'guard_report_evidence')) {
        return 0;
    }

    $ivBinary = base64_decode($ivB64, true);
    if ($ivBinary === false || $ivBinary === '') {
        return 0;
    }

    $files = guard_portal_normalized_upload_files($uploadField);
    $saved = 0;

    foreach ($files as $i => $file) {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            continue;
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            continue;
        }

        $bytes = file_get_contents($tmp);
        if ($bytes === false || $bytes === '') {
            continue;
        }

        $storageName = 'ev_' . $reportNumber . '_' . $i . '_' . bin2hex(random_bytes(4)) . '.enc';
        $dest = rtrim($uploadRoot, '/\\') . '/' . $storageName;
        $encryptedBytes = openssl_encrypt($bytes, $cipherAlgo, $masterKey, 0, $ivBinary);
        if ($encryptedBytes === false || file_put_contents($dest, $encryptedBytes) === false) {
            continue;
        }

        $relativePath = rtrim($uploadsRelativePrefix, '/') . '/' . $storageName;
        $pathCipher = openssl_encrypt($relativePath, $cipherAlgo, $masterKey, 0, $ivBinary);
        if ($pathCipher === false) {
            @unlink($dest);
            continue;
        }

        $meta = (string) ($evidenceMeta[$i] ?? '');
        $metaCipher = $meta !== ''
            ? (openssl_encrypt($meta, $cipherAlgo, $masterKey, 0, $ivBinary) ?: null)
            : null;

        $hasMetaColumn = db_column_exists($conn, 'guard_report_evidence', 'meta_cipher');
        if ($hasMetaColumn) {
            $ok = db_execute(
                $conn,
                'INSERT INTO guard_report_evidence (report_number, company_id, file_name, meta_cipher, captured_at)
                 VALUES (?, ?, ?, ?, ?)',
                'issss',
                [$reportNumber, $companyId, $pathCipher, $metaCipher, date('Y-m-d H:i:s')]
            );
        } else {
            $ok = db_execute(
                $conn,
                'INSERT INTO guard_report_evidence (report_number, company_id, file_name, gps_lat, gps_lng, captured_at)
                 VALUES (?, ?, ?, ?, ?, ?)',
                'sisdds',
                [$reportNumber, $companyId, $pathCipher, null, null, date('Y-m-d H:i:s')]
            );
        }

        if ($ok) {
            ++$saved;
        } else {
            @unlink($dest);
        }
    }

    return $saved;
}

/**
 * Numbered policy text → HTML list (one counter; numbers stripped from item text).
 */
function guard_portal_policy_body_html(string $body): string
{
    $body = trim($body);
    if ($body === '') {
        return '';
    }

    $items = guard_portal_policy_numbered_items($body);
    if (count($items) < 2) {
        return nl2br(e($body));
    }

    $html = '<ul class="guard-policy-modal__list">';
    foreach ($items as $item) {
        $html .= '<li>' . e($item) . '</li>';
    }
    $html .= '</ul>';

    return $html;
}

/** @return list<string> */
function guard_portal_policy_numbered_items(string $body): array
{
    $lines = preg_split('/\r\n|\r|\n/', $body) ?: [];
    $items = [];
    $current = '';

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        if (preg_match('/^\d+\.\s*(.*)$/u', $line, $matches)) {
            if ($current !== '') {
                $items[] = trim($current);
            }
            $current = trim((string) ($matches[1] ?? ''));
            continue;
        }
        if ($current !== '') {
            $current .= ' ' . $line;
        }
    }

    if ($current !== '') {
        $items[] = trim($current);
    }

    return array_values(array_filter($items, static fn (string $item): bool => $item !== ''));
}

/** @return list<array{title:string,slug:string,body:string}> */
function guard_portal_policy_sections(): array
{
    return [
        [
            'title' => '11 General Orders',
            'slug' => 'general-orders',
            'body' => <<<'TEXT'
1. To take charge of my post and all company properties in view and protect/preserve the same with utmost diligence;

2. To walk in an alert manner during my tour of duty observing everything that takes place within sight or hearing;

3. To report all violation of regulations and orders that I am instructed to enforce;

4. To relay all calls from posts more distant from the security house where I am stationed;

5. To quit my post only when properly relieved;

6. To receive, obey and pass to the relieving guard all orders from company officers or officials, superiors, post in-charge or shift leaders;

7. To talk to no one except in line of duty;

8. To sound or call the alarm in case of fire or disorder;

9. To call the superior officer in any case not covered by instructions;

10. To salute all company officials, superiors in the agency, ranking public officials and officers of the Armed Forces of the Philippines and Philippine National Police; and

11. To be especially watchful at night and during the time of challenging, to challenge all persons on or near my post and to allow no one to pass or loiter without proper authority.
TEXT,
        ],
        [
            'title' => 'Code of Ethics',
            'slug' => 'ethics',
            'body' => <<<'TEXT'
1. As a security agent, his fundamental duty is to serve the interest or mission of his agency in compliance with the contract entered into with the clients of the agency he is supposed to serve.

2. He shall be honest in thoughts and deeds both in his personal and official actuations, obeying the law of the land and the regulations prescribed by his agency and those established by the company he is supposed to protect.

3. He shall not reveal any confidential matter that is confided to him as security guard and such other matters imposed upon him by law.

4. He shall act at all times with decorum and shall not permit personal feelings, prejudices and undue friendship to influence his actuation in the performance of his official functions.

5. He shall not compromise with criminals and other lawless elements to the prejudice of the customer or his client but assist government in its relentless drive against lawlessness and other forms of criminality.

6. He must carry his assigned duties as security guard or watchman as required by law to the best of his ability and safeguard life and property to the establishment he is assigned.

7. He shall wear his uniform, badge, patches and insignia properly as a symbol of public trust and confidence as an honest and trustworthy security guard, watchman and private detective.

8. He must keep his allegiance first to the government, to the agency he is and to the establishment he is assigned to serve with loyalty and dedicated service.

9. He shall diligently and progressively familiarize himself with the rules and regulations laid down by his agency and that of the customer or clients.

10. He shall at all times be courteous, respectful and salute to his superior officers, government officials and officials of the establishment where he is assigned and the company he is supposed to serve.

11. He shall report to perform his duties always in proper uniform and neat in his appearance.

12. He shall learn at heart or memorize and strictly observe the laws and regulations governing the use of firearms.
TEXT,
        ],
        [
            'title' => 'Cardinal Rules of Gun Safety',
            'slug' => 'firearm',
            'body' => <<<'TEXT'
1. Treat all guns as if they are always loaded.

2. Never point a gun at anything you are not willing to destroy.

3. Keep your finger off the trigger until you are ready to shoot.

4. Be sure of your target and what lies beyond it.
TEXT,
        ],
    ];
}

/** @return list<array{title:string,subtitle:string,url:string,page_url:string,icon:string}> */
function guard_portal_social_feeds(): array
{
    return [
        [
            'title' => 'PADPAO Live Feed',
            'subtitle' => 'PADPAO INC SINCE 1958',
            'url' => 'https://www.facebook.com/PADPAOINCSINCE1958',
            'page_url' => 'https://www.facebook.com/PADPAOINCSINCE1958',
            'icon' => 'fa-facebook',
        ],
        [
            'title' => 'SOSIA Live Feed',
            'subtitle' => 'CSG SOSIA PNP',
            'url' => 'https://www.facebook.com/csg.sosia.pnp',
            'page_url' => 'https://www.facebook.com/csg.sosia.pnp',
            'icon' => 'fa-facebook',
        ],
    ];
}

function guard_portal_facebook_page_plugin_url(string $pageUrl, int $width, int $height): string
{
    $query = http_build_query([
        'href' => $pageUrl,
        'tabs' => 'timeline',
        'width' => (string) max(280, min($width, 500)),
        'height' => (string) max(400, min($height, 700)),
        'small_header' => 'true',
        'adapt_container_width' => 'true',
        'hide_cover' => 'true',
        'show_facepile' => 'false',
    ], '', '&', PHP_QUERY_RFC3986);

    return 'https://www.facebook.com/plugins/page.php?' . $query;
}

/** Visible post viewport height (Facebook chrome cropped above). */
function guard_portal_social_feed_iframe_height(): int
{
    return 400;
}

function guard_portal_social_feeds_markup(array $feeds): void
{
    $iframeHeight = guard_portal_social_feed_iframe_height();
    ?>
    <div class="guard-live-feeds" data-guard-live-feeds>
        <?php foreach ($feeds as $feed): ?>
            <article class="guard-live-feed" data-guard-live-feed data-page-url="<?= e((string) $feed['page_url']) ?>">
                <header class="guard-live-feed__head">
                    <h3 class="guard-live-feed__title"><?= e((string) $feed['title']) ?></h3>
                    <a
                        href="<?= e((string) $feed['url']) ?>"
                        class="guard-live-feed__open"
                        target="_blank"
                        rel="noopener noreferrer"
                    >Facebook</a>
                </header>
                <div
                    class="guard-live-feed__scroll"
                    data-guard-live-feed-scroll
                    data-guard-live-feed-viewport-h="<?= (int) $iframeHeight ?>"
                >
                    <p class="guard-live-feed__status" data-guard-live-feed-loading aria-live="polite">
                        <span class="guard-live-feed__pulse" aria-hidden="true"></span>
                        Loading posts…
                    </p>
                    <div class="guard-live-feed__fallback" data-guard-live-feed-fallback hidden>
                        <p>Timeline could not load here (login, blocker, or network).</p>
                        <a href="<?= e((string) $feed['url']) ?>" target="_blank" rel="noopener noreferrer">View posts on Facebook</a>
                    </div>
                    <div class="guard-live-feed__viewport" data-guard-live-feed-viewport>
                        <iframe
                            class="guard-live-feed__frame"
                            data-guard-live-feed-frame
                            title="<?= e((string) $feed['title']) ?> posts"
                            width="500"
                            height="<?= (int) $iframeHeight ?>"
                            style="border:none;overflow:hidden"
                            scrolling="no"
                            frameborder="0"
                            allowfullscreen="true"
                            allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"
                        ></iframe>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <?php
}
