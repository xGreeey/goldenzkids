<?php
declare(strict_types=1);

if (!function_exists('message_groups_table_exists')) {
    require_once __DIR__ . '/group_messaging.php';
}

/**
 * @var list<array{company_id:string,label:string,unread:int}> $messagingContacts
 * @var string $messagingViewerId
 * @var string|null $messagingActivePeer
 * @var list<array{message_id:int,sender_company_id:string,body_text:string,is_mine:bool,created_at:string}> $messagingThread
 * @var string $messagingPostUrl
 * @var string $messagingReturnUrl
 * @var bool $messagingAvailable
 * @var string $messagingMode
 * @var list<array{group_id:int,group_name:string,unread:int,member_count:int}> $messagingGroups
 * @var int|null $messagingActiveGroupId
 * @var list<array{message_id:int,sender_company_id:string,sender_label:string,body_text:string,is_mine:bool,created_at:string}> $messagingGroupThread
 * @var array{group_id:int,group_name:string,members:list<array{company_id:string,label:string}>}|null $messagingGroupMeta
 * @var bool $messagingCanCreateGroups
 * @var list<array{company_id:string,label:string}> $messagingHeadGuardOptions
 * @var string $messagingGroupPostUrl
 * @var string $messagingCreateGroupUrl
 * @var bool $messagingShowDirect
 * @var bool $groupsAvailable
 */
$messagingContacts = $messagingContacts ?? [];
$messagingViewerId = $messagingViewerId ?? '';
$messagingActivePeer = $messagingActivePeer ?? null;
$messagingThread = $messagingThread ?? [];
$messagingPostUrl = $messagingPostUrl ?? '';
$messagingReturnUrl = $messagingReturnUrl ?? '';
$messagingAvailable = $messagingAvailable ?? false;
$messagingMode = $messagingMode ?? 'direct';
$messagingGroups = $messagingGroups ?? [];
$messagingActiveGroupId = $messagingActiveGroupId ?? null;
$messagingGroupThread = $messagingGroupThread ?? [];
$messagingGroupMeta = $messagingGroupMeta ?? null;
$messagingCanCreateGroups = $messagingCanCreateGroups ?? false;
$messagingHeadGuardOptions = $messagingHeadGuardOptions ?? [];
$messagingGroupPostUrl = $messagingGroupPostUrl ?? 'send-group-message.php';
$messagingCreateGroupUrl = $messagingCreateGroupUrl ?? 'create-message-group.php';
$messagingShowDirect = $messagingShowDirect ?? (internal_messaging_peer_role(auth_user_role()) >= 0);
$groupsAvailable = $groupsAvailable ?? (isset($conn) && $conn instanceof mysqli && message_groups_table_exists($conn));

$messagingPeerLabel = '';
if ($messagingActivePeer !== null && $messagingActivePeer !== '') {
    foreach ($messagingContacts as $contact) {
        if ($contact['company_id'] === $messagingActivePeer) {
            $messagingPeerLabel = $contact['label'];
            break;
        }
    }
    if ($messagingPeerLabel === '') {
        $messagingPeerLabel = $messagingActivePeer;
    }
}

$boardSubtitle = $messagingShowDirect
    ? 'Direct messages with super administrators, plus group chats with head guards.'
    : 'Group chats with operations administrators.';
