# Stable Tag

Current stable version: v2.2.0

Date: 2026-06-14

Quality gate:
- composer install: passed
- npm install: passed
- npm run build: passed
- composer audit: passed, no security advisories
- php artisan migrate:fresh --env=testing --force: passed
- php artisan db:seed --env=testing --force: passed
- php artisan test: passed, 53 tests / 756 assertions

Notes:
- v2.2.0 is the Product Factory Foundation release: the app now stores product templates, plugin templates, landing page templates, pricing/License package templates, generated product drafts, and launch checklists in simulation mode. It must not create real external websites or automatically sell products.
