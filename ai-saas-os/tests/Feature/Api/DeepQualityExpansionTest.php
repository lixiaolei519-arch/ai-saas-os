<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeepQualityExpansionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_read_quality_version_deployment_and_docs_status(): void
    {
        $admin = User::create([
            'name' => 'Quality Admin',
            'email' => 'quality-admin@example.com',
            'password' => 'password123',
            'status' => 'active',
            'is_admin' => true,
        ]);
        $headers = $this->bearerHeaders($admin->createToken('admin')->plainTextToken);

        $this->getJson('/api/v1/admin/quality/version', $headers)
            ->assertOk()
            ->assertJsonPath('data.api_base', '/api/v1')
            ->assertJsonStructure(['data' => ['stable_version', 'git_commit', 'php_version']]);

        $this->getJson('/api/v1/admin/quality/deployment', $headers)
            ->assertOk()
            ->assertJsonPath('data.database_connected', true)
            ->assertJsonPath('data.console_build_exists', true)
            ->assertJsonStructure(['data' => ['health_ok', 'queue_connection', 'sensitive_paths_blocklist']]);

        $docs = $this->getJson('/api/v1/admin/quality/docs', $headers)
            ->assertOk()
            ->assertJsonStructure(['data' => ['files']])
            ->json('data.files');

        $this->assertContains('docs/openapi-v1.yaml', collect($docs)->pluck('path')->all());
        $this->assertTrue(collect($docs)->firstWhere('path', 'docs/openapi-v1.yaml')['exists']);
    }

    public function test_quality_admin_api_is_role_scoped_and_openapi_exists(): void
    {
        $customer = User::create([
            'name' => 'Quality Customer',
            'email' => 'quality-customer@example.com',
            'password' => 'password123',
            'status' => 'active',
            'is_admin' => false,
        ]);

        $this->getJson('/api/v1/admin/quality/version')
            ->assertUnauthorized();

        $this->getJson(
            '/api/v1/admin/quality/version',
            $this->bearerHeaders($customer->createToken('customer')->plainTextToken)
        )->assertForbidden();

        $this->assertFileExists(base_path('docs/openapi-v1.yaml'));
        $this->assertStringContainsString('/admin/quality/deployment', (string) file_get_contents(base_path('docs/openapi-v1.yaml')));
    }

    private function bearerHeaders(string $token): array
    {
        return [
            'Authorization' => 'Bearer '.$token,
        ];
    }
}
