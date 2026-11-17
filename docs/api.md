# API reference

This page indexes the application-facing API. Each linked guide contains
examples, imports, and required application setup.

## DataTable extension points

Extend `Thuraaung\TableKit\DataTable`.

| Method | Required | Return / purpose |
| --- | --- | --- |
| `query()` | Yes | Eloquent `Builder`; authorized base dataset |
| `columns()` | Yes | Array of `Column` definitions |
| `authorize(Request $request)` | No | Boolean; defaults to true; checked before rendering/AJAX |
| `configure()` | No | Configure search, selection, page size, default sort |
| `layout()` | No | `Layout`; unspecified properties inherit global defaults |
| `dataTablesVersion()` | No | String version assertion; defaults to global `auto` |
| `filters()` | No | Array of `Filter` definitions |
| `actions()` | No | Array of header `Action` definitions |
| `rowActions()` | No | Array of `RowAction` definitions |
| `bulkActions()` | No | Array of `BulkAction` definitions |
| `requiredColumns()` | No | Extra SQL fields for policies/formatters/actions |

Use `configure()` rather than overriding the constructor. `__invoke(Request)`
is supplied by the base class for a Laravel data route.

## Fluent definitions

| Class | Methods / guide |
| --- | --- |
| `Columns\Column` | `make`, `label`, `database`, `searchable`, `sortable`, `hidden`, `width`, `align`, `date`, `number`, `format`, `html`, `requires`; [guide](columns.md) |
| `Filters\Filter` | `text`, `select`, `date`, `dateRange`, `label`, `placeholder`, `defaultValue`, `database`, `rules`, `timezone`, `applyUsing`; [guide](filters.md) |
| `Actions\Action` | `make`, `label`, `icon`, `order`, `route`, `url`, `method`, `confirm`, `visible`, `authorized`, `can`; [guide](actions.md) |
| `Actions\RowAction` | Same fluent methods, with model route-key resolution; [guide](actions.md) |
| `Actions\BulkAction` | Same methods; defaults to POST; `authorizedModels(table, request, ability)` validates selected records; [guide](actions.md) |
| `Layout` | `make`, `density`, `toolbar`, `filters`; [guide](configuration.md) |

Behavior methods on `DataTable` are `searchable(bool)`, `selectable(bool)`,
`perPage(int)`, and `defaultSort(key, direction)`. The component's attributes
and every config key are listed in [Configuration](configuration.md).

## Callback arguments

| Callback | Arguments | Expected result |
| --- | --- | --- |
| Column `format()` / `html()` | Model | Display value, converted to a string |
| Filter `applyUsing()` | Nested Eloquent Builder, validated value | Add query predicates |
| Action `visible()` / `authorized()` | Model or null, Request | Boolean |
| Action route parameter callback | Model or null | Route parameter array |
| Action `url()` callback | Model or null | Safe URL string |

## JavaScript surface

`TableKit.init(scope)`, `TableKit.get(id)`, and
`TableKit.resolveVersion(expectedVersion)` are global helpers.
Instances expose `reload`, `search`, `setFilter`, `resetFilters`,
`getSelectedIds`, `clearSelection`, `destroy`, `dataTable`, and `element`.
See [JavaScript API](javascript.md) for timing and return behavior.

## Request protocol

All supported engines use the same server-side protocol.

| Input | Accepted behavior |
| --- | --- |
| `draw` | Integer 0–2147483647; defaults to 0 |
| `start` | Integer 0–2147483647; defaults to 0 |
| `length` | Positive integer, capped at max; `-1` also clamps to max |
| `search[value]` | String up to 500 characters; defaults to empty |
| `search[regex]` | Regex is disabled; a true request is rejected |
| `order[*][column]` | Index of a server-defined sortable column |
| `order[*][dir]` | `asc` or `desc` |
| `columns[*]` | Optional mapping validation; names/flags never grant SQL access |
| `filters[key]` | Values matching the table's filter definitions |

Length zero, other negative lengths, invalid sort targets/directions,
regex requests, per-column searches, and unknown/invalid filters return
422. The pre-1.10 `sEcho` protocol is unsupported.

A normal response looks like:

```json
{
  "draw": 1,
  "recordsTotal": 128,
  "recordsFiltered": 47,
  "data": [
    {
      "name": "Aye Aye Win",
      "phone": "0912345678"
    }
  ]
}
```

`recordsTotal` retains base query scopes before UI filters/search.
`recordsFiltered` is counted after filters/search and before paging.
Cell values are strings escaped for HTML, except explicit `html()` output.

Selection adds `_tk_select` markup to each row and a top-level `pageToken`.
Row actions add `_tk_actions` markup. These reserved fields are managed by
TableKit. The selection column is included when validating sort indices.
The rest of the model is not serialized.

For access control and safe mutation handling, read
[Authorization and security](security.md).

[Documentation index](README.md)
