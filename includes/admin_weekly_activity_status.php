<?php
declare(strict_types=1);

/** Legacy guard workspace status — excluded from admin weekly summary registry. */
const ADMIN_WEEKLY_ACTIVITY_STATUS_DRAFT = 'draft';

const ADMIN_WEEKLY_ACTIVITY_STATUS_SUBMITTED = 'submitted';
const ADMIN_WEEKLY_ACTIVITY_STATUS_PENDING = 'pending_review';
const ADMIN_WEEKLY_ACTIVITY_STATUS_APPROVED = 'approved';
const ADMIN_WEEKLY_ACTIVITY_STATUS_RETURNED = 'returned';

/**
 * @return array<string, array{label: string, tab: string, kpi: string, description: string}>
 */
function admin_weekly_activity_status_definitions(): array
{
    return [
        ADMIN_WEEKLY_ACTIVITY_STATUS_SUBMITTED => [
            'label' => 'Submitted',
            'tab' => 'Submitted',
            'kpi' => 'Submitted',
            'description' => 'Received from the field — queued for operations review.',
        ],
        ADMIN_WEEKLY_ACTIVITY_STATUS_PENDING => [
            'label' => 'Pending review',
            'tab' => 'Pending',
            'kpi' => 'Pending',
            'description' => 'Under admin review — accomplishments and post coverage checked.',
        ],
        ADMIN_WEEKLY_ACTIVITY_STATUS_APPROVED => [
            'label' => 'Approved',
            'tab' => 'Approved',
            'kpi' => 'Approved',
            'description' => 'Accepted — filed for weekly roll-up and client reporting.',
        ],
        ADMIN_WEEKLY_ACTIVITY_STATUS_RETURNED => [
            'label' => 'Returned',
            'tab' => 'Returned',
            'kpi' => 'Returned',
            'description' => 'Sent back to head guard — corrections or missing items required.',
        ],
    ];
}

/** @return list<string> */
function admin_weekly_activity_status_slugs(): array
{
    return array_keys(admin_weekly_activity_status_definitions());
}

/** @return array<string, string> */
function admin_weekly_activity_status_options(): array
{
    $options = [];
    foreach (admin_weekly_activity_status_definitions() as $slug => $def) {
        $options[$slug] = $def['label'];
    }

    return $options;
}

function admin_weekly_activity_status_label(string $status): string
{
    $defs = admin_weekly_activity_status_definitions();

    return $defs[$status]['label'] ?? $defs[ADMIN_WEEKLY_ACTIVITY_STATUS_PENDING]['label'];
}

function admin_weekly_activity_status_is_valid(string $status): bool
{
    return array_key_exists($status, admin_weekly_activity_status_definitions());
}

/**
 * @param array<string, mixed> $report
 */
function admin_weekly_activity_status_badge_html(array $report): string
{
    $slug = (string) ($report['status'] ?? ADMIN_WEEKLY_ACTIVITY_STATUS_PENDING);
    if (!admin_weekly_activity_status_is_valid($slug)) {
        $slug = ADMIN_WEEKLY_ACTIVITY_STATUS_PENDING;
    }
    $label = (string) ($report['status_label'] ?? admin_weekly_activity_status_label($slug));
    $tip = (string) ($report['status_description'] ?? '');

    return '<span class="reports-badge reports-badge--' . e($slug) . '"'
        . ($tip !== '' ? ' title="' . e_attr($tip) . '"' : '')
        . '>' . e($label) . '</span>';
}
