# Run the demo

The demo is a small Laravel app in `demo/`. It includes the client table,
Create/Edit forms, policies, mutation controllers, selection, and a CSV
export. It is separate from the package runtime.

## 1. Install development dependencies

Run commands **from the TableKit checkout**, not from another Laravel app.
Use one of these PHP setups.

### PHP 8.3 or newer

```sh
composer update --prefer-dist
```

Composer selects dependencies compatible with that PHP binary. The demo
uses the root `vendor` installation by default.

### PHP 7.4 and Laravel 7 compatibility demo

```sh
composer update --with orchestra/testbench:^5.0 --with laravel/framework:^7.0 --prefer-dist --no-security-blocking
```

This deliberately installs EOL development fixtures. The security exception
is for this isolated compatibility demo; do not apply it to a production
app to suppress dependency advisories. See [Compatibility](compatibility.md).

Then install the frontend test/demo assets:

```sh
npm ci --ignore-scripts
```

Use a PHP binary with SQLite support. `php -m` should list `pdo_sqlite`.
Keep the same PHP version for dependency installation, setup, and serving.
If changing a previous demo's Laravel version, clear its config, route, and
view caches using the commands below before starting it again.

## 2. Prepare and start

```sh
php scripts/setup-demo.php
php -S 127.0.0.1:8123 -t demo/public demo/public/router.php
```

Open [http://127.0.0.1:8123](http://127.0.0.1:8123) and select **Enter demo**.
The setup command creates a random local application key, an SQLite file,
200 client records on the first run, and local frontend assets.

Setup is safe to repeat: it keeps the existing key and data, and refreshes
published assets. It does not reset the database. The SQLite file is
`demo/database/demo.sqlite`; local keys are in `demo/.env`.

The login signs in as the seeded administrator without a password. Keep
this demo bound to loopback and do not deploy it as a public application.

## Try the features

| Task | How |
| --- | --- |
| Search | Type a name or phone in Search |
| Filter | Open Filters and choose a status or office |
| Reset | Select Reset filters; global search stays in place |
| Sort | Activate Name, Office, Status, or Registered Date |
| Add a client | Select Add Client, complete the form, and Save |
| Edit/delete | Open a row's secondary action menu |
| Bulk change | Select current-page rows, then Mark inactive |
| Change layout | Pick density, toolbar, and filter modes; Apply layout |
| Change engine | Pick 110, 113, 2, or 3; Apply layout |

The engine values mean DataTables 1.10.25, 1.13.11, 2.3.8, and 3.1.5.
Each page loads one engine. The 3.x page does not load jQuery.
Selection clears whenever the data redraws.

## Understand the CSV example

**Export CSV (scoped)** streams all clients in the authorized base scope.
The toolbar link does not automatically include the active UI filters.
The endpoint also accepts validated filter/search parameters, for example:

```text
/clients/export?filters[status]=active&search[value]=Aye
```

It uses SQL chunking and escapes spreadsheet-formula prefixes. This is an
application-owned example, not a package-wide export integration or an Excel
export. The same controller is responsible for its access check and scopes.

## Find the example code

| File | What it demonstrates |
| --- | --- |
| [ClientTable](../demo/app/DataTables/ClientTable.php) | Columns, filters, header/row/bulk actions |
| [ClientController](../demo/app/Http/Controllers/ClientController.php) | CRUD handlers, bulk authorization, streamed CSV |
| [ClientRequest](../demo/app/Http/Requests/ClientRequest.php) | Create/Edit validation and authorization |
| [ClientPolicy](../demo/app/Policies/ClientPolicy.php) | Table and record permissions |
| [Routes](../demo/routes/web.php) | Authenticated data and mutation endpoints |
| [Index view](../demo/resources/views/clients/index.blade.php) | Component, layout controls, engine loading |
| [Form view](../demo/resources/views/clients/form.blade.php) | Application-owned Create/Edit form |

## Caches and tests

To check cached operation:

```sh
php demo/artisan config:cache
php demo/artisan route:cache
```

To undo caches while editing:

```sh
php demo/artisan config:clear
php demo/artisan route:clear
php demo/artisan view:clear
```

For automated browser checks, stop any manually running server on port 8123,
install Playwright's Chromium, and run:

```sh
npx --no-install playwright install chromium
npm run test:demo
```

The test command starts and stops its own server. It creates and changes
local demo records while exercising mutations. Use only a development demo
database. See [Development](development.md) to select PHP/Chrome binaries.

If `demo/vendor` exists, it takes priority over root `vendor`. You can also
set `TABLEKIT_VENDOR` to an absolute vendor directory. If the app reports a
runtime mismatch, check which installation it is loading.

[Documentation index](README.md)
