<?php

use App\Models\CommissionRecord;
use App\Models\License;
use App\Models\User;
use App\Services\CatalogService;
use App\Services\CustomerPortalService;
use App\Services\LicenseService;
use App\Services\MarketingService;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\TenantService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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

Artisan::command('app:create-demo-users
    {--admin-email= : Admin account email}
    {--admin-password= : Admin account password}
    {--customer-email= : Customer account email}
    {--customer-password= : Customer account password}', function () {
    $adminEmail = $this->option('admin-email') ?: env('ADMIN_DEMO_EMAIL', 'admin@example.com');
    $customerEmail = $this->option('customer-email') ?: env('CUSTOMER_DEMO_EMAIL', 'customer@example.com');
    $adminPassword = $this->option('admin-password') ?: Str::random(20);
    $customerPassword = $this->option('customer-password') ?: Str::random(20);

    User::updateOrCreate([
        'email' => $adminEmail,
    ], [
        'name' => 'Deployment Admin',
        'password' => Hash::make($adminPassword),
        'status' => 'active',
        'is_admin' => true,
    ]);

    User::updateOrCreate([
        'email' => $customerEmail,
    ], [
        'name' => 'Deployment Customer',
        'password' => Hash::make($customerPassword),
        'status' => 'active',
        'is_admin' => false,
    ]);

    $this->line('admin email: '.$adminEmail);
    $this->line('admin password: '.$adminPassword);
    $this->line('customer email: '.$customerEmail);
    $this->line('customer password: '.$customerPassword);

    return 0;
})->purpose('Create deployment verification admin and customer accounts');

