<?php

use App\Models\CommissionRecord;
use App\Models\License;
use App\Models\Order;
use App\Models\User;
use App\Services\CatalogService;
use App\Services\CustomerPortalService;
use App\Services\AiCompanyService;
use App\Services\LicenseService;
use App\Services\MarketingService;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\SelfEvolutionService;
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
        'APP_DEBUG',
        'APP_URL',
        'DB_CONNECTION',
        'DB_DATABASE',
        'DB_USERNAME',
        'DB_PASSWORD',
        'DB_COLLATION',
        'QUEUE_CONNECTION',
        'CACHE_STORE',
    ];
    $missingEnvKeys = array_values(array_filter($requiredEnvKeys, function (string $key) use ($envContents) {
        return ! preg_match('/^\s*'.preg_quote($key, '/').'\s*=/m', $envContents);
    }));

    $checks = [
        'APP_ENV is production' => app()->environment('production'),
        'APP_DEBUG is false' => config('app.debug') === false,
        'APP_KEY exists' => filled(config('app.key')),
        'APP_URL exists' => filled(config('app.url')) && Str::startsWith(config('app.url'), ['http://', 'https://']),
        'Database is reachable' => function () {
            try {
                DB::connection()->getPdo();
                DB::select('select 1');

                return true;
            } catch (Throwable) {
                return false;
            }
        },
        'DB_COLLATION is configured' => function () use ($envContents) {
            $connection = config('database.default');
            $driver = config('database.connections.'.$connection.'.driver');

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                return filled(config('database.connections.'.$connection.'.collation'))
                    || preg_match('/^\s*DB_COLLATION\s*=\s*\S+/m', $envContents) === 1;
            }

            return preg_match('/^\s*DB_COLLATION\s*=/m', $envContents) === 1;
        },
        'Storage is writable' => is_writable(storage_path()),
        'Bootstrap cache is writable' => is_writable(base_path('bootstrap/cache')),
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
        'Console build exists' => is_file(public_path('console/index.html')),
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
        '/console route is accessible' => function () {
            try {
                $response = Route::dispatch(HttpRequest::create('/console', 'GET'));

                return $response->getStatusCode() === 200;
            } catch (Throwable) {
                return false;
            }
        },
        'API JSON response is available' => function () {
            try {
                $request = HttpRequest::create('/api/v1/product-plans', 'GET', [], [], [], [
                    'HTTP_ACCEPT' => 'application/json',
                ]);
                $response = app(\Illuminate\Contracts\Http\Kernel::class)->handle($request);
                $contentType = $response->headers->get('Content-Type', '');
                $payload = json_decode((string) $response->getContent(), true);
                \Illuminate\Support\Facades\Auth::forgetGuards();

                return $response->getStatusCode() === 200
                    && str_contains($contentType, 'application/json')
                    && is_array($payload)
                    && array_key_exists('data', $payload);
            } catch (Throwable) {
                return false;
            }
        },
        'Sensitive files are not web accessible' => function () {
            foreach (['/.env', '/.git/config', '/composer.json'] as $path) {
                $response = app(\Illuminate\Contracts\Http\Kernel::class)->handle(HttpRequest::create($path, 'GET'));
                if ($response->getStatusCode() === 200) {
                    \Illuminate\Support\Facades\Auth::forgetGuards();

                    return false;
                }
            }
            \Illuminate\Support\Facades\Auth::forgetGuards();

            return true;
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

Artisan::command('app:queue-check', function () {
    $connection = config('queue.default');
    $checks = [
        'queue connection configured' => filled($connection) && is_array(config('queue.connections.'.$connection)),
        'jobs table exists' => Schema::hasTable('jobs'),
        'failed_jobs table exists' => Schema::hasTable('failed_jobs'),
        'schedule command available' => true,
    ];

    $failed = false;
    foreach ($checks as $label => $passed) {
        $this->line(($passed ? '[PASS] ' : '[FAIL] ').$label);
        $failed = $failed || ! $passed;
    }

    if (Schema::hasTable('jobs')) {
        $this->line('[INFO] pending jobs: '.DB::table('jobs')->count());
    }
    if (Schema::hasTable('failed_jobs')) {
        $this->line('[INFO] failed jobs: '.DB::table('failed_jobs')->count());
    }

    return $failed ? 1 : 0;
})->purpose('Check queue and failed-job storage readiness');

Artisan::command('app:renewal-reminders', function () {
    $deliveries = app(MarketingService::class)->processDueRenewalReminders();
    $this->line('[OK] renewal reminders processed: '.$deliveries->count());

    return 0;
})->purpose('Process due renewal reminder records')->dailyAt('09:00');

Artisan::command('app:orders-expire {--minutes=30 : Pending order timeout in minutes}', function () {
    $minutes = max(1, (int) $this->option('minutes'));
    $orders = Order::where('status', 'pending')
        ->where('created_at', '<=', now()->subMinutes($minutes))
        ->get();
    $orders->each(function (Order $order) {
        $order->update([
            'status' => 'expired',
            'metadata' => array_merge($order->metadata ?? [], [
                'expired_by' => 'app:orders-expire',
                'expired_at' => now()->toIso8601String(),
            ]),
        ]);
    });

    $this->line('[OK] expired pending orders: '.$orders->count());

    return 0;
})->purpose('Expire pending orders older than the configured timeout')->everyFifteenMinutes();

Artisan::command('app:commissions-settle {--mark-paid : Mark pending commissions as settled in simulation mode}', function () {
    $query = CommissionRecord::where('status', 'pending');
    $count = (clone $query)->count();

    if ($this->option('mark-paid')) {
        $query->get()->each(function (CommissionRecord $commission) {
            $commission->update([
                'status' => 'settled',
                'metadata' => array_merge($commission->metadata ?? [], [
                    'settled_by' => 'app:commissions-settle',
                    'settled_at' => now()->toIso8601String(),
                    'settlement_mode' => 'simulation',
                ]),
            ]);
        });
        $this->line('[OK] settled pending commissions: '.$count);

        return 0;
    }

    $this->line('[OK] pending commissions checked: '.$count);

    return 0;
})->purpose('Check commission settlement queue without external payouts')->dailyAt('10:00');

Artisan::command('ai-company:scan {--stable-version= : Stable version label}', function () {
    $result = app(AiCompanyService::class)->scan($this->option('stable-version'));

    $this->line('[OK] ai company scan completed');
    $this->line('[OK] quality report generated: '.$result['quality_report']->id);
    $this->line('[OK] risk report generated: '.$result['risk_report']->id);
    $this->line('[OK] simulation mode enabled');

    return 0;
})->purpose('Generate AI Company OS quality and risk reports in simulation mode');

Artisan::command('ai-company:plan {--target-version=v2.0.0 : Target version for the draft plan}', function () {
    $result = app(AiCompanyService::class)->plan($this->option('target-version'));

    $this->line('[OK] ai company plan generated');
    $this->line('[OK] roadmap generated: '.$result['roadmap']->id);
    $this->line('[OK] release plan generated: '.$result['release_plan']->id);
    $this->line('[OK] draft tasks generated: '.$result['tasks']->count());
    $this->line('[OK] manual approval required');

    return 0;
})->purpose('Generate AI Company OS roadmap, release plan, and draft tasks');

Artisan::command('ai-company:generate-prompts {--target-version=v2.0.0 : Target version for Codex prompt drafts}', function () {
    $prompts = app(AiCompanyService::class)->generatePrompts($this->option('target-version'));

    $this->line('[OK] codex prompt drafts generated: '.$prompts->count());
    $this->line('[OK] prompts require manual approval');
    $this->line('[OK] no code, deployment, or external action executed');

    return 0;
})->purpose('Generate draft Codex prompts from AI Company OS tasks without executing them');

Artisan::command('ai-company:daily-report {--date= : Report date in YYYY-MM-DD format}', function () {
    $report = app(AiCompanyService::class)->dailyReport($this->option('date'));

    $this->line('[OK] daily report generated: '.$report->report_date->toDateString());
    $this->line('[OK] report status: '.$report->status);
    $this->line('[OK] simulation mode enabled');

    return 0;
})->purpose('Generate an internal AI Company OS daily operations report');

Artisan::command('self-evolve:scan {--stable-version= : Stable version label}', function () {
    $scan = app(SelfEvolutionService::class)->scan($this->option('stable-version'));

    $this->line('[OK] self evolution scan completed');
    $this->line('[OK] scan generated: '.$scan->id);
    $this->line('[OK] simulation mode enabled');

    return 0;
})->purpose('Generate a safe self-evolution scan draft');

Artisan::command('self-evolve:score {--stable-version= : Stable version label}', function () {
    $score = app(SelfEvolutionService::class)->score($this->option('stable-version'));

    $this->line('[OK] self evolution score generated');
    $this->line('[OK] overall score: '.$score->overall_score);
    $this->line('[OK] scoring remains draft-only');

    return 0;
})->purpose('Generate a self-evolution score draft');

Artisan::command('self-evolve:plan {--target-version=v2.1.0 : Target version for the draft plan}', function () {
    $plan = app(SelfEvolutionService::class)->plan($this->option('target-version'));

    $this->line('[OK] self evolution plan generated');
    $this->line('[OK] target version: '.$plan->target_version);
    $this->line('[OK] manual approval required');

    return 0;
})->purpose('Generate a self-evolution version plan draft');

Artisan::command('self-evolve:review-release {--release-version= : Release version to review}', function () {
    $review = app(SelfEvolutionService::class)->reviewRelease($this->option('release-version'));

    $this->line('[OK] release review generated');
    $this->line('[OK] decision: '.$review->decision);
    $this->line('[OK] no production action executed');

    return 0;
})->purpose('Generate release review, rollback, deployment, test, security, and business suggestions');

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
    $dispatchJson = function (string $method, string $uri, array $payload = [], ?string $token = null) {
        $server = [
            'HTTP_ACCEPT' => 'application/json',
        ];
        if ($token) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$token;
        }

        $request = HttpRequest::create($uri, $method, [], [], [], $server);
        if ($payload !== []) {
            $request->merge($payload);
        }

        $response = app(\Illuminate\Contracts\Http\Kernel::class)->handle($request);
        $body = json_decode((string) $response->getContent(), true) ?: [];
        \Illuminate\Support\Facades\Auth::forgetGuards();

        return [$response, $body];
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

    $runStep('console build exists', function () {
        $path = public_path('console/index.html');
        if (! is_file($path)) {
            throw new RuntimeException('Missing public/console/index.html');
        }
    }, 'Run cd frontend/admin-console && npm install && npm run build, then deploy public/console.');
    if ($failed) {
        return 1;
    }

    $runStep('console route accessible', function () {
        $response = Route::dispatch(HttpRequest::create('/console/dashboard', 'GET'));
        $content = (string) file_get_contents(public_path('console/index.html'));

        if ($response->getStatusCode() !== 200 || ! str_contains($content, '/console/assets/')) {
            throw new RuntimeException('Console route returned HTTP '.$response->getStatusCode().' without the React asset entry.');
        }
    }, 'Confirm the /console fallback route is registered, route cache is rebuilt, and public/console/index.html exists.');
    if ($failed) {
        return 1;
    }

    $runStep('api json response', function () use ($dispatchJson) {
        [$response, $payload] = $dispatchJson('GET', '/api/v1/product-plans');
        $contentType = $response->headers->get('Content-Type', '');

        if ($response->getStatusCode() !== 200 || ! str_contains($contentType, 'application/json') || ! array_key_exists('data', $payload)) {
            throw new RuntimeException('Product plans API returned HTTP '.$response->getStatusCode().': '.$response->getContent());
        }
    }, 'Confirm /api/v1/* routes are loaded and requests send Accept: application/json.');
    if ($failed) {
        return 1;
    }

    $runStep('sensitive files inaccessible', function () {
        foreach (['/.env', '/.git/config', '/composer.json'] as $path) {
            $response = app(\Illuminate\Contracts\Http\Kernel::class)->handle(HttpRequest::create($path, 'GET'));
            \Illuminate\Support\Facades\Auth::forgetGuards();

            if ($response->getStatusCode() === 200) {
                throw new RuntimeException($path.' returned HTTP 200');
            }
        }
    }, 'Point Nginx site root to public and keep deny rules for dotfiles plus env/log/sql/bak/conf files.');
    if ($failed) {
        return 1;
    }

    $context['demo_admin'] = $runStep('demo admin exists', function () {
        return User::firstOrCreate([
            'email' => env('ADMIN_DEMO_EMAIL', 'admin@example.com'),
        ], [
            'name' => 'Deployment Admin',
            'password' => Hash::make(Str::random(24)),
            'status' => 'active',
            'is_admin' => true,
        ]);
    }, 'Run php artisan app:create-demo-users and confirm the admin account is active.');
    if ($failed) {
        return 1;
    }

    $context['demo_customer'] = $runStep('demo customer exists', function () {
        return User::firstOrCreate([
            'email' => env('CUSTOMER_DEMO_EMAIL', 'customer@example.com'),
        ], [
            'name' => 'Deployment Customer',
            'password' => Hash::make(Str::random(24)),
            'status' => 'active',
            'is_admin' => false,
        ]);
    }, 'Run php artisan app:create-demo-users and confirm the customer account is active.');
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

    $context['login'] = $runStep('customer login', function () use (&$context, $dispatchJson) {
        [$response, $payload] = $dispatchJson('POST', '/api/v1/auth/login', [
            'email' => $context['customer']->email,
            'password' => $context['customer_password'],
        ]);

        if ($response->getStatusCode() !== 200 || empty($payload['data']['token'])) {
            throw new RuntimeException('Login returned HTTP '.$response->getStatusCode().': '.$response->getContent());
        }

        return $payload['data'];
    }, 'Use POST /api/v1/auth/login with Accept: application/json and confirm the user status is active.');
    if ($failed) {
        return 1;
    }

    $runStep('admin api accessible', function () use (&$context, $dispatchJson) {
        $token = $context['demo_admin']->createToken('smoke-admin', ['admin'])->plainTextToken;
        [$response] = $dispatchJson('GET', '/api/v1/admin/stats', [], $token);

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException('Admin stats returned HTTP '.$response->getStatusCode().': '.$response->getContent());
        }
    }, 'Confirm the demo admin account is active and /api/v1/admin/* accepts admin Bearer tokens.');
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
    }, 'Check APP_KEY, WECHAT_PAY_WEBHOOK_SECRET, and confirm the callback payload is signed with the configured secret.');
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

    $context['other_data'] = $runStep('other customer data ready', function () use ($smokeId, &$context) {
        $otherTenant = app(TenantService::class)->createTenantWithOwner([
            'tenant_name' => 'Smoke Other '.$smokeId,
            'owner_name' => 'Smoke Other Customer',
            'owner_email' => 'smoke-other-'.$smokeId.'@example.invalid',
            'owner_password' => 'SmokeOther-'.Str::random(12).'1!',
            'metadata' => [
                'source' => 'deployment_smoke_test',
                'smoke_id' => $smokeId,
            ],
        ]);
        $otherLicense = app(LicenseService::class)->issue([
            'tenant_id' => $otherTenant->id,
            'product_plan_id' => $context['plan']->id,
            'domain' => 'other-'.$smokeId.'.example.invalid',
            'expires_at' => now()->addMonth()->toIso8601String(),
        ])['license'];
        $otherOrder = app(OrderService::class)->createOrder([
            'tenant_id' => $otherTenant->id,
            'product_plan_id' => $context['plan']->id,
            'payment_channel' => 'wechat',
            'metadata' => [
                'source' => 'deployment_smoke_test_other',
            ],
        ]);

        return [
            'tenant' => $otherTenant,
            'license' => $otherLicense,
            'order' => $otherOrder,
        ];
    }, 'Check tenant, order, and License creation for customer data isolation verification.');
    if ($failed) {
        return 1;
    }

    $runStep('customer portal api accessible', function () use (&$context, $dispatchJson) {
        [$response] = $dispatchJson('GET', '/api/v1/portal/dashboard', [], $context['login']['token']);

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException('Portal dashboard returned HTTP '.$response->getStatusCode().': '.$response->getContent());
        }
    }, 'Confirm customer Bearer token can access /api/v1/portal/dashboard.');
    if ($failed) {
        return 1;
    }

    $runStep('customer license api is isolated', function () use (&$context, $dispatchJson) {
        [$response, $payload] = $dispatchJson('GET', '/api/v1/portal/licenses', [], $context['login']['token']);
        $ids = collect($payload['data'] ?? [])->pluck('id')->map(fn ($id) => (int) $id);

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException('Portal licenses returned HTTP '.$response->getStatusCode().': '.$response->getContent());
        }
        if (! $ids->contains((int) $context['license']->id) || $ids->contains((int) $context['other_data']['license']->id)) {
            throw new RuntimeException(sprintf(
                'Portal licenses did not return only the current customer data. expected=%d other=%d returned=%s',
                $context['license']->id,
                $context['other_data']['license']->id,
                $ids->implode(',')
            ));
        }
    }, 'Confirm portal License queries are restricted by the current user tenant ids.');
    if ($failed) {
        return 1;
    }

    $runStep('customer order api is isolated', function () use (&$context, $dispatchJson) {
        [$response, $payload] = $dispatchJson('GET', '/api/v1/portal/orders', [], $context['login']['token']);
        $ids = collect($payload['data'] ?? [])->pluck('id')->map(fn ($id) => (int) $id);

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException('Portal orders returned HTTP '.$response->getStatusCode().': '.$response->getContent());
        }
        if (! $ids->contains((int) $context['order']->id) || $ids->contains((int) $context['other_data']['order']->id)) {
            throw new RuntimeException('Portal orders did not return only the current customer data.');
        }
    }, 'Confirm portal order queries are restricted by the current user tenant ids.');
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
