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
        return MarketingChannel::query()
            ->latest('id')
            ->limit($limit)
            ->get();
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
        ];
    }
}
