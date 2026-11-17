<?php

namespace TableKitDemo\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use TableKitDemo\Models\Client;
use TableKitDemo\Policies\ClientPolicy;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Client::class, ClientPolicy::class);
    }
}
