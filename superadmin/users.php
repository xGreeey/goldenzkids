<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/superadmin_accountability.php';
require_once __DIR__ . '/../includes/superadmin_user_form.php';

auth_require_permission('superadmin.users.manage');

$roleCol = auth_users_role_column($conn);
$createForm = superadmin_default_form();
$createError = null;
$openCreateModal = isset($_GET['create']);

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['create_account'])) {
    csrf_verify();
    $result = superadmin_handle_account_post($conn, false, '');
    $createForm = $result['form'];
    $createError = $result['error'];
    if ($result['success'] !== null) {
        header('Location: users.php');
        exit;
    }
    $openCreateModal = true;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['toggle_active'])) {
    csrf_verify();

    $targetId = trim((string) ($_POST['company_id'] ?? ''));
    $newActive = (int) ($_POST['new_active'] ?? 0) === 1 ? 1 : 0;

    if (auth_username_valid($targetId)
        && $targetId !== (string) $_SESSION['company_id']
    ) {
        $ok = db_execute(
            $conn,
            'UPDATE users SET is_active = ? WHERE Company_ID = ?',
            'is',
            [$newActive, $targetId]
        );
        if ($ok) {
            superadmin_log_account_event(
                $conn,
                $targetId,
                $newActive ? 'ACCOUNT_ENABLED' : 'ACCOUNT_DISABLED',
                null
            );
            $qs = [];
            if (isset($_GET['q']) && trim((string) $_GET['q']) !== '') {
                $qs['q'] = trim((string) $_GET['q']);
            }
            if (isset($_GET['role']) && (string) $_GET['role'] !== '') {
                $qs['role'] = (string) $_GET['role'];
            }
            if (isset($_GET['status']) && (string) $_GET['status'] !== '' && (string) $_GET['status'] !== 'all') {
                $qs['status'] = (string) $_GET['status'];
            }
            $tail = $qs === [] ? '' : '?' . http_build_query($qs);
            header('Location: users.php' . $tail);
            exit;
        }
    }
}

$search = trim((string) ($_GET['q'] ?? ''));
$filterRole = $_GET['role'] ?? '';
$filterStatus = trim((string) ($_GET['status'] ?? ''));
if ($filterStatus === '') {
    $filterStatus = 'all';
}
if (!in_array($filterStatus, ['all', 'active', 'inactive'], true)) {
    $filterStatus = 'all';
}
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$params = [];
$types = '';
$where = [];

