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

## AI Company OS

AI Company OS v1.9.0 is internal simulation only. Artisan commands create draft tasks, ideas, roadmaps, release plans, quality reports, risk reports, Codex prompt drafts, and daily reports. They do not call external AI providers, modify code, deploy, push to production, send messages, publish marketing content, or spend money.

Safe commands:

- `php artisan ai-company:scan`
- `php artisan ai-company:plan`
- `php artisan ai-company:generate-prompts`
- `php artisan ai-company:daily-report`

## Self-Evolution Engine

Self-Evolution Engine v2.0.0 is internal simulation only. Artisan commands create draft scans, scores, plans, release reviews, rollback suggestions, deployment suggestions, testing suggestions, security suggestions, and business suggestions. They do not modify production code, deploy, push, call external services, send messages, publish marketing content, or spend money.

Safe commands:

- `php artisan self-evolve:scan`
- `php artisan self-evolve:score`
- `php artisan self-evolve:plan`
- `php artisan self-evolve:review-release`

## Autonomous Operations Center

Autonomous Operations Center v2.1.0 is internal simulation only. It creates draft reports, SEO plans, landing page copy, pricing suggestions, release announcements, customer emails, FAQ content, partner recruiting copy, sales lead tasks, customer follow-up tasks, and promotion tasks. It does not send email/SMS, publish pages, buy ads, contact customers, or execute outbound actions.

Safe command:

- `php artisan operations:generate-drafts`

## Product Factory

Product Factory v2.2.0 is internal simulation only. It creates product templates, plugin templates, landing page templates, pricing package templates, License package templates, generated product drafts, launch checklists, and Codex prompt drafts. It does not create real external websites or automatically sell products.

Safe command:

- `php artisan product-factory:generate-drafts`

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

Only internal event, condition, action, log, and retry behavior is included. Workflow actions do not call external services.

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
- `GET /admin/workflows`
- `GET /admin/workflow-runs`
- `GET /admin/workflow-events`
- `GET /admin/ai-company/dashboard`
- `GET /admin/ai-company/tasks`
- `GET /admin/ai-company/ideas`
- `GET /admin/ai-company/roadmaps`
- `GET /admin/ai-company/release-plans`
- `GET /admin/ai-company/quality-reports`
- `GET /admin/ai-company/risk-reports`
- `GET /admin/ai-company/codex-prompts`
- `GET /admin/ai-company/daily-reports`
- `GET /admin/self-evolution/dashboard`
- `GET /admin/self-evolution/scans`
- `GET /admin/self-evolution/scores`
- `GET /admin/self-evolution/plans`
- `GET /admin/self-evolution/release-reviews`
- `GET /admin/self-evolution/suggestions`
- `GET /admin/operations/dashboard`
- `GET /admin/operations/reports`
- `GET /admin/operations/seo-plans`
- `GET /admin/operations/landing-pages`
- `GET /admin/operations/pricing`
- `GET /admin/operations/release-announcements`
- `GET /admin/operations/customer-emails`
- `GET /admin/operations/faq`
- `GET /admin/operations/partner-recruiting`
- `GET /admin/product-factory/dashboard`
- `GET /admin/product-factory/product-templates`
- `GET /admin/product-factory/plugin-templates`
- `GET /admin/product-factory/landing-page-templates`
- `GET /admin/product-factory/package-templates`
- `GET /admin/product-factory/launch-checklists`
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
