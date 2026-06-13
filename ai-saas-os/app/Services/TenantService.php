<?php

namespace App\Services;

use App\Models\AiAccount;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantService
{
    public function __construct(private readonly AuditService $auditService)
    {
    }

    public function createTenantWithOwner(array $data): Tenant
    {
        return DB::transaction(function () use ($data) {
            $slug = $this->uniqueSlug($data['slug'] ?? $data['tenant_name']);

            $tenant = Tenant::create([
                'uuid' => (string) Str::uuid(),
                'name' => $data['tenant_name'],
                'slug' => $slug,
                'contact_name' => $data['owner_name'],
                'contact_email' => $data['owner_email'],
                'status' => 'active',
                'plan_code' => $data['plan_code'] ?? 'free',
                'metadata' => $data['metadata'] ?? [],
            ]);

            $user = User::firstOrCreate(
                ['email' => $data['owner_email']],
                [
                    'name' => $data['owner_name'],
                    'password' => Hash::make($data['owner_password']),
                    'status' => 'active',
                ]
            );

            $tenant->users()->syncWithoutDetaching([
                $user->id => [
                    'role' => 'owner',
                    'status' => 'active',
                    'joined_at' => now(),
                ],
            ]);

            AiAccount::create([
                'tenant_id' => $tenant->id,
                'balance_amount' => $data['ai_balance_amount'] ?? 0,
                'balance_tokens' => $data['ai_balance_tokens'] ?? 0,
                'currency' => 'CNY',
            ]);

            $this->auditService->record('tenant.created', $tenant->id, $user->id, $tenant);

            return $tenant->load(['users', 'aiAccount']);
        });
    }

    private function uniqueSlug(string $value): string
    {
        $base = Str::slug($value);
        $base = $base !== '' ? $base : 'tenant-'.Str::lower(Str::random(8));
        $slug = $base;
        $counter = 2;

        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
