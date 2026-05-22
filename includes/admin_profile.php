<?php
declare(strict_types=1);

require_once __DIR__ . '/superadmin_user_form.php';

/** @return array{company_id: string, first_name: string, last_name: string, email: string, role_name: string} */
function admin_profile_defaults(string $companyId = ''): array
{
    return [
        'company_id' => $companyId,
        'first_name' => '',
        'last_name' => '',
        'email' => '',
        'role_name' => auth_role_name(auth_user_role()),
    ];
}

/** @return array{company_id: string, first_name: string, last_name: string, email: string, role_name: string}|null */
function admin_profile_load(PDO $conn, string $companyId): ?array
{
    if ($companyId === '') {
        return null;
    }

    $roleCol = auth_users_role_column($conn);
    $hasProfileNames = auth_users_has_profile_names($conn);
    $nameCols = $hasProfileNames ? ', First_Name, Last_Name' : '';
    $user = db_fetch_one(
        $conn,
        "SELECT Company_ID, Email{$nameCols}, {$roleCol} AS role FROM users WHERE Company_ID = ? AND is_active = 1 LIMIT 1",
        's',
        [$companyId]
    );

    if ($user === null) {
        return null;
    }

    return [
        'company_id' => (string) ($user['Company_ID'] ?? ''),
        'first_name' => (string) ($user['First_Name'] ?? ''),
        'last_name' => (string) ($user['Last_Name'] ?? ''),
        'email' => (string) ($user['Email'] ?? ''),
        'role_name' => auth_role_name(auth_normalize_role($user['role'] ?? AUTH_ROLE_ADMIN)),
    ];
}

/**
 * @return array{success: ?string, error: ?string, form: array{company_id: string, first_name: string, last_name: string, email: string, role_name: string}}
 */
