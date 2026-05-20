<?php
declare(strict_types=1);

/**
 * Shared auth portal layout (sign-in, forgot password, verification).
 * Use with body class auth-shell auth-sign-in and theme_styles().
 */

function auth_page_head(string $pageTitle, string $metaDescription = '', string $headExtraCss = ''): void
{
    $agency = app_agency_name();
    $metaDescription = $metaDescription !== ''
        ? $metaDescription
        : 'Secure access to the ' . $agency . ' employee portal.';
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?= mobile_meta_tags() ?>
    <meta name="description" content="<?= e($metaDescription) ?>">
    <title><?= e($pageTitle) ?> | <?= e($agency) ?></title>
    <script src="https://kit.fontawesome.com/3142eebea3.js" crossorigin="anonymous"></script>
    <?= app_fonts_link() ?>
    <style>
<?php theme_styles(); ?>
<?= $headExtraCss ?>
<?= mobile_base_css() ?>
    </style>
</head>
    <?php
}

function auth_body_start(): void
{
    echo '<body class="auth-shell auth-sign-in">' . "\n";
}

function auth_main_open(): void
{
    echo '<main class="main-content">' . "\n";
}

function auth_module_open(): void
{
    ?>
    <div class="login-module">
        <div class="login-logo-above">
            <img
                src="<?= e(app_logo_url()) ?>"
                alt="<?= e(app_agency_name()) ?>"
                class="login-logo-mark"
                width="72"
                height="72"
                decoding="async"
            >
            <p class="login-logo-caption"><?= e(app_agency_name()) ?></p>
        </div>
        <div class="login-card">
            <div class="login-card-toolbar">
                <?= theme_toggle_markup(['mode' => 'dark-class', 'title' => 'Toggle light or dark appearance']) ?>
            </div>
    <?php
}

function auth_card_back_link(string $href, string $label): void
{
    ?>
            <p class="auth-card-back">
                <a href="<?= e($href) ?>" class="btn-back"<?= ui_tooltip($label) ?>>
                    <span class="btn-back__icon" aria-hidden="true">
                        <svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12.78 15.22 7.56 10l5.22-5.22 1.06 1.06L9.68 10l4.16 4.16-1.06 1.06Z"/>
                        </svg>
                    </span>
                    <span class="btn-back__label"><?= e($label) ?></span>
                </a>
            </p>
    <?php
}

function auth_card_intro(string $title, string $subtitle): void
{
    ?>
            <div class="login-card-intro">
                <h1 class="login-title"><?= e($title) ?></h1>
                <p class="login-subtitle"><?= e($subtitle) ?></p>
            </div>
    <?php
}

function auth_alert_error(string $message): void
{
    ?>
            <div class="alert-error" role="alert" style="display: flex;">
                <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                <span><?= e($message) ?></span>
            </div>
    <?php
}

function auth_alert_success(string $message): void
{
    ?>
            <div class="alert-success" role="status">
                <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                <span><?= e($message) ?></span>
            </div>
    <?php
}

function auth_card_support_footer(string $title, string $text): void
{
    ?>
            <footer class="login-card-support">
                <p class="login-support-title"><?= e($title) ?></p>
                <p class="login-support-text"><?= e($text) ?></p>
            </footer>
    <?php
}

function auth_module_close(): void
{
    ?>
        </div>
    </div>
    <?php
}

function auth_main_close(): void
{
    echo "</main>\n";
}

function auth_page_end(): void
{
    theme_toggle_script();
    echo "</body>\n</html>\n";
}
