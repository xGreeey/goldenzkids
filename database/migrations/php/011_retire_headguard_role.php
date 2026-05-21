<?php
declare(strict_types=1);

/**
 * Role 0 = head guard (guard/*). No data migration — do not promote role 0 to admin.
 */
return static function (PDO $conn): void {
    if (!db_column_exists($conn, 'users', 'role')) {
        echo "  [skip] users.role column not found.\n";

        return;
    }

    echo "  [skip] Role 0 = head guard; portal at guard/*.\n";
};
