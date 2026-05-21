<?php
declare(strict_types=1);

/**
 * Role 0 = head guard (guard/*). No data migration — do not promote role 0 to admin.
 */
return static function (mysqli $conn): void {
    $role = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
    if (!$role || $role->num_rows === 0) {
        echo "  [skip] users.role column not found.\n";

        return;
    }

    echo "  [skip] Role 0 = head guard; portal at guard/*.\n";
};
