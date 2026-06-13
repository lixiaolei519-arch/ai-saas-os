<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\MarketingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MarketingController extends Controller
{
    public function createChannel(Request $request, MarketingService $marketingService): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:128', 'unique:marketing_channels,code'],
            'type' => ['nullable', Rule::in(['affiliate', 'partner', 'campaign'])],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'commission_rate_basis_points' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'metadata' => ['nullable', 'array'],
        ]);

        return response()->json([
            'data' => $marketingService->createChannel($data),
        ], 201);
    }

    public function createPromotionLink(Request $request, MarketingService $marketingService): JsonResponse
    {
        $data = $request->validate([
            'marketing_channel_id' => ['required', 'integer', 'exists:marketing_channels,id'],
            'code' => ['nullable', 'string', 'max:128', 'unique:promotion_links,code'],
            'destination_url' => ['required', 'url', 'max:2048'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'metadata' => ['nullable', 'array'],
        ]);

        return response()->json([
            'data' => $marketingService->createPromotionLink($data),
        ], 201);
    }

    public function attributePromotion(Request $request, MarketingService $marketingService): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'promotion_link_id' => ['nullable', 'integer', 'exists:promotion_links,id', 'required_without:promotion_link_code'],
            'promotion_link_code' => ['nullable', 'string', 'max:128', 'required_without:promotion_link_id'],
            'attributed_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ]);

        return response()->json([
            'data' => $marketingService->attributePromotion($data),
        ], 201);
    }

    public function calculateCommission(Request $request, MarketingService $marketingService): JsonResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
        ]);

        return response()->json([
            'data' => $marketingService->calculateCommission($data),
        ]);
    }

    public function createTemplate(Request $request, MarketingService $marketingService): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'code' => ['required', 'string', 'max:128'],
            'name' => ['required', 'string', 'max:255'],
            'channel' => ['nullable', Rule::in(['email', 'sms', 'wechat', 'in_app'])],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'variables' => ['nullable', 'array'],
        ]);

        return response()->json([
            'data' => $marketingService->createTemplate($data),
        ], 201);
    }

    public function sendNotification(Request $request, MarketingService $marketingService): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'template_code' => ['required', 'string', 'max:128'],
            'recipient' => ['required', 'string', 'max:255'],
            'variables' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ]);

        return response()->json([
            'data' => $marketingService->sendNotification($data),
        ], 201);
    }

    public function scheduleRenewal(Request $request, MarketingService $marketingService): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'license_id' => ['nullable', 'integer', 'exists:licenses,id'],
            'product_plan_id' => ['required', 'integer', 'exists:product_plans,id'],
            'interval' => ['nullable', Rule::in(['day', 'month', 'quarter', 'year'])],
            'next_run_at' => ['required', 'date'],
            'metadata' => ['nullable', 'array'],
        ]);

        return response()->json([
            'data' => $marketingService->scheduleRenewal($data),
        ], 201);
    }

    public function processRenewals(MarketingService $marketingService): JsonResponse
    {
        return response()->json([
            'data' => $marketingService->processDueRenewals(),
        ]);
    }

    public function processRenewalReminders(MarketingService $marketingService): JsonResponse
    {
        return response()->json([
            'data' => $marketingService->processDueRenewalReminders(),
        ]);
    }
}
