<?php

namespace App\Services;

use App\Models\AiAccount;
use App\Models\AiUsageRecord;
use App\Models\BalanceTransaction;
use App\Services\Ai\MockAiProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BillingService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly LicenseService $licenseService,
        private readonly MockAiProvider $mockAiProvider,
    ) {
    }

    public function balance(int $tenantId): AiAccount
    {
        return AiAccount::where('tenant_id', $tenantId)->firstOrFail();
    }

    public function grantCredits(array $data): AiAccount
    {
        return DB::transaction(function () use ($data) {
            $account = AiAccount::where('tenant_id', $data['tenant_id'])->lockForUpdate()->firstOrFail();
            $amount = round((float) ($data['amount'] ?? 0), 6);
            $tokens = (int) ($data['tokens'] ?? 0);

            $account->balance_amount = round(((float) $account->balance_amount) + $amount, 6);
            $account->balance_tokens += $tokens;
            $account->save();

            BalanceTransaction::create([
                'tenant_id' => $account->tenant_id,
                'ai_account_id' => $account->id,
                'type' => 'grant',
                'amount_delta' => $amount,
                'token_delta' => $tokens,
                'balance_after' => $account->balance_amount,
                'tokens_after' => $account->balance_tokens,
                'metadata' => [
                    'source' => $data['source'] ?? 'manual',
                    'note' => $data['note'] ?? null,
                ],
                'occurred_at' => now(),
            ]);

            $this->auditService->record('ai_account.credits_granted', $account->tenant_id, $data['user_id'] ?? null, $account, [
                'amount' => $amount,
                'tokens' => $tokens,
            ]);

            return $account->fresh();
        });
    }

    public function chargeUsage(array $data): AiUsageRecord
    {
        return DB::transaction(function () use ($data) {
            $licenseResult = $this->licenseService->verify([
                'license_key' => $data['license_key'],
                'domain' => $data['domain'] ?? null,
                'fingerprint' => $data['fingerprint'] ?? 'ai-request',
                'ip_address' => $data['ip_address'] ?? null,
                'user_agent' => $data['user_agent'] ?? null,
            ]);

            if (! ($licenseResult['valid'] ?? false)) {
                throw ValidationException::withMessages([
                    'license_key' => ['License validation failed: '.$licenseResult['reason']],
                ]);
            }

            if ((int) $licenseResult['license']->tenant_id !== (int) $data['tenant_id']) {
                throw ValidationException::withMessages([
                    'license_key' => ['License does not belong to this tenant.'],
                ]);
            }

            $account = AiAccount::where('tenant_id', $data['tenant_id'])->lockForUpdate()->firstOrFail();
            $promptTokens = (int) ($data['prompt_tokens'] ?? 0);
            $completionTokens = (int) ($data['completion_tokens'] ?? 0);
            $totalTokens = $promptTokens + $completionTokens;
            $unitPrice = (float) ($data['unit_price_per_1k'] ?? 0);
            $cost = round(($totalTokens / 1000) * $unitPrice, 6);

            if ((float) $account->balance_amount < $cost) {
                throw ValidationException::withMessages([
                    'balance' => ['AI account balance is insufficient.'],
                ]);
            }

            if ($account->balance_tokens < $totalTokens) {
                throw ValidationException::withMessages([
                    'balance_tokens' => ['AI token quota is insufficient.'],
                ]);
            }

            $usage = AiUsageRecord::create([
                'tenant_id' => $data['tenant_id'],
                'user_id' => $data['user_id'] ?? null,
                'request_id' => $data['request_id'],
                'provider' => $data['provider'],
                'model' => $data['model'],
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens' => $totalTokens,
                'unit_price_per_1k' => $unitPrice,
                'total_cost_amount' => $cost,
                'status' => 'charged',
                'metadata' => $data['metadata'] ?? [],
            ]);

            $account->balance_amount = round(((float) $account->balance_amount) - $cost, 6);
            $account->balance_tokens = max(0, $account->balance_tokens - $totalTokens);
            $account->save();

            BalanceTransaction::create([
                'tenant_id' => $account->tenant_id,
                'ai_account_id' => $account->id,
                'type' => 'consume',
                'amount_delta' => -$cost,
                'token_delta' => -$totalTokens,
                'balance_after' => $account->balance_amount,
                'tokens_after' => $account->balance_tokens,
                'related_type' => AiUsageRecord::class,
                'related_id' => $usage->id,
                'occurred_at' => now(),
            ]);

            $this->auditService->record('ai_usage.charged', $account->tenant_id, $data['user_id'] ?? null, $usage);

            return $usage->fresh();
        });
    }

    public function chargeMockCompletion(array $data): array
    {
        $completion = $this->mockAiProvider->complete($data['prompt'], $data['model'] ?? null);
        $usage = $this->chargeUsage([
            'tenant_id' => $data['tenant_id'],
            'user_id' => $data['user_id'] ?? null,
            'license_key' => $data['license_key'],
            'domain' => $data['domain'] ?? null,
            'fingerprint' => $data['fingerprint'],
            'request_id' => $data['request_id'] ?? 'mock-ai-'.(string) Str::uuid(),
            'provider' => 'mock',
            'model' => $completion['model'],
            'prompt_tokens' => $completion['prompt_tokens'],
            'completion_tokens' => $completion['completion_tokens'],
            'unit_price_per_1k' => $data['unit_price_per_1k'] ?? config('ai.mock.unit_price_per_1k', 0.01),
            'metadata' => array_merge($data['metadata'] ?? [], [
                'simulation' => true,
                'prompt_hash' => hash('sha256', $data['prompt']),
            ]),
        ]);

        return [
            'provider' => 'mock',
            'model' => $completion['model'],
            'simulation' => true,
            'message' => $completion['message'],
            'usage' => $usage,
        ];
    }

    public function usageRecords(int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return AiUsageRecord::query()
            ->with(['tenant', 'user'])
            ->latest('id')
            ->limit($limit)
            ->get();
    }
}
