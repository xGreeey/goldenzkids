<?php
declare(strict_types=1);

/**
 * Hash legacy Pin values into users.password_hash and assign role_id from Designation.
 * Also syncs portal_users when that table exists.
 */
return static function (mysqli $conn): void {
    $tableCheck = $conn->query("SHOW TABLES LIKE 'users'");
    if (!$tableCheck || $tableCheck->num_rows === 0) {
        echo "  [skip] Table `users` not found.\n";
        return;
    }

    $columns = $conn->query('SHOW COLUMNS FROM users');
    if (!$columns) {
        throw new RuntimeException('Could not inspect users table.');
    }

    $colNames = [];
    while ($col = $columns->fetch_assoc()) {
        $colNames[] = strtolower((string) $col['Field']);
    }

    if (!in_array('password_hash', $colNames, true)) {
        echo "  [skip] Run php/005_alter_users_hashed_auth.php first (password_hash column missing).\n";
        return;
    }

    $hasPin = in_array('pin', $colNames, true);

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
        throw new RuntimeException('Roles admin/guard must exist. Run 002_seed_roles_permissions.sql first.');
    }

    $selectCols = 'Company_ID, Designation, Email';
    if ($hasPin) {
        $selectCols = 'Company_ID, Pin, Designation, Email';
    }
    $selectCols .= ', password_hash';

    $rows = $conn->query("SELECT {$selectCols} FROM users");
    if (!$rows) {
        throw new RuntimeException('Failed to read users: ' . $conn->error);
    }

    $updateUser = $conn->prepare(
        'UPDATE users SET password_hash = ?, role_id = ?, password_changed_at = NOW() WHERE Company_ID = ?'
    );

    $hasPortalUsers = false;
    $portalCheck = $conn->query("SHOW TABLES LIKE 'portal_users'");
    if ($portalCheck && $portalCheck->num_rows > 0) {
        $hasPortalUsers = true;
    }

    $updatePortal = null;
    $insertPortal = null;
    if ($hasPortalUsers) {
        $updatePortal = $conn->prepare(
            'UPDATE portal_users SET password_hash = ?, role_id = ?, email = ?, is_active = 1, password_changed_at = NOW()
             WHERE company_id = ?'
        );
        $insertPortal = $conn->prepare(
            'INSERT INTO portal_users (company_id, email, password_hash, role_id, is_active, password_changed_at)
             VALUES (?, ?, ?, ?, 1, NOW())'
        );
    }

    $hashed = 0;
    $skipped = 0;

    while ($row = $rows->fetch_assoc()) {
        $companyId = strtoupper(trim((string) ($row['Company_ID'] ?? '')));
        if ($companyId === '') {
            continue;
        }

        $existingHash = trim((string) ($row['password_hash'] ?? ''));
        if ($existingHash !== '' && str_starts_with($existingHash, '$2')) {
            $skipped++;
            continue;
        }

        if (!$hasPin) {
            echo "  [warn] {$companyId}: no Pin column and no password_hash — set password manually.\n";
            continue;
        }

        $pin = str_pad((string) ($row['Pin'] ?? ''), 6, '0', STR_PAD_LEFT);
        if (!preg_match('/^[0-9]{6}$/', $pin)) {
            echo "  [warn] Skipping {$companyId}: invalid PIN (must be 6 digits).\n";
            continue;
        }

        $designation = strtoupper(trim((string) ($row['Designation'] ?? 'GUARD')));
        $roleId = $designation === 'ADMIN' ? $roleIds['admin'] : $roleIds['guard'];
        $hash = password_hash($pin, PASSWORD_DEFAULT);
        $email = trim((string) ($row['Email'] ?? ''));
        $email = $email !== '' ? $email : null;

        $updateUser->bind_param('sis', $hash, $roleId, $companyId);
        if (!$updateUser->execute()) {
            echo "  [warn] Failed to update users.{$companyId}: " . $updateUser->error . "\n";
            continue;
        }

        if ($hasPortalUsers && $updatePortal && $insertPortal) {
            $exists = db_query($conn, 'SELECT id FROM portal_users WHERE company_id = ? LIMIT 1', 's', [$companyId]);
            if ($exists && $exists->num_rows > 0) {
                $updatePortal->bind_param('siss', $hash, $roleId, $email, $companyId);
                $updatePortal->execute();
            } else {
                $insertPortal->bind_param('sssi', $companyId, $email, $hash, $roleId);
                $insertPortal->execute();
            }
        }

        $hashed++;
    }

    $updateUser->close();
    if ($updatePortal) {
        $updatePortal->close();
    }
    if ($insertPortal) {
        $insertPortal->close();
    }

    // Sync from portal_users when Pin was already removed or never hashed in users
    if ($hasPortalUsers && $hashed === 0) {
        $sync = $conn->query(
            'UPDATE users u
             INNER JOIN portal_users pu ON pu.company_id COLLATE utf8mb4_unicode_ci = u.Company_ID
             SET u.password_hash = pu.password_hash,
                 u.role_id = pu.role_id,
                 u.password_changed_at = NOW()
             WHERE u.password_hash IS NULL OR u.password_hash = ""'
        );
        if ($sync) {
            $synced = $conn->affected_rows;
            echo "  Synced {$synced} user(s) from portal_users into users.\n";
            $hashed = $synced;
        }
    }

    echo "  Hashed {$hashed} user(s) in `users`, skipped {$skipped} (already hashed).\n";

    $pinStillExists = $conn->query("SHOW COLUMNS FROM users LIKE 'Pin'");
    if ($pinStillExists && $pinStillExists->num_rows > 0) {
        if ($conn->query('ALTER TABLE users DROP COLUMN Pin')) {
            echo "  Dropped plain-text Pin column from users.\n";
        } else {
            echo "  [warn] Could not drop Pin column: {$conn->error}\n";
        }
    }
};
