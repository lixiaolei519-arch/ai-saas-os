<?php

return [
    'provider' => env('AI_PROVIDER', 'mock'),
    'mock' => [
        'model' => env('AI_MOCK_MODEL', 'mock-gpt-lite'),
        'unit_price_per_1k' => (float) env('AI_MOCK_UNIT_PRICE_PER_1K', 0.01),
        'max_completion_tokens' => (int) env('AI_MOCK_MAX_COMPLETION_TOKENS', 256),
    ],
];
