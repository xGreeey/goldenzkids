<?php
declare(strict_types=1);

/**
 * Add First_Name and Last_Name to users for portal account profiles.
 */
return static function (PDO $conn): void {
    if (db_column_exists($conn, 'users', 'First_Name')) {
        echo "  [skip] users.First_Name / Last_Name already exist.\n";
        return;
    }

    $conn->exec(
        'ALTER TABLE users
            ADD COLUMN First_Name VARCHAR(64) NULL AFTER Email,
            ADD COLUMN Last_Name VARCHAR(64) NULL AFTER First_Name'
    );

    echo "  [ok] Added users.First_Name and users.Last_Name.\n";
};
