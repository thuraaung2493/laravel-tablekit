# Laravel TableKit — Project Specification

**Status:** Draft v1.1  
**Product:** Laravel TableKit  
**Suggested Composer package:** `thuraaung/tablekit`  
**Namespace:** `Thuraaung\TableKit`  
**Type:** Reusable Laravel package with version-aware DataTables 1.x / 2.x / 3.x frontend adapters  
**Minimum runtime:** PHP 7.4 and Laravel 7  
**Latest supported:** PHP latest and Laravel 13
**DataTables version:** Application-selectable (`auto` by default)

> This document defines the desired developer experience, public API, UI design, architecture, implementation phases, and acceptance criteria. Examples are proposed APIs, not claims that the package has already been implemented.

## 1. Vision

Build a lightweight, elegant, Laravel-first DataTables package that removes repetitive table controllers, AJAX wiring, JavaScript initialization, query filtering, sorting, and action rendering.

A developer should be able to create a server-side table by defining an Eloquent query and some columns, registering a route, and rendering one Blade component.

### Principles

- **Laravel style:** dedicated classes, expressive fluent APIs, Eloquent builders, Blade components, policies, configuration publishing, and service providers.
- **Simple first:** only `query()` and `columns()` are required for a basic table.
- **Convention over configuration:** sensible global defaults, with explicit overrides at the table or view level.
- **Performance by default:** server-side paging/searching/sorting, selected columns, efficient counts, and safe limits.
- **Secure by default:** allowlisted sort/search targets, authorization, CSRF for mutations, escaped text, and validated request inputs.
- **Customizable:** customizable layout, columns, filters, actions, templates, styling, and frontend lifecycle hooks.
- **No unnecessary dependencies:** use Laravel components and the DataTables major version already chosen by the host application; avoid requiring a separate table framework.
- **Predictable APIs:** no hidden magic that makes debugging data queries difficult.
- **Legacy compatibility first:** PHP 7.4 and Laravel 7 are the minimum; never require newer PHP syntax or Laravel-only APIs without a compatible fallback.
- **Frontend version choice:** support DataTables 1.10+, 2.x, and 3.x with runtime detection or an explicit configured version; do not force legacy projects to upgrade frontend assets.

## Runtime and version compatibility (mandatory)

### Minimum and supported ranges

| Dependency    | Requirement                            | Notes                                                                                                                                 |
| ------------- | -------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------- |
| PHP           | **7.4 minimum**                        | Package source, generators, examples, and transitive runtime dependencies must work on 7.4.                                           |
| Laravel       | **7.0 minimum**                        | Support Laravel 7 onward, including newer majors only when tested and dependency resolution permits.                                  |
| DataTables JS | **1.10+**, **2.x**, **3.x**            | Host application selects the installed version; TableKit selects the matching adapter. DataTables earlier than 1.10 are out of scope. |
| jQuery        | Required by DataTables 1.x / 2.x       | Provided by the application. DataTables 3 does not require jQuery, but may integrate with it if present.                              |
| Browser       | Dependent on selected DataTables major | DataTables 3 targets ES6-capable browsers; earlier major versions have their own browser constraints.                                 |

**Important:** PHP 7.4 and Laravel 7 are both end-of-life and do not receive upstream security fixes. The package must still support these as requested, but docs should recommend upgrades for publicly exposed production applications. _Minimum support_ is not a claim that every PHP/Laravel version combination can run: each Laravel major has its own PHP requirements.

### Composer package constraints (proposed)

Use Composer constraints that allow Laravel 7 while permitting newer Laravel majors without pulling in PHP-8-only runtime dependencies on PHP 7.4. For example, the relevant part of `composer.json` could be:

```json
{
  "require": {
    "php": "^7.4 || ^8.0",
    "illuminate/support": "^7.0 || ^8.0 || ^9.0 || ^10.0 || ^11.0 || ^12.0 || ^13.0",
    "illuminate/database": "^7.0 || ^8.0 || ^9.0 || ^10.0 || ^11.0 || ^12.0 || ^13.0",
    "illuminate/http": "^7.0 || ^8.0 || ^9.0 || ^10.0 || ^11.0 || ^12.0 || ^13.0",
    "illuminate/view": "^7.0 || ^8.0 || ^9.0 || ^10.0 || ^11.0 || ^12.0 || ^13.0"
  }
}
```

