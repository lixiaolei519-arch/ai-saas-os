# AI SaaS OS

AI SaaS OS is a Laravel-based minimum commercial SaaS backend for mainland China deployment scenarios. The v1.1.1 scope adds the React customer portal into the existing React + Ant Design Pro console project on top of the launchable foundation: users, tenants, License authorization, orders, simulated payment callbacks, AI billing ledger, plugin foundation, workflow foundation, risk controls, marketing attribution, admin APIs, customer portal APIs, deployment readiness, and one-command deployment smoke testing.

Advanced AI autonomous operations, real payment adapters, advanced plugin ecosystems, and complex workflow products are out of scope for v1.1.1.

## Requirements

- PHP 8.2 or newer
- Composer
- MySQL 8.0 or compatible for production
- SQLite is used by automated tests
- Nginx with site root pointing to `public`
- Node.js and npm are required only when rebuilding the React console

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
php artisan db:seed --env=testing --force
php artisan test
```

Frontend console build:

```bash
cd frontend/admin-console
npm install
npm run build
```

The React source is in `frontend/admin-console`, and the committed production build is in `public/console`.

## Enterprise Console

- Administrator console URL: `https://ai.js3.cn/console/login`
- Customer portal URL: `https://ai.js3.cn/console/portal/login`
- API URL: `https://ai.js3.cn/api/v1`
- Frontend source: `frontend/admin-console`
- Build output: `public/console`

The console is one Vite SPA using React 18, Ant Design, Ant Design ProComponents, React Router, Zustand, and Axios. It contains both the administrator console and the customer portal. API requests use `VITE_API_BASE_URL=/api/v1`, store the token in `localStorage`, and send `Accept: application/json` plus `Authorization: Bearer <token>`.

Administrator users enter `/console/dashboard` after login. Customer users enter `/console/portal/dashboard` and use the dedicated portal menu for their own licenses, orders, referral links, and commissions.

Baota servers without Node.js can use the committed `public/console` build directly. After changing frontend source, rebuild it:

```bash
cd frontend/admin-console
npm install
npm run build
```

## Deployment Verification Accounts

Create temporary deployment verification accounts after migration:

```bash
php artisan app:create-demo-users
```

The command prints the admin/customer emails and generated passwords in the terminal. Use that output for login smoke tests. Do not store real production passwords in documentation or the repository.

## Deployment Smoke Test

Run the one-command commercial flow smoke test after deployment:

```bash
php artisan app:smoke-test
```

The command verifies database connectivity, key tables, `/health`, administrator and customer demo accounts, customer login, customer portal API access, customer data isolation, administrator API access, `/console/index.html`, order creation, simulated payment callback, automatic License provisioning, LicenseKey readback, License verification, promotion attribution, and commission generation. On failure it prints the failed step, reason, and suggested fix.

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
cd frontend/admin-console
npm install
npm run build
cd ../..
php artisan app:production-check
php artisan production:check
php artisan security:prelaunch
php artisan app:smoke-test
```

`app:production-check` validates production environment readiness: `APP_ENV`, `APP_KEY`, database connectivity, writable storage/cache, queue configuration, required `.env` fields, and `/health` accessibility.
`app:smoke-test` validates the minimum commercial launch flow using synthetic smoke-test data.
