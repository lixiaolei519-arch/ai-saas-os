<?php

return [
    'provider' => env('PAYMENT_PROVIDER', 'mock'),
    'default_currency' => 'CNY',
    'channels' => [
        'mock' => [
            'webhook_secret' => env('MOCK_PAY_WEBHOOK_SECRET', 'local-mock-secret'),
        ],
        'wechat' => [
            'merchant_id' => env('WECHAT_PAY_MCH_ID', env('WECHAT_PAY_MERCHANT_ID')),
            'mch_id' => env('WECHAT_PAY_MCH_ID', env('WECHAT_PAY_MERCHANT_ID')),
            'app_id' => env('WECHAT_PAY_APP_ID'),
            'cert_path' => env('WECHAT_PAY_CERT_PATH'),
            'key_path' => env('WECHAT_PAY_KEY_PATH'),
            'api_v3_key' => env('WECHAT_PAY_API_V3_KEY'),
            'webhook_secret' => env('WECHAT_PAY_WEBHOOK_SECRET', 'local-wechat-secret'),
        ],
        'alipay' => [
            'app_id' => env('ALIPAY_APP_ID'),
            'private_key' => env('ALIPAY_PRIVATE_KEY'),
            'public_key' => env('ALIPAY_PUBLIC_KEY'),
            'webhook_secret' => env('ALIPAY_WEBHOOK_SECRET', 'local-alipay-secret'),
        ],
    ],
];
