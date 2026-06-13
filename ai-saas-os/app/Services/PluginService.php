<?php

namespace App\Services;

use App\Models\Plugin;
use App\Models\PluginDownloadRecord;
use App\Models\PluginDownloadToken;
use App\Models\PluginInstallation;
use App\Models\PluginPackage;
use App\Models\PluginRelease;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PluginService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly LicenseService $licenseService,
    ) {
    }

    public function publish(array $data): Plugin
    {
        return DB::transaction(function () use ($data) {
            $plugin = Plugin::create([
                'developer_tenant_id' => $data['developer_tenant_id'] ?? null,
                'name' => $data['name'],
                'slug' => $data['slug'] ?? Str::slug($data['name']),
                'category' => $data['category'] ?? 'general',
                'status' => $data['status'] ?? 'published',
                'price_cents' => $data['price_cents'] ?? 0,
                'currency' => 'CNY',
                'description' => $data['description'] ?? null,
                'manifest' => $data['manifest'] ?? [],
            ]);

            $release = PluginRelease::create([
                'plugin_id' => $plugin->id,
                'version' => $data['version'] ?? '1.0.0',
                'status' => 'published',
                'package_path' => $data['package_path'] ?? 'plugins/'.$plugin->slug.'.zip',
                'checksum' => $data['checksum'] ?? null,
                'published_at' => now(),
            ]);

            PluginPackage::create([
                'plugin_id' => $plugin->id,
                'plugin_release_id' => $release->id,
                'file_name' => basename($release->package_path),
                'storage_path' => $release->package_path,
                'checksum' => $release->checksum ?? hash('sha256', $release->package_path.'|'.$release->version),
                'size_bytes' => $data['size_bytes'] ?? 0,
                'metadata' => $data['package_metadata'] ?? [],
            ]);

            $this->auditService->record('plugin.published', $plugin->developer_tenant_id, null, $plugin);

            return $plugin->load('releases.packages');
        });
    }

    public function uploadReleasePackage(int $pluginId, array $data): PluginRelease
    {
        return DB::transaction(function () use ($pluginId, $data) {
            $plugin = Plugin::findOrFail($pluginId);
            $packagePath = $data['package_path'];
            $checksum = $data['checksum'] ?? hash('sha256', $packagePath.'|'.$data['version']);

            $release = PluginRelease::create([
                'plugin_id' => $plugin->id,
                'version' => $data['version'],
                'status' => $data['status'] ?? 'published',
                'package_path' => $packagePath,
                'checksum' => $checksum,
                'metadata' => $data['metadata'] ?? [],
                'published_at' => now(),
            ]);

            PluginPackage::create([
                'plugin_id' => $plugin->id,
                'plugin_release_id' => $release->id,
                'file_name' => $data['file_name'] ?? basename($packagePath),
                'storage_path' => $packagePath,
                'checksum' => $checksum,
                'size_bytes' => $data['size_bytes'] ?? 0,
                'metadata' => $data['package_metadata'] ?? [],
            ]);

            $this->auditService->record('plugin_release.uploaded', $plugin->developer_tenant_id, null, $release);

            return $release->fresh('packages');
        });
    }

    public function install(array $data): PluginInstallation
    {
        $release = PluginRelease::where('plugin_id', $data['plugin_id'])
            ->where('status', 'published')
            ->latest('id')
            ->firstOrFail();

        $installation = PluginInstallation::updateOrCreate(
            [
                'tenant_id' => $data['tenant_id'],
                'plugin_id' => $data['plugin_id'],
            ],
            [
                'plugin_release_id' => $release->id,
                'status' => 'installed',
                'config' => $data['config'] ?? [],
                'installed_at' => now(),
            ]
        );

        $this->auditService->record('plugin.installed', $installation->tenant_id, null, $installation);

        return $installation->fresh();
    }

    public function issueDownloadToken(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $release = PluginRelease::with('packages')->whereKey($data['plugin_release_id'])->firstOrFail();
            $licenseResult = $this->licenseService->verify([
                'license_key' => $data['license_key'],
                'domain' => $data['domain'] ?? null,
                'fingerprint' => $data['fingerprint'] ?? 'plugin-download',
                'ip_address' => $data['ip_address'] ?? null,
                'user_agent' => $data['user_agent'] ?? null,
            ]);

            if (! ($licenseResult['valid'] ?? false)) {
                throw ValidationException::withMessages([
                    'license_key' => ['License validation failed: '.$licenseResult['reason']],
                ]);
            }

            if ((int) $licenseResult['license']->tenant_id !== (int) $data['tenant_id']) {
                throw ValidationException::withMessages([
                    'license_key' => ['License does not belong to this tenant.'],
                ]);
            }

            $installed = PluginInstallation::where('tenant_id', $data['tenant_id'])
                ->where('plugin_id', $release->plugin_id)
                ->where('status', 'installed')
                ->exists();

            if (! $installed) {
                throw ValidationException::withMessages([
                    'plugin_release_id' => ['Plugin must be installed before download.'],
                ]);
            }

            $plainToken = 'PDL-'.Str::random(48);
            $downloadToken = PluginDownloadToken::create([
                'tenant_id' => $data['tenant_id'],
                'plugin_release_id' => $release->id,
                'license_id' => $licenseResult['license']->id,
                'token_hash' => hash('sha256', $plainToken),
                'status' => 'active',
                'expires_at' => now()->addMinutes((int) ($data['ttl_minutes'] ?? 30)),
                'metadata' => $data['metadata'] ?? [],
            ]);

            $this->auditService->record('plugin_download_token.issued', $data['tenant_id'], null, $downloadToken, [
                'plugin_release_id' => $release->id,
            ]);

            return [
                'download_token' => $plainToken,
                'expires_at' => $downloadToken->expires_at,
                'release' => $release,
            ];
        });
    }

    public function verifyDownloadToken(string $token): array
    {
        $downloadToken = PluginDownloadToken::with('release.packages')
            ->where('token_hash', hash('sha256', $token))
            ->first();

        if (! $downloadToken || $downloadToken->status !== 'active' || $downloadToken->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'download_token' => ['Download token is invalid or expired.'],
            ]);
        }

        $downloadToken->update(['used_at' => now()]);
        $package = $downloadToken->release->packages->first();
        $record = PluginDownloadRecord::create([
            'tenant_id' => $downloadToken->tenant_id,
            'plugin_id' => $downloadToken->release->plugin_id,
            'plugin_release_id' => $downloadToken->plugin_release_id,
            'plugin_package_id' => $package?->id,
            'plugin_download_token_id' => $downloadToken->id,
            'status' => 'authorized',
            'metadata' => [
                'source' => 'download_token_verify',
            ],
            'downloaded_at' => now(),
        ]);

        return [
            'authorized' => true,
            'release' => $downloadToken->release,
            'package' => $package,
            'download_record' => $record,
        ];
    }

    public function availableForTenants(array $tenantIds): \Illuminate\Database\Eloquent\Collection
    {
        return PluginInstallation::query()
            ->whereIn('tenant_id', $tenantIds)
            ->where('status', 'installed')
            ->with(['tenant', 'plugin.releases.packages', 'release.packages'])
            ->latest('id')
            ->get();
    }

    public function downloadRecords(int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return PluginDownloadRecord::query()
            ->with(['tenant', 'plugin', 'release', 'package'])
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function checkUpdate(array $data): array
    {
        $latestRelease = PluginRelease::where('plugin_id', $data['plugin_id'])
            ->where('status', 'published')
            ->orderByDesc('id')
            ->firstOrFail();

        return [
            'update_available' => version_compare($latestRelease->version, $data['current_version'], '>'),
            'latest_release' => $latestRelease,
        ];
    }
}
