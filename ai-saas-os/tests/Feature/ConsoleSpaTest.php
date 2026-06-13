<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsoleSpaTest extends TestCase
{
    use RefreshDatabase;

    public function test_console_entry_returns_frontend_app(): void
    {
        $this->get('/console')
            ->assertOk();

        $this->assertFileExists(public_path('console/index.html'));
    }

    public function test_console_dashboard_deep_link_returns_frontend_app(): void
    {
        $this->get('/console/dashboard')
            ->assertOk();

        $this->assertStringContainsString(
            '/console/assets/',
            (string) file_get_contents(public_path('console/index.html'))
        );
    }

    public function test_console_portal_deep_links_return_frontend_app(): void
    {
        $this->get('/console/portal/login')
            ->assertOk();

        $this->get('/console/portal/dashboard')
            ->assertOk();

        $this->get('/console/portal/ai-usage')
            ->assertOk();

        $this->get('/console/portal/plugins')
            ->assertOk();

        $this->assertStringContainsString(
            '/console/assets/',
            (string) file_get_contents(public_path('console/index.html'))
        );
    }

    public function test_console_hardening_deep_links_return_frontend_app(): void
    {
        $this->get('/console/403')
            ->assertOk();

        $this->get('/console/ai-usage')
            ->assertOk();

        $this->get('/console/plugins')
            ->assertOk();

        $this->get('/console/plugin-downloads')
            ->assertOk();

        $this->get('/console/workflows')
            ->assertOk();

        $this->get('/console/workflow-runs')
            ->assertOk();

        $this->get('/console/workflow-events')
            ->assertOk();

        $this->get('/console/ai-company/dashboard')
            ->assertOk();

        $this->get('/console/ai-company/tasks')
            ->assertOk();

        $this->get('/console/ai-company/ideas')
            ->assertOk();

        $this->get('/console/ai-company/roadmap')
            ->assertOk();

        $this->get('/console/ai-company/releases')
            ->assertOk();

        $this->get('/console/ai-company/quality')
            ->assertOk();

        $this->get('/console/ai-company/risks')
            ->assertOk();

        $this->get('/console/ai-company/prompts')
            ->assertOk();

        $this->get('/console/ai-company/reports')
            ->assertOk();

        $this->get('/console/self-evolution/dashboard')
            ->assertOk();

        $this->get('/console/self-evolution/score')
            ->assertOk();

        $this->get('/console/self-evolution/plans')
            ->assertOk();

        $this->get('/console/self-evolution/release-review')
            ->assertOk();

        $this->get('/console/self-evolution/suggestions')
            ->assertOk();

        $this->get('/console/missing-page')
            ->assertOk();

        $this->get('/console/portal/missing-page')
            ->assertOk();

        $this->assertStringContainsString(
            '/console/assets/',
            (string) file_get_contents(public_path('console/index.html'))
        );
    }

    public function test_api_v1_routes_are_not_affected_by_console_fallback(): void
    {
        $this->getJson('/api/v1/product-plans')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_admin_system_endpoint_requires_admin_token_and_returns_status(): void
    {
        $admin = User::create([
            'name' => 'Console Admin',
            'email' => 'console-admin@example.com',
            'password' => 'password123',
            'status' => 'active',
            'is_admin' => true,
        ]);

        $this->getJson('/api/v1/admin/system')
            ->assertUnauthorized();

        $this->withToken($admin->createToken('admin', ['admin'])->plainTextToken)
            ->getJson('/api/v1/admin/system')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'app_env',
                    'app_debug',
                    'database_connected',
                    'health_ok',
                    'stable_version',
                    'git_commit',
                    'php_version',
                    'laravel_version',
                ],
            ]);
    }

    public function test_console_api_auth_errors_are_json_and_role_scoped(): void
    {
        $customer = User::create([
            'name' => 'Console Customer',
            'email' => 'console-customer@example.com',
            'password' => 'password123',
            'status' => 'active',
            'is_admin' => false,
        ]);

        $this->withHeader('Authorization', 'Bearer invalid-token')
            ->getJson('/api/v1/admin/stats')
            ->assertUnauthorized();

        $this->withToken($customer->createToken('customer')->plainTextToken)
            ->getJson('/api/v1/admin/stats')
            ->assertForbidden();
    }
}
