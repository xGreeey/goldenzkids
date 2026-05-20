<?php
declare(strict_types=1);

/**
 * Shared create/edit account form and save handler for superadmin.
 */

/**
 * Generate a temporary alphanumeric password.
 */
function superadmin_generate_temporary_password(int $length = 12): string
{
    $length = max(8, min(64, $length));
    $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    $lower = 'abcdefghijkmnopqrstuvwxyz';
    $digits = '23456789';
    $symbols = '!@#$%^&*()-_=+?';
    $all = $upper . $lower . $digits . $symbols;

    $password = $upper[random_int(0, strlen($upper) - 1)]
        . $lower[random_int(0, strlen($lower) - 1)]
        . $digits[random_int(0, strlen($digits) - 1)]
        . $symbols[random_int(0, strlen($symbols) - 1)];

    for ($i = 4; $i < $length; $i++) {
        $password .= $all[random_int(0, strlen($all) - 1)];
    }

    return str_shuffle($password);
}

/**
 * Send account temporary password to the user's email.
 */
function superadmin_send_temporary_password_email(string $recipientEmail, string $username, string $temporaryPassword): bool
{
    $smtpUser = trim((string) ($_ENV['EMAIL'] ?? getenv('EMAIL') ?? ''));
    $smtpPass = trim((string) ($_ENV['APP_PASSWORD'] ?? getenv('APP_PASSWORD') ?? ''));
    if ($smtpUser === '' || $smtpPass === '') {
        error_log('Temporary password email failed: EMAIL/APP_PASSWORD missing');
        return false;
    }

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUser;
        $mail->Password = $smtpPass;
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom($smtpUser, app_agency_name());
        $mail->addAddress($recipientEmail);
        $mail->isHTML(true);
        $mail->Subject = 'Your Temporary Portal Password';

        $safeUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        $safeTempPassword = htmlspecialchars($temporaryPassword, ENT_QUOTES, 'UTF-8');
        $mail->Body = '<p>Hello,</p>'
            . '<p>Your account has been created.</p>'
            . '<p><strong>Username:</strong> ' . $safeUsername . '</p>'
            . '<p><strong>Temporary password:</strong> <span style="font-size:16px; letter-spacing:1px;">'
            . $safeTempPassword
            . '</span></p>'
            . '<p>Please sign in and change your password immediately.</p>';
        $mail->AltBody = "Your account has been created.\n"
            . "Username: {$username}\n"
            . "Temporary password: {$temporaryPassword}\n"
            . "Please sign in and change your password immediately.";

        return $mail->send();
    } catch (\PHPMailer\PHPMailer\Exception $exception) {
        error_log('Temporary password email failed: ' . $exception->getMessage());
        return false;
    }
}

/** @return array{company_id: string, email: string, role: string, is_active: string, password: string} */
function superadmin_default_form(string $companyId = ''): array
{
    return [
        'company_id' => $companyId,
        'email' => '',
        'role' => (string) AUTH_ROLE_HEADGUARD,
        'is_active' => '1',
        'password' => '',
    ];
}

/**
 * Process create or edit account POST.
 *
 * @return array{
 *   success: ?string,
 *   error: ?string,
 *   form: array,
 *   is_edit: bool,
 *   edit_id: string,
 *   account_trail: array
 * }
 */
