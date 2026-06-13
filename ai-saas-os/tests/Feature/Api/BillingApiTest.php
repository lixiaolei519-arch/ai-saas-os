<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
