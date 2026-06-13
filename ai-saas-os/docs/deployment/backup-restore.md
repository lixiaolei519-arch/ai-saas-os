# Backup and Restore Guide

Scope: v1.5.0 production hardening.

This guide is for manual Baota/Linux server operations. Do not store backup archives in the Git repository.

## Before Every Deployment

1. Confirm the project directory:

```bash
cd /www/wwwroot/ai-saas-os
pwd
```

2. Create a database backup from Baota Panel or MySQL CLI:

```bash
mysqldump -u ai_saas_os -p --single-transaction --routines --triggers ai_saas_os > /www/backup/ai_saas_os_$(date +%Y%m%d_%H%M%S).sql
```

3. Backup the production environment file outside the repository:

```bash
cp .env /www/backup/ai_saas_os_env_$(date +%Y%m%d_%H%M%S).backup
```

4. Record the current Git commit:

```bash
git rev-parse HEAD
```

## Restore Database

Restore only after confirming the rollback window and expected data loss.

```bash
mysql -u ai_saas_os -p ai_saas_os < /www/backup/ai_saas_os_YYYYmmdd_HHMMSS.sql
```

Then rebuild Laravel caches:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

## Post-Restore Verification

```bash
php artisan app:production-check
php artisan app:smoke-test
```

Confirm:

- `GET /health` returns JSON `status=ok`.
- `/console/login` loads the React console.
- `/api/v1/product-plans` returns JSON.
- No `.env`, `.git/config`, or `composer.json` can be accessed through the public website.
