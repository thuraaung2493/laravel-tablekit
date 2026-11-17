<?php

namespace Thuraaung\TableKit\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use Thuraaung\TableKit\TableKitServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [TableKitServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('app.debug', false);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('clients', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('tenant_id');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('status')->default('active');
            $table->string('office')->default('yangon');
            $table->string('secret')->default('private');
            $table->dateTime('created_at')->nullable();
        });
        Client::insert([
            ['tenant_id' => 1, 'name' => 'Alice', 'phone' => '100', 'status' => 'active'],
            ['tenant_id' => 1, 'name' => 'Bob', 'phone' => '200', 'status' => 'inactive'],
            ['tenant_id' => 2, 'name' => 'Alice elsewhere', 'phone' => '300', 'status' => 'active'],
        ]);
    }
}

class Client extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $table = 'clients';
}
