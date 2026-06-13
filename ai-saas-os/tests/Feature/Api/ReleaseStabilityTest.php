<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReleaseStabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_v010_stable_release_criteria_work(): void
    {
        $auth = $this->postJson('/api/v1/auth/register', [
            'name' => 'Stable User',
            'email' => 'stable@example.com',
            'password' => 'password123',
        ])
            ->assertCreated()
            ->assertJsonPath('data.user.email', 'stable@example.com')
            ->json('data');

        $this->withToken($auth['token'])
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'stable@example.com');

        $tenant = $this->postJson('/api/v1/tenants', [
            'tenant_name' => 'Stable Tenant',
            'owner_name' => 'Stable Owner',
            'owner_email' => 'stable-owner@example.com',
            'owner_password' => 'password123',
        ])
            ->assertCreated()
            ->json('data');

        $plan = $this->postJson('/api/v1/product-plans', [
            'name' => 'Stable Plan',
            'code' => 'stable_plan',
            'price_cents' => 19900,
        ])
            ->assertCreated()
            ->json('data');

        $license = $this->postJson('/api/v1/licenses', [
            'tenant_id' => $tenant['id'],
            'product_plan_id' => $plan['id'],
            'domain' => 'stable.example.cn',
            'expires_at' => now()->addMonth()->toIso8601String(),
        ])
            ->assertCreated()
            ->assertJsonPath('data.license.status', 'active')
            ->json('data');

        $this->postJson('/api/v1/licenses/verify', [
            'license_key' => $license['license_key'],
            'domain' => 'stable.example.cn',
            'fingerprint' => 'stable-server',
        ])
            ->assertOk()
            ->assertJsonPath('data.valid', true);

        $order = $this->postJson('/api/v1/orders', [
            'tenant_id' => $tenant['id'],
            'product_plan_id' => $plan['id'],
            'payment_channel' => 'wechat',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->json('data');

        $payment = $order['payments'][0];
        $signature = hash_hmac(
            'sha256',
            implode('|', [$payment['out_trade_no'], '19900', 'SUCCESS']),
            config('payments.channels.wechat.webhook_secret')
        );

        $this->postJson('/api/v1/payments/callbacks/wechat', [
            'out_trade_no' => $payment['out_trade_no'],
            'provider_trade_no' => 'stable-wx-001',
            'trade_status' => 'SUCCESS',
            'amount_cents' => 19900,
            'signature' => $signature,
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'processed');

        $this->assertDatabaseHas('orders', [
            'id' => $order['id'],
            'status' => 'paid',
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment['id'],
            'status' => 'paid',
        ]);
    }
}
