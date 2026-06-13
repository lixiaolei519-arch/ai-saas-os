# Stable Tag

Current stable version: v1.3.0

Date: 2026-06-14

Quality gate:
- composer install: passed
- npm install: passed
- npm run build: passed
- composer audit: passed, no security advisories
- php artisan migrate:fresh --env=testing --force: passed
- php artisan db:seed --env=testing --force: passed
- php artisan test: passed, 37 tests / 486 assertions

Notes:
- v1.3.0 is the business dashboard analytics release: the admin dashboard now exposes and displays revenue metrics, order/revenue trends, License and commission status distributions, and recent orders, payment callbacks, and Licenses.
