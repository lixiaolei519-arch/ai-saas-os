# Changelog

## v0.6.0 - 2026-06-14

### Added
- Added marketing channel, promotion link, promotion attribution, and commission record tables.
- Added APIs for creating channels, creating promotion links, recording attribution, and calculating commissions.
- Added automatic commission creation from the existing paid-order payment callback flow.
- Added renewal reminder processing backed by existing notification templates.
- Added tests covering promotion links, attribution, referral commission records, renewal reminders, and automatic notification delivery.

### Verified
- `composer install` completed successfully.
- `composer audit` reported no security vulnerability advisories.
- `php artisan migrate:fresh --env=testing --force` completed successfully.
- `php artisan test` passed with 15 tests and 191 assertions before final release tagging.

## v0.5.0 - 2026-06-14

### Added
- Added risk blacklist APIs for creating and evaluating blocked identity values.
- Added API rate-limit checks backed by Laravel's rate limiter.
- Added abnormal License event recording for risk auditing.
- Added high-risk operation event recording with actor, action, resource, and metadata context.
- Added tests covering blacklist blocking, rate-limit denial, abnormal License risk events, generic risk events, and high-risk operation logging.

### Verified
- `composer install` completed successfully.
- `composer audit` reported no security vulnerability advisories.
- `php artisan migrate:fresh --env=testing --force` completed successfully.
- `php artisan test` passed with 14 tests and 169 assertions before final release tagging.

## v0.4.0 - 2026-06-14

### Added
- Added workflow rules table and model.
- Added workflow definition support for node-level rules.
- Added condition evaluation for workflow nodes with equals, not-equals, comparison, and presence checks.
- Added action execution outputs for supported workflow action types.
- Added failed workflow retry API with optional payload override.
- Added tests covering event execution, condition failure, action completion, execution logs, and retry success.

### Verified
- `composer install` completed successfully.
- `composer audit` reported no security vulnerability advisories.
- `php artisan migrate:fresh --env=testing --force` completed successfully.
- `php artisan test` passed with 13 tests and 150 assertions before final release tagging.

## v0.3.0 - 2026-06-14

### Added
- Added plugin package storage tables and models.
- Added plugin release package upload API with versioned package metadata.
- Added License-gated plugin download token issuance for installed plugins.
- Added plugin download token verification API.
- Added plugin update check API.
- Added tests covering package upload, version management, authorized download, invalid-license blocking, installation records, and update checks.

### Verified
- `composer install` completed successfully.
- `composer audit` reported no security vulnerability advisories.
- `php artisan migrate:fresh --env=testing --force` completed successfully.
- `php artisan test` passed with 12 tests and 137 assertions before final release tagging.

## v0.2.0 - 2026-06-14

### Added
- Added AI account balance API for tenant AI billing accounts.
- Added credit grant flow for issuing money balance and token quota into the AI billing ledger.
- Enforced License verification before AI usage can be charged.
- Enforced money balance and token quota checks before AI usage records are created.
- Added ledger-backed AI usage charging with tests for normal charging, invalid license blocking, and insufficient balance blocking.

### Verified
- `composer install` completed successfully.
- `composer audit` reported no security vulnerability advisories.
- `php artisan migrate:fresh --env=testing --force` completed successfully.
- `php artisan test` passed with 10 tests and 114 assertions before final release tagging.

## v0.1.1 - 2026-06-14

### Security
- Upgraded `laravel/framework` to `v12.62.0` to resolve `CVE-2026-48019`.
- Upgraded compatible support dependencies: `laravel/sanctum` to `v4.3.2`, `nunomaduro/collision` to `v8.9.4`, and PHPUnit to the 11.5 line required by the updated development tooling.
- Removed the temporary Composer advisory bypass policy now that `composer audit` passes.

### Verified
- `composer install` completed successfully.
- `composer audit` reported no security vulnerability advisories.
- `php artisan migrate:fresh --env=testing --force` completed successfully.
- `php artisan test` passed with 8 tests and 93 assertions.

## v0.1.0 - 2026-06-14

### Added
- Created the Laravel 11 project structure and core database schema for tenants, users, licenses, product plans, orders, payments, AI billing, plugin marketplace, workflow automation, risk control, audit events, and marketing automation.
- Added service-layer implementations and API routes for the current stable scope: user authentication, tenant onboarding, authorization/RBAC, license issue/verify, product plans, orders, and payment callbacks.
- Added payment gateway adapters for WeChat Pay and Alipay callback signature verification.
- Added release stability coverage for v0.1.0 criteria: user system, license system, order system, and payment callbacks.

### Changed
- Entered release control mode and stopped expanding next-phase AI/plugin/workflow functionality until v0.1.0 is stable.
- Removed the in-progress AI recharge and balance endpoints from this release control pass.

### Verified
- v0.1.0 stable criteria passed: user system, license system, order system, and payment callback flow.
- Automated test suite passed with 8 tests and 93 assertions.

### Known Risks
- `composer audit` reports `CVE-2026-48019` for `laravel/framework`; keep the Composer advisory policy visible and upgrade Laravel as soon as a Laravel 11-compatible patched release is available or the project is approved to move beyond Laravel 11.
