<?php

namespace Thuraaung\TableKit\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Thuraaung\TableKit\TableKitServiceProvider;
use Thuraaung\TableKit\Tests\TestCase;

class DeveloperExperienceTest extends TestCase
{
    public function test_generator_produces_working_php_and_protects_existing_files(): void
    {
        $path = app_path('DataTables/Admin/GeneratedTable.php');
        @unlink($path);
        try {
            $this->artisan('tablekit:make', [
                'name' => 'Admin/GeneratedTable',
                '--model' => 'Thuraaung\\TableKit\\Tests\\Client',
            ])->assertExitCode(0);
            $source = file_get_contents($path);
            $this->assertStringContainsString('namespace App\\DataTables\\Admin;', $source);
            require_once $path;
            Route::get('/generated', 'App\\DataTables\\Admin\\GeneratedTable');
            $this->getJson('/generated')->assertOk()->assertJsonPath('recordsTotal', 3);
            $this->artisan('tablekit:make', ['name' => 'Admin/GeneratedTable'])->assertExitCode(1);
            $this->assertSame($source, file_get_contents($path));
            $this->artisan('tablekit:make', ['name' => 'Admin/GeneratedTable', '--force' => true])->assertExitCode(0);
        } finally {
            @unlink($path);
        }
    }

    public function test_generator_rejects_path_traversal(): void
    {
        $this->artisan('tablekit:make', ['name' => '../Escape'])->assertExitCode(1);
        $this->artisan('tablekit:make', ['name' => 'class'])->assertExitCode(1);
    }

    public function test_publishing_config_views_and_assets(): void
    {
        foreach (['tablekit-config', 'tablekit-views', 'tablekit-assets'] as $tag) {
            $paths = TableKitServiceProvider::pathsToPublish(TableKitServiceProvider::class, $tag);
            $this->assertNotEmpty($paths);
            foreach ($paths as $source => $target) {
                $this->assertFileExists($source);
            }
            $this->artisan('vendor:publish', ['--tag' => $tag, '--force' => true])->assertExitCode(0);
            foreach ($paths as $source => $target) {
                $this->assertFileExists($target);
                $files = new \Illuminate\Filesystem\Filesystem();
                is_dir($target) ? $files->deleteDirectory($target) : $files->delete($target);
            }
        }
    }

    public function test_config_cache_and_invokable_route_serialization(): void
    {
        $config = $this->app['config']->all();
        $cached = eval('return '.var_export($config, true).';');
        $this->assertSame('compact', $cached['tablekit']['layout']['density']);
        $route = Route::get('/cacheable', BasicTable::class)->name('cacheable');
        $route = clone $route;
        $route->prepareForSerialization();
        $restored = unserialize(serialize($route));
        $this->assertStringContainsString(BasicTable::class, $restored->getActionName());
        $this->getJson('/cacheable')->assertOk();
    }
}
