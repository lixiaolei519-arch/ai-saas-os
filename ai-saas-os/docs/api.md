# API Draft

Base path: `/api/v1`

## Auth

- `POST /auth/register`
- `POST /auth/login`
- `GET /auth/me`
- `POST /auth/logout`

## Tenant And Authorization

- `POST /tenants`
- `POST /permissions`
- `POST /roles`
- `POST /roles/{role}/permissions`
- `POST /tenants/{tenant}/users/{user}/roles`
- `GET /tenants/{tenant}/users/{user}/permissions/{permission}`

## License

- `POST /licenses`
- `POST /licenses/verify`

Paid orders automatically provision a License for the order tenant. The customer portal can copy the encrypted LicenseKey for newly provisioned licenses.

## Orders And Payments

- `POST /orders`
- `GET /product-plans`
- `POST /product-plans`
- `POST /payments/callbacks/wechat`
- `POST /payments/callbacks/alipay`

Payment callbacks use the simulated HMAC adapter unless real WeChat Pay or Alipay credentials are configured.

## AI Billing

- `GET /ai/accounts/{tenant}`
- `POST /ai/credits/grant`
- `POST /ai/usage`
- `POST /ai/mock/completions`

AI usage checks License validity and balance before charging. The mock completion endpoint estimates tokens, creates usage and ledger records, and returns a simulated response without calling a real model provider.

## Plugin Foundation

- `POST /plugins`
- `POST /plugins/{plugin}/releases`
- `POST /plugins/install`
- `POST /plugins/download-tokens`
- `POST /plugins/download-tokens/verify`
- `POST /plugins/updates/check`

Only delivery foundation APIs are included. Download token verification creates a download record. Advanced marketplace features and plugin code execution are intentionally out of scope.

## Workflow Foundation

- `POST /workflows`
- `POST /workflows/run`
- `POST /workflows/runs/{run}/retry`

Only basic event, condition, action, log, and retry behavior is included in v1.0.0.

## Risk

- `POST /risk/blacklist`
- `POST /risk/evaluate`
- `POST /risk/rate-limit/check`
- `POST /risk/high-risk`

## Marketing And Channel

- `POST /marketing/channels`
- `POST /marketing/promotion-links`
- `POST /marketing/attributions`
- `POST /marketing/commissions/calculate`
- `POST /marketing/templates`
- `POST /marketing/notifications/send`
- `POST /marketing/renewals`
- `POST /marketing/renewals/process`
- `POST /marketing/renewals/reminders/process`

## Admin

- `POST /admin/auth/login`
- `GET /admin/users`
- `GET /admin/tenants`
- `GET /admin/licenses`
- `GET /admin/orders`
- `GET /admin/payment-callbacks`
- `GET /admin/marketing/channels`
- `GET /admin/marketing/commissions`
- `GET /admin/ai/usage-records`
- `GET /admin/plugins`
- `GET /admin/plugin-downloads`
- `GET /admin/stats`

Admin routes require an authenticated admin token.

## Customer Portal

- `GET /portal/licenses`
- `GET /portal/orders`
- `GET /portal/ai-account`
- `GET /portal/usage-records`
- `GET /portal/plugins`
- `GET /portal/promotion-links`
- `GET /portal/commissions`
- `POST /portal/renewals`
- `GET /portal/licenses/{license}/key`
- `DELETE /portal/licenses/{license}/domain`

Portal routes only return resources that belong to the authenticated user's tenants.

## Health

- `GET /health`
