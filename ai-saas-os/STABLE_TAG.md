# Stable Tag

Current stable version: v0.7.0

Date: 2026-06-14

Quality gate:
- composer install: passed
- composer audit: passed, no security advisories
- php artisan migrate:fresh --env=testing --force: passed
- php artisan test: passed, 16 tests / 232 assertions

Notes:
- v0.7.0 is the admin foundation release: admin login, read-only backoffice resource APIs, marketing commission visibility, and basic statistics.
