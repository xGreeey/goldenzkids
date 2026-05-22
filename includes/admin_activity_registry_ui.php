<?php
declare(strict_types=1);

require_once __DIR__ . '/admin_ui_icons.php';

/**
 * Shared modal UI for daily activity and weekly summary registries.
 *
 * @return array{title: string, description: string}
 */
function admin_activity_registry_history_section_copy(bool $isWeekly): array
{
    if ($isWeekly) {
        return [
            'title' => 'Status & activity log',
            'description' => 'Chronological trail of head guard submission and admin review actions for this weekly summary.',
        ];
    }

    return [
        'title' => 'Status & activity log',
        'description' => 'Chronological trail of field submission and admin review actions for this daily activity report.',
    ];
}

function admin_activity_registry_history_event_label(string $event): string
{
    $event = trim($event);
    if ($event === '') {
        return 'Activity logged';
    }
    if (preg_match('/^Registry:\s*(.+)$/iu', $event)) {
        return 'Review status updated';
    }

    return $event;
}

function admin_activity_registry_history_note_text(string $event, string $note): string
{
    $note = trim($note);
    if (preg_match('/^Registry:\s*(.+)$/iu', trim($event), $m)) {
        $statusLine = 'New status: ' . trim($m[1]);

        return $note !== '' ? $statusLine . ' — ' . $note : $statusLine;
    }

    return $note;
}

function admin_activity_registry_history_datetime_attr(string $at): string
{
    $at = trim($at);
    if ($at === '') {
        return '';
    }
    $ts = strtotime($at);

    return $ts !== false ? date('c', $ts) : '';
}

/**
 * @param array<string, mixed> $entry
 */
function admin_activity_registry_history_timeline_item_html(array $entry, bool $isCurrent): string
{
    $event = (string) ($entry['event'] ?? '');
    $title = admin_activity_registry_history_event_label($event);
    $noteText = admin_activity_registry_history_note_text($event, (string) ($entry['note'] ?? ''));
    $at = trim((string) ($entry['at'] ?? ''));
    $datetimeAttr = admin_activity_registry_history_datetime_attr($at);

    $item = '<li class="reports-activity-timeline__item' . ($isCurrent ? ' is-current' : '') . '">';
    $item .= '<div class="reports-activity-timeline__rail" aria-hidden="true">';
    $item .= '<span class="reports-activity-timeline__dot"></span></div>';
    $item .= '<div class="reports-activity-timeline__content">';
    if ($at !== '') {
        $item .= '<header class="reports-activity-timeline__meta">';
        $item .= '<time class="reports-activity-timeline__when"'
            . ($datetimeAttr !== '' ? ' datetime="' . htmlspecialchars($datetimeAttr, ENT_QUOTES, 'UTF-8') . '"' : '')
            . '>' . htmlspecialchars($at, ENT_QUOTES, 'UTF-8') . '</time>';
        $item .= '</header>';
    }
    $item .= '<p class="reports-activity-timeline__title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</p>';
    if ($noteText !== '') {
        $item .= '<p class="reports-activity-timeline__note">' . htmlspecialchars($noteText, ENT_QUOTES, 'UTF-8') . '</p>';
    }
    $item .= '</div></li>';

    return $item;
}

/**
 * @param list<array<string, mixed>>|mixed $history
 */
function admin_activity_registry_history_timeline_html($history): string
{
    if (!is_array($history) || $history === []) {
        return '<p class="reports-activity-timeline__empty">No activity logged for this report yet.</p>';
    }

    $items = [];
    $entries = array_values(array_filter($history, static fn ($entry): bool => is_array($entry)));
    $count = count($entries);
    foreach ($entries as $index => $entry) {
        $items[] = admin_activity_registry_history_timeline_item_html($entry, $index === $count - 1);
    }

    if ($items === []) {
        return '<p class="reports-activity-timeline__empty">No activity logged for this report yet.</p>';
    }

    return '<ol class="reports-activity-timeline" role="list">' . implode('', $items) . '</ol>';
}

/**
 * Compact status editor — lives in the modal footer (not the scrollable body).
 *
 * @param array<string, string> $statusOptions
 */
function admin_activity_registry_status_edit_form_html(
    string $postAction,
    string $idFieldName,
    ?string $recordId,
    array $statusOptions,
    ?string $selectedStatus,
    bool $visible
): string {
    $hidden = $visible ? '' : ' hidden';
    $currentLabel = '';
    if ($selectedStatus !== null && isset($statusOptions[$selectedStatus])) {
        $currentLabel = (string) $statusOptions[$selectedStatus];
    }

    $html = '<form method="POST" id="activity-edit-form" class="reports-activity-status-edit"' . $hidden . '>';
    $html .= csrf_field();
    $html .= '<input type="hidden" name="action" value="' . htmlspecialchars($postAction, ENT_QUOTES, 'UTF-8') . '">';
    $html .= '<input type="hidden" name="' . htmlspecialchars($idFieldName, ENT_QUOTES, 'UTF-8') . '" id="activity-edit-id" value="'
        . htmlspecialchars($recordId ?? '', ENT_QUOTES, 'UTF-8') . '">';
    $html .= '<div class="reports-activity-status-edit__main reports-form-field">';
    $html .= '<div class="reports-activity-status-edit__copy">';
    $html .= '<label for="activity-edit-status" class="reports-activity-status-edit__label">Review status</label>';
    if ($currentLabel !== '') {
        $html .= '<p class="reports-activity-status-edit__hint">Currently <strong>'
            . htmlspecialchars($currentLabel, ENT_QUOTES, 'UTF-8')
            . '</strong> — choose the new status.</p>';
    } else {
        $html .= '<p class="reports-activity-status-edit__hint">Choose how this report appears in the registry.</p>';
    }
    $html .= '</div>';
    $html .= '<select name="status" id="activity-edit-status" class="reports-activity-status-edit__select" required>';
    foreach ($statusOptions as $val => $label) {
        $selected = $selectedStatus !== null && $selectedStatus === (string) $val ? ' selected' : '';
        $html .= '<option value="' . htmlspecialchars((string) $val, ENT_QUOTES, 'UTF-8') . '"' . $selected . '>'
            . htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') . '</option>';
    }
    $html .= '</select></div>';
    $html .= '<div class="reports-button-set reports-activity-status-edit__actions">';
    $html .= '<button type="submit" class="reports-btn reports-btn--primary">';
    $html .= admin_btn_icon('floppy-disk') . '<span class="reports-btn__text">Save</span></button>';
    $html .= '<button type="button" class="reports-btn reports-btn--secondary" id="activity-cancel-edit">';
    $html .= '<span class="reports-btn__text">Cancel</span></button></div></form>';

    return $html;
}
