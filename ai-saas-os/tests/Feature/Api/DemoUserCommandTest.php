<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_demo_admin_and_customer_users_that_can_login(): void
    {
        $exitCode = Artisan::call('app:create-demo-users', [
            '--admin-email' => 'deploy-admin@example.com',
            '--admin-password' => 'AdminPass123!',
            '--customer-email' => 'deploy-customer@example.com',
            '--customer-password' => 'CustomerPass123!',
        ]);

        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('admin email: deploy-admin@example.com', $output);
        $this->assertStringContainsString('admin password: AdminPass123!', $output);
        $this->assertStringContainsString('customer email: deploy-customer@example.com', $output);
        $this->assertStringContainsString('customer password: CustomerPass123!', $output);

        $admin = User::where('email', 'deploy-admin@example.com')->firstOrFail();
        $customer = User::where('email', 'deploy-customer@example.com')->firstOrFail();

        $this->assertTrue($admin->is_admin);
        $this->assertFalse($customer->is_admin);
        $this->assertSame('active', $admin->status);
        $this->assertSame('active', $customer->status);
        $this->assertTrue(Hash::check('AdminPass123!', $admin->password));
        $this->assertTrue(Hash::check('CustomerPass123!', $customer->password));

        $this->postJson('/api/v1/admin/auth/login', [
            'email' => 'deploy-admin@example.com',
            'password' => 'AdminPass123!',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.email', 'deploy-admin@example.com')
            ->assertJsonPath('data.user.is_admin', true)
            ->assertJsonStructure(['data' => ['token']]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'deploy-customer@example.com',
            'password' => 'CustomerPass123!',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.email', 'deploy-customer@example.com')
            ->assertJsonStructure(['data' => ['token']]);
    }
}
