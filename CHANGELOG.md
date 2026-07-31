# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.2] - 2026-07-31

### Security

- Rewrote the `DROP TABLE` statements in `APCA_Database::drop_tables()` and `uninstall.php` to use `$wpdb->prepare()` with `%i` identifier placeholders instead of raw string interpolation.

### Fixed

- Eliminated an N+1 query pattern in `APCA_Database::get_score_data()`: category scores are now computed from a single grouped query instead of one query per category.

### Added

- Multisite compatibility: plugin tables are now created automatically on new sub-sites via `wp_initialize_site`.
- Accessible labels (`aria-label`) on the results page search input and settings page checkboxes.

### Changed

- Renamed the plugin to "AuditPilot – Smart Content Audit" (dropped "for WordPress") to comply with WordPress.org trademark guidelines.
- Corrected the readme license identifier to the SPDX form `GPL-2.0-or-later`.
- Excluded `.wordpress-org/` from release builds via `.distignore`.

## [1.0.1] - 2026-07-20

### Changed

- Increased minimum required PHP version from 7.4 to 8.1 (plugin header, readme.txt, composer.json, and PHPCompatibilityWP baseline).

### Added

- CI/CD: PHPCS (WordPress Coding Standards), PHPStan static analysis, PHPUnit across PHP 8.1-8.5, PHPCompatibilityWP, Composer Audit, CodeQL, and an automated release workflow.
- Full docblock coverage (file, class, and function level) across the codebase.
- `composer.json` with dev tooling, `phpcs.xml.dist`, `phpstan.neon`, and an initial PHPUnit test.
- LICENSE, CONTRIBUTING.md, CODE_OF_CONDUCT.md, SECURITY.md, PR template, CODEOWNERS, dependabot config, .editorconfig, .gitattributes.

### Fixed

- Various WordPress Coding Standards violations (0 errors, down from 238) and PHPStan findings (0 errors, down from 6): int-to-string casts before `esc_html()`/`esc_attr()`, Yoda conditions, short ternaries, a `current_time()` deprecated-argument pattern, and a `count()` call hoisted out of a loop condition.

### Internal

- Renamed 11 class files to match the WordPress `class-{name}.php` naming convention.

## [1.0.0] - 2026-07-01

### Added

- Initial release.
