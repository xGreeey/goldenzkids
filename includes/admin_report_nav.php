<?php
declare(strict_types=1);

/**
 * Report submenu — single source for sidebar links and active-state slugs.
 *
 * @return list<array{slug: string, href: string, label: string, icon: string, tip: string, active: list<string>}>
 */
function admin_report_nav_items(): array
{
    return [
        [
            'slug' => 'weekly-activity',
            'href' => 'weekly-activity.php',
            'label' => 'Weekly Activity Report',
            'icon' => 'clipboard-list',
            'tip' => 'Weekly Activity Report — review head guard weekly summaries',
            'active' => ['weekly-activity', 'weekly-accomplishment'],
        ],
        [
            'slug' => 'daily-activity',
            'href' => 'daily-activity.php',
            'label' => 'Daily Activity Report',
            'icon' => 'clock',
            'tip' => 'Daily Activity Report — shift logs and field activity for the current day',
            'active' => ['daily-activity'],
        ],
        [
            'slug' => 'dtr',
            'href' => 'dtr.php',
            'label' => 'Daily Time Record',
            'icon' => 'calendar-day',
            'tip' => 'Daily Time Record (DTR) — time-in/out, NTE, missing values',
            'active' => ['dtr', 'dtr-registry', 'dad', 'duty'],
        ],
        [
            'slug' => 'reports',
            'href' => 'reports.php',
            'label' => 'Incident report',
            'icon' => 'file-lines',
            'tip' => 'Incident reports — monitor and archive',
            'active' => ['reports'],
        ],
    ];
}

/** @return list<string> */
function admin_report_nav_open_slugs(): array
{
    $slugs = [];
    foreach (admin_report_nav_items() as $item) {
        foreach ($item['active'] as $active) {
            $slugs[] = $active;
        }
    }

    return array_values(array_unique($slugs));
}

function admin_report_nav_is_open(string $adminNavActive): bool
{
    return in_array($adminNavActive, admin_report_nav_open_slugs(), true);
}

/**
 * @return array{slug: string, href: string, label: string, icon: string, tip: string, active: list<string>}|null
 */
function admin_report_nav_item_by_slug(string $slug): ?array
{
    foreach (admin_report_nav_items() as $item) {
        if ($item['slug'] === $slug || in_array($slug, $item['active'], true)) {
            return $item;
        }
    }

    return null;
}
