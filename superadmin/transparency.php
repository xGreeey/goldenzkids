<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

header('Location: ' . app_url('superadmin/audit-log.php'), true, 302);
exit();
