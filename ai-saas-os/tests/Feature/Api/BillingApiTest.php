<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class BillingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_credits_can_be_granted_and_usage_is_charged_through_ledger(): void
    {
        $tenant = $this->postJson('/api/v1/tenants', [
            'tenant_name' => 'AI Billing Tenant',
            'owner_name' => 'Owner',
            'owner_email' => 'ai-billing-owner@example.com',
            'owner_password' => 'password123',
        ])->assertCreated()->json('data');

        $license = $this->postJson('/api/v1/licenses', [
            'tenant_id' => $tenant['id'],
            'domain' => 'ai.example.cn',
            'expires_at' => now()->addMonth()->toIso8601String(),
        ])->assertCreated()->json('data');

        $this->postJson('/api/v1/ai/credits/grant', [
            'tenant_id' => $tenant['id'],
            'amount' => 10,
            'tokens' => 10000,
            'source' => 'manual_test',
        ])
            ->assertOk()
            ->assertJsonPath('data.balance_amount', '10.000000')
            ->assertJsonPath('data.balance_tokens', 10000);

        $this->postJson('/api/v1/ai/usage', [
            'tenant_id' => $tenant['id'],
            'license_key' => $license['license_key'],
            'domain' => 'ai.example.cn',
            'fingerprint' => 'ai-worker-1',
            'request_id' => 'ai-normal-001',
            'provider' => 'openai',
            'model' => 'gpt-4.1-mini',
            'prompt_tokens' => 1000,
            'completion_tokens' => 500,
            'unit_price_per_1k' => 0.02,
        ])
            ->assertCreated()
            ->assertJsonPath('data.total_tokens', 1500)
            ->assertJsonPath('data.total_cost_amount', '0.030000')
            ->assertJsonPath('data.status', 'charged');

        $this->getJson('/api/v1/ai/accounts/'.$tenant['id'])
            ->assertOk()
            ->assertJsonPath('data.balance_amount', '9.970000')
            ->assertJsonPath('data.balance_tokens', 8500);

        $this->assertDatabaseHas('balance_transactions', [
            'type' => 'grant',
            'token_delta' => 10000,
        ]);
        $this->assertDatabaseHas('balance_transactions', [
            'type' => 'consume',
            'token_delta' => -1500,
        ]);
        $this->assertDatabaseHas('ai_usage_records', [
            'request_id' => 'ai-normal-001',
            'status' => 'charged',
        ]);
    }

    public function test_ai_usage_requires_valid_license_and_sufficient_balance(): void
    {
        $tenant = $this->postJson('/api/v1/tenants', [
            'tenant_name' => 'AI Guard Tenant',
            'owner_name' => 'Owner',
            'owner_email' => 'ai-guard-owner@example.com',
            'owner_password' => 'password123',
        ])->assertCreated()->json('data');

        $license = $this->postJson('/api/v1/licenses', [
            'tenant_id' => $tenant['id'],
            'domain' => 'guard.example.cn',
            'expires_at' => now()->addMonth()->toIso8601String(),
        ])->assertCreated()->json('data');

        $this->postJson('/api/v1/ai/usage', [
            'tenant_id' => $tenant['id'],
            'license_key' => 'invalid-license',
            'domain' => 'guard.example.cn',
            'fingerprint' => 'ai-worker-guard',
            'request_id' => 'ai-invalid-license',
            'provider' => 'openai',
            'model' => 'gpt-4.1-mini',
            'prompt_tokens' => 1000,
            'completion_tokens' => 0,
            'unit_price_per_1k' => 0.02,
        ])->assertUnprocessable();

        $this->postJson('/api/v1/ai/usage', [
            'tenant_id' => $tenant['id'],
            'license_key' => $license['license_key'],
            'domain' => 'guard.example.cn',
            'fingerprint' => 'ai-worker-guard',
            'request_id' => 'ai-insufficient-balance',
            'provider' => 'openai',
            'model' => 'gpt-4.1-mini',
            'prompt_tokens' => 1000,
            'completion_tokens' => 0,
            'unit_price_per_1k' => 0.02,
        ])->assertUnprocessable();

        $this->assertDatabaseMissing('ai_usage_records', [
            'request_id' => 'ai-invalid-license',
        ]);
        $this->assertDatabaseMissing('ai_usage_records', [
            'request_id' => 'ai-insufficient-balance',
        ]);
    }

    public function test_mock_ai_provider_charges_usage_and_is_visible_to_admin_and_customer(): void
    {
        $tenant = $this->postJson('/api/v1/tenants', [
            'tenant_name' => 'AI Mock Tenant',
            'owner_name' => 'Mock Owner',
            'owner_email' => 'ai-mock-owner@example.com',
            'owner_password' => 'password123',
            'ai_balance_amount' => 5,
            'ai_balance_tokens' => 10000,
        ])->assertCreated()->json('data');

        $license = $this->postJson('/api/v1/licenses', [
            'tenant_id' => $tenant['id'],
            'domain' => 'mock.example.cn',
            'expires_at' => now()->addMonth()->toIso8601String(),
        ])->assertCreated()->json('data');

        $this->postJson('/api/v1/ai/mock/completions', [
            'tenant_id' => $tenant['id'],
            'license_key' => $license['license_key'],
            'domain' => 'mock.example.cn',
            'fingerprint' => 'mock-worker-1',
            'request_id' => 'ai-mock-001',
            'prompt' => '请生成一个模拟回复，不调用真实大模型。',
        ])
            ->assertCreated()
            ->assertJsonPath('data.provider', 'mock')
            ->assertJsonPath('data.simulation', true)
            ->assertJsonPath('data.usage.request_id', 'ai-mock-001')
            ->assertJsonPath('data.usage.provider', 'mock')
            ->assertJsonPath('data.usage.status', 'charged');

        $this->assertDatabaseHas('ai_usage_records', [
            'request_id' => 'ai-mock-001',
            'provider' => 'mock',
            'model' => 'mock-gpt-lite',
            'status' => 'charged',
        ]);

        $admin = User::create([
            'name' => 'AI Admin',
            'email' => 'ai-admin@example.com',
            'password' => 'password123',
            'status' => 'active',
            'is_admin' => true,
        ]);

        $this->getJson('/api/v1/admin/ai/usage-records', $this->bearerHeaders($admin->createToken('admin')->plainTextToken))
            ->assertOk()
            ->assertJsonFragment(['request_id' => 'ai-mock-001'])
            ->assertJsonFragment(['provider' => 'mock']);
        $this->flushHeaders();
        Auth::forgetGuards();

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'ai-mock-owner@example.com',
            'password' => 'password123',
        ])->assertOk()->json('data');

        $balanceResponse = $this->getJson('/api/v1/portal/ai-account', $this->bearerHeaders($login['token']))
            ->assertOk()
            ->assertJsonPath('data.currency', 'CNY');

        $this->assertLessThan(10000, $balanceResponse->json('data.balance_tokens'));

        $this->getJson('/api/v1/portal/usage-records', $this->bearerHeaders($login['token']))
            ->assertOk()
            ->assertJsonFragment(['request_id' => 'ai-mock-001'])
            ->assertJsonFragment(['provider' => 'mock']);
    }

    public function test_mock_ai_provider_blocks_insufficient_balance_without_real_provider_call(): void
    {
        $tenant = $this->postJson('/api/v1/tenants', [
            'tenant_name' => 'AI Mock Block Tenant',
            'owner_name' => 'Mock Block Owner',
            'owner_email' => 'ai-mock-block@example.com',
            'owner_password' => 'password123',
        ])->assertCreated()->json('data');

        $license = $this->postJson('/api/v1/licenses', [
            'tenant_id' => $tenant['id'],
            'domain' => 'mock-block.example.cn',
            'expires_at' => now()->addMonth()->toIso8601String(),
        ])->assertCreated()->json('data');

        $this->postJson('/api/v1/ai/mock/completions', [
            'tenant_id' => $tenant['id'],
            'license_key' => $license['license_key'],
            'domain' => 'mock-block.example.cn',
            'fingerprint' => 'mock-worker-block',
            'request_id' => 'ai-mock-blocked-001',
            'prompt' => '余额不足时必须拦截。',
        ])->assertUnprocessable();

        $this->assertDatabaseMissing('ai_usage_records', [
            'request_id' => 'ai-mock-blocked-001',
        ]);
    }

    private function bearerHeaders(string $token): array
    {
        return [
            'Authorization' => 'Bearer '.$token,
        ];
    }
}
