<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\ProductPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LicenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerPortalSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_login_and_access_only_owned_portal_data(): void
    {
        [$customer, $tenant] = $this->customerTenant('portal-secure@example.com', 'portal-secure');
        [$otherCustomer, $otherTenant] = $this->customerTenant('portal-other@example.com', 'portal-other');
        $plan = $this->plan();

        $license = app(LicenseService::class)->issue([
            'tenant_id' => $tenant->id,
            'product_plan_id' => $plan->id,
            'domain' => 'owned.example.cn',
            'expires_at' => now()->addMonth()->toIso8601String(),
        ]);
        app(LicenseService::class)->issue([
            'tenant_id' => $otherTenant->id,
            'product_plan_id' => $plan->id,
            'domain' => 'other.example.cn',
            'expires_at' => now()->addMonth()->toIso8601String(),
        ]);

        $order = $this->order($tenant, $customer, 'PORTAL-OWNED-ORDER');
        $this->order($otherTenant, $otherCustomer, 'PORTAL-OTHER-ORDER');

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'portal-secure@example.com',
            'password' => 'password123',
        ])
            ->assertOk()
            ->assertJsonStructure(['data' => ['token', 'user']])
            ->json('data');

        $token = $login['token'];

        $this->withToken($token)
            ->getJson('/api/v1/portal/me')
            ->assertOk()
            ->assertJsonPath('data.user.email', 'portal-secure@example.com');

        $this->withToken($token)
            ->getJson('/api/v1/portal/licenses')
            ->assertOk()
            ->assertJsonFragment(['domain' => 'owned.example.cn'])
            ->assertJsonFragment(['license_key' => $license['license_key']])
            ->assertJsonMissing(['domain' => 'other.example.cn'])
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'per_page', 'total', 'last_page']]);

        $this->withToken($token)
            ->getJson('/api/v1/portal/orders')
            ->assertOk()
            ->assertJsonFragment(['order_no' => $order->order_no])
            ->assertJsonMissing(['order_no' => 'PORTAL-OTHER-ORDER'])
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'per_page', 'total', 'last_page']]);
    }

    public function test_customer_cannot_access_admin_api_and_guest_gets_json_unauthorized(): void
    {
        $this->getJson('/api/v1/portal/me')
            ->assertUnauthorized();

        [$customer] = $this->customerTenant('portal-denied@example.com', 'portal-denied');
        $token = $customer->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/admin/stats')
            ->assertForbidden();
    }

    public function test_smoke_test_command_executes_with_portal_checks(): void
    {
        Artisan::call('app:create-demo-users', [
            '--admin-email' => 'admin@example.com',
            '--admin-password' => 'AdminPass123!',
            '--customer-email' => 'customer@example.com',
            '--customer-password' => 'CustomerPass123!',
        ]);

        $exitCode = Artisan::call('app:smoke-test');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode, $output);
        $this->assertStringContainsString('[OK] customer portal api accessible', $output);
        $this->assertStringContainsString('[OK] customer license api is isolated', $output);
        $this->assertStringContainsString('[OK] customer order api is isolated', $output);
        $this->assertStringContainsString('[OK] admin api accessible', $output);
        $this->assertStringContainsString('[OK] console build exists', $output);
    }

    private function customerTenant(string $email, string $slug): array
    {
        $customer = User::create([
            'name' => $email,
            'email' => $email,
            'password' => 'password123',
            'status' => 'active',
            'is_admin' => false,
        ]);

        $tenant = Tenant::create([
            'uuid' => (string) Str::uuid(),
            'name' => $slug,
            'slug' => $slug,
            'contact_name' => $email,
            'contact_email' => $email,
            'status' => 'active',
            'plan_code' => 'free',
        ]);

        $tenant->users()->attach($customer->id, [
            'role' => 'owner',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        return [$customer->fresh('tenants'), $tenant];
    }

    private function plan(): ProductPlan
    {
        return ProductPlan::create([
            'name' => 'Portal Secure Plan',
            'code' => 'portal_secure_plan',
            'price_cents' => 10000,
            'status' => 'active',
        ]);
    }

    private function order(Tenant $tenant, User $customer, string $orderNo): Order
    {
        return Order::create([
            'tenant_id' => $tenant->id,
            'user_id' => $customer->id,
            'order_no' => $orderNo,
            'status' => 'pending',
            'subtotal_cents' => 10000,
            'discount_cents' => 0,
            'total_cents' => 10000,
            'currency' => 'CNY',
        ]);
    }
}
