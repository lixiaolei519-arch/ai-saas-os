<?php

namespace App\Services\Payments;

use App\Models\Payment;

class AlipayAdapter extends AbstractHmacGateway
{
    public function channel(): string
    {
        return 'alipay';
    }

    public function buildPayParams(Payment $payment): array
    {
        $required = [
            'ALIPAY_APP_ID',
            'ALIPAY_PRIVATE_KEY',
            'ALIPAY_PUBLIC_KEY',
        ];

        if (! $this->hasConfigValues([
            config('payments.channels.alipay.app_id'),
            config('payments.channels.alipay.private_key'),
            config('payments.channels.alipay.public_key'),
        ])) {
            return $this->unconfiguredPayParams(
                $payment,
                'alipay_unconfigured',
                'Alipay credentials are not configured. Use mock payment or set the required ALIPAY_* values before production payment traffic.',
                $required
            );
        }

        return array_merge(parent::buildPayParams($payment), [
            'configured' => true,
            'app_id' => config('payments.channels.alipay.app_id'),
        ]);
    }

    protected function secret(): string
    {
        return (string) config('payments.channels.alipay.webhook_secret');
    }
}
