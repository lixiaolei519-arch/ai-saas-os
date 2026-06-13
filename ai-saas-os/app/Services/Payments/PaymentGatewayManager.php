<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use InvalidArgumentException;

class PaymentGatewayManager
{
    /**
     * @return array<string, PaymentGateway>
     */
    private function gateways(): array
    {
        return [
            'mock' => app(MockPayAdapter::class),
            'wechat' => app(WechatPayAdapter::class),
            'alipay' => app(AlipayAdapter::class),
        ];
    }

    public function gateway(string $channel): PaymentGateway
    {
        $gateway = $this->gateways()[$channel] ?? null;

        if (! $gateway) {
            throw new InvalidArgumentException("Unsupported payment channel [{$channel}].");
        }

        return $gateway;
    }
}
