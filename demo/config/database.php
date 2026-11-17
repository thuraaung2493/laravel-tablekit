<?php

return [
    'default' => 'sqlite',
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => database_path('demo.sqlite'),
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
    ],
];
