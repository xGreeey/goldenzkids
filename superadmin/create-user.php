<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/superadmin_accountability.php';
require_once __DIR__ . '/../includes/superadmin_user_form.php';

auth_require_permission('superadmin.users.manage');

$editId = trim((string) ($_GET['edit'] ?? ''));
$isEdit = $editId !== '' && auth_username_valid($editId);

if (!$isEdit && ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: users.php?create=1');
    exit;
}

$success = null;
$error = null;
$form = superadmin_default_form($editId);
$accountTrail = [];
$editingSelf = $isEdit && $editId === (string) ($_SESSION['company_id'] ?? '');

if ($isEdit && ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    $roleCol = auth_users_role_column($conn);
    $existing = db_query(
        $conn,
        "SELECT Company_ID, Email, {$roleCol} AS role, is_active FROM users WHERE Company_ID = ? LIMIT 1",
        's',
        [$editId]
    );
    if (!$existing || $existing->num_rows === 0) {
        header('Location: users.php');
        exit;
    }
    $row = $existing->fetch_assoc();
    $form['email'] = (string) ($row['Email'] ?? '');
    $form['role'] = (string) auth_normalize_role($row['role'] ?? AUTH_ROLE_ADMIN);
    $form['is_active'] = (string) ((int) ($row['is_active'] ?? 1));
    $accountTrail = superadmin_account_audit_trail($conn, $editId);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();
    $originalId = trim((string) ($_POST['original_company_id'] ?? ''));
    if ($originalId === '' || !auth_username_valid($originalId)) {
        header('Location: users.php');
        exit;
    }
    $editId = $originalId;
    $isEdit = true;

    $result = superadmin_handle_account_post($conn, $isEdit, $editId);
    $form = $result['form'];
    $error = $result['error'];
    $success = $result['success'];
    $accountTrail = $result['account_trail'];
    $editingSelf = $result['editing_self'] ?? false;
    $isEdit = $result['is_edit'];
    $editId = $result['edit_id'];

    if (!$isEdit) {
        header('Location: users.php?create=1');
        exit;
    }

    if ($success !== null && $error === null) {
        $origPost = trim((string) ($_POST['original_company_id'] ?? ''));
        if ($origPost !== '' && $editId !== '' && $origPost !== $editId && auth_username_valid($editId)) {
            header('Location: create-user.php?edit=' . rawurlencode($editId));
            exit;
        }
    }
}

$superadminNavActive = 'users';
$superadminMobileTitle = $isEdit ? 'Edit Account' : 'Create Account';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?= mobile_meta_tags() ?>
    <title><?= e(app_agency_name()) ?> | <?= $isEdit ? 'Edit' : 'Create' ?> Account</title>
    <script src="https://kit.fontawesome.com/3142eebea3.js" crossorigin="anonymous"></script>
    <?= app_fonts_link() ?>
    <style>
<?php admin_shell_styles(); ?>
<?php superadmin_page_styles(); ?>
    </style>
</head>
<body class="light-mode superadmin-portal">

<?php require __DIR__ . '/../includes/superadmin_sidebar.php'; ?>

    <main class="app-main">
        <header class="page-header">
            <h1 class="page-title">Edit account</h1>
            <p class="page-subtitle">Update role, access, and account status for this employee.</p>
        </header>

        <?php if ($success !== null): ?>
            <div class="alert alert--success" role="status"><?= e($success) ?></div>
        <?php endif; ?>
        <?php if ($error !== null): ?>
            <div class="alert alert--error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <section class="card-panel">
            <h2 class="panel-title">Account details</h2>
            <form method="POST" class="form-grid" autocomplete="off"
                  data-sa-edit-account-form
                  data-orig-company="<?= e($form['company_id']) ?>"
                  data-orig-email="<?= e($form['email']) ?>"
                  data-orig-role="<?= e($form['role']) ?>"
                  data-orig-active="<?= $form['is_active'] === '1' ? '1' : '0' ?>"
                  data-editing-self="<?= $editingSelf ? '1' : '0' ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="original_company_id" value="<?= e($editId) ?>">

                <div class="form-field">
                    <label for="company_id" class="label-with-icon"><i class="fa-solid fa-id-badge" aria-hidden="true"></i> Username</label>
                    <input type="text" id="company_id" name="company_id" required
                           data-sa-edit-field="company"
                           pattern="[A-Za-z0-9]{1,20}"
                           maxlength="20"
                           value="<?= e($form['company_id']) ?>"
                           readonly
                           placeholder="Username"<?= $editingSelf ? ' title="Your username cannot be changed"' : '' ?>>
                </div>

                <div class="form-field">
                    <label for="email" class="label-with-icon"><i class="fa-solid fa-envelope" aria-hidden="true"></i> Email</label>
                    <input type="email" id="email" name="email" required data-sa-edit-field="email"
                           value="<?= e($form['email']) ?>" readonly>
                </div>

                <div class="form-field">
                    <label for="role" class="label-with-icon"><i class="fa-solid fa-user-shield" aria-hidden="true"></i> Role</label>
                    <?php if (!$editingSelf): ?>
                        <input type="hidden" name="role" value="<?= e($form['role']) ?>" data-sa-role-fallback>
                    <?php endif; ?>
                    <select id="role" required data-sa-edit-field="role" disabled<?= $editingSelf ? ' data-sa-role-locked="1"' : '' ?>>
                        <option value="0"<?= $form['role'] === '0' ? ' selected' : '' ?>>Head guard</option>
                        <option value="1"<?= $form['role'] === '1' ? ' selected' : '' ?>>Administrator</option>
                        <option value="2"<?= $form['role'] === '2' ? ' selected' : '' ?>>Super administrator</option>
                    </select>
                    <?php if ($editingSelf): ?>
                        <input type="hidden" name="role" value="2">
                    <?php endif; ?>
                </div>

                <div class="form-field form-field--checkbox">
                    <label class="checkbox-row" for="is_active">
                        <span class="checkbox-row__label">
                            <i class="fa-solid fa-toggle-on" aria-hidden="true"></i>
                            Account is active
                        </span>
                        <?php if (!$editingSelf): ?>
                            <input type="hidden" name="is_active" value="<?= $form['is_active'] === '1' ? '1' : '0' ?>" data-sa-active-fallback>
                        <?php endif; ?>
                        <input type="checkbox" id="is_active" value="1" data-sa-edit-field="active"
                            <?= $form['is_active'] === '1' ? ' checked' : '' ?>
                            disabled>
                    </label>
                    <?php if ($editingSelf): ?>
                        <input type="hidden" name="is_active" value="1">
                    <?php endif; ?>
                </div>

                <div class="form-toolbar-sa" data-sa-toolbar-view>
                    <button type="button" class="btn-ghost" data-sa-edit-acc-start>
                        <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                        Edit
                    </button>
                </div>
                <div class="form-toolbar-sa is-hidden" data-sa-toolbar-editing>
                    <button type="button" class="btn-ghost" data-sa-edit-acc-cancel>
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        Cancel
                    </button>
                </div>

                <div class="form-actions is-hidden" data-sa-save-wrap>
                    <button type="submit" class="btn-primary" data-sa-edit-acc-submit>
                        <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                        Save changes
                    </button>
                </div>
                <div class="form-actions">
                    <a href="users.php" class="btn-ghost"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to users</a>
                </div>
            </form>
        </section>

        <?php if ($isEdit && $accountTrail !== []): ?>
        <section class="card-panel">
            <h2 class="panel-title">Record for <?= e($editId) ?></h2>
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

<?php admin_shell_scripts(); ?>
</body>
</html>
