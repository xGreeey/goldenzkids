<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/superadmin_accountability.php';

auth_require_permission('superadmin.users.manage');

$roleCol = auth_users_role_column($conn);
$success = null;
$error = null;

$editId = strtoupper(trim((string) ($_GET['edit'] ?? '')));
$isEdit = $editId !== '' && preg_match('/^ABC-2[0-9]{3}-[0-9]{4}$/', $editId);

$form = [
    'company_id' => $editId,
    'email' => '',
    'role' => (string) AUTH_ROLE_HEADGUARD,
    'is_active' => '1',
    'password' => '',
];
$beforeState = null;
$accountTrail = [];
$editingSelf = $isEdit && $editId === (string) ($_SESSION['company_id'] ?? '');

if ($isEdit) {
    $existing = db_query(
        $conn,
        "SELECT Company_ID, Email, {$roleCol} AS role, is_active FROM users WHERE Company_ID = ? LIMIT 1",
        's',
        [$editId]
    );
    if (!$existing || $existing->num_rows === 0) {
        $error = 'Account not found.';
        $isEdit = false;
    } else {
        $row = $existing->fetch_assoc();
        $form['email'] = (string) ($row['Email'] ?? '');
        $form['role'] = (string) auth_normalize_role($row['role'] ?? AUTH_ROLE_HEADGUARD);
        $form['is_active'] = (string) ((int) ($row['is_active'] ?? 1));
        $beforeState = [
            'email' => $form['email'],
            'role' => (int) $form['role'],
            'is_active' => (int) $form['is_active'],
        ];
        $accountTrail = superadmin_account_audit_trail($conn, $editId);
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();

    $form['company_id'] = strtoupper(trim((string) ($_POST['company_id'] ?? '')));
    $form['email'] = trim((string) ($_POST['email'] ?? ''));
    $form['role'] = (string) ($_POST['role'] ?? '0');
    $form['is_active'] = isset($_POST['is_active']) ? '1' : '0';
    $form['password'] = trim((string) ($_POST['password'] ?? ''));

    $role = auth_role_from_input($form['role']);
    if ($role === null) {
        $role = auth_normalize_role((int) $form['role']);
    }

    if (!preg_match('/^ABC-2[0-9]{3}-[0-9]{4}$/', $form['company_id'])) {
        $error = 'Employee ID must match ABC-2###-#### (example: ABC-2024-0001).';
    } elseif ($form['email'] === '' || !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'A valid email address is required.';
    } elseif ($form['password'] !== '' && !preg_match('/^[0-9]{6}$/', $form['password'])) {
        $error = 'Access code must be exactly 6 digits.';
    } elseif (!$isEdit && $form['password'] === '') {
        $error = 'Access code is required for new accounts.';
    } else {
        $exists = db_query($conn, 'SELECT Company_ID FROM users WHERE Company_ID = ? LIMIT 1', 's', [$form['company_id']]);
        $alreadyExists = $exists && $exists->num_rows > 0;

        if (!$isEdit && $alreadyExists) {
            $error = 'This employee ID is already registered.';
        } elseif ($isEdit && $form['company_id'] !== $editId) {
            $error = 'Employee ID cannot be changed when editing.';
        } else {
            $active = (int) $form['is_active'];
            $targetId = $isEdit ? $editId : $form['company_id'];

            if ($isEdit && $editId === (string) ($_SESSION['company_id'] ?? '')) {
                if ($active !== 1) {
                    $error = 'You cannot deactivate your own account.';
                } elseif ($role !== AUTH_ROLE_SUPERADMIN) {
                    $error = 'You cannot change your own role.';
                } else {
                    $role = AUTH_ROLE_SUPERADMIN;
                    $active = 1;
                }
            }

            if ($error === null && $form['password'] !== '') {
                $hash = auth_hash_password($form['password']);
                if ($alreadyExists) {
                    $ok = db_execute(
                        $conn,
                        "UPDATE users SET Email = ?, password_hash = ?, {$roleCol} = ?, is_active = ?,
                         password_changed_at = NOW() WHERE Company_ID = ?",
                        'siiss',
                        [$form['email'], $hash, $role, $active, $targetId]
                    );
                } else {
                    $ok = db_execute(
                        $conn,
                        "INSERT INTO users (Company_ID, Email, password_hash, {$roleCol}, is_active, password_changed_at)
                         VALUES (?, ?, ?, ?, ?, NOW())",
                        'sssii',
                        [$targetId, $form['email'], $hash, $role, $active]
                    );
                }
            } elseif ($error === null && $alreadyExists) {
                $ok = db_execute(
                    $conn,
                    "UPDATE users SET Email = ?, {$roleCol} = ?, is_active = ? WHERE Company_ID = ?",
                    'siis',
                    [$form['email'], $role, $active, $targetId]
                );
            } elseif ($error === null) {
                $error = 'Access code is required for new accounts.';
                $ok = false;
            }

            if ($error === null && !isset($ok)) {
                $ok = false;
            }

            if ($error === null && !empty($ok)) {
                $afterState = [
                    'email' => $form['email'],
                    'role' => $role,
                    'is_active' => $active,
                    'password_changed' => $form['password'] !== '',
                ];
                superadmin_log_account_diff(
                    $conn,
                    $targetId,
                    $beforeState ?? [],
                    $afterState,
                    !$alreadyExists
                );
                $roleName = auth_role_name($role);
                $success = $isEdit
                    ? "Updated {$targetId} ({$roleName})."
                    : "Created account {$targetId} ({$roleName}).";
                if (!$isEdit) {
                    $form = [
                        'company_id' => '',
                        'email' => '',
                        'role' => (string) AUTH_ROLE_HEADGUARD,
                        'is_active' => '1',
                        'password' => '',
                    ];
                }
            } elseif ($error === null) {
                $error = 'Could not save account. ' . $conn->error;
            }
        }
    }
}

$superadminNavActive = 'create-user';
$superadminMobileTitle = $isEdit ? 'Edit Account' : 'Create Account';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ABC Security | <?= $isEdit ? 'Edit' : 'Create' ?> Account</title>
    <script src="https://kit.fontawesome.com/3142eebea3.js" crossorigin="anonymous"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
<?php require __DIR__ . '/../includes/admin_shell.css.php'; ?>
<?php require __DIR__ . '/../includes/superadmin_page.css.php'; ?>
    </style>
</head>
<body class="light-mode">

<?php require __DIR__ . '/../includes/superadmin_sidebar.php'; ?>

    <main class="app-main">
        <header class="page-header">
            <h1 class="page-title"><?= $isEdit ? 'Edit account' : 'Create account' ?></h1>
        </header>

        <?php if ($success !== null): ?>
            <div class="alert alert--success" role="status"><?= e($success) ?></div>
        <?php endif; ?>
        <?php if ($error !== null): ?>
            <div class="alert alert--error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <section class="card-panel">
            <h2 class="panel-title"><i class="fa-solid fa-id-card" aria-hidden="true"></i> Account details</h2>
            <form method="POST" class="form-grid" autocomplete="off">
                <?= csrf_field() ?>

                <div class="form-field">
                    <label for="company_id" class="label-with-icon"><i class="fa-solid fa-id-badge" aria-hidden="true"></i> Employee ID</label>
                    <input type="text" id="company_id" name="company_id" required
                           pattern="ABC-2[0-9]{3}-[0-9]{4}"
                           value="<?= e($form['company_id']) ?>"
                           <?= $isEdit ? 'readonly' : '' ?>
                           placeholder="ABC-2024-0001">
                </div>

                <div class="form-field">
                    <label for="email" class="label-with-icon"><i class="fa-solid fa-envelope" aria-hidden="true"></i> Email</label>
                    <input type="email" id="email" name="email" required value="<?= e($form['email']) ?>">
                </div>

                <div class="form-field">
                    <label for="role" class="label-with-icon"><i class="fa-solid fa-user-shield" aria-hidden="true"></i> Role</label>
                    <select id="role" name="role" required<?= $editingSelf ? ' disabled' : '' ?>>
                        <option value="0"<?= $form['role'] === '0' ? ' selected' : '' ?>>Head guard</option>
                        <option value="1"<?= $form['role'] === '1' ? ' selected' : '' ?>>Administrator</option>
                        <option value="2"<?= $form['role'] === '2' ? ' selected' : '' ?>>Super administrator</option>
                    </select>
                    <?php if ($editingSelf): ?>
                        <input type="hidden" name="role" value="2">
                    <?php endif; ?>
                </div>

                <div class="form-field">
                    <label for="password" class="label-with-icon"><i class="fa-solid fa-key" aria-hidden="true"></i> Access code (6 digits)</label>
                    <input type="password" id="password" name="password" inputmode="numeric" pattern="[0-9]{6}"
                           maxlength="6" autocomplete="new-password"
                           <?= $isEdit ? '' : 'required' ?>
                           placeholder="<?= $isEdit ? 'Leave blank to keep current' : '123456' ?>">
                </div>

                <div class="form-field">
                    <label class="label-with-icon">
                        <input type="checkbox" name="is_active" value="1"<?= $form['is_active'] === '1' ? ' checked' : '' ?><?= $editingSelf ? ' disabled checked' : '' ?>>
                        <i class="fa-solid fa-toggle-on" aria-hidden="true"></i> Account is active
                    </label>
                    <?php if ($editingSelf): ?>
                        <input type="hidden" name="is_active" value="1">
                    <?php endif; ?>
                </div>

                <div>
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                        <?= $isEdit ? 'Save changes' : 'Create account' ?>
                    </button>
                    <a href="users.php" class="btn-ghost" style="margin-left:8px;"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to users</a>
                </div>
            </form>
        </section>

        <?php if ($isEdit && $accountTrail !== []): ?>
        <section class="card-panel">
            <h2 class="panel-title"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Record for <?= e($editId) ?></h2>
            <div class="data-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><i class="fa-solid fa-clock th-icon" aria-hidden="true"></i>Time</th>
                            <th><i class="fa-solid fa-user-shield th-icon" aria-hidden="true"></i>Performed by</th>
                            <th><i class="fa-solid fa-bolt th-icon" aria-hidden="true"></i>Event</th>
                            <th><i class="fa-solid fa-align-left th-icon" aria-hidden="true"></i>Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($accountTrail as $entry): ?>
                            <?php $ev = (string) ($entry['Event'] ?? ''); ?>
                            <tr>
                                <td class="mono"><?= e((string) ($entry['Time_Of_Event'] ?? '')) ?></td>
                                <td class="mono"><?= e(superadmin_audit_actor_label($entry)) ?></td>
                                <td>
                                    <span class="event-cell">
                                        <i class="fa-solid <?= e(superadmin_event_icon($ev)) ?>" aria-hidden="true"></i>
                                        <?= e(superadmin_event_label($ev)) ?>
                                    </span>
                                </td>
                                <td><?= e((string) ($entry['event_detail'] ?? '—')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>
    </main>
</div>

<script>
<?php require __DIR__ . '/../includes/admin_shell.js.php'; ?>
</script>
</body>
</html>
