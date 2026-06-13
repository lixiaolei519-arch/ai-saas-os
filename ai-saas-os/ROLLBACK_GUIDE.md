# Rollback Guide v1.0.0

This guide covers rollback for the `v1.0.0` minimum commercial launch.

Git tag: `v1.0.0`

Stable commit: `c69377b Release v1.0.0 minimum commercial launch`

## Rollback Principles

- Do not delete production data during rollback.
- Always take a database backup before rollback.
- Prefer code rollback first.
- Treat schema rollback as a separate, manual decision.
- Preserve payment callbacks, orders, licenses, and commission records.

## Fast Code Rollback

Use when code deployment fails but database migration completed safely.

```bash
git fetch --all --tags
git checkout v1.0.0
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan production:check
```

Verify:

```bash
curl https://your-domain.example/health
```

Expected:

```json
{"status":"ok"}
```

## Roll Back To Previous Deployment

Use when the deployed server has a previous known-good release directory.

1. Stop traffic or switch load balancer to maintenance page.
2. Stop queue worker.
3. Back up current database.
4. Switch symlink or Baota site root back to previous release directory.
5. Run:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan production:check
```

6. Re-enable traffic after smoke tests pass.

## Database Rollback

Database rollback is high risk because v1.0.0 includes commercial records:

- Orders
- Payments
- Payment callbacks
- Licenses
- License activations
- Promotion attributions
- Commission records

Do not run `migrate:rollback` in production unless:

- A fresh backup exists.
- Traffic is stopped.
- Payment callbacks are paused.
- The exact target schema is confirmed.
- Data retention impact is accepted.

Preferred database recovery path:

1. Keep schema forward-compatible.
2. Apply a hotfix migration if needed.
3. Restore from backup only if data corruption occurred and business accepts the data loss window.

## Payment Callback Incident

If callbacks fail:

1. Stop retry workers if they are amplifying failure.
2. Preserve callback payloads.
3. Confirm webhook secret configuration.
4. Confirm `out_trade_no`, `amount_cents`, and `trade_status`.
5. Re-run callback manually only after signature and order status are verified.

## License Provisioning Incident

If paid orders do not provision License:

1. Confirm order status is `paid`.
2. Confirm order item has a product plan.
3. Check `orders.metadata.provisioned_license_id`.
4. If missing, issue a License manually through `POST /api/v1/licenses`.
5. Record the manual remediation in operations notes.

## Commission Incident

If commission is missing:

1. Confirm promotion attribution exists for the buyer tenant.
2. Confirm marketing channel is active.
3. Confirm commission rate is greater than zero.
4. Call `POST /api/v1/marketing/commissions/calculate` with the paid `order_id`.

## Post-Rollback Validation

- `GET /health` returns ok.
- Admin login works.
- Customer login works.
- Existing paid orders are readable.
- Existing licenses verify.
- Queue worker is running.
- Scheduler is running.
- Error logs are stable for at least 15 minutes.
