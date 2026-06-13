# GitHub Deployment Guide

Scope: v1.5.0 production hardening.

Repository: `https://github.com/lixiaolei519-arch/ai-saas-os.git`

## First Server Checkout

```bash
cd /www/wwwroot
git clone https://github.com/lixiaolei519-arch/ai-saas-os.git ai-saas-os
cd /www/wwwroot/ai-saas-os
git checkout main
```

Create `.env` from the production example, then configure real production values:

```bash
cp .env.production.example .env
php artisan key:generate --force
```

## Update Existing Deployment

```bash
cd /www/wwwroot/ai-saas-os
git fetch origin
git checkout main
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan app:production-check
php artisan app:smoke-test
```

## React Console

The committed build output is in `public/console`. If the server does not have Node.js, use the committed build.

If Node.js is available and frontend source changed:

```bash
cd /www/wwwroot/ai-saas-os/frontend/admin-console
npm install
npm run build
```

Then run the backend deployment checks again.

## Required Git Hygiene

- Do not commit `.env`, `vendor`, `node_modules`, or runtime storage files.
- Keep `public/console` committed for Baota servers without Node.js.
- Use `STABLE_TAG.md` and `CHANGELOG.md` to confirm the intended release.
