# Database Design

This project starts with a single shared database and tenant-scoped rows. The first production target is MySQL, while tests use SQLite in memory. String status fields are used instead of database enums so migrations run consistently in both environments.

## Core ownership

- `tenants`: customer accounts. All commercial, license, billing, plugin, workflow, and risk data is scoped here.
- `users`: platform users.
- `tenant_user`: tenant membership with a simple owner/admin/member role for fast authorization.
- `roles`, `permissions`, `permission_role`, `role_user`: extensible permission model for tenant-scoped RBAC.
- `audit_events`: immutable business activity trail.

## License system

- `licenses`: stores only a hash of the license key, signed license payload, bound domain, activation limits, status, and expiry.
- `license_activations`: tracks device or deployment fingerprints, IP, domain, last-seen time, and revocation.

## Commerce and payments

- `product_plans`: sellable SaaS plans or packages.
- `orders`, `order_items`: order ledger. All revenue starts here.
- `payments`: WeChat Pay and Alipay transaction records.
- `payment_callbacks`: raw callback capture and processing result for traceability.

## AI billing

- `ai_accounts`: tenant balance in money and token units.
- `ai_usage_records`: model usage, token count, and calculated cost per request.
- `balance_transactions`: append-only balance movements linked to usage, orders, refunds, or manual adjustments.

## Plugin marketplace

- `plugins`: marketplace listing and manifest.
- `plugin_releases`: versioned upload packages.
- `plugin_installations`: tenant installations and runtime config.

## Workflow automation

- `workflow_definitions`: event-triggered workflow graph.
- `workflow_runs`, `workflow_run_steps`: execution history and step results.

## Risk control

- `risk_rules`: global or tenant-specific rules.
- `risk_events`: evaluated events and decisions.
- `risk_blacklist_entries`: hashed IP/domain/fingerprint/user/license blacklist entries.

## Marketing automation

- `notification_templates`: reusable tenant or global templates for email, SMS, WeChat, and in-app channels.
- `notification_deliveries`: rendered delivery log with status and trace metadata.
- `renewal_schedules`: automatic renewal jobs that create new orders when due.