This is a **proposed baseline**, not a verified installable manifest. Finalize exactly which Illuminate packages are required from actual source imports, test Composer resolution with each supported Laravel major, and avoid unrelated dependencies or dependencies requiring PHP >=8.0. The package must not require users to update Laravel or PHP simply to select a newer DataTables JavaScript major.

### PHP / Laravel implementation rules

- Write all distributed PHP source and generator stubs in **PHP 7.4-compatible syntax**, including method signatures, nullable types, typed properties when helpful, and return types supported by PHP 7.4.
- **Do not use** PHP 8+ syntax or APIs in production code: enums, union/intersection types, `match`, nullsafe `?->`, attributes, constructor property promotion, `readonly`, named arguments, or PHP-8-only functions such as `str_contains()` unless a polyfill is intentionally introduced.
- Use Laravel 7-compatible package registration, Blade component registration, `FormRequest`/validator usage, Eloquent/Query Builder APIs, pagination, routing, configuration, and authorization.
- Do not assume Laravel 9+ / 10+ / 11+ methods or framework conveniences exist. Only add version-specific behavior behind a tested compatibility layer if truly needed.
- Be careful with modern dev dependencies (PHPUnit, Testbench, code formatters) that have PHP/platform constraints; select compatible versions per CI job rather than forcing a recent version into the PHP 7.4 installation.
- Maintain a single public API across supported Laravel majors, with no extra config required for Laravel 7.

### Version-aware DataTables engine selection

The PHP server-side request/response protocol stays the same across supported DataTables majors; differences primarily affect frontend initialization, built-in layout placement, styling, and extension compatibility.

**Default:** detect the version loaded by the host application (`auto`). **Override:** configure `1`, `2`, or `3` as a major, or a concrete released version such as `1.13.11`, `2.3.8`, or `3.1.5` for explicit runtime validation. The actual JavaScript/CSS files are **installed by the application**, not chosen or downloaded by a PHP config value.

For example, the application can choose a concrete major/patch by installing one of the following (choose **one**; these are examples, not all-time latest-version guarantees):

```bash
npm install datatables.net@1.13.11
npm install datatables.net@2.3.8
npm install datatables.net@3.1.5
```

Install the matching DataTables styling integration package and optional extensions for that version; older sites may instead retain their existing local script/style tags. On 1.x / 2.x, ensure an appropriately compatible jQuery version is present. Do **not** install all three versions simultaneously in the same application bundle.

- `auto`: read the loaded DataTables version and select the compatible adapter.
- `1` / `2` / `3`: select the corresponding major adapter and check that the loaded major matches.
- Full version: validate the installed version against the specified version; select the adapter by its major.
- If no supported engine is loaded, the explicit version mismatches the actual script, or unsupported 1.9-era assets are present, show a useful development error rather than silently initializing a broken table.
- Never initialize two different DataTables major versions against a shared global object on one page. Per-table overrides are for pages with a single compatible installed engine; incompatible per-table selections on the same page must be rejected.
- Do not automatically inject CDN assets, jQuery, extension packages, or duplicate CSS. Document how developers choose and pin the version with npm or their existing script tags/asset pipeline.

### Frontend compatibility architecture

- **Shared PHP processor:** one server-side protocol for DataTables 1.10+, 2.x, and 3.x (`draw`, `recordsTotal`, `recordsFiltered`, `data`). Never implement the pre-1.10 legacy `sEcho` / `iDisplayStart` protocol.
- **Shared TableKit UI:** render the search field, filter controls, actions, selection controls, and layout shell as **TableKit-owned HTML**, so the compact/top/collapsible design is independent of the upstream DataTables major.
- **DataTables 1.x adapter:** initialize using `$(selector).DataTable(options)` with 1.x-compatible `dom` placement; avoid DataTables 2-only `layout` options, 3-only features, or unsupported extensions.
- **DataTables 2.x adapter:** use the modern `layout` option for native info/pagination placement; keep TableKit's separate `toolbar` / `filters` layout presets distinct from the DataTables `layout` API.
- **DataTables 3.x adapter:** use `new DataTable(selector, options)` and version-3-compatible `layout`; do not depend on jQuery in adapter code. Use public APIs rather than removed internal settings or jQuery-specific CSS selectors.
- **Cross-version UI:** normalize CSS and exposed JS methods (`reload`, `resetFilters`, `getSelectedIds`, `clearSelection`); do not assume native DataTables CSS classes are identical across majors.
- **Extensions:** only enable Buttons, Responsive, Select, etc. when the host application's installed extension version is compatible with its DataTables core version. Do not conflate TableKit's own selection implementation with DataTables Select.

