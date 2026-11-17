<?php

namespace Thuraaung\TableKit\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Thuraaung\TableKit\Layout;
use Thuraaung\TableKit\Tests\TestCase;
use Thuraaung\TableKit\View\Components\Table;

class LayoutTest extends TestCase
{
    public function test_partial_layout_and_engine_precedence_are_independent(): void
    {
        config(['tablekit.layout.toolbar' => 'bottom', 'tablekit.datatables.version' => '1']);
        $table = new Table(LayoutTable::class, '/data', 'clients', null, 'both', null, '3');
        $this->assertSame([
            'density' => 'comfortable',
            'toolbar' => 'both',
            'filters' => 'collapsible',
        ], $table->layout);
        $this->assertSame('3', json_decode($table->configJson, true)['version']);
        $table = new Table(LayoutTable::class, '/data');
        $this->assertSame('bottom', $table->layout['toolbar']);
        $this->assertSame('2', json_decode($table->configJson, true)['version']);
    }

    public function test_blade_component_renders_safe_configuration_and_multiple_unique_targets(): void
    {
        $path = storage_path('framework/views/tablekit-test.blade.php');
        file_put_contents($path, '<x-tablekit::table :table="$class" url="/data" title="&lt;script&gt;" toolbar="both" />'
            .'<x-tablekit::table :table="$class" url="/data" />');
        $html = view()->file($path, ['class' => BasicTable::class])->render();
        $this->assertSame(2, substr_count($html, 'data-tk-root'));
        $this->assertSame(3, substr_count($html, 'data-tk-search'));
        $this->assertStringNotContainsString('<script>', $html);
        preg_match_all('/<table id="([^"]+)"/', $html, $matches);
        $this->assertCount(2, array_unique($matches[1]));
    }

    public function test_all_layout_combinations_render(): void
    {
        foreach (['compact', 'comfortable'] as $density) {
            foreach (['top', 'bottom', 'both', 'none'] as $toolbar) {
                foreach (['collapsible', 'inline', 'sidebar', 'none'] as $filters) {
                    $component = new Table(BasicTable::class, '/data', null, $density, $toolbar, $filters);
                    $html = $component->render()->with($component->data())->render();
                    $this->assertStringContainsString('tk-density-'.$density, $html);
                    $this->assertStringContainsString('tk-filters-'.$filters, $html);
                }
            }
        }
    }

    public function test_partial_published_config_inherits_section_defaults(): void
    {
        $this->app['config']->set('tablekit', [
            'layout' => ['density' => 'comfortable'],
            'table' => ['page_length_options' => [5], 'page_length' => 5],
        ]);
        (new \Thuraaung\TableKit\TableKitServiceProvider($this->app))->register();
        $table = new Table(BasicTable::class, '/data');
        $this->assertSame('top', $table->layout['toolbar']);
        $this->assertSame([5], $table->settings['page_length_options']);
        $this->assertSame(100, $table->settings['max_page_length']);
        $this->assertSame('auto', json_decode($table->configJson, true)['version']);
    }

    public function test_invalid_layout_fails_clearly(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Table(BasicTable::class, '/data', null, 'invalid');
    }

    public function test_legacy_engine_below_110_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Table(BasicTable::class, '/data', null, null, null, null, '1.9.4');
    }
}

class LayoutTable extends BasicTable
{
    public function layout(): Layout
    {
        return Layout::make()->density('comfortable');
    }

    public function dataTablesVersion(): string
    {
        return '2';
    }
}
