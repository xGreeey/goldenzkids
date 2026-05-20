<?php
declare(strict_types=1);

/**
 * Promote legacy portal role 0 (removed head guard / field portal) to administrator (1).
 */
return static function (mysqli $conn): void {
    $role = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
    if (!$role || $role->num_rows === 0) {
        echo "  [skip] users.role column not found.\n";

        return;
    }

    if (!$conn->query('UPDATE users SET role = 1 WHERE role = 0')) {
        throw new RuntimeException('Failed to promote role-0 users: ' . $conn->error);
    }

    echo '  Promoted ' . $conn->affected_rows . " user(s) from role 0 to administrator.\n";
};
