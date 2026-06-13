<?php

namespace App\Services;

use App\Models\AiUsageRecord;
use App\Models\CommissionRecord;
use App\Models\License;
use App\Models\LicenseActivation;
use App\Models\MarketingChannel;
use App\Models\Order;
use App\Models\PromotionLink;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
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
        return License::query()
            ->whereIn('tenant_id', $this->tenantIds($user, $tenantId))
            ->with('productPlan')
            ->latest('id')
            ->get();
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

    public function promotionLinks(User $user, ?int $tenantId = null): Collection
    {
        $channelIds = MarketingChannel::query()
            ->whereIn('tenant_id', $this->tenantIds($user, $tenantId))
            ->pluck('id');

        return PromotionLink::query()
            ->whereIn('marketing_channel_id', $channelIds)
            ->with('channel')
            ->latest('id')
            ->get();
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
