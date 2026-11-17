# Actions and current-page selection

TableKit renders action links and forms. Your Laravel application defines
the destination routes, controllers, and permissions.

## Add header and row actions

These examples assume your app already has `clients.create`, `clients.show`,
and `clients.edit` routes. The mutation routes are defined below.
Add these imports to `ClientTable`:

```php
use Illuminate\Support\Facades\Gate;
use App\Models\Client;
use Thuraaung\TableKit\Actions\Action;
use Thuraaung\TableKit\Actions\RowAction;
```

Keep an existing import if it is already present. Add these methods:

```php
public function actions(): array
{
    return [
        Action::make('create')
            ->label('Add Client')
            ->icon('plus')
            ->route('clients.create')
            ->authorized(function () {
                return Gate::allows('create', Client::class);
            }),
    ];
}

public function rowActions(): array
{
    return [
        RowAction::make('view')->route('clients.show')->can('view'),
        RowAction::make('edit')->route('clients.edit')->can('update'),
        RowAction::make('delete')
            ->route('clients.destroy')
            ->method('DELETE')
            ->confirm('Delete this client?')
            ->can('delete'),
    ];
}
```

The first allowed row action is shown directly. Other allowed row actions
appear in a native expandable menu. Denied actions are omitted.
Register the policy described in [Authorization and security](security.md).
If that policy reads `tenant_id`, include it in the table's
`requiredColumns()` even if it is not a displayed column.

## Action options

The same fluent options work on `Action`, `RowAction`, and `BulkAction`:

| Method | Purpose |
| --- | --- |
| `make(key)` | Create an action; the default label comes from the key |
| `label(text)` | Set visible text |
| `icon(name)` | Use `plus`, `download`, `view`, or `edit` |
| `order(int)` | Sort actions; equal positions preserve definition order |
| `route(name, parameters = [], key = null)` | Resolve a named route |
| `url(string or callback)` | Resolve a direct HTTP(S) or relative URL |
| `method(verb)` | GET, POST, PUT, PATCH, or DELETE |
| `confirm(message)` | Ask for confirmation before navigation/submission |
| `visible(callback)` | Decide whether to show it |
| `authorized(callback)` | Check application-specific permission |
| `can(ability)` | Check a Laravel Gate/policy ability |

`visible()` and `authorized()` receive `(model-or-null, Request)`. Header
and bulk actions have no row model. Their `can()` calls pass no policy
arguments, so use an authorization callback for a model-class policy such
as `Gate::allows('create', Client::class)`.

`Action` and `RowAction` default to GET. `BulkAction` defaults to POST and
rejects GET. Mutations render POST forms with `_token` and, where needed,
Laravel's `_method` field. Confirmation uses an accessible dialog, with a
browser confirmation fallback on older browsers.

## Route keys and URL callbacks

Row routes use the model's route key by default:

```php
RowAction::make('view')->route('clients.show');
```

To name the route parameter and use an explicit model field:

```php
RowAction::make('view')->route('clients.show', 'client', 'uuid');
```

Your route binding must also resolve that UUID. Include `uuid` in
`requiredColumns()` if it is not the model's normal route key.
For more complex routes, return all parameters from a callback:

```php
RowAction::make('view')->route('clients.show', function ($client) {
    return ['client' => $client->getRouteKey()];
});

RowAction::make('profile')->url(function ($client) {
    return route('clients.show', ['client' => $client->getRouteKey()]);
});
```

Header route parameters are ordinary arrays, for example
`Action::make('create')->route('clients.create', ['office' => 'yangon'])`.
Unsafe URL schemes and control characters are rejected.

## Enable selection and a bulk action

Add the import:

```php
use Thuraaung\TableKit\Actions\BulkAction;
```

Add or extend these table methods:

```php
public function configure(): void
{
    $this->selectable();
}

public function bulkActions(): array
{
    return [
        BulkAction::make('archive')
            ->label('Mark inactive')
            ->route('clients.bulk')
            ->confirm('Mark selected clients inactive?'),
    ];
}
```

Selection applies to the **current page only**. The header checkbox selects
that page's rows. Every AJAX draw clears selection, including page, sort,
search, filter, page-length, and reload changes. No DataTables Select
extension is required.

## Implement the mutation endpoints

Add these imports and routes to `routes/web.php`, before a broader dynamic
client route where applicable:

```php
use App\Http\Controllers\ClientActionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::post('/clients/bulk', [ClientActionController::class, 'bulk'])
        ->name('clients.bulk');
    Route::delete('/clients/{client}', [ClientActionController::class, 'destroy'])
        ->name('clients.destroy');
});
```

Create `app/Http/Controllers/ClientActionController.php`. This example needs
a `status` column, a registered Client policy, and the `clients.index` route
from the getting started guide:

```php
<?php

namespace App\Http\Controllers;

use App\DataTables\ClientTable;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Thuraaung\TableKit\Actions\BulkAction;

class ClientActionController extends Controller
{
    public function destroy(Client $client)
    {
        Gate::authorize('delete', $client);
        $client->delete();

        return redirect()->route('clients.index');
    }

    public function bulk(Request $request)
    {
        DB::transaction(function () use ($request) {
            $rows = BulkAction::authorizedModels(
                new ClientTable(),
                $request,
                'update'
            );

            foreach ($rows as $client) {
                $client->update(['status' => 'inactive']);
            }
        });

        return redirect()->route('clients.index');
    }
}
```

Allow `status` in the Client model's mass-assignment configuration if you
use `update()` as shown. These routes must run in Laravel's `web` middleware
with normal CSRF protection; do not exempt them to fix a 419 response.
Your app owns transactions and any operation-specific locking requirements.

The helper validates table access, bounded distinct string IDs, signed page
membership, record existence, current base query scopes, and the policy
for every row before returning models. A denied row prevents the operation.

Bulk forms submit `ids[]`, `page_token`, and `_token`. The page token is
signed with `app.key`, bound to the table class and current user, and valid
for 30 minutes. It proves membership in a server-issued page; it does not
track the latest page in a server session. Reload an expired selection.

An action with no URL is disabled. For example,
`RowAction::make('delete')->confirm()` does not delete anything. Actions
named `delete` or `destroy` with a URL require a mutation method.

For a working application with all routes, forms, and policies, see the
[demo](demo.md).

[Documentation index](README.md)
