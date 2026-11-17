# Configuration and layout

Publish the defaults into your Laravel app:

```sh
php artisan vendor:publish --tag=tablekit-config
```

Edit `config/tablekit.php`. This is the complete default configuration:

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

| Setting | Meaning |
| --- | --- |
| `page_length` | Initial number of rows; must be positive |
| `page_length_options` | Positive integer options shown in the Rows control |
| `max_page_length` | Server limit, even if a request asks for more |
| `server_side` | Must remain `true`; client-side mode is not implemented |
| `search_delay` | Delay in milliseconds after typing before search runs |
| `datatables.version` | `auto`, a major, or an exact version |
| `datatables.assets` | Must remain `external`; your app supplies core assets |

Page length is capped at the maximum. Options above the maximum are hidden.
Invalid settings fail with an explicit exception.

## Layout choices

| Property | Value | Result |
| --- | --- | --- |
| `density` | `compact` | Smaller cell spacing; default |
| `density` | `comfortable` | More cell spacing |
| `toolbar` | `top` | Controls above the table; default |
| `toolbar` | `bottom` | Controls below the table |
| `toolbar` | `both` | Both positions, with synchronized search/row controls |
| `toolbar` | `none` | Hide the toolbar; JavaScript APIs remain available |
| `filters` | `collapsible` | Initially closed filter popover; default |
| `filters` | `inline` | Always-visible filter panel |
| `filters` | `sidebar` | Side panel on desktop, toggleable region on mobile |
| `filters` | `none` | Hide filter controls |

A filter panel is rendered only if the table defines filters. The
collapsible popover closes on Escape or an outside click without clearing
values. Hiding filter controls does not remove server scopes
or configured filter defaults. Native info/pagination stays available even
when the toolbar is hidden.

## Set a table's layout

Add the import and method to your table class:

```php
use Thuraaung\TableKit\Layout;
```

```php
public function layout(): Layout
{
    return Layout::make()
        ->density('comfortable')
        ->toolbar('both');
}
```

The filter mode still inherits the global value because this method does
not override it.

## Override from Blade

```blade
<x-tablekit::table
    :table="App\DataTables\ClientTable::class"
    :url="route('clients.data')"
    density="compact"
    toolbar="top"
    filters="inline"
    datatable-version="3"
/>
```

Each layout property resolves separately: **Blade > table > global**.
The engine version uses the same precedence, independently of layout.
For example, overriding only `density` does not reset `toolbar`.

## Configure table behavior

Add this method to a table that defines a sortable `created_at` column:

```php
public function configure(): void
{
    $this->searchable(true)
        ->selectable(true)
        ->perPage(25)
        ->defaultSort('created_at', 'desc');
}
```

| Method | Default | Purpose |
| --- | --- | --- |
| `searchable(bool)` | `true` | Enable global search; false also ignores incoming global search |
| `selectable(bool)` | `false` | Add current-page checkboxes |
| `perPage(int)` | Global `page_length` | Set the initial page size |
| `defaultSort(key, direction)` | None | Sort by a declared sortable data key; `asc` or `desc` |

Without a default sort, existing query ordering is retained. The processor
adds the model primary key as a tie breaker. Selection and query behavior
belong in `configure()`, while appearance belongs in `layout()`.

## Blade component attributes

| Attribute | Required | Meaning |
| --- | --- | --- |
| `:table` | Yes | DataTable class name or instance |
| `:url` | Yes | Data endpoint URL, normally `route(...)` |
| `id` | No | Unique HTML ID; generated when omitted |
| `title` | No | Heading and accessible table title |
| `description` | No | Text below the heading |
| `density` | No | Layout override |
| `toolbar` | No | Layout override |
| `filters` | No | Layout override |
| `datatable-version` | No | Engine assertion; see the frontend guide |

IDs must start with a letter and contain letters, digits, `_`, or `-`.
For multiple tables, give each a distinct ID or omit IDs. Wrap the component
in your own element to add application-specific classes or markup.

## Customize views and refresh caches

To customize the HTML templates:

```sh
php artisan vendor:publish --tag=tablekit-views
```

Published views live in `resources/views/vendor/tablekit`. Keep the
`data-tk-*` hooks and configuration attributes used by the JavaScript.
Published views override package views, so review them after package updates.

After configuration or view changes:

```sh
php artisan config:clear
php artisan view:clear
```

If your deployment uses cached configuration, rebuild it with
`php artisan config:cache` after editing the config.

[Documentation index](README.md)
