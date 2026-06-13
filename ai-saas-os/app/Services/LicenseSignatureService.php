<?php

namespace App\Services;

use RuntimeException;

class LicenseSignatureService
{
    public function sign(array $payload): array
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $privateKey = config('license.private_key');

        if ($privateKey && openssl_pkey_get_private($privateKey)) {
            openssl_sign($json, $signature, $privateKey, OPENSSL_ALGO_SHA256);

            return [
                'algorithm' => 'RS256',
                'payload' => $payload,
                'signature' => base64_encode($signature),
            ];
        }

        $secret = config('app.key');
        if (! is_string($secret) || $secret === '') {
            throw new RuntimeException('APP_KEY is required to sign licenses.');
        }

        return [
            'algorithm' => 'HS256-dev',
            'payload' => $payload,
            'signature' => hash_hmac('sha256', $json, $secret),
        ];
    }
}
