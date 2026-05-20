<?php
declare(strict_types=1);

/**
 * Copy legacy `users` rows (Pin, Designation) into `portal_users` with bcrypt hashes.
 * Safe to re-run: skips company_ids that already exist in portal_users.
 */
return static function (mysqli $conn): void {
    $tableCheck = $conn->query("SHOW TABLES LIKE 'users'");
    if (!$tableCheck || $tableCheck->num_rows === 0) {
        echo "  [skip] Legacy table `users` not found.\n";
        return;
    }

    $columns = $conn->query("SHOW COLUMNS FROM users");
    if (!$columns) {
        throw new RuntimeException('Could not inspect legacy users table.');
    }

    $colNames = [];
    while ($col = $columns->fetch_assoc()) {
        $colNames[] = strtolower((string) $col['Field']);
    }

    if (!in_array('company_id', $colNames, true) || !in_array('pin', $colNames, true)) {
        echo "  [skip] Legacy `users` table missing Company_ID or Pin column.\n";
        return;
    }

    $roleStmt = $conn->prepare('SELECT id, slug FROM roles WHERE slug IN (?, ?)');
    $adminSlug = 'admin';
    $guardSlug = 'guard';
    $roleStmt->bind_param('ss', $adminSlug, $guardSlug);
    $roleStmt->execute();
    $roleResult = $roleStmt->get_result();
    $roleIds = [];
    while ($row = $roleResult->fetch_assoc()) {
        $roleIds[strtolower((string) $row['slug'])] = (int) $row['id'];
    }
    $roleStmt->close();

    if (!isset($roleIds['admin'], $roleIds['guard'])) {
        throw new RuntimeException('Roles admin/guard must exist before migrating users. Run 002_seed_roles_permissions.sql first.');
    }

    $selectSql = 'SELECT Company_ID, Pin, Designation, Email FROM users';
    if (!in_array('email', $colNames, true)) {
        $selectSql = 'SELECT Company_ID, Pin, Designation, NULL AS Email FROM users';
    }

    $legacy = $conn->query($selectSql);
    if (!$legacy) {
        throw new RuntimeException('Legacy user query failed: ' . $conn->error);
    }

    $insert = $conn->prepare(
        'INSERT INTO portal_users (company_id, email, password_hash, role_id, is_active)
         VALUES (?, ?, ?, ?, 1)'
    );

    $migrated = 0;
    $skipped = 0;

    while ($row = $legacy->fetch_assoc()) {
        $companyId = strtoupper(trim((string) ($row['Company_ID'] ?? '')));
        if ($companyId === '') {
            continue;
        }

        $exists = db_query($conn, 'SELECT id FROM portal_users WHERE company_id = ? LIMIT 1', 's', [$companyId]);
        if ($exists && $exists->num_rows > 0) {
            $skipped++;
            continue;
        }

        $designation = strtoupper(trim((string) ($row['Designation'] ?? 'GUARD')));
        $roleId = $designation === 'ADMIN' ? $roleIds['admin'] : $roleIds['guard'];

        $pin = str_pad((string) ($row['Pin'] ?? ''), 6, '0', STR_PAD_LEFT);
        if (!preg_match('/^[0-9]{6}$/', $pin)) {
            echo "  [warn] Skipping {$companyId}: invalid legacy PIN format.\n";
            continue;
        }

        $hash = password_hash($pin, PASSWORD_DEFAULT);
        $email = isset($row['Email']) ? trim((string) $row['Email']) : '';
        $email = $email !== '' ? $email : null;

        $insert->bind_param('sssi', $companyId, $email, $hash, $roleId);
        if ($insert->execute()) {
            $migrated++;
        } else {
            echo "  [warn] Failed to migrate {$companyId}: " . $insert->error . "\n";
        }
    }

    $insert->close();
    echo "  Migrated {$migrated} user(s), skipped {$skipped} existing portal_users.\n";
};
