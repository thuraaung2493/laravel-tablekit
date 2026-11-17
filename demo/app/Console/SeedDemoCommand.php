<?php

namespace TableKitDemo\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use TableKitDemo\Models\Client;
use TableKitDemo\Models\User;

class SeedDemoCommand extends Command
{
    protected $signature = 'demo:seed {--count=200 : Number of clients}';
    protected $description = 'Create local demo tables and seed clients once';

    public function handle(): int
    {
        if (Schema::hasTable('clients')) {
            $this->info('Demo data already exists; no changes made.');

            return 0;
        }
        $count = filter_var($this->option('count'), FILTER_VALIDATE_INT);
        if ($count === false || $count < 1 || $count > 100000) {
            $this->error('Count must be between 1 and 100000.');

            return 1;
        }
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('password')->default('');
            $table->rememberToken();
        });
        Schema::create('clients', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('tenant_id')->index();
            $table->string('name');
            $table->string('phone');
            $table->string('office')->index();
            $table->string('status')->index();
            $table->timestamps();
        });
        User::create(['name' => 'Local demo admin']);
        $names = ['Aye Aye Win', 'Min Thu', 'Su Myat', 'Htet Aung', 'May Thazin'];
        for ($start = 0; $start < $count; $start += 500) {
            $rows = [];
            for ($i = $start; $i < min($start + 500, $count); $i++) {
                $rows[] = [
                    'tenant_id' => 1,
                    'name' => $names[$i % 5].' '.($i + 1),
                    'phone' => '09'.str_pad((string) $i, 9, '0', STR_PAD_LEFT),
                    'office' => ['yangon', 'mandalay', 'bago'][$i % 3],
                    'status' => $i % 4 === 0 ? 'inactive' : 'active',
                    'created_at' => '2026-11-'.str_pad((string) (($i % 16) + 1), 2, '0', STR_PAD_LEFT).' 08:00:00',
                    'updated_at' => '2026-11-16 08:00:00',
                ];
            }
            Client::insert($rows);
        }
        $this->info('Seeded '.$count.' clients.');

        return 0;
    }
}
