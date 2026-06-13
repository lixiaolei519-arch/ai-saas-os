<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_and_renewal_schedules_work(): void
    {
        $tenant = $this->postJson('/api/v1/tenants', [
            'tenant_name' => 'Marketing Tenant',
            'owner_name' => 'Owner',
            'owner_email' => 'marketing-owner@example.com',
            'owner_password' => 'password123',
        ])->assertCreated()->json('data');

        $plan = $this->postJson('/api/v1/product-plans', [
            'name' => 'Renewal Plan',
            'code' => 'renewal_plan',
            'price_cents' => 9900,
        ])->assertCreated()->json('data');

        $this->postJson('/api/v1/marketing/templates', [
            'tenant_id' => $tenant['id'],
            'code' => 'renewal_notice',
            'name' => 'Renewal Notice',
            'channel' => 'email',
            'subject' => 'Renewal for {{tenant}}',
            'body' => 'Hello {{name}}, your SaaS renewal is ready.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.code', 'renewal_notice');

        $this->postJson('/api/v1/marketing/notifications/send', [
            'tenant_id' => $tenant['id'],
            'template_code' => 'renewal_notice',
            'recipient' => 'billing@example.com',
            'variables' => [
                'tenant' => 'Marketing Tenant',
                'name' => 'Billing',
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'sent')
            ->assertJsonPath('data.subject', 'Renewal for Marketing Tenant');

        $this->postJson('/api/v1/marketing/renewals', [
            'tenant_id' => $tenant['id'],
            'product_plan_id' => $plan['id'],
            'interval' => 'month',
            'next_run_at' => now()->subMinute()->toIso8601String(),
            'metadata' => ['payment_channel' => 'alipay'],
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'active');

        $this->postJson('/api/v1/marketing/renewals/process')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'pending');

        $this->assertDatabaseHas('orders', [
            'tenant_id' => $tenant['id'],
            'total_cents' => 9900,
        ]);
    }

    public function test_promotion_attribution_commissions_and_renewal_reminders_work(): void
    {
        $merchant = $this->postJson('/api/v1/tenants', [
            'tenant_name' => 'Merchant Tenant',
            'owner_name' => 'Merchant Owner',
            'owner_email' => 'merchant@example.com',
            'owner_password' => 'password123',
        ])->assertCreated()->json('data');

        $partner = $this->postJson('/api/v1/tenants', [
            'tenant_name' => 'Partner Tenant',
            'owner_name' => 'Partner Owner',
            'owner_email' => 'partner@example.com',
            'owner_password' => 'password123',
        ])->assertCreated()->json('data');

        $plan = $this->postJson('/api/v1/product-plans', [
            'name' => 'Channel Plan',
            'code' => 'channel_plan',
            'price_cents' => 9900,
        ])->assertCreated()->json('data');

        $channel = $this->postJson('/api/v1/marketing/channels', [
            'tenant_id' => $partner['id'],
            'name' => 'Partner A',
            'code' => 'partner-a',
            'type' => 'affiliate',
            'commission_rate_basis_points' => 1200,
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'active')
            ->json('data');

        $this->postJson('/api/v1/marketing/promotion-links', [
            'marketing_channel_id' => $channel['id'],
            'code' => 'AFF001',
            'destination_url' => 'https://example.cn/signup',
        ])
            ->assertCreated()
            ->assertJsonPath('data.tracking_url', 'https://example.cn/signup?ref=AFF001');

        $this->postJson('/api/v1/marketing/attributions', [
            'tenant_id' => $merchant['id'],
            'promotion_link_code' => 'AFF001',
            'metadata' => ['landing_page' => '/signup'],
        ])
            ->assertCreated()
            ->assertJsonPath('data.marketing_channel_id', $channel['id']);

        $order = $this->postJson('/api/v1/orders', [
            'tenant_id' => $merchant['id'],
            'product_plan_id' => $plan['id'],
            'quantity' => 1,
            'payment_channel' => 'wechat',
        ])
            ->assertCreated()
            ->json('data');

        $payment = $order['payments'][0];
        $signature = hash_hmac(
            'sha256',
            implode('|', [$payment['out_trade_no'], '9900', 'SUCCESS']),
            config('payments.channels.wechat.webhook_secret')
        );

        $this->postJson('/api/v1/payments/callbacks/wechat', [
            'out_trade_no' => $payment['out_trade_no'],
            'provider_trade_no' => 'wx-affiliate-001',
            'trade_status' => 'SUCCESS',
            'amount_cents' => 9900,
            'signature' => $signature,
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'processed');

        $this->assertDatabaseHas('commission_records', [
            'tenant_id' => $merchant['id'],
            'marketing_channel_id' => $channel['id'],
            'order_id' => $order['id'],
            'base_amount_cents' => 9900,
            'commission_rate_basis_points' => 1200,
            'commission_amount_cents' => 1188,
            'status' => 'pending',
        ]);

        $this->postJson('/api/v1/marketing/commissions/calculate', [
            'order_id' => $order['id'],
        ])
            ->assertOk()
            ->assertJsonPath('data.commission_amount_cents', 1188);

        $this->postJson('/api/v1/marketing/templates', [
            'tenant_id' => $merchant['id'],
            'code' => 'renewal_reminder',
            'name' => 'Renewal Reminder',
            'channel' => 'email',
            'subject' => 'Renewal reminder',
            'body' => 'Renewal due at {{next_run_at}} for {{tenant}}.',
        ])->assertCreated();

        $this->postJson('/api/v1/marketing/renewals', [
            'tenant_id' => $merchant['id'],
            'product_plan_id' => $plan['id'],
            'interval' => 'month',
            'next_run_at' => now()->addDay()->toIso8601String(),
            'metadata' => [
                'payment_channel' => 'wechat',
                'reminder_template_code' => 'renewal_reminder',
                'reminder_recipient' => 'billing@example.com',
                'remind_before_days' => 3,
                'reminder_variables' => [
                    'tenant' => 'Merchant Tenant',
                ],
            ],
        ])->assertCreated();

        $this->postJson('/api/v1/marketing/renewals/reminders/process')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'sent')
            ->assertJsonPath('data.0.metadata.source', 'renewal_reminder');

        $this->assertDatabaseHas('notification_deliveries', [
            'tenant_id' => $merchant['id'],
            'recipient' => 'billing@example.com',
            'status' => 'sent',
        ]);
    }
}
