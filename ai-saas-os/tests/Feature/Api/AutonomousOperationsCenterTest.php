<?php

namespace Tests\Feature\Api;

use App\Models\AutonomousOperationDraft;
use App\Models\AutonomousOperationTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AutonomousOperationsCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_operations_command_generates_draft_only_content(): void
    {
        $this->artisan('operations:generate-drafts')
            ->expectsOutput('[OK] autonomous operations drafts generated: 9')
            ->expectsOutput('[OK] autonomous operations tasks generated: 3')
            ->expectsOutput('[OK] manual approval required')
            ->expectsOutput('[OK] no email, page publish, ad spend, or customer contact executed')
            ->assertExitCode(0);

        $this->assertSame(9, AutonomousOperationDraft::count());
        $this->assertSame(3, AutonomousOperationTask::count());
        $this->assertTrue(AutonomousOperationDraft::firstOrFail()->simulation_mode);
        $this->assertTrue(AutonomousOperationDraft::firstOrFail()->requires_approval);
        $this->assertTrue(AutonomousOperationTask::firstOrFail()->simulation_mode);
        $this->assertTrue(AutonomousOperationTask::firstOrFail()->requires_approval);
        $this->assertSame('draft', AutonomousOperationDraft::firstOrFail()->status);
        $this->assertSame('draft', AutonomousOperationTask::firstOrFail()->status);
    }

    public function test_admin_can_view_operations_resources(): void
    {
        $this->artisan('operations:generate-drafts')->assertExitCode(0);

        $admin = User::create([
            'name' => 'Operations Admin',
            'email' => 'operations-admin@example.com',
            'password' => 'password123',
            'status' => 'active',
            'is_admin' => true,
        ]);
        $headers = $this->bearerHeaders($admin->createToken('admin')->plainTextToken);

        $this->getJson('/api/v1/admin/operations/dashboard', $headers)
            ->assertOk()
            ->assertJsonPath('data.simulation_mode', true)
            ->assertJsonPath('data.drafts_count', 9)
            ->assertJsonPath('data.tasks_count', 3);

        $this->getJson('/api/v1/admin/operations/reports', $headers)
            ->assertOk()
            ->assertJsonFragment(['type' => 'product_daily_report'])
            ->assertJsonFragment(['type' => 'operations_weekly_report']);

        $this->getJson('/api/v1/admin/operations/seo-plans', $headers)
            ->assertOk()
            ->assertJsonFragment(['type' => 'seo_content_plan']);

        $this->getJson('/api/v1/admin/operations/landing-pages', $headers)
            ->assertOk()
            ->assertJsonFragment(['type' => 'landing_page_copy']);

        $this->getJson('/api/v1/admin/operations/pricing', $headers)
            ->assertOk()
            ->assertJsonFragment(['type' => 'pricing_strategy']);

        $this->getJson('/api/v1/admin/operations/release-announcements', $headers)
            ->assertOk()
            ->assertJsonFragment(['type' => 'release_announcement']);

        $this->getJson('/api/v1/admin/operations/customer-emails', $headers)
            ->assertOk()
            ->assertJsonFragment(['type' => 'customer_email']);

        $this->getJson('/api/v1/admin/operations/faq', $headers)
            ->assertOk()
            ->assertJsonFragment(['type' => 'support_faq']);

        $this->getJson('/api/v1/admin/operations/partner-recruiting', $headers)
            ->assertOk()
            ->assertJsonFragment(['type' => 'partner_recruiting_copy'])
            ->assertJsonFragment(['requires_approval' => true]);

        $this->flushHeaders();
        Auth::forgetGuards();
    }

    public function test_customer_cannot_access_operations_admin_api(): void
    {
        $customer = User::create([
            'name' => 'Operations Customer',
            'email' => 'operations-customer@example.com',
            'password' => 'password123',
            'status' => 'active',
            'is_admin' => false,
        ]);

        $this->getJson('/api/v1/admin/operations/dashboard')
            ->assertUnauthorized();

        $this->getJson(
            '/api/v1/admin/operations/dashboard',
            $this->bearerHeaders($customer->createToken('customer')->plainTextToken)
        )->assertForbidden();
    }

    private function bearerHeaders(string $token): array
    {
        return [
            'Authorization' => 'Bearer '.$token,
        ];
    }
}
