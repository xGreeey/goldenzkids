<?php
declare(strict_types=1);

/**
 * Role 0 is the security guard portal (guard/*). No data migration required.
 */
return static function (mysqli $conn): void {
    $role = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
    if (!$role || $role->num_rows === 0) {
        echo "  [skip] users.role column not found.\n";

        return;
    }

    echo "  [skip] Role 0 retained for security guard portal accounts.\n";
};
