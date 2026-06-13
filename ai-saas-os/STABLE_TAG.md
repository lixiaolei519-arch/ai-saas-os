# Stable Tag

Current stable version: v2.1.0

Date: 2026-06-14

Quality gate:
- composer install: passed
- npm install: passed
- npm run build: passed
- composer audit: passed, no security advisories
- php artisan migrate:fresh --env=testing --force: passed
- php artisan db:seed --env=testing --force: passed
- php artisan test: passed, 50 tests / 721 assertions

Notes:
- v2.1.0 is the Autonomous Operations Center release: the app now stores draft-only operations reports, SEO plans, landing page copy, pricing suggestions, release announcements, customer email drafts, support FAQ, partner recruiting copy, sales lead tasks, customer follow-up tasks, and promotion tasks. It must not send email/SMS, publish pages, buy ads, contact customers, or execute external actions automatically.
