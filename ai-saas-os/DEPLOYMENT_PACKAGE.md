# Deployment Package v1.0.0

Git tag: `v1.0.0`

Stable commit: `c69377b Release v1.0.0 minimum commercial launch`

## Package Contents

Required repository files:

- Laravel application source.
- `composer.json` and `composer.lock`.
- `.env.example`.
- `database/migrations/*`.
- `database/seeders/DatabaseSeeder.php`.
- `README.md`.
- `RELEASE_NOTES_v1.0.0.md`.
- `PRODUCTION_CHECKLIST.md`.
- `ROLLBACK_GUIDE.md`.
- `docs/api.md`.
- `docs/deployment/baota-production.md`.
- `docs/deployment/backup-restore.md`.
- `docs/deployment/github-deployment.md`.
- `docs/deployment/baota-troubleshooting.md`.
- `docs/deployment/prelaunch-checklist.md`.
- `docs/security/prelaunch-security.md`.
- `scripts/deploy-bt.sh`.

Do not package:

- `.env`
- `.env.backup`
- `.env.production`
- `vendor/`
- `node_modules/`
- `.phpunit.result.cache`
- Runtime logs

## Target Runtime

- PHP 8.2 or newer, PHP 8.3 recommended.
- MySQL 8.0 or compatible.
- Nginx.
- Baota panel supported.
- Queue driver: database queue.
- Scheduler: system cron.

## Build Commands

Run on the production server:

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan production:check
php artisan security:prelaunch
```

Run the security audit before traffic:

```bash
composer audit --no-interaction
```

## Environment Values To Replace

Replace these before production traffic:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
DB_DATABASE=ai_saas_os
DB_USERNAME=ai_saas_os
DB_PASSWORD=replace-with-strong-password
WECHAT_PAY_WEBHOOK_SECRET=replace-with-real-secret
ALIPAY_WEBHOOK_SECRET=replace-with-real-secret
ADMIN_DEMO_PASSWORD=replace-with-strong-password
CUSTOMER_DEMO_PASSWORD=replace-with-strong-password
```

Optional production License signing:

```env
LICENSE_PRIVATE_KEY=
LICENSE_PUBLIC_KEY=
```

If RSA keys are not configured, License signing falls back to `APP_KEY`. For a controlled first launch this is acceptable only if explicitly approved.

## Web Server

Set Baota/Nginx site root to:

```text
public
```

Pseudo-static rule:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ /\. {
    deny all;
}
```

## Queue Worker

Run through Baota Supervisor or process manager:

```bash
php artisan queue:work database --sleep=3 --tries=3 --timeout=90
```

After deploy:

```bash
php artisan queue:restart
```

## Scheduler

Cron:

```bash
* * * * * cd /www/wwwroot/ai-saas-os && php artisan schedule:run >> /dev/null 2>&1
```

## Smoke Test

After deployment:

```bash
curl https://your-domain.example/health
```

Expected:

```json
{"status":"ok"}
```

Then verify:

- Admin login works.
- Customer login works.
- Order creation works.
- Simulated payment callback works in staging.
- Paid order provisions License.
- License verification works.
- Promotion attribution creates commission.
