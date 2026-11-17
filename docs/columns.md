# Columns and queries

Add this import to your table class:

```php
use Thuraaung\TableKit\Columns\Column;
```

Only declared columns are sent to the browser. A model's other attributes
are not serialized automatically.

## Define columns

```php
public function columns(): array
{
    return [
        Column::make('name')->label('Client Name')->searchable()->sortable(),
        Column::make('phone'),
        Column::make('status')->sortable(),
        Column::make('created_at')->label('Registered Date')->date()->sortable(),
    ];
}
```

This example needs `name`, `phone`, `status`, and `created_at` database
columns. Search and sorting are off for each column until enabled.

| Method | Use |
| --- | --- |
| `make('name')` | Create a unique data key; defaults to the same SQL column |
| `label('Client Name')` | Set the column heading |
| `database('clients.name')` | Map the key to a real SQL column |
| `searchable(bool = true)` | Include in global search |
| `sortable(bool = true)` | Allow ordering by this field |
| `hidden(bool = true)` | Hide in the UI; the value is still sent |
| `width('8rem')` | Set a width in `px`, `%`, `rem`, or `em` |
| `align('right')` | Use `left`, `center`, or `right` alignment |
| `date('Y-m-d')` | Format a date or timestamp |
| `number(2)` | Format a number with two decimal places |
| `format(callback)` | Return escaped display text from a model |
| `html(callback)` | Return trusted HTML without automatic escaping |
| `requires(array)` | Set the fields needed by a formatter |

Keys must be simple identifiers. Dotted keys and reserved `_tk_*` keys are
not supported. SQL mappings accept a plain column or `table.column`;
expressions and browser-supplied SQL identifiers are rejected.

## Map a displayed key to a database field

```php
Column::make('registered')
    ->database('clients.created_at')
    ->label('Registered Date')
    ->date('d M Y')
    ->sortable();
```

The browser receives `registered`. The server sorts by
`clients.created_at`. The mapping is used for both search and sort.
A default sort would use `defaultSort('registered', 'desc')`.

## Format text or HTML

If your model has `first_name` and `last_name`:

```php
Column::make('full_name')
    ->requires(['first_name', 'last_name'])
    ->format(function ($client) {
        return $client->first_name.' '.$client->last_name;
    });
```

The callback receives the Eloquent model. Its output is escaped, so text
such as `<script>` displays as text. `requires()` replaces the default
field dependency; include every field your callback reads.

For a trusted HTML badge:

```php
Column::make('status_badge')
    ->requires(['status'])
    ->html(function ($client) {
        return '<strong>'.e($client->status).'</strong>';
    });
```

Escape dynamic text yourself inside `html()`. Sanitize user-authored markup
before returning it. Do not add another DataTables text renderer to normal
columns; they are already escaped by the server.

A computed field needs a real `database()` mapping before it can be searched
or sorted. Otherwise leave those capabilities disabled.

## Select extra model fields

TableKit normally selects the model primary key and the fields required by
your columns. Row actions also include the model's usual route key.
Add other dependencies with:

```php
public function requiredColumns(): array
{
    return ['tenant_id', 'uuid'];
}
```

This is important for policies, route callbacks, and formatters that read
fields not displayed in the table. Extra dependencies are fetched for the
server model but are not added to JSON unless declared as columns.

If `query()` already uses `select()`, your app owns the whole select list.
Include the primary key, route keys, policy fields, formatter fields, and
relationship foreign keys explicitly.

`hidden()` is a display setting. To keep a value private, omit it from
`columns()` entirely.

## Display a relationship without extra queries per row

This example needs an `Office` model with `id` and `name`, plus an
`office_id` foreign key on clients. Add this relationship method to your
Client model:

```php
public function officeRecord()
{
    return $this->belongsTo(\App\Models\Office::class, 'office_id');
}
```

Then add these methods to the table, with `Client` and `Builder` imported
as in the starter:

```php
public function query(): Builder
{
    return Client::query()->with('officeRecord');
}

public function columns(): array
{
    return [
        Column::make('name')->searchable(),
        Column::make('office_name')
            ->requires(['office_id'])
            ->format(function ($client) {
                return $client->officeRecord ? $client->officeRecord->name : '';
            }),
    ];
}
```

`with()` eager loads the relationship for the page. TableKit does not infer
relationship search or sort SQL. Use a configured filter callback for
relationship filtering; see [Filters](filters.md).

## Query rules

Return an Eloquent `Builder` from `query()`. Put tenant, office, and other
required access scopes there so counts and rows share the same limits.
Do not call `get()`, `paginate()`, `limit()`, or `offset()` in the base query.

TableKit performs two counts and one bounded page query, plus any eager-load
queries. Global search predicates are grouped under your base scopes.
Requested sort ordering replaces query ordering, with a primary-key tie
breaker for stable pages.

Joined queries need explicit qualified fields and, where necessary, an
explicit select list with aliases. The current processor expects one row
per model key. Grouped aggregates, custom data sources, and inferred
relationship sorting are outside this contract.

[Documentation index](README.md)
