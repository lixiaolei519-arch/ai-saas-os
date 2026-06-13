<?php

namespace App\Services;

use App\Models\AiAccount;
use App\Models\AiUsageRecord;
use App\Models\CommissionRecord;
use App\Models\License;
use App\Models\LicenseActivation;
use App\Models\MarketingChannel;
use App\Models\Order;
use App\Models\PromotionLink;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerPortalService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly OrderService $orderService,
    ) {
    }

    public function licenses(User $user, ?int $tenantId = null): Collection
    {
        $licenses = License::query()
            ->whereIn('tenant_id', $this->tenantIds($user, $tenantId))
            ->with('productPlan')
            ->latest('id')
            ->get();

        $licenses->each(function (License $license) {
            $license->setAttribute(
                'license_key',
                $license->license_key_encrypted ? Crypt::decryptString($license->license_key_encrypted) : null
            );
            $license->setAttribute('source_order_id', $license->metadata['source_order_id'] ?? null);
        });

        return $licenses;
    }

    public function orders(User $user, ?int $tenantId = null): Collection
    {
        return Order::query()
            ->whereIn('tenant_id', $this->tenantIds($user, $tenantId))
            ->with(['items', 'payments'])
            ->latest('id')
            ->get();
    }

    public function usageRecords(User $user, ?int $tenantId = null): Collection
    {
        return AiUsageRecord::query()
            ->whereIn('tenant_id', $this->tenantIds($user, $tenantId))
            ->latest('id')
            ->get();
    }

    public function aiAccounts(User $user, ?int $tenantId = null): Collection
    {
        return AiAccount::query()
            ->whereIn('tenant_id', $this->tenantIds($user, $tenantId))
            ->with('tenant')
            ->latest('id')
            ->get();
    }

    public function promotionLinks(User $user, ?int $tenantId = null): Collection
    {
        $channelIds = MarketingChannel::query()
            ->whereIn('tenant_id', $this->tenantIds($user, $tenantId))
            ->pluck('id');

        $links = PromotionLink::query()
            ->whereIn('marketing_channel_id', $channelIds)
            ->with('channel')
            ->latest('id')
            ->get();

        $links->each(function (PromotionLink $link) {
            $link->setAttribute(
                'orders_count',
                CommissionRecord::where('marketing_channel_id', $link->marketing_channel_id)->count()
            );
            $link->setAttribute(
                'commission_amount_cents',
                CommissionRecord::where('marketing_channel_id', $link->marketing_channel_id)->sum('commission_amount_cents')
            );
        });

        return $links;
    }

    public function commissions(User $user, ?int $tenantId = null): Collection
    {
        $channelIds = MarketingChannel::query()
            ->whereIn('tenant_id', $this->tenantIds($user, $tenantId))
            ->pluck('id');

        return CommissionRecord::query()
            ->whereIn('marketing_channel_id', $channelIds)
            ->latest('id')
            ->get();
    }

    public function me(User $user): array
    {
        return [
            'user' => $user->fresh('tenants'),
        ];
    }

    public function dashboard(User $user): array
    {
        $tenantIds = $this->tenantIds($user);
        $channelIds = MarketingChannel::query()
            ->whereIn('tenant_id', $tenantIds)
            ->pluck('id');

        return [
            'licenses_count' => License::whereIn('tenant_id', $tenantIds)->count(),
            'orders_count' => Order::whereIn('tenant_id', $tenantIds)->count(),
            'commission_amount_cents' => CommissionRecord::whereIn('marketing_channel_id', $channelIds)->sum('commission_amount_cents'),
            'promotion_links_count' => PromotionLink::whereIn('marketing_channel_id', $channelIds)->count(),
            'ai_balance_amount' => AiAccount::whereIn('tenant_id', $tenantIds)->sum('balance_amount'),
            'ai_balance_tokens' => AiAccount::whereIn('tenant_id', $tenantIds)->sum('balance_tokens'),
            'recent_orders' => Order::whereIn('tenant_id', $tenantIds)
                ->with(['items', 'payments'])
                ->latest('id')
                ->limit(5)
                ->get(),
            'recent_licenses' => License::whereIn('tenant_id', $tenantIds)
                ->with('productPlan')
                ->latest('id')
                ->limit(5)
                ->get()
                ->each(function (License $license) {
                    $license->setAttribute('source_order_id', $license->metadata['source_order_id'] ?? null);
                    $license->setAttribute('valid_until_label', $license->expires_at ? Carbon::parse($license->expires_at)->toDateString() : null);
                }),
        ];
    }

    public function requestRenewal(User $user, array $data): Order
    {
        $this->assertTenantOwned($user, (int) $data['tenant_id']);

        return $this->orderService->createOrder([
            'tenant_id' => $data['tenant_id'],
            'user_id' => $user->id,
            'product_plan_id' => $data['product_plan_id'],
            'quantity' => $data['quantity'] ?? 1,
            'payment_channel' => $data['payment_channel'],
            'metadata' => [
                'source' => 'customer_portal_renewal',
                'requested_by_user_id' => $user->id,
            ],
        ]);
    }

    public function copyLicenseKey(User $user, int $licenseId): array
    {
        $license = $this->ownedLicense($user, $licenseId);

        if (! $license->license_key_encrypted) {
            throw ValidationException::withMessages([
                'license_id' => ['The LicenseKey is not available for this historical license.'],
            ]);
        }

        $this->auditService->record('license_key.copied', $license->tenant_id, $user->id, $license);

        return [
            'license_id' => $license->id,
            'license_key' => Crypt::decryptString($license->license_key_encrypted),
        ];
    }

    public function unbindDomain(User $user, int $licenseId): License
    {
        $license = $this->ownedLicense($user, $licenseId);

        return DB::transaction(function () use ($license, $user) {
            $license->update([
                'domain' => null,
                'domain_hash' => null,
                'metadata' => array_merge($license->metadata ?? [], [
                    'domain_unbound_at' => now()->toIso8601String(),
                    'domain_unbound_by_user_id' => $user->id,
                ]),
            ]);

            LicenseActivation::where('license_id', $license->id)->update([
                'domain' => null,
            ]);

            $this->auditService->record('license.domain_unbound', $license->tenant_id, $user->id, $license);

            return $license->fresh();
        });
    }

    private function ownedLicense(User $user, int $licenseId): License
    {
        return License::query()
            ->whereKey($licenseId)
            ->whereIn('tenant_id', $this->tenantIds($user))
            ->firstOrFail();
    }

    private function tenantIds(User $user, ?int $tenantId = null): array
    {
        $ids = $user->tenants()->pluck('tenants.id')->map(fn ($id) => (int) $id)->all();

        if ($tenantId === null) {
            return $ids;
        }

        $this->assertTenantOwned($user, $tenantId, $ids);

        return [$tenantId];
    }

    private function assertTenantOwned(User $user, int $tenantId, ?array $tenantIds = null): void
    {
        $tenantIds ??= $user->tenants()->pluck('tenants.id')->map(fn ($id) => (int) $id)->all();

        if (! in_array($tenantId, $tenantIds, true)) {
            throw ValidationException::withMessages([
                'tenant_id' => ['The tenant does not belong to the authenticated customer.'],
            ]);
        }
    }
}
