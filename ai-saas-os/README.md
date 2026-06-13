# AI SaaS OS

AI SaaS OS is a Laravel-based minimum commercial SaaS backend for mainland China deployment scenarios. The v1.0.0 scope is intentionally limited to the launchable foundation: users, tenants, License authorization, orders, simulated payment callbacks, AI billing ledger, plugin foundation, workflow foundation, risk controls, marketing attribution, admin APIs, customer portal APIs, and deployment readiness.

Advanced AI autonomous operations, advanced plugin ecosystems, and complex workflow products are out of scope for v1.0.0.

## Requirements

- PHP 8.2 or newer
- Composer
- MySQL 8.0 or compatible for production
- SQLite is used by automated tests
- Nginx with site root pointing to `public`

## Local Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

## Quality Gate

```bash
composer install --no-interaction
composer audit --no-interaction
php artisan migrate:fresh --env=testing --force
php artisan test
```

## Demo Accounts

Seeded defaults can be changed in `.env`:

- Admin: `admin@example.com` / `password123`
- Customer: `customer@example.com` / `password123`

Do not use default demo passwords in production.

## Commercial Flow

1. Register and log in.
2. Create a tenant.
3. Create a product plan.
4. Create a marketing channel and promotion link.
5. Record promotion attribution.
6. Create an order.
7. Send a simulated WeChat Pay or Alipay callback.
8. Payment callback marks the order paid.
9. Paid order automatically provisions a License.
10. Customer portal copies the LicenseKey.
11. License verification succeeds.
12. Commission record is generated from attribution.

## Deployment

- Baota deployment guide: `docs/deployment/baota-production.md`
- Prelaunch checklist: `docs/deployment/prelaunch-checklist.md`
- Security checks: `docs/security/prelaunch-security.md`
- API draft: `docs/api.md`

Health check:

```text
GET /health
```

Production checks:

```bash
php artisan app:production-check
php artisan production:check
php artisan security:prelaunch
```

`app:production-check` validates production environment readiness: `APP_ENV`, `APP_KEY`, database connectivity, writable storage/cache, queue configuration, required `.env` fields, and `/health` accessibility.
