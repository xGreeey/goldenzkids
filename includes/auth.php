<?php
declare(strict_types=1);

/** 0 = headguard (field / guard portal) */
const AUTH_ROLE_HEADGUARD = 0;
/** 1 = admin (operations dashboard) */
const AUTH_ROLE_ADMIN = 1;
/** 2 = superadmin (full admin access) */
const AUTH_ROLE_SUPERADMIN = 2;

/**
 * Password hashing (bcrypt via PASSWORD_DEFAULT).
 */
function auth_hash_password(string $password): string
{
    return password_hash($password, PASSWORD_DEFAULT);
}

function auth_verify_password(string $password, string $passwordHash): bool
{
    if ($passwordHash === '') {
        return false;
    }

    return password_verify($password, $passwordHash);
}

function auth_password_policy_valid(string $password): bool
{
    return (bool) preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,64}$/', $password);
}

function auth_username_valid(string $username): bool
{
    return (bool) preg_match('/^[A-Za-z0-9]{1,20}$/', $username);
}

function auth_password_change_required_for_user(array $user): bool
{
    $changedAt = trim((string) ($user['password_changed_at'] ?? ''));
    if ($changedAt === '' || $changedAt === '0000-00-00 00:00:00') {
        return true;
    }

    return false;
}

function auth_role_name(int $role): string
{
    return match (auth_normalize_role($role)) {
        AUTH_ROLE_ADMIN => 'Administrator',
        AUTH_ROLE_SUPERADMIN => 'Super Administrator',
        default => 'Head Guard',
    };
}

function auth_role_label_for_recording(int $role): string
{
    return match (auth_normalize_role($role)) {
        AUTH_ROLE_ADMIN => 'ADMIN',
        AUTH_ROLE_SUPERADMIN => 'SUPERADMIN',
        default => 'HEADGUARD',
    };
}

function auth_normalize_role(mixed $role): int
{
    $role = (int) $role;
    if ($role < AUTH_ROLE_HEADGUARD || $role > AUTH_ROLE_SUPERADMIN) {
        return AUTH_ROLE_HEADGUARD;
    }

    return $role;
}

function auth_role_from_input(string $input): ?int
{
    $input = strtolower(trim($input));
    if ($input === '' || is_numeric($input)) {
        $n = (int) $input;
        if ($n >= AUTH_ROLE_HEADGUARD && $n <= AUTH_ROLE_SUPERADMIN) {
            return $n;
        }
    }

    return match ($input) {
        'headguard', 'guard', '0' => AUTH_ROLE_HEADGUARD,
        'admin', '1' => AUTH_ROLE_ADMIN,
        'superadmin', 'super', '2' => AUTH_ROLE_SUPERADMIN,
        default => null,
    };
}

function auth_users_table_supports_hashes(mysqli $conn): bool
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $cols = $conn->query("SHOW COLUMNS FROM users LIKE 'password_hash'");
    $cached = $cols && $cols->num_rows > 0;

    return $cached;
}

function auth_users_role_column(mysqli $conn): string
{
    static $column = null;
    if ($column !== null) {
        return $column;
    }

    $role = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
    if ($role && $role->num_rows > 0) {
        $column = 'role';
        return $column;
    }

    $roleId = $conn->query("SHOW COLUMNS FROM users LIKE 'role_id'");
    $column = ($roleId && $roleId->num_rows > 0) ? 'role_id' : 'role';

    return $column;
}

/**
 * @return list<string>
 */
function auth_permissions_for_role(int $role): array
{
    $role = auth_normalize_role($role);

    $guard = [
        'guard.portal.access',
        'guard.inbox.view',
        'guard.messaging.send',
        'guard.corner.view',
        'guard.reports.submit',
    ];

    $admin = [
        'admin.dashboard.view',
        'admin.inbox.manage',
        'admin.messaging.send',
        'admin.memo.send',
        'admin.legacy_portal',
    ];

    $superadmin = [
        'superadmin.dashboard.view',
        'superadmin.users.manage',
        'superadmin.audit.view',
    ];

    if ($role === AUTH_ROLE_SUPERADMIN) {
        return array_values(array_unique(array_merge($guard, $admin, $superadmin)));
    }

    if ($role === AUTH_ROLE_ADMIN) {
        return $admin;
    }

    return $guard;
}

/**
 * @param array<string,mixed> $row
 * @return array{company_id:string,email:?string,password_hash:string,role:int,role_name:string,is_active:int,password_changed_at:?string}
 */
