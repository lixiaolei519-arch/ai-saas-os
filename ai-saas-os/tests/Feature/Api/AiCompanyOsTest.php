<?php

namespace Tests\Feature\Api;

use App\Models\AiCompanyCodexPrompt;
use App\Models\AiCompanyDailyReport;
use App\Models\AiCompanyQualityReport;
use App\Models\AiCompanyRiskReport;
use App\Models\AiCompanyTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AiCompanyOsTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_company_commands_generate_only_simulation_drafts(): void
    {
        $this->artisan('ai-company:scan', ['--stable-version' => 'v1.9.0'])
            ->expectsOutput('[OK] ai company scan completed')
            ->expectsOutput('[OK] simulation mode enabled')
            ->assertExitCode(0);

        $this->artisan('ai-company:plan', ['--target-version' => 'v2.0.0'])
            ->expectsOutput('[OK] ai company plan generated')
            ->expectsOutput('[OK] manual approval required')
            ->assertExitCode(0);

        $this->artisan('ai-company:generate-prompts', ['--target-version' => 'v2.0.0'])
            ->expectsOutput('[OK] prompts require manual approval')
            ->expectsOutput('[OK] no code, deployment, or external action executed')
            ->assertExitCode(0);

        $this->artisan('ai-company:daily-report', ['--date' => '2026-06-14'])
            ->expectsOutput('[OK] daily report generated: 2026-06-14')
            ->expectsOutput('[OK] simulation mode enabled')
            ->assertExitCode(0);

        $this->assertTrue(AiCompanyQualityReport::firstOrFail()->simulation_mode);
        $this->assertTrue(AiCompanyRiskReport::firstOrFail()->simulation_mode);
        $this->assertTrue(AiCompanyTask::firstOrFail()->simulation_mode);
        $this->assertTrue(AiCompanyTask::firstOrFail()->requires_approval);
        $this->assertTrue(AiCompanyCodexPrompt::firstOrFail()->simulation_mode);
        $this->assertTrue(AiCompanyCodexPrompt::firstOrFail()->requires_approval);
        $this->assertTrue(AiCompanyDailyReport::firstOrFail()->simulation_mode);
        $this->assertSame('draft', AiCompanyDailyReport::firstOrFail()->status);
    }

    public function test_admin_can_view_ai_company_os_resources(): void
    {
        $this->artisan('ai-company:scan', ['--stable-version' => 'v1.9.0'])->assertExitCode(0);
        $this->artisan('ai-company:plan', ['--target-version' => 'v2.0.0'])->assertExitCode(0);
        $this->artisan('ai-company:generate-prompts', ['--target-version' => 'v2.0.0'])->assertExitCode(0);
        $this->artisan('ai-company:daily-report', ['--date' => '2026-06-14'])->assertExitCode(0);

        $admin = User::create([
            'name' => 'AI Company Admin',
            'email' => 'ai-company-admin@example.com',
            'password' => 'password123',
            'status' => 'active',
            'is_admin' => true,
        ]);
        $headers = $this->bearerHeaders($admin->createToken('admin')->plainTextToken);

        $this->getJson('/api/v1/admin/ai-company/dashboard', $headers)
            ->assertOk()
            ->assertJsonPath('data.simulation_mode', true)
            ->assertJsonPath('data.tasks_count', 3)
            ->assertJsonPath('data.codex_prompts_count', 3);

        $this->getJson('/api/v1/admin/ai-company/tasks', $headers)
            ->assertOk()
            ->assertJsonFragment(['requires_approval' => true])
            ->assertJsonFragment(['simulation_mode' => true]);

        $this->getJson('/api/v1/admin/ai-company/ideas', $headers)
            ->assertOk()
            ->assertJsonFragment(['simulation_mode' => true]);

        $this->getJson('/api/v1/admin/ai-company/roadmaps', $headers)
            ->assertOk()
            ->assertJsonFragment(['version' => 'v2.0.0']);

        $this->getJson('/api/v1/admin/ai-company/release-plans', $headers)
            ->assertOk()
            ->assertJsonFragment(['requires_approval' => true]);

        $this->getJson('/api/v1/admin/ai-company/quality-reports', $headers)
            ->assertOk()
            ->assertJsonFragment(['version' => 'v1.9.0']);

        $this->getJson('/api/v1/admin/ai-company/risk-reports', $headers)
            ->assertOk()
            ->assertJsonFragment(['status' => 'draft']);

        $this->getJson('/api/v1/admin/ai-company/codex-prompts', $headers)
            ->assertOk()
            ->assertJsonFragment(['target_version' => 'v2.0.0']);

        $this->getJson('/api/v1/admin/ai-company/daily-reports', $headers)
            ->assertOk()
            ->assertJsonFragment(['summary' => 'AI Company OS simulation report generated for internal review. No external actions were executed.']);

        $this->flushHeaders();
        Auth::forgetGuards();
    }

    public function test_customer_cannot_access_ai_company_os_admin_api(): void
    {
        $customer = User::create([
            'name' => 'AI Company Customer',
            'email' => 'ai-company-customer@example.com',
            'password' => 'password123',
            'status' => 'active',
            'is_admin' => false,
        ]);

        $this->getJson('/api/v1/admin/ai-company/dashboard')
            ->assertUnauthorized();

        $this->getJson(
            '/api/v1/admin/ai-company/dashboard',
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
