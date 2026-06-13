<?php

namespace Tests\Feature\Api;

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
    }
}
