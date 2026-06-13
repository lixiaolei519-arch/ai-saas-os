# Stable Tag

Current stable version: v1.1.1

Date: 2026-06-14

Quality gate:
- composer install: passed
- npm install: passed
- npm run build: passed
- composer audit: passed, no security advisories
- php artisan migrate:fresh --env=testing --force: passed
- php artisan db:seed --env=testing --force: passed
- php artisan test: passed, 32 tests / 395 assertions

Notes:
- v1.1.1 is the React customer portal release: the existing frontend/admin-console Vite SPA now includes customer login, dashboard, licenses, orders, referrals, and commissions under /console/portal/*, with authenticated portal APIs scoped to the current customer and an enhanced deployment smoke test.
