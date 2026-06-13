# Production Checklist v1.0.0

Use this checklist before switching production traffic to `v1.0.0`.

## Release Identity

- [ ] Git tag is `v1.0.0`.
- [ ] Tagged commit is `c69377b`.
- [ ] `STABLE_TAG.md` says `Current stable version: v1.0.0`.
- [ ] `CHANGELOG.md` contains `v1.0.0`.
- [ ] `RELEASE_NOTES_v1.0.0.md` exists.
- [ ] `DEPLOYMENT_PACKAGE.md` exists.
- [ ] `ROLLBACK_GUIDE.md` exists.

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
- [ ] Payment callback secrets are no longer placeholder values.
- [ ] Demo passwords have been changed.

## Database

- [ ] MySQL database exists.
- [ ] MySQL user has least required privileges.
- [ ] Database backup completed before migration.
- [ ] `php artisan migrate --force` completed.
- [ ] `php artisan db:seed --force` completed only if demo bootstrap data is required.

## Build

- [ ] `composer install --no-dev --optimize-autoloader` completed.
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
- [ ] Scheduler cron is configured:

```bash
* * * * * cd /www/wwwroot/ai-saas-os && php artisan schedule:run >> /dev/null 2>&1
```

## Checks

- [ ] `composer audit --no-interaction` reports no advisories.
- [ ] `php artisan production:check` passes.
- [ ] `php artisan security:prelaunch` passes.
- [ ] `GET /health` returns `status=ok`.

## Smoke Test

- [ ] User registration works.
- [ ] User login works.
- [ ] Tenant creation works.
- [ ] Product plan exists.
- [ ] Order creation works.
- [ ] Simulated or real payment callback marks order paid.
- [ ] Paid order automatically provisions License.
- [ ] Customer portal can copy LicenseKey.
- [ ] License verification works.
- [ ] Promotion attribution creates commission.
- [ ] Admin can view stats.

## Launch Decision

Production traffic may be switched only after every required item above is checked.
