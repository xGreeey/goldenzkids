<?php
declare(strict_types=1);

/** 0 = head guard (field portal) */
const AUTH_ROLE_GUARD = 0;
/** Alias for {@see AUTH_ROLE_GUARD} — head guard portal accounts. */
const AUTH_ROLE_HEADGUARD = AUTH_ROLE_GUARD;
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

/** True when plaintext matches stored hash (reuse of current password). */
function auth_password_matches_existing_hash(string $password, string $passwordHash): bool
{
    if (trim($passwordHash) === '') {
        return false;
    }

    return auth_verify_password($password, $passwordHash);
}

function auth_password_policy_valid(string $password): bool
{
    return (bool) preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,64}$/', $password);
}

function auth_username_valid(string $username): bool
{
    return (bool) preg_match('/^[A-Za-z0-9]{1,20}$/', $username);
}

function auth_profile_name_valid(string $name): bool
{
    $name = trim($name);

    return $name !== ''
        && strlen($name) <= 64
        && (bool) preg_match("/^[\p{L}\p{M}'\-. ]+$/u", $name);
}

function auth_users_has_profile_names(PDO $conn): bool
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $cached = db_column_exists($conn, 'users', 'First_Name')
        && db_column_exists($conn, 'users', 'Last_Name');

    return $cached;
}

/** Guard roster / field ID format (e.g. ABC-2024-0021). */
function auth_guard_company_id_valid(string $companyId): bool
{
    return (bool) preg_match('/^ABC-2[0-9]{3}-[0-9]{4}$/i', $companyId);
}

/** Values accepted on the shared login form (admin username or guard company ID). */
function auth_login_identifier_valid(string $identifier): bool
{
    $identifier = trim($identifier);

    return auth_username_valid($identifier) || auth_guard_company_id_valid($identifier);
}

function auth_guard_roster_has(PDO $conn, string $companyId): bool
{
    if ($companyId === '') {
        return false;
    }

    return db_fetch_one(
        $conn,
        'SELECT Company_ID FROM guards WHERE Company_ID = ? LIMIT 1',
        's',
        [$companyId]
    ) !== null;
}

/**
 * Decide portal destination after login (guard roster / role 0 -> guard module).
 */
function auth_resolve_role_at_login(PDO $conn, array $user): int
{
    $companyId = (string) ($user['company_id'] ?? '');
    $stored = auth_normalize_role((int) ($user['role'] ?? AUTH_ROLE_ADMIN));

    if ($stored === AUTH_ROLE_SUPERADMIN) {
        return AUTH_ROLE_SUPERADMIN;
    }

    if ($stored === AUTH_ROLE_ADMIN) {
        return AUTH_ROLE_ADMIN;
    }

    if ($stored === AUTH_ROLE_GUARD || auth_guard_roster_has($conn, $companyId)) {
        return AUTH_ROLE_GUARD;
    }

    return AUTH_ROLE_ADMIN;
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
        AUTH_ROLE_SUPERADMIN => 'Super Administrator',
        AUTH_ROLE_GUARD => 'Head Guard',
        default => 'Administrator',
    };
}

function auth_role_label_for_recording(int $role): string
{
    return match (auth_normalize_role($role)) {
        AUTH_ROLE_SUPERADMIN => 'SUPERADMIN',
        AUTH_ROLE_GUARD => 'GUARD',
        default => 'ADMIN',
    };
}

/**
 * Normalize stored role values (0 = guard, 1 = admin, 2 = superadmin).
 */
function auth_normalize_role(mixed $role): int
{
    $role = (int) $role;
    if ($role === AUTH_ROLE_GUARD || $role === AUTH_ROLE_ADMIN || $role === AUTH_ROLE_SUPERADMIN) {
        return $role;
    }

    return AUTH_ROLE_ADMIN;
}

function auth_role_from_input(string $input): ?int
{
    $input = strtolower(trim($input));
    if ($input === '' || is_numeric($input)) {
        $n = (int) $input;
        if ($n === AUTH_ROLE_GUARD || $n === AUTH_ROLE_ADMIN || $n === AUTH_ROLE_SUPERADMIN) {
            return $n;
        }

        return null;
    }

    return match ($input) {
        'guard', 'headguard', '0' => AUTH_ROLE_GUARD,
        'admin', '1' => AUTH_ROLE_ADMIN,
        'superadmin', 'super', '2' => AUTH_ROLE_SUPERADMIN,
        default => null,
    };
}

function auth_users_table_supports_hashes(PDO $conn): bool
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $cached = db_column_exists($conn, 'users', 'password_hash');

    return $cached;
}

function auth_users_role_column(PDO $conn): string
{
    static $column = null;
    if ($column !== null) {
        return $column;
    }

    if (db_column_exists($conn, 'users', 'role')) {
        $column = 'role';
    } else {
        $column = db_column_exists($conn, 'users', 'role_id') ? 'role_id' : 'role';
    }

    return $column;
}

/**
 * @return list<string>
 */
function auth_permissions_for_role(int $role): array
{
    $role = auth_normalize_role($role);

    $admin = [
        'admin.dashboard.view',
        'admin.inbox.manage',
        'admin.reports.view',
        'admin.duty.view', // Daily Attendance Detail (DAD) registry
        'admin.dad.view',
        'admin.messaging.send',
        'admin.memo.send',
        'admin.legacy_portal',
    ];

    $superadmin = [
        'superadmin.dashboard.view',
        'superadmin.users.manage',
        'superadmin.audit.view',
    ];

    $guard = [
        'guard.portal.access',
        'guard.dashboard.view',
        'guard.inbox.view',
        'guard.corner.view',
        'guard.reports.submit',
    ];

    if ($role === AUTH_ROLE_SUPERADMIN) {
        return array_values(array_unique(array_merge($admin, $superadmin)));
    }

    if ($role === AUTH_ROLE_GUARD) {
        return $guard;
    }

    return $admin;
}

