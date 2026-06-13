<?php

namespace App\Services;

use App\Models\RiskBlacklistEntry;
use App\Models\RiskEvent;
use Illuminate\Support\Facades\RateLimiter;

class RiskService
{
    public function recordEvent(
        string $eventType,
        string $severity,
        string $decision,
        array $context = [],
        ?int $tenantId = null,
        ?int $userId = null,
        ?int $licenseId = null,
    ): RiskEvent {
        return RiskEvent::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'license_id' => $licenseId,
            'event_type' => $eventType,
            'severity' => $severity,
            'decision' => $decision,
            'ip_address' => $context['ip_address'] ?? null,
            'user_agent' => $context['user_agent'] ?? null,
            'fingerprint_hash' => $context['fingerprint_hash'] ?? null,
            'context' => $context,
            'occurred_at' => now(),
        ]);
    }

    public function evaluateBlacklist(array $data): RiskEvent
    {
        $valueHash = hash('sha256', $data['value']);
        $blocked = RiskBlacklistEntry::where('value_type', $data['value_type'])
            ->where('value_hash', $valueHash)
            ->where(function ($query) use ($data) {
                $query->whereNull('tenant_id')->orWhere('tenant_id', $data['tenant_id'] ?? null);
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();

        return $this->recordEvent(
            'risk.blacklist.evaluate',
            $blocked ? 'high' : 'low',
            $blocked ? 'deny' : 'allow',
            [
                'value_type' => $data['value_type'],
                'value_hash' => $valueHash,
            ],
            $data['tenant_id'] ?? null,
            $data['user_id'] ?? null,
        );
    }

    public function addBlacklistEntry(array $data): RiskBlacklistEntry
    {
        $valueHash = hash('sha256', $data['value']);

        return RiskBlacklistEntry::updateOrCreate(
            [
                'tenant_id' => $data['tenant_id'] ?? null,
                'value_type' => $data['value_type'],
                'value_hash' => $valueHash,
            ],
            [
                'value' => $data['value'],
                'reason' => $data['reason'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
            ]
        );
    }

    public function checkRateLimit(array $data): RiskEvent
    {
        $key = 'risk-rate-limit:'.($data['tenant_id'] ?? 'global').':'.$data['key'];
        $maxAttempts = (int) ($data['max_attempts'] ?? 60);
        $decaySeconds = (int) ($data['decay_seconds'] ?? 60);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return $this->recordEvent(
                'risk.rate_limit.exceeded',
                'high',
                'deny',
                [
                    'key' => $data['key'],
                    'max_attempts' => $maxAttempts,
                    'decay_seconds' => $decaySeconds,
                    'available_in' => RateLimiter::availableIn($key),
                ],
                $data['tenant_id'] ?? null,
                $data['user_id'] ?? null,
            );
        }

        RateLimiter::hit($key, $decaySeconds);

        return $this->recordEvent(
            'risk.rate_limit.checked',
            'low',
            'allow',
            [
                'key' => $data['key'],
                'max_attempts' => $maxAttempts,
                'remaining' => RateLimiter::remaining($key, $maxAttempts),
            ],
            $data['tenant_id'] ?? null,
            $data['user_id'] ?? null,
        );
    }

    public function recordHighRiskOperation(array $data): RiskEvent
    {
        return $this->recordEvent(
            'risk.high_risk_operation',
            $data['severity'] ?? 'high',
            $data['decision'] ?? 'review',
            $data['context'] ?? [],
            $data['tenant_id'] ?? null,
            $data['user_id'] ?? null,
            $data['license_id'] ?? null,
        );
    }
}