function superadmin_handle_account_post(mysqli $conn, bool $isEdit, string $editId): array
{
    $roleCol = auth_users_role_column($conn);
    $form = superadmin_default_form($isEdit ? $editId : '');
    $error = null;
    $success = null;
    $accountTrail = [];
    $beforeState = null;
    $editingSelf = $isEdit && $editId === (string) ($_SESSION['company_id'] ?? '');

    if ($isEdit) {
        $existing = db_query(
            $conn,
            "SELECT Company_ID, Email, {$roleCol} AS role, is_active, password_hash FROM users WHERE Company_ID = ? LIMIT 1",
            's',
            [$editId]
        );
        if (!$existing || $existing->num_rows === 0) {
            return [
                'success' => null,
                'error' => 'Account not found.',
                'form' => $form,
                'is_edit' => false,
                'edit_id' => '',
                'account_trail' => [],
                'editing_self' => false,
            ];
        }
        $row = $existing->fetch_assoc();
        $beforeState = [
            'email' => (string) ($row['Email'] ?? ''),
            'role' => auth_normalize_role($row['role'] ?? AUTH_ROLE_HEADGUARD),
            'is_active' => (int) ($row['is_active'] ?? 1),
        ];
        $accountTrail = superadmin_account_audit_trail($conn, $editId);
    }

    $form['company_id'] = trim((string) ($_POST['company_id'] ?? $form['company_id']));
    $form['email'] = trim((string) ($_POST['email'] ?? ''));
    $form['role'] = (string) ($_POST['role'] ?? '0');
    $form['is_active'] = isset($_POST['is_active']) ? '1' : '0';
    $form['password'] = trim((string) ($_POST['password'] ?? ''));

    $role = auth_role_from_input($form['role']);
    if ($role === null) {
        $role = auth_normalize_role((int) $form['role']);
    }

    if (!auth_username_valid($form['company_id'])) {
        $error = 'Username must be alphanumeric and up to 20 characters.';
    } elseif ($form['email'] === '' || !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'A valid email address is required.';
    } elseif ($isEdit && $form['password'] !== '' && !auth_password_policy_valid($form['password'])) {
        $error = 'Password must be 8-64 chars with uppercase, lowercase, number, and symbol.';
    } elseif (
        $isEdit
        && $form['password'] !== ''
        && auth_password_matches_existing_hash($form['password'], trim((string) ($row['password_hash'] ?? '')))
    ) {
        $error = 'New password cannot be the same as the current password.';
    } else {
        $exists = db_query($conn, 'SELECT Company_ID FROM users WHERE Company_ID = ? LIMIT 1', 's', [$form['company_id']]);
        $alreadyExists = $exists && $exists->num_rows > 0;

        if (!$isEdit && $alreadyExists) {
            $error = 'This username is already registered.';
        } elseif ($isEdit && $form['company_id'] !== $editId) {
            $error = 'Username cannot be changed when editing.';
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

            $ok = false;
            if ($error === null && $isEdit && $form['password'] !== '') {
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
            } elseif ($error === null && !$alreadyExists) {
                $temporaryPassword = superadmin_generate_temporary_password();
                $hash = auth_hash_password($temporaryPassword);

                $conn->begin_transaction();
                $ok = db_execute(
                    $conn,
                    "INSERT INTO users (Company_ID, Email, password_hash, {$roleCol}, is_active, password_changed_at)
                     VALUES (?, ?, ?, ?, ?, NULL)",
                    'sssii',
                    [$targetId, $form['email'], $hash, $role, $active]
                );

                if (!empty($ok) && superadmin_send_temporary_password_email($form['email'], $targetId, $temporaryPassword)) {
                    db_execute(
                        $conn,
                        'UPDATE users SET password_changed_at = NULL WHERE Company_ID = ?',
                        's',
                        [$targetId]
                    );
                    $conn->commit();
                } else {
                    $conn->rollback();
                    $ok = false;
                    $error = 'Could not send temporary password email. Account was not created.';
                }
            } elseif ($error === null && $alreadyExists) {
                $ok = db_execute(
                    $conn,
                    "UPDATE users SET Email = ?, {$roleCol} = ?, is_active = ? WHERE Company_ID = ?",
                    'siis',
                    [$form['email'], $role, $active, $targetId]
                );
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
                    : "Created account {$targetId} ({$roleName}) and sent temporary password to email.";
                if (!$isEdit) {
                    $form = superadmin_default_form();
                }
            } elseif ($error === null) {
                $error = 'Could not save account. ' . $conn->error;
            }
        }
    }

    return [
        'success' => $success,
        'error' => $error,
        'form' => $form,
        'is_edit' => $isEdit,
        'edit_id' => $editId,
        'account_trail' => $accountTrail,
        'editing_self' => $editingSelf,
    ];
}

