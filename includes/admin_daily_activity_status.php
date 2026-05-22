<?php
declare(strict_types=1);

const ADMIN_DAILY_ACTIVITY_STATUS_PENDING = 'pending';
const ADMIN_DAILY_ACTIVITY_STATUS_REVIEWED = 'reviewed';
const ADMIN_DAILY_ACTIVITY_STATUS_ON_HOLD = 'on_hold';
const ADMIN_DAILY_ACTIVITY_STATUS_ARCHIVED = 'archived';

/**
 * @return array<string, array{label: string, tab: string, kpi: string, description: string}>
 */
function admin_daily_activity_status_definitions(): array
{
    return [
        ADMIN_DAILY_ACTIVITY_STATUS_PENDING => [
            'label' => 'Pending review',
            'tab' => 'Pending',
            'kpi' => 'Pending',
            'description' => 'Submitted by head guard — awaiting admin review.',
        ],
        ADMIN_DAILY_ACTIVITY_STATUS_REVIEWED => [
            'label' => 'Reviewed',
            'tab' => 'Reviewed',
            'kpi' => 'Reviewed',
            'description' => 'Checked and logged — no further action required.',
        ],
        ADMIN_DAILY_ACTIVITY_STATUS_ON_HOLD => [
            'label' => 'On hold',
            'tab' => 'On hold',
            'kpi' => 'On hold',
            'description' => 'Paused — waiting for photos, clarification, or follow-up visit.',
        ],
        ADMIN_DAILY_ACTIVITY_STATUS_ARCHIVED => [
            'label' => 'Archived',
            'tab' => 'Archived',
            'kpi' => 'Archived',
            'description' => 'Closed and retained for records — week/month roll-up complete.',
        ],
    ];
}

/** @return list<string> */
function admin_daily_activity_status_slugs(): array
{
    return array_keys(admin_daily_activity_status_definitions());
}

/** @return array<string, string> */
function admin_daily_activity_status_options(): array
{
    $options = [];
    foreach (admin_daily_activity_status_definitions() as $slug => $def) {
        $options[$slug] = $def['label'];
    }

    return $options;
}

function admin_daily_activity_status_label(string $status): string
{
    $defs = admin_daily_activity_status_definitions();

    return $defs[$status]['label'] ?? $defs[ADMIN_DAILY_ACTIVITY_STATUS_PENDING]['label'];
}

function admin_daily_activity_status_is_valid(string $status): bool
{
    return array_key_exists($status, admin_daily_activity_status_definitions());
}

/**
 * @param array<string, mixed> $report
 */
function admin_daily_activity_status_badge_html(array $report): string
{
    $slug = (string) ($report['status'] ?? ADMIN_DAILY_ACTIVITY_STATUS_PENDING);
    if (!admin_daily_activity_status_is_valid($slug)) {
        $slug = ADMIN_DAILY_ACTIVITY_STATUS_PENDING;
    }
    $label = (string) ($report['status_label'] ?? admin_daily_activity_status_label($slug));
    $tip = (string) ($report['status_description'] ?? '');

    return '<span class="reports-badge reports-badge--' . e($slug) . '"'
        . ($tip !== '' ? ' title="' . e_attr($tip) . '"' : '')
        . '>' . e($label) . '</span>';
}
