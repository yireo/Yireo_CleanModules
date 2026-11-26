# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Added
- `yireo:modules:clean` has a `--remove` option to delete missing modules from `app/etc/config.php` instead of disabling them

## [0.0.3] - 25 November 2026
### Added
- `yireo:modules:clean` has a `--dry-run` option to preview which modules would be disabled, and a `--force` option to skip the confirmation and safety checks

### Changed
- `yireo:modules:clean` now asks for confirmation, refuses to disable `Magento_` core modules or more than 10 modules at once without `--force`, and writes a backup of `app/etc/config.php` before changing it
- `yireo:modules:check` now exits with a failure code when modules are missing

### Removed
- Support for PHP 8.1 and Magento framework versions older than 103.0.6 (now requires PHP 8.2 to 8.5)

### Fixed
- Storefront CSP violation reports, including full page URLs with password reset tokens, were sent to a third-party uriports.com endpoint
- Google, Google Tag Manager and PayPal hosts were globally allowed in the storefront CSP policy
- `app/etc/config.php` could get corrupted, because it was read with `include` and rewritten by string replacement instead of through Magento's deployment config
- Missing modules are now detected via the component registrar and module names are escaped in console output

## [0.0.1] - 10 April 2025
### Fixed
- Initial release