/**
 * @param array{company_id: string, email: string, role: string, is_active: string, password: string} $form
 */
function superadmin_render_create_account_form(
    array $form,
    ?string $error,
    string $formAction = 'users.php',
    string $idPrefix = 'create'
): void {
    $pid = static fn (string $field): string => $idPrefix . '_' . $field;
    ?>
    <?php if ($error !== null): ?>
        <div class="alert alert--error" role="alert"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> <?= e($error) ?></div>
    <?php endif; ?>
    <form method="POST" class="form-grid" action="<?= e($formAction) ?>" autocomplete="off" id="<?= e($idPrefix) ?>AccountForm">
        <?= csrf_field() ?>
        <input type="hidden" name="create_account" value="1">

        <div class="form-field">
            <label for="<?= e($pid('company_id')) ?>" class="label-with-icon"><i class="fa-solid fa-id-badge" aria-hidden="true"></i> Username</label>
            <input type="text" id="<?= e($pid('company_id')) ?>" name="company_id" required
                   pattern="[A-Za-z0-9]{1,20}"
                   maxlength="20"
                   value="<?= e($form['company_id']) ?>"
                   placeholder="Username">
        </div>

        <div class="form-field">
            <label for="<?= e($pid('email')) ?>" class="label-with-icon"><i class="fa-solid fa-envelope" aria-hidden="true"></i> Email</label>
            <input type="email" id="<?= e($pid('email')) ?>" name="email" required value="<?= e($form['email']) ?>">
        </div>

        <div class="form-field">
            <label for="<?= e($pid('role')) ?>" class="label-with-icon"><i class="fa-solid fa-user-shield" aria-hidden="true"></i> Role</label>
            <select id="<?= e($pid('role')) ?>" name="role" required>
                <option value="0"<?= $form['role'] === '0' ? ' selected' : '' ?>>Head guard</option>
                <option value="1"<?= $form['role'] === '1' ? ' selected' : '' ?>>Administrator</option>
                <option value="2"<?= $form['role'] === '2' ? ' selected' : '' ?>>Super administrator</option>
            </select>
        </div>

        <div class="form-field form-field--checkbox">
            <label class="checkbox-row" for="<?= e($pid('is_active')) ?>">
                <span class="checkbox-row__label">
                    <i class="fa-solid fa-toggle-on" aria-hidden="true"></i>
                    Account is active
                </span>
                <input type="checkbox" id="<?= e($pid('is_active')) ?>" name="is_active" value="1"<?= $form['is_active'] === '1' ? ' checked' : '' ?>>
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">
                <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                Create account
            </button>
        </div>
    </form>
    <?php
}

function superadmin_create_account_modal(array $form, ?string $error, bool $open = false): void
{
    ?>
    <div class="app-modal<?= $open ? ' is-open' : '' ?>" id="createAccountModal" role="presentation"<?= $open ? ' data-open-on-load="1"' : '' ?><?= $open ? '' : ' hidden' ?> aria-hidden="<?= $open ? 'false' : 'true' ?>">
        <div class="app-modal__backdrop" data-modal-close tabindex="-1"></div>
        <div class="app-modal__panel" role="dialog" aria-modal="true" aria-labelledby="createAccountModalHeading">
            <button type="button" class="app-modal__close app-modal__close--floating" data-modal-close aria-label="Close">
                <span class="app-modal__close-glyph" aria-hidden="true">×</span>
            </button>
            <h2 class="panel-title app-modal__heading" id="createAccountModalHeading">Account Creation</h2>
            <?php superadmin_render_create_account_form($form, $error); ?>
        </div>
    </div>
    <?php
}

