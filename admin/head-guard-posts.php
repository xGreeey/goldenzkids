<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once APP_ROOT . '/includes/admin_ui_icons.php';
require_once __DIR__ . '/../includes/admin_head_guard_posts.php';
require_once __DIR__ . '/../includes/admin_head_guard_roster.php';

auth_require_permission('admin.duty.view');

$tablesReady = admin_head_guard_posts_ready($conn);

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['assign_post'])) {
    csrf_verify();

    $companyId = trim((string) ($_POST['company_id'] ?? ''));
    $postId = (int) ($_POST['post_id'] ?? 0);

    if ($companyId === '') {
        flash_set('error', 'Missing head guard account.');
    } else {
        $result = admin_head_guard_posts_assign($conn, $companyId, $postId);
        if ($result['ok']) {
            $name = trim((string) ($result['post_name'] ?? ''));
            require_once __DIR__ . '/../includes/portal_audit.php';
            portal_audit_log(
                $conn,
                'POST_ASSIGNED',
                $name !== '' ? 'Assigned to ' . $name : 'Assignment cleared',
                $companyId,
                (string) ($_SESSION['company_id'] ?? ''),
                auth_user_role()
            );
            flash_set(
                'success',
                $name !== ''
                    ? 'Assigned ' . $companyId . ' to ' . $name . '.'
                    : 'Cleared post assignment for ' . $companyId . '.'
            );
        } else {
            flash_set('error', (string) ($result['error'] ?? 'Could not save assignment.'));
        }
    }

    header('Location: head-guard-posts.php');
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['assign_guards'])) {
    csrf_verify();

    $companyId = trim((string) ($_POST['company_id'] ?? ''));
    $guardIds = isset($_POST['guard_ids']) && is_array($_POST['guard_ids'])
        ? array_map(static fn ($id): string => trim((string) $id), $_POST['guard_ids'])
        : [];

    if ($companyId === '') {
        flash_set('error', 'Missing head guard account.');
    } else {
        $result = admin_head_guard_roster_save_team($conn, $companyId, $guardIds);
        if ($result['ok']) {
            require_once __DIR__ . '/../includes/portal_audit.php';
            $count = (int) ($result['count'] ?? 0);
            portal_audit_log(
                $conn,
                'GUARDS_ASSIGNED',
                $count > 0 ? 'Assigned ' . $count . ' guard(s)' : 'Cleared guard team',
                $companyId,
                (string) ($_SESSION['company_id'] ?? ''),
                auth_user_role()
            );
            flash_set(
                'success',
                $count > 0
                    ? 'Assigned ' . $count . ' guard(s) to ' . $companyId . '.'
                    : 'Cleared guard team for ' . $companyId . '.'
            );
        } else {
            flash_set('error', (string) ($result['error'] ?? 'Could not save guard assignment.'));
        }
    }

    header('Location: head-guard-posts.php#assign-guards');
    exit;
}

$posts = admin_head_guard_posts_list_posts($conn);
$headGuards = admin_head_guard_posts_list_users($conn);
$activeCount = count(array_filter($headGuards, static fn (array $row): bool => (int) ($row['is_active'] ?? 0) === 1));
$assignedCount = count(array_filter(
    $headGuards,
    static fn (array $row): bool => trim((string) ($row['assigned_post_name'] ?? '')) !== ''
));
$unassignedCount = max(0, count($headGuards) - $assignedCount);
$fieldGuards = admin_head_guard_roster_ready($conn) ? admin_head_guard_roster_list_field_guards($conn) : [];
$unassignedGuardCount = count(array_filter(
    $fieldGuards,
    static fn (array $row): bool => ($row['head_id'] ?? null) === null
));
$rosterReady = admin_head_guard_roster_ready($conn);

$adminNavActive = 'head-guards';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?= mobile_meta_tags() ?>
    <title><?= e(app_agency_name()) ?> | Head guard posts</title>
    <script src="https://kit.fontawesome.com/3142eebea3.js" crossorigin="anonymous"></script>
    <?= app_fonts_link() ?>
    <style>
<?php admin_shell_styles(); ?>
<?php readfile(__DIR__ . '/assets/css/dashboard.css'); ?>
    </style>
