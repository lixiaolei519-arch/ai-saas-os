<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthorizationService
{
    public function __construct(private readonly AuditService $auditService)
    {
    }

    public function createPermission(array $data): Permission
    {
        return Permission::firstOrCreate(
            ['slug' => $data['slug']],
            [
                'name' => $data['name'],
                'module' => $data['module'],
            ]
        );
    }

    public function createRole(array $data): Role
    {
        $role = Role::create([
            'tenant_id' => $data['tenant_id'] ?? null,
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Str::slug($data['name']),
            'scope' => isset($data['tenant_id']) ? 'tenant' : 'global',
            'metadata' => $data['metadata'] ?? [],
        ]);

        $this->auditService->record('role.created', $role->tenant_id, null, $role);

        return $role;
    }

    public function attachPermission(int $roleId, int $permissionId): Role
    {
        $role = Role::findOrFail($roleId);
        $permission = Permission::findOrFail($permissionId);

        $role->permissions()->syncWithoutDetaching([$permission->id]);
        $this->auditService->record('role.permission_attached', $role->tenant_id, null, $role, [
            'permission_id' => $permission->id,
        ]);

        return $role->fresh('permissions');
    }

    public function assignRole(int $tenantId, int $userId, int $roleId): User
    {
        $role = Role::findOrFail($roleId);

        if ($role->tenant_id !== null && $role->tenant_id !== $tenantId) {
            throw ValidationException::withMessages([
                'role_id' => ['Role does not belong to this tenant.'],
            ]);
        }

        $user = User::findOrFail($userId);
        $user->roles()->syncWithoutDetaching([
            $role->id => ['tenant_id' => $tenantId],
        ]);

        $this->auditService->record('user.role_assigned', $tenantId, $user->id, $role, [
            'role_id' => $role->id,
        ]);

        return $user->fresh('roles.permissions');
    }

    public function userHasPermission(int $tenantId, int $userId, string $permissionSlug): bool
    {
        return DB::table('role_user')
            ->join('permission_role', 'role_user.role_id', '=', 'permission_role.role_id')
            ->join('permissions', 'permission_role.permission_id', '=', 'permissions.id')
            ->where('role_user.tenant_id', $tenantId)
            ->where('role_user.user_id', $userId)
            ->where('permissions.slug', $permissionSlug)
            ->exists();
    }
}