?>
<section class="messaging-board" id="messaging-board" aria-labelledby="messaging-board-heading">
    <div class="messaging-board__head">
        <h2 id="messaging-board-heading" class="messaging-board__title">
            <i class="fa-solid fa-comments" aria-hidden="true"></i>
            Staff messaging board
        </h2>
        <p class="messaging-board__subtitle"><?= e($boardSubtitle) ?></p>
    </div>
    <?php if (!$messagingAvailable && !$groupsAvailable): ?>
        <p class="messaging-board__notice" role="status">
            Messaging is not available yet. Run <code>php database/migrate.php</code> to create the messaging tables.
        </p>
    <?php else: ?>
    <div class="messaging-board__layout">
        <div class="messaging-board__contacts" role="navigation" aria-label="Conversations">
            <?php if ($messagingShowDirect && $messagingAvailable): ?>
            <div class="messaging-board__section">
                <h3 class="messaging-board__section-title">Direct</h3>
                <?php if ($messagingContacts === []): ?>
                    <p class="messaging-board__empty">No <?= auth_normalize_role(auth_user_role()) === AUTH_ROLE_ADMIN ? 'super administrator' : 'administrator' ?> accounts are active yet.</p>
                <?php else: ?>
                <ul class="messaging-contact-list">
                    <?php foreach ($messagingContacts as $contact): ?>
                        <?php
                        $isActive = $messagingMode === 'direct' && $messagingActivePeer === $contact['company_id'];
                        $peerUrl = $messagingReturnUrl . (str_contains($messagingReturnUrl, '?') ? '&' : '?')
                            . 'peer=' . rawurlencode($contact['company_id']) . '#messaging-board';
                        ?>
                    <li>
                        <a href="<?= e($peerUrl) ?>"
                           class="messaging-contact<?= $isActive ? ' is-active' : '' ?>"
                           <?= $isActive ? 'aria-current="true"' : '' ?>>
                            <span class="messaging-contact__label"><?= e($contact['label']) ?></span>
                            <span class="messaging-contact__id"><?= e($contact['company_id']) ?></span>
                            <?php if ($contact['unread'] > 0): ?>
                                <span class="messaging-contact__badge" aria-label="<?= (int) $contact['unread'] ?> unread"><?= (int) $contact['unread'] ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($groupsAvailable): ?>
            <div class="messaging-board__section<?= $messagingShowDirect ? ' messaging-board__section--groups' : '' ?>">
                <div class="messaging-board__section-head">
                    <h3 class="messaging-board__section-title">Groups</h3>
                    <?php if ($messagingCanCreateGroups): ?>
                        <button type="button"
                                class="messaging-board__new-group"
                                id="openCreateGroup"
                                aria-expanded="false"
                                aria-controls="createGroupPanel">
                            <i class="fa-solid fa-plus" aria-hidden="true"></i>
                            New group
                        </button>
                    <?php endif; ?>
                </div>

                <?php if ($messagingCanCreateGroups && $messagingHeadGuardOptions !== []): ?>
                <div class="messaging-create-group" id="createGroupPanel" hidden>
                    <form method="POST" action="<?= e($messagingCreateGroupUrl) ?>" class="messaging-create-group__form">
                        <?= csrf_field() ?>
                        <label class="messaging-create-group__label" for="groupNameInput">Group name</label>
                        <input type="text"
                               name="group_name"
                               id="groupNameInput"
                               class="messaging-create-group__input"
                               maxlength="120"
                               required
                               placeholder="e.g. SM Megamall supervisors">
                        <span class="messaging-create-group__label">Head guards</span>
                        <div class="messaging-create-group__members" role="group" aria-label="Select head guards">
                            <?php foreach ($messagingHeadGuardOptions as $option): ?>
                            <label class="messaging-member-option">
                                <input type="checkbox" name="member_ids[]" value="<?= e($option['company_id']) ?>">
                                <span><?= e($option['label']) ?></span>
                                <span class="messaging-member-option__id"><?= e($option['company_id']) ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <button type="submit" class="messaging-create-group__submit">Create group</button>
                    </form>
                </div>
                <?php elseif ($messagingCanCreateGroups): ?>
                <p class="messaging-board__empty">Link head guards to user accounts in callout settings before creating groups.</p>
                <?php endif; ?>

                <?php if ($messagingGroups === []): ?>
                    <p class="messaging-board__empty">No group chats yet.</p>
                <?php else: ?>
                <ul class="messaging-contact-list">
                    <?php foreach ($messagingGroups as $group): ?>
                        <?php
                        $isActive = $messagingMode === 'group' && $messagingActiveGroupId === $group['group_id'];
                        $groupUrl = $messagingReturnUrl . (str_contains($messagingReturnUrl, '?') ? '&' : '?')
                            . 'group=' . (int) $group['group_id'] . '#messaging-board';
                        ?>
                    <li>
                        <a href="<?= e($groupUrl) ?>"
                           class="messaging-contact messaging-contact--group<?= $isActive ? ' is-active' : '' ?>"
                           <?= $isActive ? 'aria-current="true"' : '' ?>>
                            <span class="messaging-contact__label">
                                <i class="fa-solid fa-users" aria-hidden="true"></i>
                                <?= e($group['group_name']) ?>
                            </span>
                            <span class="messaging-contact__id"><?= (int) $group['member_count'] ?> members</span>
                            <?php if ($group['unread'] > 0): ?>
                                <span class="messaging-contact__badge" aria-label="<?= (int) $group['unread'] ?> unread"><?= (int) $group['unread'] ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="messaging-board__thread">
            <?php if ($messagingMode === 'group' && $messagingActiveGroupId !== null && $messagingGroupMeta !== null): ?>
                <div class="messaging-thread__header">
                    <strong><?= e($messagingGroupMeta['group_name']) ?></strong>
                    <span class="messaging-thread__meta">
                        <?= count($messagingGroupMeta['members']) ?> members —
                        <?= e(implode(', ', array_map(static fn ($m) => $m['label'], $messagingGroupMeta['members']))) ?>
                    </span>
                </div>
                <div class="messaging-thread__messages" id="messagingThreadScroll" tabindex="0" aria-live="polite">
                    <?php if ($messagingGroupThread === []): ?>
                        <p class="messaging-board__placeholder">No messages yet. Send the first message below.</p>
                    <?php else: ?>
                        <?php foreach ($messagingGroupThread as $message): ?>
                        <div class="messaging-bubble<?= $message['is_mine'] ? ' messaging-bubble--mine' : ' messaging-bubble--theirs' ?>">
                            <?php if (!$message['is_mine']): ?>
                                <span class="messaging-bubble__sender"><?= e($message['sender_label']) ?></span>
                            <?php endif; ?>
                            <p class="messaging-bubble__text"><?= nl2br(e($message['body_text'])) ?></p>
                            <time class="messaging-bubble__time" datetime="<?= e($message['created_at']) ?>">
                                <?= e(date('M j, Y g:i A', strtotime($message['created_at']) ?: time())) ?>
                            </time>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <form method="POST" action="<?= e($messagingGroupPostUrl) ?>" class="messaging-compose">
                    <?= csrf_field() ?>
                    <input type="hidden" name="group_id" value="<?= (int) $messagingActiveGroupId ?>">
                    <label class="visually-hidden" for="messagingGroupBody">Group message</label>
                    <div class="messaging-compose__field">
                        <textarea name="body" id="messagingGroupBody" class="messaging-compose__input" rows="2" maxlength="4000" required placeholder="Message the group…"></textarea>
                        <button type="submit" class="messaging-compose__submit" aria-label="Send group message"<?= ui_tooltip('Send message') ?>>
                            <svg class="messaging-compose__submit-icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false">
                                <path fill="currentColor" d="M2.01 21 23 12 2.01 3 2 10l15 2-15 2z"/>
                            </svg>
                        </button>
                    </div>
                </form>
            <?php elseif ($messagingMode === 'direct' && $messagingActivePeer !== null && $messagingActivePeer !== ''): ?>
                <div class="messaging-thread__header">
                    <strong><?= e($messagingPeerLabel) ?></strong>
                    <span class="messaging-thread__meta"><?= e($messagingActivePeer) ?></span>
                </div>
                <div class="messaging-thread__messages" id="messagingThreadScroll" tabindex="0" aria-live="polite">
                    <?php if ($messagingThread === []): ?>
                        <p class="messaging-board__placeholder">No messages yet. Send the first message below.</p>
                    <?php else: ?>
                        <?php foreach ($messagingThread as $message): ?>
                        <div class="messaging-bubble<?= $message['is_mine'] ? ' messaging-bubble--mine' : ' messaging-bubble--theirs' ?>">
                            <p class="messaging-bubble__text"><?= nl2br(e($message['body_text'])) ?></p>
                            <time class="messaging-bubble__time" datetime="<?= e($message['created_at']) ?>">
                                <?= e(date('M j, Y g:i A', strtotime($message['created_at']) ?: time())) ?>
                            </time>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <form method="POST" action="<?= e($messagingPostUrl) ?>" class="messaging-compose">
                    <?= csrf_field() ?>
                    <input type="hidden" name="recipient_id" value="<?= e($messagingActivePeer) ?>">
                    <input type="hidden" name="return_peer" value="<?= e($messagingActivePeer) ?>">
                    <label class="visually-hidden" for="messagingBody">Message</label>
                    <div class="messaging-compose__field">
                        <textarea name="body" id="messagingBody" class="messaging-compose__input" rows="2" maxlength="4000" required placeholder="Type your message…"></textarea>
                        <button type="submit" class="messaging-compose__submit" aria-label="Send message"<?= ui_tooltip('Send message') ?>>
                            <svg class="messaging-compose__submit-icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false">
                                <path fill="currentColor" d="M2.01 21 23 12 2.01 3 2 10l15 2-15 2z"/>
                            </svg>
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <p class="messaging-board__placeholder">
                    <?= $messagingShowDirect ? 'Select a direct contact or group to view the conversation.' : 'Select a group to view the conversation.' ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</section>
<?php if ($messagingCanCreateGroups): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('openCreateGroup');
    var panel = document.getElementById('createGroupPanel');
    if (!btn || !panel) {
        return;
    }
    btn.addEventListener('click', function () {
        var open = panel.hidden;
        panel.hidden = !open;
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
});
</script>
<?php endif; ?>