### Example: selecting a version

`config/tablekit.php`:

```php
'datatables' => [
    'version' => 'auto',
    'assets' => 'external',
],
```

Per-table optional override (not part of `layout()`, because it is an engine setting):

```php
public function dataTablesVersion(): string
{
    return '1';
}
```

Per-view optional override:

```blade
<x-tablekit::table
    :table="ClientTable::class"
    :url="route('clients.data')"
    datatable-version="2"
/>
```

**Resolution precedence:** Blade `datatable-version` > table `dataTablesVersion()` > global `datatables.version` (default `auto`). Resolve version independently of layout properties. A concrete version is a compatibility assertion, **not** an instruction to load or install that version.

---

## 2. Scope

### MVP

1. Table class abstraction with an invokable AJAX endpoint.
2. Eloquent Builder query support.
3. Fluent column definitions with labels, visibility, search, and sort configuration.
4. Server-side DataTables **1.10+ / 2.x / 3.x** request/response handling, with a shared protocol.
5. Global search, configurable default sorting, page length and limits.
6. Select, text, date, and date-range filters with a reusable UI.
7. Global, per-table, and Blade-level layout configuration.
8. Top toolbar with search, filters toggle, customizable actions.
9. Row actions with authorization/visibility support and confirmation.
10. Optional current-page checkbox selection and bulk action hooks.
11. Blade component and version-aware JavaScript adapters with namespaced, cross-version styles.
12. Loading, empty, error, and accessibility states.
13. Artisan generator, test suite, examples, and documentation.

### Future / optional extensions

- Query Builder and custom data-source interfaces.
- Relationship-aware search/sorting (explicitly configured, not guessed).
- Server-side Excel/CSV exports and optional PDF printing.
- Select-all-filtered-results, with explicit server-side semantics.
- Column visibility chooser, saved views, advanced filters, and persistent table state.
- Theme adapters for Bootstrap, Tailwind, and custom design systems.
- Optional DataTables extensions (Buttons, Responsive, Select, etc.).

### Out of scope for MVP

- Building a full CRUD/form-builder framework.
- Automatic model form generation.
- Infinite scroll and real-time live updates.
- Automatically generating database indexes.
- Treating browser-side CSV exports as a complete export of a server-side dataset.

A matching **Create Client** form can be included in the demo Laravel application, but CRUD forms are application responsibilities rather than required TableKit features.

## 3. Proposed developer experience

### 3.1 Define a table

`app/DataTables/ClientTable.php`

```php
<?php

namespace App\DataTables;

use App\Models\Client;
use Illuminate\Database\Eloquent\Builder;
use Thuraaung\TableKit\DataTable;
use Thuraaung\TableKit\Columns\Column;
use Thuraaung\TableKit\Filters\Filter;
use Thuraaung\TableKit\Actions\Action;
use Thuraaung\TableKit\Actions\RowAction;
use Thuraaung\TableKit\Layout;

class ClientTable extends DataTable
{
    public function query(): Builder
    {
        return Client::query();
    }

    public function columns(): array
    {
        return [
            Column::make('id')->sortable(),
            Column::make('name')->searchable()->sortable(),
            Column::make('phone')->searchable(),
            Column::make('office')->searchable(),
            Column::make('status')->sortable(),
            Column::make('created_at')
                ->label('Registered Date')
                ->sortable(),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::select('status', [
                'active' => 'Active',
                'inactive' => 'Inactive',
            ]),
            Filter::select('office', [
                'yangon' => 'Yangon',
                'mandalay' => 'Mandalay',
                'bago' => 'Bago',
            ]),
        ];
    }

    public function layout(): Layout
    {
        return Layout::make()
            ->density('compact')
            ->toolbar('top')
            ->filters('collapsible');
    }

    public function actions(): array
    {
        return [
            Action::make('create')
                ->label('Add Client')
                ->icon('plus')
                ->route('clients.create'),
            Action::make('export')
                ->label('Export Excel')
                ->icon('download')
                ->route('clients.export'),
        ];
    }

    public function rowActions(): array
    {
        return [
            RowAction::make('view')->route('clients.show'),
            RowAction::make('edit')->route('clients.edit'),
            RowAction::make('delete')->confirm(),
        ];
    }
}
```

`RowAction::route()` should use the model route key by convention and permit an explicit parameter name/key when needed. `delete()->confirm()` describes the UI action only; the application must define the authorized mutation endpoint and actual handler. Exact callback signatures can be finalized during implementation, while maintaining the fluent API above.

