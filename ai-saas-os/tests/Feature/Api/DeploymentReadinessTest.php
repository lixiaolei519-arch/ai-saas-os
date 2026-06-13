<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
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

    public function test_app_production_check_command_runs(): void
    {
        app()->detectEnvironment(fn () => 'production');
        config([
            'app.key' => 'base64:4xgxwIGkfqQ+E0Mkc2U59hmp/TpmZPND2tMVgqktk8s=',
            'cache.default' => 'array',
            'database.default' => 'sqlite',
            'queue.default' => 'sync',
        ]);

        $envFile = storage_path('framework/testing/app-production-check.env');
        File::ensureDirectoryExists(dirname($envFile));
        File::put($envFile, implode(PHP_EOL, [
            'APP_ENV=production',
            'APP_KEY=base64:4xgxwIGkfqQ+E0Mkc2U59hmp/TpmZPND2tMVgqktk8s=',
            'APP_URL=https://example.test',
            'DB_CONNECTION=sqlite',
            'DB_DATABASE=:memory:',
            'DB_USERNAME=null',
            'QUEUE_CONNECTION=sync',
            'CACHE_STORE=array',
        ]));

        $exitCode = Artisan::call('app:production-check', [
            '--env-file' => $envFile,
        ]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('[PASS] APP_ENV is production', $output);
        $this->assertStringContainsString('[PASS] APP_KEY exists', $output);
        $this->assertStringContainsString('[PASS] Database is reachable', $output);
        $this->assertStringContainsString('[PASS] Storage is writable', $output);
        $this->assertStringContainsString('[PASS] Cache is writable', $output);
        $this->assertStringContainsString('[PASS] Queue config exists', $output);
        $this->assertStringContainsString('[PASS] .env required fields exist', $output);
        $this->assertStringContainsString('[PASS] /health is accessible', $output);
    }
}
