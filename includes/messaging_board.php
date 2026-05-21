<?php
declare(strict_types=1);

/**
 * @var list<array{company_id:string,label:string,unread:int}> $messagingContacts
 * @var string $messagingViewerId
 * @var string|null $messagingActivePeer
 * @var list<array{message_id:int,sender_company_id:string,body_text:string,is_mine:bool,created_at:string}> $messagingThread
 * @var string $messagingPostUrl
 * @var string $messagingReturnUrl
 * @var bool $messagingAvailable
 */
$messagingContacts = $messagingContacts ?? [];
$messagingViewerId = $messagingViewerId ?? '';
$messagingActivePeer = $messagingActivePeer ?? null;
$messagingThread = $messagingThread ?? [];
$messagingPostUrl = $messagingPostUrl ?? '';
$messagingReturnUrl = $messagingReturnUrl ?? '';
$messagingAvailable = $messagingAvailable ?? false;
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
?>
<section class="messaging-board" id="messaging-board" aria-labelledby="messaging-board-heading">
    <div class="messaging-board__head">
        <h2 id="messaging-board-heading" class="messaging-board__title">
            <i class="fa-solid fa-comments" aria-hidden="true"></i>
            Staff messaging board
        </h2>
        <p class="messaging-board__subtitle">Chat between administrators and super administrators. Select a contact to open the thread.</p>
    </div>
    <?php if (!$messagingAvailable): ?>
        <p class="messaging-board__notice" role="status">
            Messaging is not available yet. Run <code>php database/migrate.php</code> to create the messaging table.
        </p>
    <?php else: ?>
    <div class="messaging-board__layout">
        <div class="messaging-board__contacts" role="navigation" aria-label="Message contacts">
            <?php if ($messagingContacts === []): ?>
                <p class="messaging-board__empty">No <?= auth_normalize_role(auth_user_role()) === AUTH_ROLE_ADMIN ? 'super administrator' : 'administrator' ?> accounts are active yet.</p>
            <?php else: ?>
                <ul class="messaging-contact-list">
                    <?php foreach ($messagingContacts as $contact): ?>
                        <?php
                        $isActive = $messagingActivePeer === $contact['company_id'];
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

        <div class="messaging-board__thread">
            <?php if ($messagingActivePeer === null || $messagingActivePeer === ''): ?>
                <p class="messaging-board__placeholder">Select a contact to view the conversation.</p>
            <?php else: ?>
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
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</section>