function admin_handle_profile_post(PDO $conn, string $sessionCompanyId): array
{
    $hasProfileNames = auth_users_has_profile_names($conn);
    $form = admin_profile_defaults($sessionCompanyId);
    $error = null;
    $success = null;

    $existing = admin_profile_load($conn, $sessionCompanyId);
    if ($existing === null) {
        return [
            'success' => null,
            'error' => 'Your account could not be loaded.',
            'form' => $form,
        ];
    }

    $form['company_id'] = trim((string) ($_POST['company_id'] ?? $existing['company_id']));
    $form['first_name'] = trim((string) ($_POST['first_name'] ?? $existing['first_name']));
    $form['last_name'] = trim((string) ($_POST['last_name'] ?? $existing['last_name']));
    $form['email'] = trim((string) ($_POST['email'] ?? ''));
    $form['role_name'] = $existing['role_name'];

    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $wantsPasswordChange = $newPassword !== '' || $confirmPassword !== '' || $currentPassword !== '';

    if (!auth_username_valid($form['company_id'])) {
        $error = 'Username must be alphanumeric and up to 20 characters.';
    } elseif ($hasProfileNames && !auth_profile_name_valid($form['first_name'])) {
        $error = 'First name is required (letters, up to 64 characters).';
    } elseif ($hasProfileNames && !auth_profile_name_valid($form['last_name'])) {
        $error = 'Last name is required (letters, up to 64 characters).';
    } elseif ($form['email'] === '' || !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'A valid email address is required.';
    } elseif (
        auth_role_is(AUTH_ROLE_SUPERADMIN)
        && $form['company_id'] !== $sessionCompanyId
    ) {
        $error = 'You cannot change your own username.';
        $form['company_id'] = $sessionCompanyId;
    } elseif ($wantsPasswordChange) {
        $user = auth_find_user_by_company_id($conn, $sessionCompanyId);
        if ($user === null || !auth_verify_password($currentPassword, (string) ($user['password_hash'] ?? ''))) {
            $error = 'Current password is incorrect.';
        } elseif (!auth_password_policy_valid($newPassword)) {
            $error = 'New password must be 8-64 characters with uppercase, lowercase, number, and symbol.';
        } elseif (!hash_equals($newPassword, $confirmPassword)) {
            $error = 'Password confirmation does not match.';
        } elseif (auth_password_matches_existing_hash($newPassword, (string) ($user['password_hash'] ?? ''))) {
            $error = 'You cannot reuse your previous password. Choose a different one.';
        }
    }

    if ($error === null && strcasecmp($form['email'], $existing['email']) !== 0) {
        if (db_fetch_one(
            $conn,
            'SELECT Company_ID FROM users WHERE Email = ? AND Company_ID != ? LIMIT 1',
            'ss',
            [$form['email'], $sessionCompanyId]
        ) !== null) {
            $error = 'That email address is already in use.';
        }
    }

    if ($error === null && $form['company_id'] !== $sessionCompanyId) {
        if (db_fetch_one(
            $conn,
            'SELECT Company_ID FROM users WHERE Company_ID = ? LIMIT 1',
            's',
            [$form['company_id']]
        ) !== null) {
            $error = 'This username is already taken.';
        }
    }

    if ($error !== null) {
        return ['success' => null, 'error' => $error, 'form' => $form];
    }

    $finalId = $form['company_id'];
    $conn->beginTransaction();

    try {
        if ($finalId !== $sessionCompanyId) {
            if (!superadmin_rename_portal_user_company_id($conn, $sessionCompanyId, $finalId)) {
                throw new RuntimeException('rename');
            }
        }

        $sql = 'UPDATE users SET Email = ?';
        $types = 's';
        $params = [$form['email']];

        if ($hasProfileNames) {
            $sql .= ', First_Name = ?, Last_Name = ?';
            $types .= 'ss';
            $params[] = $form['first_name'];
            $params[] = $form['last_name'];
        }

        if ($wantsPasswordChange) {
            $sql .= ', password_hash = ?, password_changed_at = NOW(), failed_login_attempts = 0, locked_until = NULL';
            $types .= 's';
            $params[] = auth_hash_password($newPassword);
        }

        $sql .= ' WHERE Company_ID = ?';
        $types .= 's';
        $params[] = $finalId;

        if (!db_execute($conn, $sql, $types, $params)) {
            throw new RuntimeException('update');
        }

        $conn->commit();
    } catch (\Throwable $e) {
        $conn->rollback();

        return [
            'success' => null,
            'error' => 'Could not save your profile. Please try again.',
            'form' => $form,
        ];
    }

    require_once __DIR__ . '/portal_audit.php';
    $profileDetail = $finalId !== $sessionCompanyId
        ? 'Username changed to ' . $finalId
        : ($wantsPasswordChange ? 'Profile and password updated' : 'Profile updated');
    portal_audit_log($conn, 'PROFILE_UPDATED', $profileDetail, $finalId, $finalId, auth_user_role());

    $_SESSION['company_id'] = $finalId;
    if ($wantsPasswordChange) {
        $_SESSION['must_change_password'] = 0;
    }

    $success = $wantsPasswordChange
        ? 'Profile and password updated successfully.'
        : 'Profile updated successfully.';

    $form['role_name'] = $existing['role_name'];

    return ['success' => $success, 'error' => null, 'form' => $form];
}

/**
 * @param array{company_id: string, first_name: string, last_name: string, email: string, role_name: string} $form
 */
