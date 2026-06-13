# Stable Tag

Current stable version: v1.8.0

Date: 2026-06-14

Quality gate:
- composer install: passed
- npm install: passed
- npm run build: passed
- composer audit: passed, no security advisories
- php artisan migrate:fresh --env=testing --force: passed
- php artisan db:seed --env=testing --force: passed
- php artisan test: passed, 41 tests / 568 assertions

Notes:
- v1.8.0 is the workflow automation foundation release: the app now records workflow events, exposes workflow definitions/runs/events to administrators, and keeps workflow actions in internal simulation mode without external service calls.
