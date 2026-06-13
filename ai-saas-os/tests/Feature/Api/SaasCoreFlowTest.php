<?php

namespace Tests\Feature\Api;

use App\Models\RiskBlacklistEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaasCoreFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_saas_business_flow_works(): void
    {
        $tenant = $this->postJson('/api/v1/tenants', [
            'tenant_name' => 'Acme AI',
            'owner_name' => 'Ada Lovelace',
            'owner_email' => 'owner@example.com',
            'owner_password' => 'password123',
            'ai_balance_amount' => 100,
            'ai_balance_tokens' => 100000,
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'active')
            ->json('data');

        $plan = $this->postJson('/api/v1/product-plans', [
            'name' => 'Pro Monthly',
            'code' => 'pro_monthly',
            'type' => 'subscription',
            'status' => 'active',
            'billing_cycle' => 'month',
            'price_cents' => 19900,
            'currency' => 'CNY',
            'features' => ['license', 'ai_billing'],
        ])
            ->assertCreated()
            ->assertJsonPath('data.code', 'pro_monthly')
            ->json('data');

        $issuedLicense = $this->postJson('/api/v1/licenses', [
            'tenant_id' => $tenant['id'],
            'product_plan_id' => $plan['id'],
            'domain' => 'app.example.cn',
            'max_activations' => 1,
            'expires_at' => now()->addMonth()->toIso8601String(),
        ])
            ->assertCreated()
            ->assertJsonPath('data.license.status', 'active')
            ->json('data');

        $this->postJson('/api/v1/licenses/verify', [
            'license_key' => $issuedLicense['license_key'],
            'domain' => 'app.example.cn',
            'fingerprint' => 'server-a',
        ])
            ->assertOk()
            ->assertJsonPath('data.valid', true)
            ->assertJsonPath('data.reason', 'ok');

        $this->postJson('/api/v1/licenses/verify', [
            'license_key' => $issuedLicense['license_key'],
            'domain' => 'app.example.cn',
            'fingerprint' => 'server-b',
        ])
            ->assertOk()
            ->assertJsonPath('data.valid', false)
            ->assertJsonPath('data.reason', 'activation_limit_reached');

        $order = $this->postJson('/api/v1/orders', [
            'tenant_id' => $tenant['id'],
            'product_plan_id' => $plan['id'],
            'quantity' => 1,
            'payment_channel' => 'wechat',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->json('data');

        $payment = $order['payments'][0];
        $paymentSignature = hash_hmac(
            'sha256',
            implode('|', [$payment['out_trade_no'], '19900', 'SUCCESS']),
            config('payments.channels.wechat.webhook_secret')
        );

        $this->postJson('/api/v1/payments/callbacks/wechat', [
            'out_trade_no' => $payment['out_trade_no'],
            'provider_trade_no' => 'wx-transaction-001',
            'trade_status' => 'SUCCESS',
            'amount_cents' => 19900,
            'signature' => $paymentSignature,
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'processed');

        $this->assertDatabaseHas('orders', [
            'id' => $order['id'],
            'status' => 'paid',
        ]);

        $this->postJson('/api/v1/ai/usage', [
            'tenant_id' => $tenant['id'],
            'license_key' => $issuedLicense['license_key'],
            'domain' => 'app.example.cn',
            'fingerprint' => 'server-a',
            'request_id' => 'req-001',
            'provider' => 'openai',
            'model' => 'gpt-4.1-mini',
            'prompt_tokens' => 1000,
            'completion_tokens' => 500,
            'unit_price_per_1k' => 0.02,
        ])
            ->assertCreated()
            ->assertJsonPath('data.total_tokens', 1500)
            ->assertJsonPath('data.status', 'charged');

        $plugin = $this->postJson('/api/v1/plugins', [
            'developer_tenant_id' => $tenant['id'],
            'name' => 'Auto Renewal',
            'category' => 'marketing',
            'manifest' => ['entry' => 'renewal.php'],
            'version' => '1.0.0',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'published')
            ->json('data');

        $this->postJson('/api/v1/plugins/install', [
            'tenant_id' => $tenant['id'],
            'plugin_id' => $plugin['id'],
            'config' => ['enabled' => true],
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'installed');

        $this->postJson('/api/v1/workflows', [
            'tenant_id' => $tenant['id'],
            'name' => 'Paid Order Followup',
            'trigger_event' => 'order.paid',
            'nodes' => [
                ['key' => 'notify', 'type' => 'notification'],
                ['key' => 'renew', 'type' => 'renewal'],
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'active');

        $this->postJson('/api/v1/workflows/run', [
            'tenant_id' => $tenant['id'],
            'trigger_event' => 'order.paid',
            'payload' => ['order_id' => $order['id']],
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonCount(2, 'data.steps');

        RiskBlacklistEntry::create([
            'tenant_id' => $tenant['id'],
            'value_type' => 'ip',
            'value' => '127.0.0.2',
            'value_hash' => hash('sha256', '127.0.0.2'),
            'reason' => 'test block',
        ]);

        $this->postJson('/api/v1/risk/evaluate', [
            'tenant_id' => $tenant['id'],
            'value_type' => 'ip',
            'value' => '127.0.0.2',
        ])
            ->assertOk()
            ->assertJsonPath('data.decision', 'deny');

        $this->assertDatabaseHas('audit_events', ['action' => 'tenant.created']);
        $this->assertDatabaseHas('balance_transactions', ['type' => 'consume']);
        $this->assertDatabaseHas('risk_events', ['decision' => 'deny']);
    }
}
