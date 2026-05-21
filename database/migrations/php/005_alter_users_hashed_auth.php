<?php
declare(strict_types=1);

/**
 * Idempotent upgrade for legacy users table (skipped when columns already exist).
 */
return static function (PDO $conn): void {
    $tableCheck = $conn->query("SHOW TABLES LIKE 'users'");
    if (!$tableCheck || $tableCheck->num_rows === 0) {
        echo "  [skip] users table not found.\n";
        return;
    }

    $cols = $conn->query('SHOW COLUMNS FROM users');
    $colNames = [];
    while ($c = $cols->fetch(PDO::FETCH_ASSOC)) {
        $colNames[] = strtolower((string) $c['Field']);
    }

    $addColumn = static function (PDO $conn, string $sql, string $label) use ($colNames): void {
        if (preg_match('/ADD COLUMN\s+`?(\w+)`?/i', $sql, $m)) {
            $name = strtolower($m[1]);
            if (in_array($name, $colNames, true)) {
                echo "  [skip] users.{$name} already exists.\n";
                return;
            }
        }
        if (!$conn->query($sql)) {
            throw new RuntimeException("Could not add {$label}: " . $conn->error);
        }
        echo "  Added {$label}.\n";
    };

    $addColumn(
        $conn,
        'ALTER TABLE users ADD COLUMN password_hash VARCHAR(255) NULL AFTER Pin',
        'password_hash'
    );
    $addColumn(
        $conn,
        'ALTER TABLE users ADD COLUMN role_id TINYINT UNSIGNED NULL AFTER password_hash',
        'role_id'
    );
    $addColumn(
        $conn,
        'ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER role_id',
        'is_active'
    );
    $addColumn(
        $conn,
        'ALTER TABLE users ADD COLUMN failed_login_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER is_active',
        'failed_login_attempts'
    );
    $addColumn(
        $conn,
        'ALTER TABLE users ADD COLUMN locked_until DATETIME NULL AFTER failed_login_attempts',
        'locked_until'
    );
    $addColumn(
        $conn,
        'ALTER TABLE users ADD COLUMN last_login_at DATETIME NULL AFTER locked_until',
        'last_login_at'
    );
    $addColumn(
        $conn,
        'ALTER TABLE users ADD COLUMN password_changed_at DATETIME NULL AFTER last_login_at',
        'password_changed_at'
    );
    $addColumn(
        $conn,
        'ALTER TABLE users ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER password_changed_at',
        'updated_at'
    );

    $idx = $conn->query("SHOW INDEX FROM users WHERE Key_name = 'idx_users_role'");
    if (!$idx || $idx->num_rows === 0) {
        if (in_array('role_id', $colNames, true) || $conn->query('SHOW COLUMNS FROM users LIKE "role_id"')->num_rows > 0) {
            $conn->query('ALTER TABLE users ADD KEY idx_users_role (role_id)');
            echo "  Added idx_users_role.\n";
        }
    }

    $rolesExist = $conn->query("SHOW TABLES LIKE 'roles'");
    $fkExists = $conn->query(
        "SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND CONSTRAINT_NAME = 'fk_users_role' LIMIT 1"
    );
    if ($rolesExist && $rolesExist->num_rows > 0 && (!$fkExists || $fkExists->num_rows === 0)) {
        @$conn->query(
            'ALTER TABLE users ADD CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles (id)'
        );
        echo "  Added fk_users_role (if roles table present).\n";
    }
};
