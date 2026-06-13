<?php

namespace App\Contracts;

use App\Models\Payment;

interface PaymentGateway
{
    public function channel(): string;

    public function buildPayParams(Payment $payment): array;

    public function verifyCallback(array $payload, array $headers = []): bool;
}
