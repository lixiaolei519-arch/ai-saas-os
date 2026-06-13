<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiskApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_blacklist_rate_limit_license_exception_and_high_risk_events_work(): void
    {
        $tenant = $this->postJson('/api/v1/tenants', [
            'tenant_name' => 'Risk Tenant',
            'owner_name' => 'Owner',
            'owner_email' => 'risk-owner@example.com',
            'owner_password' => 'password123',
        ])->assertCreated()->json('data');

        $this->postJson('/api/v1/risk/blacklist', [
            'tenant_id' => $tenant['id'],
            'value_type' => 'ip',
            'value' => '10.0.0.9',
            'reason' => 'test block',
        ])
            ->assertCreated()
            ->assertJsonPath('data.value_type', 'ip');

        $this->postJson('/api/v1/risk/evaluate', [
            'tenant_id' => $tenant['id'],
            'value_type' => 'ip',
            'value' => '10.0.0.9',
        ])
            ->assertOk()
            ->assertJsonPath('data.decision', 'deny');

        $ratePayload = [
            'tenant_id' => $tenant['id'],
            'key' => 'POST:/api/v1/licenses/verify:10.0.0.9',
            'max_attempts' => 2,
            'decay_seconds' => 60,
        ];

        $this->postJson('/api/v1/risk/rate-limit/check', $ratePayload)
            ->assertOk()
            ->assertJsonPath('data.decision', 'allow');
        $this->postJson('/api/v1/risk/rate-limit/check', $ratePayload)
            ->assertOk()
            ->assertJsonPath('data.decision', 'allow');
        $this->postJson('/api/v1/risk/rate-limit/check', $ratePayload)
            ->assertOk()
            ->assertJsonPath('data.decision', 'deny');

        $this->postJson('/api/v1/licenses/verify', [
            'license_key' => 'invalid-license',
            'domain' => 'risk.example.cn',
            'fingerprint' => 'risk-node',
        ])
            ->assertOk()
            ->assertJsonPath('data.valid', false);

        $this->postJson('/api/v1/risk/high-risk', [
            'tenant_id' => $tenant['id'],
            'severity' => 'critical',
            'decision' => 'review',
            'context' => ['operation' => 'manual_license_reset'],
        ])
            ->assertCreated()
            ->assertJsonPath('data.event_type', 'risk.high_risk_operation');

        $this->assertDatabaseHas('risk_events', [
            'event_type' => 'risk.blacklist.evaluate',
            'decision' => 'deny',
        ]);
        $this->assertDatabaseHas('risk_events', [
            'event_type' => 'risk.rate_limit.exceeded',
            'decision' => 'deny',
        ]);
        $this->assertDatabaseHas('risk_events', [
            'event_type' => 'license.invalid_key',
            'decision' => 'deny',
        ]);
        $this->assertDatabaseHas('risk_events', [
            'event_type' => 'risk.high_risk_operation',
            'severity' => 'critical',
        ]);
    }
}
