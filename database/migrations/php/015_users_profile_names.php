<?php
declare(strict_types=1);

/**
 * Add First_Name and Last_Name to users for portal account profiles.
 */
return static function (mysqli $conn): void {
    $check = $conn->query("SHOW COLUMNS FROM users LIKE 'First_Name'");
    if ($check && $check->num_rows > 0) {
        echo "  [skip] users.First_Name / Last_Name already exist.\n";
        return;
    }

    if (!$conn->query(
        'ALTER TABLE users
            ADD COLUMN First_Name VARCHAR(64) NULL AFTER Email,
            ADD COLUMN Last_Name VARCHAR(64) NULL AFTER First_Name'
    )) {
        throw new RuntimeException('Failed to add users profile name columns: ' . $conn->error);
    }

    echo "  [ok] Added users.First_Name and users.Last_Name.\n";
};
