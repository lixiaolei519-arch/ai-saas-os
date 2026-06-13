# Production Checklist v1.0.1

Use this checklist before switching production traffic to `v1.0.1`.

## Release Identity

- [ ] Stable release is `v1.0.1`.
- [ ] Release commit is `Release v1.0.1 deployment smoke test`.
- [ ] `STABLE_TAG.md` says `Current stable version: v1.0.1`.
- [ ] `CHANGELOG.md` contains `v1.0.1`.
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
- [ ] `php artisan app:production-check` passes.
- [ ] `GET /health` returns `status=ok`.

## Smoke Test

- [ ] `php artisan app:smoke-test` passes.
- [ ] Smoke test output includes `[OK] database connected`.
- [ ] Smoke test output includes `[OK] customer login`.
- [ ] Smoke test output includes `[OK] order created`.
- [ ] Smoke test output includes `[OK] mock payment callback`.
- [ ] Smoke test output includes `[OK] license provisioned`.
- [ ] Smoke test output includes `[OK] license verified`.
- [ ] Smoke test output includes `[OK] commission generated`.
- [ ] If smoke test fails, the printed `Reason:` and `Suggested fix:` have been resolved before launch.

## Launch Decision

Production traffic may be switched only after every required item above is checked.
