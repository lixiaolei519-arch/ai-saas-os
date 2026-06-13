<?php

namespace Tests\Feature\Api;

use App\Models\CommissionRecord;
use App\Models\MarketingChannel;
use App\Models\Order;
use App\Models\PromotionAttribution;
use App\Models\PromotionLink;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Tests\TestCase;

class QueueSchedulerFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_check_command_reports_ready_storage(): void
    {
        $exitCode = Artisan::call('app:queue-check');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode, $output);
        $this->assertStringContainsString('[PASS] queue connection configured', $output);
        $this->assertStringContainsString('[PASS] jobs table exists', $output);
        $this->assertStringContainsString('[PASS] failed_jobs table exists', $output);
        $this->assertStringContainsString('[INFO] pending jobs:', $output);
        $this->assertStringContainsString('[INFO] failed jobs:', $output);
    }

    public function test_scheduler_foundation_commands_execute_without_external_side_effects(): void
    {
        $tenant = Tenant::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Queue Tenant',
            'slug' => 'queue-tenant',
            'contact_name' => 'Queue Owner',
            'contact_email' => 'queue@example.com',
            'status' => 'active',
            'plan_code' => 'free',
        ]);

        $order = Order::create([
            'tenant_id' => $tenant->id,
            'order_no' => 'QUEUE-ORDER-001',
            'status' => 'pending',
            'subtotal_cents' => 10000,
            'discount_cents' => 0,
            'total_cents' => 10000,
            'currency' => 'CNY',
            'metadata' => [],
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);

        $channel = MarketingChannel::create([
            'tenant_id' => $tenant->id,
            'name' => 'Queue Channel',
            'code' => 'queue-channel',
            'commission_rate_basis_points' => 1000,
            'status' => 'active',
        ]);
        $link = PromotionLink::create([
            'marketing_channel_id' => $channel->id,
            'code' => 'QUEUE-LINK',
            'destination_url' => 'https://example.cn/queue',
            'status' => 'active',
        ]);
        $attribution = PromotionAttribution::create([
            'tenant_id' => $tenant->id,
            'marketing_channel_id' => $channel->id,
            'promotion_link_id' => $link->id,
            'status' => 'active',
            'attributed_at' => now(),
        ]);
        $commission = CommissionRecord::create([
            'tenant_id' => $tenant->id,
            'marketing_channel_id' => $channel->id,
            'promotion_attribution_id' => $attribution->id,
            'order_id' => $order->id,
            'base_amount_cents' => 10000,
            'commission_rate_basis_points' => 1000,
            'commission_amount_cents' => 1000,
            'currency' => 'CNY',
            'status' => 'pending',
            'metadata' => [],
            'calculated_at' => now(),
        ]);

        $exitCode = Artisan::call('app:renewal-reminders');
        $output = Artisan::output();
        $this->assertSame(0, $exitCode, $output);
        $this->assertStringContainsString('[OK] renewal reminders processed:', $output);

        $exitCode = Artisan::call('app:orders-expire', ['--minutes' => 30]);
        $this->assertSame(0, $exitCode, Artisan::output());
        $this->assertSame('expired', $order->fresh()->status);
        $this->assertSame('app:orders-expire', $order->fresh()->metadata['expired_by'] ?? null);

        $exitCode = Artisan::call('app:commissions-settle');
        $output = Artisan::output();
        $this->assertSame(0, $exitCode, $output);
        $this->assertStringContainsString('[OK] pending commissions checked:', $output);
        $this->assertSame('pending', $commission->fresh()->status);

        $exitCode = Artisan::call('app:commissions-settle', ['--mark-paid' => true]);
        $this->assertSame(0, $exitCode, Artisan::output());
        $this->assertSame('settled', $commission->fresh()->status);
        $this->assertSame('simulation', $commission->fresh()->metadata['settlement_mode'] ?? null);
    }
}