Artisan::command('app:smoke-test', function () {
    $failed = false;
    $context = [];
    $smokeId = now()->format('YmdHis').'-'.Str::lower(Str::random(6));

    $runStep = function (string $label, callable $callback, string $suggestion) use (&$failed) {
        if ($failed) {
            return null;
        }

        try {
            $result = $callback();
            $this->line('[OK] '.$label);

            return $result;
        } catch (Throwable $exception) {
            $failed = true;
            $reason = $exception->getMessage() ?: $exception::class;

            $this->error('[FAIL] '.$label);
            $this->line('Reason: '.$reason);
            $this->line('Suggested fix: '.$suggestion);

            return null;
        }
    };

    $runStep('database connected', function () {
        DB::connection()->getPdo();
        DB::select('select 1');
    }, 'Check DB_* values in .env, confirm MySQL is running, then run php artisan migrate --force.');
    if ($failed) {
        return 1;
    }

    $runStep('key tables exist', function () {
        $requiredTables = [
            'users',
            'personal_access_tokens',
            'tenants',
            'tenant_user',
            'product_plans',
            'orders',
            'order_items',
            'payments',
            'payment_callbacks',
            'licenses',
            'license_activations',
            'marketing_channels',
            'promotion_links',
            'promotion_attributions',
            'commission_records',
        ];
        $missingTables = array_values(array_filter(
            $requiredTables,
            fn (string $table) => ! Schema::hasTable($table)
        ));

        if ($missingTables !== []) {
            throw new RuntimeException('Missing tables: '.implode(', ', $missingTables));
        }
    }, 'Run php artisan migrate --force and verify the production database points to the expected schema.');
    if ($failed) {
        return 1;
    }

    $runStep('/health route exists', function () {
        $request = HttpRequest::create('/health', 'GET');
        $route = Route::getRoutes()->match($request);
        $response = $route->run();
        $payload = json_decode((string) $response->getContent(), true);

        if ($response->getStatusCode() !== 200 || ($payload['status'] ?? null) !== 'ok') {
            throw new RuntimeException('Unexpected /health response status '.$response->getStatusCode());
        }
    }, 'Confirm GET /health is registered, the Nginx site root points to public, and route cache has been rebuilt.');
    if ($failed) {
        return 1;
    }

    $context['customer_password'] = 'SmokeTest-'.Str::random(16).'1!';
    $context['customer'] = $runStep('test customer ready', function () use (&$context) {
        return User::updateOrCreate([
            'email' => env('SMOKE_TEST_CUSTOMER_EMAIL', 'smoke-test@example.invalid'),
        ], [
            'name' => 'Deployment Smoke Customer',
            'password' => Hash::make($context['customer_password']),
            'status' => 'active',
            'is_admin' => false,
        ]);
    }, 'Check the users table migration and make sure the smoke test customer email is not blocked.');
    if ($failed) {
        return 1;
    }

    $context['login'] = $runStep('customer login', function () use (&$context) {
        $request = HttpRequest::create(
            '/api/v1/auth/login',
            'POST',
            [],
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
            ]
        );
        $request->merge([
            'email' => $context['customer']->email,
            'password' => $context['customer_password'],
        ]);
        $response = app(\Illuminate\Contracts\Http\Kernel::class)->handle($request);
        $payload = json_decode((string) $response->getContent(), true);

        if ($response->getStatusCode() !== 200 || empty($payload['data']['token'])) {
            throw new RuntimeException('Login returned HTTP '.$response->getStatusCode().': '.$response->getContent());
        }

        return $payload['data'];
    }, 'Use POST /api/v1/auth/login with Accept: application/json and confirm the user status is active.');
    if ($failed) {
        return 1;
    }

    $context['merchant'] = $runStep('merchant tenant ready', function () use (&$context, $smokeId) {
        return app(TenantService::class)->createTenantWithOwner([
            'tenant_name' => 'Smoke Merchant '.$smokeId,
            'owner_name' => $context['customer']->name,
            'owner_email' => $context['customer']->email,
            'owner_password' => $context['customer_password'],
            'metadata' => [
                'source' => 'deployment_smoke_test',
                'smoke_id' => $smokeId,
            ],
        ]);
    }, 'Check tenant migrations and make sure tenant slug generation is available.');
    if ($failed) {
        return 1;
    }

    $context['partner'] = $runStep('partner tenant ready', function () use (&$context, $smokeId) {
        return app(TenantService::class)->createTenantWithOwner([
            'tenant_name' => 'Smoke Partner '.$smokeId,
            'owner_name' => $context['customer']->name,
            'owner_email' => $context['customer']->email,
            'owner_password' => $context['customer_password'],
            'metadata' => [
                'source' => 'deployment_smoke_test',
                'smoke_id' => $smokeId,
            ],
        ]);
    }, 'Check tenant migrations and confirm the customer can be attached to a tenant.');
    if ($failed) {
        return 1;
    }

    $context['plan'] = $runStep('product plan ready', function () use ($smokeId) {
        return app(CatalogService::class)->createPlan([
            'name' => 'Smoke Monthly '.$smokeId,
            'code' => 'smoke_monthly_'.str_replace('-', '_', $smokeId),
            'billing_cycle' => 'month',
            'price_cents' => 50000,
            'features' => [
                'source' => 'deployment_smoke_test',
            ],
        ]);
    }, 'Check product_plans migration and unique code constraints.');
    if ($failed) {
        return 1;
    }

    $context['promotion_link'] = $runStep('promotion attribution', function () use (&$context, $smokeId) {
        $marketing = app(MarketingService::class);
        $channel = $marketing->createChannel([
            'tenant_id' => $context['partner']->id,
            'name' => 'Smoke Affiliate '.$smokeId,
            'code' => 'smoke-channel-'.$smokeId,
            'commission_rate_basis_points' => 1000,
            'metadata' => [
                'source' => 'deployment_smoke_test',
                'smoke_id' => $smokeId,
            ],
        ]);
        $link = $marketing->createPromotionLink([
            'marketing_channel_id' => $channel->id,
            'code' => 'SMOKE'.Str::upper(Str::random(10)),
            'destination_url' => 'https://example.invalid/smoke-test',
            'metadata' => [
                'source' => 'deployment_smoke_test',
                'smoke_id' => $smokeId,
            ],
        ]);
        $attribution = $marketing->attributePromotion([
            'tenant_id' => $context['merchant']->id,
            'user_id' => $context['customer']->id,
            'promotion_link_code' => $link->code,
            'metadata' => [
                'source' => 'deployment_smoke_test',
                'smoke_id' => $smokeId,
            ],
        ]);

        if (! $attribution->exists) {
            throw new RuntimeException('Promotion attribution was not persisted.');
        }

        return $link;
    }, 'Check marketing channel/link migrations and ensure the promotion link is active.');
    if ($failed) {
        return 1;
    }

    $context['license_domain'] = 'smoke-'.$smokeId.'.example.invalid';
    $context['order'] = $runStep('order created', function () use (&$context) {
        $order = app(OrderService::class)->createOrder([
            'tenant_id' => $context['merchant']->id,
            'user_id' => $context['customer']->id,
            'product_plan_id' => $context['plan']->id,
            'payment_channel' => 'wechat',
            'metadata' => [
                'source' => 'deployment_smoke_test',
                'license_domain' => $context['license_domain'],
            ],
        ]);

        if (! $order->payments->first()) {
            throw new RuntimeException('Order was created without a payment record.');
        }

        return $order;
    }, 'Check orders/payments migrations and verify the wechat payment channel configuration.');
    if ($failed) {
        return 1;
    }

    $context['payment_callback'] = $runStep('mock payment callback', function () use (&$context, $smokeId) {
        $payment = $context['order']->payments->first();
        $signature = hash_hmac(
            'sha256',
            implode('|', [$payment->out_trade_no, (string) $payment->amount_cents, 'SUCCESS']),
            config('payments.channels.wechat.webhook_secret')
        );
        $callback = app(PaymentService::class)->handleCallback('wechat', [
            'out_trade_no' => $payment->out_trade_no,
            'provider_trade_no' => 'smoke-wx-'.$smokeId,
            'trade_status' => 'SUCCESS',
            'amount_cents' => $payment->amount_cents,
            'signature' => $signature,
        ]);

        if ($callback->status !== 'processed') {
            throw new RuntimeException($callback->error_message ?: 'Callback status is '.$callback->status);
        }

        return $callback;
    }, 'Check WECHAT_PAY_WEBHOOK_SECRET and confirm the callback payload is signed with the configured secret.');
    if ($failed) {
        return 1;
    }

    $context['license'] = $runStep('license provisioned', function () use (&$context) {
        $order = $context['order']->fresh();
        $licenseId = $order->metadata['provisioned_license_id'] ?? null;
        $license = $licenseId ? License::find($licenseId) : null;

        if (! $license || $license->status !== 'active') {
            throw new RuntimeException('Paid order did not provision an active License.');
        }

        return $license;
    }, 'Check LicenseService, APP_KEY, licenses table, and payment callback auto-provisioning.');
    if ($failed) {
        return 1;
    }

    $context['license_key'] = $runStep('license key readable', function () use (&$context) {
        $result = app(CustomerPortalService::class)->copyLicenseKey(
            $context['customer']->fresh(),
            $context['license']->id
        );

        if (empty($result['license_key'])) {
            throw new RuntimeException('Customer portal returned an empty LicenseKey.');
        }

        return $result['license_key'];
    }, 'Check customer tenant ownership and encrypted LicenseKey storage.');
    if ($failed) {
        return 1;
    }

    $runStep('license verified', function () use (&$context, $smokeId) {
        $result = app(LicenseService::class)->verify([
            'license_key' => $context['license_key'],
            'domain' => $context['license_domain'],
            'fingerprint' => 'smoke-server-'.$smokeId,
        ]);

        if (($result['valid'] ?? false) !== true) {
            throw new RuntimeException('License verification failed: '.($result['reason'] ?? 'unknown'));
        }
    }, 'Confirm the LicenseKey matches the generated License and the verification domain is unchanged.');
    if ($failed) {
        return 1;
    }

    $runStep('commission generated', function () use (&$context) {
        $commission = CommissionRecord::where('order_id', $context['order']->id)->first();

        if (! $commission || $commission->commission_amount_cents <= 0) {
            throw new RuntimeException('No positive commission record was generated for the paid order.');
        }
    }, 'Check promotion attribution exists before payment, commission rate is greater than zero, and commission_records migration is present.');
    if ($failed) {
        return 1;
    }

    $this->line('[OK] deployment smoke test completed');

    return 0;
})->purpose('Run deployment smoke test for the minimum commercial flow');
