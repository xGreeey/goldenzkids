<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once APP_ROOT . '/includes/internal_messaging.php';
require_once APP_ROOT . '/includes/group_messaging.php';
require_once APP_ROOT . '/includes/messaging_action.php';

auth_require_permission('guard.inbox.view');

messaging_action_handle($conn, (string) ($_SESSION['company_id'] ?? ''), auth_user_role());
