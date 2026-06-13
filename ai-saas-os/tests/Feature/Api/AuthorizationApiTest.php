<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_role_permissions_can_be_assigned_and_checked(): void
    {
        $tenant = $this->postJson('/api/v1/tenants', [
            'tenant_name' => 'RBAC Tenant',
            'owner_name' => 'Owner',
            'owner_email' => 'rbac-owner@example.com',
            'owner_password' => 'password123',
        ])->assertCreated()->json('data');

        $userId = $tenant['users'][0]['id'];

        $permission = $this->postJson('/api/v1/permissions', [
            'name' => 'Create License',
            'slug' => 'license.create',
            'module' => 'license',
        ])
            ->assertCreated()
            ->json('data');

        $role = $this->postJson('/api/v1/roles', [
            'tenant_id' => $tenant['id'],
            'name' => 'License Manager',
            'slug' => 'license-manager',
        ])
            ->assertCreated()
            ->json('data');

        $this->postJson('/api/v1/roles/'.$role['id'].'/permissions', [
            'permission_id' => $permission['id'],
        ])
            ->assertOk()
            ->assertJsonPath('data.permissions.0.slug', 'license.create');

        $this->postJson('/api/v1/tenants/'.$tenant['id'].'/users/'.$userId.'/roles', [
            'role_id' => $role['id'],
        ])
            ->assertOk()
            ->assertJsonPath('data.roles.0.slug', 'license-manager');

        $this->getJson('/api/v1/tenants/'.$tenant['id'].'/users/'.$userId.'/permissions/license.create')
            ->assertOk()
            ->assertJsonPath('data.allowed', true);

        $this->getJson('/api/v1/tenants/'.$tenant['id'].'/users/'.$userId.'/permissions/payment.refund')
            ->assertOk()
            ->assertJsonPath('data.allowed', false);
    }
}
