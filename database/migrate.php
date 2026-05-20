<?php
declare(strict_types=1);

/**
 * Apply pending SQL and PHP migrations.
 *
 * CLI:  php database/migrate.php
 * Web:  http://localhost/goldenzkids/database/migrate.php  (localhost only)
 */

$appRoot = dirname(__DIR__);
require_once $appRoot . '/config/bootstrap.php';
require_once $appRoot . '/config/database.php';
require_once $appRoot . '/includes/db.php';

$isCli = PHP_SAPI === 'cli';

if (!$isCli) {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $local = in_array($host, ['localhost', '127.0.0.1'], true) || str_contains($host, 'localhost');
    if (!$local) {
        http_response_code(403);
        exit('Migrations can only be run from CLI or localhost.');
    }
    header('Content-Type: text/plain; charset=UTF-8');
}

function migrate_out(string $line): void
{
    echo $line . (PHP_SAPI === 'cli' ? PHP_EOL : "\n");
    if (PHP_SAPI !== 'cli') {
        flush();
    }
}

function migrate_ensure_tracking_table(mysqli $conn): void
{
    $conn->query(
        'CREATE TABLE IF NOT EXISTS schema_migrations (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INT UNSIGNED NOT NULL DEFAULT 1,
            executed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_schema_migrations_migration (migration)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function migrate_applied(mysqli $conn, string $name): bool
{
    $stmt = $conn->prepare('SELECT 1 FROM schema_migrations WHERE migration = ? LIMIT 1');
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $exists;
}

function migrate_record(mysqli $conn, string $name, int $batch): void
{
    $stmt = $conn->prepare('INSERT INTO schema_migrations (migration, batch) VALUES (?, ?)');
    $stmt->bind_param('si', $name, $batch);
    $stmt->execute();
    $stmt->close();
}

function migrate_run_sql_file(mysqli $conn, string $path): void
{
    $sql = file_get_contents($path);
    if ($sql === false || trim($sql) === '') {
        return;
    }

    if (!$conn->multi_query($sql)) {
        throw new RuntimeException('SQL error in ' . basename($path) . ': ' . $conn->error);
    }

    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());

    if ($conn->errno) {
        throw new RuntimeException('SQL error in ' . basename($path) . ': ' . $conn->error);
    }
}

migrate_out(app_agency_name() . ' — database migrations');
migrate_out(str_repeat('-', 50));

migrate_ensure_tracking_table($conn);

$sqlDir = __DIR__ . '/migrations';
$phpDir = __DIR__ . '/migrations/php';

$sqlFiles = glob($sqlDir . '/*.sql') ?: [];
sort($sqlFiles, SORT_NATURAL);

$phpFiles = glob($phpDir . '/*.php') ?: [];
sort($phpFiles, SORT_NATURAL);

$batchResult = $conn->query('SELECT COALESCE(MAX(batch), 0) + 1 AS next_batch FROM schema_migrations');
$batchRow = $batchResult ? $batchResult->fetch_assoc() : ['next_batch' => 1];
$batch = (int) ($batchRow['next_batch'] ?? 1);

$applied = 0;

foreach ($sqlFiles as $file) {
    $name = basename($file);
    if (migrate_applied($conn, $name)) {
        migrate_out("[skip] {$name}");
        continue;
    }

    migrate_out("[run]  {$name}");
    migrate_run_sql_file($conn, $file);
    migrate_record($conn, $name, $batch);
    $applied++;
}

foreach ($phpFiles as $file) {
    $name = 'php/' . basename($file);
    if (migrate_applied($conn, $name)) {
        migrate_out("[skip] {$name}");
        continue;
    }

    migrate_out("[run]  {$name}");
    $runner = require $file;
    if (!is_callable($runner)) {
        throw new RuntimeException("PHP migration must return a callable: {$file}");
    }
    $runner($conn);
    migrate_record($conn, $name, $batch);
    $applied++;
}

migrate_out(str_repeat('-', 50));
migrate_out($applied > 0 ? "Done. Applied {$applied} migration(s) in batch {$batch}." : 'Nothing to migrate — all up to date.');