function superadmin_modal_styles(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;
    ?>
        .app-modal {
            position: fixed;
            inset: 0;
            z-index: 2000;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: max(16px, env(safe-area-inset-top, 0px)) max(16px, env(safe-area-inset-right, 0px))
                max(16px, env(safe-area-inset-bottom, 0px)) max(16px, env(safe-area-inset-left, 0px));
            pointer-events: none;
        }

        /* When hoisted to document.body (superadminInitCreateAccountModal), stay above shell + avoid .app-main overflow clipping */
        body > #createAccountModal {
            z-index: 2100;
        }

        .app-modal[hidden] {
            display: none;
        }

        .app-modal.is-open {
            pointer-events: auto;
        }

        .app-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            -webkit-backdrop-filter: blur(6px) saturate(1.02);
            backdrop-filter: blur(6px) saturate(1.02);
            opacity: 0;
            transition: opacity 0.24s ease;
        }

        body:not(.light-mode) .app-modal__backdrop {
            background: rgba(0, 0, 0, 0.55);
        }

        @supports not ((-webkit-backdrop-filter: blur(1px)) or (backdrop-filter: blur(1px))) {
            .app-modal__backdrop {
                background: rgba(15, 23, 42, 0.62);
            }

            body:not(.light-mode) .app-modal__backdrop {
                background: rgba(0, 0, 0, 0.68);
            }
        }

        .app-modal.is-open .app-modal__backdrop {
            opacity: 1;
        }

        #createAccountModal .app-modal__panel {
            /* Re-declare superadmin tokens because modal is hoisted to <body>, outside .app-main */
            --sa-card-bg: var(--app-card-bg);
            --sa-card-border: var(--app-border);
            --sa-card-ink: var(--app-ink);
            --sa-card-ink-muted: var(--app-ink-muted);
            --sa-card-ink-soft: var(--app-ink-soft);
            --sa-input-bg: var(--app-card-bg);
            --sa-input-border: var(--app-border);
            --sa-control-h: 34px;
            position: relative;
            z-index: 1;
            align-self: center;
            box-sizing: border-box;
            width: 100%;
            max-width: 460px;
            flex: 0 1 auto;
            min-width: 0;
            max-height: min(88vh, 640px);
            display: flex;
            flex-direction: column;
            padding: 12px 16px 16px;
            overflow-y: auto;
            overscroll-behavior: contain;
            background: var(--sa-card-bg, var(--app-card-bg));
            border: 1px solid var(--sa-card-border, var(--app-border));
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1), 0 12px 32px rgba(0, 0, 0, 0.08);
            opacity: 0;
            transform: translateY(16px) scale(0.98);
            transition:
                opacity 0.26s cubic-bezier(0.4, 0, 0.2, 1),
                transform 0.26s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body:not(.light-mode) #createAccountModal .app-modal__panel {
            --sa-card-border: var(--app-border-on-dark);
            --sa-card-ink: var(--app-ink-on-dark);
            --sa-card-ink-muted: var(--app-ink-muted-on-dark);
            --sa-card-ink-soft: var(--app-ink-soft-on-dark);
            --sa-input-bg: rgba(255, 255, 255, 0.06);
            --sa-input-border: var(--app-border-on-dark);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.35), 0 24px 56px rgba(0, 0, 0, 0.28);
        }

        #createAccountModal.app-modal.is-open .app-modal__panel {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .app-modal__close--floating {
            position: absolute;
            top: 8px;
            right: 8px;
            z-index: 10;
        }

        /* High-contrast chip: --sa-* may match dialog surface and hide the icon */
        #createAccountModal .app-modal__panel > .app-modal__close {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            min-width: 30px;
            min-height: 30px;
            max-width: 30px;
            max-height: 30px;
            padding: 0;
            line-height: 1;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0;
            -webkit-font-smoothing: antialiased;
            box-sizing: border-box;
            border: 1px solid rgba(15, 39, 68, 0.22);
            background: rgba(255, 255, 255, 0.92);
            color: #0f2744;
            box-shadow:
                0 0 0 1px rgba(255, 255, 255, 0.9) inset,
                0 1px 3px rgba(15, 39, 68, 0.12);
            transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
        }

        #createAccountModal .app-modal__panel > .app-modal__close .app-modal__close-glyph {
            display: block;
            font-size: 1.125rem;
            font-weight: 600;
            line-height: 1;
            color: inherit;
            margin-top: -1px;
        }

        body:not(.light-mode) #createAccountModal .app-modal__panel > .app-modal__close {
            border: 1px solid rgba(255, 255, 255, 0.42);
            background: rgba(0, 20, 35, 0.55);
            color: #ffffff;
            box-shadow:
                0 0 0 1px rgba(255, 255, 255, 0.12) inset,
                0 2px 8px rgba(0, 0, 0, 0.35);
        }

        #createAccountModal .app-modal__panel > .app-modal__close:hover {
            background: #ffffff;
            color: #001e30;
            border-color: rgba(15, 39, 68, 0.35);
            box-shadow: 0 2px 8px rgba(15, 39, 68, 0.15);
        }

        body:not(.light-mode) #createAccountModal .app-modal__panel > .app-modal__close:hover {
            background: rgba(255, 255, 255, 0.18);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.55);
        }

        #createAccountModal .app-modal__panel > .app-modal__close:focus-visible {
            outline: 2px solid var(--app-accent, #c4a35a);
            outline-offset: 2px;
        }

        #createAccountModal .app-modal__panel .app-modal__heading.panel-title {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            margin-top: 4px;
            margin-left: 0;
            margin-right: 0;
            padding: 0 40px;
            box-sizing: border-box;
            text-align: center;
        }

        /* Modal-specific compact checkbox sizing to match label text line-height */
        #createAccountModal .app-modal__panel .form-field--checkbox .checkbox-row {
            gap: 5px;
            align-items: center;
        }

        #createAccountModal .app-modal__panel .form-field--checkbox .checkbox-row input[type="checkbox"] {
            width: 15px;
            height: 15px;
            min-height: 15px;
            border-radius: 3px;
        }

        #createAccountModal .app-modal__panel .form-grid {
            max-width: none;
            gap: 12px;
        }

        #createAccountModal .app-modal__panel .alert {
            margin-bottom: 10px;
            padding: 8px 10px;
            font-size: 0.75rem;
        }

        #createAccountModal .app-modal__panel .form-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            margin-top: 2px;
        }

        #createAccountModal .app-modal__panel .form-actions {
            justify-content: flex-end;
        }

        #createAccountModal .app-modal__panel .btn-primary,
        #createAccountModal .app-modal__panel button.btn-primary {
            min-height: 34px;
            border-radius: 8px;
            border: 1px solid transparent;
            padding: 7px 12px;
            font-size: 0.8125rem;
            font-weight: 700;
            line-height: 1.2;
            text-decoration: none;
            cursor: pointer;
            color: #fff;
            background: #5c6b7d;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06);
            transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
        }

        body:not(.light-mode) #createAccountModal .app-modal__panel .btn-primary,
        body:not(.light-mode) #createAccountModal .app-modal__panel button.btn-primary {
            background: rgba(255, 255, 255, 0.12);
            border-color: var(--app-border-on-dark);
            color: var(--app-ink-on-dark);
        }

        #createAccountModal .app-modal__panel .btn-primary:hover,
        #createAccountModal .app-modal__panel button.btn-primary:hover {
            color: #fff;
            background: #4a5868;
            border-color: transparent;
            box-shadow: 0 2px 8px rgba(15, 39, 68, 0.16);
        }

        body:not(.light-mode) #createAccountModal .app-modal__panel .btn-primary:hover,
        body:not(.light-mode) #createAccountModal .app-modal__panel button.btn-primary:hover {
            background: rgba(255, 255, 255, 0.18);
            border-color: var(--app-border-on-dark);
            color: var(--app-ink-on-dark);
        }

        #createAccountModal .app-modal__panel .btn-primary:focus-visible,
        #createAccountModal .app-modal__panel button.btn-primary:focus-visible {
            outline: 2px solid #5c6b7d;
            outline-offset: 2px;
        }

        body.app-modal-open {
            overflow: hidden;
        }

        @media (prefers-reduced-motion: reduce) {
            .app-modal__backdrop,
            #createAccountModal .app-modal__panel {
                transition: none;
            }

            #createAccountModal .app-modal__panel {
                opacity: 1;
                transform: none;
            }
        }
    <?php
}

