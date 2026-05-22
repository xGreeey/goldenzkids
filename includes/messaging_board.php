<?php
declare(strict_types=1);

if (!function_exists('message_groups_table_exists')) {
    require_once __DIR__ . '/group_messaging.php';
}
require_once __DIR__ . '/messaging_labels.php';
require_once __DIR__ . '/messaging_unread.php';
require_once __DIR__ . '/messaging_board_ui.php';

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
 * @var bool $messagingShowCreatePanel
 * @var string $messagingThreadApi
 * @var string $messagingActionUrl
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
$messagingShowDirect = $messagingShowDirect ?? internal_messaging_can_use_direct(auth_user_role());
$groupsAvailable = $groupsAvailable ?? (isset($conn) && $conn instanceof PDO && message_groups_table_exists($conn));
$messagingShowCreatePanel = $messagingShowCreatePanel ?? false;
$messagingThreadApi = $messagingThreadApi ?? 'messaging-thread.php';
$messagingPollApi = $messagingPollApi ?? 'messaging-poll.php';
$messagingActionUrl = $messagingActionUrl ?? 'messaging-action.php';

$messagingPeerLabel = '';
if ($messagingActivePeer !== null && $messagingActivePeer !== '') {
    foreach ($messagingContacts as $contact) {
        if ($contact['company_id'] === $messagingActivePeer) {
            $messagingPeerLabel = $contact['label'];
            break;
        }
    }
    if ($messagingPeerLabel === '') {
        $messagingPeerLabel = isset($conn) && $conn instanceof PDO
            ? messaging_resolve_user_label($conn, $messagingActivePeer)
            : $messagingActivePeer;
    }
}

$boardSubtitle = match (auth_normalize_role(auth_user_role())) {
    AUTH_ROLE_ADMIN => 'Direct messages to head guards, plus group chats.',
    AUTH_ROLE_GUARD => 'Direct messages with administrators, plus group chats.',
    default => 'Direct messages with administrators, plus group chats with head guards.',
};

$messagingSidebarTitle = $messagingSidebarTitle ?? 'Conversations';
$messagingSidebarSubtitle = $messagingSidebarSubtitle ?? 'Direct and group conversations with head guards.';
$messagingHideSidebarHead = $messagingHideSidebarHead ?? false;

$canRenderCreateForm = $messagingCanCreateGroups && $messagingHeadGuardOptions !== [];
$hasActiveThread = ($messagingMode === 'group' && $messagingGroupMeta !== null)
    || ($messagingMode === 'direct' && $messagingActivePeer !== null && $messagingActivePeer !== '');

