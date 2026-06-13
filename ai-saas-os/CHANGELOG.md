# Changelog

## v2.1.0 - 2026-06-14

### Added
- Added draft-only Autonomous Operations Center tables for operation drafts and operation tasks.
- Added `AutonomousOperationsService` and the safe `operations:generate-drafts` command.
- Added administrator APIs for operations dashboard, reports, SEO plans, landing pages, pricing, release announcements, customer emails, FAQ, and partner recruiting drafts.
- Added React administrator pages under `/console/operations/*` for autonomous operations visibility.
- Added tests covering draft-only generation, simulation/approval safety flags, administrator API visibility, customer access blocking, and console deep links.

### Verified
- `npm install` completed in `frontend/admin-console`.
- `npm run build` generated the committed `public/console` production build.
- `composer audit --no-interaction` reported no security vulnerability advisories.
- `php artisan migrate:fresh --env=testing --force` completed successfully.
- `php artisan db:seed --env=testing --force` completed successfully.
- `php artisan test` passed with 50 tests and 721 assertions after the Autonomous Operations Center changes.

## v2.0.0 - 2026-06-14

### Added
- Added Self-Evolution Engine simulation tables for scans, scores, plans, release reviews, and suggestions.
- Added `SelfEvolutionService` and safe artisan commands: `self-evolve:scan`, `self-evolve:score`, `self-evolve:plan`, and `self-evolve:review-release`.
- Added administrator APIs for self-evolution dashboard, scans, scores, plans, release reviews, and suggestions.
- Added React administrator pages under `/console/self-evolution/*` for dashboard, scoring, plans, release review, and suggestions.
- Added tests covering self-evolution commands, simulation/approval safety flags, administrator API visibility, customer access blocking, and console deep links.

### Verified
- `npm install` completed in `frontend/admin-console`.
- `npm run build` generated the committed `public/console` production build.
- `composer audit --no-interaction` reported no security vulnerability advisories.
- `php artisan migrate:fresh --env=testing --force` completed successfully.
- `php artisan db:seed --env=testing --force` completed successfully.
- `php artisan test` passed with 47 tests and 674 assertions after the Self-Evolution Engine changes.

## v1.9.0 - 2026-06-14

### Added
- Added AI Company OS simulation tables for tasks, ideas, roadmaps, release plans, quality reports, risk reports, Codex prompt drafts, and daily reports.
- Added `AiCompanyService` and safe artisan commands: `ai-company:scan`, `ai-company:plan`, `ai-company:generate-prompts`, and `ai-company:daily-report`.
- Added administrator APIs for AI Company OS dashboard and read-only list views.
- Added React administrator pages under `/console/ai-company/*` for AI Company OS dashboard, task pool, idea pool, roadmap, releases, quality, risks, prompts, and reports.
- Added tests covering AI Company OS commands, simulation/approval safety flags, administrator API visibility, customer access blocking, and console deep links.

### Verified
- `npm install` completed in `frontend/admin-console`.
- `npm run build` generated the committed `public/console` production build.
- `composer audit --no-interaction` reported no security vulnerability advisories.
- `php artisan migrate:fresh --env=testing --force` completed successfully.
- `php artisan db:seed --env=testing --force` completed successfully.
- `php artisan test` passed with 44 tests and 624 assertions after the AI Company OS core changes.

## v1.8.0 - 2026-06-14

### Added
- Added workflow event logs to persist received and processed workflow events.
- Added supported internal workflow action handling for simulated notification, commission, License, and audit-log style actions without external calls.
- Added administrator APIs for workflow definitions, workflow runs, and workflow event logs.
- Added React administrator pages for workflow lists, execution records, and event logs.
- Added tests covering workflow event logging, administrator workflow visibility, and workflow console deep links.

### Verified
- `npm install` completed in `frontend/admin-console`.
- `npm run build` generated the committed `public/console` production build.
- `composer audit --no-interaction` reported no security vulnerability advisories.
- `php artisan migrate:fresh --env=testing --force` completed successfully.
- `php artisan db:seed --env=testing --force` completed successfully.
- `php artisan test` passed with 41 tests and 568 assertions after the workflow automation foundation changes.

## v1.7.0 - 2026-06-14

### Added
- Added plugin download records to persist authorized package download verification events.
- Added administrator APIs for plugin lists and plugin download records.
- Added customer portal API for installed/downloadable plugins scoped to the authenticated customer's tenants.
- Added administrator React pages for plugin upload/version management and plugin download records.
- Added customer portal React page for downloadable installed plugins.
- Added tests covering plugin download record generation, administrator visibility, customer portal visibility, and plugin console deep links.

