<?php
declare(strict_types=1);

require_once __DIR__ . '/admin_ui_icons.php';
require_once __DIR__ . '/memo_portal.php';

$memoHeadGuardCount = count(memo_portal_head_guard_recipient_ids($conn));
?>
<section class="panel panel--compose panel--inbox" aria-labelledby="compose-heading">
    <div class="panel-head">
        <h2 id="compose-heading" class="panel-title">
            <?= admin_ui_icon('envelope-open-text', 18) ?>
            Memo
        </h2>
    </div>
    <div class="panel-body">
        <p class="form-hint memo-compose-hint">
            Publish a secured memo to every active head guard account (role 0). Each memo appears on
            <strong>Guard corner → Board → Announcement</strong> for those accounts.
        </p>
        <?php if ($memoHeadGuardCount === 0): ?>
            <p class="field-hint field-hint--alert" role="status">No active head guard accounts found. Create head guard users before publishing memos.</p>
        <?php endif; ?>
        <form action="<?= e(app_url('admin/send-memo.php')) ?>" method="POST" id="memoForm" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="distribution_type" id="distTypeValue" value="broadcast">
            <input type="hidden" name="recipient_scope" value="head_guards">

            <div id="memoDetailsContainer" class="form-details is-visible">
                <div class="field">
                    <label for="memoTypeInput" class="field-label">Message category<span class="required-mark">*</span></label>
                    <select name="memo_type" id="memoTypeInput" class="field-select" required>
                        <option value="" disabled selected>Select a category…</option>
                        <option value="DIRECTIVE">Policy directive — rules and procedure updates</option>
                        <option value="NOTICE">General notice — informational updates</option>
                        <option value="NTE">Notice to explain — formal compliance request</option>
                        <option value="BOLO">Security advisory — threat or watch notice</option>
                    </select>
                </div>

                <div class="field">
                    <label for="memoContentInput" class="field-label">Memo body<span class="required-mark">*</span></label>
                    <textarea name="content" id="memoContentInput" class="field-textarea" rows="8" required></textarea>
                </div>

                <button type="submit" name="generate_memo" class="btn-primary"<?= $memoHeadGuardCount === 0 ? ' disabled' : '' ?><?= ui_tooltip('Send memo to all head guards') ?>>
                    <?= admin_ui_icon('lock', 16) ?>
                    Publish memo to head guards
                </button>
            </div>
        </form>
    </div>
</section>
