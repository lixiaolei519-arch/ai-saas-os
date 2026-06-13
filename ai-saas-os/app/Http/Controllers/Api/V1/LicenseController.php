<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\LicenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public function store(Request $request, LicenseService $licenseService): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'product_plan_id' => ['nullable', 'integer', 'exists:product_plans,id'],
            'domain' => ['nullable', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date'],
            'max_activations' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'metadata' => ['nullable', 'array'],
        ]);

        $result = $licenseService->issue($data);

        return response()->json([
            'data' => [
                'license_key' => $result['license_key'],
                'license' => $result['license'],
                'signed_payload' => $result['signed_payload'],
            ],
        ], 201);
    }

    public function verify(Request $request, LicenseService $licenseService): JsonResponse
    {
        $data = $request->validate([
            'license_key' => ['required', 'string'],
            'domain' => ['nullable', 'string', 'max:255'],
            'fingerprint' => ['nullable', 'string', 'max:255'],
        ]);

        $data['ip_address'] = $request->ip();
        $data['user_agent'] = $request->userAgent();

        return response()->json([
            'data' => $licenseService->verify($data),
        ]);
    }
}
