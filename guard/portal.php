<?php
require_once __DIR__ . '/php/bootstrap.php';

auth_require_permission('guard.portal.access');

guard_render_app_page(
    $conn,
    [
        'title' => 'Guard Portal',
        'activeNav' => 'portal',
        'headerPrimaryTabActive' => true,
        'headerSecondaryHref' => guard_url('inbox.php'),
        'headerSecondaryLabel' => 'Emergency Codes',
        'headerSecondaryActive' => false,
        'locationOpensEstablishmentPicker' => false,
        'showAvatar' => true,
        'showGreeting' => true,
        'showSearch' => false,
        'showTabs' => false,
        'searchInputId' => 'guardPortalSearch',
        'searchPlaceholder' => 'Search...',
        'primaryTabLabel' => 'Around Me',
    ]
);
