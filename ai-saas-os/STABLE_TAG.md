# Stable Tag

Current stable version: v1.1.0

Date: 2026-06-14

Quality gate:
- composer install: passed
- npm install: passed
- npm run build: passed
- composer audit: passed, no security advisories
- php artisan migrate:fresh --env=testing --force: passed
- php artisan db:seed --env=testing --force: passed
- php artisan test: passed, 28 tests / 359 assertions

Notes:
- v1.1.0 is the React Ant Design Pro admin console release: Vite SPA source in frontend/admin-console, committed production build in public/console, Laravel /console fallback routing, admin system status API, and Baota deployment documentation for the console.