### 3.2 Register its data endpoint

`routes/web.php`

```php
use App\DataTables\ClientTable;

Route::get('/clients/data', ClientTable::class)
    ->middleware('auth')
    ->name('clients.data');
```

The base `DataTable` class provides `__invoke(Request $request)` to serve the validated DataTables JSON response. Table authorization is additionally enforced independently of `auth` middleware.

### 3.3 Render it in Blade

```blade
<x-tablekit::table
    :table="App\DataTables\ClientTable::class"
    :url="route('clients.data')"
    id="clients-table"
/>
```

With optional view-level overrides:

```blade
<x-tablekit::table
    :table="App\DataTables\ClientTable::class"
    :url="route('clients.data')"
    id="clients-table"
    density="comfortable"
    toolbar="both"
    filters="inline"
/>
```

The component should render valid HTML, serialized safe configuration, and a deterministic initialization target. It must support multiple independent tables on one page.

### 3.4 Minimal example

The following should work without filters, actions, or layout methods:

```php
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
}
```

## 4. Design system and layout

### Default admin-table layout

- **Density:** `compact`.
- **Toolbar:** `top`.
- **Filters:** `collapsible` (initially closed).
- **Style:** subtle border, restrained spacing, neutral surface, rounded corners, readable typography.
- **Desktop:** actions and tools arranged horizontally.
- **Mobile:** toolbar wraps, table can scroll horizontally, row actions remain usable.

### Visual hierarchy

```text
┌──────────────────────────────────────────────────────────────────────────────┐
│ Clients                                             [+ Add Client]           │
│ Manage and browse client records                                             │
├──────────────────────────────────────────────────────────────────────────────┤
│ [ Search clients...                         ] [ Filters ▾ ] [ Export Excel ]│
│                                                                              │
│  Filters open:                                                                │
│  ┌─────────────────────────────────────────────────────────────────────────┐ │
│  │ [ Status: All ▾ ]  [ Office: All ▾ ]              [ Reset filters ]    │ │
│  └─────────────────────────────────────────────────────────────────────────┘ │
│                                                                              │
│  128 records    2 selected       [ Bulk action ▾ ]           Rows: [25 ▾] │
├────┬─────────────┬─────────────┬───────────┬─────────────┬──────────────────┤
│ □  │ Client      │ Phone       │ Office    │ Status      │ Actions          │
├────┼─────────────┼─────────────┼───────────┼─────────────┼──────────────────┤
│ □  │ Aye Aye Win │ 09...       │ Yangon    │ Active      │ [ View ] [···]   │
│ □  │ Min Thu     │ 09...       │ Mandalay  │ Active      │ [ View ] [···]   │
│ □  │ Su Myat     │ 09...       │ Bago      │ Inactive    │ [ View ] [···]   │
├────┴─────────────┴─────────────┴───────────┴─────────────┴──────────────────┤
│ Showing 1–25 of 128                                    [<] [1] [2] [>]    │
└──────────────────────────────────────────────────────────────────────────────┘
```

### UI elements

- **Search:** global search field in the top toolbar; debounced input with loading indication.
- **Filter toggle:** displays active filter count, expandable panel beneath the toolbar.
- **Filters panel:** type-specific controls and clear/reset operation; optionally apply-on-change or an Apply button when useful.
- **Header actions:** Create, Export, and developer-defined custom actions.
- **Table:** sticky header optional, accessible sort affordances, zebra stripes optional (off by default), predictable empty state.
- **Row actions:** visible primary action plus `...` menu for secondary actions.
- **Checkboxes:** optional row selection, select current page, selected-count toolbar and bulk action menu.
- **Footer:** current range, total filtered rows, page size, and pagination.
- **States:** loading, no data, no search matches, network/server error, disabled action, and unauthorized action.

## 5. Configurable layout

**Every table layout choice must be configurable**, not hardcoded.

### 5.1 Global defaults

`config/tablekit.php`

```php
<?php

return [
    'layout' => [
        'density' => 'compact',
        'toolbar' => 'top',
        'filters' => 'collapsible',
    ],
    'datatables' => [
        'version' => 'auto',
        'assets' => 'external',
    ],
    'table' => [
        'page_length' => 25,
        'page_length_options' => [10, 25, 50, 100],
        'server_side' => true,
        'search_delay' => 350,
        'max_page_length' => 100,
    ],
];
```

