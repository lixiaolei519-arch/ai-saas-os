<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentCallbackController extends Controller
{
    public function store(string $channel, Request $request, PaymentService $paymentService): JsonResponse
    {
        validator(['channel' => $channel], [
            'channel' => ['required', Rule::in(['mock', 'wechat', 'alipay'])],
        ])->validate();

        $payload = $request->validate([
            'out_trade_no' => ['required', 'string'],
            'provider_trade_no' => ['nullable', 'string'],
            'trade_status' => ['required', 'string'],
            'amount_cents' => ['nullable', 'integer', 'min:0'],
            'signature' => ['nullable', 'string'],
            'signature_valid' => ['nullable', 'boolean'],
        ]);

        return response()->json([
            'data' => $paymentService->handleCallback($channel, $payload, $request->headers->all()),
        ]);
    }
}