function auth_map_user_row(array $row): array
{
    $role = auth_normalize_role($row['role'] ?? $row['role_id'] ?? AUTH_ROLE_HEADGUARD);

    return [
        'company_id' => (string) $row['company_id'],
        'email' => isset($row['email']) && $row['email'] !== null && $row['email'] !== ''
            ? (string) $row['email']
            : null,
        'password_hash' => (string) $row['password_hash'],
        'role' => $role,
        'role_name' => auth_role_name($role),
        'is_active' => (int) ($row['is_active'] ?? 1),
        'password_changed_at' => isset($row['password_changed_at']) && $row['password_changed_at'] !== null
            ? (string) $row['password_changed_at']
            : null,
    ];
}

function auth_find_user_by_company_id(mysqli $conn, string $companyId): ?array
{
    if (!auth_users_table_supports_hashes($conn)) {
        return null;
    }

    $roleCol = auth_users_role_column($conn);
    $sql = "SELECT u.Company_ID AS company_id, u.Email AS email, u.password_hash,
                   u.{$roleCol} AS role, u.is_active, u.locked_until, u.password_changed_at
            FROM users u
            WHERE u.Company_ID = ? AND u.password_hash IS NOT NULL AND u.password_hash != ''
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('s', $companyId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || !(int) $row['is_active']) {
        return null;
    }

    if (!empty($row['locked_until']) && strtotime((string) $row['locked_until']) > time()) {
        return null;
    }

    return auth_map_user_row($row);
}

/**
 * @param array{company_id:string,role:int,role_name?:string} $user
 * @param list<string> $permissions
 */
function auth_is_logged_in(): bool
{
    return isset($_SESSION['company_id'])
        && (string) $_SESSION['company_id'] !== '';
}

/**
 * Send already-authenticated visitors to their home dashboard (public pages only).
 */
function auth_redirect_if_authenticated(): void
{
    if (!auth_is_logged_in() || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        return;
    }

    header('Location: ' . auth_login_redirect_url(auth_user_role()));
    exit();
}

/**
 * Release the session file lock so other tabs/requests can load the same session.
 */
function auth_session_release_lock(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
}

function auth_login_session(array $user, array $permissions): void
{
    regenerate_session();

    $role = auth_normalize_role($user['role']);

    $_SESSION['company_id'] = $user['company_id'];
    $_SESSION['role'] = $role;
    $_SESSION['role_name'] = $user['role_name'] ?? auth_role_name($role);
    $_SESSION['permissions'] = $permissions;
    $_SESSION['must_change_password'] = auth_password_change_required_for_user($user) ? 1 : 0;

    unset($_SESSION['user_id'], $_SESSION['role_id'], $_SESSION['role_slug']);

    $_SESSION['designation'] = match ($role) {
        AUTH_ROLE_ADMIN => 'ADMIN',
        AUTH_ROLE_SUPERADMIN => 'SUPERADMIN',
        default => 'GUARD',
    };
}

function auth_login_redirect_url(int $role): string
{
    if ((int) ($_SESSION['must_change_password'] ?? 0) === 1) {
        return app_url('auth/change-temporary-password.php');
    }

    $role = auth_normalize_role($role);

    return match ($role) {
        AUTH_ROLE_SUPERADMIN => app_url('superadmin/dashboard.php'),
        AUTH_ROLE_ADMIN => app_url('admin/dashboard.php'),
        default => app_url('guard/portal.php'),
    };
}

function auth_must_change_password(): bool
{
    return auth_is_logged_in() && (int) ($_SESSION['must_change_password'] ?? 0) === 1;
}

function auth_record_login(mysqli $conn, string $companyId): void
{
    if ($companyId === '' || !auth_users_table_supports_hashes($conn)) {
        return;
    }

    db_execute(
        $conn,
        'UPDATE users SET last_login_at = NOW(), failed_login_attempts = 0, locked_until = NULL WHERE Company_ID = ?',
        's',
        [$companyId]
    );
}

function auth_record_failed_login(mysqli $conn, string $companyId): void
{
    if ($companyId === '' || !auth_users_table_supports_hashes($conn)) {
        return;
    }

    db_execute(
        $conn,
        'UPDATE users SET failed_login_attempts = LEAST(failed_login_attempts + 1, 255),
         locked_until = IF(failed_login_attempts >= 4, DATE_ADD(NOW(), INTERVAL 15 MINUTE), locked_until)
         WHERE Company_ID = ?',
        's',
        [$companyId]
    );
}

function auth_user_role(): int
{
    return auth_normalize_role($_SESSION['role'] ?? AUTH_ROLE_HEADGUARD);
}

