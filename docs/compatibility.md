# Compatibility and verification

This record describes local checks completed on **17 November 2026**.
It does not claim that remote CI or every dependency combination has run.

## Supported contract

- Distributed PHP source and generator stubs target PHP 7.4 syntax.
- Composer allows Illuminate 7–13, subject to each Laravel major's PHP needs.
- DataTables 1.10+, 2.x, and 3.x share one server-side protocol.
- Applications supply DataTables, jQuery where needed, styles, and extensions.
- The npm project contains private development fixtures only.

## Executed PHP matrix

Each row passed **33 tests and 208 assertions**, including rendering, AJAX,
filters, generator, publishing, policies, tenant scope, projection, bulk
membership, and SQL paging checks.

| PHP | Laravel | Testbench | PHPUnit | Result |
| --- | --- | --- | --- | --- |
| 7.4.33 | 7.30.7 | 5.20.0 | 9.6.39 | Pass |
| 7.4.33 | 8.83.29 | 6.48.0 | 9.6.39 | Pass |
| 8.3.33 | 9.52.23 | 7.58.0 | 9.6.39 | Pass |
| 8.3.33 | 10.50.4 | 8.39.0 | 9.6.39 | Pass |
| 8.3.33 | 11.57.1 | 9.18.0 | 11.5.58 | Pass |
| 8.3.33 | 12.72.0 | 10.12.0 | 11.5.58 | Pass |
| 8.3.33 | 13.42.0 | 11.3.0 | 12.5.44 | Pass |
| 8.5.12 | 13.42.0 | 11.3.0 | 12.5.44 | Pass |

Each Laravel major was resolved and installed by Composer. Root `vendor`
held Laravel 7; other rows used isolated `.compat/` installations. No PHP
platform requirements were ignored or simulated.

CI is configured for PHP/Laravel 7.4/7, 7.4/8, 8.0/9, 8.1/10, 8.2/11,
8.2/12, 8.3/13, and 8.5/13. The PHP 8.0/8.1/8.2 rows were not run locally
because those binaries were unavailable. Remote CI has not run as part of
this work. See [Development](development.md) for reproduction commands.

## Executed frontend checks

The final suite passed **10 browser tests** in headless Chrome and **2 Node
version-selection tests**.

| DataTables | Adapter | jQuery | Check |
| --- | --- | --- | --- |
| 1.10.25 | v1 / `dom` | 3.7.1 | Adapter and live Laravel demo |
| 1.13.11 | v1 / `dom` | 3.7.1 | Adapter and live Laravel demo |
| 2.3.8 | v2 / `layout` | 3.7.1 | Adapter and live Laravel demo |
| 3.1.5 | v3 / `layout` | Absent | Adapter and live Laravel demo |

The tests load real engine assets. Adapter tests use controlled HTTP
responses; live demo tests use Laravel 7, SQLite, middleware, policies,
controllers, and published assets with config/route caches enabled.

Covered flows include initialization, search, sorting, paging, synchronized
controls, filters/reset, loading/error recovery, multiple tables, selection,
bulk form payloads, authentication, CSRF rejection, XSS text, Create Client,
bulk mutation, deletion, layouts, mobile filters, and scoped CSV export.
Version tests cover missing/unsupported/conflicting engines and explicit
major/full-version mismatches.

## Other checks and limits

| Area | Recorded check | Limit |
| --- | --- | --- |
| Syntax | Actual PHP 7.4 lint, including rendered stubs | No claim for future PHP syntax changes |
| Metadata | Strict Composer validation and local archive inspection | No package publication performed |
| SQL | 5,000 additional records, bounded paging, indexed exact filter, one eager-load query | SQLite only; MySQL/PostgreSQL and production load remain unverified |
| Browser | Desktop/mobile screenshots and Chrome flows | Other browsers and optional extensions untested |
| Integration | Config/route caches, asset/view/config publishing, generator | App customizations still need their own tests |

There was no Git repository in the supplied directory. Final source and
whitespace checks used the files directly rather than a Git diff.

## Known dependency advisories

Legacy compatibility fixtures intentionally retain EOL dependencies.
Passing tests do not make those dependencies maintained or secure.

| Installation | Audit result recorded |
| --- | --- |
| Root Laravel 7 fixture | 17 advisories across Laravel Framework, CommonMark, and Flysystem, including critical/high severities; Swiftmailer abandoned |
| Laravel 12/13 fixtures | No advisories reported during dependency resolution |
| npm test fixtures | One moderate XSS advisory affecting `dt110` / DataTables 1.10.25 |

The DataTables advisory is
[GHSA-h73q-5wmj-q8pj](https://github.com/advisories/GHSA-h73q-5wmj-q8pj).
The old engine is a compatibility fixture, not a recommended production
asset. No engine or `node_modules` is included in the Composer runtime
archive. Legacy host apps may still inherit framework advisories through
their own dependency graph.

Modern patched framework/asset versions remain selectable. Rerun audits
before deployment; these are dated observations, not a standing clean-audit
claim. `--no-security-blocking` in isolated legacy tests does not change a
consumer application's Composer security policy.

[Documentation index](README.md)
