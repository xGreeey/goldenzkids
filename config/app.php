<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session.php';
require_once APP_ROOT . '/includes/security.php';
require_once APP_ROOT . '/includes/app_notify.php';
require_once APP_ROOT . '/includes/db.php';
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/includes/theme.php';
require_once APP_ROOT . '/includes/admin_shell.php';

auth_enforce_area_access();

$authPublicEntryPages = ['index.php', 'forgot-access-code.php', 'enter-otp.php', 'reset-password.php'];
$authCurrentScript = basename((string) ($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? ''));
if (in_array($authCurrentScript, $authPublicEntryPages, true)) {
    auth_redirect_if_authenticated();
}

send_security_headers();