### 5.2 Per-table configuration

```php
public function layout(): Layout
{
    return Layout::make()
        ->density('compact')
        ->toolbar('top')
        ->filters('collapsible');
}
```

The `layout()` method is optional. Unspecified properties inherit global defaults, even if `layout()` overrides only one setting.

### 5.3 Blade overrides

```blade
<x-tablekit::table
    :table="ClientTable::class"
    :url="route('clients.data')"
    density="comfortable"
    toolbar="both"
    filters="sidebar"
/>
```

### Supported values

| Setting   | Values                                     | Default       |
| --------- | ------------------------------------------ | ------------- |
| `density` | `compact`, `comfortable`                   | `compact`     |
| `toolbar` | `top`, `bottom`, `both`, `none`            | `top`         |
| `filters` | `collapsible`, `inline`, `sidebar`, `none` | `collapsible` |

- `top`: show configured toolbar above the table.
- `bottom`: show toolbar beneath table.
- `both`: render toolbar in both locations, with synchronized inputs/state.
- `none`: hide toolbar, without disabling programmatic search/filter APIs.
- `collapsible`: hidden until user expands the panel; retain active filters when collapsed.
- `inline`: always-visible controls beneath/within the toolbar region.
- `sidebar`: filter region adjacent to the table; collapse to a drawer on small screens.
- `none`: no filter controls rendered; any configured server-enforced scopes remain active.

### Configuration precedence

**Blade attributes > table-specific `layout()` > `config/tablekit.php` defaults.**

Resolve each layout property independently. Do not overwrite the entire layout structure when overriding a single property.

Invalid config values should fail clearly during development; the UI must not silently choose an arbitrary layout.

## 6. PHP public APIs

### Columns

```php
Column::make('name')->label('Client Name')->searchable()->sortable();
Column::make('created_at')->label('Registered Date')->sortable();
Column::make('status')->label('Status');
Column::make('internal_code')->hidden();
```

Column requirements:

- `make()`, `label()`, `searchable()`, `sortable()`, `hidden()`, optional `width()` and `align()`.
- Distinguish data key from database column: client-supplied names can never become SQL identifiers.
- Support explicit sort/search mapping where displayed fields are computed or aliases.
- Support safe string/date/number formatting with escaping by default.
- Support explicitly opt-in custom HTML rendering, with documented trust and sanitization rules.
- Reuse the same column configuration for heading, client-side initialization, and server-side allowlisting.

### Filters

```php
Filter::text('name')->label('Name');
Filter::select('status', ['active' => 'Active', 'inactive' => 'Inactive']);
Filter::date('registered_at');
Filter::dateRange('created_at')->label('Registered Between');
```

Filter requirements:

- Supported MVP types: text, select, date, date range.
- Explicit validation, known field mapping, and callbacks for complex queries.
- Configurable input label, placeholder, default, and options.
- A filter value must survive pagination/sorting/search redraws until reset.
- Reset removes active UI filters and reloads the dataset without losing table definition.
- Handle timezones and inclusive/exclusive date-range boundaries consistently.

### Actions

```php
Action::make('create')->label('Add Client')->icon('plus')->route('clients.create');
Action::make('export')->label('Export Excel')->route('clients.export');
RowAction::make('view')->route('clients.show');
RowAction::make('edit')->route('clients.edit');
RowAction::make('delete')->confirm();
```

Action requirements:

- Header, row, and bulk actions are separate concepts.
- Labels, icons, ordering, URL/route resolution, visibility, authorization, and optional confirmation.
- The application owns the actual mutation endpoint/handler; TableKit should never silently delete or modify records.
- Never expose unauthorized actions solely based on client-side hiding; authorize backend routes independently.
- Confirmations are accessible and do not submit unsafe GET requests.

### Table behavior

```php
public function configure(): void
{
    $this->searchable()
        ->selectable()
        ->perPage(25)
        ->defaultSort('created_at', 'desc');
}
```

`configure()` is an optional ergonomic proposal. Final design should avoid duplicating settings already owned by `layout()` or the global config. Searchability, selection, and query behavior are separate from visual layout.

## 7. DataTables 1.x / 2.x / 3.x integration

Use the version of DataTables already selected by the host application. The frontend must ship a small compatibility layer for DataTables **1.10+, 2.x, and 3.x**. DataTables 3 is the newest major release as of November 2026; do not present DataTables 2 as the only supported or latest engine. DataTables 1.x / 2.x rely on jQuery, whereas DataTables 3 can run without it.

