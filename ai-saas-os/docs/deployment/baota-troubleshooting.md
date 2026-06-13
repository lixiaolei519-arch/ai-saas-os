# Baota Troubleshooting Guide

Scope: v1.5.0 production hardening.

## `/health` Returns 404

Check:

- Baota site root is `/www/wwwroot/ai-saas-os/public`.
- Nginx rewrite uses `try_files $uri $uri/ /index.php?$query_string;`.
- Laravel route cache was rebuilt.

Commands:

```bash
cd /www/wwwroot/ai-saas-os
php artisan route:clear
php artisan route:cache
curl -i https://ai.js3.cn/health
```

## `/console` Is Blank Or Shows 404

Check:

- `public/console/index.html` exists.
- Asset files exist under `public/console/assets`.
- The committed frontend build was deployed.

Commands:

```bash
ls -lah public/console
php artisan app:production-check
```

If frontend source changed and Node.js is available:

```bash
cd frontend/admin-console
npm install
npm run build
```

## API Returns HTML Instead Of JSON

API requests must include:

```text
Accept: application/json
```

Example:

```bash
curl -i -H "Accept: application/json" https://ai.js3.cn/api/v1/product-plans
```

## Login Returns `credentials invalid`

Create fresh deployment verification users:

```bash
php artisan app:create-demo-users
```

Use the email and password printed by the command. Do not copy placeholder passwords from documentation.

## Storage Or Cache Permission Failure

```bash
cd /www/wwwroot/ai-saas-os
chown -R www:www storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
php artisan app:production-check
```

## Sensitive Files Are Public

Immediately fix the Baota site root and Nginx deny rules. The site root must be `public`, not the project root.

Expected blocked paths:

- `/.env`
- `/.git/config`
- `/composer.json`

Run:

```bash
php artisan app:production-check
```
