# Authorization and security

Protect the page, the data endpoint, and every mutation/export endpoint.
Hiding a button is a UI decision; backend permissions still need checking.

## Table access

A table's default `authorize()` returns `true`, which allows public tables.
For an authenticated list, the starter checks for a signed-in user. For a
policy-controlled list, import `Request`, `Gate`, and your Client model,
then add:

```php
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
```

```php
public function authorize(Request $request): bool
{
    return $request->user() !== null
        && Gate::allows('viewAny', Client::class);
}
```

This runs before both rendering and data queries. Keep the application's
`auth` middleware on the page and data routes as well.

## Tenant scopes and policy fields

If your User and Client models have `tenant_id`, scope the base query:

```php
public function query(): \Illuminate\Database\Eloquent\Builder
{
    return Client::query()->where('tenant_id', auth()->user()->tenant_id);
}

public function requiredColumns(): array
{
    return ['tenant_id'];
}
```

Use the authenticated `authorize()` method above before relying on the
current user in this query. `query()` scopes are retained in total counts,
filtered counts, page queries, and the bulk helper. Do not take tenant IDs
from browser filter values as proof of permission.

`requiredColumns()` lets row policies read the tenant field even when it
is not displayed. Without it, automatic column projection may omit fields
your policy needs.

## A complete policy example

This example assumes both models have `tenant_id` and User has an
application-controlled boolean `is_admin` field. It allows tenant members
to view records and administrators to change them.

Create `app/Policies/ClientPolicy.php`:

```php
<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user) && (bool) $user->is_admin;
    }

    public function view(User $user, Client $client): bool
    {
        return $user->tenant_id !== null
            && (string) $user->tenant_id === (string) $client->tenant_id;
    }

    public function update(User $user, Client $client): bool
    {
        return $this->view($user, $client) && (bool) $user->is_admin;
    }

    public function delete(User $user, Client $client): bool
    {
        return $this->update($user, $client);
    }
}
```

On Laravel 7, adjust imports if your models live directly in `App`.
Register explicitly for consistent behavior across supported Laravel majors.
In your existing `AppServiceProvider`, add these imports and the registration
to its `boot()` method, preserving other boot code:

```php
use App\Models\Client;
use App\Policies\ClientPolicy;
use Illuminate\Support\Facades\Gate;
```

```php
Gate::policy(Client::class, ClientPolicy::class);
```

Row actions can then use `can('view')`, `can('update')`, or `can('delete')`.
Controllers must still call `Gate::authorize()`, or use the bulk helper
shown in [Actions and selection](actions.md).

## Output and input rules

- Ordinary column output, headings, and action labels are escaped.
- `html()` is an explicit trust boundary. Escape dynamic text and sanitize
  any user-authored markup inside its callback.
- Only declared columns enter the row JSON. A `hidden()` column still
  reaches the browser; omit private columns instead.
- Search uses bound values and literal wildcard escaping. Browser names,
  search flags, and sort fields never become SQL identifiers.
- Pagination is bounded on the server. Regex and pre-1.10 requests are not
  supported; validation failures return 422.
- Action URLs allow safe HTTP(S) or relative destinations. Mutations belong
  on POST/PUT/PATCH/DELETE endpoints with normal Laravel CSRF middleware.

## Bulk operations and exports

Use `BulkAction::authorizedModels()` before changing selected records. It
checks signed page membership and reauthorizes each record under current
query scopes. It does not replace CSRF, transactions, or any operation-specific
locking policy. An expired page token requires a reload and new selection.

The app owns exports. Authorize them independently and apply the same
intended base query and UI filters on the server. A browser-side export of
a paginated table contains only loaded records unless the app implements
a separate export endpoint. The demo's streamed CSV is an application
example; see [Demo](demo.md).

## Production configuration

Use `APP_DEBUG=false` so Laravel does not return exception details to users.
The TableKit alert itself uses a generic message. Keep `app.key` configured
for sessions, CSRF, and signed selection tokens. Rotating it invalidates
existing page tokens.

Retained PHP/Laravel/DataTables compatibility is separate from upstream
security maintenance. Review [the recorded dependency advisories](compatibility.md)
and your application's current dependency audit before production use.

[Documentation index](README.md)
