# Release checklist

This checklist separates checks already recorded for the MVP from work
required before a future release. An unpublished package does not need
publication steps marked complete to have complete user documentation.

## Recorded local checks

- [x] PHP suite passed on each locally installed Laravel 7–13 row.
- [x] Real PHP 7.4/Laravel 7 rendering, AJAX, filters, and generator checked.
- [x] PHP 7.4 syntax checks passed, including rendered generator stubs.
- [x] Tenant scope, escaping, sort allowlists, pagination, policies, and bulk
      selection checks passed.
- [x] Browser tests passed on DataTables 1.10, 1.13, 2.x, and jQuery-free 3.x.
- [x] Live Laravel 7 demo passed with config/route caches and published assets.
- [x] Desktop/mobile screenshots reviewed.
- [x] SQL paging, a representative filter index plan, and eager loading checked.
- [x] Composer metadata validated; local runtime archive inspected.
- [x] Config, views, and assets publishing checked.
- [x] Legacy dependency audit findings recorded without hiding them.

Exact versions and coverage limits are in [Compatibility](compatibility.md).

## Before authorizing a release

- [ ] Run the remote CI matrix, including minimum PHP combinations unavailable
      locally, and resolve any failures.
- [ ] Check the intended production database dialects/collations and query plans.
- [ ] Verify the target browser, styling, and extension combinations.
- [ ] Review keyboard interactions and application-specific accessibility.
- [ ] Rerun dependency audits and review all remaining advisories.
- [ ] Repeat tests, publishing, caches, and archive checks on the final source.
- [ ] Choose the initial version and prepare release notes and metadata.
- [ ] Review user docs, compatibility claims, and optional-feature status.
- [ ] Obtain explicit authorization to publish or create a release.

The runtime archive must include source, config, templates, JS/CSS, and the
stub. It must exclude dependencies, local keys/databases, compatibility
fixtures, caches, and test output.

No development check should publish a package, push changes, create a release,
or bump a version automatically. The npm manifest is private and exists
for development tests.

[Documentation index](README.md)
