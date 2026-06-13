# Stable Tag

Current stable version: v1.4.0

Date: 2026-06-14

Quality gate:
- composer install: passed
- npm install: passed
- npm run build: passed
- composer audit: passed, no security advisories
- php artisan migrate:fresh --env=testing --force: passed
- php artisan db:seed --env=testing --force: passed
- php artisan test: passed, 39 tests / 503 assertions

Notes:
- v1.4.0 is the queue and scheduler foundation release: the app now includes queue readiness checks, renewal reminder processing, stale pending order expiration, simulated commission settlement checks, failed-job visibility, and scheduler registrations.
