<?php

namespace App\Services;

use App\Models\License;
use App\Models\LicenseActivation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LicenseService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly LicenseSignatureService $signatureService,
        private readonly RiskService $riskService,
    ) {
    }

    public function issue(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $plainKey = 'LIC-'.Str::upper(Str::random(32));
            $payload = [
                'tenant_id' => (int) $data['tenant_id'],
                'product_plan_id' => $data['product_plan_id'] ?? null,
                'domain' => $this->normalizeDomain($data['domain'] ?? null),
                'issued_at' => now()->toIso8601String(),
                'expires_at' => isset($data['expires_at']) ? Carbon::parse($data['expires_at'])->toIso8601String() : null,
                'max_activations' => (int) ($data['max_activations'] ?? 1),
            ];

            $signedPayload = $this->signatureService->sign($payload);

            $license = License::create([
                'tenant_id' => $payload['tenant_id'],
                'product_plan_id' => $payload['product_plan_id'],
                'license_key_hash' => $this->hashValue($plainKey),
                'signed_payload' => json_encode($signedPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'domain' => $payload['domain'],
                'domain_hash' => $payload['domain'] ? $this->hashValue($payload['domain']) : null,
                'status' => 'active',
                'max_activations' => $payload['max_activations'],
                'issued_at' => now(),
                'expires_at' => $data['expires_at'] ?? null,
                'metadata' => $data['metadata'] ?? [],
            ]);

            $this->auditService->record('license.issued', $license->tenant_id, null, $license);

            return [
                'license_key' => $plainKey,
                'license' => $license,
                'signed_payload' => $signedPayload,
            ];
        });
    }

    public function verify(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $license = License::where('license_key_hash', $this->hashValue($data['license_key']))->lockForUpdate()->first();
            $domain = $this->normalizeDomain($data['domain'] ?? null);
            $fingerprint = $data['fingerprint'] ?? 'unknown';
            $fingerprintHash = $this->hashValue($fingerprint);

            if (! $license) {
                $this->riskService->recordEvent('license.invalid_key', 'high', 'deny', [
                    'domain' => $domain,
                    'fingerprint_hash' => $fingerprintHash,
                ]);

                return ['valid' => false, 'reason' => 'license_not_found'];
            }

            if ($license->status !== 'active') {
                return $this->deny($license, 'license_inactive', $domain, $fingerprintHash);
            }

            if ($license->expires_at && $license->expires_at->isPast()) {
                return $this->deny($license, 'license_expired', $domain, $fingerprintHash);
            }

            if ($license->domain && $license->domain !== $domain) {
                return $this->deny($license, 'domain_mismatch', $domain, $fingerprintHash);
            }

            $activation = LicenseActivation::where('license_id', $license->id)
                ->where('fingerprint_hash', $fingerprintHash)
                ->first();

            if (! $activation && $license->activation_count >= $license->max_activations) {
                return $this->deny($license, 'activation_limit_reached', $domain, $fingerprintHash);
            }

            if ($activation?->revoked_at) {
                return $this->deny($license, 'activation_revoked', $domain, $fingerprintHash);
            }

            if (! $activation) {
                LicenseActivation::create([
                    'license_id' => $license->id,
                    'fingerprint_hash' => $fingerprintHash,
                    'domain' => $domain,
                    'ip_address' => $data['ip_address'] ?? null,
                    'user_agent' => $data['user_agent'] ?? null,
                    'activated_at' => now(),
                    'last_seen_at' => now(),
                ]);

                $license->increment('activation_count');
            } else {
                $activation->update([
                    'domain' => $domain,
                    'ip_address' => $data['ip_address'] ?? $activation->ip_address,
                    'user_agent' => $data['user_agent'] ?? $activation->user_agent,
                    'last_seen_at' => now(),
                ]);
            }

            $license->update(['last_verified_at' => now()]);
            $this->auditService->record('license.verified', $license->tenant_id, null, $license, ['domain' => $domain]);

            return [
                'valid' => true,
                'reason' => 'ok',
                'license' => $license->fresh(['tenant']),
            ];
        });
    }

    private function deny(License $license, string $reason, ?string $domain, string $fingerprintHash): array
    {
        $this->riskService->recordEvent('license.'.$reason, 'high', 'deny', [
            'domain' => $domain,
            'fingerprint_hash' => $fingerprintHash,
        ], $license->tenant_id, null, $license->id);

        return [
            'valid' => false,
            'reason' => $reason,
            'license_id' => $license->id,
        ];
    }

    private function normalizeDomain(?string $domain): ?string
    {
        return $domain ? Str::lower(trim($domain)) : null;
    }

    private function hashValue(string $value): string
    {
        return hash('sha256', $value);
    }
}
