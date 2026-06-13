<?php

namespace App\Services\Payments;

class WeChatPayGateway extends AbstractHmacGateway
{
    public function channel(): string
    {
        return 'wechat';
    }

    protected function secret(): string
    {
        return (string) config('payments.channels.wechat.webhook_secret');
    }
}
