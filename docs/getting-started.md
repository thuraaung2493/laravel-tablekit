# Create your first table

This guide adds an authenticated client list to an existing Laravel app.
Use [the demo](demo.md) if you want a ready-made app instead.

## Before you start

You need:

- A Laravel app with a working login system and the `auth` middleware.
- A `Client` Eloquent model backed by a `clients` table with `id`, `name`,
  and `phone` columns. TableKit does not create these for you.
- Node/npm for preparing frontend files. A local TableKit checkout is
  needed only if you choose a path repository.

The example allows signed-in users to browse all clients. For tenant or
role restrictions, follow [Authorization and security](security.md).

## 1. Install the PHP package

In your application's `composer.json`, add the public GitHub repository.
Keep any existing entries in your `repositories` list:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/thuraaung2493/laravel-tablekit"
    }
  ]
}
```

Alternatively, use a local path repository and adjust its path to your
checkout:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../laravel-tablekit"
    }
  ]
}
```

Run these commands **inside your Laravel application**:

```sh
composer require thuraaung/tablekit:@dev
php artisan vendor:publish --tag=tablekit-config
php artisan vendor:publish --tag=tablekit-assets
php artisan tablekit:make ClientTable --model='App\Models\Client'
```

Laravel discovers the provider automatically. The command creates
`app/DataTables/ClientTable.php`. On Laravel 7, use `--model='App\Client'`
if that is your model's namespace.

The package is not on Packagist yet; either repository entry makes the
source available to Composer.

## 2. Define the table

Replace `app/DataTables/ClientTable.php` with:

```php
<?php

namespace App\DataTables;

use App\Models\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Thuraaung\TableKit\Columns\Column;
use Thuraaung\TableKit\DataTable;

class ClientTable extends DataTable
{
    public function query(): Builder
    {
        return Client::query();
    }

    public function columns(): array
    {
        return [
            Column::make('name')->searchable()->sortable(),
            Column::make('phone'),
        ];
    }

    public function authorize(Request $request): bool
    {
        return $request->user() !== null;
    }
}
```

`query()` defines which records are available. `columns()` defines which
values reach the browser. The `authorize()` method checks both page
rendering and AJAX access. Filters, actions, and layout methods are optional.

## 3. Register the routes

Add these imports and routes to `routes/web.php`:

```php
use App\DataTables\ClientTable;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/clients/data', ClientTable::class)->name('clients.data');
    Route::view('/clients', 'clients.index')->name('clients.index');
});
```

Register `/clients/data` before an existing `/clients/{client}` route so
`data` is not treated as a model identifier. Do not duplicate existing route
names; adapt your existing client index route if needed.

## 4. Prepare frontend files

The following uses the DataTables 3.1.5 version covered by the package's
browser tests. Run inside your Laravel app:

```sh
npm install --save-exact datatables.net@3.1.5
mkdir -p public/vendor/datatables
cp node_modules/datatables.net/js/dataTables.min.js public/vendor/datatables/dataTables.min.js
```

TableKit JS/CSS were copied by `vendor:publish` in step 1. These commands
copy the application-owned DataTables core to a URL the browser can load.
Repeat the copy when updating the npm dependency, or use your asset pipeline.

For DataTables 1.x/2.x, existing script tags, or a bundler, use the
[frontend guide](frontend.md) in place of this step.

## 5. Create the page

Create `resources/views/clients/index.blade.php`:

```blade
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Clients</title>
    <link rel="stylesheet" href="{{ asset('vendor/tablekit/css/tablekit.css') }}">
</head>
<body>
    <main>
        <x-tablekit::table
            :table="App\DataTables\ClientTable::class"
            :url="route('clients.data')"
            id="clients-table"
            title="Clients"
            description="Browse client contact details"
        />
    </main>

    <script src="{{ asset('vendor/datatables/dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/tablekit/js/adapters/datatables-v3.js') }}"></script>
    <script src="{{ asset('vendor/tablekit/js/tablekit.js') }}"></script>
</body>
</html>
```

The order of the three scripts matters: engine, matching adapter, TableKit.
Do not also initialize this table with `new DataTable()` yourself.

## Check the result

Sign in and open `/clients` on your application's normal development URL.
You should see the Name and Phone columns, search, page length, and paging.
Search applies to Name; sorting is enabled for Name. With no records, the
table displays an empty message.

In browser developer tools, `/clients/data` should return JSON containing
`draw`, `recordsTotal`, `recordsFiltered`, and `data`. A failed request or
missing frontend engine produces a visible table error.

Next, add [filters](filters.md), change the [layout](configuration.md), or
add [actions](actions.md). If the page does not load, start with
[Troubleshooting](troubleshooting.md).

[Documentation index](README.md)
