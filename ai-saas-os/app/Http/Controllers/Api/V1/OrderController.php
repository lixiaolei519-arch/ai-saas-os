<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function store(Request $request, OrderService $orderService): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'product_plan_id' => ['required', 'integer', 'exists:product_plans,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'discount_cents' => ['nullable', 'integer', 'min:0'],
            'payment_channel' => ['required', Rule::in(['wechat', 'alipay'])],
            'metadata' => ['nullable', 'array'],
        ]);

        return response()->json([
            'data' => $orderService->createOrder($data),
        ], 201);
    }
}
