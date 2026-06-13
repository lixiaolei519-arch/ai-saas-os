<?php

namespace Tests\Feature\Api;

use App\Models\CommissionRecord;
use App\Models\License;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MinimumCommercialLaunchTest extends TestCase
{
    use RefreshDatabase;

    public function test_minimum_commercial_launch_flow_works_end_to_end(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Launch Customer',
            'email' => 'launch-customer@example.com',
            'password' => 'password123',
        ])->assertCreated();

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'launch-customer@example.com',
            'password' => 'password123',
        ])->assertOk()->json('data');

        $merchant = $this->postJson('/api/v1/tenants', [
            'tenant_name' => 'Launch Merchant',
            'owner_name' => 'Launch Customer',
            'owner_email' => 'launch-customer@example.com',
            'owner_password' => 'password123',
        ])->assertCreated()->json('data');

        $partner = $this->postJson('/api/v1/tenants', [
            'tenant_name' => 'Launch Partner',
            'owner_name' => 'Launch Customer',
            'owner_email' => 'launch-customer@example.com',
            'owner_password' => 'password123',
        ])->assertCreated()->json('data');

        $plan = $this->postJson('/api/v1/product-plans', [
            'name' => 'Launch Monthly',
            'code' => 'launch_monthly',
            'billing_cycle' => 'month',
            'price_cents' => 50000,
        ])->assertCreated()->json('data');

        $channel = $this->postJson('/api/v1/marketing/channels', [
            'tenant_id' => $partner['id'],
            'name' => 'Launch Affiliate',
            'code' => 'launch-affiliate',
            'commission_rate_basis_points' => 1000,
        ])->assertCreated()->json('data');

        $this->postJson('/api/v1/marketing/promotion-links', [
            'marketing_channel_id' => $channel['id'],
            'code' => 'LAUNCHREF',
            'destination_url' => 'https://example.cn/register',
        ])->assertCreated();

        $this->postJson('/api/v1/marketing/attributions', [
            'tenant_id' => $merchant['id'],
            'promotion_link_code' => 'LAUNCHREF',
        ])->assertCreated();

        $order = $this->postJson('/api/v1/orders', [
            'tenant_id' => $merchant['id'],
            'product_plan_id' => $plan['id'],
            'payment_channel' => 'wechat',
            'metadata' => [
                'license_domain' => 'launch.example.cn',
            ],
        ])->assertCreated()->json('data');

        $payment = $order['payments'][0];
        $signature = hash_hmac(
            'sha256',
            implode('|', [$payment['out_trade_no'], '50000', 'SUCCESS']),
            config('payments.channels.wechat.webhook_secret')
        );

        $this->postJson('/api/v1/payments/callbacks/wechat', [
            'out_trade_no' => $payment['out_trade_no'],
            'provider_trade_no' => 'launch-wx-001',
            'trade_status' => 'SUCCESS',
            'amount_cents' => 50000,
            'signature' => $signature,
        ])->assertOk()->assertJsonPath('data.status', 'processed');

        $this->assertDatabaseHas('orders', [
            'id' => $order['id'],
            'status' => 'paid',
        ]);

        $license = License::where('tenant_id', $merchant['id'])->latest('id')->firstOrFail();
        $this->assertSame('launch.example.cn', $license->domain);
        $this->assertSame('paid_order', $license->metadata['source'] ?? null);
        $this->assertSame($order['id'], $license->metadata['source_order_id'] ?? null);

        $key = $this->withToken($login['token'])
            ->getJson('/api/v1/portal/licenses/'.$license->id.'/key')
            ->assertOk()
            ->json('data.license_key');

        $this->postJson('/api/v1/licenses/verify', [
            'license_key' => $key,
            'domain' => 'launch.example.cn',
            'fingerprint' => 'launch-server',
        ])
            ->assertOk()
            ->assertJsonPath('data.valid', true)
            ->assertJsonPath('data.reason', 'ok');

        $commission = CommissionRecord::where('order_id', $order['id'])->firstOrFail();
        $this->assertSame(5000, $commission->commission_amount_cents);
        $this->assertSame($channel['id'], $commission->marketing_channel_id);
    }
}
