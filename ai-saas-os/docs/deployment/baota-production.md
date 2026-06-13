# Baota Production Deployment

This guide targets a Baota panel deployment for the minimum commercial SaaS release.

## Runtime

- PHP: 8.2 or newer, PHP 8.3 recommended
- Extensions: bcmath, ctype, curl, dom, fileinfo, mbstring, openssl, pdo_mysql, tokenizer, xml, zip
- Web server: Nginx
- Database: MySQL 8.0 or compatible
- Queue: database queue for the current release
- Scheduler: system cron calling Laravel scheduler

## Project Setup

1. Upload the project to the Baota site directory.
2. Set the site web root to `public`.
3. Copy `.env.example` to `.env`.
4. Configure `APP_URL`, `APP_KEY`, database credentials, payment secrets, and optional `LICENSE_PRIVATE_KEY` / `LICENSE_PUBLIC_KEY`.
5. Run:

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan production:check
```

## Database Initialization

Create a MySQL database and user in Baota, then set:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ai_saas_os
DB_USERNAME=ai_saas_os
DB_PASSWORD=replace-with-strong-password
```

Initialize the schema:

```bash
php artisan migrate --force
```

## Nginx Pseudo-Static Rules

Use this Nginx rewrite block in Baota:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ /\. {
    deny all;
}
```

The site root must be `public`, not the repository root.

## Queue Worker

Use Baota Supervisor or a system service:

```bash
php artisan queue:work database --sleep=3 --tries=3 --timeout=90
```

Restart workers after each deploy:

```bash
php artisan queue:restart
```

## Scheduler

Add one cron entry:

```bash
* * * * * cd /www/wwwroot/ai-saas-os && php artisan schedule:run >> /dev/null 2>&1
```

## Health Check

Use:

```text
GET /health
```

Expected response:

```json
{"status":"ok"}
```

## Simulated Payments

When real WeChat Pay or Alipay credentials are not available, keep the HMAC webhook secrets from `.env.example` and call the existing payment callback APIs with a matching signature:

```text
POST /api/v1/payments/callbacks/wechat
POST /api/v1/payments/callbacks/alipay
```

The test adapters verify `out_trade_no`, `amount_cents`, and `trade_status` with the configured webhook secret. Replace the placeholder secrets before production use.

## Production Check

Run this before switching traffic:

```bash
php artisan production:check
composer audit --no-interaction
```