/**
 * @param array<string,mixed> $row
 * @return array{company_id:string,email:?string,password_hash:string,role:int,role_name:string,is_active:int,password_changed_at:?string}
 */
function auth_map_user_row(array $row): array
{
    $role = auth_normalize_role($row['role'] ?? $row['role_id'] ?? AUTH_ROLE_ADMIN);

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

/**
 * Email on file for the currently signed-in portal user.
 */
function auth_current_user_email(PDO $conn): ?string
{
    if (!auth_is_logged_in()) {
        return null;
    }

    $companyId = (string) ($_SESSION['company_id'] ?? '');
    if ($companyId === '') {
        return null;
    }

    $user = auth_find_user_by_company_id($conn, $companyId);
    $email = $user['email'] ?? null;

    return is_string($email) && $email !== '' ? $email : null;
}

function auth_find_user_by_company_id(PDO $conn, string $companyId): ?array
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

    $row = db_fetch_one($conn, $sql, 's', [$companyId]);

    if ($row === null || !(int) $row['is_active']) {
        return null;
    }

    if (!empty($row['locked_until']) && strtotime((string) $row['locked_until']) > time()) {
        return null;
    }

    return auth_map_user_row($row);
}

function auth_is_deactivated_account(PDO $conn, string $companyId): bool
{
    if ($companyId === '') {
        return false;
    }

    $record = db_fetch_one(
        $conn,
        'SELECT is_active FROM users WHERE Company_ID = ? LIMIT 1',
        's',
        [$companyId]
    );

    if ($record === null) {
        return false;
    }

    return ((int) ($record['is_active'] ?? 1)) === 0;
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

/**
 * @param array{company_id:string,role:int,role_name?:string,password_changed_at?:?string} $user
 * @param list<string> $permissions
 */
function auth_update_session_access(array $user, array $permissions): void
{
    $role = auth_normalize_role($user['role']);

    $_SESSION['role'] = $role;
    $_SESSION['role_name'] = $user['role_name'] ?? auth_role_name($role);
    $_SESSION['permissions'] = $permissions;
    $_SESSION['must_change_password'] = auth_password_change_required_for_user($user) ? 1 : 0;

    unset($_SESSION['user_id'], $_SESSION['role_id'], $_SESSION['role_slug']);

    $_SESSION['designation'] = match ($role) {
        AUTH_ROLE_SUPERADMIN => 'SUPERADMIN',
        AUTH_ROLE_GUARD => 'GUARD',
        default => 'ADMIN',
    };
}

function auth_login_session(array $user, array $permissions): void
{
    regenerate_session();

    $_SESSION['company_id'] = $user['company_id'];
    auth_update_session_access($user, $permissions);
}

/**
 * Keep session role/permissions aligned with the database (role changes, new permission slugs).
 */
function auth_sync_session_access(mysqli $conn): void
{
    if (!auth_is_logged_in()) {
        return;
    }

    $companyId = (string) $_SESSION['company_id'];
    $user = auth_find_user_by_company_id($conn, $companyId);
    if ($user === null) {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        header('Location: ' . app_url('index.php'));
        exit();
    }

    $user['role'] = auth_resolve_role_at_login($conn, $user);
    auth_update_session_access($user, auth_permissions_for_role((int) $user['role']));
}

function auth_login_redirect_url(int $role): string
{
    if ((int) ($_SESSION['must_change_password'] ?? 0) === 1) {
        return app_url('auth/change-temporary-password.php');
    }

    $role = auth_normalize_role($role);

    return match ($role) {
        AUTH_ROLE_SUPERADMIN => app_url('superadmin/dashboard.php'),
        AUTH_ROLE_GUARD => app_url('guard/dashboard.php'),
        default => app_url('admin/dashboard.php'),
    };
}

function auth_must_change_password(): bool
{
    return auth_is_logged_in() && (int) ($_SESSION['must_change_password'] ?? 0) === 1;
}

function auth_record_login(PDO $conn, string $companyId): void
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

function auth_record_failed_login(PDO $conn, string $companyId): void
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
    return auth_normalize_role($_SESSION['role'] ?? AUTH_ROLE_ADMIN);
}

function auth_user_can(string $permissionSlug): bool
{
    $perms = $_SESSION['permissions'] ?? [];
    if (!is_array($perms)) {
        return false;
    }

    if (in_array($permissionSlug, $perms, true)) {
        return true;
    }

    // Daily Attendance Detail (DAD) — alias for sessions granted before admin.dad.view
    if ($permissionSlug === 'admin.dad.view' && in_array('admin.duty.view', $perms, true)) {
        return true;
    }

    return false;
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
        'guard', 'headguard' => auth_role_is(AUTH_ROLE_GUARD),
        'admin' => auth_role_is(AUTH_ROLE_ADMIN, AUTH_ROLE_SUPERADMIN),
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

    global $conn;
    if (isset($conn) && $conn instanceof mysqli) {
        auth_sync_session_access($conn);
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
        header('Location: ' . auth_login_redirect_url(auth_user_role()));
        exit();
    }

    if (str_contains($script, '/guard/') && !auth_role_is(AUTH_ROLE_GUARD)) {
        header('Location: ' . auth_login_redirect_url(auth_user_role()));
        exit();
    }
}
