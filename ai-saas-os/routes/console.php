<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('production:check', function () {
    $checks = [
        'APP_KEY is configured' => filled(config('app.key')),
        'APP_DEBUG is disabled outside local' => app()->environment('local') || config('app.debug') === false,
        'Database connection is configured' => filled(config('database.default')),
        'Queue connection is configured' => filled(config('queue.default')),
        'License signing material is configured' => filled(config('license.private_key')) || filled(config('app.key')),
        'WeChat Pay webhook secret is configured' => filled(config('payments.channels.wechat.webhook_secret')),
        'Alipay webhook secret is configured' => filled(config('payments.channels.alipay.webhook_secret')),
        'Storage directory is writable' => is_writable(storage_path()),
    ];

    $failed = false;

    foreach ($checks as $label => $passed) {
        $this->line(($passed ? '[PASS] ' : '[FAIL] ').$label);
        $failed = $failed || ! $passed;
    }

    return $failed ? 1 : 0;
})->purpose('Run production readiness checks before deployment');

Artisan::command('security:prelaunch', function () {
    $checks = [
        'RELEASE_LOCK.md exists' => file_exists(base_path('RELEASE_LOCK.md')),
        'APP_KEY is configured' => filled(config('app.key')),
        'APP_DEBUG is disabled outside local' => app()->environment('local') || config('app.debug') === false,
        'WeChat Pay webhook secret is configured' => filled(config('payments.channels.wechat.webhook_secret')),
        'Alipay webhook secret is configured' => filled(config('payments.channels.alipay.webhook_secret')),
        'Default admin password is not used in production' => ! app()->environment('production') || env('ADMIN_DEMO_PASSWORD') !== 'password123',
        'Default customer password is not used in production' => ! app()->environment('production') || env('CUSTOMER_DEMO_PASSWORD') !== 'password123',
    ];

    $failed = false;

    foreach ($checks as $label => $passed) {
        $this->line(($passed ? '[PASS] ' : '[FAIL] ').$label);
        $failed = $failed || ! $passed;
    }

    return $failed ? 1 : 0;
})->purpose('Run prelaunch security checks');

Artisan::command('app:production-check {--env-file= : Optional env file path for validation}', function () {
    $envFile = $this->option('env-file') ?: base_path('.env');
    $envContents = is_file($envFile) ? (string) file_get_contents($envFile) : '';
    $requiredEnvKeys = [
        'APP_ENV',
        'APP_KEY',
        'APP_URL',
        'DB_CONNECTION',
        'DB_DATABASE',
        'DB_USERNAME',
        'QUEUE_CONNECTION',
        'CACHE_STORE',
    ];
    $missingEnvKeys = array_values(array_filter($requiredEnvKeys, function (string $key) use ($envContents) {
        return ! preg_match('/^\s*'.preg_quote($key, '/').'\s*=/m', $envContents);
    }));

    $checks = [
        'APP_ENV is production' => app()->environment('production'),
        'APP_KEY exists' => filled(config('app.key')),
        'Database is reachable' => function () {
            try {
                DB::connection()->getPdo();
                DB::select('select 1');

                return true;
            } catch (Throwable) {
                return false;
            }
        },
        'Storage is writable' => is_writable(storage_path()),
        'Cache is writable' => function () {
            try {
                $key = 'app_production_check_'.now()->timestamp;
                Cache::put($key, 'ok', 10);
                $passed = Cache::get($key) === 'ok';
                Cache::forget($key);

                return $passed;
            } catch (Throwable) {
                return false;
            }
        },
        'Queue config exists' => function () {
            $connection = config('queue.default');

            return filled($connection) && is_array(config('queue.connections.'.$connection));
        },
        '.env exists' => is_file($envFile),
        '.env required fields exist' => empty($missingEnvKeys),
        '/health is accessible' => function () {
            try {
                $response = Route::dispatch(HttpRequest::create('/health', 'GET'));

                return $response->getStatusCode() === 200;
            } catch (Throwable) {
                return false;
            }
        },
    ];

    $failed = false;

    foreach ($checks as $label => $check) {
        $passed = is_callable($check) ? (bool) $check() : (bool) $check;
        $this->line(($passed ? '[PASS] ' : '[FAIL] ').$label);
        $failed = $failed || ! $passed;
    }

    if ($missingEnvKeys !== []) {
        $this->line('[INFO] Missing .env keys: '.implode(', ', $missingEnvKeys));
    }

    return $failed ? 1 : 0;
})->purpose('Run application production environment self-checks');
