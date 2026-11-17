# Filters

Filters add validated query controls to a table. Add this import:

```php
use Thuraaung\TableKit\Filters\Filter;
```

Then add a `filters()` method. This example needs `name`, `status`,
`office`, and `created_at` fields:

```php
public function filters(): array
{
    return [
        Filter::text('name')->label('Client Name')->placeholder('Type a name'),
        Filter::select('status', [
            'active' => 'Active',
            'inactive' => 'Inactive',
        ]),
        Filter::select('office', [
            'yangon' => 'Yangon',
            'mandalay' => 'Mandalay',
            'bago' => 'Bago',
        ]),
        Filter::dateRange('created_at')->label('Registered Between'),
    ];
}
```

## Filter types

| Definition | Accepted value | Query behavior |
| --- | --- | --- |
| `Filter::text('name')` | String, up to 500 characters | Literal contains search |
| `Filter::select('status', $options)` | One of the option keys | Exact match |
| `Filter::date('created_at')` | `Y-m-d` string | Records within that day |
| `Filter::dateRange('created_at')` | `from` / `to` date array | Records within the range |

Every filter has a unique key. By default it maps to the same database
column. Use `database('clients.status')` for an explicit mapping.

| Method | Purpose |
| --- | --- |
| `label(string)` | Set the input label |
| `placeholder(string)` | Set text input hint or select's empty option |
| `defaultValue(value)` | Apply a value when the filter is omitted |
| `database(string)` | Map to a plain SQL column identifier |
| `rules(array)` | Add rules for nonempty scalar values |
| `timezone(input, storage = 'UTC')` | Set date input/storage timezones |
| `applyUsing(callback)` | Apply a custom predicate to a nested builder |

## Defaults and reset

```php
Filter::select('status', [
    'active' => 'Active',
    'inactive' => 'Inactive',
])->defaultValue('active');
```

This initially shows active clients. The empty select option clears that
filter. **Reset filters** clears all UI filters, including defaults, and
returns to page one. It preserves global search and sorting.

Filter values survive page, sort, and search redraws. Closing the filter
panel also keeps its values. Required tenant/authorization scopes in
`query()` remain active when UI filters are reset.

## Dates and timezones

```php
Filter::date('registered_day')
    ->database('created_at')
    ->timezone('Asia/Bangkok', 'UTC');

Filter::dateRange('created_at')
    ->timezone('Asia/Bangkok', 'UTC')
    ->defaultValue([
        'from' => '2026-11-01',
        'to' => '2026-11-16',
    ]);
```

The first timezone is how users interpret the calendar dates; the second
is the database timestamp timezone. Both default to UTC. Inputs are
`Y-m-d` regardless of display formatting on a column.

The end date includes the whole day. Internally, TableKit uses `>=` the
start midnight and `<` the next local midnight, converted to storage time.
This also handles daylight-saving changes. Either range bound may be empty.
Invalid dates and reversed ranges return 422.

## Add validation or a custom query

`rules()` adds Laravel validation rules for nonempty scalar values:

```php
Filter::text('name')->rules(['max:100']);
```

Empty values clear a filter before these extra rules run. `rules()` does
not add custom validation to the date-range array; its built-in date rules
still apply.

For a relationship filter, also import the Eloquent builder:

```php
use Illuminate\Database\Eloquent\Builder;
```

```php
Filter::text('office_name')
    ->rules(['max:100'])
    ->applyUsing(function (Builder $query, $value) {
        $query->whereHas('officeRecord', function ($office) use ($value) {
            $office->where('name', $value);
        });
    });
```

This assumes the `officeRecord()` relationship from your application. The
callback receives `(Builder $query, $validatedValue)`. Add predicates to
that builder; do not fetch records or start a new unscoped query. Callback
OR conditions stay grouped under the table's base query scopes.

## Request values and programmatic control

The browser sends filter values in a separate namespace:

```text
filters[status]=active
filters[created_at][from]=2026-11-01
filters[created_at][to]=2026-11-16
```

Unknown keys and invalid values return 422. An omitted key uses its default;
an explicit empty value clears it.

You can also call `table.setFilter('status', 'active')` or
`table.setFilter('created_at', { from: '2026-11-01', to: '2026-11-16' })`.
See [JavaScript API](javascript.md). To change where controls appear, see
[Configuration](configuration.md).

[Documentation index](README.md)
