<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PluginApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_plugin_package_upload_download_authorization_and_update_check_work(): void
    {
        $tenant = $this->postJson('/api/v1/tenants', [
            'tenant_name' => 'Plugin Tenant',
            'owner_name' => 'Owner',
            'owner_email' => 'plugin-owner@example.com',
            'owner_password' => 'password123',
        ])->assertCreated()->json('data');

        $license = $this->postJson('/api/v1/licenses', [
            'tenant_id' => $tenant['id'],
            'domain' => 'plugin.example.cn',
            'expires_at' => now()->addMonth()->toIso8601String(),
        ])->assertCreated()->json('data');

        $plugin = $this->postJson('/api/v1/plugins', [
            'developer_tenant_id' => $tenant['id'],
            'name' => 'Invoice Exporter',
            'slug' => 'invoice-exporter',
            'version' => '1.0.0',
            'package_path' => 'plugins/invoice-exporter-1.0.0.zip',
            'checksum' => hash('sha256', 'v1'),
            'size_bytes' => 1000,
        ])
            ->assertCreated()
            ->assertJsonPath('data.releases.0.version', '1.0.0')
            ->assertJsonPath('data.releases.0.packages.0.size_bytes', 1000)
            ->json('data');

        $release = $this->postJson('/api/v1/plugins/'.$plugin['id'].'/releases', [
            'version' => '1.1.0',
            'package_path' => 'plugins/invoice-exporter-1.1.0.zip',
            'checksum' => hash('sha256', 'v1.1'),
            'size_bytes' => 1500,
        ])
            ->assertCreated()
            ->assertJsonPath('data.version', '1.1.0')
            ->assertJsonPath('data.packages.0.size_bytes', 1500)
            ->json('data');

        $this->postJson('/api/v1/plugins/install', [
            'tenant_id' => $tenant['id'],
            'plugin_id' => $plugin['id'],
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'installed');

        $download = $this->postJson('/api/v1/plugins/download-tokens', [
            'tenant_id' => $tenant['id'],
            'plugin_release_id' => $release['id'],
            'license_key' => $license['license_key'],
            'domain' => 'plugin.example.cn',
            'fingerprint' => 'plugin-node-1',
        ])
            ->assertCreated()
            ->assertJsonPath('data.release.version', '1.1.0')
            ->json('data');

        $this->postJson('/api/v1/plugins/download-tokens/verify', [
            'download_token' => $download['download_token'],
        ])
            ->assertOk()
            ->assertJsonPath('data.authorized', true)
            ->assertJsonPath('data.package.size_bytes', 1500);

        $this->postJson('/api/v1/plugins/updates/check', [
            'plugin_id' => $plugin['id'],
            'current_version' => '1.0.0',
        ])
            ->assertOk()
            ->assertJsonPath('data.update_available', true)
            ->assertJsonPath('data.latest_release.version', '1.1.0');
    }

    public function test_plugin_download_requires_valid_license(): void
    {
        $tenant = $this->postJson('/api/v1/tenants', [
            'tenant_name' => 'Plugin Guard Tenant',
            'owner_name' => 'Owner',
            'owner_email' => 'plugin-guard-owner@example.com',
            'owner_password' => 'password123',
        ])->assertCreated()->json('data');

        $plugin = $this->postJson('/api/v1/plugins', [
            'developer_tenant_id' => $tenant['id'],
            'name' => 'Guarded Plugin',
            'version' => '1.0.0',
            'package_path' => 'plugins/guarded-1.0.0.zip',
        ])->assertCreated()->json('data');

        $release = $plugin['releases'][0];

        $this->postJson('/api/v1/plugins/install', [
            'tenant_id' => $tenant['id'],
            'plugin_id' => $plugin['id'],
        ])->assertCreated();

        $this->postJson('/api/v1/plugins/download-tokens', [
            'tenant_id' => $tenant['id'],
            'plugin_release_id' => $release['id'],
            'license_key' => 'invalid-license',
            'domain' => 'plugin-guard.example.cn',
            'fingerprint' => 'plugin-node-guard',
        ])->assertUnprocessable();

        $this->assertDatabaseMissing('plugin_download_tokens', [
            'tenant_id' => $tenant['id'],
        ]);
    }
}
