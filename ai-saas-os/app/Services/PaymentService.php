<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentCallback;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly PaymentGatewayManager $paymentGatewayManager,
        private readonly WorkflowService $workflowService,
        private readonly MarketingService $marketingService,
    ) {
    }

    public function handleCallback(string $channel, array $payload, array $headers = []): PaymentCallback
    {
        return DB::transaction(function () use ($channel, $payload, $headers) {
            $payment = isset($payload['out_trade_no'])
                ? Payment::where('out_trade_no', $payload['out_trade_no'])->lockForUpdate()->first()
                : null;

            $signatureValid = $this->paymentGatewayManager
                ->gateway($channel)
                ->verifyCallback($payload, $headers);
            $callback = PaymentCallback::create([
                'payment_id' => $payment?->id,
                'channel' => $channel,
                'out_trade_no' => $payload['out_trade_no'] ?? null,
                'signature_valid' => $signatureValid,
                'status' => $signatureValid ? 'processing' : 'rejected',
                'headers' => $headers,
                'payload' => $payload,
                'processed_at' => now(),
            ]);

            if (! $payment) {
                $callback->update([
                    'status' => 'failed',
                    'error_message' => 'payment_not_found',
                ]);

                return $callback->fresh();
            }

            if (! $signatureValid) {
                $callback->update(['error_message' => 'invalid_signature']);

                return $callback->fresh(['payment']);
            }

            $paid = in_array($payload['trade_status'] ?? null, ['SUCCESS', 'TRADE_SUCCESS', 'paid'], true);

            if ($paid) {
                $payment->update([
                    'provider_trade_no' => $payload['provider_trade_no'] ?? $payment->provider_trade_no,
                    'status' => 'paid',
                    'paid_at' => now(),
                    'callback_payload' => $payload,
                ]);

                $payment->order()->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);

                $this->auditService->record('payment.paid', $payment->order->tenant_id, $payment->order->user_id, $payment);
                $this->workflowService->triggerEvent($payment->order->tenant_id, 'order.paid', [
                    'order_id' => $payment->order_id,
                    'payment_id' => $payment->id,
                    'channel' => $payment->channel,
                ]);
                $this->marketingService->calculateCommissionForOrder($payment->order);
                $callback->update(['status' => 'processed']);
            }

            return $callback->fresh(['payment']);
        });
    }
}
