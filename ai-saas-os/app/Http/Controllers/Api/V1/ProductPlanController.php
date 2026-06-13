<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductPlanController extends Controller
{
    public function index(CatalogService $catalogService): JsonResponse
    {
        return response()->json([
            'data' => $catalogService->activePlans(),
        ]);
    }

    public function store(Request $request, CatalogService $catalogService): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', 'unique:product_plans,code'],
            'type' => ['nullable', Rule::in(['subscription', 'one_time', 'usage_package'])],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'billing_cycle' => ['nullable', Rule::in(['day', 'month', 'quarter', 'year', 'none'])],
            'price_cents' => ['required', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'max:8'],
            'features' => ['nullable', 'array'],
            'limits' => ['nullable', 'array'],
        ]);

        return response()->json([
            'data' => $catalogService->createPlan($data),
        ], 201);
    }
}