### Verified
- `npm install` completed in `frontend/admin-console`.
- `npm run build` generated the committed `public/console` production build.
- `composer audit --no-interaction` reported no security vulnerability advisories.
- `php artisan migrate:fresh --env=testing --force` completed successfully.
- `php artisan db:seed --env=testing --force` completed successfully.
- `php artisan test` passed with 41 tests and 558 assertions after the plugin delivery foundation changes.

## v1.6.0 - 2026-06-14

### Added
- Added a configurable mock AI provider that estimates tokens and returns a simulated response without calling real model APIs.
- Added `POST /api/v1/ai/mock/completions` to run mock AI completion billing through the existing License, balance, usage record, and ledger flow.
- Added administrator API and React console page for AI usage records.
- Added customer portal AI account API, AI balance dashboard cards, and AI balance/usage page.
- Added AI mock provider environment placeholders without adding any real OpenAI, Claude, Gemini, or other provider keys.
- Added tests covering mock AI billing, insufficient-balance blocking, administrator visibility, customer portal balance visibility, and AI console deep links.

### Verified
- `npm install` completed in `frontend/admin-console`.
- `npm run build` generated the committed `public/console` production build.
- `composer audit --no-interaction` reported no security vulnerability advisories.
- `php artisan migrate:fresh --env=testing --force` completed successfully.
- `php artisan db:seed --env=testing --force` completed successfully.
- `php artisan test` passed with 41 tests and 544 assertions after the AI billing foundation changes.

## v1.5.0 - 2026-06-14

### Added
- Enhanced `php artisan app:production-check` with checks for `APP_DEBUG=false`, `APP_URL`, `DB_COLLATION`, writable `bootstrap/cache`, `public/console/index.html`, `/console`, public API JSON response, and sensitive file exposure.
- Enhanced `php artisan app:smoke-test` with console route, API JSON, and sensitive file probes.
- Added `DB_COLLATION` to `.env.example` and `.env.production.example`.
- Added Baota backup/restore, GitHub deployment, and Baota troubleshooting documents.
- Added `scripts/deploy-bt.sh` as a manual Baota deployment script draft.
- Added test coverage for the production hardening checks and deployment documents.

### Verified
- Frontend source was not changed; no React rebuild was required for this release.
- `composer audit --no-interaction` reported no security vulnerability advisories.
- `php artisan migrate:fresh --env=testing --force` completed successfully.
- `php artisan db:seed --env=testing --force` completed successfully.
- `php artisan test` passed with 39 tests and 519 assertions after the production hardening changes.

## v1.4.0 - 2026-06-14

### Added
- Added `php artisan app:queue-check` to verify queue connection configuration, `jobs` table, `failed_jobs` table, pending job count, and failed job count.
- Added `php artisan app:renewal-reminders` for scheduled renewal reminder processing using internal notification records.
- Added `php artisan app:orders-expire` to expire stale pending orders without deleting data.
- Added `php artisan app:commissions-settle` for simulated commission settlement checks without external payouts.
- Registered queue and scheduler foundation commands with Laravel scheduling.
- Added tests covering queue checks, renewal reminder command execution, pending order expiration, and simulated commission settlement.

### Verified
- `npm install` completed in `frontend/admin-console`.
- `npm run build` generated the committed `public/console` production build.
- `composer audit --no-interaction` reported no security vulnerability advisories.
- `php artisan migrate:fresh --env=testing --force` completed successfully.
- `php artisan db:seed --env=testing --force` completed successfully.
- `php artisan test` passed with 39 tests and 503 assertions after the queue and scheduler foundation changes.

## v1.3.0 - 2026-06-14

### Added
- Added `/api/v1/admin/dashboard` for business dashboard analytics.
- Added dashboard metrics for today orders, today revenue, month revenue, paid orders, pending orders, commission totals, users, tenants, and Licenses.
- Added seven-day order and revenue trend payloads.
- Added License and commission status distribution payloads.
- Added recent orders, recent payment callbacks, and recent Licenses to the admin dashboard payload.
- Upgraded the React administrator dashboard to show analytics cards and tables with Chinese labels, status tags, empty states, loading states, and RMB formatting.
- Added test coverage for the new dashboard analytics API structure and key values.

### Verified
- `npm install` completed in `frontend/admin-console`.
- `npm run build` generated the committed `public/console` production build.
- `composer audit --no-interaction` reported no security vulnerability advisories.
- `php artisan migrate:fresh --env=testing --force` completed successfully.
- `php artisan db:seed --env=testing --force` completed successfully.
- `php artisan test` passed with 37 tests and 486 assertions after the business dashboard analytics changes.

## v1.2.0 - 2026-06-14

