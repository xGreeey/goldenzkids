<?php
declare(strict_types=1);

/**
 * Allow longer portal audit event codes (e.g. ACCOUNT_PASSWORD_RESET, INCIDENT_SUBMITTED).
 */
return static function (PDO $conn): void {
    if (!db_table_exists($conn, 'recording')) {
        echo "  [skip] recording table not found.\n";
        return;
    }

    $eventCol = db_fetch_one($conn, "SHOW COLUMNS FROM recording LIKE 'Event'");
    if ($eventCol === null) {
        return;
    }

    $type = strtolower((string) ($eventCol['Type'] ?? ''));
    if (preg_match('/varchar\((\d+)\)/', $type, $m) && (int) $m[1] < 64) {
        $conn->exec('ALTER TABLE recording MODIFY Event varchar(64) NOT NULL');
        echo "  Expanded recording.Event to varchar(64).\n";
    }
};
