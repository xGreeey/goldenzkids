<?php
declare(strict_types=1);

require_once __DIR__ . '/superadmin_page.css.php';

/**
 * Guard portal layout — same shell, sidebar, and theme tokens as admin / superadmin.
 */
function guard_layout_head(string $documentTitle): void
{
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?= mobile_meta_tags() ?>
    <title><?= e(app_agency_name()) ?> | <?= e($documentTitle) ?></title>
    <script src="https://kit.fontawesome.com/3142eebea3.js" crossorigin="anonymous"></script>
    <?= app_fonts_link() ?>
    <style>
<?php admin_shell_styles(); ?>
<?php superadmin_page_styles(); ?>
    </style>
</head>
<body class="light-mode superadmin-portal guard-portal">
<?php
    require __DIR__ . '/guard_sidebar.php';
    echo '<main class="app-main" id="main-content">';
}

function guard_layout_end(): void
{
    echo '</main></div>';
    admin_shell_scripts();
    require dirname(__DIR__) . '/includes/global-alerts.php';
    echo '</body></html>';
}