function admin_render_profile_form(array $form, ?string $error, ?string $success): void
{
    global $conn;
    $showProfileNames = ($conn instanceof PDO) && auth_users_has_profile_names($conn);
    $lockUsername = auth_role_is(AUTH_ROLE_SUPERADMIN);
    ?>
    <?php if ($success !== null): ?>
        <div class="alert alert--success" role="status"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> <?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error !== null): ?>
        <div class="alert alert--error" role="alert"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> <?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="form-grid" id="adminProfileForm" autocomplete="off">
        <?= csrf_field() ?>
        <input type="hidden" name="update_profile" value="1">

        <?php if ($showProfileNames): ?>
        <div class="form-field">
            <label for="profile_first_name" class="label-with-icon">
                <i class="fa-solid fa-user" aria-hidden="true"></i> First name
            </label>
            <input type="text" id="profile_first_name" name="first_name" required maxlength="64"
                   value="<?= e($form['first_name']) ?>"
                   placeholder="First name">
        </div>

        <div class="form-field">
            <label for="profile_last_name" class="label-with-icon">
                <i class="fa-solid fa-user" aria-hidden="true"></i> Last name
            </label>
            <input type="text" id="profile_last_name" name="last_name" required maxlength="64"
                   value="<?= e($form['last_name']) ?>"
                   placeholder="Last name">
        </div>
        <?php endif; ?>

        <div class="form-field">
            <label for="profile_company_id" class="label-with-icon">
                <i class="fa-solid fa-id-badge" aria-hidden="true"></i> Username
            </label>
            <input type="text" id="profile_company_id" name="company_id" required
                   pattern="[A-Za-z0-9]{1,20}" maxlength="20"
                   value="<?= e($form['company_id']) ?>"
                   placeholder="Portal username"
                   <?= $lockUsername ? ' readonly title="Your username cannot be changed"' : '' ?>>
            <?php if (!$lockUsername): ?>
            <p class="form-hint">Letters and numbers only, up to 20 characters.</p>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="profile_email" class="label-with-icon">
                <i class="fa-solid fa-envelope" aria-hidden="true"></i> Email
            </label>
            <input type="email" id="profile_email" name="email" required value="<?= e($form['email']) ?>">
        </div>

        <div class="form-field form-field--readonly">
            <span class="label-with-icon">
                <i class="fa-solid fa-user-shield" aria-hidden="true"></i> Role
            </span>
            <p class="profile-readonly-value"><?= e($form['role_name']) ?></p>
        </div>

        <fieldset class="profile-password-fieldset">
            <legend class="profile-password-legend">Change password</legend>
            <p class="form-hint">Leave blank to keep your current password.</p>

            <div class="form-field">
                <label for="profile_current_password" class="label-with-icon">
                    <i class="fa-solid fa-lock" aria-hidden="true"></i> Current password
                </label>
                <input type="password" id="profile_current_password" name="current_password"
                       autocomplete="current-password">
            </div>

            <div class="form-field">
                <label for="profile_new_password" class="label-with-icon">
                    <i class="fa-solid fa-key" aria-hidden="true"></i> New password
                </label>
                <input type="password" id="profile_new_password" name="new_password"
                       autocomplete="new-password">
            </div>

            <div class="form-field">
                <label for="profile_confirm_password" class="label-with-icon">
                    <i class="fa-solid fa-key" aria-hidden="true"></i> Confirm new password
                </label>
                <input type="password" id="profile_confirm_password" name="confirm_password"
                       autocomplete="new-password">
            </div>
        </fieldset>

        <div class="form-actions">
            <button type="submit" class="btn-primary">
                <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                Save changes
            </button>
        </div>
    </form>
    <?php
}

function admin_profile_page_styles(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;
    ?>
        .profile-settings-panel {
            width: 100%;
            max-width: 520px;
            margin-left: auto;
            margin-right: auto;
            box-sizing: border-box;
        }

        .profile-password-fieldset {
            border: 1px solid var(--sa-card-border, var(--app-border));
            border-radius: 10px;
            padding: 14px 16px 4px;
            margin: 4px 0 0;
        }

        .profile-password-legend {
            font-size: 0.9rem;
            font-weight: 600;
            padding: 0 6px;
            color: var(--sa-card-ink, var(--app-ink));
        }

        .profile-readonly-value {
            margin: 6px 0 0;
            font-size: 0.9375rem;
            color: var(--sa-card-ink-muted, var(--app-ink-muted));
        }

        .form-hint {
            margin: 6px 0 0;
            font-size: 0.8125rem;
            color: var(--sa-card-ink-soft, var(--app-ink-soft));
        }
    <?php
}
