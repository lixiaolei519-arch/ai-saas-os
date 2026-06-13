# Stable Tag

Current stable version: v2.0.0

Date: 2026-06-14

Quality gate:
- composer install: passed
- npm install: passed
- npm run build: passed
- composer audit: passed, no security advisories
- php artisan migrate:fresh --env=testing --force: passed
- php artisan db:seed --env=testing --force: passed
- php artisan test: passed, 47 tests / 674 assertions

Notes:
- v2.0.0 is the Self-Evolution Engine release: the app now stores internal scans, scores, plans, release reviews, and suggestions in simulation mode. It can only generate plans, tasks, prompts, and drafts; it must not directly modify production code, deploy, push, call external services, spend money, send messages, or publish marketing content.
