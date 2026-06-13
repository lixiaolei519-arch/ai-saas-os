<?php

namespace App\Services;

use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class QualityStatusService
{
    public function version(): array
    {
        return [
            'stable_version' => $this->stableVersion(),
            'git_commit' => $this->gitCommit(),
            'app_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'api_base' => '/api/v1',
            'console_base' => '/console',
            'generated_at' => now()->toIso8601String(),
        ];
    }

    public function deployment(): array
    {
        return [
            'database_connected' => $this->databaseConnected(),
            'health_ok' => $this->healthOk(),
            'console_build_exists' => is_file(public_path('console/index.html')),
            'storage_writable' => is_writable(storage_path()),
            'bootstrap_cache_writable' => is_writable(base_path('bootstrap/cache')),
            'cache_writable' => $this->cacheWritable(),
            'queue_connection' => config('queue.default'),
            'app_env' => app()->environment(),
            'app_debug' => config('app.debug'),
            'sensitive_paths_blocklist' => ['/.env', '/.git/config', '/composer.json'],
            'generated_at' => now()->toIso8601String(),
        ];
    }

    public function docs(): array
    {
        $files = [
            'README.md',
            'CHANGELOG.md',
            'STABLE_TAG.md',
            'BT_PANEL_DEPLOY.md',
            'PRODUCTION_CHECKLIST.md',
            'DEPLOY_AFTER_SLEEP.md',
            'docs/api.md',
            'docs/openapi-v1.yaml',
        ];

        return [
            'files' => collect($files)->map(fn (string $path) => [
                'path' => $path,
                'exists' => is_file(base_path($path)),
            ])->values()->all(),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    private function databaseConnected(): bool
    {
        try {
            DB::select('select 1');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function healthOk(): bool
    {
        try {
            $route = Route::getRoutes()->match(HttpRequest::create('/health', 'GET'));
            $response = $route->run();
            $payload = json_decode((string) $response->getContent(), true);

            return $response->getStatusCode() === 200 && ($payload['status'] ?? null) === 'ok';
        } catch (\Throwable) {
            return false;
        }
    }

    private function cacheWritable(): bool
    {
        try {
            $key = 'quality_status_'.now()->timestamp;
            Cache::put($key, 'ok', 10);
            $passed = Cache::get($key) === 'ok';
            Cache::forget($key);

            return $passed;
        } catch (\Throwable) {
            return false;
        }
    }

    private function stableVersion(): string
    {
        $path = base_path('STABLE_TAG.md');
        if (! is_file($path)) {
            return 'unknown';
        }

        $contents = (string) file_get_contents($path);

        return preg_match('/Current stable version:\s*(.+)/', $contents, $matches)
            ? trim($matches[1])
            : 'unknown';
    }

    private function gitCommit(): string
    {
        $headPath = base_path('.git/HEAD');
        if (! is_file($headPath)) {
            return 'unknown';
        }

        $head = trim((string) file_get_contents($headPath));
        if (str_starts_with($head, 'ref: ')) {
            $refPath = base_path('.git/'.substr($head, 5));

            return is_file($refPath) ? substr(trim((string) file_get_contents($refPath)), 0, 7) : 'unknown';
        }

        return substr($head, 0, 7);
    }
}