[$messagingContacts, $messagingGroups, $messagingUnreadTotal] = messaging_apply_open_thread_unread(
    $messagingContacts,
    $messagingGroups,
    $messagingMode === 'direct' ? $messagingActivePeer : null,
    $messagingMode === 'group' ? $messagingActiveGroupId : null
);
?>
<section class="messaging-board messaging-board--split"
         id="messaging-board"
         aria-labelledby="messaging-board-heading"
         data-unread-total="<?= (int) $messagingUnreadTotal ?>"
         data-thread-api="<?= e($messagingThreadApi) ?>"
         data-poll-api="<?= e($messagingPollApi) ?>"
         data-poll-ms="1000"
         data-send-direct="<?= e($messagingPostUrl) ?>"
         data-send-group="<?= e($messagingGroupPostUrl) ?>"
         data-base-url="<?= e($messagingReturnUrl) ?>"
         data-csrf="<?= e(csrf_token()) ?>"
         data-initial-peer="<?= $messagingMode === 'direct' && $messagingActivePeer ? e($messagingActivePeer) : '' ?>"
         data-initial-group="<?= $messagingMode === 'group' && $messagingActiveGroupId ? (int) $messagingActiveGroupId : '' ?>"
         data-initial-create="<?= $messagingShowCreatePanel ? '1' : '0' ?>"
         data-create-group-url="<?= e($messagingCreateGroupUrl) ?>"
         data-action-url="<?= e($messagingActionUrl) ?>">
    <?php if (!$messagingAvailable && !$groupsAvailable): ?>
        <p class="messaging-board__notice" role="status">
            Messaging is not available yet. Run <code>php database/migrate.php</code> to create the messaging tables.
        </p>
    <?php else: ?>
    <div class="messaging-board__layout">
        <aside class="messaging-board__contacts" role="navigation" aria-label="Conversations">
            <?php if ($messagingHideSidebarHead): ?>
                <h2 id="messaging-board-heading" class="visually-hidden messaging-board__sidebar-title">
                    <?= e($messagingSidebarTitle) ?>
                    <?php if ($messagingUnreadTotal > 0): ?>
                        <span class="messaging-board__sidebar-badge" aria-label="<?= (int) $messagingUnreadTotal ?> unread"><?= (int) $messagingUnreadTotal ?></span>
                    <?php endif; ?>
                </h2>
            <?php else: ?>
            <div class="messaging-board__sidebar-head">
                <h2 id="messaging-board-heading" class="messaging-board__sidebar-title">
                    <?= e($messagingSidebarTitle) ?>
                    <?php if ($messagingUnreadTotal > 0): ?>
                        <span class="messaging-board__sidebar-badge" aria-label="<?= (int) $messagingUnreadTotal ?> unread"><?= (int) $messagingUnreadTotal ?></span>
                    <?php endif; ?>
                </h2>
                <p class="messaging-board__sidebar-subtitle"><?= e($messagingSidebarSubtitle) ?></p>
                <?php if ($messagingCanCreateGroups && $groupsAvailable): ?>
                    <button type="button" class="messaging-board__head-create" data-messaging-action="create-group">
                        <i class="fa-solid fa-users-rays" aria-hidden="true"></i>
                        Create group chat
                    </button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="messaging-board__unread-banner<?= $messagingUnreadTotal > 0 ? '' : ' is-hidden' ?>"
                 id="messagingUnreadBanner"
                 role="status"
                 aria-live="polite"
                 <?= $messagingUnreadTotal > 0 ? '' : ' hidden' ?>>
                <span class="messaging-board__unread-banner-icon" aria-hidden="true">
                    <i class="fa-solid fa-comment-dots"></i>
                </span>
                <span class="messaging-board__unread-banner-text" data-messaging-unread-banner-text>
                    <?php if ($messagingUnreadTotal === 1): ?>
                        You have <strong>1 new message</strong> — open a conversation below.
                    <?php else: ?>
                        You have <strong><?= (int) $messagingUnreadTotal ?> new messages</strong> — open a conversation below.
                    <?php endif; ?>
                </span>
            </div>
            <div class="messaging-board__contacts-scroll">
            <?php if ($messagingShowDirect && $messagingAvailable): ?>
            <div class="messaging-board__section">
                <h3 class="messaging-board__section-title">Direct</h3>
                <?php if ($messagingContacts === []): ?>
                    <p class="messaging-board__empty"><?= auth_normalize_role(auth_user_role()) === AUTH_ROLE_ADMIN
                        ? 'No head guard accounts are active yet.'
                        : 'No administrator accounts are active yet.' ?></p>
                <?php else: ?>
                <ul class="messaging-contact-list">
                    <?php foreach ($messagingContacts as $contact): ?>
                        <?php $isActive = $messagingMode === 'direct' && $messagingActivePeer === $contact['company_id']; ?>
                    <li>
                        <?php $contactUnread = (int) ($contact['unread'] ?? 0); ?>
                        <button type="button"
                                class="messaging-contact<?= $isActive ? ' is-active' : '' ?><?= $contactUnread > 0 ? ' has-unread' : '' ?>"
                                data-chat-type="direct"
                                data-peer-id="<?= e($contact['company_id']) ?>"
                                data-chat-label="<?= e($contact['label']) ?>"
                                data-unread="<?= $contactUnread ?>"
                                <?= $isActive ? 'aria-current="true"' : '' ?>>
                            <?= messaging_ui_avatar_html($contact['label'], $contact['company_id'], ['size' => 'sm']) ?>
                            <span class="messaging-contact__body">
                                <span class="messaging-contact__row">
                                    <span class="messaging-contact__label"><?= e($contact['label']) ?></span>
                                    <?php if ($contactUnread > 0): ?>
                                        <span class="messaging-contact__badge" aria-label="<?= $contactUnread ?> unread"><?= $contactUnread ?></span>
                                    <?php endif; ?>
                                </span>
                                <span class="messaging-contact__id"><?= e($contact['company_id']) ?></span>
                            </span>
                        </button>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($groupsAvailable): ?>
            <div class="messaging-board__section<?= $messagingShowDirect ? ' messaging-board__section--groups' : '' ?>">
                <h3 class="messaging-board__section-title">Groups</h3>

                <?php if ($messagingCanCreateGroups): ?>
                <button type="button" class="messaging-board__create-group-btn" data-messaging-action="create-group">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    Create group chat
                </button>
                <?php endif; ?>

                <?php if ($messagingGroups === []): ?>
                    <p class="messaging-board__empty">No group chats yet. Use <strong>Create group chat</strong> to start one.</p>
                <?php else: ?>
                <ul class="messaging-contact-list">
                    <?php foreach ($messagingGroups as $group): ?>
                        <?php $isActive = $messagingMode === 'group' && $messagingActiveGroupId === $group['group_id']; ?>
                    <li>
                        <?php $groupUnread = (int) ($group['unread'] ?? 0); ?>
                        <button type="button"
                                class="messaging-contact messaging-contact--group<?= $isActive ? ' is-active' : '' ?><?= $groupUnread > 0 ? ' has-unread' : '' ?>"
                                data-chat-type="group"
                                data-group-id="<?= (int) $group['group_id'] ?>"
                                data-chat-label="<?= e($group['group_name']) ?>"
                                data-unread="<?= $groupUnread ?>"
                                <?= $isActive ? 'aria-current="true"' : '' ?>>
                            <?= messaging_ui_avatar_html($group['group_name'], 'group-' . $group['group_id'], ['size' => 'sm', 'class' => 'messaging-avatar--group']) ?>
                            <span class="messaging-contact__body">
                                <span class="messaging-contact__row">
                                    <span class="messaging-contact__label"><?= e($group['group_name']) ?></span>
                                    <?php if ($groupUnread > 0): ?>
                                        <span class="messaging-contact__badge" aria-label="<?= $groupUnread ?> unread"><?= $groupUnread ?></span>
                                    <?php endif; ?>
                                </span>
                                <span class="messaging-contact__id"><?= (int) $group['member_count'] ?> members</span>
                            </span>
                        </button>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            </div>
        </aside>

        <div class="messaging-board__thread"
             id="messagingThreadPane"
             data-thread-mode="<?= e($messagingShowCreatePanel ? 'create' : ($hasActiveThread ? $messagingMode : 'idle')) ?>">
            <?php if ($messagingShowCreatePanel && $messagingCanCreateGroups): ?>
                <?php require __DIR__ . '/messaging_board_create_panel.php'; ?>
            <?php elseif ($messagingMode === 'group' && $messagingActiveGroupId !== null && $messagingGroupMeta !== null): ?>
                <?php messaging_ui_thread_header_markup(
                    $messagingGroupMeta['group_name'],
                    count($messagingGroupMeta['members']) . ' members',
                    'group-' . $messagingActiveGroupId,
                    true
                ); ?>
                <div class="messaging-thread__messages" id="messagingThreadScroll" tabindex="0" aria-live="off">
                    <?php if ($messagingGroupThread === []): ?>
                        <p class="messaging-board__placeholder">No messages yet. Send the first message below.</p>
                    <?php else: ?>
                        <?php foreach ($messagingGroupThread as $message): ?>
                        <div class="messaging-bubble<?= $message['is_mine'] ? ' messaging-bubble--mine' : ' messaging-bubble--theirs' ?>" data-message-id="<?= (int) $message['message_id'] ?>">
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
                <?php messaging_ui_compose_markup($messagingGroupPostUrl, 'group', (string) $messagingActiveGroupId); ?>
            <?php elseif ($messagingMode === 'direct' && $messagingActivePeer !== null && $messagingActivePeer !== ''): ?>
                <?php messaging_ui_thread_header_markup($messagingPeerLabel, $messagingActivePeer, $messagingActivePeer); ?>
                <div class="messaging-thread__messages" id="messagingThreadScroll" tabindex="0" aria-live="off">
                    <?php if ($messagingThread === []): ?>
                        <p class="messaging-board__placeholder">No messages yet. Send the first message below.</p>
                    <?php else: ?>
                        <?php foreach ($messagingThread as $message): ?>
                        <div class="messaging-bubble<?= $message['is_mine'] ? ' messaging-bubble--mine' : ' messaging-bubble--theirs' ?>" data-message-id="<?= (int) $message['message_id'] ?>">
                            <p class="messaging-bubble__text"><?= nl2br(e($message['body_text'])) ?></p>
                            <time class="messaging-bubble__time" datetime="<?= e($message['created_at']) ?>">
                                <?= e(date('M j, Y g:i A', strtotime($message['created_at']) ?: time())) ?>
                            </time>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php messaging_ui_compose_markup($messagingPostUrl, 'direct', $messagingActivePeer, $messagingActivePeer); ?>
            <?php else: ?>
                <div class="messaging-board__idle">
                    <?php if ($messagingShowDirect): ?>
                        <p class="messaging-board__placeholder">Select a direct contact or a group from the list to open a conversation.</p>
                    <?php else: ?>
                        <p class="messaging-board__placeholder">Select a group from the list to open the conversation.</p>
                    <?php endif; ?>
                    <?php if ($messagingCanCreateGroups && $groupsAvailable): ?>
                        <button type="button" class="messaging-board__idle-create" data-messaging-action="create-group">
                            <i class="fa-solid fa-users-rays" aria-hidden="true"></i>
                            Create group chat
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($messagingCanCreateGroups): ?>
    <template id="messagingCreateGroupTemplate">
        <?php require __DIR__ . '/messaging_board_create_panel.php'; ?>
    </template>
    <?php endif; ?>

    <template id="messagingIdleTemplate">
        <div class="messaging-board__idle">
            <?php if ($messagingShowDirect): ?>
                <p class="messaging-board__placeholder">Select a direct contact or a group from the list to open a conversation.</p>
            <?php else: ?>
                <p class="messaging-board__placeholder">Select a group from the list to open the conversation.</p>
            <?php endif; ?>
            <?php if ($messagingCanCreateGroups && $groupsAvailable): ?>
                <button type="button" class="messaging-board__idle-create" data-messaging-action="create-group">
                    <i class="fa-solid fa-users-rays" aria-hidden="true"></i>
                    Create group chat
                </button>
            <?php endif; ?>
        </div>
    </template>
    <?php endif; ?>
</section>
