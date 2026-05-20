<?php
declare(strict_types=1);

/**
 * Superadmin layout — same shell as admin (sidebar, theme, fonts).
 * Call superadmin_layout_head() then page content, then superadmin_layout_end().
 */
function superadmin_layout_head(string $documentTitle): void
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
<body class="light-mode superadmin-portal">
<?php
    require __DIR__ . '/superadmin_sidebar.php';
    echo '<main class="app-main">';
}

function superadmin_layout_end(): void
{
    echo '</main></div>';
    admin_shell_scripts();
    echo '</body></html>';
}
