# Prelaunch Security Checks

Run these checks before the first commercial launch:

```bash
composer audit --no-interaction
php artisan production:check
php artisan security:prelaunch
```

Required results:

- Composer reports no unresolved security advisories.
- `APP_KEY` is configured.
- `APP_DEBUG=false` outside local development.
- `RELEASE_LOCK.md` exists in the project root.
- WeChat Pay and Alipay webhook secrets are configured.
- Demo passwords are not used in production.
- `.env` is not committed and is not web-accessible.
- Nginx site root points to `public`.
- Backups exist before production traffic.

For missing real WeChat Pay or Alipay credentials during testing, use the simulated HMAC payment adapter secrets from `.env.example`. Replace them before production traffic.