The server-side processor is **major-version-agnostic**. The browser adapter handles initialization, controls placement, extension compatibility, and differing DOM/CSS. TableKit's `toolbar` and `filters` presets are **not** the upstream DataTables `layout` option; use `dom` only in the 1.x adapter, and use DataTables `layout` where supported in 2.x/3.x.

Version and asset-management settings are documented under **Runtime and version compatibility**; compatibility code must be isolated rather than scattered throughout table components.

### Server-side protocol

Validate and process these DataTables request fields:

- `draw`, `start`, `length`
- `search[value]`
- `order[*][column]` and `order[*][dir]`
- `columns[*]` for mapping/validation only
- custom filter values in a namespaced payload, e.g. `filters[status]`

A successful response follows the DataTables server-side contract:

```json
{
  "draw": 1,
  "recordsTotal": 128,
  "recordsFiltered": 47,
  "data": [
    {
      "id": 1001,
      "name": "Aye Aye Win",
      "phone": "09...",
      "office": "Yangon",
      "status": "Active",
      "created_at": "2026-11-08"
    }
  ]
}
```

- `recordsTotal` counts records before _user-controlled search and UI filters_, while retaining required authorization/tenant/data scopes from `query()`.
- `recordsFiltered` counts records after all user-controlled filters/search but before pagination.
- `draw` must be parsed as an integer, never reflected as raw user input.
- Disallow regex searching by default; do not interpret raw user input as SQL or regular expressions.
- The server, not the browser, determines which columns can be searched or sorted.
- Set a configurable maximum `length`; reject or clamp `-1` in server-side mode.
- Apply deterministic ordering so pages do not flicker or duplicate records.

Reference: https://datatables.net/manual/core/server-side

## 8. Selection and bulk operations

MVP selection mode: **current page only**, with explicit UX and server-side enforcement.

- Header checkbox selects/deselects rows on the current page.
- `getSelectedIds()` returns selected IDs for the chosen selection model.
- `clearSelection()` clears the current selection.
- Filter, sort, page, and redraw transitions must follow a documented selection policy. MVP default: clear selection when page/filter/search changes to avoid stale or hidden selections.
- Bulk actions must use POST/PATCH/DELETE as appropriate, include CSRF protection, validate submitted IDs, and reauthorize each record or the bulk scope server-side.
- No implicit “select all matching 100,000 records” behavior in the MVP.

Possible JS adapter surface:

```js
const table = TableKit.get('clients-table');

const ids = table.getSelectedIds();
table.clearSelection();
table.reload();
table.resetFilters();
```

`TableKit.get()` is a proposed convenience API; maintain easy access to the underlying DataTables instance for advanced customization.

## 9. Performance expectations

- Apply `limit`/`offset` in SQL before fetching data; never hydrate an entire dataset to slice it in PHP.
- Avoid `SELECT *` when only a subset of columns is needed, while including keys/fields required for actions and formatting.
- Avoid N+1 queries by supporting explicit eager loading.
- Build global search from an allowlist, group OR conditions correctly, and avoid global-search predicates leaking outside tenant/authorization scopes.
- Support column-to-index/field mapping explicitly for ambiguous or joined tables.
- Keep counts accurate; offer optional count optimizations only when semantics remain clear.
- Escape wildcard characters appropriately for LIKE conditions as supported by the SQL dialect.
- Debounce global search (suggested default: 350 ms).
- Prevent excessive page sizes and reject invalid sort indices/directions.
- Test on large seeded datasets and review query plans for typical filters.
- No automatic JSON loading of all records into the browser.

## 10. Security and authorization

- Do not trust column names, sort flags, route params, or bulk IDs supplied by the client.
- Authorize access to the table and individually sensitive actions using policies/Gates or application-supplied callbacks.
- Escape cell values and user input by default; mark raw HTML as explicitly unsafe/opt-in.
- Avoid exposing hidden/private model attributes just because they exist on the Eloquent model.
- Return safe validation/error messages; never expose SQL traces in production.
- Use normal Laravel CSRF middleware for state-changing operations.
- Respect tenant/office scopes inside the base query and all count queries.
- Enforce export authorization and the same intended filters/scope on the server.

## 11. Laravel package structure