if ($search !== '') {
    $where[] = '(Company_ID LIKE ? OR Email LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}

if ($filterRole !== '' && $filterRole !== 'all') {
    $roleFilter = auth_role_from_input((string) $filterRole);
    if ($roleFilter !== null) {
        $where[] = "{$roleCol} = ?";
        $params[] = $roleFilter;
        $types .= 'i';
    }
}

if ($filterStatus === 'active') {
    $where[] = 'is_active = 1';
} elseif ($filterStatus === 'inactive') {
    $where[] = 'is_active = 0';
}

$fromSql = " FROM users";
if ($where !== []) {
    $fromSql .= ' WHERE ' . implode(' AND ', $where);
}

$totalRows = 0;
$countSql = "SELECT COUNT(*) AS c" . $fromSql;
if ($params === []) {
    $countResult = $conn->query($countSql);
} else {
    $countResult = db_query($conn, $countSql, $types, $params);
}
if ($countResult) {
    $totalRows = (int) (($countResult->fetch_assoc()['c'] ?? 0));
}

$totalPages = max(1, (int) ceil($totalRows / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

$sql = "SELECT Company_ID, Email, {$roleCol} AS role, is_active, last_login_at, created_at"
    . $fromSql
    . ' ORDER BY created_at DESC LIMIT ? OFFSET ?';

$users = [];
if ($params === []) {
    $result = db_query($conn, $sql, 'ii', [$perPage, $offset]);
} else {
    $result = db_query($conn, $sql, $types . 'ii', [...$params, $perPage, $offset]);
}

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

function superadmin_role_badge(int $role): string
{
    return match (auth_normalize_role($role)) {
        AUTH_ROLE_ADMIN => 'badge--admin',
        AUTH_ROLE_SUPERADMIN => 'badge--super',
        default => 'badge--guard',
    };
}

function superadmin_page_url(int $targetPage, string $search, string $filterRole, string $filterStatus = 'all'): string
{
    $q = ['page' => max(1, $targetPage)];
    if ($search !== '') {
        $q['q'] = $search;
    }
    if ($filterRole !== '' && $filterRole !== 'all') {
        $q['role'] = $filterRole;
    }
    if ($filterStatus !== '' && $filterStatus !== 'all') {
        $q['status'] = $filterStatus;
    }
    return 'users.php?' . http_build_query($q);
}

$superadminNavActive = 'users';
$superadminMobileTitle = 'User Accounts';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?= mobile_meta_tags() ?>
    <title><?= e(app_agency_name()) ?> | User Accounts</title>
    <script src="https://kit.fontawesome.com/3142eebea3.js" crossorigin="anonymous"></script>
    <?= app_fonts_link() ?>
    <style>
<?php admin_shell_styles(); ?>
<?php superadmin_page_styles(); ?>
<?php superadmin_modal_styles(); ?>
    </style>
</head>
<body class="light-mode superadmin-portal">

<?php require __DIR__ . '/../includes/superadmin_sidebar.php'; ?>

    <main class="app-main">
        <header class="page-header">
            <h1 class="page-title">Portal accounts</h1>
            <p class="page-subtitle">Search, filter, and manage head guard, admin, and superadmin portal accounts.</p>
        </header>

        <div class="toolbar">
            <form method="GET" class="filter-form">
                <div class="form-field">
                    <label for="q" class="label-with-icon"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Search</label>
                    <input type="search" id="q" name="q" value="<?= e($search) ?>" placeholder="Username or email">
                </div>
                <div class="form-field">
                    <label for="role" class="label-with-icon"><i class="fa-solid fa-filter" aria-hidden="true"></i> Role</label>
                    <select id="role" name="role">
                        <option value="all"<?= $filterRole === '' || $filterRole === 'all' ? ' selected' : '' ?>>All roles</option>
                        <option value="0"<?= $filterRole === '0' ? ' selected' : '' ?>>Head guard</option>
                        <option value="1"<?= $filterRole === '1' ? ' selected' : '' ?>>Admin</option>
                        <option value="2"<?= $filterRole === '2' ? ' selected' : '' ?>>Superadmin</option>
                    </select>
                </div>
                <div class="form-field">
                    <label for="status" class="label-with-icon"><i class="fa-solid fa-signal" aria-hidden="true"></i> Status</label>
                    <select id="status" name="status">
                        <option value="all"<?= $filterStatus === 'all' ? ' selected' : '' ?>>All statuses</option>
                        <option value="active"<?= $filterStatus === 'active' ? ' selected' : '' ?>>Active</option>
                        <option value="inactive"<?= $filterStatus === 'inactive' ? ' selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <button type="submit" class="btn-primary"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filter</button>
            </form>
            <button type="button" class="btn-primary" id="openCreateAccountModal"><i class="fa-solid fa-user-plus" aria-hidden="true"></i> Create account</button>
        </div>

        <section class="card-panel">
            <h2 class="panel-title">All accounts</h2>
            <?php if ($users === []): ?>
                <p class="empty-state"><i class="fa-solid fa-users-slash" aria-hidden="true"></i>No accounts match your filters.</p>
            <?php else: ?>
                <div class="data-table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th><i class="fa-solid fa-id-card th-icon" aria-hidden="true"></i>Username</th>
                                <th><i class="fa-solid fa-envelope th-icon" aria-hidden="true"></i>Email</th>
                                <th><i class="fa-solid fa-user-tag th-icon" aria-hidden="true"></i>Role</th>
                                <th><i class="fa-solid fa-signal th-icon" aria-hidden="true"></i>Status</th>
                                <th><i class="fa-solid fa-clock th-icon" aria-hidden="true"></i>Last login</th>
                                <th><i class="fa-solid fa-gear th-icon" aria-hidden="true"></i>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <?php
                                $uid = (string) $user['Company_ID'];
                                $role = auth_normalize_role($user['role'] ?? 0);
                                $active = (int) ($user['is_active'] ?? 0) === 1;
                                $isSelf = $uid === (string) $_SESSION['company_id'];
                                ?>
                                <tr>
                                    <td class="mono"><?= e($uid) ?></td>
                                    <td><?= e((string) ($user['Email'] ?? '')) ?></td>
                                    <td><span class="badge role-badge"><?= e(auth_role_name($role)) ?></span></td>
                                    <td>
                                        <span class="badge status-badge <?= $active ? 'status-badge--active' : 'status-badge--inactive' ?>">
                                            <?= $active ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td class="mono"><?= e((string) ($user['last_login_at'] ?? '—')) ?></td>
                                    <td>
                                        <a href="create-user.php?edit=<?= rawurlencode($uid) ?>" class="btn-ghost"><i class="fa-solid fa-pen" aria-hidden="true"></i> Edit</a>
                                        <?php if (!$isSelf): ?>
                                            <?php
                                            $confirmToggle = $active
                                                ? 'Are you sure you want to deactivate this account (' . $uid . ')? They will not be able to sign in until reactivated.'
                                                : 'Are you sure you want to activate this account (' . $uid . ')? They will be able to sign in to the portal.';
                                            ?>
                                            <form method="POST" class="js-confirm-submit" style="display:inline;" data-confirm="<?= e($confirmToggle) ?>">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="company_id" value="<?= e($uid) ?>">
                                                <input type="hidden" name="new_active" value="<?= $active ? '0' : '1' ?>">
                                                <button type="submit" name="toggle_active" value="1" class="btn-ghost">
                                                    <i class="fa-solid <?= $active ? 'fa-user-slash' : 'fa-user-check' ?>" aria-hidden="true"></i>
                                                    <?= $active ? 'Deactivate' : 'Activate' ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($totalPages > 1): ?>
                    <nav class="pagination" aria-label="Accounts pagination">
                        <?php if ($page > 1): ?>
                            <a href="<?= e(superadmin_page_url($page - 1, $search, (string) $filterRole, $filterStatus)) ?>" aria-label="Previous page">Prev</a>
                        <?php endif; ?>
                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <?php if ($p === $page): ?>
                                <span class="current" aria-current="page"><?= e((string) $p) ?></span>
                            <?php else: ?>
                                <a href="<?= e(superadmin_page_url($p, $search, (string) $filterRole, $filterStatus)) ?>"><?= e((string) $p) ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <a href="<?= e(superadmin_page_url($page + 1, $search, (string) $filterRole, $filterStatus)) ?>" aria-label="Next page">Next</a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <?php superadmin_create_account_modal($createForm, $createError, $openCreateModal); ?>
    </main>
</div>

<?php admin_shell_scripts(); ?>
</body>
</html>
