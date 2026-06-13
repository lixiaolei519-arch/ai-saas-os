# Stable Tag

Current stable version: v0.8.0

Date: 2026-06-14

Quality gate:
- composer install: passed
- composer audit: passed, no security advisories
- php artisan migrate:fresh --env=testing --force: passed
- php artisan test: passed, 17 tests / 267 assertions

Notes:
- v0.8.0 is the customer portal foundation release: owned license/order/usage/promotion/commission views, renewal requests, LicenseKey copy, and domain unbinding.