function auth_user_can(string $permissionSlug): bool
{
    $perms = $_SESSION['permissions'] ?? [];
    if (!is_array($perms)) {
        return false;
    }

    return in_array($permissionSlug, $perms, true);
}

function auth_role_is(int ...$roles): bool
{
    $current = auth_user_role();

    foreach ($roles as $role) {
        if ($current === auth_normalize_role($role)) {
            return true;
        }
    }

    return false;
}

/** @deprecated Use auth_role_is(AUTH_ROLE_ADMIN) */
function auth_user_has_role(string $roleSlug): bool
{
    return match (strtolower($roleSlug)) {
        'admin' => auth_role_is(AUTH_ROLE_ADMIN, AUTH_ROLE_SUPERADMIN),
        'guard', 'headguard' => auth_role_is(AUTH_ROLE_HEADGUARD),
        'superadmin' => auth_role_is(AUTH_ROLE_SUPERADMIN),
        default => false,
    };
}

function auth_require_permission(string $permissionSlug): void
{
    if (!isset($_SESSION['company_id'])) {
        header('Location: ' . app_url('index.php'));
        exit();
    }

    if (!auth_user_can($permissionSlug)) {
        http_response_code(403);
        exit('You do not have permission to access this resource.');
    }
}

function auth_require_role(int ...$roles): void
{
    if (!isset($_SESSION['company_id'])) {
        header('Location: ' . app_url('index.php'));
        exit();
    }

    if (!auth_role_is(...$roles)) {
        http_response_code(403);
        exit('You do not have permission to access this resource.');
    }
}

function auth_enforce_area_access(): void
{
    if (!isset($_SESSION['company_id'])) {
        return;
    }

    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));

    if (auth_must_change_password()) {
        $isChangePasswordPage = str_contains($script, '/auth/change-temporary-password.php');
        $isLogoutPage = str_contains($script, '/auth/logout');
        if (!$isChangePasswordPage && !$isLogoutPage) {
            header('Location: ' . app_url('auth/change-temporary-password.php'));
            exit();
        }
    }

    if (str_contains($script, '/superadmin/') && !auth_role_is(AUTH_ROLE_SUPERADMIN)) {
        header('Location: ' . auth_login_redirect_url(auth_user_role()));
        exit();
    }

    if (str_contains($script, '/admin/') && !auth_role_is(AUTH_ROLE_ADMIN, AUTH_ROLE_SUPERADMIN)) {
        header('Location: ' . app_url('guard/portal.php'));
        exit();
    }

    if (str_contains($script, '/guard/') && !auth_role_is(AUTH_ROLE_HEADGUARD)) {
        header('Location: ' . auth_login_redirect_url(auth_user_role()));
        exit();
    }
}

/**
 * Fallback: plain Pin column — only before password migration.
 * @return array{user:array,permissions:list<string>}|null
 */
function auth_attempt_legacy_login(mysqli $conn, string $companyId, string $pin): ?array
{
    $pinCol = $conn->query("SHOW COLUMNS FROM users LIKE 'Pin'");
    if (!$pinCol || $pinCol->num_rows === 0) {
        return null;
    }

    if (auth_users_table_supports_hashes($conn)) {
        $hasHash = db_query(
            $conn,
            'SELECT password_hash FROM users WHERE Company_ID = ? AND password_hash IS NOT NULL AND password_hash != "" LIMIT 1',
            's',
            [$companyId]
        );
        if ($hasHash && $hasHash->num_rows > 0) {
            return null;
        }
    }

    $stmt = $conn->prepare('SELECT Pin, Designation, Email FROM users WHERE Company_ID = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('s', $companyId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }

    $storedPin = str_pad((string) ($row['Pin'] ?? ''), 6, '0', STR_PAD_LEFT);
    if (!hash_equals($storedPin, $pin)) {
        return null;
    }

    $designation = strtoupper(trim((string) ($row['Designation'] ?? 'GUARD')));
    $role = match ($designation) {
        'ADMIN' => AUTH_ROLE_ADMIN,
        'SUPERADMIN', 'SUPER' => AUTH_ROLE_SUPERADMIN,
        default => AUTH_ROLE_HEADGUARD,
    };

    $user = auth_map_user_row([
        'company_id' => $companyId,
        'email' => $row['Email'] ?? null,
        'password_hash' => '',
        'role' => $role,
        'is_active' => 1,
    ]);

    return [
        'user' => $user,
        'permissions' => auth_permissions_for_role($role),
    ];
}
