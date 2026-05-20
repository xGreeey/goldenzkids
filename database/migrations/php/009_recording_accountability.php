<?php
declare(strict_types=1);

/**
 * Extend recording table for accountable audit trail (actor + change detail).
 */
return static function (mysqli $conn): void {
    $table = $conn->query("SHOW TABLES LIKE 'recording'");
    if (!$table || $table->num_rows === 0) {
        echo "  [skip] recording table not found.\n";
        return;
    }

    $col = $conn->query("SHOW COLUMNS FROM recording LIKE 'event_detail'");
    if (!$col || $col->num_rows === 0) {
        if (!$conn->query(
            'ALTER TABLE recording
             ADD COLUMN event_detail varchar(255) DEFAULT NULL AFTER Event,
             ADD COLUMN actor_company_id varchar(13) DEFAULT NULL AFTER Company_ID,
             ADD KEY idx_recording_actor (actor_company_id)'
        )) {
            throw new RuntimeException('Could not add recording audit columns: ' . $conn->error);
        }
        echo "  Added recording.actor_company_id and recording.event_detail.\n";
    }

    $designation = $conn->query("SHOW COLUMNS FROM recording LIKE 'Designation'");
    if ($designation && ($row = $designation->fetch_assoc())) {
        $type = strtolower((string) ($row['Type'] ?? ''));
        if (str_contains($type, 'varchar(20)')) {
            $conn->query('ALTER TABLE recording MODIFY Designation varchar(64) DEFAULT NULL');
            echo "  Expanded recording.Designation to varchar(64).\n";
        }
    }
};
