<?php
declare(strict_types=1);

function messaging_ui_initials(string $label): string
{
    $label = trim($label);
    if ($label === '') {
        return '?';
    }

    if (preg_match('/^([^@]+)@/u', $label, $m)) {
        $local = preg_replace('/[^a-zA-Z0-9]/u', '', $m[1]);
        if ($local !== '') {
            return strtoupper(mb_substr($local, 0, 2));
        }
    }

    $parts = preg_split('/\s+/u', $label, 2, PREG_SPLIT_NO_EMPTY);
    if ($parts !== false && count($parts) >= 2) {
        return strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
    }

    $compact = preg_replace('/\s+/u', '', $label);

    return strtoupper(mb_substr($compact !== '' ? $compact : $label, 0, 2));
}

function messaging_ui_avatar_tone(string $seed): string
{
    $tones = ['navy', 'slate', 'blue', 'steel'];
    $idx = abs((int) crc32($seed)) % count($tones);

    return 'messaging-avatar--' . $tones[$idx];
}

/**
 * @param array{class?:string,size?:string} $options
 */
function messaging_ui_avatar_html(string $label, string $seed, array $options = []): string
{
    $size = $options['size'] ?? 'md';
    $extra = trim((string) ($options['class'] ?? ''));
    $classes = trim('messaging-avatar messaging-avatar--' . $size . ' ' . messaging_ui_avatar_tone($seed) . ' ' . $extra);

    return '<span class="' . e($classes) . '" aria-hidden="true">' . e(messaging_ui_initials($label)) . '</span>';
}

function messaging_ui_thread_header_markup(string $title, string $meta, string $seed, bool $isGroup = false): void
{
    $icon = $isGroup ? '<i class="fa-solid fa-users messaging-thread__header-icon" aria-hidden="true"></i>' : '';
    ?>
    <div class="messaging-thread__header">
        <div class="messaging-thread__header-profile">
            <?= messaging_ui_avatar_html($title, $seed, ['size' => 'lg']) ?>
            <div class="messaging-thread__header-info">
                <strong class="messaging-thread__name"><?= e($title) ?></strong>
                <span class="messaging-thread__status"><?= $icon ?><?= e($meta) ?></span>
            </div>
        </div>
    </div>
    <?php
}

function messaging_ui_compose_paperclip_svg(): string
{
    return '<svg class="messaging-compose__icon-svg messaging-compose__icon-svg--attach" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">'
        . '<path d="M21.44 11.05l-8.54 8.54a5 5 0 1 1-7.07-7.07l8.54-8.54a3.5 3.5 0 1 1 4.95 4.95l-9.19 9.19a2.5 2.5 0 1 1-3.54-3.54l8.28-8.28" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>';
}

function messaging_ui_compose_send_svg(): string
{
    return '<svg class="messaging-compose__icon-svg messaging-compose__icon-svg--send" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">'
        . '<path d="M22 2 11 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
        . '<path d="M22 2 15 22 11 13 2 9 22 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
}

function messaging_ui_compose_markup(string $formAction, string $mode, string $targetValue, ?string $returnPeer = null): void
{
    $isGroup = $mode === 'group';
    $textareaId = $isGroup ? 'messagingGroupBody' : 'messagingBody';
    $attachInputId = $isGroup ? 'messagingAttachGroup' : 'messagingAttachDirect';
    $hiddenName = $isGroup ? 'group_id' : 'recipient_id';
    $hiddenValue = $targetValue;
    ?>
    <form method="POST" action="<?= e($formAction) ?>" class="messaging-compose js-messaging-compose" data-mode="<?= e($mode) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="<?= e($hiddenName) ?>" value="<?= e($hiddenValue) ?>">
        <?php if (!$isGroup && $returnPeer !== null && $returnPeer !== ''): ?>
            <input type="hidden" name="return_peer" value="<?= e($returnPeer) ?>">
        <?php endif; ?>
        <label class="visually-hidden" for="<?= e($textareaId) ?>">Message</label>
        <div class="messaging-compose__bar">
            <textarea
                name="body"
                id="<?= e($textareaId) ?>"
                class="messaging-compose__input"
                rows="1"
                maxlength="4000"
                placeholder="Type a message…"
            ></textarea>
            <div class="messaging-compose__actions">
                <label class="messaging-compose__attach" for="<?= e($attachInputId) ?>" title="Attach photo or PDF">
                    <input
                        type="file"
                        name="attachment"
                        id="<?= e($attachInputId) ?>"
                        class="messaging-compose__file-input"
                        accept="image/jpeg,image/png,image/gif,image/webp,application/pdf"
                    >
                    <span class="messaging-compose__attach-icon" aria-hidden="true"><?= messaging_ui_compose_paperclip_svg() ?></span>
                    <span class="visually-hidden">Attach photo or PDF</span>
                </label>
                <button type="submit" class="messaging-compose__send" aria-label="Send message">
                    <span class="messaging-compose__send-label">Send</span>
                    <?= messaging_ui_compose_send_svg() ?>
                </button>
            </div>
        </div>
        <p class="messaging-compose__file-chip js-messaging-file-chip" hidden></p>
    </form>
    <?php
}
