<?php

namespace App\Services\Payments;

use App\Models\Payment;

class WechatPayAdapter extends AbstractHmacGateway
{
    public function channel(): string
    {
        return 'wechat';
    }

    public function buildPayParams(Payment $payment): array
    {
        $required = [
            'WECHAT_PAY_MCH_ID',
            'WECHAT_PAY_APP_ID',
            'WECHAT_PAY_CERT_PATH',
            'WECHAT_PAY_KEY_PATH',
            'WECHAT_PAY_API_V3_KEY',
        ];

        if (! $this->hasConfigValues([
            config('payments.channels.wechat.mch_id'),
            config('payments.channels.wechat.app_id'),
            config('payments.channels.wechat.cert_path'),
            config('payments.channels.wechat.key_path'),
            config('payments.channels.wechat.api_v3_key'),
        ])) {
            return $this->unconfiguredPayParams(
                $payment,
                'wechat_pay_unconfigured',
                'WeChat Pay credentials are not configured. Use mock payment or set the required WECHAT_PAY_* values before production payment traffic.',
                $required
            );
        }

        return array_merge(parent::buildPayParams($payment), [
            'configured' => true,
            'merchant_id' => config('payments.channels.wechat.mch_id'),
            'app_id' => config('payments.channels.wechat.app_id'),
        ]);
    }

    protected function secret(): string
    {
        return (string) config('payments.channels.wechat.webhook_secret');
    }
}