</head>
<body class="light-mode page-head-guard-posts">
<?php admin_theme_body_boot(); ?>

<?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <main class="app-main">
        <header class="page-header page-header--inline">
            <h1 class="page-title">Head guard posts</h1>
            <p class="page-subtitle">Assign duty posts to head guard portal accounts so they can submit incident reports.</p>
        </header>

        <?php if (!$tablesReady): ?>
            <section class="panel panel--setup-missing" aria-labelledby="hg-posts-missing-heading">
                <div class="panel-body panel-body--setup-missing">
                    <div class="hg-setup-missing" role="status">
                        <span class="hg-setup-missing__icon" aria-hidden="true"><?= admin_ui_icon('database', 28) ?></span>
                        <div class="hg-setup-missing__copy">
                            <h2 id="hg-posts-missing-heading" class="hg-setup-missing__title">Database setup required</h2>
                            <p class="hg-setup-missing__lead">
                                Post assignment needs the callout tables. Run migration
                                <code>012_callout_posts_head_guards.sql</code> or
                                <code>php database/migrate.php</code> to create
                                <code>callout_posts</code>, <code>callout_head_guards</code>, and
                                <code>callout_post_assignments</code>.
                            </p>
                        </div>
                    </div>
                </div>
            </section>
        <?php else: ?>
            <section class="kpi-grid" aria-label="Head guard post summary">
                <article class="kpi-card kpi-card--personnel" title="Head guard portal accounts (role 0)">
                    <div class="kpi-stat">
                        <?= admin_kpi_icon('user-shield') ?>
                        <span class="kpi-value"><?= count($headGuards) ?></span>
                    </div>
                    <p class="kpi-label">Head guards</p>
                </article>
                <article class="kpi-card kpi-card--assigned" title="Accounts with an active duty post">
                    <div class="kpi-stat">
                        <?= admin_kpi_icon('map-location-dot') ?>
                        <span class="kpi-value"><?= $assignedCount ?></span>
                    </div>
                    <p class="kpi-label">Assigned to a post</p>
                </article>
                <article class="kpi-card kpi-card--pending" title="Active accounts still needing a post">
                    <div class="kpi-stat">
                        <?= admin_kpi_icon('triangle-exclamation') ?>
                        <span class="kpi-value"><?= $unassignedCount ?></span>
                    </div>
                    <p class="kpi-label">Without a post</p>
                </article>
                <article class="kpi-card kpi-card--posts" title="Duty posts available for assignment">
                    <div class="kpi-stat">
                        <?= admin_kpi_icon('clipboard-list') ?>
                        <span class="kpi-value"><?= count($posts) ?></span>
                    </div>
                    <p class="kpi-label">Duty posts</p>
                </article>
            </section>

            <section class="panel panel--duty" aria-labelledby="hg-posts-heading">
                <header class="panel-head panel-head--registry">
                    <div class="panel-head__head">
                        <div class="panel-head__intro">
                            <h2 id="hg-posts-heading" class="panel-title panel-title--registry">
                                <?= admin_ui_icon('map-location-dot', 18) ?>
                                Post assignments
                            </h2>
                            <div class="panel-head__subrow">
                                <p class="panel-head__note">
                                    <?= e((string) $activeCount) ?> active head guard<?= $activeCount === 1 ? '' : 's' ?> ·
                                    <?= e((string) count($posts)) ?> duty post<?= count($posts) === 1 ? '' : 's' ?> in the catalog.
                                    Unassigned guards see “No post is assigned to your guard profile” in the portal.
                                </p>
                                <div class="panel-head__table-labels panel-head__table-labels--desktop panel-head__table-labels--4" id="hg-posts-table-labels">
                                    <span>Account</span>
                                    <span>Email</span>
                                    <span>Current post</span>
                                    <span>Assign post</span>
                                </div>
                            </div>
                        </div>
                        <span class="panel-badge panel-badge--role" title="Portal role for head guards">Head guard · role 0</span>
                    </div>
                    <?php if ($headGuards !== []): ?>
                        <div class="hg-posts-toolbar" role="search">
                            <label class="hg-posts-toolbar__label" for="hg-posts-search">
                                <?= admin_ui_icon('magnifying-glass', 14) ?>
                                <span>Search accounts</span>
                            </label>
                            <input
                                type="search"
                                id="hg-posts-search"
                                class="hg-posts-toolbar__input field-input"
                                placeholder="Name, company ID, email, or post…"
                                autocomplete="off"
                            >
                        </div>
                    <?php endif; ?>
                </header>
                <div class="panel-body panel-body--table">
                    <div class="table-wrap">
                        <table class="data-table data-table--hg-posts" aria-describedby="hg-posts-table-labels">
                            <colgroup>
                                <col class="col-account">
                                <col class="col-email">
                                <col class="col-current">
                                <col class="col-assign">
                            </colgroup>
                            <thead class="data-table__head--compact">
                                <tr>
                                    <th scope="col">Account</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">Current post</th>
                                    <th scope="col">Assign post</th>
                                </tr>
                            </thead>
                            <tbody id="hg-posts-tbody">
                                <?php if ($headGuards === []): ?>
                                    <tr>
                                        <td colspan="4" class="table-empty">
                                            No head guard accounts (role 0) found.
                                            Create or activate them in User Management, then return here to assign posts.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($headGuards as $guard): ?>
                                        <?php
                                        $companyId = (string) ($guard['company_id'] ?? '');
                                        $assignedId = $guard['assigned_post_id'] ?? null;
                                        $assignedName = trim((string) ($guard['assigned_post_name'] ?? ''));
                                        $isActive = (int) ($guard['is_active'] ?? 0) === 1;
                                        $hasPost = $assignedName !== '';
                                        $fieldId = 'post_id_' . preg_replace('/[^a-z0-9_-]/i', '_', $companyId);
                                        $searchBlob = strtolower(implode(' ', [
                                            (string) ($guard['label'] ?? ''),
                                            $companyId,
                                            (string) ($guard['email'] ?? ''),
                                            $assignedName,
                                        ]));
                                        ?>
                                        <tr
                                            data-hg-post-row
                                            data-search="<?= e_attr($searchBlob) ?>"
                                            class="<?= $isActive ? '' : 'is-muted' ?><?= $hasPost ? '' : ' is-unassigned' ?>"
                                        >
                                            <td class="hg-posts-cell hg-posts-cell--account">
                                                <span class="hg-posts-name"><?= e((string) ($guard['label'] ?? $companyId)) ?></span>
                                                <span class="table-meta mono"><?= e($companyId) ?></span>
                                                <?php if (!$isActive): ?>
                                                    <span class="hg-posts-flag hg-posts-flag--inactive">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="hg-posts-cell hg-posts-cell--email">
                                                <?php if (trim((string) ($guard['email'] ?? '')) !== ''): ?>
                                                    <a href="mailto:<?= e_attr((string) $guard['email']) ?>" class="hg-posts-email"><?= e((string) $guard['email']) ?></a>
                                                <?php else: ?>
                                                    <span class="table-meta">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="hg-posts-cell hg-posts-cell--current">
                                                <?php if ($hasPost): ?>
                                                    <span class="hg-post-status hg-post-status--assigned">
                                                        <?= admin_ui_icon('circle-check', 12) ?>
                                                        <?= e($assignedName) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="hg-post-status hg-post-status--unassigned">
                                                        <?= admin_ui_icon('triangle-exclamation', 12) ?>
                                                        Not assigned
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="hg-posts-cell hg-posts-cell--assign">
                                                <form method="POST" class="hg-post-assign-form">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="assign_post" value="1">
                                                    <input type="hidden" name="company_id" value="<?= e($companyId) ?>">
                                                    <div class="hg-post-assign-form__field">
                                                        <label class="visually-hidden" for="<?= e($fieldId) ?>">Duty post for <?= e($companyId) ?></label>
                                                        <select
                                                            id="<?= e($fieldId) ?>"
                                                            name="post_id"
                                                            class="field-select hg-post-assign-form__select"
                                                        >
                                                            <option value="0"<?= $assignedId === null || (int) $assignedId <= 0 ? ' selected' : '' ?>>No post</option>
                                                            <?php foreach ($posts as $post): ?>
                                                                <?php $pid = (int) ($post['post_id'] ?? 0); ?>
                                                                <option value="<?= $pid ?>"<?= (int) $assignedId === $pid ? ' selected' : '' ?>><?= e((string) ($post['post_name'] ?? '')) ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <button type="submit" class="hg-post-assign-form__save">
                                                        <?= admin_ui_icon('floppy-disk', 14) ?>
                                                        <span>Save</span>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr id="hg-posts-no-results" class="hg-posts-no-results" hidden>
                                        <td colspan="4" class="table-empty">No accounts match your search.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <?php if ($rosterReady): ?>
            <section class="panel panel--duty panel--hg-roster" id="assign-guards" aria-labelledby="hg-roster-heading">
                <header class="panel-head panel-head--registry">
                    <div class="panel-head__head">
                        <div class="panel-head__intro">
                            <h2 id="hg-roster-heading" class="panel-title panel-title--registry">
                                <?= admin_ui_icon('users', 18) ?>
                                Assign Guards
                            </h2>
                            <div class="panel-head__subrow">
                                <p class="panel-head__note">
                                    <?= e((string) count($fieldGuards)) ?> field guard<?= count($fieldGuards) === 1 ? '' : 's' ?> in the roster ·
                                    <?= e((string) $unassignedGuardCount) ?> unassigned.
                                    Assign each head guard’s field guards from this panel.
                                </p>
                                <div class="panel-head__table-labels panel-head__table-labels--desktop panel-head__table-labels--4" id="hg-roster-table-labels">
                                    <span>Head guard</span>
                                    <span>Post</span>
                                    <span>Current team</span>
                                    <span>Assign guards</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php if ($headGuards !== []): ?>
                        <div class="hg-posts-toolbar" role="search">
                            <label class="hg-posts-toolbar__label" for="hg-roster-search">
                                <?= admin_ui_icon('magnifying-glass', 14) ?>
                                <span>Search head guards</span>
                            </label>
                            <input
                                type="search"
                                id="hg-roster-search"
                                class="hg-posts-toolbar__input field-input"
                                placeholder="Name, company ID, or post…"
                                autocomplete="off"
                            >
                        </div>
                    <?php endif; ?>
                </header>
                <div class="panel-body panel-body--table">
                    <div class="table-wrap">
                        <table class="data-table data-table--hg-roster" aria-describedby="hg-roster-table-labels">
                            <colgroup>
                                <col class="col-hg-account">
                                <col class="col-hg-post">
                                <col class="col-hg-team">
                                <col class="col-hg-pick">
                            </colgroup>
                            <thead class="data-table__head--compact">
                                <tr>
                                    <th scope="col">Head guard</th>
                                    <th scope="col">Post</th>
                                    <th scope="col">Current team</th>
                                    <th scope="col">Assign guards</th>
                                </tr>
                            </thead>
                            <tbody id="hg-roster-tbody">
                                <?php if ($headGuards === []): ?>
                                    <tr>
                                        <td colspan="4" class="table-empty">
                                            No head guard accounts (role 0) found. Create them in User Management first.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($headGuards as $guard): ?>
                                        <?php
                                        $companyId = (string) ($guard['company_id'] ?? '');
                                        $assignedName = trim((string) ($guard['assigned_post_name'] ?? ''));
                                        $team = admin_head_guard_roster_team_for_head($conn, $companyId);
                                        $teamCount = count($team);
                                        $rosterFieldId = 'guard_ids_' . preg_replace('/[^a-z0-9_-]/i', '_', $companyId);
                                        $rosterOptions = admin_head_guard_roster_select_options_admin($conn, $companyId);
                                        $searchBlob = strtolower(implode(' ', [
                                            (string) ($guard['label'] ?? ''),
                                            $companyId,
                                            $assignedName,
                                        ]));
                                        ?>
                                        <tr
                                            data-hg-roster-row
                                            data-search="<?= e_attr($searchBlob) ?>"
                                            class="<?= $teamCount > 0 ? '' : ' is-unassigned' ?>"
                                        >
                                            <td class="hg-posts-cell hg-posts-cell--account">
                                                <span class="hg-posts-name"><?= e((string) ($guard['label'] ?? $companyId)) ?></span>
                                                <span class="table-meta mono"><?= e($companyId) ?></span>
                                            </td>
                                            <td class="hg-posts-cell hg-posts-cell--current">
                                                <?php if ($assignedName !== ''): ?>
                                                    <span class="hg-post-status hg-post-status--assigned">
                                                        <?= admin_ui_icon('circle-check', 12) ?>
                                                        <?= e($assignedName) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="hg-post-status hg-post-status--unassigned">
                                                        <?= admin_ui_icon('triangle-exclamation', 12) ?>
                                                        No post
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="hg-roster-cell hg-roster-cell--team">
                                                <?php if ($teamCount > 0): ?>
                                                    <span class="hg-roster-count"><?= e((string) $teamCount) ?> guard<?= $teamCount === 1 ? '' : 's' ?></span>
                                                    <span class="hg-roster-team-preview table-meta"><?= e(implode(', ', array_map(static fn (array $m): string => (string) $m['label'], array_slice($team, 0, 4)))) ?><?= $teamCount > 4 ? '…' : '' ?></span>
                                                <?php else: ?>
                                                    <span class="table-meta">No guards assigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="hg-roster-cell hg-roster-cell--pick">
                                                <form method="POST" class="hg-roster-assign-form">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="assign_guards" value="1">
                                                    <input type="hidden" name="company_id" value="<?= e($companyId) ?>">
                                                    <div class="hg-roster-assign-form__field">
                                                        <label class="visually-hidden" for="<?= e($rosterFieldId) ?>">Guards for <?= e($companyId) ?></label>
                                                        <select
                                                            id="<?= e($rosterFieldId) ?>"
                                                            name="guard_ids[]"
                                                            class="field-select hg-roster-assign-form__select"
                                                            multiple
                                                            size="6"
                                                        >
                                                            <?php
                                                            $lastGroup = '';
                                                            foreach ($rosterOptions as $opt):
                                                                $group = (string) ($opt['group'] ?? '');
                                                                if ($group !== $lastGroup && $group !== ''):
                                                                    if ($lastGroup !== ''):
                                                                        echo '</optgroup>';
                                                                    endif;
                                                                    echo '<optgroup label="' . e_attr($group) . '">';
                                                                    $lastGroup = $group;
                                                                endif;
                                                                ?>
                                                                <option
                                                                    value="<?= e((string) $opt['company_id']) ?>"
                                                                    <?= !empty($opt['selected']) ? ' selected' : '' ?>
                                                                ><?= e((string) $opt['label']) ?></option>
                                                            <?php endforeach;
                                                            if ($lastGroup !== ''):
                                                                echo '</optgroup>';
                                                            endif;
                                                            ?>
                                                        </select>
                                                        <p class="form-hint hg-roster-assign-form__hint">Hold Ctrl (Windows) or Cmd (Mac) to select multiple.</p>
                                                    </div>
                                                    <button type="submit" class="hg-post-assign-form__save">
                                                        <?= admin_ui_icon('floppy-disk', 14) ?>
                                                        <span>Save team</span>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr id="hg-roster-no-results" class="hg-posts-no-results" hidden>
                                        <td colspan="4" class="table-empty">No head guards match your search.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</div>

<?php admin_shell_scripts(); ?>
<?php if ($tablesReady && $headGuards !== []): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    function wireFilter(searchId, rowSelector, emptyId) {
        const search = document.getElementById(searchId);
        const rows = document.querySelectorAll(rowSelector);
        const emptyRow = document.getElementById(emptyId);
        if (!search || !rows.length) {
            return;
        }
        function applyFilter() {
            const q = search.value.trim().toLowerCase();
            let visible = 0;
            rows.forEach(function (row) {
                const blob = (row.getAttribute('data-search') || '').toLowerCase();
                const show = q === '' || blob.indexOf(q) !== -1;
                row.hidden = !show;
                if (show) {
                    visible += 1;
                }
            });
            if (emptyRow) {
                emptyRow.hidden = q === '' || visible > 0;
            }
        }
        search.addEventListener('input', applyFilter);
        search.addEventListener('search', applyFilter);
    }
    wireFilter('hg-posts-search', '[data-hg-post-row]', 'hg-posts-no-results');
    wireFilter('hg-roster-search', '[data-hg-roster-row]', 'hg-roster-no-results');
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/global-alerts.php'; ?>
</body>
</html>