```text
laravel-tablekit/
├── composer.json
├── config/
│   └── tablekit.php
├── src/
│   ├── DataTable.php
│   ├── Layout.php
│   ├── TableKitServiceProvider.php
│   ├── Actions/
│   │   ├── Action.php
│   │   ├── RowAction.php
│   │   └── BulkAction.php
│   ├── Columns/
│   │   └── Column.php
│   ├── Filters/
│   │   └── Filter.php
│   ├── Http/
│   │   ├── TableRequest.php
│   │   └── DataTableResponse.php
│   ├── Processing/
│   │   └── EloquentProcessor.php
│   ├── View/
│   │   └── Components/
│   │       └── Table.php
│   └── Console/
│       └── MakeDataTableCommand.php
├── resources/
│   ├── views/
│   │   └── components/
│   │       └── table.blade.php
│   ├── js/
│   │   ├── tablekit.js
│   └── adapters/
│       ├── datatables-v1.js
│       ├── datatables-v2.js
│       └── datatables-v3.js
│   └── css/
│       └── tablekit.css
├── tests/
│   ├── Feature/
│   └── Unit/
├── docs/
├── README.md
└── SPEC.md
```

Structure is a target, not a requirement to add an empty file for each entry. Keep classes cohesive and avoid needless abstractions until justified.

### Package conventions

- Register service provider through Composer's Laravel auto-discovery.
- Merge default config, publish config and assets, register Blade component and Artisan command.
- Load view resources using Laravel package namespaces.
- Make config caching and route caching work.
- Publish frontend assets or document bundler-based imports; avoid silently relying on CDN scripts.
- Document external peer assets, jQuery requirements for DataTables 1.x / 2.x, optional jQuery for 3.x, and how to pin compatible versions of core and extensions.
- Support **PHP 7.4+ and Laravel 7+** subject to compatible Composer resolution and verified CI combinations; Laravel 7 / PHP 7.4 is a first-class test target, not a legacy best-effort exception. Laravel 13 is also an intended test target on its supported PHP versions.

References: https://laravel.com/docs/7.x/packages and https://laravel.com/docs/13.x/packages

## 12. CLI and installation

Suggested installation:

```bash
composer require thuraaung/tablekit
php artisan vendor:publish --tag=tablekit-config
php artisan tablekit:make ClientTable
```

The generator should create `app/DataTables/ClientTable.php` with a clean, small starter class (not a large boilerplate controller). Consider supporting `--model=Client` and `--force` options.

Document frontend asset installation separately for DataTables 1.10+ / 2.x / 3.x, including jQuery on 1.x/2.x, and one-time TableKit JS/CSS initialization. Users choose the actual installed DataTables version through npm, existing local assets, or script tags; the PHP setting only selects/validates adapter compatibility. Do not claim the package is published on Packagist until it is.

## 13. Demo application

Create a minimal demonstration page with the UI design from section 4:

- A client table with name, phone, office, status, and registered date.
- Search, two filters, compact rows, collapsible filter panel, top toolbar, and pagination.
- Create button, export button, primary View action, secondary Edit/Delete menu.
- Checkbox selection and example bulk action.
- A matching **Create Client** application form with Client Name, Phone, Office, Status, Save, and Cancel, for visual consistency only.
- Toggle density, toolbar position, and filter mode in the demo to verify configuration behavior.

The form should use normal Laravel validation, request classes, and app controllers; it is not a dependency of TableKit.

## 14. Testing and acceptance criteria

### Functional

- [ ] A table works with only `query()` and `columns()`.
- [ ] A Laravel invokable route returns valid DataTables 1.10+ / 2.x / 3.x JSON.
- [ ] Search, sort, paginate, and filters work together with accurate counts.
- [ ] Every layout value renders and behaves correctly.
- [ ] Global defaults are inherited when table-level methods are omitted.
- [ ] Blade overrides take precedence property by property.
- [ ] Collapsing filters does not clear their values.
- [ ] Resetting filters restores unfiltered results within application scopes.
- [ ] Header/row/bulk actions respect visibility and authorization.
- [ ] Multiple table components can coexist on one page.
- [ ] Empty/loading/error states are usable and accessible.
- [ ] The generator creates a valid working table class.

### Security

- [ ] Unexpected sort fields, column indices, and raw SQL fragments are rejected.
- [ ] XSS strings in the table/query do not execute.
- [ ] Invalid pagination inputs and oversized `length` are bounded safely.
- [ ] Tenant and authorization filters remain intact during global searches and counts.
- [ ] Mutations and bulk actions are protected by CSRF and server-side permissions.

### Performance

