<?php

return [
    'default_currency' => 'CNY',
    'channels' => [
        'wechat' => [
            'merchant_id' => env('WECHAT_PAY_MERCHANT_ID'),
            'app_id' => env('WECHAT_PAY_APP_ID'),
            'webhook_secret' => env('WECHAT_PAY_WEBHOOK_SECRET', 'local-wechat-secret'),
        ],
        'alipay' => [
            'app_id' => env('ALIPAY_APP_ID'),
            'webhook_secret' => env('ALIPAY_WEBHOOK_SECRET', 'local-alipay-secret'),
        ],
    ],
];
