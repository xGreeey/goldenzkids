<?php
declare(strict_types=1);

/**
 * One-time repair: copy password_hash + role_id from portal_users into users
 * (e.g. if Pin was dropped before hashing). Safe to re-run.
 */
return static function (mysqli $conn): void {
    $col = $conn->query("SHOW COLUMNS FROM users LIKE 'password_hash'");
    if (!$col || $col->num_rows === 0) {
        echo "  [skip] users.password_hash column missing.\n";
        return;
    }

    $portalCheck = $conn->query("SHOW TABLES LIKE 'portal_users'");
    if (!$portalCheck || $portalCheck->num_rows === 0) {
        echo "  [skip] portal_users table not found.\n";
        return;
    }

    $ok = $conn->query(
        'UPDATE users u
         INNER JOIN portal_users pu ON pu.company_id COLLATE utf8mb4_unicode_ci = u.Company_ID
         SET u.password_hash = pu.password_hash,
             u.role_id = pu.role_id,
             u.is_active = 1,
             u.password_changed_at = NOW()
         WHERE u.password_hash IS NULL OR u.password_hash = ""'
    );

    if (!$ok) {
        throw new RuntimeException('Repair failed: ' . $conn->error);
    }

    echo "  Repaired {$conn->affected_rows} user(s) from portal_users.\n";
};
