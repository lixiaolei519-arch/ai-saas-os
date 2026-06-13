# Stable Tag

Current stable version: v2.3.0

Date: 2026-06-14

Quality gate:
- composer install: passed
- npm install: not required, frontend unchanged
- npm run build: not required, frontend unchanged
- composer audit: passed, no security advisories
- php artisan migrate:fresh --env=testing --force: passed
- php artisan db:seed --env=testing --force: passed
- php artisan test: passed, 55 tests / 778 assertions

Notes:
- v2.3.0 is the Deep Quality Expansion release: the app now exposes read-only quality status APIs and an OpenAPI draft for version, deployment, and documentation checks.
