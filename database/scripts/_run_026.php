<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/db.php';

if (!db_table_exists($conn, 'guard_announcements')) {
    fwrite(STDOUT, "guard_announcements table not found.\n");
    exit(0);
}

$conn->exec("DELETE FROM guard_announcements WHERE title IN ('Shift briefing', 'Uniform inspection')");
fwrite(STDOUT, 'Removed mock guard announcements.' . PHP_EOL);
