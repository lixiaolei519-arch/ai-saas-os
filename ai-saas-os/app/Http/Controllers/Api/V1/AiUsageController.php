<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiUsageController extends Controller
{
    public function balance(int $tenant, BillingService $billingService): JsonResponse
    {
        return response()->json([
            'data' => $billingService->balance($tenant),
        ]);
    }

    public function grant(Request $request, BillingService $billingService): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'tokens' => ['nullable', 'integer', 'min:0'],
            'source' => ['nullable', 'string', 'max:64'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        return response()->json([
            'data' => $billingService->grantCredits($data),
        ]);
    }

    public function store(Request $request, BillingService $billingService): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'license_key' => ['required', 'string'],
            'domain' => ['nullable', 'string', 'max:255'],
            'fingerprint' => ['required', 'string', 'max:255'],
            'request_id' => ['required', 'string', 'max:255', 'unique:ai_usage_records,request_id'],
            'provider' => ['required', 'string', 'max:64'],
            'model' => ['required', 'string', 'max:128'],
            'prompt_tokens' => ['required', 'integer', 'min:0'],
            'completion_tokens' => ['required', 'integer', 'min:0'],
            'unit_price_per_1k' => ['required', 'numeric', 'min:0'],
            'metadata' => ['nullable', 'array'],
        ]);

        $data['ip_address'] = $request->ip();
        $data['user_agent'] = $request->userAgent();

        return response()->json([
            'data' => $billingService->chargeUsage($data),
        ], 201);
    }
}
