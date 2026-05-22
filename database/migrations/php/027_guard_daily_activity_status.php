<?php
declare(strict_types=1);

return static function (PDO $conn): void {
    if (!db_table_exists($conn, 'guard_daily_activity_submissions')) {
        echo "  [skip] guard_daily_activity_submissions missing — run 022 first.\n";

        return;
    }

    $col = db_fetch_one(
        $conn,
        "SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'guard_daily_activity_submissions'
           AND COLUMN_NAME = 'status'
         LIMIT 1"
    );
    if ($col !== null) {
        echo "  [skip] guard_daily_activity_submissions.status already exists.\n";

        return;
    }

    $conn->exec(
        "ALTER TABLE guard_daily_activity_submissions
         ADD COLUMN status VARCHAR(32) NOT NULL DEFAULT 'pending' AFTER activity_mode,
         ADD KEY idx_guard_da_status (status)"
    );

    echo "  [ok] Added status column to guard_daily_activity_submissions.\n";
};
