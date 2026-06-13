# Deploy After Sleep

Last updated: 2026-06-14 06:57:20 +08:00

## Run Summary

- Start time: 2026-06-14 06:09:34 +08:00
- End time: in progress
- Estimated duration: in progress
- Start version: v1.1.1
- Current target version: v1.7.0
- End version: in progress
- GitHub repository: https://github.com/lixiaolei519-arch/ai-saas-os

## Baseline Stage

### v1.1.1 React Customer Portal

- Status: already stable at the start of this run
- Commit: `587dd008e04fd923ebc66beabff3e9dcfec45044`
- Tests: `php artisan test` passed with 32 tests / 395 assertions before this run
- Frontend build: `npm run build` passed before this run
- Push: pushed to GitHub `main`

## Current Stage

### v1.7.0 Plugin Delivery Foundation

- Status: next
- Commit: pending
- Tests: pending
- Frontend build: pending if frontend files change
- Push: pending

## Completed Stages

### v1.6.0 AI Billing Foundation

- Status: stable locally, pending release commit and push
- Commit: pending
- Tests: `php artisan test` passed with 41 tests / 544 assertions
- Frontend build: `npm install` and `npm run build` passed
- Backend gates: `composer audit`, testing migration, and testing seed passed
- Push: pending

Completed changes so far:
- Added mock AI provider and `/api/v1/ai/mock/completions`.
- Added administrator AI usage API and console page.
- Added customer portal AI account API, balance dashboard cards, and usage page.
- Rebuilt committed React assets in `public/console`.

### v1.5.0 Production Hardening

- Status: stable and pushed
- Commit: `cd187cc5a501715afa9888a9efcb6a7944a1661b` (`Release v1.5.0 production hardening`)
- Tests: `php artisan test` passed with 39 tests / 519 assertions
- Frontend build: not required unless frontend files change
- Backend gates: `composer audit`, testing migration, and testing seed passed
- Push: pushed to GitHub `main`

Completed changes so far:
- Enhanced `app:production-check` for production config, writable cache, console, API JSON, and sensitive-file checks.
- Enhanced `app:smoke-test` with console/API/sensitive-file probes.
- Added backup/restore, GitHub deployment, and Baota troubleshooting docs.
- Added `scripts/deploy-bt.sh` as a manual deployment draft.

### v1.4.0 Queue and Scheduler Foundation

- Status: stable and pushed
- Commit: `23408908ab0096869892028b7b9102d5e97efd20` (`Release v1.4.0 queue and scheduler foundation`)
- Tests: `php artisan test` passed with 39 tests / 503 assertions
- Frontend build: `npm install` and `npm run build` passed
- Backend gates: `composer audit`, testing migration, and testing seed passed
- Push: pushed to GitHub `main`

Completed changes so far:
- Added queue readiness command.
- Added scheduler foundation commands for renewal reminders, stale pending orders, and simulated commission settlement.
- Added tests for internal-only scheduler command behavior.

### v1.3.0 Business Dashboard Analytics

- Status: stable and pushed
- Commit: `3d8eb0e` (`Release v1.3.0 business dashboard analytics`)
- Tests: `php artisan test` passed with 37 tests / 486 assertions
- Frontend build: `npm install` and `npm run build` passed
- Backend gates: `composer audit`, testing migration, and testing seed passed
- Push: pushed to GitHub `main`

Completed changes so far:
- Added `/api/v1/admin/dashboard`.
- Added analytics metrics, trends, status distributions, and recent activity payloads.
- Upgraded React dashboard to show operational analytics tables.

### v1.2.0 Payment Adapter Foundation

- Status: stable and pushed
- Commit: `3b6c01b` (`Release v1.2.0 payment adapter foundation`)
- Tests: `php artisan test` passed with 37 tests / 436 assertions
- Frontend build: `npm install` and `npm run build` passed
- Backend gates: `composer audit`, testing migration, and testing seed passed
- Push: pushed to GitHub `main`

Completed changes so far:
- Added mock, WeChat Pay, and Alipay adapter structure.
- Added missing-credential payload errors for real payment adapters.
- Added mock payment channel support for order creation and callbacks.
- Added callback amount mismatch rejection.
- Added duplicate paid-callback idempotency for Licenses and commissions.

### v1.1.2 Console Permissions and UX Hardening

- Status: stable and pushed
- Commit: `4c2f423` (`Release v1.1.2 console permissions and UX hardening`)
- Tests: `php artisan test` passed with 34 tests / 401 assertions
- Frontend build: `npm install` and `npm run build` passed
- Backend gates: `composer audit`, testing migration, and testing seed passed
- Push: pushed to GitHub `main`

Completed changes:
- Added role-aware React route guards for administrator and customer sections.
- Added shared `403` and `404` pages.
- Hardened Axios handling for `401`, `403`, `422`, and `500` responses.
- Added stable version, Git commit, and frontend build-time display.
- Unified search and pagination behavior across administrator and customer list pages.

## Server Deployment Commands

Run after a stable version has been pushed:

```bash
cd /www/wwwroot/ai-saas-os
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan queue:restart
php artisan app:production-check
php artisan app:smoke-test
```

If frontend source changed and the server has Node.js:

```bash
cd /www/wwwroot/ai-saas-os/frontend/admin-console
npm install
npm run build
```

If the server does not have Node.js, use the committed `public/console` build artifact.

## Manual Checks For Tomorrow

- Confirm production `.env` still has `APP_ENV=production` and `APP_DEBUG=false`.
- Confirm no real payment, email, SMS, marketing, or production automation credentials were added by this run.
- Confirm `/health`, `/console/login`, `/console/portal/login`, and `/api/v1/*` respond after deployment.
- Confirm `php artisan app:smoke-test` passes on the server.

## Problems Encountered

- None at run start.

## Unfinished Work

- v1.6.0 has not started yet.

## Risk List

- Long-running staged development must keep each version independently releasable.
- Frontend build artifacts under `public/console` must be committed whenever React source changes.
- Production deployment should remain manual; this run must not operate the production server directly.

## Production Deployment Risk

Current risk: low. v1.5.0 changes production diagnostics, smoke-test probes, and deployment documents only; production deployment still requires the manual commands above and post-deploy smoke test.
