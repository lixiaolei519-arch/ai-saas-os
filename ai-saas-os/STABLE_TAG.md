# Stable Tag

Current stable version: v0.9.0

Date: 2026-06-14

Quality gate:
- composer install: passed
- composer audit: passed, no security advisories
- php artisan migrate:fresh --env=testing --force: passed
- php artisan test: passed, 20 tests / 277 assertions

Notes:
- v0.9.0 is the deployment readiness release: environment template, Baota/Nginx/queue/scheduler documentation, /health endpoint, production checks, simulated payment notes, and prelaunch checklist.
