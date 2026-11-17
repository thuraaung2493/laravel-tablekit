# Troubleshooting

Start with the browser's Network tab. Find the table's data request and
check its HTTP status. Then check `storage/logs/laravel.log` in your app
(`demo/storage/logs/laravel.log` for the demo).

## The table shows a frontend engine error

| Message or symptom | Check |
| --- | --- |
| Load DataTables before initializing TableKit | Core script loaded successfully, before adapter and TableKit |
| TableKit expects a different version | The configured major/full version matches the actual core file |
| Load the TableKit adapter | The matching `datatables-v1`, `v2`, or `v3` script is included |
| DataTables 1.x/2.x requires jQuery | One compatible jQuery instance loads before core |
| Conflicting engines | Remove duplicate core scripts/bundles and use one version per page |
| Cannot reinitialize DataTable | Remove manual initialization for a TableKit-owned table |

Use the complete commands and script order in [Frontend setup](frontend.md).
Changing `datatable-version` does not download a different engine.

## The alert says records could not load

| Data request status | Likely cause and next step |
| --- | --- |
| 401 or redirect to login | Sign in; check the auth middleware and request origin |
| 403 | Check table `authorize()` and the registered policy |
| 404 | Check the `:url`, route name, prefix, and cached routes |
| 422 | Inspect JSON validation errors; check sort mapping, filters, and pagination |
| 419 on a mutation | Check session/CSRF cookies, `_token`, app key, and same-origin URLs |
| 500 | Read the application log; check columns, formatter dependencies, and SQL |
| 200 with HTML | The URL returned a page, redirect, or error view instead of table JSON |

The data endpoint should return `draw`, `recordsTotal`, `recordsFiltered`,
and `data`. Do not point `:url` at the page route. Register `/clients/data`
before a route such as `/clients/{client}`.

The alert intentionally avoids showing server exception details. Use logs
for diagnosis; keep debug output disabled in production.

## The component or assets cannot be found

From the Laravel app:

```sh
composer dump-autoload
php artisan vendor:publish --tag=tablekit-assets
php artisan view:clear
php artisan config:clear
```

Confirm that Composer installed the local package and discovered
`Thuraaung\TableKit\TableKitServiceProvider`. If package discovery is
intentionally disabled, register that provider in your app's provider list.

Use the exact tag `<x-tablekit::table>`. For a local path installation,
make sure the Composer repository points at the actual checkout.
A browser request for each JS/CSS file must return the file, not an HTML 404.

## Search, sort, or a computed column fails

- Mark a column `searchable()` or `sortable()` on the server; browser flags
  do not enable access.
- `defaultSort()` must name a declared sortable data key, not an arbitrary
  SQL field or expression.
- Computed keys need `requires()` for display dependencies and a real
  `database()` mapping if you enable search or sorting.
- Query aliases/joins may need an explicit select list and qualified SQL
  fields. A returned model must retain its primary key.
- Per-column DataTables search and regex searches are rejected. Use TableKit
  filter definitions instead.

See [Columns and queries](columns.md). A reset clears UI filters but retains
search, sorting, and required query scopes; remaining restrictions may be
intentional.

## Actions are missing, blank, or disabled

Check `visible()`, `authorized()`, `can()`, policy registration, and the
current user. If a row policy reads fields such as `tenant_id`, add them to
`requiredColumns()` or your explicit select list.

An action without a route/URL is disabled. A delete action needs an actual
authorized endpoint and `method('DELETE')`; `confirm()` alone is only UI.
Custom route keys must be selected and supported by your app's route binding.

Do not remove backend permission checks to make a button visible.

## Selection or a bulk form fails

- Call `selectable()` and define a `bulkActions()` entry.
- Select rows after the latest redraw; all AJAX draws clear selection.
- Configure a nonempty `app.key`.
- Keep normal CSRF middleware and include the rendered form's tokens.
- Reload and reselect after a page token expires (30 minutes).
- The server can reject a record that changed tenant, was deleted, or is
  no longer authorized since it was displayed.

The default covers one issued page, not every filtered record. See
[Actions and selection](actions.md).

## Configuration changes do not appear

Check precedence: Blade attributes override table methods, which override
global defaults. A single override changes only its own property.

Clear cached config/views and, after route changes, clear route cache:

```sh
php artisan config:clear
php artisan view:clear
php artisan route:clear
```

Republish assets after package changes. If you published views, check
`resources/views/vendor/tablekit`; those templates override package views.
Review local customizations before using `--force` to overwrite them.

## Composer or the demo does not start

A PHP platform error usually means the PHP binary does not match the
installed Laravel/dev dependencies. Run `php -v`, check the selected vendor
directory, and install dependencies with the intended binary.

Modern Composer may block an EOL Laravel fixture because of advisories.
The documented exception in [Demo](demo.md) is only for local compatibility
work. Keep your production app's security policy intact.

For the demo, run setup from the package root, enable `pdo_sqlite`, and stop
other processes using port 8123 before `npm run test:demo`. Setup keeps
existing data, so `demo:seed --count=...` will not grow an already-seeded DB.

[Documentation index](README.md)
