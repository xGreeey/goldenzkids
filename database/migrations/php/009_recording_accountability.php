<?php
declare(strict_types=1);

/**
 * Extend recording table for accountable audit trail (actor + change detail).
 */
return static function (PDO $conn): void {
    if (!db_table_exists($conn, 'recording')) {
        echo "  [skip] recording table not found.\n";
        return;
    }

    if (!db_column_exists($conn, 'recording', 'event_detail')) {
        $conn->exec(
            'ALTER TABLE recording
             ADD COLUMN event_detail varchar(255) DEFAULT NULL AFTER Event,
             ADD COLUMN actor_company_id varchar(13) DEFAULT NULL AFTER Company_ID,
             ADD KEY idx_recording_actor (actor_company_id)'
        );
        echo "  Added recording.actor_company_id and recording.event_detail.\n";
    }

    $designation = db_fetch_one($conn, "SHOW COLUMNS FROM recording LIKE 'Designation'");
    if ($designation !== null) {
        $type = strtolower((string) ($designation['Type'] ?? ''));
        if (str_contains($type, 'varchar(20)')) {
            $conn->exec('ALTER TABLE recording MODIFY Designation varchar(64) DEFAULT NULL');
            echo "  Expanded recording.Designation to varchar(64).\n";
        }
    }
};
