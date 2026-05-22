<?php
declare(strict_types=1);

require_once __DIR__ . '/guard_ui_icons.php';

function guard_ui_brand_label(): string
{
    return 'Golden Z-5 Security & Investigation';
}

/** @return list<array{id:string, href:string, label:string, icon:string}> */
function guard_ui_nav_items(): array
{
    return [
        ['id' => 'dashboard', 'href' => 'dashboard.php', 'label' => 'Dashboard', 'icon' => 'dashboard'],
        ['id' => 'submit', 'href' => 'submit-report.php', 'label' => 'Submit report', 'icon' => 'plus-circle'],
        ['id' => 'inbox', 'href' => 'inbox.php', 'label' => 'Inbox', 'icon' => 'inbox'],
        ['id' => 'corner', 'href' => 'corner.php', 'label' => 'Guard corner', 'icon' => 'shield'],
    ];
}

function guard_ui_topbar_markup(string $pageTitle): void
{
    ?>
    <header class="guard-app__topbar" aria-label="Application header">
        <button
            type="button"
            class="guard-app__menu-btn"
            id="guardAppMenuBtn"
            aria-controls="guardAppDrawer"
            aria-expanded="false"
            aria-label="Open navigation menu"
        >
            <?= guard_ui_icon('menu', 22) ?>
        </button>
        <p class="guard-app__topbar-title" id="guardAppTopbarTitle"><?= e($pageTitle) ?></p>
    </header>
    <?php
}

function guard_ui_profile_block_markup(string $modifier = ''): void
{
    $profile = admin_sidebar_profile();
    $class = 'guard-app__profile' . ($modifier !== '' ? ' ' . $modifier : '');
    ?>
    <div class="<?= e($class) ?>">
        <div class="guard-app__profile-avatar" aria-hidden="true">
            <?= guard_ui_icon('user', 22) ?>
        </div>
        <div class="guard-app__profile-text">
            <p class="guard-app__profile-name"><?= e($profile['name']) ?></p>
            <p class="guard-app__profile-role"><?= e($profile['role']) ?></p>
            <p class="guard-app__profile-email"><?= e($profile['email']) ?></p>
        </div>
    </div>
    <?php
}

function guard_ui_settings_row_markup(string $themeToggleId, string $navActive = ''): void
{
    ?>
    <div class="guard-app__settings">
        <div class="guard-app__settings-tools" role="toolbar" aria-label="Account settings, sign out, and appearance">
            <a href="settings.php" class="guard-app__icon-btn guard-app__toolbar-btn<?= $navActive === 'settings' ? ' is-active' : '' ?>" aria-label="Account settings"<?= $navActive === 'settings' ? ' aria-current="page"' : '' ?>>
                <?= guard_ui_icon('gear', 20) ?>
                <span class="guard-app__toolbar-btn-label">Settings</span>
            </a>
            <form method="POST" action="../auth/logout-guard.php" class="guard-app__logout-form">
                <?= csrf_field() ?>
                <button type="submit" class="guard-app__icon-btn guard-app__toolbar-btn guard-app__logout-btn" aria-label="Sign out">
                    <?= guard_ui_icon('logout', 20) ?>
                    <span class="guard-app__toolbar-btn-label">Logout</span>
                </button>
            </form>
            <div class="guard-app__theme-toggle">
                <?= theme_toggle_markup([
                    'id' => $themeToggleId,
                    'mode' => 'light-class',
                    'title' => 'Toggle light or dark appearance',
                ]) ?>
            </div>
        </div>
    </div>
    <?php
}

function guard_ui_drawer_markup(string $navActive): void
{
    global $guardInboxUnread;
    $guardInboxUnread = (int) ($guardInboxUnread ?? 0);
    ?>
    <div class="guard-app__drawer" id="guardAppDrawer" aria-hidden="true">
        <div class="guard-app__drawer-backdrop" data-guard-drawer-close tabindex="-1" aria-hidden="true"></div>
        <aside class="guard-app__drawer-panel" role="dialog" aria-modal="true" aria-label="Navigation menu">
            <div class="guard-app__drawer-head">
                <p class="guard-app__drawer-brand"><?= e(guard_ui_brand_label()) ?></p>
                <button type="button" class="guard-app__drawer-close" data-guard-drawer-close aria-label="Close menu">
                    <?= guard_ui_icon('close', 20) ?>
                </button>
            </div>
            <nav class="guard-app__drawer-nav" aria-label="Guard workspace">
                <?php foreach (guard_ui_nav_items() as $item): ?>
                    <a
                        href="<?= e($item['href']) ?>"
                        class="guard-app__drawer-link<?= $navActive === $item['id'] ? ' is-active' : '' ?>"
                        <?= $navActive === $item['id'] ? ' aria-current="page"' : '' ?>
                        <?= $item['id'] === 'inbox' ? ' data-guard-inbox-nav' : '' ?>
                    >
                        <span class="guard-app__drawer-link-icon"><?= guard_ui_icon($item['icon'], 20) ?></span>
                        <?= e($item['label']) ?>
                        <?php if ($item['id'] === 'inbox' && $guardInboxUnread > 0): ?>
                            <span class="guard-app__drawer-link__badge" data-guard-inbox-badge aria-label="<?= $guardInboxUnread ?> unread messages"><?= $guardInboxUnread ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            <footer class="guard-app__drawer-footer">
                <?php guard_ui_profile_block_markup('guard-app__profile--drawer'); ?>
                <?php guard_ui_settings_row_markup('guardAppDrawerThemeToggle', $navActive); ?>
            </footer>
        </aside>
    </div>
    <?php
}

