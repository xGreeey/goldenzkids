<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/account_settings_layout.php';

auth_require_permission('superadmin.dashboard.view');

account_settings_run_page(
    __DIR__ . '/../includes/superadmin_sidebar.php',
    'superadminNavActive',
    'settings',
    'light-mode superadmin-portal'
);
