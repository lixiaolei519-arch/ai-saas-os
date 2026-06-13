# Stable Tag

Current stable version: v1.1.2

Date: 2026-06-14

Quality gate:
- composer install: passed
- npm install: passed
- npm run build: passed
- composer audit: passed, no security advisories
- php artisan migrate:fresh --env=testing --force: passed
- php artisan db:seed --env=testing --force: passed
- php artisan test: passed, 34 tests / 401 assertions

Notes:
- v1.1.2 is the console permissions and UX hardening release: the existing frontend/admin-console Vite SPA now includes stronger role-aware route guards, dedicated 403 and 404 pages, unified API error handling, header version/Git/build metadata, and consistent table search/pagination behavior.
