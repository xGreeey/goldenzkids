<?php
declare(strict_types=1);

/**
 * Replace roles table FK with users.role TINYINT:
 *   0 = headguard, 1 = admin, 2 = superadmin
 * Drops roles, permissions, role_permissions, portal_users.
 */
return static function (mysqli $conn): void {
    $tableCheck = $conn->query("SHOW TABLES LIKE 'users'");
    if (!$tableCheck || $tableCheck->num_rows === 0) {
        echo "  [skip] users table not found.\n";
        return;
    }

    $cols = $conn->query('SHOW COLUMNS FROM users');
    $colNames = [];
    while ($c = $cols->fetch_assoc()) {
        $colNames[] = strtolower((string) $c['Field']);
    }

    $dropFk = static function (mysqli $conn, string $table, string $constraint): void {
        $conn->query("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
    };

    if (in_array('role_id', $colNames, true)) {
        foreach (['fk_users_role'] as $fk) {
            @$dropFk($conn, 'users', $fk);
        }

        if (!in_array('role', $colNames, true)) {
            if (!$conn->query(
                "ALTER TABLE users ADD COLUMN role TINYINT UNSIGNED NOT NULL DEFAULT 0
                 COMMENT '0=headguard,1=admin,2=superadmin' AFTER role_id"
            )) {
                throw new RuntimeException('Could not add users.role: ' . $conn->error);
            }
            echo "  Added users.role column.\n";
        }

        $rolesExist = $conn->query("SHOW TABLES LIKE 'roles'");
        if ($rolesExist && $rolesExist->num_rows > 0) {
            $conn->query(
                'UPDATE users u
                 INNER JOIN roles r ON r.id = u.role_id
                 SET u.role = CASE r.slug
                     WHEN "admin" THEN 1
                     WHEN "superadmin" THEN 2
                     ELSE 0
                 END'
            );
            echo "  Mapped users.role from roles.slug (guard → 0 headguard).\n";
        } else {
            $conn->query(
                'UPDATE users SET role = CASE
                     WHEN role_id = 1 THEN 1
                     WHEN role_id = 2 THEN 2
                     ELSE 0
                 END'
            );
            echo "  Mapped users.role from role_id (1=admin, 2=superadmin, else headguard).\n";
        }

        if (!$conn->query('ALTER TABLE users DROP COLUMN role_id')) {
            throw new RuntimeException('Could not drop users.role_id: ' . $conn->error);
        }
        echo "  Dropped users.role_id.\n";
    } elseif (!in_array('role', $colNames, true)) {
        if (!$conn->query(
            "ALTER TABLE users ADD COLUMN role TINYINT UNSIGNED NOT NULL DEFAULT 0
             COMMENT '0=headguard,1=admin,2=superadmin'"
        )) {
            throw new RuntimeException('Could not add users.role: ' . $conn->error);
        }
        echo "  Added users.role column (fresh).\n";
    } else {
        echo "  users.role already present.\n";
    }

    $portalExists = $conn->query("SHOW TABLES LIKE 'portal_users'");
    if ($portalExists && $portalExists->num_rows > 0) {
        @$conn->query('ALTER TABLE portal_users DROP FOREIGN KEY fk_portal_users_role');
        $conn->query('DROP TABLE IF EXISTS portal_users');
        echo "  Dropped portal_users.\n";
    }

    $conn->query('DROP TABLE IF EXISTS role_permissions');
    $conn->query('DROP TABLE IF EXISTS permissions');
    $conn->query('DROP TABLE IF EXISTS roles');
    echo "  Dropped roles, permissions, role_permissions.\n";
};
