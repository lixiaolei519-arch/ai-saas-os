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
            'wechat' => app(WeChatPayGateway::class),
            'alipay' => app(AlipayGateway::class),
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
