# Release Notes v1.0.0

Release date: 2026-06-14

Git tag: `v1.0.0`

Stable commit: `c69377b Release v1.0.0 minimum commercial launch`

## Release Status

`v1.0.0` is the minimum commercial launch release. It is production-ready for a controlled first launch after completing the deployment checklist and replacing demo secrets/passwords.

## Included Scope

- User registration, login, profile, and logout.
- Tenant creation with owner binding.
- License issue, encrypted LicenseKey storage, customer LicenseKey copy, domain unbind, and License verification.
- Product plan, order creation, payment record creation, and HMAC simulated WeChat Pay / Alipay callbacks.
- Paid-order automatic License provisioning.
- AI billing foundation with balance account, credit grant, usage charging, License check, and ledger records.
- Plugin foundation with release package metadata, installation records, download token issue/verify, and update checks.
- Workflow foundation with event trigger, rule evaluation, action output, execution logs, and retry.
- Risk foundation with blacklist, rate-limit checks, abnormal License records, risk events, and high-risk operation logs.
- Marketing channel foundation with promotion links, attribution, commission records, renewal reminders, and notification delivery.
- Admin foundation APIs for users, tenants, licenses, orders, payment callbacks, channels, commissions, and basic stats.
- Customer portal APIs for owned licenses, orders, usage records, promotion links, commissions, renewal request, LicenseKey copy, and domain unbind.
- `/health` endpoint.
- Production readiness and prelaunch security commands.
- Baota deployment guide, prelaunch checklist, security checklist, and API draft.

## Explicitly Excluded

- Large-scale AI autonomous operations.
- Advanced plugin marketplace ecosystem.
- Complex workflow builder or advanced workflow orchestration.
- Real WeChat Pay / Alipay production SDK integration.
- Production server provisioning automation.

## Quality Gate Result

Last verified before sealing:

```bash
composer install --no-interaction
composer audit --no-interaction
php artisan migrate:fresh --env=testing --force
php artisan db:seed --env=testing --force
php artisan test
```

Result:

- `composer install --no-interaction`: passed
- `composer audit --no-interaction`: no advisories
- `php artisan migrate:fresh --env=testing --force`: passed
- `php artisan db:seed --env=testing --force`: passed
- `php artisan test`: 21 passed / 302 assertions

## Minimum Commercial Flow

The covered launch path is:

1. Register and log in.
2. Create tenant.
3. Create product plan.
4. Create marketing channel and promotion link.
5. Record promotion attribution.
6. Create order.
7. Send simulated payment callback.
8. Mark order paid.
9. Automatically provision License.
10. Copy LicenseKey from customer portal.
11. Verify License.
12. Generate commission record.

## Deployment Artifacts

- `DEPLOYMENT_PACKAGE.md`
- `PRODUCTION_CHECKLIST.md`
- `ROLLBACK_GUIDE.md`
- `docs/deployment/baota-production.md`
- `docs/deployment/prelaunch-checklist.md`
- `docs/security/prelaunch-security.md`
- `docs/api.md`

## Operational Notes

- Use simulated HMAC payment callbacks only for testing or controlled staging.
- Replace all placeholder webhook secrets before production traffic.
- Replace demo account passwords before production traffic.
- Run `php artisan production:check` and `php artisan security:prelaunch` before switching traffic.
- Keep a database backup before and after first production migration.
