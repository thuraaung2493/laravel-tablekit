# TableKit documentation

New to TableKit? Follow [Getting started](getting-started.md) from top to
bottom. To try an existing working application, use the [Demo](demo.md).

## Build a table

1. [Getting started](getting-started.md) — install, define, route, and render.
2. [Frontend setup](frontend.md) — DataTables 1/2/3 and bundler setup.
3. [Configuration](configuration.md) — global defaults and view overrides.
4. [Columns and queries](columns.md) — fields, formatting, and relationships.
5. [Filters](filters.md) — inputs, validation, defaults, and dates.
6. [Actions and selection](actions.md) — links, mutations, policies, and bulk actions.
7. [JavaScript API](javascript.md) — reload, search, filters, and lifecycle.
8. [Authorization and security](security.md) — access, tenant scope, and escaping.

## Run and maintain

- [Demo](demo.md) — local setup and a feature walkthrough.
- [Troubleshooting](troubleshooting.md) — common symptoms and fixes.
- [Development](development.md) — tests, caches, updates, and package structure.
- [Compatibility](compatibility.md) — tested versions, limits, and audit results.
- [API reference](api.md) — a compact index of the supported extension points.
- [Implementation status](implementation.md) — implemented and optional features.
- [Release checklist](release-checklist.md) — completed checks and release gates.

## Example conventions

Application examples use `App\Models\Client` and a `clients` table. If your
Laravel 7 model lives at `App\Client`, change that import. Basic examples
need `id`, `name`, and `phone`. Feature guides state any extra fields,
relationships, routes, or policies they need.

A code block labelled with a file path is a complete file unless the text
says to add methods or imports. Other blocks are small additions to an
existing table. Examples use PHP 7.4-compatible syntax.

The package does not create your database schema or authentication system.
It uses the models, login flow, routes, and policies in your Laravel app.

[Back to project overview](../README.md)
