# Stable Tag

Current stable version: v1.0.1

Date: 2026-06-14

Quality gate:
- composer install: passed
- composer audit: passed, no security advisories
- php artisan migrate:fresh --env=testing --force: passed
- php artisan test: passed, 24 tests / 342 assertions

Notes:
- v1.0.1 is the deployment readiness patch release: production self-checks, demo user creation for deployment verification, one-command commercial smoke test, Baota deployment updates, and stable v1.0.1 delivery documentation.
