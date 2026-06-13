<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_order_triggers_active_workflow(): void
    {
        $tenant = $this->postJson('/api/v1/tenants', [
            'tenant_name' => 'Workflow Tenant',
            'owner_name' => 'Owner',
            'owner_email' => 'workflow-owner@example.com',
            'owner_password' => 'password123',
        ])->assertCreated()->json('data');

        $plan = $this->postJson('/api/v1/product-plans', [
            'name' => 'Workflow Plan',
            'code' => 'workflow_plan',
            'price_cents' => 5000,
        ])->assertCreated()->json('data');

        $this->postJson('/api/v1/workflows', [
            'tenant_id' => $tenant['id'],
            'name' => 'Paid Order Automation',
            'trigger_event' => 'order.paid',
            'nodes' => [
                ['key' => 'notify', 'type' => 'notification'],
            ],
        ])->assertCreated();

        $order = $this->postJson('/api/v1/orders', [
            'tenant_id' => $tenant['id'],
            'product_plan_id' => $plan['id'],
            'payment_channel' => 'alipay',
        ])->assertCreated()->json('data');

        $payment = $order['payments'][0];
        $signature = hash_hmac(
            'sha256',
            implode('|', [$payment['out_trade_no'], '5000', 'TRADE_SUCCESS']),
            config('payments.channels.alipay.webhook_secret')
        );

        $this->postJson('/api/v1/payments/callbacks/alipay', [
            'out_trade_no' => $payment['out_trade_no'],
            'provider_trade_no' => 'ali-transaction-001',
            'trade_status' => 'TRADE_SUCCESS',
            'amount_cents' => 5000,
            'signature' => $signature,
        ])->assertOk();

        $this->assertDatabaseHas('workflow_runs', [
            'tenant_id' => $tenant['id'],
            'trigger_event' => 'order.paid',
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('workflow_run_steps', [
            'node_key' => 'notify',
            'status' => 'completed',
        ]);
    }
}