### Added
- Added payment adapter classes for mock payment, WeChat Pay, and Alipay.
- Added `PAYMENT_PROVIDER`, mock payment secret, WeChat Pay merchant/certificate/key/API v3 configuration placeholders, and Alipay private/public key placeholders.
- Added explicit unconfigured payment payloads for WeChat Pay and Alipay when real credentials are missing.
- Added `mock` as a first-class payment channel for orders and payment callbacks.
- Added automated coverage for mock payment provisioning, unconfigured real payment adapters, amount mismatch rejection, and duplicate callback idempotency.

### Changed
- Hardened payment callback processing so amount mismatches are rejected before License provisioning.
- Duplicate paid callbacks are now acknowledged without re-opening Licenses, re-running workflows, or duplicating commissions.
- Commission generation now remains idempotent for an order.

### Verified
- `npm install` completed in `frontend/admin-console`.
- `npm run build` generated the committed `public/console` production build.
- `composer audit --no-interaction` reported no security vulnerability advisories.
- `php artisan migrate:fresh --env=testing --force` completed successfully.
- `php artisan db:seed --env=testing --force` completed successfully.
- `php artisan test` passed with 37 tests and 436 assertions after the payment adapter foundation changes.

## v1.1.2 - 2026-06-14

### Added
- Added role-aware console route guards for administrator and customer sections.
- Added dedicated React `403` and `404` pages for forbidden and missing console routes.
- Added frontend build metadata for stable version, Git commit, and build time.
- Added shared header identity display for current user role and email.
- Added unified table pagination and search helpers across administrator and customer portal list pages.
- Added Laravel coverage for console hardening deep links, invalid token JSON `401`, and customer rejection from administrator APIs.

### Changed
- Hardened Axios response handling for `401`, `403`, `422`, and `500` API responses.
- Existing logged-in users are redirected away from login pages to their role-specific console entry.

### Verified
- `npm install` completed in `frontend/admin-console`.
- `npm run build` generated the committed `public/console` production build.
- `composer audit --no-interaction` reported no security vulnerability advisories.
- `php artisan migrate:fresh --env=testing --force` completed successfully.
- `php artisan db:seed --env=testing --force` completed successfully.
- `php artisan test` passed with 34 tests and 401 assertions after the console hardening changes.

## v1.1.1 - 2026-06-14

### Added
- Added the React customer portal inside the existing `frontend/admin-console` Vite SPA without creating a second frontend project.
- Added customer portal routes for login, dashboard, licenses, orders, referrals, and commissions under `/console/portal/*`.
- Added customer-only portal API endpoints for profile, dashboard, licenses, orders, referrals, and commissions.
- Added paginated customer portal responses that are scoped to the authenticated customer's tenant ownership.
- Added portal UI pages with Ant Design Pro tables, loading states, empty states, status tags, RMB formatting, and copy actions for customer LicenseKey and referral links.
- Enhanced `php artisan app:smoke-test` to validate demo accounts, customer portal API access, customer License/order isolation, admin API access, and the committed console build.
- Added Laravel tests for customer portal authentication, ownership isolation, customer rejection from admin APIs, portal JSON 401 responses, smoke-test execution, and portal SPA deep links.

### Verified
- `npm install` completed in `frontend/admin-console`.
- `npm run build` generated the committed `public/console` production build for the administrator console and customer portal.
- `composer audit --no-interaction` reported no security vulnerability advisories.
- `php artisan migrate:fresh --env=testing --force` completed successfully.
- `php artisan db:seed --env=testing --force` completed successfully.
- `php artisan test` passed with 32 tests and 395 assertions after the React customer portal changes.

## v1.1.0 - 2026-06-14

### Added
- Added a React 18 + Ant Design Pro enterprise admin console in `frontend/admin-console`.
- Added Vite production build output under `public/console` for Baota deployments without Node.js.
- Added `/console/{any?}` Laravel SPA fallback serving `public/console/index.html`.
- Added admin console pages for login, dashboard, users, tenants, licenses, orders, payment callbacks, marketing channels, commissions, and system status.
- Added a protected read-only `/api/v1/admin/system` endpoint for console system diagnostics.
- Extended admin channel and stats responses with read-only fields required by the console.
- Added Laravel tests covering `/console`, `/console/dashboard`, unaffected `/api/v1/*` routing, and admin system status access.

### Verified
- `npm install` completed in `frontend/admin-console` with zero npm audit vulnerabilities after dependency overrides.
- `npm run build` generated the committed `public/console` production build.
- `composer audit --no-interaction` reported no security vulnerability advisories.
- `php artisan migrate:fresh --env=testing --force` completed successfully.
- `php artisan db:seed --env=testing --force` completed successfully.
- `php artisan test` passed with 28 tests and 359 assertions after the React console changes.

