<?php

declare(strict_types=1);



require_once __DIR__ . '/superadmin_page.css.php';

require_once __DIR__ . '/guard_page.css.php';

require_once __DIR__ . '/guard_ui.css.php';

require_once __DIR__ . '/guard_ui_icons.php';

require_once __DIR__ . '/guard_ui_shell.php';

require_once __DIR__ . '/guard_ui.js.php';

require_once __DIR__ . '/guard_hub.css.php';

require_once __DIR__ . '/guard_hub.js.php';



/**

 * Guard portal — full-viewport mobile shell (topbar + scroll, no extra wrapper).

 */

function guard_layout_head(string $documentTitle, ?string $navActive = null, bool $profileSettingsPage = false): void

{

    global $guardNavActive;

    $navActive = $navActive ?? ($guardNavActive ?? 'dashboard');
    $guardNavActive = $navActive;

    ?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <?= mobile_meta_tags() ?>

    <title><?= e(app_agency_name()) ?> | <?= e($documentTitle) ?></title>
    <meta name="guard-page-title" content="<?= e($documentTitle) ?>">

    <script src="https://kit.fontawesome.com/3142eebea3.js" crossorigin="anonymous"></script>

    <?= app_fonts_link() ?>

    <style>

<?= mobile_base_css() ?>

<?php admin_shell_styles(); ?>

<?php superadmin_page_styles(); ?>

<?php guard_page_styles(); ?>

<?php guard_ui_styles(); ?>

<?php guard_hub_styles(); ?>
<?php if ($navActive === 'inbox') {
    require_once __DIR__ . '/guard_inbox.css.php';
    guard_inbox_styles();
} ?>
<?php if ($profileSettingsPage) {
    require_once __DIR__ . '/admin_profile.php';
    admin_profile_page_styles();
} ?>

    </style>

</head>

<body class="light-mode superadmin-portal guard-portal<?= $navActive === 'inbox' ? ' guard-page-inbox' : '' ?>">

<?php

    require __DIR__ . '/guard_sidebar.php';

    guard_ui_topbar_markup($documentTitle);

    echo '<main class="app-main guard-app__main" id="main-content">';

    echo '<div class="guard-app__scroll" data-guard-panel-root>';

}



function guard_layout_end(): void

{
    global $guardNavActive;

    $navActive = $guardNavActive ?? 'dashboard';

    echo '</div></main>';

    echo '</div>';

    require __DIR__ . '/guard_policy_modal.php';

    guard_ui_drawer_markup($navActive);

    admin_shell_scripts();

    guard_hub_scripts();

    guard_ui_scripts();

    require dirname(__DIR__) . '/includes/global-alerts.php';

    echo '</body></html>';

}


