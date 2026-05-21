<?php
declare(strict_types=1);

/** Create-group panel fragment (included from messaging_board.php). */
?>
<div class="messaging-create-panel">
    <div class="messaging-create-panel__intro">
        <i class="fa-solid fa-users-rays messaging-create-panel__icon" aria-hidden="true"></i>
        <div>
            <h3 class="messaging-create-panel__title">Create a group chat</h3>
            <p class="messaging-create-panel__desc">Name the group and choose which head guards should receive messages in this thread.</p>
        </div>
    </div>
    <?php if ($canRenderCreateForm): ?>
    <form method="POST" action="<?= e($messagingCreateGroupUrl) ?>" class="messaging-create-group__form js-messaging-create-group-form">
        <?= csrf_field() ?>
        <label class="messaging-create-group__label" for="groupNameInput">Group name</label>
        <input type="text"
               name="group_name"
               id="groupNameInput"
               class="messaging-create-group__input"
               maxlength="120"
               required
               placeholder="e.g. SM Megamall supervisors">
        <span class="messaging-create-group__label">Head guards to include</span>
        <div class="messaging-create-group__members" role="group" aria-label="Select head guards">
            <?php foreach ($messagingHeadGuardOptions as $option): ?>
            <label class="messaging-member-option">
                <input type="checkbox" name="member_ids[]" value="<?= e($option['company_id']) ?>">
                <span><?= e($option['label']) ?></span>
                <span class="messaging-member-option__id"><?= e($option['company_id']) ?></span>
            </label>
            <?php endforeach; ?>
        </div>
        <button type="submit" class="messaging-create-group__submit">
            <i class="fa-solid fa-check" aria-hidden="true"></i>
            Create group chat
        </button>
    </form>
    <?php else: ?>
    <p class="messaging-board__notice">
        No active head guard accounts yet. In <strong>User Management</strong>, create or activate a user with role <strong>0 (head guard)</strong>, then return here to build a group.
    </p>
    <?php endif; ?>
</div>
