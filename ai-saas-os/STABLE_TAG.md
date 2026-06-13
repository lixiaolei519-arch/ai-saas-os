# Stable Tag

Current stable version: v1.5.0

Date: 2026-06-14

Quality gate:
- composer install: passed
- npm install: not required, frontend source unchanged
- npm run build: not required, frontend source unchanged
- composer audit: passed, no security advisories
- php artisan migrate:fresh --env=testing --force: passed
- php artisan db:seed --env=testing --force: passed
- php artisan test: passed, 39 tests / 519 assertions

Notes:
- v1.5.0 is the production hardening release: the app now has stronger production self-checks, smoke-test probes for console/API/sensitive-file exposure, deployment backup/GitHub/Baota troubleshooting docs, and a manual Baota deployment script draft.
