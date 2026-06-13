<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\RiskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RiskController extends Controller
{
    public function blacklist(Request $request, RiskService $riskService): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'value_type' => ['required', Rule::in(['ip', 'domain', 'fingerprint', 'user', 'license'])],
            'value' => ['required', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date'],
        ]);

        return response()->json([
            'data' => $riskService->addBlacklistEntry($data),
        ], 201);
    }

    public function evaluate(Request $request, RiskService $riskService): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'value_type' => ['required', Rule::in(['ip', 'domain', 'fingerprint', 'user', 'license'])],
            'value' => ['required', 'string', 'max:255'],
        ]);

        return response()->json([
            'data' => $riskService->evaluateBlacklist($data),
        ]);
    }

    public function rateLimit(Request $request, RiskService $riskService): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'key' => ['required', 'string', 'max:255'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'decay_seconds' => ['nullable', 'integer', 'min:1', 'max:86400'],
        ]);

        return response()->json([
            'data' => $riskService->checkRateLimit($data),
        ]);
    }

    public function highRisk(Request $request, RiskService $riskService): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'license_id' => ['nullable', 'integer', 'exists:licenses,id'],
            'severity' => ['nullable', Rule::in(['low', 'medium', 'high', 'critical'])],
            'decision' => ['nullable', Rule::in(['allow', 'review', 'deny'])],
            'context' => ['nullable', 'array'],
        ]);

        return response()->json([
            'data' => $riskService->recordHighRiskOperation($data),
        ], 201);
    }
}
