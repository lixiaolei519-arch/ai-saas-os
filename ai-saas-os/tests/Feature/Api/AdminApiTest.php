<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_and_view_core_backoffice_resources(): void
    {
        $admin = User::create([
            'name' => 'System Admin',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'status' => 'active',
            'is_admin' => true,
        ]);

        $member = User::create([
            'name' => 'Regular User',
            'email' => 'member@example.com',
            'password' => 'password123',
            'status' => 'active',
        ]);

        $this->getJson('/api/v1/admin/users')
            ->assertUnauthorized();

        $this->getJson('/api/v1/admin/users', $this->bearerHeaders($member->createToken('api')->plainTextToken))
            ->assertForbidden();
        $this->flushHeaders();
        Auth::forgetGuards();

        $merchant = $this->postJson('/api/v1/tenants', [
            'tenant_name' => 'Admin Merchant',
            'owner_name' => 'Merchant Owner',
            'owner_email' => 'merchant-admin@example.com',
            'owner_password' => 'password123',
        ])->assertCreated()->json('data');

        $partner = $this->postJson('/api/v1/tenants', [
            'tenant_name' => 'Admin Partner',
            'owner_name' => 'Partner Owner',
            'owner_email' => 'partner-admin@example.com',
            'owner_password' => 'password123',
        ])->assertCreated()->json('data');

        $plan = $this->postJson('/api/v1/product-plans', [
            'name' => 'Admin Plan',
            'code' => 'admin_plan',
            'price_cents' => 20000,
        ])->assertCreated()->json('data');

        $this->postJson('/api/v1/licenses', [
            'tenant_id' => $merchant['id'],
            'product_plan_id' => $plan['id'],
            'domain' => 'admin.example.cn',
            'expires_at' => now()->addMonth()->toIso8601String(),
        ])->assertCreated();

        $channel = $this->postJson('/api/v1/marketing/channels', [
            'tenant_id' => $partner['id'],
            'name' => 'Admin Channel',
            'code' => 'admin-channel',
            'commission_rate_basis_points' => 1000,
        ])->assertCreated()->json('data');

        $this->postJson('/api/v1/marketing/promotion-links', [
            'marketing_channel_id' => $channel['id'],
            'code' => 'ADMINREF',
            'destination_url' => 'https://example.cn/admin-ref',
        ])->assertCreated();

        $this->postJson('/api/v1/marketing/attributions', [
            'tenant_id' => $merchant['id'],
            'promotion_link_code' => 'ADMINREF',
        ])->assertCreated();

        $order = $this->postJson('/api/v1/orders', [
            'tenant_id' => $merchant['id'],
            'product_plan_id' => $plan['id'],
            'payment_channel' => 'wechat',
        ])->assertCreated()->json('data');

        $payment = $order['payments'][0];
        $signature = hash_hmac(
            'sha256',
            implode('|', [$payment['out_trade_no'], '20000', 'SUCCESS']),
            config('payments.channels.wechat.webhook_secret')
        );

        $this->postJson('/api/v1/payments/callbacks/wechat', [
            'out_trade_no' => $payment['out_trade_no'],
            'provider_trade_no' => 'admin-wx-001',
            'trade_status' => 'SUCCESS',
            'amount_cents' => 20000,
            'signature' => $signature,
        ])->assertOk();

        $login = $this->postJson('/api/v1/admin/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.is_admin', true)
            ->json('data');

        $token = $login['token'];

        $this->getJson('/api/v1/auth/me', $this->bearerHeaders($token))
            ->assertOk()
            ->assertJsonPath('data.email', $admin->email)
            ->assertJsonPath('data.is_admin', true);

        $this->getJson('/api/v1/admin/users', $this->bearerHeaders($token))
            ->assertOk()
            ->assertJsonFragment(['email' => $admin->email]);

        $this->getJson('/api/v1/admin/tenants', $this->bearerHeaders($token))
            ->assertOk()
            ->assertJsonFragment(['name' => 'Admin Merchant'])
            ->assertJsonFragment(['name' => 'Admin Partner']);

        $this->getJson('/api/v1/admin/licenses', $this->bearerHeaders($token))
            ->assertOk()
            ->assertJsonFragment(['domain' => 'admin.example.cn']);

        $this->getJson('/api/v1/admin/orders', $this->bearerHeaders($token))
            ->assertOk()
            ->assertJsonFragment(['status' => 'paid']);

        $this->getJson('/api/v1/admin/payment-callbacks', $this->bearerHeaders($token))
            ->assertOk()
            ->assertJsonFragment(['provider_trade_no' => 'admin-wx-001'])
            ->assertJsonFragment(['signature_valid' => true]);

        $this->getJson('/api/v1/admin/marketing/channels', $this->bearerHeaders($token))
            ->assertOk()
            ->assertJsonFragment(['code' => 'admin-channel']);

        $this->getJson('/api/v1/admin/marketing/commissions', $this->bearerHeaders($token))
            ->assertOk()
            ->assertJsonFragment(['commission_amount_cents' => 2000]);

        $this->getJson('/api/v1/admin/stats', $this->bearerHeaders($token))
            ->assertOk()
            ->assertJsonPath('data.tenants_count', 2)
            ->assertJsonPath('data.licenses_count', 1)
            ->assertJsonPath('data.orders_count', 1)
            ->assertJsonPath('data.paid_orders_count', 1)
            ->assertJsonPath('data.payment_callbacks_count', 1)
            ->assertJsonPath('data.marketing_channels_count', 1)
            ->assertJsonPath('data.commission_records_count', 1)
            ->assertJsonPath('data.commission_amount_cents', 2000);
    }

    private function bearerHeaders(string $token): array
    {
        return [
            'Authorization' => 'Bearer '.$token,
        ];
    }
}
