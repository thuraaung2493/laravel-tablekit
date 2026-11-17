<?php

namespace Thuraaung\TableKit\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Thuraaung\TableKit\Actions\Action;
use Thuraaung\TableKit\Actions\BulkAction;
use Thuraaung\TableKit\Actions\RowAction;
use Thuraaung\TableKit\Columns\Column;
use Thuraaung\TableKit\Filters\Filter;
use Thuraaung\TableKit\Support\PageSelection;
use Thuraaung\TableKit\Tests\Client;
use Thuraaung\TableKit\Tests\TestCase;
use Thuraaung\TableKit\View\Components\Table;

class FiltersActionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Route::get('/filtered', FilteredTable::class);
        Route::get('/clients/{client}', function () { return 'View'; })->name('clients.show');
        Route::delete('/clients/{client}', function () { return 'Delete'; })->name('clients.destroy');
        Gate::define('change', function ($user, $client) { return (int) $client->tenant_id === 1; });
    }

    private function data(array $input = [])
    {
        return $this->getJson('/filtered?'.http_build_query($input));
    }

    public function test_filters_search_sort_and_paging_compose(): void
    {
        $this->data([
            'filters' => ['status' => 'active', 'name' => 'Ali'],
            'search' => ['value' => 'Alice'],
            'order' => [['column' => 0, 'dir' => 'desc']],
            'length' => 1,
        ])->assertOk()->assertJsonPath('recordsTotal', 2)
            ->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.name', 'Alice');
        $this->data(['filters' => ['status' => '', 'name' => '']])
            ->assertOk()->assertJsonPath('recordsFiltered', 2);
    }

    public function test_select_defaults_can_be_explicitly_cleared(): void
    {
        Route::get('/default', DefaultFilterTable::class);
        $this->getJson('/default')->assertOk()->assertJsonPath('recordsFiltered', 1);
        $this->getJson('/default?filters[status]=')->assertOk()->assertJsonPath('recordsFiltered', 2);
    }

    public function test_date_range_is_inclusive_in_input_timezone_with_exclusive_next_day(): void
    {
        Client::where('id', 1)->update(['created_at' => '2026-11-15 17:00:00']);
        Client::where('id', 2)->update(['created_at' => '2026-11-16 17:00:00']);
        $this->data(['filters' => ['created_at' => ['from' => '2026-11-16', 'to' => '2026-11-16']]])
            ->assertOk()->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.name', 'Alice');
        $this->data(['filters' => ['day' => '2026-11-16']])
            ->assertOk()->assertJsonPath('recordsFiltered', 1);
    }

    public function test_callback_ors_remain_inside_tenant_scope(): void
    {
        $this->data(['filters' => ['complex' => 'Alice']])->assertOk()
            ->assertJsonPath('recordsFiltered', 1)->assertJsonPath('recordsTotal', 2);
    }

    public function test_unknown_invalid_select_and_dates_are_rejected(): void
    {
        foreach ([
            ['secret' => 'x'],
            ['status' => 'unknown'],
            ['status' => ['active']],
            ['created_at' => ['from' => '2026-02-30']],
            ['created_at' => ['from' => '2026-11-17', 'to' => '2026-11-16']],
            ['created_at' => ['sql' => 'anything']],
        ] as $filters) {
            $this->data(['filters' => $filters])->assertStatus(422);
        }
    }

    public function test_row_actions_escape_labels_and_omit_forbidden_targets(): void
    {
        $row = Client::findOrFail(1);
        $this->assertStringContainsString('/clients/1', RowAction::make('view')->route('clients.show')->render($row));
        $html = RowAction::make('delete')->route('clients.destroy')->method('DELETE')
            ->label('<script>')->confirm()->render($row);
        $this->assertStringContainsString('name="_token"', $html);
        $this->assertStringContainsString('name="_method" value="DELETE"', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertSame('', RowAction::make('view')->url('/private')
            ->authorized(function () { return false; })->render($row));
        $this->assertSame('', Action::make('create')->url('/create')
            ->visible(function () { return false; })->render());
        $this->assertStringContainsString('disabled', RowAction::make('delete')->confirm()->render($row));
    }

    public function test_row_gates_receive_explicit_policy_dependencies_and_private_fields_stay_out_of_json(): void
    {
        $user = new \Illuminate\Foundation\Auth\User();
        $user->id = 42;
        $this->actingAs($user);
        Gate::define('inspect', function ($user, $row) {
            return (int) $row->tenant_id === 1 && (int) $row->id === 1;
        });
        Route::get('/policy-fields', PolicyFieldTable::class);
        $response = $this->getJson('/policy-fields')->assertOk();
        $this->assertStringContainsString('/clients/1', $response->json('data.0._tk_actions'));
        $this->assertStringNotContainsString('/clients/2', $response->json('data.1._tk_actions'));
        $this->assertArrayNotHasKey('tenant_id', $response->json('data.0'));
    }

    public function test_action_urls_cannot_execute_javascript(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Action::make('view')->url('javascript:alert(1)')->render();
    }

    public function test_selection_protocol_and_synthetic_column_mapping(): void
    {
        Route::get('/selected', SelectedTable::class);
        $response = $this->getJson('/selected?order[0][column]=1&order[0][dir]=desc')
            ->assertOk()->assertJsonPath('data.0.name', 'Bob');
        $this->assertNotEmpty($response->json('pageToken'));
        $this->assertStringContainsString('data-tk-select', $response->json('data.0._tk_select'));
        $this->assertStringContainsString('tk-row-actions', $response->json('data.0._tk_actions'));
        $this->getJson('/selected?order[0][column]=0&order[0][dir]=asc')->assertStatus(422);
        $component = new Table(SelectedTable::class, '/selected');
        $html = $component->render()->with($component->data())->render();
        $this->assertStringContainsString('data-tk-select-page', $html);
        $this->assertStringContainsString('data-tk-bulk', $html);
    }

    public function test_bulk_helper_rechecks_scope_permissions_and_signed_page_membership(): void
    {
        $user = new \Illuminate\Foundation\Auth\User();
        $user->id = 42;
        $this->actingAs($user);
        $table = new SelectedTable();
        Route::get('/selected', SelectedTable::class);
        $token = $this->getJson('/selected')->assertOk()->json('pageToken');
        Route::post('/bulk', function (Request $request) {
            return BulkAction::authorizedModels(new SelectedTable(), $request, 'change')->modelKeys();
        });
        $this->postJson('/bulk', ['ids' => ['1'], 'page_token' => $token])->assertOk()->assertExactJson([1]);
        $this->postJson('/bulk', ['ids' => ['3'], 'page_token' => $token])->assertStatus(422);
        $this->postJson('/bulk', ['ids' => ['1', '1'], 'page_token' => $token])->assertStatus(422);
        $this->postJson('/bulk', ['ids' => ['1'], 'page_token' => $token.'tampered'])->assertStatus(422);
        Gate::define('change', function ($user, $client) { return (int) $client->id === 1; });
        $this->postJson('/bulk', ['ids' => ['1', '2'], 'page_token' => $token])->assertForbidden();
        $other = new \Illuminate\Foundation\Auth\User();
        $other->id = 43;
        $this->actingAs($other);
        $this->postJson('/bulk', ['ids' => ['1'], 'page_token' => $token])->assertStatus(422);
        $this->actingAs($user);
        Client::where('id', 1)->update(['tenant_id' => 2]);
        $this->postJson('/bulk', ['ids' => ['1'], 'page_token' => $token])->assertForbidden();
    }
}

class FilteredTable extends BasicTable
{
    public function filters(): array
    {
        return [
            Filter::select('status', ['active' => 'Active', 'inactive' => 'Inactive']),
            Filter::text('name'),
            Filter::dateRange('created_at')->timezone('Asia/Bangkok'),
            Filter::date('day')->database('created_at')->timezone('Asia/Bangkok'),
            Filter::text('complex')->applyUsing(function ($query, $value) {
                $query->where('name', $value)->orWhere('phone', '300');
            }),
        ];
    }
}

class DefaultFilterTable extends BasicTable
{
    public function filters(): array
    {
        return [Filter::select('status', ['active' => 'Active'])->defaultValue('active')];
    }
}

class SelectedTable extends FilteredTable
{
    public function configure(): void
    {
        $this->selectable();
    }

    public function rowActions(): array
    {
        return [RowAction::make('view')->route('clients.show')];
    }

    public function bulkActions(): array
    {
        return [BulkAction::make('archive')->url('/bulk')->confirm()];
    }
}

class PolicyFieldTable extends BasicTable
{
    public function requiredColumns(): array
    {
        return ['tenant_id'];
    }

    public function rowActions(): array
    {
        return [RowAction::make('view')->route('clients.show')->can('inspect')];
    }
}
