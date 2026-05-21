<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Run from CLI only.\n");
    exit(1);
}

$appRoot = dirname(__DIR__, 2);
require_once $appRoot . '/config/bootstrap.php';
require_once $appRoot . '/config/database.php';
require_once $appRoot . '/includes/db.php';
require_once $appRoot . '/includes/auth.php';

$companyId = strtoupper(trim($argv[1] ?? ''));
$password = $argv[2] ?? '';
$roleInput = trim($argv[3] ?? '');
$email = isset($argv[4]) ? trim($argv[4]) : '';
if ($email === '') {
    $email = strtolower($companyId) . '@noemail.local';
}

if ($companyId === '' || $password === '' || $roleInput === '') {
    fwrite(STDERR, "Usage: php database/scripts/create_user.php COMPANY_ID PASSWORD ROLE [EMAIL]\n");
    fwrite(STDERR, "  ROLE: 0|guard  1|admin  2|superadmin\n");
    fwrite(STDERR, "  Example: php database/scripts/create_user.php COMPANY_ID 123456 admin\n");
    exit(1);
}

if (!preg_match('/^ABC-2[0-9]{3}-[0-9]{4}$/', $companyId)) {
    fwrite(STDERR, "Invalid company_id format.\n");
    exit(1);
}

$role = auth_role_from_input($roleInput);
if ($role === null) {
    fwrite(STDERR, "Invalid role. Use 1 (admin) or 2 (superadmin).\n");
    exit(1);
}

$roleCol = auth_users_role_column($conn);
$hash = password_hash($password, PASSWORD_DEFAULT);

$exists = db_query($conn, 'SELECT Company_ID FROM users WHERE Company_ID = ? LIMIT 1', 's', [$companyId]);

if ($exists && $exists->num_rows > 0) {
    $sql = "UPDATE users SET password_hash = ?, {$roleCol} = ?, Email = COALESCE(?, Email),
            is_active = 1, password_changed_at = NOW() WHERE Company_ID = ?";
    $ok = db_execute($conn, $sql, 'siss', [$hash, $role, $email, $companyId]);
} else {
    $sql = "INSERT INTO users (Company_ID, Email, password_hash, {$roleCol}, is_active, password_changed_at)
            VALUES (?, ?, ?, ?, 1, NOW())";
    $ok = db_execute($conn, $sql, 'sssi', [$companyId, $email, $hash, $role]);
}

$roleName = auth_role_name($role);
echo $ok
    ? "Saved {$companyId} as {$roleName} (role={$role}).\n"
    : "Failed: {$conn->error}\n";
exit($ok ? 0 : 1);
