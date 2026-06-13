<?php

namespace Tests\Feature\Api;

use App\Models\CommissionRecord;
use App\Models\License;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentAdapterFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mock_payment_adapter_can_provision_license(): void
    {
        [$tenant, $plan] = $this->tenantAndPlan('Mock Pay Merchant', 'mock_pay_plan', 12000);

        $order = $this->postJson('/api/v1/orders', [
            'tenant_id' => $tenant['id'],
            'product_plan_id' => $plan['id'],
            'payment_channel' => 'mock',
            'metadata' => [
                'license_domain' => 'mock-pay.example.cn',
            ],
        ])->assertCreated()
            ->assertJsonPath('data.payments.0.request_payload.channel', 'mock')
            ->json('data');

        $payment = $order['payments'][0];

        $this->postJson('/api/v1/payments/callbacks/mock', [
            'out_trade_no' => $payment['out_trade_no'],
            'provider_trade_no' => 'mock-provider-001',
            'trade_status' => 'paid',
            'amount_cents' => 12000,
            'signature' => $this->signature('mock', $payment['out_trade_no'], 12000, 'paid'),
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'processed');

        $this->assertDatabaseHas('orders', [
            'id' => $order['id'],
            'status' => 'paid',
        ]);
        $this->assertSame(1, License::where('tenant_id', $tenant['id'])->count());
    }

    public function test_unconfigured_real_payment_adapters_return_clear_payload_errors(): void
    {
        [$tenant, $plan] = $this->tenantAndPlan('Unconfigured Pay Merchant', 'unconfigured_pay_plan', 8800);

        $this->postJson('/api/v1/orders', [
            'tenant_id' => $tenant['id'],
            'product_plan_id' => $plan['id'],
            'payment_channel' => 'wechat',
        ])
            ->assertCreated()
            ->assertJsonPath('data.payments.0.request_payload.configured', false)
            ->assertJsonPath('data.payments.0.request_payload.error.code', 'wechat_pay_unconfigured');

        $this->postJson('/api/v1/orders', [
            'tenant_id' => $tenant['id'],
            'product_plan_id' => $plan['id'],
            'payment_channel' => 'alipay',
        ])
            ->assertCreated()
            ->assertJsonPath('data.payments.0.request_payload.configured', false)
            ->assertJsonPath('data.payments.0.request_payload.error.code', 'alipay_unconfigured');
    }

    public function test_amount_mismatch_and_duplicate_callbacks_do_not_duplicate_business_effects(): void
    {
        [$merchant, $plan] = $this->tenantAndPlan('Idempotent Merchant', 'idempotent_pay_plan', 50000);
        $partner = $this->postJson('/api/v1/tenants', [
            'tenant_name' => 'Idempotent Partner',
            'owner_name' => 'Partner Owner',
            'owner_email' => 'partner-idempotent@example.com',
            'owner_password' => 'password123',
        ])->assertCreated()->json('data');

        $channel = $this->postJson('/api/v1/marketing/channels', [
            'tenant_id' => $partner['id'],
            'name' => 'Idempotent Affiliate',
            'code' => 'idempotent-affiliate',
            'commission_rate_basis_points' => 1000,
        ])->assertCreated()->json('data');

        $this->postJson('/api/v1/marketing/promotion-links', [
            'marketing_channel_id' => $channel['id'],
            'code' => 'IDEMPOTENT',
            'destination_url' => 'https://example.cn/idempotent',
        ])->assertCreated();

        $this->postJson('/api/v1/marketing/attributions', [
            'tenant_id' => $merchant['id'],
            'promotion_link_code' => 'IDEMPOTENT',
        ])->assertCreated();

        $order = $this->postJson('/api/v1/orders', [
            'tenant_id' => $merchant['id'],
            'product_plan_id' => $plan['id'],
            'payment_channel' => 'mock',
            'metadata' => [
                'license_domain' => 'idempotent.example.cn',
            ],
        ])->assertCreated()->json('data');
        $payment = $order['payments'][0];

        $this->postJson('/api/v1/payments/callbacks/mock', [
            'out_trade_no' => $payment['out_trade_no'],
            'provider_trade_no' => 'mock-provider-mismatch',
            'trade_status' => 'paid',
            'amount_cents' => 49999,
            'signature' => $this->signature('mock', $payment['out_trade_no'], 49999, 'paid'),
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.error_message', 'amount_mismatch');

        $this->assertSame('pending', Order::find($order['id'])->status);
        $this->assertSame(0, License::where('tenant_id', $merchant['id'])->count());

        $validPayload = [
            'out_trade_no' => $payment['out_trade_no'],
            'provider_trade_no' => 'mock-provider-valid',
            'trade_status' => 'paid',
            'amount_cents' => 50000,
            'signature' => $this->signature('mock', $payment['out_trade_no'], 50000, 'paid'),
        ];

        $this->postJson('/api/v1/payments/callbacks/mock', $validPayload)
            ->assertOk()
            ->assertJsonPath('data.status', 'processed');

        $this->postJson('/api/v1/payments/callbacks/mock', $validPayload)
            ->assertOk()
            ->assertJsonPath('data.status', 'processed')
            ->assertJsonPath('data.error_message', 'duplicate_callback_ignored');

        $this->assertSame(1, License::where('tenant_id', $merchant['id'])->count());
        $this->assertSame(1, CommissionRecord::where('order_id', $order['id'])->count());
    }

    private function tenantAndPlan(string $tenantName, string $planCode, int $priceCents): array
    {
        $tenant = $this->postJson('/api/v1/tenants', [
            'tenant_name' => $tenantName,
            'owner_name' => $tenantName.' Owner',
            'owner_email' => str($planCode)->replace('_', '-')->append('@example.com')->value(),
            'owner_password' => 'password123',
        ])->assertCreated()->json('data');

        $plan = $this->postJson('/api/v1/product-plans', [
            'name' => $tenantName.' Plan',
            'code' => $planCode,
            'billing_cycle' => 'month',
            'price_cents' => $priceCents,
        ])->assertCreated()->json('data');

        return [$tenant, $plan];
    }

    private function signature(string $channel, string $outTradeNo, int $amountCents, string $status): string
    {
        return hash_hmac(
            'sha256',
            implode('|', [$outTradeNo, (string) $amountCents, $status]),
            config("payments.channels.{$channel}.webhook_secret")
        );
    }
}
