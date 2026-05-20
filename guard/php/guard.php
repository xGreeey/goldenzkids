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

/**
 * Canonical body classes for guard app-shell pages.
 *
 * @param 'portal'|'inbox'|'corner' $pageSlug
 */
function guard_shell_body_class(string $pageSlug): string
{
    $slug = in_array($pageSlug, ['portal', 'inbox', 'corner'], true) ? $pageSlug : 'portal';
    $pageClass = match ($slug) {
        'inbox' => 'guard-inbox',
        'corner' => 'guard-corner',
        default => 'guard-portal-home',
    };

    return trim('guard-portal ' . $pageClass . ' guard-portal-home light-mode guard-mobile-shell');
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

/**
 * Render shared mobile AppFrame shell for guard pages.
 *
 * Expected $config keys:
 * - title (string)
 * - activeNav ('portal'|'inbox'|'corner')
 * - headerPrimaryTabActive (bool)
 * - headerSecondaryHref (string)
 * - headerSecondaryLabel (string)
 * - headerSecondaryActive (bool)
 * Optional keys:
 * - bodyClass (string)
 * - locationOpensEstablishmentPicker (bool)
 * - showAvatar (bool)
 * - showGreeting (bool)
 * - showSearch (bool)
 * - showTabs (bool)
 * - searchInputId (?string)
 * - searchPlaceholder (string)
 * - primaryTabLabel (string)
 */
function guard_render_app_page(mysqli $conn, array $config): void
{
    $title = (string) ($config['title'] ?? 'Guard');
    $activeNav = (string) ($config['activeNav'] ?? 'portal');
    $bodyClass = (string) ($config['bodyClass'] ?? guard_shell_body_class($activeNav));
    $meta = guard_portal_greeting_meta($conn);

    guard_head($title, $bodyClass);
    ?>
<div class="guard-app-frame">
    <main class="guard-app-frame__main" id="hds-main">
        <section class="guard-location-selector" aria-label="Location selector">
            <button type="button"
                    class="guard-location-selector__trigger"
                    id="guardLocationSelectorTrigger"
                    aria-expanded="false"
                    aria-controls="guardLocationPickerPanel">
                <span class="guard-location-selector__icon" aria-hidden="true">
                    <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                </span>
                <span class="guard-location-selector__content">
                    <span class="guard-location-selector__eyebrow">Current location</span>
                    <span class="guard-location-selector__text" id="guardLocationChipLabel"><?= e((string) ($meta['location'] ?? 'Select location')) ?></span>
                </span>
                <span class="guard-location-selector__chevron" aria-hidden="true">
                    <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                </span>
            </button>
            <div class="guard-location-picker" id="guardLocationPickerPanel" hidden>
                <p class="guard-location-picker__heading">Set delivery-style location</p>
                <button type="button" class="guard-location-picker__action" id="guardUseCurrentLocationBtn">
                    <i class="fa-solid fa-crosshairs" aria-hidden="true"></i>
                    <span>Use current location</span>
                </button>
                <button type="button" class="guard-location-picker__action" id="guardUseAssignedLocationBtn" data-location="<?= e((string) ($meta['location'] ?? '')) ?>">
                    <i class="fa-solid fa-building-shield" aria-hidden="true"></i>
                    <span>Use assigned post</span>
                </button>
                <div class="guard-location-picker__manual">
                    <label class="visually-hidden" for="guardLocationManualInput">Enter location</label>
                    <input type="text" id="guardLocationManualInput" placeholder="Search area, city, barangay">
                    <button type="button" id="guardApplyLocationBtn">Apply</button>
                </div>
                <div class="guard-location-picker__results" id="guardLocationSearchResults" role="listbox" aria-label="Location suggestions"></div>
            </div>
        </section>
        <section class="guard-dynamic-content" aria-label="Emergency dynamic content container">
            <div class="guard-dynamic-content__canvas" role="presentation" aria-hidden="true"></div>
        </section>
    </main>
    <footer class="guard-app-frame__footer">
        <?php guard_mobile_bottom_nav($activeNav); ?>
    </footer>
    <div class="guard-app-frame__modals" id="guard-app-modals"></div>
</div>
<?php
    guard_footer();
}

/**
 * Resolve guard display name + duty location label for portal greeting (matches guards row).
 *
 * @return array{name:string, location:string}
 */
function guard_portal_greeting_meta(mysqli $conn): array
{
    $company_id = isset($_SESSION['company_id']) ? (string) $_SESSION['company_id'] : '';

    $name = 'Guard';
    $post = '';

    if ($company_id !== '') {
        $row = db_query(
            $conn,
            'SELECT First_Name, Post_Assigned FROM guards WHERE Company_ID = ? LIMIT 1',
            's',
            [$company_id]
        );
        if ($row && ($g = $row->fetch_assoc())) {
            $first = trim((string) ($g['First_Name'] ?? ''));
            if ($first !== '') {
                $name = $first;
            }
            $post = trim((string) ($g['Post_Assigned'] ?? ''));
        }
    }

    $location = $post !== ''
        ? $post
        : 'Sta. Ana, Manila';

    return ['name' => $name, 'location' => $location];
}

/**
 * Emergency-style mobile top chrome: duty row + greeting + search + segmented tabs.
 *
 * @param array{name:string, location:string} $meta from guard_portal_greeting_meta()
 */
function guard_mobile_app_header(
    array $meta,
    bool $primaryTabActive,
    string $secondaryTabHref,
    string $secondaryTabLabel,
    bool $secondaryTabActive,
    bool $locationOpensEstablishmentPicker = false,
    bool $showAvatar = false,
    bool $showGreeting = true,
    bool $showSearch = true,
    bool $showTabs = true,
    ?string $searchInputId = null,
    string $searchPlaceholder = 'Search…',
    string $primaryTabLabel = 'Around Me'
): void {
    $name = trim((string) ($meta['name'] ?? '')) !== ''
        ? (string) $meta['name']
        : 'Guard';
    $location = (string) ($meta['location'] ?? '');
    if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
        $initial = mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8');
    } else {
        $initial = strtoupper(substr($name, 0, 1));
    }
    $searchElId = $searchInputId !== null && $searchInputId !== '' ? $searchInputId : 'guardMobileSearch';
    ?>
    <header class="guard-mobile-app-header" aria-label="Guard app">
        <div class="guard-mobile-app-header__top">
            <div class="guard-mobile-app-header__location">
                <?php if ($locationOpensEstablishmentPicker): ?>
                    <button type="button" class="guard-mobile-location-chip" id="guardLocationChip" aria-controls="Establishment">
                        <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                        <span class="guard-mobile-location-chip__text" id="guardLocationChipLabel"><?= e($location) ?></span>
                        <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                    </button>
                <?php else: ?>
                    <div class="guard-mobile-location-chip guard-mobile-location-chip--static" role="status">
                        <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                        <span class="guard-mobile-location-chip__text" id="guardLocationChipLabel"><?= e($location) ?></span>
                        <i class="fa-solid fa-chevron-down guard-mobile-location-chip__chevron-dim" aria-hidden="true"></i>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($showAvatar): ?>
            <div class="guard-mobile-app-header__avatar" aria-hidden="true"><?= e($initial) ?></div>
            <?php endif; ?>
        </div>
        <?php if ($showGreeting): ?>
        <p class="guard-mobile-app-header__greeting">
            <span class="guard-mobile-app-header__hello">Hello,</span>
            <strong class="guard-mobile-app-header__who"><?= e($name) ?></strong>
        </p>
        <?php endif; ?>
        <?php if ($showSearch): ?>
        <div class="guard-mobile-app-header__search-wrap">
            <label class="visually-hidden" for="<?= e($searchElId) ?>">Search</label>
            <i class="fa-solid fa-magnifying-glass guard-mobile-app-header__search-icon" aria-hidden="true"></i>
            <input type="search" class="guard-mobile-app-header__search" id="<?= e($searchElId) ?>"
                   placeholder="<?= e($searchPlaceholder) ?>" autocomplete="off">
        </div>
        <?php endif; ?>
        <?php if ($showTabs): ?>
        <div class="guard-mobile-app-header__tabs" role="tablist">
            <a href="<?= e(guard_url('portal.php')) ?>"
               class="guard-mobile-seg <?= $primaryTabActive ? 'is-active' : '' ?>"
               role="tab" <?= $primaryTabActive ? 'aria-selected="true"' : 'aria-selected="false"' ?>>
                <?= e($primaryTabLabel) ?>
            </a>
            <a href="<?= e($secondaryTabHref) ?>"
               class="guard-mobile-seg <?= $secondaryTabActive ? 'is-active' : '' ?>"
               role="tab" <?= $secondaryTabActive ? 'aria-selected="true"' : 'aria-selected="false"' ?>>
                <?= e($secondaryTabLabel) ?>
            </a>
        </div>
        <?php endif; ?>
    </header>
    <?php
}

