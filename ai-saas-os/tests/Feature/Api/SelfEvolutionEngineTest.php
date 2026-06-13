<?php

namespace Tests\Feature\Api;

use App\Models\SelfEvolutionPlan;
use App\Models\SelfEvolutionReleaseReview;
use App\Models\SelfEvolutionScan;
use App\Models\SelfEvolutionScore;
use App\Models\SelfEvolutionSuggestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class SelfEvolutionEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_self_evolution_commands_generate_safe_drafts(): void
    {
        $this->artisan('self-evolve:scan', ['--stable-version' => 'v2.0.0'])
            ->expectsOutput('[OK] self evolution scan completed')
            ->expectsOutput('[OK] simulation mode enabled')
            ->assertExitCode(0);

        $this->artisan('self-evolve:score', ['--stable-version' => 'v2.0.0'])
            ->expectsOutput('[OK] self evolution score generated')
            ->expectsOutput('[OK] scoring remains draft-only')
            ->assertExitCode(0);

        $this->artisan('self-evolve:plan', ['--target-version' => 'v2.1.0'])
            ->expectsOutput('[OK] self evolution plan generated')
            ->expectsOutput('[OK] manual approval required')
            ->assertExitCode(0);

        $this->artisan('self-evolve:review-release', ['--release-version' => 'v2.0.0'])
            ->expectsOutput('[OK] release review generated')
            ->expectsOutput('[OK] no production action executed')
            ->assertExitCode(0);

        $this->assertTrue(SelfEvolutionScan::firstOrFail()->simulation_mode);
        $this->assertTrue(SelfEvolutionScore::firstOrFail()->simulation_mode);
        $this->assertTrue(SelfEvolutionPlan::firstOrFail()->simulation_mode);
        $this->assertTrue(SelfEvolutionPlan::firstOrFail()->requires_approval);
        $this->assertTrue(SelfEvolutionReleaseReview::firstOrFail()->simulation_mode);
        $this->assertTrue(SelfEvolutionReleaseReview::firstOrFail()->requires_approval);
        $this->assertTrue(SelfEvolutionSuggestion::firstOrFail()->simulation_mode);
        $this->assertTrue(SelfEvolutionSuggestion::firstOrFail()->requires_approval);
        $this->assertSame('draft', SelfEvolutionSuggestion::firstOrFail()->status);
    }

    public function test_admin_can_view_self_evolution_resources(): void
    {
        $this->artisan('self-evolve:scan', ['--stable-version' => 'v2.0.0'])->assertExitCode(0);
        $this->artisan('self-evolve:score', ['--stable-version' => 'v2.0.0'])->assertExitCode(0);
        $this->artisan('self-evolve:plan', ['--target-version' => 'v2.1.0'])->assertExitCode(0);
        $this->artisan('self-evolve:review-release', ['--release-version' => 'v2.0.0'])->assertExitCode(0);

        $admin = User::create([
            'name' => 'Self Evolution Admin',
            'email' => 'self-evolution-admin@example.com',
            'password' => 'password123',
            'status' => 'active',
            'is_admin' => true,
        ]);
        $headers = $this->bearerHeaders($admin->createToken('admin')->plainTextToken);

        $this->getJson('/api/v1/admin/self-evolution/dashboard', $headers)
            ->assertOk()
            ->assertJsonPath('data.simulation_mode', true)
            ->assertJsonPath('data.scans_count', 1)
            ->assertJsonPath('data.suggestions_count', 5);

        $this->getJson('/api/v1/admin/self-evolution/scans', $headers)
            ->assertOk()
            ->assertJsonFragment(['version' => 'v2.0.0'])
            ->assertJsonFragment(['simulation_mode' => true]);

        $this->getJson('/api/v1/admin/self-evolution/scores', $headers)
            ->assertOk()
            ->assertJsonFragment(['version' => 'v2.0.0']);

        $this->getJson('/api/v1/admin/self-evolution/plans', $headers)
            ->assertOk()
            ->assertJsonFragment(['target_version' => 'v2.1.0'])
            ->assertJsonFragment(['requires_approval' => true]);

        $this->getJson('/api/v1/admin/self-evolution/release-reviews', $headers)
            ->assertOk()
            ->assertJsonFragment(['version' => 'v2.0.0'])
            ->assertJsonFragment(['decision' => 'ready_for_manual_review']);

        $this->getJson('/api/v1/admin/self-evolution/suggestions', $headers)
            ->assertOk()
            ->assertJsonFragment(['category' => 'security'])
            ->assertJsonFragment(['status' => 'draft']);

        $this->flushHeaders();
        Auth::forgetGuards();
    }

    public function test_customer_cannot_access_self_evolution_admin_api(): void
    {
        $customer = User::create([
            'name' => 'Self Evolution Customer',
            'email' => 'self-evolution-customer@example.com',
            'password' => 'password123',
            'status' => 'active',
            'is_admin' => false,
        ]);

        $this->getJson('/api/v1/admin/self-evolution/dashboard')
            ->assertUnauthorized();

        $this->getJson(
            '/api/v1/admin/self-evolution/dashboard',
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
