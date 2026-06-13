<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPortalApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_portal_exposes_only_owned_business_resources(): void
    {
        $partnerTenant = $this->postJson('/api/v1/tenants', [
            'tenant_name' => 'Portal Partner',
            'owner_name' => 'Portal Owner',
            'owner_email' => 'portal-owner@example.com',
            'owner_password' => 'password123',
            'ai_balance_amount' => 100,
            'ai_balance_tokens' => 100000,
        ])->assertCreated()->json('data');

        $buyerTenant = $this->postJson('/api/v1/tenants', [
            'tenant_name' => 'Portal Buyer',
            'owner_name' => 'Portal Owner',
            'owner_email' => 'portal-owner@example.com',
            'owner_password' => 'password123',
        ])->assertCreated()->json('data');

        $otherTenant = $this->postJson('/api/v1/tenants', [
            'tenant_name' => 'Other Customer',
            'owner_name' => 'Other Owner',
            'owner_email' => 'other-owner@example.com',
            'owner_password' => 'password123',
        ])->assertCreated()->json('data');

        $plan = $this->postJson('/api/v1/product-plans', [
            'name' => 'Portal Plan',
            'code' => 'portal_plan',
            'price_cents' => 30000,
        ])->assertCreated()->json('data');

        $licenseResult = $this->postJson('/api/v1/licenses', [
            'tenant_id' => $partnerTenant['id'],
            'product_plan_id' => $plan['id'],
            'domain' => 'portal.example.cn',
            'expires_at' => now()->addMonth()->toIso8601String(),
        ])->assertCreated()->json('data');

        $otherLicense = $this->postJson('/api/v1/licenses', [
            'tenant_id' => $otherTenant['id'],
            'product_plan_id' => $plan['id'],
            'domain' => 'other.example.cn',
        ])->assertCreated()->json('data.license');

        $this->postJson('/api/v1/ai/usage', [
            'tenant_id' => $partnerTenant['id'],
            'license_key' => $licenseResult['license_key'],
            'domain' => 'portal.example.cn',
            'fingerprint' => 'portal-ai',
            'request_id' => 'portal-usage-001',
            'provider' => 'openai',
            'model' => 'gpt-4.1-mini',
            'prompt_tokens' => 1000,
            'completion_tokens' => 1000,
            'unit_price_per_1k' => 0.01,
        ])->assertCreated();

        $channel = $this->postJson('/api/v1/marketing/channels', [
            'tenant_id' => $partnerTenant['id'],
            'name' => 'Portal Channel',
            'code' => 'portal-channel',
            'commission_rate_basis_points' => 1500,
        ])->assertCreated()->json('data');

        $this->postJson('/api/v1/marketing/promotion-links', [
            'marketing_channel_id' => $channel['id'],
            'code' => 'PORTALREF',
            'destination_url' => 'https://example.cn/portal',
        ])->assertCreated();

        $this->postJson('/api/v1/marketing/attributions', [
            'tenant_id' => $buyerTenant['id'],
            'promotion_link_code' => 'PORTALREF',
        ])->assertCreated();

        $order = $this->postJson('/api/v1/orders', [
            'tenant_id' => $buyerTenant['id'],
            'product_plan_id' => $plan['id'],
            'payment_channel' => 'alipay',
        ])->assertCreated()->json('data');

        $payment = $order['payments'][0];
        $signature = hash_hmac(
            'sha256',
            implode('|', [$payment['out_trade_no'], '30000', 'TRADE_SUCCESS']),
            config('payments.channels.alipay.webhook_secret')
        );

        $this->postJson('/api/v1/payments/callbacks/alipay', [
            'out_trade_no' => $payment['out_trade_no'],
            'provider_trade_no' => 'portal-alipay-001',
            'trade_status' => 'TRADE_SUCCESS',
            'amount_cents' => 30000,
            'signature' => $signature,
        ])->assertOk();

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'portal-owner@example.com',
            'password' => 'password123',
        ])->assertOk()->json('data');

        $token = $login['token'];

        $this->withToken($token)
            ->getJson('/api/v1/portal/licenses')
            ->assertOk()
            ->assertJsonFragment(['domain' => 'portal.example.cn'])
            ->assertJsonMissing(['domain' => 'other.example.cn']);

        $this->withToken($token)
            ->getJson('/api/v1/portal/orders')
            ->assertOk()
            ->assertJsonFragment(['order_no' => $order['order_no']])
            ->assertJsonFragment(['status' => 'paid']);

        $this->withToken($token)
            ->getJson('/api/v1/portal/usage-records')
            ->assertOk()
            ->assertJsonFragment(['request_id' => 'portal-usage-001']);

        $this->withToken($token)
            ->getJson('/api/v1/portal/promotion-links')
            ->assertOk()
            ->assertJsonFragment(['code' => 'PORTALREF'])
            ->assertJsonFragment(['tracking_url' => 'https://example.cn/portal?ref=PORTALREF']);

        $this->withToken($token)
            ->getJson('/api/v1/portal/commissions')
            ->assertOk()
            ->assertJsonFragment(['commission_amount_cents' => 4500]);

        $this->withToken($token)
            ->postJson('/api/v1/portal/renewals', [
                'tenant_id' => $partnerTenant['id'],
                'product_plan_id' => $plan['id'],
                'payment_channel' => 'wechat',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.metadata.source', 'customer_portal_renewal');

        $this->withToken($token)
            ->getJson('/api/v1/portal/licenses/'.$licenseResult['license']['id'].'/key')
            ->assertOk()
            ->assertJsonPath('data.license_key', $licenseResult['license_key']);

        $this->withToken($token)
            ->getJson('/api/v1/portal/licenses/'.$otherLicense['id'].'/key')
            ->assertNotFound();

        $this->withToken($token)
            ->deleteJson('/api/v1/portal/licenses/'.$licenseResult['license']['id'].'/domain')
            ->assertOk()
            ->assertJsonPath('data.domain', null);

        $this->assertDatabaseHas('licenses', [
            'id' => $licenseResult['license']['id'],
            'domain' => null,
            'domain_hash' => null,
        ]);
    }
}