function superadmin_modal_script(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;
    ?>
<script>
(function () {
    if (window.__saCreateAccountModalEscapeInstalled) {
        return;
    }
    window.__saCreateAccountModalEscapeInstalled = true;
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') {
            return;
        }
        if (typeof window.__saCreateAccountModalClose === 'function') {
            window.__saCreateAccountModalClose();
        }
    });
})();

(function () {
    if (window.__saCreateAccountModalTriggerInstalled) {
        return;
    }
    window.__saCreateAccountModalTriggerInstalled = true;
    document.addEventListener('click', function (e) {
        var btn = e.target && e.target.closest ? e.target.closest('#openCreateAccountModal') : null;
        if (!btn) {
            return;
        }
        if (typeof window.__saCreateAccountModalOpen !== 'function'
            && typeof window.superadminInitCreateAccountModal === 'function') {
            window.superadminInitCreateAccountModal();
        }
        if (typeof window.__saCreateAccountModalOpen === 'function') {
            e.preventDefault();
            window.__saCreateAccountModalOpen(btn);
        }
    });
})();

window.superadminInitCreateAccountModal = function () {
    var modal = document.getElementById('createAccountModal');
    if (!modal || modal.getAttribute('data-sa-modal-bound') === '1') {
        return;
    }
    modal.setAttribute('data-sa-modal-bound', '1');

    /* Fixed overlays must not live under .app-main (overflow-x: clip clips descendants). */
    if (modal.parentNode !== document.body) {
        document.body.appendChild(modal);
    }

    var lastFocus = null;

    function openModal(triggerEl) {
        lastFocus = triggerEl || document.activeElement;
        modal.removeAttribute('hidden');
        modal.setAttribute('aria-hidden', 'false');
        modal.classList.add('is-open');
        document.body.classList.add('app-modal-open');
        requestAnimationFrame(function () {
            var first = modal.querySelector('input:not([type="hidden"]):not([type="checkbox"]), select');
            if (first) {
                first.focus();
            } else {
                var cb = modal.querySelector('input[type="checkbox"]');
                if (cb) {
                    cb.focus();
                }
            }
        });
    }

    function closeModal() {
        if (!modal.classList.contains('is-open')) {
            return;
        }
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('app-modal-open');
        window.setTimeout(function () {
            if (!modal.isConnected) {
                return;
            }
            modal.setAttribute('hidden', '');
            if (lastFocus && typeof lastFocus.focus === 'function') {
                lastFocus.focus();
            }
        }, 260);
    }

    modal.querySelectorAll('[data-modal-close]').forEach(function (el) {
        el.addEventListener('click', closeModal);
    });

    modal.addEventListener('click', function (e) {
        if (e.target && e.target.classList.contains('app-modal__backdrop')) {
            closeModal();
        }
    });

    var openFromServer = modal.dataset.openOnLoad === '1';
    var openFromQuery = window.location.search.indexOf('create=1') !== -1;
    if (openFromServer || openFromQuery) {
        openModal();
        if (openFromQuery) {
            var url = new URL(window.location.href);
            url.searchParams.delete('create');
            history.replaceState(history.state, '', url.pathname + url.search + url.hash);
        }
    }

    window.__saCreateAccountModalOpen = openModal;
    window.__saCreateAccountModalClose = closeModal;
};

document.addEventListener('DOMContentLoaded', window.superadminInitCreateAccountModal);
</script>
    <?php
}
