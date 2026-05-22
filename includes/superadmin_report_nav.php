<?php
declare(strict_types=1);

require_once __DIR__ . '/admin_report_nav.php';

/**
 * Superadmin report submenu — same labels as admin, recovery-focused pages.
 *
 * @return list<array{slug: string, href: string, label: string, menu_label: string, icon: string, tip: string, active: list<string>}>
 */
function superadmin_report_nav_items(): array
{
    $items = [];
    foreach (admin_report_nav_items() as $item) {
        $slug = (string) $item['slug'];
        $recoverySlug = $slug === 'reports' ? 'reports' : $slug;
        $items[] = [
            'slug' => $recoverySlug,
            'href' => $slug === 'reports' ? 'reports.php' : $slug . '.php',
            'label' => (string) $item['label'],
            'menu_label' => (string) ($item['menu_label'] ?? $item['label']),
            'icon' => (string) ($item['icon'] ?? 'file-lines'),
            'tip' => 'Deleted & archived only — ' . (string) ($item['label'] ?? ''),
            'active' => $item['active'],
        ];
    }

    return $items;
}

/** @return list<string> */
function superadmin_report_nav_open_slugs(): array
{
    $slugs = [];
    foreach (superadmin_report_nav_items() as $item) {
        foreach ($item['active'] as $active) {
            $slugs[] = $active;
        }
    }

    return array_values(array_unique($slugs));
}

function superadmin_report_nav_is_open(string $navActive): bool
{
    return in_array($navActive, superadmin_report_nav_open_slugs(), true);
}

/** Map sidebar slug → recovery store kind. */
function superadmin_report_recovery_kind(string $navActive): string
{
    return match ($navActive) {
        'weekly-activity', 'weekly-accomplishment' => 'weekly-activity',
        'daily-activity' => 'daily-activity',
        'dtr', 'dtr-registry', 'dad', 'duty' => 'dtr',
        'reports' => 'incident',
        default => 'incident',
    };
}
