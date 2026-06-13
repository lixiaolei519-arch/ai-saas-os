# Production Checklist v2.1.0

Use this checklist before switching production traffic to `v2.1.0`.

## Release Identity

- [ ] Stable release is `v2.1.0`.
- [ ] Release commit is `Release v2.1.0 autonomous operations center`.
- [ ] `STABLE_TAG.md` says `Current stable version: v2.1.0`.
- [ ] `CHANGELOG.md` contains `v2.1.0`.
- [ ] `RELEASE_NOTES_v1.0.0.md` exists.
- [ ] `DEPLOYMENT_PACKAGE.md` exists.
- [ ] `ROLLBACK_GUIDE.md` exists.
- [ ] `docs/deployment/backup-restore.md` exists.
- [ ] `docs/deployment/github-deployment.md` exists.
- [ ] `docs/deployment/baota-troubleshooting.md` exists.
- [ ] `scripts/deploy-bt.sh` exists.

## Server

- [ ] PHP version is 8.2 or newer.
- [ ] Required PHP extensions are installed: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `zip`.
- [ ] Nginx is enabled.
- [ ] Site root points to `public`.
- [ ] HTTPS certificate is installed.
- [ ] Hidden files are denied by Nginx.

## Environment

- [ ] `.env` exists on the server.
- [ ] `.env` is not committed.
- [ ] `APP_ENV=production`.
- [ ] `APP_DEBUG=false`.
- [ ] `APP_KEY` is generated.
- [ ] `APP_URL` uses the production HTTPS domain.
- [ ] Database credentials are production credentials.
- [ ] `DB_COLLATION=utf8mb4_0900_ai_ci` or an approved MySQL-compatible collation is configured.
- [ ] Payment callback secrets are no longer placeholder values.
- [ ] `PAYMENT_PROVIDER=mock` is used until real WeChat Pay or Alipay credentials are complete.
- [ ] If real WeChat Pay is enabled, `WECHAT_PAY_MCH_ID`, `WECHAT_PAY_APP_ID`, `WECHAT_PAY_CERT_PATH`, `WECHAT_PAY_KEY_PATH`, `WECHAT_PAY_API_V3_KEY`, and `WECHAT_PAY_WEBHOOK_SECRET` are configured.
- [ ] If real Alipay is enabled, `ALIPAY_APP_ID`, `ALIPAY_PRIVATE_KEY`, `ALIPAY_PUBLIC_KEY`, and `ALIPAY_WEBHOOK_SECRET` are configured.
- [ ] `AI_PROVIDER=mock` is used until a later approved real-provider release.
- [ ] No real OpenAI, Claude, Gemini, or other model-provider API key is stored in `.env`, docs, or source code.
- [ ] AI Company OS is used only in simulation/draft mode.
- [ ] AI Company OS output cannot directly modify code, deploy, push production, send email/SMS, publish marketing content, call external model APIs, or spend money.
- [ ] Self-Evolution Engine is used only in simulation/draft mode.
- [ ] Self-Evolution Engine output cannot directly modify production code, deploy, push, call external services, send messages, publish marketing content, or spend money.
- [ ] Autonomous Operations Center is used only in simulation/draft mode.
- [ ] Autonomous Operations Center output cannot send email/SMS, publish pages, buy ads, contact customers, or execute outbound actions.
- [ ] Duplicate payment callbacks do not create duplicate Licenses or commission records.
- [ ] Amount-mismatch payment callbacks are rejected before business provisioning.
- [ ] Deployment verification accounts are created with `php artisan app:create-demo-users`.
- [ ] No real production passwords are stored in documentation or the repository.

## Database

- [ ] MySQL database exists.
- [ ] MySQL user has least required privileges.
- [ ] Database backup completed before migration.
- [ ] `php artisan migrate --force` completed.
- [ ] `php artisan db:seed --force` completed only if demo bootstrap data is required.

## Build

- [ ] `composer install --no-dev --optimize-autoloader` completed.
- [ ] React source exists at `frontend/admin-console`.
- [ ] React build output exists at `public/console`.
- [ ] If frontend source changed, rebuild completed:

```bash
cd frontend/admin-console
npm install
npm run build
```

- [ ] Baota servers without Node.js can serve the committed `public/console` build directly.
- [ ] `php artisan storage:link` completed.
- [ ] `php artisan config:cache` completed.
- [ ] `php artisan route:cache` completed.
- [ ] `php artisan view:cache` completed.

## Workers

- [ ] Queue worker command is configured:

```bash
php artisan queue:work database --sleep=3 --tries=3 --timeout=90
```

- [ ] Queue worker is supervised and restarts on failure.
- [ ] `php artisan app:queue-check` passes.
- [ ] Scheduler cron is configured:

```bash
* * * * * cd /www/wwwroot/ai-saas-os && php artisan schedule:run >> /dev/null 2>&1
```

- [ ] Scheduler command registration includes renewal reminders, order expiration, and commission settlement checks.
- [ ] No scheduler command sends real email, SMS, external marketing, or payouts without manual production configuration.

## Checks

- [ ] `composer audit --no-interaction` reports no advisories.
- [ ] `php artisan production:check` passes.
- [ ] `php artisan security:prelaunch` passes.
- [ ] `php artisan app:production-check` passes.
- [ ] `GET /health` returns `status=ok`.
- [ ] `php artisan app:production-check` confirms `/console` is accessible.
- [ ] `php artisan app:production-check` confirms `/api/v1/product-plans` returns JSON.
- [ ] `php artisan app:production-check` confirms `/.env`, `/.git/config`, and `/composer.json` are not publicly accessible.
- [ ] `https://ai.js3.cn/console/login` returns the React administrator console entry.
- [ ] `https://ai.js3.cn/console/portal/login` returns the React customer portal entry.
- [ ] `https://ai.js3.cn/api/v1` remains the API base path.

