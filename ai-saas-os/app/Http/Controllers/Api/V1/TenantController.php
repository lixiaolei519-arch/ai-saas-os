<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function store(Request $request, TenantService $tenantService): JsonResponse
    {
        $data = $request->validate([
            'tenant_name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'max:255'],
            'owner_password' => ['required', 'string', 'min:8'],
            'plan_code' => ['nullable', 'string', 'max:64'],
            'ai_balance_amount' => ['nullable', 'numeric', 'min:0'],
            'ai_balance_tokens' => ['nullable', 'integer', 'min:0'],
            'metadata' => ['nullable', 'array'],
        ]);

        return response()->json([
            'data' => $tenantService->createTenantWithOwner($data),
        ], 201);
    }
}
