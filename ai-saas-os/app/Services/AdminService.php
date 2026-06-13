<?php

namespace App\Services;

use App\Models\CommissionRecord;
use App\Models\License;
use App\Models\MarketingChannel;
use App\Models\Order;
use App\Models\PaymentCallback;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

class AdminService
{
    public function users(int $limit = 50): Collection
    {
        return User::query()
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function tenants(int $limit = 50): Collection
    {
        return Tenant::query()
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function licenses(int $limit = 50): Collection
    {
        return License::query()
            ->with(['tenant', 'productPlan'])
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function orders(int $limit = 50): Collection
    {
        return Order::query()
            ->with(['tenant', 'items', 'payments'])
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function paymentCallbacks(int $limit = 50): Collection
    {
        return PaymentCallback::query()
            ->with('payment')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function channels(int $limit = 50): Collection
    {
        $channels = MarketingChannel::query()
            ->with('promotionLinks')
            ->latest('id')
            ->limit($limit)
            ->get();

        $channels->each(function (MarketingChannel $channel) {
            $channel->setAttribute('orders_count', CommissionRecord::where('marketing_channel_id', $channel->id)->count());
            $channel->setAttribute('commission_amount_cents', CommissionRecord::where('marketing_channel_id', $channel->id)->sum('commission_amount_cents'));
        });

        return $channels;
    }

    public function commissions(int $limit = 50): Collection
    {
        return CommissionRecord::query()
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function stats(): array
    {
        return [
            'users_count' => User::count(),
            'tenants_count' => Tenant::count(),
            'licenses_count' => License::count(),
            'orders_count' => Order::count(),
            'paid_orders_count' => Order::where('status', 'paid')->count(),
            'payment_callbacks_count' => PaymentCallback::count(),
            'marketing_channels_count' => MarketingChannel::count(),
            'commission_records_count' => CommissionRecord::count(),
            'commission_amount_cents' => CommissionRecord::sum('commission_amount_cents'),
            'today_orders_count' => Order::whereDate('created_at', today())->count(),
            'today_users_count' => User::whereDate('created_at', today())->count(),
        ];
    }

    public function dashboard(): array
    {
        $stats = $this->stats();

        return array_merge($stats, [
            'today_revenue_cents' => Order::where('status', 'paid')->whereDate('paid_at', today())->sum('total_cents'),
            'month_revenue_cents' => Order::where('status', 'paid')->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('total_cents'),
            'pending_orders_count' => Order::where('status', 'pending')->count(),
            'order_trend' => $this->dailyTrend('orders_count'),
            'revenue_trend' => $this->dailyTrend('revenue_cents'),
            'license_status_distribution' => $this->statusDistribution(License::class),
            'commission_status_distribution' => $this->statusDistribution(CommissionRecord::class),
            'recent_orders' => Order::query()
                ->with(['tenant', 'items', 'payments'])
                ->latest('id')
                ->limit(8)
                ->get(),
            'recent_payment_callbacks' => PaymentCallback::query()
                ->with('payment')
                ->latest('id')
                ->limit(8)
                ->get(),
            'recent_licenses' => License::query()
                ->with(['tenant', 'productPlan'])
                ->latest('id')
                ->limit(8)
                ->get(),
        ]);
    }

    public function system(): array
    {
        return [
            'app_env' => app()->environment(),
            'app_debug' => config('app.debug'),
            'database_connected' => $this->databaseConnected(),
            'health_ok' => $this->healthOk(),
            'stable_version' => $this->stableVersion(),
            'git_commit' => $this->gitCommit(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
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

    private function dailyTrend(string $metric): array
    {
        return collect(range(6, 0))->map(function (int $daysAgo) use ($metric) {
            $date = today()->subDays($daysAgo);
            $value = $metric === 'orders_count'
                ? Order::whereDate('created_at', $date)->count()
                : Order::where('status', 'paid')->whereDate('paid_at', $date)->sum('total_cents');

            return [
                'date' => $date->toDateString(),
                $metric => $value,
            ];
        })->values()->all();
    }

    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model> $model
     */
    private function statusDistribution(string $model): array
    {
        return $model::query()
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->map(fn ($row) => [
                'status' => $row->status,
                'count' => (int) $row->count,
            ])
            ->values()
            ->all();
    }

    private function healthOk(): bool
    {
        try {
            $route = Route::getRoutes()->match(Request::create('/health', 'GET'));
            $response = $route->run();
            $payload = json_decode((string) $response->getContent(), true);

            return $response->getStatusCode() === 200 && ($payload['status'] ?? null) === 'ok';
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
