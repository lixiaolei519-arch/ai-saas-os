<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CustomerPortalService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerPortalController extends Controller
{
    public function __construct(
        private readonly CustomerPortalService $portalService,
    ) {
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->portalService->me($request->user()),
        ]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->portalService->dashboard($request->user()),
        ]);
    }

    public function licenses(Request $request): JsonResponse
    {
        return $this->paginated(
            $this->portalService->licenses($request->user(), $this->tenantId($request)),
            $request
        );
    }

    public function orders(Request $request): JsonResponse
    {
        return $this->paginated(
            $this->portalService->orders($request->user(), $this->tenantId($request)),
            $request
        );
    }

    public function usageRecords(Request $request): JsonResponse
    {
        return $this->paginated(
            $this->portalService->usageRecords($request->user(), $this->tenantId($request)),
            $request
        );
    }

    public function aiAccount(Request $request): JsonResponse
    {
        $accounts = $this->portalService->aiAccounts($request->user(), $this->tenantId($request));

        return response()->json([
            'data' => [
                'accounts' => $accounts->values(),
                'balance_amount' => $accounts->sum(fn ($account) => (float) $account->balance_amount),
                'balance_tokens' => $accounts->sum('balance_tokens'),
                'currency' => $accounts->first()->currency ?? 'CNY',
            ],
        ]);
    }

    public function promotionLinks(Request $request): JsonResponse
    {
        return $this->paginated(
            $this->portalService->promotionLinks($request->user(), $this->tenantId($request)),
            $request
        );
    }

    public function plugins(Request $request): JsonResponse
    {
        return $this->paginated(
            $this->portalService->plugins($request->user(), $this->tenantId($request)),
            $request
        );
    }

    public function referrals(Request $request): JsonResponse
    {
        return $this->promotionLinks($request);
    }

    public function commissions(Request $request): JsonResponse
    {
        return $this->paginated(
            $this->portalService->commissions($request->user(), $this->tenantId($request)),
            $request
        );
    }

    public function requestRenewal(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'product_plan_id' => ['required', 'integer', 'exists:product_plans,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'payment_channel' => ['required', Rule::in(['mock', 'wechat', 'alipay'])],
        ]);

        return response()->json([
            'data' => $this->portalService->requestRenewal($request->user(), $data),
        ], 201);
    }

    public function copyLicenseKey(int $license, Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->portalService->copyLicenseKey($request->user(), $license),
        ]);
    }

    public function unbindDomain(int $license, Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->portalService->unbindDomain($request->user(), $license),
        ]);
    }

    private function tenantId(Request $request): ?int
    {
        $data = $request->validate([
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
        ]);

        return isset($data['tenant_id']) ? (int) $data['tenant_id'] : null;
    }

    private function paginated(Collection $items, Request $request): JsonResponse
    {
        $data = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $page = (int) ($data['page'] ?? 1);
        $perPage = (int) ($data['per_page'] ?? 15);
        $total = $items->count();
        $pageItems = $items->forPage($page, $perPage)->values();

        return response()->json([
            'data' => $pageItems,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) max(1, ceil($total / $perPage)),
            ],
        ]);
    }
}
