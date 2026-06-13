# Prelaunch Checklist

## Environment

- `.env` exists and is not committed.
- `APP_ENV=production`.
- `APP_DEBUG=false`.
- `APP_KEY` is generated.
- `APP_URL` uses the production HTTPS domain.
- `LICENSE_PRIVATE_KEY` / `LICENSE_PUBLIC_KEY` are configured for production signing, or the APP_KEY fallback has been explicitly accepted for the first launch.
- WeChat Pay and Alipay webhook secrets are configured, or simulated payment mode is explicitly accepted for testing only.

## Database

- MySQL database and user exist.
- Database password is strong.
- `php artisan migrate --force` has completed.
- A database backup policy exists before first production traffic.

## Web Server

- Baota site root points to `public`.
- Nginx pseudo-static rules use `try_files $uri $uri/ /index.php?$query_string`.
- HTTPS certificate is installed.
- Hidden files such as `.env` are denied by Nginx.

## Laravel Runtime

- `composer install --no-dev --optimize-autoloader` has completed.
- `php artisan config:cache` has completed.
- `php artisan route:cache` has completed.
- `php artisan view:cache` has completed.
- `php artisan production:check` passes.
- `composer audit --no-interaction` reports no advisories.

## Workers

- Queue worker is managed by Supervisor or Baota process manager.
- `php artisan queue:work database --sleep=3 --tries=3 --timeout=90` is running.
- Cron calls `php artisan schedule:run` every minute.

## Smoke Tests

- `GET /health` returns `status=ok`.
- User registration and login work.
- Order creation works.
- Simulated payment callback marks the order paid.
- License issuance and verification work.
- Promotion attribution creates commission records after payment.
- Admin login and backoffice stats work.
- Customer portal can view owned licenses and orders.
