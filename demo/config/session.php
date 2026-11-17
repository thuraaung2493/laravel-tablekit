<?php

return [
    'driver' => 'file',
    'lifetime' => 120,
    'expire_on_close' => false,
    'encrypt' => false,
    'lottery' => [2, 100],
    'files' => storage_path('framework/sessions'),
    'cookie' => 'tablekit_demo_session',
    'path' => '/',
    'domain' => null,
    'secure' => false,
    'http_only' => true,
    'same_site' => 'lax',
];