- [ ] Standard table requests execute bounded SQL queries, not entire-table fetches.
- [ ] Relationship display/search is possible without N+1 behavior.
- [ ] Paging and filtering on large datasets remain database-backed.
- [ ] Debounced search avoids a request for every keystroke.

### Compatibility

- [ ] A **real PHP 7.4 + Laravel 7** installation passes Composer resolution, rendering, AJAX, filters, and generator tests.
- [ ] PHP 7.4 source/stubs pass a PHP 7.4 syntax check; no PHP-8-only language/runtime features leak into supported paths.
- [ ] Representative compatible PHP/Laravel combinations pass CI: 7.4/7, 7.4/8, 8.0/9, 8.1/10, 8.2/11, 8.2/12, 8.3/13 (expand and adjust based on upstream support and Composer constraints).
- [ ] DataTables **1.10.x**, **1.13.x**, **2.x**, and **3.x** pass frontend smoke/E2E tests for initialization, search, filters, sorting, pagination, and loading/error states.
- [ ] `auto` detects supported installed versions, and explicit major/full-version mismatches show a clear error.
- [ ] `datatables.version` is configurable globally, per table, and from Blade without affecting visual layout precedence.
- [ ] Unsupported DataTables <1.10 and conflicting major versions on the same page are rejected.
- [ ] Core features do not depend on an incompatible DataTables extension, and DataTables 3 works without jQuery.
- [ ] Package supports config caching, route caching, and published views/assets on the oldest supported Laravel version.

## 15. Implementation plan

### Phase 1 — Core

Build PHP 7.4/Laravel 7-compatible package scaffolding, service provider, DataTable base class, Column, request validator, Eloquent processor, and shared DataTables 1.10+ / 2.x / 3.x JSON protocol tests. Verify the PHP 7.4 + Laravel 7 environment at the start, not at release time.

### Phase 2 — Frontend and layout

Add a Laravel 7-compatible Blade component, version detection and separate DataTables 1.x / 2.x / 3.x JS adapters, scoped CSS, global/table/Blade config resolution, all density/toolbar/filter modes, and states. Test with existing legacy DataTables assets and with jQuery-free DataTables 3.

### Phase 3 — Filters and actions

Implement typed filters, toolbar actions, row actions, authorization integration, and current-page selection/bulk action hooks.

### Phase 4 — Developer experience

Add `tablekit:make` generator, documentation, demo app (including Create Client form), compatibility tests, examples, and release checklist.

### Phase 5 — Optional enhancements

Add export integrations, saved states, advanced relationship queries, styling adapters, and explicit select-all-filtered semantics only after MVP behavior is stable.

## 16. Non-negotiable decisions

1. Laravel package, not a standalone JavaScript-only plugin.
2. **PHP 7.4 minimum and Laravel 7 minimum**; package source and generated code must remain compatible.
3. **User-selectable DataTables 1.10+ / 2.x / 3.x**, with `auto` detection by default; the host app owns the actual JS/CSS assets.
4. Eloquent is the default data source.
5. Server-side processing is enabled by default and uses one protocol for all supported DataTables majors.
6. A basic table requires only `query()` and `columns()`.
7. Layout options **must be configurable** globally, on the table class, and from Blade.
8. Default UI: **compact + top toolbar + collapsible filters**.
9. Layout priority: **Blade > DataTable > global config**.
10. DataTables version priority: **Blade > DataTable > global config**, independent from visual layout.
11. Filters, header actions, row actions, and selection are optional.
12. Keep the core package focused on tables; the Create Client form belongs to the demo/application.
13. Prefer small, readable Laravel-style APIs; avoid duplicated responsibilities.
14. Favor performance, accessibility, and secure server-side processing over convenience shortcuts.

## 17. Reference documentation

- DataTables server-side processing (1.10+): https://datatables.net/manual/core/server-side
- DataTables 2 layout API: https://datatables.net/reference/option/layout
- DataTables 1.x `dom` option: https://datatables.net/reference/option/dom
- DataTables 1 -> 2 upgrade notes: https://datatables.net/download/upgrade/core/2.0/upgrade
- DataTables 2 -> 3 upgrade notes: https://datatables.net/download/upgrade/core/3.0/upgrade
- DataTables 3 release: https://datatables.net/blog/2026/datatables-3
- DataTables releases/versions: https://datatables.net/download/release
- Laravel 7 package development: https://laravel.com/docs/7.x/packages
- Laravel 7 Blade components: https://laravel.com/docs/7.x/blade
- Laravel 13 package development: https://laravel.com/docs/13.x/packages
