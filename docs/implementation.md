# Implementation status

The MVP in specification phases 1–4 is implemented. The optional Phase 5
features are listed below so they are not confused with available APIs.
This page describes the current package; [Getting started](getting-started.md)
is the guide for using it.

## Implemented features

| Phase | Available features | How to use them |
| --- | --- | --- |
| 1: Core | Laravel provider, table/column classes, validated shared protocol, scoped counts, SQL search/sort/paging, escaped output | [Getting started](getting-started.md), [Columns](columns.md) |
| 2: Frontend/layout | Blade component, DataTables 1/2/3 adapters, version detection, layout precedence, all layout modes, loading/empty/error states, multiple tables | [Frontend](frontend.md), [Configuration](configuration.md) |
| 3: Filters/actions | Typed filters, timezone boundaries, header/row/bulk actions, permission checks, CSRF forms, current-page selection | [Filters](filters.md), [Actions](actions.md), [Security](security.md) |
| 4: Developer tools | Generator, published assets/views/config, local demo, Create/Edit forms, examples, tests, compatibility tooling, CI workflow, documentation | [Demo](demo.md), [Development](development.md) |

The default UI is compact, with a top toolbar and closed collapsible
filters. A basic table needs only `query()` and `columns()`; authorization,
filters, actions, selection, and layout overrides can be added as needed.

## Verification

The recorded local suite passed 33 PHP tests / 208 assertions on Laravel
7–13, including actual PHP 7.4/Laravel 7. Two Node tests and ten browser
tests passed using real DataTables 1.10.25, 1.13.11, 2.3.8, and jQuery-free
3.1.5. Cached Laravel 7 demo operation, publishing, generator output,
SQL paging, eager loading, and archive contents were checked.

See [Compatibility](compatibility.md) for exact versions, unexecuted CI
combinations, database/browser coverage limits, and known legacy advisories.
A passing compatibility test is not a claim that an EOL dependency is secure.

## Optional features not implemented

| Feature | Current alternative |
| --- | --- |
| General Excel/PDF/export integrations | Implement an authorized app endpoint; the demo includes streamed CSV |
| Saved views or persistent table state | Keep state in application code; there is no package persistence API |
| Automatic relationship search/sort | Use explicit field mappings and configured filter callbacks |
| Bootstrap/Tailwind theme adapters | Use the namespaced default CSS or customize app styles/views |
| Select every filtered result across pages | Use current-page selection and the bulk helper |
| Query Builder/custom data sources | Return an Eloquent Builder |

These remain optional future work from Phase 5. No public APIs for them
are implied by specification examples. The Create/Edit form and mutation
controllers belong to the demo/application, not a generated CRUD framework.

## Distribution status

The source repository is [public on GitHub](https://github.com/thuraaung2493/laravel-tablekit).
The package is not published to Packagist or npm and has no release.
Install it using a Composer VCS or local path repository.

[Documentation index](README.md)
