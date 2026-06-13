# AI SaaS OS

AI SaaS OS is a Laravel-based minimum commercial SaaS backend for mainland China deployment scenarios. The v1.6.0 scope adds AI billing foundation visibility on top of the launchable foundation: users, tenants, License authorization, orders, mock payment callbacks, payment adapter structure, AI billing ledger, mock AI provider, plugin foundation, workflow foundation, risk controls, marketing attribution, admin APIs, customer portal APIs, administrator console, customer portal, deployment readiness, queue/scheduler checks, and one-command deployment smoke testing.

Advanced AI autonomous operations, live payment fund capture, real model-provider calls, advanced plugin ecosystems, and complex workflow products are out of scope for v1.6.0.

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

The console includes role-aware route guards, dedicated `403` and `404` pages, unified API handling for `401`, `403`, `422`, and `500` responses, and header metadata for the stable version, Git commit, and frontend build time.

The administrator dashboard uses `/api/v1/admin/dashboard` for operational analytics: today orders, today revenue, month revenue, paid/pending orders, commission totals, seven-day order and revenue trends, License and commission status distributions, recent orders, recent payment callbacks, and recent Licenses.

The administrator console also includes `/console/ai-usage`, backed by `/api/v1/admin/ai/usage-records`, for read-only AI usage records.

The customer portal includes `/console/portal/ai-usage`, backed by `/api/v1/portal/ai-account` and `/api/v1/portal/usage-records`, for AI balance and usage visibility.

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

## Payment Adapters

Payment channels are handled through adapter classes under `app/Services/Payments`:

- `mock`: local HMAC mock payment adapter for testing and deployment verification
- `wechat`: WeChat Pay adapter structure with explicit unconfigured payloads until real credentials are set
- `alipay`: Alipay adapter structure with explicit unconfigured payloads until real credentials are set

Set `PAYMENT_PROVIDER=mock` until production payment credentials are ready. Missing WeChat Pay or Alipay credentials do not crash order creation; their payment request payloads include a clear `*_unconfigured` error. Payment callbacks validate signatures, reject amount mismatches, and ignore duplicate paid callbacks without opening a second License or creating duplicate commissions.

## AI Billing

The AI billing foundation uses the existing `ai_accounts`, `ai_usage_records`, and `balance_transactions` tables.

Mock AI completion endpoint:

```text
POST /api/v1/ai/mock/completions
```

The mock provider estimates token usage, validates the License, checks balance and token quota, writes an AI usage record, writes a ledger transaction, and returns a simulated response. It does not call OpenAI, Claude, Gemini, or any external model provider, and no real model API key is required or stored.

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
- Backup and restore guide: `docs/deployment/backup-restore.md`
- GitHub deployment guide: `docs/deployment/github-deployment.md`
- Baota troubleshooting guide: `docs/deployment/baota-troubleshooting.md`
- Prelaunch checklist: `docs/deployment/prelaunch-checklist.md`
- Security checks: `docs/security/prelaunch-security.md`
- API draft: `docs/api.md`
- Manual Baota deployment script draft: `scripts/deploy-bt.sh`

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
php artisan app:queue-check
php artisan app:smoke-test
```

`app:production-check` validates production environment readiness: `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY`, `APP_URL`, database connectivity, `DB_COLLATION`, writable `storage` and `bootstrap/cache`, queue configuration, required `.env` fields, `public/console/index.html`, `/health`, `/console`, public API JSON response, and blocked sensitive paths such as `/.env`.
`app:smoke-test` validates the minimum commercial launch flow using synthetic smoke-test data and also probes `/console`, `/api/v1/product-plans`, and sensitive-file exposure.

Queue and scheduler commands:

```bash
php artisan app:queue-check
php artisan app:renewal-reminders
php artisan app:orders-expire --minutes=30
php artisan app:commissions-settle
```

The scheduler wires renewal reminders daily, pending-order expiration every fifteen minutes, and commission settlement checks daily. These commands only write internal records/statuses and do not send real email, SMS, payouts, or external marketing actions.
