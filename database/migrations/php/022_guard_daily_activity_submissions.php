<?php
declare(strict_types=1);

return static function (PDO $conn): void {
    if (db_table_exists($conn, 'guard_daily_activity_submissions')) {
        echo "  [skip] guard_daily_activity_submissions already exists.\n";

        return;
    }

    $sql = file_get_contents(dirname(__DIR__) . '/022_guard_daily_activity_submissions.sql');
    if ($sql === false) {
        throw new RuntimeException('Could not read 022_guard_daily_activity_submissions.sql');
    }

    foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
        if ($statement === '' || str_starts_with($statement, '--')) {
            continue;
        }
        $conn->exec($statement);
    }

    echo "  [ok] Created guard_daily_activity_submissions.\n";
};