/**
 * Bottom tab bar (labeled Home / Map / FAB / History / Settings).
 *
 * @param 'portal'|'corner'|'inbox' $activeSlug
 */
function guard_mobile_bottom_nav(string $activeSlug = 'portal'): void
{
    ?>
    <nav class="guard-mobile-tabbar guard-mobile-tabbar--simple" aria-label="Primary">
        <div class="guard-mobile-tabbar__rail guard-mobile-tabbar__rail--simple">
            <a href="<?= e(guard_url('portal.php')) ?>"
               class="guard-mobile-tab guard-mobile-tab--simple <?= $activeSlug === 'portal' ? 'is-active' : '' ?>"
               <?= $activeSlug === 'portal' ? 'aria-current="page"' : '' ?>
               <?= ui_tooltip('Home') ?>>
                <span class="guard-mobile-tab__content">
                    <span class="guard-mobile-tab__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                            <path d="M12 3.4 3.8 10v10.6h6.4v-6.7h3.6v6.7h6.4V10L12 3.4Z" fill="currentColor"/>
                        </svg>
                    </span>
                    <span class="guard-mobile-tab__label">Home</span>
                </span>
            </a>
            <a href="<?= e(guard_url('inbox.php')) ?>"
               class="guard-mobile-tab guard-mobile-tab--simple <?= $activeSlug === 'inbox' ? 'is-active' : '' ?>"
               <?= $activeSlug === 'inbox' ? 'aria-current="page"' : '' ?>
               <?= ui_tooltip('Inbox') ?>>
                <span class="guard-mobile-tab__content">
                    <span class="guard-mobile-tab__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                            <path d="M4 6.2h16a1.8 1.8 0 0 1 1.8 1.8v8a1.8 1.8 0 0 1-1.8 1.8H4A1.8 1.8 0 0 1 2.2 16V8A1.8 1.8 0 0 1 4 6.2Zm.4 1.8 7.6 5.1L19.6 8H4.4Z" fill="currentColor"/>
                        </svg>
                    </span>
                    <span class="guard-mobile-tab__label">Inbox</span>
                </span>
            </a>
            <a href="<?= e(guard_url('corner.php')) ?>"
               class="guard-mobile-tab guard-mobile-tab--simple <?= $activeSlug === 'corner' ? 'is-active' : '' ?>"
               <?= $activeSlug === 'corner' ? 'aria-current="page"' : '' ?>
               <?= ui_tooltip('Corner') ?>>
                <span class="guard-mobile-tab__content">
                    <span class="guard-mobile-tab__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                            <path d="m20.7 13.4.1-2.8-2.1-.8a7.2 7.2 0 0 0-.8-1.8l1-2-2-2-2 1a7.2 7.2 0 0 0-1.8-.8l-.8-2.1h-2.8l-.8 2.1a7.2 7.2 0 0 0-1.8.8l-2-1-2 2 1 2a7.2 7.2 0 0 0-.8 1.8l-2.1.8v2.8l2.1.8c.2.6.5 1.2.8 1.8l-1 2 2 2 2-1c.6.3 1.2.6 1.8.8l.8 2.1h2.8l.8-2.1c.6-.2 1.2-.5 1.8-.8l2 1 2-2-1-2c.3-.6.6-1.2.8-1.8l2.1-.8ZM12 15.8A3.8 3.8 0 1 1 12 8a3.8 3.8 0 0 1 0 7.6Z" fill="currentColor"/>
                        </svg>
                    </span>
                    <span class="guard-mobile-tab__label">Corner</span>
                </span>
            </a>
            <form method="POST" action="<?= e(app_url('auth/logout-guard.php')) ?>" class="guard-mobile-tab guard-mobile-tab--simple guard-mobile-tab--logout">
                <?= csrf_field() ?>
                <button type="submit" class="guard-mobile-tab__button"<?= ui_tooltip('Sign out') ?>>
                    <span class="guard-mobile-tab__content">
                        <span class="guard-mobile-tab__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                                <path d="M13.5 4.5h-7A2.5 2.5 0 0 0 4 7v10a2.5 2.5 0 0 0 2.5 2.5h7v-2h-7a.5.5 0 0 1-.5-.5V7c0-.3.2-.5.5-.5h7v-2Zm4.2 3.2-1.4 1.4 1.9 1.9H10v2h8.2l-1.9 1.9 1.4 1.4L22 12l-4.3-4.3Z" fill="currentColor"/>
                            </svg>
                        </span>
                        <span class="guard-mobile-tab__label">Logout</span>
                    </span>
                </button>
            </form>
        </div>
    </nav>
    <?php
}

function guard_layout_header_nav(): void
{
    ?>
    <header class="guard-header-desktop guard-desktop-chrome">
        <img src="<?= e(app_logo_url()) ?>" alt="<?= e(app_agency_name()) ?>" class="logo-img" width="52" height="52" decoding="async">
        <div class="agency-name"><?= e(app_agency_name_upper()) ?></div>
        <nav class="nav-links">
            <a href="<?= e(guard_url('corner.php')) ?>" class="nav-link"<?= ui_tooltip("Guard's corner — resources and updates") ?>>GUARD'S CORNER</a>
            <a href="<?= e(guard_url('inbox.php')) ?>" class="nav-link"<?= ui_tooltip('View memos and report status') ?>>GUARD'S INBOX</a>
            <?= theme_toggle_markup([
                'id' => 'guardDesktopThemeToggle',
                'mode' => 'light-class',
                'title' => 'Toggle light or dark appearance',
            ]) ?>
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
    <header class="guard-desktop-chrome">
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
    // Intentionally empty: footer content removed from guard views.
}
