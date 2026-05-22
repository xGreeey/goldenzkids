<?php
declare(strict_types=1);

/**
 * Inline SVG icons for admin UI (sidebar, KPIs, toolbars, notifications).
 * Works without Font Awesome kit loading.
 */

function admin_ui_icon(string $name, int $size = 18, string $extraClass = ''): string
{
    $s = max(12, min(28, $size));
    $class = 'admin-ui-icon';
    if ($extraClass !== '') {
        $class .= ' ' . $extraClass;
    }

    $paths = match ($name) {
        'chart-line' => '<path d="M3 3v18h18"/><path d="M7 14l4-4 4 4 5-6"/>',
        'inbox' => '<path d="M22 12H6l-2 3H2V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v7z"/><path d="M6 12V5h12v7"/>',
        'bullhorn' => '<path d="M3 11v2a2 2 0 0 0 2 2h1l6 5V4L6 9H5a2 2 0 0 0-2 2z"/><path d="M13 8.5a4.5 4.5 0 0 1 0 7"/><path d="M16 6a7 7 0 0 1 0 12"/>',
        'file-lines' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h8M8 9h2"/>',
        'calendar-day' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="M8 14h2v2H8z"/>',
        'map-location-dot' => '<path d="M12 21s6-5.2 6-10a6 6 0 1 0-12 0c0 4.8 6 10 6 10z"/><circle cx="12" cy="11" r="2"/>',
        'clipboard-list' => '<path d="M9 5H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4M9 8h6"/>',
        'folder-open' => '<path d="M6 20H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h5l2 2h9a2 2 0 0 1 2 2v2"/><path d="M6 20h12.5a2 2 0 0 0 1.9-1.4L22 10H6z"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'circle-check' => '<circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/>',
        'ban' => '<circle cx="12" cy="12" r="9"/><path d="M5 5l14 14"/>',
        'calendar-days' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01"/>',
        'hourglass-half' => '<path d="M6 2h12v4l-4 5 4 5v4H6v-4l4-5-4-5V2z"/><path d="M6 6h12M6 18h12"/>',
        'file-signature' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 15c1-2 3-2 4 0s3 2 4 0"/>',
        'magnifying-glass' => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3-3"/>',
        'rotate-left' => '<path d="M3 12a9 9 0 1 0 3 6.7"/><path d="M3 3v6h6"/>',
        'file-export' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M12 11v6"/><path d="M9 14l3-3 3 3"/>',
        'book-open' => '<path d="M12 7c2-2 6-2 8 0v11c-2-1.5-6-1.5-8 0V7z"/><path d="M4 7c2-2 6-2 8 0v11C10 16.5 6 16.5 4 15V7z"/>',
        'database' => '<ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v6c0 1.7 3.6 3 8 3s8-1.3 8-3V5"/><path d="M4 11v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/>',
        'calendar-xmark' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="M10 14l4 4M14 14l-4 4"/>',
        'clock-rotate-left' => '<path d="M3 12a9 9 0 1 0 3 6.7"/><path d="M3 3v6h6"/><path d="M12 7v5l3 2"/>',
        'pen-to-square' => '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>',
        'floppy-disk' => '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8"/><path d="M7 3v5h8"/>',
        'bell' => '<path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>',
        'comment' => '<path d="M21 14c0 2-2 4-7 4H8l-5 3V7c0-2 2-4 7-4h6c5 0 7 2 7 4z"/>',
        'users' => '<circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2"/><path d="M3 19c0-3 3-5 6-5s6 2 6 5M14 19c0-2 2-3 4-3"/>',
        'triangle-exclamation' => '<path d="M12 3 2 20h20L12 3z"/><path d="M12 9v4M12 17h.01"/>',
        'user-shield' => '<path d="M12 3l8 3v6c0 5-3.5 9-8 9s-8-4-8-9V6l8-3z"/><path d="M9 12l2 2 4-4"/>',
        'chart-pie' => '<path d="M12 2v10l8.5 4.9A10 10 0 1 1 12 2z"/><path d="M12 2a10 10 0 0 1 8.5 4.9L12 12V2z"/>',
        'envelope-open-text' => '<path d="M22 12H6l-2 3H2V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v7z"/><path d="M6 12V5h12v7"/><path d="M10 14h4M10 17h6"/>',
        'user-pen' => '<circle cx="10" cy="8" r="4"/><path d="M4 20c0-4 2.7-6 6-6"/><path d="M15 11l4 4-6 2 2-6z"/>',
        'lock' => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 1 1 8 0v4"/>',
        'image' => '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M21 17l-5-5L5 19"/>',
        'wand-magic-sparkles' => '<path d="M15 4V2M15 8V6M17 6h2M13 6h2"/><path d="M3 20l9-9"/><path d="M18.5 5.5l1 1M20 3l1 1"/>',
        'eye' => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'file-csv' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h1M12 13h1M16 13h1M8 17h1M12 17h1M16 17h1"/>',
        default => '<circle cx="12" cy="12" r="9"/>',
    };

    return '<svg class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '" width="' . $s . '" height="' . $s
        . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" '
        . 'stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths . '</svg>';
}

/** Maps legacy Font Awesome slug names used in notification payloads. */
function admin_ui_icon_fa_alias(string $faSlug, int $size = 16): string
{
    $slug = strtolower(trim($faSlug));
    $map = [
        'comment' => 'comment',
        'comments' => 'comment',
        'users' => 'users',
        'file-lines' => 'file-lines',
        'triangle-exclamation' => 'triangle-exclamation',
        'calendar-day' => 'calendar-day',
        'bell' => 'bell',
    ];

    return admin_ui_icon($map[$slug] ?? $slug, $size);
}

function admin_kpi_icon(string $name): string
{
    return '<span class="kpi-icon" aria-hidden="true">' . admin_ui_icon($name, 20) . '</span>';
}

function admin_btn_icon(string $name): string
{
    return '<span class="reports-btn__icon" aria-hidden="true">' . admin_ui_icon($name, 14) . '</span>';
}

function admin_nav_icon(string $name): string
{
    return '<span class="sidebar-link__icon" aria-hidden="true">' . admin_ui_icon($name, 18) . '</span>';
}
