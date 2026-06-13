# Stable Tag

Current stable version: v1.6.0

Date: 2026-06-14

Quality gate:
- composer install: passed
- npm install: passed
- npm run build: passed
- composer audit: passed, no security advisories
- php artisan migrate:fresh --env=testing --force: passed
- php artisan db:seed --env=testing --force: passed
- php artisan test: passed, 41 tests / 544 assertions

Notes:
- v1.6.0 is the AI billing foundation release: the app now has a mock AI provider, AI completion billing through the existing ledger, administrator AI usage visibility, and customer portal AI balance visibility without real model API credentials.
