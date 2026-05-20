<?php
require_once __DIR__ . '/php/bootstrap.php';

auth_require_permission('guard.inbox.view');

guard_render_app_page(
    $conn,
    [
        'title' => 'Inbox',
        'activeNav' => 'inbox',
        'headerPrimaryTabActive' => false,
        'headerSecondaryHref' => guard_url('inbox.php'),
        'headerSecondaryLabel' => 'Emergency Codes',
        'headerSecondaryActive' => true,
        'locationOpensEstablishmentPicker' => false,
        'showAvatar' => true,
        'showGreeting' => true,
        'showSearch' => false,
        'showTabs' => false,
        'searchInputId' => 'guardInboxSearch',
        'searchPlaceholder' => 'Search...',
        'primaryTabLabel' => 'Around Me',
    ]
);
