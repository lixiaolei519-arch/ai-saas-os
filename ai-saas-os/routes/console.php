<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

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
