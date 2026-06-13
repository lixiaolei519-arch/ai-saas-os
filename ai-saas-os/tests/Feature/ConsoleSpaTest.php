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
}
