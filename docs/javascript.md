# JavaScript API

TableKit initializes rendered components automatically. For explicit control,
load the engine, adapter, and TableKit scripts before your application code.
Give the component a stable ID:

```blade
<x-tablekit::table
    :table="App\DataTables\ClientTable::class"
    :url="route('clients.data')"
    id="clients-table"
/>
```

## Get the instance

After the component has initialized:

```js
var table = TableKit.get('clients-table');
```

`get()` returns `undefined` if the table has not initialized or initialization
failed. For a regular page, place your application script after the TableKit
script and wait for DOM ready:

```js
document.addEventListener('DOMContentLoaded', function () {
  var table = TableKit.get('clients-table');
  if (!table) return;

  document.getElementById('refresh-clients').addEventListener('click', function () {
    table.reload();
  });
});
```

This example expects an application button with `id="refresh-clients"`.
For code that may run after DOM ready, call `TableKit.init(document)` first;
initialization is idempotent for an existing component.

## Instance methods

| Method/property | Behavior |
| --- | --- |
| `reload()` | Reload rows, retain the page, clear selection |
| `search(value)` | Search immediately, sync toolbar inputs, return to page one |
| `setFilter(key, value)` | Update a known filter and its controls, reload page one |
| `resetFilters()` | Clear UI filters/defaults, keep search/sort, reload page one |
| `getSelectedIds()` | Return a copy of current-page selected IDs as strings |
| `clearSelection()` | Uncheck rows and reset selection controls |
| `dataTable` | Underlying public DataTables API instance |
| `element` | The TableKit section element |
| `destroy()` | Abort requests, remove listeners, destroy the engine instance |

Use these as separate operations as needed:

```js
var table = TableKit.get('clients-table');

table.search('Aye');
table.setFilter('status', 'active');
table.setFilter('created_at', {
  from: '2026-11-01',
  to: '2026-11-16'
});
```

Those filter keys must exist in the PHP table. `setFilter()` throws for an
unknown key. Set a filter to `''` to clear it. To clear both search and
filters, call `table.search('')` and `table.resetFilters()`; these are two
operations and can issue two requests.

Typed global search is debounced using `table.search_delay`; programmatic
`search()` is immediate. Each new request cancels a superseded request.

## Use the DataTables API

```js
var table = TableKit.get('clients-table');

table.dataTable.page('next').draw('page');
table.dataTable.ajax.reload(null, false);
```

Use public APIs compatible with your installed engine. Avoid internal
settings or jQuery-only selectors if the app also supports DataTables 3.
TableKit does not automatically install or configure extensions.

## Multiple or dynamically inserted tables

Each component needs its own ID. Layout and filter state remain independent,
while every table shares the same installed DataTables core.

After inserting Blade-rendered components into the document:

```js
TableKit.init(document);
var table = TableKit.get('clients-table');
```

You may pass a containing element instead of `document`. `init(scope)` scans
its descendants for TableKit components, so pass their parent container.
Do not initialize the same HTML table separately through DataTables.

Before removing an initialized component:

```js
var table = TableKit.get('clients-table');
if (table) table.destroy();
```

Then remove its markup using your app's normal UI lifecycle. A subsequent
`get()` returns `undefined` until the component is initialized again.

## States and errors

Loading sets `aria-busy` and a live status message. Empty results use native
DataTables empty/no-match messages. Failed HTTP requests or invalid JSON
show a safe alert; retry with `reload()` after fixing the cause.

Version errors appear in the table and browser console. For debugging,
`TableKit.resolveVersion('auto')` returns the loaded `actual` version and
`major`, or throws an error if the engine is missing or incompatible.

Selection clears on every AJAX draw. Use the provided bulk forms and
[server helper](actions.md) rather than trusting IDs from JavaScript alone.

[Documentation index](README.md)
