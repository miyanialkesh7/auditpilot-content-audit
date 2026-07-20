# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
