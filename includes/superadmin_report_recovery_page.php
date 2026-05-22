<?php
declare(strict_types=1);

/**
 * Superadmin vault — deleted & archived snapshots only (no admin registry fetch).
 *
 * Expects: $superadminNavActive
 */

require_once __DIR__ . '/superadmin_report_nav.php';
require_once __DIR__ . '/superadmin_page.css.php';
require_once __DIR__ . '/admin_report_recovery.php';

auth_require_permission('superadmin.dashboard.view');

$recoveryKind = superadmin_report_recovery_kind($superadminNavActive ?? 'reports');
$navItem = null;
foreach (superadmin_report_nav_items() as $item) {
    if (in_array($superadminNavActive ?? '', $item['active'], true) || $item['slug'] === ($superadminNavActive ?? '')) {
        $navItem = $item;
        break;
    }
}
$pageTitle = $navItem !== null ? (string) $navItem['label'] : 'Report recovery';
$actorId = (string) ($_SESSION['company_id'] ?? 'superadmin');

$flash = null;
$supportsDelete = in_array($recoveryKind, ['incident', 'dtr', 'weekly-activity'], true);
$supportsArchive = in_array($recoveryKind, ['incident', 'dtr', 'daily-activity'], true);

$allowedVaults = ['all'];
if ($supportsDelete) {
    $allowedVaults[] = 'deleted';
}
if ($supportsArchive) {
    $allowedVaults[] = 'archived';
}

$actionFilter = trim((string) ($_GET['vault'] ?? 'all'));
if (!in_array($actionFilter, $allowedVaults, true)) {
    $actionFilter = 'all';
}
$vaultFilter = $actionFilter === 'all' ? null : $actionFilter;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();
    $postAction = trim((string) ($_POST['recovery_action'] ?? ''));
    $recoveryId = $_POST['recovery_id'] ?? '';
    if ($postAction === 'restore' && $recoveryId !== '') {
        $result = admin_report_recovery_restore($recoveryId, $actorId);
        $flash = ['type' => ($result['ok'] ?? false) ? 'success' : 'error', 'message' => (string) ($result['message'] ?? '')];
    } elseif ($postAction === 'purge' && $recoveryId !== '') {
        $result = admin_report_recovery_purge($recoveryId);
        $flash = ['type' => ($result['ok'] ?? false) ? 'success' : 'error', 'message' => (string) ($result['message'] ?? '')];
    }
}

$entries = admin_report_recovery_list($recoveryKind, $vaultFilter);
$deletedCount = $supportsDelete ? count(admin_report_recovery_list($recoveryKind, 'deleted')) : 0;
$archivedCount = $supportsArchive ? count(admin_report_recovery_list($recoveryKind, 'archived')) : 0;
$totalCount = $deletedCount + $archivedCount;

