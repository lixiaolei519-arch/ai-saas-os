<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DeploymentReadinessTest extends TestCase
{
    public function test_health_endpoint_reports_ok(): void
    {
        $this->getJson('/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonStructure([
                'status',
                'app',
                'environment',
                'timestamp',
            ]);
    }

    public function test_deployment_documents_exist(): void
    {
        $this->assertFileExists(base_path('docs/deployment/baota-production.md'));
        $this->assertFileExists(base_path('docs/deployment/prelaunch-checklist.md'));
        $this->assertFileExists(base_path('docs/security/prelaunch-security.md'));
        $this->assertFileExists(base_path('docs/api.md'));
    }

    public function test_production_and_security_check_commands_run(): void
    {
        config([
            'app.debug' => false,
            'app.key' => 'base64:4xgxwIGkfqQ+E0Mkc2U59hmp/TpmZPND2tMVgqktk8s=',
            'license.private_key' => 'test-private-key-placeholder',
        ]);

        $exitCode = Artisan::call('production:check');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('[PASS] APP_KEY is configured', Artisan::output());

        $securityExitCode = Artisan::call('security:prelaunch');

        $this->assertSame(0, $securityExitCode);
        $this->assertStringContainsString('[PASS] RELEASE_LOCK.md exists', Artisan::output());
    }
}
