<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/admin_head_guard_posts.php';

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

$posts = admin_head_guard_posts_list_posts($conn);
$headGuards = admin_head_guard_posts_list_users($conn);
$activeCount = count(array_filter($headGuards, static fn (array $row): bool => (int) ($row['is_active'] ?? 0) === 1));

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
<body class="light-mode">

<?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <main class="app-main">
        <header class="page-header page-header--inline">
            <h1 class="page-title">Head guard posts</h1>
            <p class="page-subtitle">Assign duty posts to head guard portal accounts so they can submit incident reports.</p>
        </header>

        <?php if (!$tablesReady): ?>
            <section class="panel" aria-labelledby="hg-posts-missing-heading">
                <div class="panel-body">
                    <h2 id="hg-posts-missing-heading" class="panel-title">Setup required</h2>
                    <p class="panel-head__note">Run migration <code>012_callout_posts_head_guards.sql</code> (or <code>php database/migrate.php</code>) to create <code>callout_posts</code>, <code>callout_head_guards</code>, and <code>callout_post_assignments</code>.</p>
                </div>
            </section>
        <?php else: ?>
            <section class="panel panel--duty" aria-labelledby="hg-posts-heading">
                <header class="panel-head panel-head--registry">
                    <div class="panel-head__head">
                        <div class="panel-head__intro">
                            <h2 id="hg-posts-heading" class="panel-title panel-title--registry">
                                <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>
                                Post assignments
                            </h2>
                            <div class="panel-head__subrow">
                                <p class="panel-head__note">
                                    <?= e((string) $activeCount) ?> active head guard<?= $activeCount === 1 ? '' : 's' ?> ·
                                    <?= e((string) count($posts)) ?> duty post<?= count($posts) === 1 ? '' : 's' ?> available.
                                    Without a post, guards see “No post is assigned to your guard profile.”
                                </p>
                                <div class="panel-head__table-labels panel-head__table-labels--desktop" id="hg-posts-table-labels">
                                    <span>Account</span>
                                    <span>Email</span>
                                    <span>Current post</span>
                                    <span>Assign post</span>
                                </div>
                            </div>
                        </div>
                        <span class="panel-badge">Role 0</span>
                    </div>
                </header>
                <div class="panel-body" style="padding: 0;">
                    <div class="table-wrap">
                        <table class="data-table" aria-describedby="hg-posts-table-labels">
                            <thead class="data-table__head--compact">
                                <tr>
                                    <th scope="col">Account</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">Current post</th>
                                    <th scope="col">Assign post</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($headGuards === []): ?>
                                    <tr>
                                        <td colspan="4" class="table-empty">No head guard accounts (role 0) found. Create them in User Management.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($headGuards as $guard): ?>
                                        <?php
                                        $companyId = (string) ($guard['company_id'] ?? '');
                                        $assignedId = $guard['assigned_post_id'] ?? null;
                                        $assignedName = $guard['assigned_post_name'] ?? null;
                                        $isActive = (int) ($guard['is_active'] ?? 0) === 1;
                                        ?>
                                        <tr<?= $isActive ? '' : ' class="is-muted"' ?>>
                                            <td>
                                                <strong><?= e((string) ($guard['label'] ?? $companyId)) ?></strong>
                                                <div class="table-meta"><?= e($companyId) ?><?= $isActive ? '' : ' · inactive' ?></div>
                                            </td>
                                            <td><?= e((string) ($guard['email'] ?? '—')) ?></td>
                                            <td>
                                                <?php if ($assignedName !== null && $assignedName !== ''): ?>
                                                    <span class="panel-badge"><?= e((string) $assignedName) ?></span>
                                                <?php else: ?>
                                                    <span class="table-meta">Not assigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form method="POST" class="hg-post-assign-form" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="assign_post" value="1">
                                                    <input type="hidden" name="company_id" value="<?= e($companyId) ?>">
                                                    <label class="visually-hidden" for="post_id_<?= e(preg_replace('/[^a-z0-9_-]/i', '_', $companyId)) ?>">Duty post for <?= e($companyId) ?></label>
                                                    <select
                                                        id="post_id_<?= e(preg_replace('/[^a-z0-9_-]/i', '_', $companyId)) ?>"
                                                        name="post_id"
                                                        class="guard-select__native"
                                                        style="min-width:12rem;max-width:100%;"
                                                    >
                                                        <option value="0"<?= $assignedId === null ? ' selected' : '' ?>>— No post —</option>
                                                        <?php foreach ($posts as $post): ?>
                                                            <?php $pid = (int) ($post['post_id'] ?? 0); ?>
                                                            <option value="<?= $pid ?>"<?= $assignedId === $pid ? ' selected' : '' ?>><?= e((string) ($post['post_name'] ?? '')) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="submit" class="btn-primary btn-primary--compact">Save</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>
</div>

<?php admin_shell_scripts(); ?>

<?php require_once __DIR__ . '/../includes/global-alerts.php'; ?>
</body>
</html>
