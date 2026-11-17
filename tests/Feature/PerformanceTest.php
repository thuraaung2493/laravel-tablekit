<?php

namespace Thuraaung\TableKit\Tests\Feature;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Thuraaung\TableKit\Columns\Column;
use Thuraaung\TableKit\Tests\Client;
use Thuraaung\TableKit\Tests\TestCase;

class PerformanceTest extends TestCase
{
    public function test_large_dataset_stays_in_sql_and_typical_filter_uses_an_index(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->index(['tenant_id', 'status']);
        });
        for ($chunk = 0; $chunk < 20; $chunk++) {
            $rows = [];
            for ($i = 0; $i < 250; $i++) {
                $rows[] = [
                    'tenant_id' => 1,
                    'name' => 'Seeded '.($chunk * 250 + $i),
                    'status' => $i % 2 ? 'active' : 'inactive',
                ];
            }
            Client::insert($rows);
        }
        Route::get('/large', FilteredTable::class);
        DB::enableQueryLog();
        $this->getJson('/large?start=100&length=25&filters[status]=active')->assertOk()
            ->assertJsonPath('recordsTotal', 5002)->assertJsonPath('recordsFiltered', 2501)
            ->assertJsonCount(25, 'data');
        $queries = DB::getQueryLog();
        $this->assertCount(3, $queries);
        $this->assertStringContainsString('limit 25 offset 100', $queries[2]['query']);
        $plan = DB::select('EXPLAIN QUERY PLAN SELECT id FROM clients WHERE tenant_id = ? AND status = ?', [1, 'active']);
        $this->assertStringContainsString('INDEX', strtoupper(json_encode($plan)));
    }

    public function test_relationship_display_has_one_eager_query_for_the_page(): void
    {
        Schema::create('offices', function (Blueprint $table) {
            $table->string('code')->primary();
            $table->string('label');
        });
        DB::table('offices')->insert(['code' => 'yangon', 'label' => 'Yangon Office']);
        Route::get('/relations', RelationshipTable::class);
        DB::enableQueryLog();
        $this->getJson('/relations')->assertOk()->assertJsonPath('data.0.office_label', 'Yangon Office');
        $queries = DB::getQueryLog();
        $this->assertCount(4, $queries);
        $this->assertStringContainsString('offices', $queries[3]['query']);
    }
}

class Office extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'offices';
    protected $primaryKey = 'code';
    protected $keyType = 'string';
    public $incrementing = false;
}

class RelatedClient extends Client
{
    public function officeRecord()
    {
        return $this->belongsTo(Office::class, 'office', 'code');
    }
}

class RelationshipTable extends BasicTable
{
    public function query(): Builder
    {
        return RelatedClient::query()->where('tenant_id', 1)->with('officeRecord');
    }

    public function columns(): array
    {
        return [
            Column::make('name')->searchable(),
            Column::make('office_label')->requires(['office'])->format(function ($row) {
                return $row->officeRecord ? $row->officeRecord->label : '';
            }),
        ];
    }
}
