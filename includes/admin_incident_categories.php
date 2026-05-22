<?php
declare(strict_types=1);

/** Incident at the guard's assigned duty post or within post jurisdiction. */
const ADMIN_INCIDENT_CATEGORY_PER_POST = 'per_post';
/** Incident outside assigned post — client site, public area, or off-post assignment. */
const ADMIN_INCIDENT_CATEGORY_OUTSIDE_POST = 'outside_post';

/**
 * @return array<string, array{label: string, description: string}>
 */
function admin_incident_category_definitions(): array
{
    return [
        ADMIN_INCIDENT_CATEGORY_PER_POST => [
            'label' => 'On post',
            'description' => 'Guard on assigned duty post — patrol, access control, and post SOP within jurisdiction.',
        ],
        ADMIN_INCIDENT_CATEGORY_OUTSIDE_POST => [
            'label' => 'Off post',
            'description' => 'Guard at client site, perimeter, or off-post assignment — not the guard’s regular duty post.',
        ],
    ];
}

function admin_incident_category_normalize(string $category): string
{
    $category = strtolower(trim($category));
    if (in_array($category, ['external', 'outside_post', 'outside'], true)) {
        return ADMIN_INCIDENT_CATEGORY_OUTSIDE_POST;
    }

    return ADMIN_INCIDENT_CATEGORY_PER_POST;
}

function admin_incident_category_label(string $category): string
{
    $slug = admin_incident_category_normalize($category);
    $defs = admin_incident_category_definitions();

    return $defs[$slug]['label'] ?? $defs[ADMIN_INCIDENT_CATEGORY_PER_POST]['label'];
}
