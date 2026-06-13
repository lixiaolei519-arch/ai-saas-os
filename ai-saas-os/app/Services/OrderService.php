<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductPlan;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly PaymentGatewayManager $paymentGatewayManager,
    ) {
    }

    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $plan = ProductPlan::findOrFail($data['product_plan_id']);
            $quantity = (int) ($data['quantity'] ?? 1);
            $subtotal = $plan->price_cents * $quantity;
            $discount = (int) ($data['discount_cents'] ?? 0);
            $total = max(0, $subtotal - $discount);

            $order = Order::create([
                'tenant_id' => $data['tenant_id'],
                'user_id' => $data['user_id'] ?? null,
                'order_no' => 'ORD'.now()->format('YmdHis').Str::upper(Str::random(8)),
                'status' => 'pending',
                'subtotal_cents' => $subtotal,
                'discount_cents' => $discount,
                'total_cents' => $total,
                'currency' => $plan->currency,
                'metadata' => $data['metadata'] ?? [],
            ]);

            $order->items()->create([
                'product_plan_id' => $plan->id,
                'item_type' => $plan->type,
                'sku' => $plan->code,
                'description' => $plan->name,
                'quantity' => $quantity,
                'unit_amount_cents' => $plan->price_cents,
                'total_amount_cents' => $subtotal,
            ]);

            $payment = Payment::create([
                'order_id' => $order->id,
                'channel' => $data['payment_channel'],
                'out_trade_no' => 'PAY'.now()->format('YmdHis').Str::upper(Str::random(8)),
                'status' => 'pending',
                'amount_cents' => $total,
                'currency' => $plan->currency,
                'request_payload' => [
                    'channel' => $data['payment_channel'],
                    'order_no' => $order->order_no,
                ],
            ]);

            $payment->update([
                'request_payload' => $this->paymentGatewayManager
                    ->gateway($payment->channel)
                    ->buildPayParams($payment),
            ]);

            $this->auditService->record('order.created', $order->tenant_id, $order->user_id, $order);

            return $order->load(['items', 'payments']);
        });
    }
}
