<?php
declare(strict_types=1);

/**
 * Bind positional parameters using mysqli-style type string (s/i/d/b).
 */
function db_bind(PDOStatement $stmt, string $types, array $params): void
{
    if ($types === '' || $params === []) {
        return;
    }

    foreach ($params as $index => $value) {
        $type = $types[$index] ?? 's';
        $paramType = match ($type) {
            'i' => PDO::PARAM_INT,
            'b' => PDO::PARAM_LOB,
            default => PDO::PARAM_STR,
        };
        $stmt->bindValue($index + 1, $value, $paramType);
    }
}

/**
 * Run a prepared SELECT and return PDOStatement or false.
 */
function db_query(PDO $conn, string $sql, string $types = '', array $params = []): PDOStatement|false
{
    try {
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            return false;
        }

        db_bind($stmt, $types, $params);
        $stmt->execute();

        return $stmt;
    } catch (PDOException $e) {
        error_log('db_query failed: ' . $e->getMessage());

        return false;
    }
}

/**
 * Run a prepared statement that does not return rows (INSERT/UPDATE/DELETE).
 */
function db_execute(PDO $conn, string $sql, string $types = '', array $params = []): bool
{
    try {
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            return false;
        }

        db_bind($stmt, $types, $params);

        return $stmt->execute();
    } catch (PDOException $e) {
        error_log('db_execute failed: ' . $e->getMessage());

        return false;
    }
}

/**
 * @return array<string,mixed>|null
 */
function db_fetch_one(PDO $conn, string $sql, string $types = '', array $params = []): ?array
{
    $stmt = db_query($conn, $sql, $types, $params);
    if ($stmt === false) {
        return null;
    }

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row === false ? null : $row;
}

/**
 * @return list<array<string,mixed>>
 */
function db_fetch_all(PDO $conn, string $sql, string $types = '', array $params = []): array
{
    $stmt = db_query($conn, $sql, $types, $params);
    if ($stmt === false) {
        return [];
    }

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function db_table_exists(PDO $conn, string $table): bool
{
    $stmt = db_query($conn, 'SHOW TABLES LIKE ?', 's', [$table]);

    return $stmt !== false && $stmt->fetch(PDO::FETCH_NUM) !== false;
}

function db_column_exists(PDO $conn, string $table, string $column): bool
{
    $stmt = db_query(
        $conn,
        'SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '` LIKE ?',
        's',
        [$column]
    );

    return $stmt !== false && $stmt->fetch(PDO::FETCH_NUM) !== false;
}

function db_last_insert_id(PDO $conn): int
{
    return (int) $conn->lastInsertId();
}

/**
 * Run raw SQL (migrations). Splits on semicolon boundaries outside quotes.
 */
function db_exec_sql_file(PDO $conn, string $sql): void
{
    $sql = trim($sql);
    if ($sql === '') {
        return;
    }

    foreach (db_split_sql_statements($sql) as $statement) {
        $statement = trim($statement);
        if ($statement === '' || str_starts_with($statement, '--')) {
            continue;
        }
        $conn->exec($statement);
    }
}

/**
 * @return list<string>
 */
function db_split_sql_statements(string $sql): array
{
    $statements = [];
    $buffer = '';
    $inSingle = false;
    $inDouble = false;
    $len = strlen($sql);

    for ($i = 0; $i < $len; $i++) {
        $char = $sql[$i];
        $prev = $i > 0 ? $sql[$i - 1] : '';

        if ($char === "'" && !$inDouble && $prev !== '\\') {
            $inSingle = !$inSingle;
        } elseif ($char === '"' && !$inSingle && $prev !== '\\') {
            $inDouble = !$inDouble;
        }

        if ($char === ';' && !$inSingle && !$inDouble) {
            $statements[] = $buffer;
            $buffer = '';
            continue;
        }

        $buffer .= $char;
    }

    if (trim($buffer) !== '') {
        $statements[] = $buffer;
    }

    return $statements;
}
