<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentCallback;
use App\Models\ProductPlan;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly PaymentGatewayManager $paymentGatewayManager,
        private readonly WorkflowService $workflowService,
        private readonly MarketingService $marketingService,
        private readonly LicenseService $licenseService,
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
                if (isset($payload['amount_cents']) && (int) $payload['amount_cents'] !== (int) $payment->amount_cents) {
                    $callback->update([
                        'status' => 'rejected',
                        'error_message' => 'amount_mismatch',
                    ]);

                    return $callback->fresh(['payment']);
                }

                $payment->load('order');
                if ($payment->status === 'paid' || $payment->order?->status === 'paid') {
                    $payment->update([
                        'provider_trade_no' => $payload['provider_trade_no'] ?? $payment->provider_trade_no,
                        'callback_payload' => $payload,
                    ]);
                    $callback->update([
                        'status' => 'processed',
                        'error_message' => 'duplicate_callback_ignored',
                    ]);

                    return $callback->fresh(['payment']);
                }

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
                $paidOrder = $payment->order()->firstOrFail();

                $this->auditService->record('payment.paid', $paidOrder->tenant_id, $paidOrder->user_id, $payment);
                $this->workflowService->triggerEvent($paidOrder->tenant_id, 'order.paid', [
                    'order_id' => $paidOrder->id,
                    'payment_id' => $payment->id,
                    'channel' => $payment->channel,
                ]);
                $this->provisionLicenseForOrder($paidOrder);
                $this->marketingService->calculateCommissionForOrder($paidOrder);
                $callback->update(['status' => 'processed']);
            }

            return $callback->fresh(['payment']);
        });
    }

    private function provisionLicenseForOrder(Order $order): void
    {
        $metadata = $order->metadata ?? [];

        if (! empty($metadata['provisioned_license_id'])) {
            return;
        }

        $item = $order->items()->first();
        if (! $item?->product_plan_id) {
            return;
        }

        $plan = ProductPlan::find($item->product_plan_id);
        if (! $plan) {
            return;
        }

        $result = $this->licenseService->issue([
            'tenant_id' => $order->tenant_id,
            'product_plan_id' => $plan->id,
            'domain' => $metadata['license_domain'] ?? null,
            'expires_at' => $this->licenseExpiresAt($plan->billing_cycle),
            'max_activations' => $metadata['max_activations'] ?? 1,
            'metadata' => [
                'source' => 'paid_order',
                'source_order_id' => $order->id,
                'source_order_no' => $order->order_no,
            ],
        ]);

        $order->update([
            'metadata' => array_merge($metadata, [
                'provisioned_license_id' => $result['license']->id,
                'provisioned_license_key_last4' => substr($result['license_key'], -4),
            ]),
        ]);
    }

    private function licenseExpiresAt(string $billingCycle): string
    {
        return match ($billingCycle) {
            'day' => now()->addDay()->toIso8601String(),
            'quarter' => now()->addQuarter()->toIso8601String(),
            'year' => now()->addYear()->toIso8601String(),
            default => now()->addMonth()->toIso8601String(),
        };
    }
}
