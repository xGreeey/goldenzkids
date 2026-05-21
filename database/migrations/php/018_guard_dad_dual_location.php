<?php
declare(strict_types=1);

return static function (PDO $conn): void {
    if (!db_table_exists($conn, 'guard_dad_submissions')) {
        echo "  [skip] guard_dad_submissions missing — run 017 first.\n";

        return;
    }

    $cols = [
        'sheet_latitude' => 'DECIMAL(10, 7) NULL',
        'sheet_longitude' => 'DECIMAL(10, 7) NULL',
        'sheet_accuracy_m' => 'DECIMAL(8, 2) NULL',
        'sheet_location_label' => 'VARCHAR(512) NULL',
        'evidence_latitude' => 'DECIMAL(10, 7) NULL',
        'evidence_longitude' => 'DECIMAL(10, 7) NULL',
        'evidence_accuracy_m' => 'DECIMAL(8, 2) NULL',
        'evidence_location_label' => 'VARCHAR(512) NULL',
    ];

    foreach ($cols as $name => $def) {
        if (!db_column_exists($conn, 'guard_dad_submissions', $name)) {
            $conn->exec("ALTER TABLE guard_dad_submissions ADD COLUMN {$name} {$def}");
            echo "  [ok] Added guard_dad_submissions.{$name}.\n";
        }
    }

    if (db_column_exists($conn, 'guard_dad_submissions', 'submit_latitude')) {
        $conn->exec(
            'UPDATE guard_dad_submissions
             SET evidence_latitude = submit_latitude,
                 evidence_longitude = submit_longitude,
                 evidence_accuracy_m = submit_accuracy_m,
                 evidence_location_label = location_label
             WHERE evidence_latitude IS NULL AND submit_latitude IS NOT NULL'
        );
        echo "  [ok] Backfilled evidence location from legacy submit_* columns.\n";
    }
};
