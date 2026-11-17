<?php

namespace Thuraaung\TableKit\Tests\Feature;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Thuraaung\TableKit\Columns\Column;
use Thuraaung\TableKit\DataTable;
use Thuraaung\TableKit\Tests\Client;
use Thuraaung\TableKit\Tests\TestCase;

class CoreTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Route::get('/data', BasicTable::class);
    }

    private function data(array $parameters = [])
    {
        return $this->getJson('/data?'.http_build_query($parameters));
    }

    public function test_minimal_invokable_table_protocol_and_private_attributes(): void
    {
        $this->data(['draw' => '7'])->assertOk()->assertExactJson([
            'draw' => 7,
            'recordsTotal' => 2,
            'recordsFiltered' => 2,
            'data' => [
                ['name' => 'Alice', 'phone' => '100'],
                ['name' => 'Bob', 'phone' => '200'],
            ],
        ]);
    }

    public function test_search_and_counts_retain_tenant_scope(): void
    {
        $this->data(['search' => ['value' => 'Alice']])
            ->assertOk()->assertJsonPath('recordsTotal', 2)
            ->assertJsonPath('recordsFiltered', 1)->assertJsonCount(1, 'data');
    }

    public function test_sort_pagination_and_stable_tie_breaker(): void
    {
        $this->data([
            'order' => [['column' => 0, 'dir' => 'desc']],
            'start' => 1,
            'length' => 1,
        ])->assertOk()->assertJsonPath('data.0.name', 'Alice')
            ->assertJsonPath('recordsFiltered', 2);
    }

    public function test_wildcards_are_literal(): void
    {
        Client::create(['tenant_id' => 1, 'name' => '100%_!', 'phone' => '400']);
        $this->data(['search' => ['value' => '%_!']])->assertOk()
            ->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.name', '100%_!');
    }

    public function test_text_is_escaped_and_sql_injection_is_bound(): void
    {
        Client::create(['tenant_id' => 1, 'name' => '<img src=x onerror=alert(1)>']);
        $this->data(['search' => ['value' => '<img']])->assertOk()
            ->assertJsonPath('data.0.name', '&lt;img src=x onerror=alert(1)&gt;');
        $this->data(['search' => ['value' => "' OR 1=1 --"]])->assertOk()
            ->assertJsonPath('recordsFiltered', 0);
    }

    public function test_invalid_inputs_are_rejected(): void
    {
        foreach (self::invalidRequests() as $scenario) {
            $this->data($scenario[0])->assertStatus(422);
        }
    }

    public static function invalidRequests(): array
    {
        return [
            [['draw' => '<script>']],
            [['start' => -1]],
            [['length' => 0]],
            [['search' => ['regex' => 'true']]],
            [['search' => ['value' => ['array']]]],
            [['order' => [['column' => 999, 'dir' => 'asc']]]],
            [['order' => [['column' => 1, 'dir' => 'asc']]]],
            [['order' => [['column' => 0, 'dir' => 'desc; DROP TABLE clients']]]],
            [['columns' => [['data' => 'secret']]]],
            [['columns' => [['data' => 'name', 'name' => 'name desc--']]]],
        ];
    }

    public function test_large_or_unlimited_page_sizes_are_clamped_in_sql(): void
    {
        config(['tablekit.table.max_page_length' => 1]);
        DB::enableQueryLog();
        $this->data(['length' => -1])->assertOk()->assertJsonCount(1, 'data');
        $queries = DB::getQueryLog();
        $this->assertCount(3, $queries);
        $this->assertStringContainsString('limit 1', $queries[2]['query']);
        $this->assertStringNotContainsString('select *', $queries[2]['query']);
        $this->data(['length' => 100000])->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_table_authorization_runs_before_query(): void
    {
        Route::get('/private', PrivateTable::class);
        DB::enableQueryLog();
        $this->getJson('/private')->assertForbidden();
        $this->assertCount(0, DB::getQueryLog());
    }

    public function test_database_mapping_uses_server_definition(): void
    {
        Route::get('/mapped', MappedTable::class);
        $this->getJson('/mapped?search[value]=Alice')->assertOk()
            ->assertJsonPath('data.0.display', 'Alice');
    }
}

class BasicTable extends DataTable
{
    public function query(): Builder
    {
        return Client::query()->where('tenant_id', 1);
    }

    public function columns(): array
    {
        return [Column::make('name')->searchable()->sortable(), Column::make('phone')];
    }
}

class PrivateTable extends BasicTable
{
    public function authorize(Request $request): bool
    {
        return false;
    }
}

class MappedTable extends BasicTable
{
    public function columns(): array
    {
        return [Column::make('display')->database('clients.name')->searchable()->sortable()];
    }
}
