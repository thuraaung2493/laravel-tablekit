<?php

namespace Thuraaung\TableKit;

use Illuminate\Support\ServiceProvider;

class TableKitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $path = __DIR__.'/../config/tablekit.php';
        $this->mergeConfigFrom($path, 'tablekit');
        foreach (require $path as $section => $defaults) {
            $this->app['config']->set('tablekit.'.$section, array_replace(
                $defaults,
                $this->app['config']->get('tablekit.'.$section, [])
            ));
        }
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([Console\MakeDataTableCommand::class]);
        }
        $this->callAfterResolving('blade.compiler', function ($blade) {
            $blade->component(View\Components\Table::class, 'tablekit::table');
        });
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'tablekit');
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/tablekit'),
        ], 'tablekit-views');
        $this->publishes([
            __DIR__.'/../config/tablekit.php' => config_path('tablekit.php'),
        ], 'tablekit-config');
        $this->publishes([
            __DIR__.'/../resources/js' => public_path('vendor/tablekit/js'),
            __DIR__.'/../resources/css' => public_path('vendor/tablekit/css'),
        ], 'tablekit-assets');
    }
}
