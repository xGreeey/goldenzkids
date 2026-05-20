<?php
declare(strict_types=1);

/** Guard module root (guard/). */
const GUARD_ROOT = __DIR__ . '/..';

const GUARD_ASSETS = GUARD_ROOT . '/assets';
const GUARD_CSS = GUARD_ASSETS . '/css';
const GUARD_JS = GUARD_ASSETS . '/js';
const GUARD_PHP = GUARD_ROOT . '/php';

/**
 * All guard CSS (Trivium theme + guard module). Call inside <style>.
 */
function guard_styles(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;
    theme_styles();
    $file = GUARD_CSS . '/guard.css';
    if (is_file($file)) {
        readfile($file);
    }
    echo mobile_base_css();
}

/** Guard module JS + theme toggle. */
function guard_scripts(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;
    $file = GUARD_JS . '/guard.js';
    if (is_file($file)) {
        echo '<script src="' . e(guard_asset_url('js/guard.js')) . '" defer></script>' . "\n";
    }
    theme_toggle_script();
}

function guard_asset_url(string $path): string
{
    return guard_url('assets/' . ltrim($path, '/'));
}

/** Relative URL within guard/ (e.g. portal.php). */
function guard_url(string $path = 'portal.php'): string
{
    return app_url('guard/' . ltrim($path, '/'));
}

function guard_head(string $title, string $bodyClass = 'guard-portal'): void
{
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?= mobile_meta_tags() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(app_agency_name()) ?> | <?= e($title) ?></title>
    <script src="https://kit.fontawesome.com/3142eebea3.js" crossorigin="anonymous"></script>
    <?= app_fonts_link() ?>
    <style>
<?php guard_styles(); ?>
    </style>
</head>
<body class="<?= e($bodyClass) ?>" data-uploads-url="<?= e(UPLOADS_URL) ?>">
    <?php
}

function guard_footer(bool $withScripts = true, bool $globalAlerts = true): void
{
    guard_layout_footer();
    if ($withScripts) {
        guard_scripts();
    }
    if ($globalAlerts) {
        require_once APP_ROOT . '/includes/global-alerts.php';
    }
    echo '</body></html>';
}

function guard_layout_marquee(?string $text = null): void
{
    $line = $text ?? 'ALL SYSTEMS SECURE // NO CRITICAL ALERTS';
    ?>
    <div class="top-bar">
        <div class="marquee-content" style="text-transform: uppercase; font-weight: bold; color: var(--accent-gold);">
            <?= e($line) ?> <?= e($line) ?>
        </div>
    </div>
    <?php
}

function guard_layout_header_nav(): void
{
    ?>
    <header>
        <div class="nav-left">
            <img src="<?= e(app_logo_url()) ?>" alt="<?= e(app_agency_name()) ?>" class="logo-img">
            <div class="agency-name"><?= e(app_agency_name_upper()) ?></div>
        </div>
        <nav class="nav-links">
            <a href="<?= e(guard_url('corner.php')) ?>" class="nav-link"<?= ui_tooltip("Guard's corner — resources and updates") ?>>GUARD'S CORNER</a>
            <a href="<?= e(guard_url('inbox.php')) ?>" class="nav-link"<?= ui_tooltip('View memos and report status') ?>>GUARD'S INBOX</a>
            <?= theme_toggle_markup(['mode' => 'light-class', 'title' => 'Toggle light or dark appearance']) ?>
            <form method="POST" action="<?= e(app_url('auth/logout-guard.php')) ?>" class="guard-logout-form">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-portal"<?= ui_tooltip('Sign out of guard portal') ?>>LOGOUT</button>
            </form>
        </nav>
    </header>
    <?php
}

function guard_layout_header_back(): void
{
    ?>
    <header>
        <div class="logo-area">
            <img src="<?= e(app_logo_url()) ?>" alt="<?= e(app_agency_name()) ?>" class="logo-img">
            <div class="agency-name"><?= e(app_agency_name_upper()) ?></div>
        </div>
        <nav class="guard-header-nav">
            <?= theme_toggle_markup(['mode' => 'light-class']) ?>
            <form method="POST" action="<?= e(app_url('auth/logout-guard.php')) ?>" class="guard-logout-form">
                <?= csrf_field() ?>
                <button type="submit" class="btn-back"<?= ui_tooltip('Sign out of guard portal') ?>>LOGOUT</button>
            </form>
            <a href="<?= e(guard_url('portal.php')) ?>" class="btn-back"<?= ui_tooltip('Return to main guard portal') ?>>
                RETURN TO PORTAL <span>(BALIK SA PORTAL)</span>
            </a>
        </nav>
    </header>
    <?php
}

function guard_layout_footer(): void
{
    ?>
    <footer class="footer-bottom">
        <div class="footer-branding">
            <img src="<?= e(app_logo_url()) ?>" alt="<?= e(app_agency_name()) ?>" class="footer-logo">
            <div class="footer-tagline">CENTRAL COMMAND HEADQUARTERS</div>
            <h2 class="footer-contact">Intramuros, Manila, PH | +63 2 8000 0000</h2>
            <p class="guard-footer-agency"><?= e(app_agency_name_upper()) ?></p>
        </div>
    </footer>
    <?php
}
