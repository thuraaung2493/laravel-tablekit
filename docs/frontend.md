# Frontend setup

Use one DataTables core version on a page. TableKit supports three adapter
families; your app installs and serves the core and any matching extensions.

| Installed core | TableKit adapter | jQuery |
| --- | --- | --- |
| DataTables 1.10+ / 1.13 | `datatables-v1.js` | Required |
| DataTables 2.x | `datatables-v2.js` | Required |
| DataTables 3.x | `datatables-v3.js` | Not required |

## Use local script files

First publish TableKit assets from your Laravel app:

```sh
php artisan vendor:publish --tag=tablekit-assets
mkdir -p public/vendor/datatables
```

Choose **one** of the following sets of commands. These are tested version
examples, not floating latest-version claims.

### DataTables 3

```sh
npm install --save-exact datatables.net@3.1.5
cp node_modules/datatables.net/js/dataTables.min.js public/vendor/datatables/dataTables.min.js
```

Load these scripts, in order, after your table markup:

```blade
<script src="{{ asset('vendor/datatables/dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/tablekit/js/adapters/datatables-v3.js') }}"></script>
<script src="{{ asset('vendor/tablekit/js/tablekit.js') }}"></script>
```

### DataTables 2

```sh
npm install --save-exact datatables.net@2.3.8 jquery@3.7.1
cp node_modules/jquery/dist/jquery.min.js public/vendor/datatables/jquery.min.js
cp node_modules/datatables.net/js/dataTables.min.js public/vendor/datatables/dataTables.min.js
```

```blade
<script src="{{ asset('vendor/datatables/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/tablekit/js/adapters/datatables-v2.js') }}"></script>
<script src="{{ asset('vendor/tablekit/js/tablekit.js') }}"></script>
```

### DataTables 1

```sh
npm install --save-exact datatables.net@1.13.11 jquery@3.7.1
cp node_modules/jquery/dist/jquery.min.js public/vendor/datatables/jquery.min.js
cp node_modules/datatables.net/js/jquery.dataTables.min.js public/vendor/datatables/dataTables.min.js
```

Use the DataTables 2 script block above, changing only the adapter to
`datatables-v1.js`. If the app already loads jQuery or DataTables, reuse
those files and avoid duplicate script tags.

The compatibility tests also cover 1.10.25 for existing legacy sites.
That old fixture has a known XSS advisory; see [Compatibility](compatibility.md).

### Styles

Include TableKit CSS once in the page head:

```blade
<link rel="stylesheet" href="{{ asset('vendor/tablekit/css/tablekit.css') }}">
```

TableKit supplies scoped default styles. If your app also uses DataTables
core CSS or a styling integration, install a version matching its core and
include it once. TableKit does not install Bootstrap, Tailwind, Buttons,
Responsive, Select, or their assets.

## Use an asset bundler

For an app with a configured JavaScript bundler, copy TableKit source files
into the app's asset directory:

```sh
mkdir -p resources/js/tablekit resources/css
cp -R vendor/thuraaung/tablekit/resources/js/. resources/js/tablekit/
cp vendor/thuraaung/tablekit/resources/css/tablekit.css resources/css/tablekit.css
```

For DataTables 3, add this to `resources/js/app.js`:

```js
import DataTable from 'datatables.net';
import '../css/tablekit.css';

window.DataTable = DataTable;

import('./tablekit/adapters/datatables-v3.js').then(function () {
  return import('./tablekit/tablekit.js');
});
```

For DataTables 2 or 1.13, also expose the same installed jQuery instance:

```js
import $ from 'jquery';
import DataTable from 'datatables.net';
import '../css/tablekit.css';

window.jQuery = $;
window.$ = $;
window.DataTable = DataTable;

import('./tablekit/adapters/datatables-v2.js').then(function () {
  return import('./tablekit/tablekit.js');
});
```

Change `v2` to `v1` for 1.13. Load the built `app.js` through your existing
Vite/Mix setup. Remove the separate TableKit/core script tags from the page.
Dynamic imports ensure the globals and adapter are ready before TableKit
initializes. Very old 1.10 bundles should use the local-script setup unless
you have verified their CommonJS initialization with your build tools.

After a package update, recopy these sources and rebuild. Use your app's
existing build command, such as `npm run build` or `npm run dev`.

## Select or validate a version

The default is `auto`: TableKit reads the loaded engine's version. Set a
major (`1`, `2`, `3`) to require that family, or a full version to require
an exact match. A setting validates existing assets; it does not install them.

Global, in `config/tablekit.php`:

```php
'datatables' => [
    'version' => 'auto',
    'assets' => 'external',
],
```

Or add this method to your table:

```php
public function dataTablesVersion(): string
{
    return '3';
}
```

Or set `datatable-version="3.1.5"` on the Blade component.
Precedence is **Blade > table > global**. All tables on a page must use the
same installed engine, even when they have separate definitions.

## Browser support

TableKit's shared scripts use ES5 syntax and XMLHttpRequest. They need
DOM `classList`, JSON, array methods, and event APIs. DataTables 3 also
requires ES6-capable browsers. Automated browser checks currently use
Chrome; an older DataTables version does not imply every old browser has
been tested with TableKit.

For engine errors or double initialization, see
[Troubleshooting](troubleshooting.md).

[Documentation index](README.md)
