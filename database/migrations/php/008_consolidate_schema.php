<?php
declare(strict_types=1);

/**
 * Align live database with app code: dgd columns, memo_recipients, establishments, recording PK.
 */
return static function (PDO $conn): void {
    $dbRow = $conn->query('SELECT DATABASE()');
    $dbName = $dbRow ? (string) ($dbRow->fetch_row()[0] ?? '') : '';
    echo "  Consolidating schema on `{$dbName}`...\n";

    $tableExists = static fn (PDO $conn, string $table): bool => db_table_exists($conn, $table);

    $columnExists = static fn (PDO $conn, string $table, string $column): bool => db_column_exists($conn, $table, $column);

    if ($tableExists($conn, 'DGD') && !$tableExists($conn, 'dgd')) {
        $conn->exec('RENAME TABLE `DGD` TO `dgd`');
        echo "  Renamed DGD → dgd.\n";
    }

    if ($tableExists($conn, 'dgd')) {
        if (!$columnExists($conn, 'dgd', 'AI_Extracted_Text')) {
            $conn->query('ALTER TABLE dgd ADD COLUMN AI_Extracted_Text TEXT NULL AFTER Template');
            echo "  Added dgd.AI_Extracted_Text.\n";
        }

        $conn->query("UPDATE dgd SET Status = 'Pending' WHERE Status IN ('Received', '') OR Status IS NULL");
        @$conn->query("ALTER TABLE dgd MODIFY COLUMN Status VARCHAR(30) NOT NULL DEFAULT 'Pending'");
    }

    if ($tableExists($conn, 'dgd')) {
        $conn->query('CREATE OR REPLACE VIEW DGD AS SELECT * FROM dgd');
        echo "  View DGD → dgd created.\n";
    }

    if ($tableExists($conn, 'memo_reception')) {
        if (!$tableExists($conn, 'memo_recipients')) {
            $conn->query('RENAME TABLE memo_reception TO memo_recipients');
            echo "  Renamed memo_reception → memo_recipients.\n";
        } else {
            $conn->query(
                'INSERT INTO memo_recipients (Memo_ID, Company_ID, is_read, read_at)
                 SELECT Memo_ID, Company_ID, COALESCE(Is_Read, 0), NULLIF(Date_read, "") FROM memo_reception
                 WHERE Company_ID IS NOT NULL'
            );
            $conn->query('DROP TABLE memo_reception');
            echo "  Merged memo_reception into memo_recipients.\n";
        }
    }

    if ($tableExists($conn, 'memo_recipients')) {
        if ($columnExists($conn, 'memo_recipients', 'Is_Read') && !$columnExists($conn, 'memo_recipients', 'is_read')) {
            $conn->query('ALTER TABLE memo_recipients CHANGE `Is_Read` `is_read` TINYINT(1) NOT NULL DEFAULT 0');
            echo "  Renamed memo_recipients.Is_Read → is_read.\n";
        }
        if (!$columnExists($conn, 'memo_recipients', 'is_read')) {
            $conn->query('ALTER TABLE memo_recipients ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0');
            echo "  Added memo_recipients.is_read.\n";
        }
        if ($columnExists($conn, 'memo_recipients', 'Date_read') && !$columnExists($conn, 'memo_recipients', 'read_at')) {
            $conn->query('ALTER TABLE memo_recipients CHANGE `Date_read` `read_at` DATETIME NULL');
            echo "  Renamed Date_read → read_at.\n";
        } elseif (!$columnExists($conn, 'memo_recipients', 'read_at')) {
            $conn->query('ALTER TABLE memo_recipients ADD COLUMN read_at DATETIME NULL');
            echo "  Added memo_recipients.read_at.\n";
        }
    }

    if ($tableExists($conn, 'list_of_establishments') && !$tableExists($conn, 'establishments')) {
        $conn->query(
            'CREATE TABLE establishments (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                company_id VARCHAR(13) NULL,
                name VARCHAR(255) NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_establishments_company (company_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $conn->query(
            'INSERT INTO establishments (company_id, name)
             SELECT Company_ID, Establishment FROM list_of_establishments
             WHERE Establishment IS NOT NULL AND TRIM(Establishment) != ""'
        );
        echo "  Created establishments from list_of_establishments.\n";
    }

    if ($columnExists($conn, 'users', 'Designation') && $columnExists($conn, 'users', 'role')) {
        @$conn->query('ALTER TABLE users DROP COLUMN Designation');
        echo "  Dropped users.Designation.\n";
    }

    if ($tableExists($conn, 'recording') && !$columnExists($conn, 'recording', 'id')) {
        @$conn->query(
            'ALTER TABLE recording
             ADD COLUMN id INT UNSIGNED NOT NULL AUTO_INCREMENT FIRST,
             ADD PRIMARY KEY (id)'
        );
        echo "  Added recording.id.\n";
    }

    echo "  Schema consolidation complete.\n";
};
