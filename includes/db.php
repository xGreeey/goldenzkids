<?php
declare(strict_types=1);

/**
 * Run a prepared SELECT and return mysqli_result or false.
 */
function db_query(mysqli $conn, string $sql, string $types = '', array $params = []): mysqli_result|false
{
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        return false;
    }

    if ($types !== '' && $params !== []) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        $stmt->close();
        return false;
    }

    $result = $stmt->get_result();
    $stmt->close();

    return $result;
}

/**
 * Run a prepared statement that does not return rows (INSERT/UPDATE/DELETE).
 */
function db_execute(mysqli $conn, string $sql, string $types = '', array $params = []): bool
{
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        return false;
    }

    if ($types !== '' && $params !== []) {
        $stmt->bind_param($types, ...$params);
    }

    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}
