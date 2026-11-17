<?php

return [
    'layout' => [
        'density' => 'compact',
        'toolbar' => 'top',
        'filters' => 'collapsible',
    ],
    'datatables' => [
        'version' => 'auto',
        'assets' => 'external',
    ],
    'table' => [
        'page_length' => 25,
        'page_length_options' => [10, 25, 50, 100],
        'server_side' => true,
        'search_delay' => 350,
        'max_page_length' => 100,
    ],
];
