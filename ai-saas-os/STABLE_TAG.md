# Stable Tag

Current stable version: v1.2.0

Date: 2026-06-14

Quality gate:
- composer install: passed
- npm install: passed
- npm run build: passed
- composer audit: passed, no security advisories
- php artisan migrate:fresh --env=testing --force: passed
- php artisan db:seed --env=testing --force: passed
- php artisan test: passed, 37 tests / 436 assertions

Notes:
- v1.2.0 is the payment adapter foundation release: the backend now has mock, WeChat Pay, and Alipay adapter structure, explicit unconfigured payloads for real payment channels, amount mismatch rejection, duplicate callback idempotency, and commission idempotency.
