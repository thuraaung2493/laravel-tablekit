<?php

return [
    'defaults' => ['guard' => 'web'],
    'guards' => ['web' => ['driver' => 'session', 'provider' => 'users']],
    'providers' => ['users' => ['driver' => 'eloquent', 'model' => TableKitDemo\Models\User::class]],
];
