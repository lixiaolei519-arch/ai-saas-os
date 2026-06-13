<?php

namespace App\Services\Payments;

class AlipayGateway extends AbstractHmacGateway
{
    public function channel(): string
    {
        return 'alipay';
    }

    protected function secret(): string
    {
        return (string) config('payments.channels.alipay.webhook_secret');
    }
}
