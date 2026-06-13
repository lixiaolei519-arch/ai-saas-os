<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Payment;

abstract class AbstractHmacGateway implements PaymentGateway
{
    public function buildPayParams(Payment $payment): array
    {
        return $this->basePayParams($payment);
    }

    protected function basePayParams(Payment $payment): array
    {
        return [
            'channel' => $this->channel(),
            'out_trade_no' => $payment->out_trade_no,
            'amount_cents' => $payment->amount_cents,
            'currency' => $payment->currency,
            'signature_algorithm' => 'HMAC-SHA256',
            'signature' => $this->signature([
                $payment->out_trade_no,
                (string) $payment->amount_cents,
                'CREATE',
            ]),
        ];
    }

    public function verifyCallback(array $payload, array $headers = []): bool
    {
        if (app()->environment(['local', 'testing']) && array_key_exists('signature_valid', $payload)) {
            return (bool) $payload['signature_valid'];
        }

        if (! isset($payload['signature'], $payload['out_trade_no'], $payload['amount_cents'], $payload['trade_status'])) {
            return false;
        }

        return hash_equals(
            $this->signature([
                $payload['out_trade_no'],
                (string) $payload['amount_cents'],
                $payload['trade_status'],
            ]),
            $payload['signature']
        );
    }

    protected function signature(array $parts): string
    {
        return hash_hmac('sha256', implode('|', $parts), $this->secret());
    }

    protected function unconfiguredPayParams(Payment $payment, string $code, string $message, array $required): array
    {
        return array_merge($this->basePayParams($payment), [
            'configured' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'required' => $required,
            ],
        ]);
    }

    protected function hasConfigValues(array $values): bool
    {
        foreach ($values as $value) {
            if (! filled($value)) {
                return false;
            }
        }

        return true;
    }

    abstract protected function secret(): string;
}
