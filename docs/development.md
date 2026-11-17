# Package development

Run these commands from the TableKit checkout. To install TableKit in a
consumer app instead, use [Getting started](getting-started.md).

## Install and run checks

Use PHP compatible with the target framework. On PHP 8.3+:

```sh
composer update --prefer-dist
npm ci --ignore-scripts
composer test
composer lint
npm test
```

`composer test` runs PHPUnit. `composer lint` syntax-checks package PHP,
config, tests, rendered generator stubs, scripts, and demo application PHP.
Run lint with a real PHP 7.4 binary to verify the minimum syntax target.

For browser checks:

```sh
npx --no-install playwright install chromium
npm run test:browser
```

This runs the four real-engine adapter tests. Live demo tests are skipped
until you select `test:demo`:

```sh
php scripts/setup-demo.php
npm run test:demo
```

The demo command runs both adapter and live Laravel tests and manages its
own server. Stop a manually running server on port 8123 first.

## Select local PHP and Chrome binaries

On macOS, if you already have these binaries installed:

```sh
TABLEKIT_CHROME='/Applications/Google Chrome.app/Contents/MacOS/Google Chrome' \
TABLEKIT_PHP=/opt/homebrew/opt/php@7.4/bin/php \
npm run test:demo
```

`TABLEKIT_PHP` affects the demo web server, not Composer. Install/setup
with the same intended PHP runtime first. `TABLEKIT_CHROME` is optional
when Playwright Chromium is installed.

## Test another Laravel major

Create an isolated project without replacing root dependencies:

```sh
php scripts/prepare-matrix.php 13
composer update --working-dir=.compat/laravel13 --prefer-dist --no-interaction
php .compat/laravel13/vendor/bin/phpunit --bootstrap .compat/laravel13/vendor/autoload.php
```

Replace `13` consistently with a major from 7 through 13. Use a PHP binary
that satisfies that Laravel major. The script selects compatible Testbench
and PHPUnit ranges. The generated manifests/installations live in ignored
`.compat/` directories.

For EOL compatibility fixtures, Composer's security blocking may require
`--no-security-blocking` on this isolated update command. This is a test
fixture exception, not a package-level setting for users.

Exact executed rows and untested minimum-runtime combinations are in
[Compatibility](compatibility.md). CI is defined in
[the workflow](../.github/workflows/tests.yml).

## Use the generator

From an application with TableKit installed:

```sh
php artisan tablekit:make ClientTable --model='App\Models\Client'
php artisan tablekit:make Admin/ClientTable --model='App\Models\Client'
```

Names are relative to `App\DataTables`; the second example creates
`app/DataTables/Admin/ClientTable.php`. The app's root namespace is used
if it differs from `App`.

Without `--model`, a model name is inferred from the table class. A short
model name prefers an existing `App\Client`, otherwise `App\Models\Client`.
An explicit fully qualified model name avoids ambiguity. The model must
exist when the generated table runs.

Existing files are protected. Use `--force` only when you intend to replace
one. The generated table includes a sortable `id` column; customize it for
the model and add authorization before exposing sensitive records.

## Project map

| Directory | Responsibility |
| --- | --- |
| `src/DataTable.php` | Table extension points and behavior settings |
| `src/Processing` | Eloquent counts, filters/search, ordering, paging, output |
| `src/Columns`, `src/Filters`, `src/Actions` | Fluent definitions |
| `src/Http` | Shared protocol validation |
| `src/View`, `resources/views` | Blade component and templates |
| `resources/js/adapters` | Engine-specific initialization |
| `resources/js/tablekit.js` | Shared controls, requests, selection, lifecycle |
| `resources/css` | Namespaced default styles |
| `src/Console`, `resources/stubs` | Generator command and starter stub |
| `tests` | PHP, Node, and browser suites |
| `demo` | Local Laravel example app |

Keep distributed source and stubs compatible with PHP 7.4. Use Laravel
7-compatible APIs or a tested compatibility path. Avoid mixing frontend
major-version checks into the PHP processor.

## Update an app after package changes

For an app using published assets:

```sh
php artisan vendor:publish --tag=tablekit-assets --force
php artisan config:clear
php artisan view:clear
```

This overwrites published asset copies. If you customized published views,
compare them with package templates before updating them. For a bundler,
recopy the source files described in [Frontend setup](frontend.md) and build.
Preserve local configuration choices when adding new config keys.

## Check caches and packaging

The local demo supports normal Laravel cache commands:

```sh
php demo/artisan config:cache
php demo/artisan route:cache
```

For non-publishing metadata/archive checks:

```sh
composer validate --strict
composer archive --format=zip --dir=/tmp/tablekit-dist
```

Inspect the archive before distribution. It should include runtime source,
config, templates, JS/CSS, and the generator stub; it should exclude vendor,
node_modules, local keys/databases, compatibility installations, and test
output. The demo and test tooling are available from the source checkout,
not the runtime archive.

## Dependency audits

```sh
composer audit --locked
npm audit
```

Known legacy test fixture advisories are recorded in
[Compatibility](compatibility.md). Do not hide audit results or replace
legacy compatibility fixtures silently just to obtain a green audit.

Development checks do not publish packages, bump versions, push branches,
or create releases. See [Release checklist](release-checklist.md).

[Documentation index](README.md)
