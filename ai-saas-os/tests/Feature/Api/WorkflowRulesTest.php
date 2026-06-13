<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_workflow_rules_conditions_actions_and_retry_work(): void
    {
        $tenant = $this->postJson('/api/v1/tenants', [
            'tenant_name' => 'Workflow Rules Tenant',
            'owner_name' => 'Owner',
            'owner_email' => 'workflow-rules-owner@example.com',
            'owner_password' => 'password123',
        ])->assertCreated()->json('data');

        $this->postJson('/api/v1/workflows', [
            'tenant_id' => $tenant['id'],
            'name' => 'Qualified Lead Handler',
            'trigger_event' => 'lead.created',
            'nodes' => [
                ['key' => 'qualify', 'type' => 'notification', 'action' => 'notify_sales'],
            ],
            'rules' => [
                [
                    'node_key' => 'qualify',
                    'field' => 'lead.status',
                    'operator' => 'equals',
                    'expected_value' => 'qualified',
                    'action_on_fail' => 'fail',
                ],
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('data.rules.0.field', 'lead.status');

        $failedRun = $this->postJson('/api/v1/workflows/run', [
            'tenant_id' => $tenant['id'],
            'trigger_event' => 'lead.created',
            'payload' => ['lead' => ['status' => 'new']],
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'failed')
            ->assertJsonPath('data.steps.0.status', 'failed')
            ->json('data');

        $this->postJson('/api/v1/workflows/runs/'.$failedRun['id'].'/retry', [
            'payload' => ['lead' => ['status' => 'qualified']],
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.steps.0.status', 'completed')
            ->assertJsonPath('data.steps.0.output.handled_by', 'notification');

        $this->assertDatabaseHas('workflow_rules', [
            'node_key' => 'qualify',
            'field' => 'lead.status',
        ]);
        $this->assertDatabaseHas('workflow_runs', [
            'tenant_id' => $tenant['id'],
            'status' => 'failed',
        ]);
        $this->assertDatabaseHas('workflow_runs', [
            'tenant_id' => $tenant['id'],
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('workflow_event_logs', [
            'tenant_id' => $tenant['id'],
            'event_name' => 'lead.created',
            'status' => 'processed',
        ]);

        $admin = User::create([
            'name' => 'Workflow Admin',
            'email' => 'workflow-admin@example.com',
            'password' => 'password123',
            'status' => 'active',
            'is_admin' => true,
        ]);
        $token = $admin->createToken('admin')->plainTextToken;

        $this->getJson('/api/v1/admin/workflows', $this->bearerHeaders($token))
            ->assertOk()
            ->assertJsonFragment(['name' => 'Qualified Lead Handler']);

        $this->getJson('/api/v1/admin/workflow-runs', $this->bearerHeaders($token))
            ->assertOk()
            ->assertJsonFragment(['trigger_event' => 'lead.created']);

        $this->getJson('/api/v1/admin/workflow-events', $this->bearerHeaders($token))
            ->assertOk()
            ->assertJsonFragment(['event_name' => 'lead.created']);
    }

    private function bearerHeaders(string $token): array
    {
        return [
            'Authorization' => 'Bearer '.$token,
        ];
    }
}
