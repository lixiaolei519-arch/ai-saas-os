<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AuthorizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthorizationController extends Controller
{
    public function createPermission(Request $request, AuthorizationService $authorizationService): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:permissions,slug'],
            'module' => ['required', 'string', 'max:64'],
        ]);

        return response()->json([
            'data' => $authorizationService->createPermission($data),
        ], 201);
    }

    public function createRole(Request $request, AuthorizationService $authorizationService): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ]);

        return response()->json([
            'data' => $authorizationService->createRole($data),
        ], 201);
    }

    public function attachPermission(int $role, Request $request, AuthorizationService $authorizationService): JsonResponse
    {
        $data = $request->validate([
            'permission_id' => ['required', 'integer', 'exists:permissions,id'],
        ]);

        return response()->json([
            'data' => $authorizationService->attachPermission($role, $data['permission_id']),
        ]);
    }

    public function assignRole(int $tenant, int $user, Request $request, AuthorizationService $authorizationService): JsonResponse
    {
        $data = $request->validate([
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        return response()->json([
            'data' => $authorizationService->assignRole($tenant, $user, $data['role_id']),
        ]);
    }

    public function check(int $tenant, int $user, string $permission, AuthorizationService $authorizationService): JsonResponse
    {
        return response()->json([
            'data' => [
                'allowed' => $authorizationService->userHasPermission($tenant, $user, $permission),
            ],
        ]);
    }
}
