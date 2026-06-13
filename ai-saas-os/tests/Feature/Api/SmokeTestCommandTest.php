<?php

namespace Tests\Feature\Api;

use App\Models\CommissionRecord;
use App\Models\License;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SmokeTestCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_smoke_test_command_verifies_the_minimum_commercial_flow(): void
    {
        $exitCode = Artisan::call('app:smoke-test');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('[OK] database connected', $output);
        $this->assertStringContainsString('[OK] key tables exist', $output);
        $this->assertStringContainsString('[OK] /health route exists', $output);
        $this->assertStringContainsString('[OK] customer login', $output);
        $this->assertStringContainsString('[OK] order created', $output);
        $this->assertStringContainsString('[OK] mock payment callback', $output);
        $this->assertStringContainsString('[OK] license provisioned', $output);
        $this->assertStringContainsString('[OK] license key readable', $output);
        $this->assertStringContainsString('[OK] license verified', $output);
        $this->assertStringContainsString('[OK] promotion attribution', $output);
        $this->assertStringContainsString('[OK] commission generated', $output);

        $order = Order::where('status', 'paid')
            ->where('metadata->source', 'deployment_smoke_test')
            ->latest('id')
            ->firstOrFail();

        $this->assertNotNull($order->metadata['provisioned_license_id'] ?? null);
        $this->assertTrue(License::whereKey($order->metadata['provisioned_license_id'])->exists());
        $this->assertTrue(CommissionRecord::where('order_id', $order->id)->exists());
    }
}
