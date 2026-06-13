# Deploy After Sleep

Last updated: 2026-06-14 07:48:26 +08:00

## Run Summary

- Start time: 2026-06-14 06:09:34 +08:00
- End time: in progress
- Estimated duration: in progress
- Start version: v1.1.1
- Current target version: v2.2.0
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

### v2.2.0 Product Factory Foundation

- Status: stable pending release commit
- Commit: pending
- Tests: `php artisan test` passed with 53 tests / 756 assertions
- Frontend build: `npm install` and `npm run build` passed
- Backend gates: `composer audit`, testing migration, and testing seed passed
- Push: pending

Completed changes so far:
- Add product, plugin, landing page, pricing, and License package templates.
- Add product launch checklist and product generation draft records.
- Add administrator API and console pages for product factory visibility.
- Keep all generated content as internal drafts and tasks only.
- Rebuilt committed React assets in `public/console`.

## Completed Stages

### v2.1.0 Autonomous Operations Center

- Status: stable and pushed
- Commit: `97df0be4da0111e5c00c3d83468fe517e833187b` (`Release v2.1.0 autonomous operations center`)
- Tests: `php artisan test` passed with 50 tests / 721 assertions
- Frontend build: `npm install` and `npm run build` passed
- Backend gates: `composer audit`, testing migration, and testing seed passed
- Push: pushed to GitHub `main`

Completed changes so far:
- Added draft-only autonomous operations records for reports, SEO plans, landing pages, pricing, announcements, customer emails, FAQ, promotion tasks, and partner recruiting copy.
- Added administrator API and console pages under `/console/operations/*`.
- Kept all outbound content as internal drafts requiring manual approval.
- Rebuilt committed React assets in `public/console`.

### v2.0.0 Self-Evolution Engine

- Status: stable and pushed
- Commit: `e3329d322888485e5e238b7dc1f1f56ae08752ec` (`Release v2.0.0 self evolution engine`)
- Tests: `php artisan test` passed with 47 tests / 674 assertions
- Frontend build: `npm install` and `npm run build` passed
- Backend gates: `composer audit`, testing migration, and testing seed passed
- Push: pushed to GitHub `main`

Completed changes so far:
- Added safe self-evolution scanner, scorer, task planner, version planner, release reviewer, and suggestion records.
- Added artisan commands for `self-evolve:scan`, `self-evolve:score`, `self-evolve:plan`, and `self-evolve:review-release`.
- Added administrator API and console pages for self-evolution visibility.
- Rebuilt committed React assets in `public/console`.

### v1.9.0 AI Company OS Core

- Status: stable and pushed
- Commit: `335fb1bd9cdad9bb1f7193e067dd205b4fd357f3` (`Release v1.9.0 AI Company OS core`)
- Tests: `php artisan test` passed with 44 tests / 624 assertions
- Frontend build: `npm install` and `npm run build` passed
- Backend gates: `composer audit`, testing migration, and testing seed passed
- Push: pushed to GitHub `main`

Completed changes so far:
- Added AI Company OS simulation records for tasks, ideas, roadmaps, release plans, quality reports, risk reports, Codex prompts, and daily reports.
- Added safe AI Company OS artisan commands.
- Added administrator AI Company OS APIs and React console pages.
- Rebuilt committed React assets in `public/console`.

### v1.8.0 Workflow Automation Foundation

- Status: stable and pushed
- Commit: `1b7b94b58bd33e05a1ab1d7fc84b8f78e69edad0` (`Release v1.8.0 workflow automation foundation`)
- Tests: `php artisan test` passed with 41 tests / 568 assertions
- Frontend build: `npm install` and `npm run build` passed
- Backend gates: `composer audit`, testing migration, and testing seed passed
- Push: pushed to GitHub `main`

Completed changes so far:
- Added workflow event logs and event log writes for workflow runs/triggers.
- Added administrator workflow definitions, runs, and event APIs/pages.
- Added workflow list, execution record, and event log pages in React.
- Rebuilt committed React assets in `public/console`.

### v1.7.0 Plugin Delivery Foundation

- Status: stable and pushed
- Commit: `1df6bce738ec2658a5dde437867ba99dac143755` (`Release v1.7.0 plugin delivery foundation`)
- Tests: `php artisan test` passed with 41 tests / 558 assertions
- Frontend build: `npm install` and `npm run build` passed
- Backend gates: `composer audit`, testing migration, and testing seed passed
- Push: pushed to GitHub `main`

Completed changes so far:
- Added plugin download records and write-on-verify behavior.
- Added administrator plugin delivery/download record APIs and pages.
- Added customer portal installed plugin API and page.
- Rebuilt committed React assets in `public/console`.

### v1.6.0 AI Billing Foundation

- Status: stable and pushed
- Commit: `ae4171eb26a0ffed45af8c5304c32ab1239c4032` (`Release v1.6.0 ai billing foundation`)
- Tests: `php artisan test` passed with 41 tests / 544 assertions
- Frontend build: `npm install` and `npm run build` passed
- Backend gates: `composer audit`, testing migration, and testing seed passed
- Push: pushed to GitHub `main`

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

- v1.7.0 has not started yet.

## Risk List

- Long-running staged development must keep each version independently releasable.
- Frontend build artifacts under `public/console` must be committed whenever React source changes.
- Production deployment should remain manual; this run must not operate the production server directly.

## Production Deployment Risk

Current risk: low. v1.5.0 changes production diagnostics, smoke-test probes, and deployment documents only; production deployment still requires the manual commands above and post-deploy smoke test.
