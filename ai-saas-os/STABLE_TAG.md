# Stable Tag

Current stable version: v1.7.0

Date: 2026-06-14

Quality gate:
- composer install: passed
- npm install: passed
- npm run build: passed
- composer audit: passed, no security advisories
- php artisan migrate:fresh --env=testing --force: passed
- php artisan db:seed --env=testing --force: passed
- php artisan test: passed, 41 tests / 558 assertions

Notes:
- v1.7.0 is the plugin delivery foundation release: the app now records authorized plugin downloads, exposes plugin delivery visibility to administrators, and shows installed/downloadable plugins in the customer portal without executing plugin code.
