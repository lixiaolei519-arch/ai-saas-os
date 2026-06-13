<?php

namespace App\Services\Payments;

class MockPayAdapter extends AbstractHmacGateway
{
    public function channel(): string
    {
        return 'mock';
    }

    protected function secret(): string
    {
        return (string) config('payments.channels.mock.webhook_secret');
    }
}
