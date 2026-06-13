<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CustomerPortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerPortalController extends Controller
{
    public function __construct(
        private readonly CustomerPortalService $portalService,
    ) {
    }

    public function licenses(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->portalService->licenses($request->user(), $this->tenantId($request)),
        ]);
    }

    public function orders(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->portalService->orders($request->user(), $this->tenantId($request)),
        ]);
    }

    public function usageRecords(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->portalService->usageRecords($request->user(), $this->tenantId($request)),
        ]);
    }

    public function promotionLinks(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->portalService->promotionLinks($request->user(), $this->tenantId($request)),
        ]);
    }

    public function commissions(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->portalService->commissions($request->user(), $this->tenantId($request)),
        ]);
    }

    public function requestRenewal(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'product_plan_id' => ['required', 'integer', 'exists:product_plans,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'payment_channel' => ['required', Rule::in(['wechat', 'alipay'])],
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
}