## v1.0.1 - 2026-06-14

### Added
- Added `php artisan app:production-check` for production environment self-checks.
- The new self-check validates `APP_ENV=production`, configured `APP_KEY`, database connectivity, writable storage and cache, queue configuration, required `.env` keys, and `/health` accessibility.
- Added automated test coverage for the production self-check command.
- Added production delivery documents for v1.0.0 release notes, deployment package, production checklist, rollback guide, Baota panel deployment, Nginx pseudo-static rules, and production environment example configuration.
- Added `php artisan app:smoke-test` for one-command deployment validation of the minimum commercial flow.
- The smoke test validates database connectivity, key tables, `/health`, smoke customer login, order creation, simulated payment callback, automatic License provisioning, LicenseKey readback, License verification, promotion attribution, and commission generation.
- Added test coverage for the deployment smoke test command.

### Fixed
- Added `php artisan app:create-demo-users` to create deployment verification admin and customer accounts with generated terminal-only passwords.
- Updated Baota deployment smoke test instructions to use command output credentials, `POST` login requests, and `Accept: application/json`.
- Added test coverage proving the demo-user command creates both users and that both accounts can log in successfully.

### Verified
- `composer audit --no-interaction` reported no security vulnerability advisories.
- `php artisan test` passed with 24 tests and 342 assertions after the deployment smoke test changes.

## v1.0.0 - 2026-06-14

### Added
- Added paid-order License auto-provisioning in the payment callback flow.
- Added minimum commercial launch end-to-end test covering register/login, tenant setup, order creation, simulated payment callback, automatic License provisioning, LicenseKey copy, License verification, promotion attribution, and commission generation.
- Added demo seed data for admin and customer accounts, demo tenant, AI balance, product plan, marketing channel, and promotion link.
- Added `security:prelaunch` command and prelaunch security documentation.
- Replaced the default Laravel README with project setup, quality gate, demo account, commercial flow, deployment, and health-check guidance.
- Added API documentation draft.

### Verified
- `composer install --no-interaction` completed successfully.
- `composer audit --no-interaction` reported no security vulnerability advisories.
- `php artisan migrate:fresh --env=testing --force` completed successfully.
- `php artisan db:seed --env=testing --force` completed successfully before final release tagging.
- `php artisan test` passed with 21 tests and 302 assertions before final release tagging.

## v0.9.0 - 2026-06-14

### Added
- Updated `.env.example` for MySQL, China timezone, payment webhook placeholders, demo account placeholders, and optional License RSA keys.
- Added `/health` JSON health check endpoint.
- Added `php artisan production:check` readiness command.
- Added Baota deployment documentation covering runtime, project setup, database initialization, Nginx pseudo-static rules, queue worker, scheduler, health checks, simulated payments, and production checks.
- Added prelaunch checklist documentation.
- Added tests covering `/health`, deployment document existence, and the production check command.

### Verified
- `composer audit` reported no security vulnerability advisories.
- `php artisan migrate:fresh --env=testing --force` completed successfully.
- `php artisan test` passed with 20 tests and 277 assertions before final release tagging.

## v0.8.0 - 2026-06-14

### Added
- Added encrypted LicenseKey storage for newly issued licenses so customer portal copy operations can return the original key without exposing it in normal list responses.
- Added customer portal service and authenticated `/api/v1/portal/*` APIs.
- Added customer-owned views for licenses, orders, AI usage records, promotion links, and commission records.
- Added customer renewal request API backed by the existing order service.
- Added customer LicenseKey copy API with ownership checks.
- Added customer License domain unbind API.
- Added tests covering customer ownership boundaries, portal listings, renewal requests, LicenseKey copy, and domain unbinding.

### Verified
- `composer audit` reported no security vulnerability advisories.
- `php artisan migrate:fresh --env=testing --force` completed successfully.
- `php artisan test` passed with 17 tests and 267 assertions before final release tagging.

## v0.7.0 - 2026-06-14

### Added
- Added root `RELEASE_LOCK.md` by copying the existing release lock from `release-system/RELEASE_LOCK.md`.
- Extended `ROADMAP.md` with the locked productionization route from v0.7.0 through v1.0.0.
- Added administrator user flag support.
- Added admin login API with protected admin token issuance.
- Added admin-only middleware for backoffice API access.
- Added read-only admin APIs for users, tenants, licenses, orders, payment callbacks, marketing channels, commission records, and basic statistics.
- Added tests covering admin login, non-admin rejection, backoffice resource listing, and stats.

### Verified
- `composer audit` reported no security vulnerability advisories.
- `php artisan migrate:fresh --env=testing --force` completed successfully.
- `php artisan test` passed with 16 tests and 232 assertions before final release tagging.

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