## Smoke Test

- [ ] `php artisan app:smoke-test` passes.
- [ ] Smoke test output includes `[OK] database connected`.
- [ ] Smoke test output includes `[OK] demo admin exists`.
- [ ] Smoke test output includes `[OK] demo customer exists`.
- [ ] Smoke test output includes `[OK] customer login`.
- [ ] Smoke test output includes `[OK] customer portal api accessible`.
- [ ] Smoke test output includes `[OK] customer license api is isolated`.
- [ ] Smoke test output includes `[OK] customer order api is isolated`.
- [ ] Smoke test output includes `[OK] admin api accessible`.
- [ ] Smoke test output includes `[OK] console build exists`.
- [ ] Smoke test output includes `[OK] console route accessible`.
- [ ] Smoke test output includes `[OK] api json response`.
- [ ] Smoke test output includes `[OK] sensitive files inaccessible`.
- [ ] Smoke test output includes `[OK] order created`.
- [ ] Smoke test output includes `[OK] mock payment callback`.
- [ ] Smoke test output includes `[OK] license provisioned`.
- [ ] Smoke test output includes `[OK] license verified`.
- [ ] Smoke test output includes `[OK] commission generated`.
- [ ] If smoke test fails, the printed `Reason:` and `Suggested fix:` have been resolved before launch.

## Console

- [ ] Administrator console URL is `https://ai.js3.cn/console/login`.
- [ ] Customer portal URL is `https://ai.js3.cn/console/portal/login`.
- [ ] `/console/login` displays the Chinese administrator login page.
- [ ] Administrator login succeeds and redirects to `/console/dashboard`.
- [ ] Dashboard shows users, tenants, License, orders, paid orders, commission amount, today orders, and today users.
- [ ] Dashboard analytics show today revenue, month revenue, pending orders, trends, status distributions, and recent business activity without errors.
- [ ] Users, tenants, licenses, orders, payments, channels, commissions, and system pages load without API errors.
- [ ] Administrator AI usage page `/console/ai-usage` loads without API errors.
- [ ] Administrator plugin delivery page `/console/plugins` loads without API errors.
- [ ] Administrator plugin download records page `/console/plugin-downloads` loads without API errors.
- [ ] Administrator workflow pages `/console/workflows`, `/console/workflow-runs`, and `/console/workflow-events` load without API errors.
- [ ] Administrator AI Company OS pages `/console/ai-company/dashboard`, `/console/ai-company/tasks`, `/console/ai-company/ideas`, `/console/ai-company/roadmap`, `/console/ai-company/releases`, `/console/ai-company/quality`, `/console/ai-company/risks`, `/console/ai-company/prompts`, and `/console/ai-company/reports` load without API errors.
- [ ] `php artisan ai-company:scan`, `php artisan ai-company:plan`, `php artisan ai-company:generate-prompts`, and `php artisan ai-company:daily-report` only create draft simulation records.
- [ ] Administrator Self-Evolution pages `/console/self-evolution/dashboard`, `/console/self-evolution/score`, `/console/self-evolution/plans`, `/console/self-evolution/release-review`, and `/console/self-evolution/suggestions` load without API errors.
- [ ] `php artisan self-evolve:scan`, `php artisan self-evolve:score`, `php artisan self-evolve:plan`, and `php artisan self-evolve:review-release` only create draft simulation records.
- [ ] Administrator Operations pages `/console/operations/dashboard`, `/console/operations/reports`, `/console/operations/seo-plans`, `/console/operations/landing-pages`, `/console/operations/pricing`, `/console/operations/release-announcements`, `/console/operations/customer-emails`, `/console/operations/faq`, and `/console/operations/partner-recruiting` load without API errors.
- [ ] `php artisan operations:generate-drafts` only creates draft simulation records.
- [ ] API requests include `Accept: application/json` and `Authorization: Bearer <token>` after login.
- [ ] 401 responses redirect back to `/console/login`.
- [ ] A customer attempting to open administrator pages sees a `403` page or is blocked before data loads.
- [ ] Missing console pages show the `404` page inside the SPA.
- [ ] Header metadata displays stable version, Git commit, and frontend build time.
- [ ] List pages use consistent search, loading, empty, and pagination behavior.

## Customer Portal

- [ ] `/console/portal/login` displays the Chinese customer login page.
- [ ] Customer login succeeds and redirects to `/console/portal/dashboard`.
- [ ] Customer portal pages show only the logged-in customer's licenses, orders, referral links, and commissions.
- [ ] Customer portal AI page `/console/portal/ai-usage` shows only the logged-in customer's AI balance and usage records.
- [ ] Customer portal plugin page `/console/portal/plugins` shows only installed/downloadable plugins for the logged-in customer's tenants.
- [ ] Plugin download token verification writes a download record before launch approval.
- [ ] Workflow actions remain internal simulations and do not call external services.
- [ ] AI Company OS tasks and Codex prompts remain draft records that require manual approval.
- [ ] Self-Evolution plans, release reviews, and suggestions remain draft records that require manual approval.
- [ ] Autonomous Operations drafts and tasks remain draft records that require manual approval.
- [ ] Customer-owned LicenseKey values can be copied from `/console/portal/licenses`.
- [ ] A normal customer token cannot access `/api/v1/admin/*`.
- [ ] Guest requests to `/api/v1/portal/*` return JSON `401`.
- [ ] 401 responses from portal API calls redirect back to `/console/portal/login`.
- [ ] Portal API `403` responses route to the shared `403` page.

## Launch Decision

Production traffic may be switched only after every required item above is checked.
