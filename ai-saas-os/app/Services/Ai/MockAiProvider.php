<?php

namespace App\Services\Ai;

class MockAiProvider
{
    public function complete(string $prompt, ?string $model = null): array
    {
        $promptTokens = max(1, (int) ceil(mb_strlen($prompt) / 4));
        $completionTokens = min(
            (int) config('ai.mock.max_completion_tokens', 256),
            max(16, (int) ceil($promptTokens / 2))
        );

        return [
            'provider' => 'mock',
            'model' => $model ?: config('ai.mock.model', 'mock-gpt-lite'),
            'message' => '模拟 AI 回复：请求已完成，未调用任何真实大模型。',
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $promptTokens + $completionTokens,
        ];
    }
}
