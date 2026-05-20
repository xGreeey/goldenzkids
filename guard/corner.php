<?php
require_once __DIR__ . '/php/bootstrap.php';

auth_require_permission('guard.corner.view');

guard_render_app_page(
    $conn,
    [
        'title' => 'Guard Corner',
        'activeNav' => 'corner',
        'headerPrimaryTabActive' => false,
        'headerSecondaryHref' => guard_url('corner.php'),
        'headerSecondaryLabel' => 'Emergency Codes',
        'headerSecondaryActive' => false,
        'locationOpensEstablishmentPicker' => false,
        'showAvatar' => true,
        'showGreeting' => true,
        'showSearch' => false,
        'showTabs' => false,
        'searchInputId' => 'guardCornerSearch',
        'searchPlaceholder' => 'Search...',
        'primaryTabLabel' => 'Around Me',
    ]
);