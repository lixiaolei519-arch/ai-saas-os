# AI SaaS OS

AI SaaS OS is a Laravel-based minimum commercial SaaS backend for mainland China deployment scenarios. The v2.2.0 scope adds a safe Product Factory foundation on top of the launchable foundation: users, tenants, License authorization, orders, mock payment callbacks, payment adapter structure, AI billing ledger, mock AI provider, plugin delivery records, workflow event logs, workflow execution records, AI Company OS simulation records, self-evolution scans/scores/plans/reviews/suggestions, draft-only operations records, product factory templates/drafts/checklists, risk controls, marketing attribution, admin APIs, customer portal APIs, administrator console, customer portal, deployment readiness, queue/scheduler checks, and one-command deployment smoke testing.

Autonomous code execution, production deployment, production pushes, live payment fund capture, real model-provider calls, real email/SMS sending, real ad publishing, real customer contact, real external website creation, automatic product sales, advanced plugin ecosystems, plugin code execution, external workflow calls, and complex workflow visual builders are out of scope for v2.2.0.

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

The administrator console includes `/console/plugins` for plugin package/version management and `/console/plugin-downloads` for authorized download records.

The administrator console includes `/console/workflows`, `/console/workflow-runs`, and `/console/workflow-events` for workflow definitions, execution records, and event logs.

The administrator console includes AI Company OS simulation pages: `/console/ai-company/dashboard`, `/console/ai-company/tasks`, `/console/ai-company/ideas`, `/console/ai-company/roadmap`, `/console/ai-company/releases`, `/console/ai-company/quality`, `/console/ai-company/risks`, `/console/ai-company/prompts`, and `/console/ai-company/reports`.

The administrator console includes Self-Evolution Engine pages: `/console/self-evolution/dashboard`, `/console/self-evolution/score`, `/console/self-evolution/plans`, `/console/self-evolution/release-review`, and `/console/self-evolution/suggestions`.

The administrator console includes Autonomous Operations Center pages: `/console/operations/dashboard`, `/console/operations/reports`, `/console/operations/seo-plans`, `/console/operations/landing-pages`, `/console/operations/pricing`, `/console/operations/release-announcements`, `/console/operations/customer-emails`, `/console/operations/faq`, and `/console/operations/partner-recruiting`.

The administrator console includes Product Factory pages: `/console/product-factory/dashboard`, `/console/product-factory/product-templates`, `/console/product-factory/plugin-templates`, `/console/product-factory/landing-page-templates`, `/console/product-factory/package-templates`, and `/console/product-factory/launch-checklists`.

The customer portal includes `/console/portal/ai-usage`, backed by `/api/v1/portal/ai-account` and `/api/v1/portal/usage-records`, for AI balance and usage visibility.

The customer portal includes `/console/portal/plugins`, backed by `/api/v1/portal/plugins`, for installed/downloadable plugins scoped to the logged-in customer's tenants.

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

## AI Company OS

The AI Company OS core is an internal simulation and planning layer. It creates database records for tasks, ideas, roadmaps, release plans, quality reports, risk reports, Codex prompt drafts, and daily reports. It does not edit source code, deploy, push to production, call external AI providers, send emails, send SMS, place ads, or spend money.

Safe commands:

```bash
php artisan ai-company:scan
php artisan ai-company:plan
php artisan ai-company:generate-prompts
php artisan ai-company:daily-report
```

Use `--stable-version=<version>` with `ai-company:scan` and `--target-version=<version>` with `ai-company:plan` or `ai-company:generate-prompts` when a specific version label is required. All generated tasks and prompts are `draft`, `simulation_mode=true`, and require manual approval.

## Self-Evolution Engine

The Self-Evolution Engine generates internal scans, scores, plans, release reviews, and suggestions. It can discover issues, score dimensions, propose tasks, draft version plans, and produce rollback/deployment/testing/security/business suggestions. It does not directly modify production code, deploy, push to production, call external services, or execute generated plans.

Safe commands:

```bash
php artisan self-evolve:scan
php artisan self-evolve:score
php artisan self-evolve:plan
php artisan self-evolve:review-release
```

Use `--stable-version=<version>` with scan/score, `--target-version=<version>` with plan, and `--release-version=<version>` with release review when a specific version label is required. All generated plans, reviews, and suggestions are `draft`, `simulation_mode=true`, and require manual approval.

## Autonomous Operations Center

The Autonomous Operations Center generates product daily reports, operations weekly reports, sales lead tasks, customer follow-up tasks, SEO content plans, landing page copy drafts, pricing strategy suggestions, release announcement drafts, customer email drafts, support FAQ drafts, promotion tasks, and partner recruiting copy drafts.

Safe command:

```bash
php artisan operations:generate-drafts
```

All generated records are `draft`, `simulation_mode=true`, and require manual approval. The command does not send email, send SMS, publish pages, buy ads, contact customers, or execute outbound actions.

## Product Factory

The Product Factory generates draft templates and plans for sellable software, plugins, landing pages, pricing packages, License packages, launch checklists, and Codex development prompts.

Safe command:

```bash
php artisan product-factory:generate-drafts
```

All generated records are `draft`, `simulation_mode=true`, and require manual approval. The command does not create real external websites and does not automatically sell products.

## Plugin Delivery

The plugin delivery foundation uses plugin metadata, releases, package records, installations, download tokens, and download records. It does not execute uploaded plugin code.

Administrator pages:

- `/console/plugins`
- `/console/plugin-downloads`

Customer portal page:

- `/console/portal/plugins`

Download token verification creates a `plugin_download_records` row so administrators can audit authorized package access.

## Workflow Automation

The workflow foundation records workflow definitions, rules, runs, run steps, and event logs.

Administrator pages:

- `/console/workflows`
- `/console/workflow-runs`
- `/console/workflow-events`

Supported workflow events include `order.created`, `order.paid`, `license.created`, `commission.generated`, `user.registered`, and `lead.created`. Workflow actions remain internal simulations and do not call external services.

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
