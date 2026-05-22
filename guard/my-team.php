<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/guard_layout.php';
require_once __DIR__ . '/../includes/guard_portal.php';
require_once __DIR__ . '/../includes/admin_head_guard_roster.php';

auth_require_permission('guard.dashboard.view');

$companyId = (string) ($_SESSION['company_id'] ?? '');
if ($companyId === '' || !admin_head_guard_roster_is_head_guard_account($conn, $companyId)) {
    http_response_code(403);
    exit('Head guard access only.');
}

$rosterReady = admin_head_guard_roster_ready($conn);
$success = null;
$error = null;

if ($rosterReady && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['save_team'])) {
    csrf_verify();
    $guardIds = isset($_POST['guard_ids']) && is_array($_POST['guard_ids'])
        ? array_map(static fn ($id): string => trim((string) $id), $_POST['guard_ids'])
        : [];
    $result = admin_head_guard_roster_save_team_self($conn, $companyId, $guardIds);
    if ($result['ok']) {
        $count = (int) ($result['count'] ?? 0);
        $success = $count > 0
            ? 'Your team now has ' . $count . ' guard(s).'
            : 'Your team list has been cleared.';
    } else {
        $error = (string) ($result['error'] ?? 'Could not save your team.');
    }
}

$team = $rosterReady ? admin_head_guard_roster_team_for_head($conn, $companyId) : [];
$options = $rosterReady ? admin_head_guard_roster_select_options_for_head($conn, $companyId) : [];
$assignedPost = guard_portal_assigned_post($conn, $companyId);

$guardNavActive = 'team';
guard_layout_head('My team', 'team', false);
?>
        <section class="card-panel guard-team-panel">
            <h2 class="panel-title">My team</h2>
            <p class="form-hint guard-team-panel__lead">
                Choose field guards assigned to you. Only unassigned guards and guards already on your team are listed.
                <?php if ($assignedPost !== ''): ?>
                    Duty post: <strong><?= e($assignedPost) ?></strong>.
                <?php endif; ?>
            </p>

            <?php if (!$rosterReady): ?>
                <p class="empty-state">Guard roster is not available yet. Contact your administrator.</p>
            <?php else: ?>
                <?php if ($success !== null): ?>
                    <p class="form-success" role="status"><?= e($success) ?></p>
                <?php endif; ?>
                <?php if ($error !== null): ?>
                    <p class="form-error" role="alert"><?= e($error) ?></p>
                <?php endif; ?>

                <?php if ($team !== []): ?>
                    <ul class="guard-team-list" aria-label="Current team">
                        <?php foreach ($team as $member): ?>
                            <li><?= e((string) $member['label']) ?> <span class="table-meta mono"><?= e((string) $member['company_id']) ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <form method="POST" class="guard-team-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="save_team" value="1">
                    <div class="form-field">
                        <label for="guard_ids_team">Assign guards</label>
                        <select id="guard_ids_team" name="guard_ids[]" class="field-select guard-team-form__select" multiple size="8">
                            <?php
                            $lastGroup = '';
                            foreach ($options as $opt):
                                $group = (string) ($opt['group'] ?? '');
                                if ($group !== $lastGroup && $group !== ''):
                                    if ($lastGroup !== ''):
                                        echo '</optgroup>';
                                    endif;
                                    echo '<optgroup label="' . e_attr($group) . '">';
                                    $lastGroup = $group;
                                endif;
                                ?>
                                <option value="<?= e((string) $opt['company_id']) ?>"<?= !empty($opt['selected']) ? ' selected' : '' ?>><?= e((string) $opt['label']) ?></option>
                            <?php endforeach;
                            if ($lastGroup !== ''):
                                echo '</optgroup>';
                            endif;
                            ?>
                        </select>
                        <p class="form-hint">Tap and hold (mobile) or Ctrl/Cmd-click (desktop) to select multiple guards, then save.</p>
                    </div>
                    <button type="submit" class="btn btn--primary">Save team</button>
                </form>
            <?php endif; ?>
        </section>
<?php
guard_layout_end();