if (!isset($adminProfile)) {
    require_once __DIR__ . '/admin_shell.php';
    $adminProfile = admin_sidebar_profile();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?= mobile_meta_tags() ?>
    <title><?= e(app_agency_name()) ?> | <?= e($pageTitle) ?> — Deleted &amp; archived</title>
    <script src="https://kit.fontawesome.com/3142eebea3.js" crossorigin="anonymous"></script>
    <?= app_fonts_link() ?>
    <style>
<?php admin_shell_styles(); ?>
<?php superadmin_page_styles(); ?>
        .sa-recovery-panel { margin-top: 0; }
        .sa-recovery-tabs { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
        .sa-recovery-tabs a {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.8125rem;
            text-decoration: none;
            border: 1px solid var(--app-border);
            color: var(--app-ink-muted);
        }
        .sa-recovery-tabs a.is-active {
            background: var(--app-accent, #1e5a8a);
            border-color: transparent;
            color: #fff;
        }
        .sa-recovery-table { width: 100%; border-collapse: collapse; font-size: 0.8125rem; }
        .sa-recovery-table th,
        .sa-recovery-table td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid var(--app-border);
            vertical-align: middle;
        }
        .sa-recovery-table th { font-weight: 600; color: var(--app-ink-muted); }
        .sa-recovery-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .sa-recovery-badge--deleted { background: rgba(220, 38, 38, 0.12); color: #b91c1c; }
        .sa-recovery-badge--archived { background: rgba(234, 179, 8, 0.15); color: #a16207; }
        .sa-recovery-actions { display: flex; gap: 6px; flex-wrap: wrap; }
        .sa-recovery-empty {
            padding: 32px 16px;
            text-align: center;
            color: var(--app-ink-muted);
        }
        .sa-recovery-empty__hint { font-size: 0.8125rem; margin-top: 8px; opacity: 0.9; }
        .sa-recovery-flash {
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 14px;
            font-size: 0.875rem;
        }
        .sa-recovery-flash--success { background: rgba(22, 163, 74, 0.12); color: #15803d; }
        .sa-recovery-flash--error { background: rgba(220, 38, 38, 0.1); color: #b91c1c; }
        .sa-recovery-btn {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.8125rem;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid transparent;
        }
        .sa-recovery-btn--primary {
            background: var(--app-accent, #1e5a8a);
            color: #fff;
        }
        .sa-recovery-btn--secondary {
            background: transparent;
            border-color: var(--app-border);
            color: var(--app-ink-muted);
        }
        .sa-recovery-inline-form { display: inline; }
    </style>
</head>
<body class="light-mode superadmin-portal page-sa-report-recovery"
      data-admin-nav="<?= e($superadminNavActive ?? 'reports') ?>">

<?php
$superadminNavActive = $superadminNavActive ?? 'reports';
require __DIR__ . '/superadmin_sidebar.php';
?>

    <main class="app-main">
        <header class="page-header page-header--inline">
            <h1 class="page-title"><?= e($pageTitle) ?></h1>
            <p class="page-subtitle">Deleted and archived items only — captured when admin removes or closes a record. This does not load the admin report registry.</p>
        </header>

        <?php if ($flash !== null): ?>
        <div class="sa-recovery-flash sa-recovery-flash--<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <section class="sa-recovery-panel" aria-label="Deleted and archived vault">
            <nav class="sa-recovery-tabs" aria-label="Filter by action">
                <?php if ($supportsDelete && $supportsArchive): ?>
                <a href="?vault=all" class="<?= $actionFilter === 'all' ? 'is-active' : '' ?>">All (<?= $totalCount ?>)</a>
                <?php endif; ?>
                <?php if ($supportsDelete): ?>
                <a href="?vault=deleted" class="<?= $actionFilter === 'deleted' ? 'is-active' : '' ?>">Deleted (<?= $deletedCount ?>)</a>
                <?php endif; ?>
                <?php if ($supportsArchive): ?>
                <a href="?vault=archived" class="<?= $actionFilter === 'archived' ? 'is-active' : '' ?>">Archived (<?= $archivedCount ?>)</a>
                <?php endif; ?>
            </nav>

            <?php if ($entries === []): ?>
            <div class="sa-recovery-empty">
                <p>No <?= $actionFilter === 'all' ? 'deleted or archived' : e($actionFilter) ?> entries yet.</p>
                <p class="sa-recovery-empty__hint">When an admin deletes or archives a record in the admin portal, it is stored here for restore.</p>
            </div>
            <?php else: ?>
            <table class="sa-recovery-table">
                <thead>
                    <tr>
                        <th scope="col">Reference</th>
                        <th scope="col">Action</th>
                        <th scope="col">When</th>
                        <th scope="col">By</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entries as $entry):
                        $action = (string) ($entry['action_type'] ?? '');
                        $badgeClass = $action === 'deleted' ? 'deleted' : 'archived';
                        $rid = $entry['recovery_id'];
                        ?>
                    <tr>
                        <td><strong><?= e((string) ($entry['record_ref'] ?? '')) ?></strong></td>
                        <td><span class="sa-recovery-badge sa-recovery-badge--<?= e($badgeClass) ?>"><?= e(ucfirst($action)) ?></span></td>
                        <td><?= e((string) ($entry['created_at'] ?? '')) ?></td>
                        <td><?= e((string) ($entry['actor_company_id'] ?? '—')) ?></td>
                        <td>
                            <div class="sa-recovery-actions">
                                <form method="POST" class="sa-recovery-inline-form" onsubmit="return confirm('Restore this record to the admin registry?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="recovery_action" value="restore">
                                    <input type="hidden" name="recovery_id" value="<?= e_attr((string) $rid) ?>">
                                    <button type="submit" class="sa-recovery-btn sa-recovery-btn--primary">Restore</button>
                                </form>
                                <form method="POST" class="sa-recovery-inline-form" onsubmit="return confirm('Remove this vault entry without restoring?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="recovery_action" value="purge">
                                    <input type="hidden" name="recovery_id" value="<?= e_attr((string) $rid) ?>">
                                    <button type="submit" class="sa-recovery-btn sa-recovery-btn--secondary">Dismiss</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </section>
    </main>

<?php
echo '</div>';
admin_shell_scripts();
?>
</body>
</html>
