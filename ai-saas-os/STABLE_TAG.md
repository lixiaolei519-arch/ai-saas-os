# Stable Tag

Current stable version: v1.9.0

Date: 2026-06-14

Quality gate:
- composer install: passed
- npm install: passed
- npm run build: passed
- composer audit: passed, no security advisories
- php artisan migrate:fresh --env=testing --force: passed
- php artisan db:seed --env=testing --force: passed
- php artisan test: passed, 44 tests / 624 assertions

Notes:
- v1.9.0 is the AI Company OS core release: the app now stores internal company tasks, ideas, roadmaps, release plans, quality reports, risk reports, Codex prompt drafts, and daily reports in simulation mode. It does not execute code changes, deployments, production pushes, external service calls, payments, email, SMS, or marketing actions automatically.
