<?php

namespace TableKitDemo\Providers;

use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends \Illuminate\Foundation\Support\Providers\RouteServiceProvider
{
    public function map(): void
    {
        Route::middleware('web')->group(base_path('routes/web.php'));
    }

    public function boot()
    {
        parent::boot();
        // Laravel 8+ uses routes(); Laravel 7 calls map() itself.
        if (method_exists($this, 'routes')) {
            $this->routes(function () { $this->map(); });
        }
    }
}
