<?php

namespace Tests\Feature\Api;

use App\Models\ProductFactoryDraft;
use App\Models\ProductFactoryLaunchChecklist;
use App\Models\ProductFactoryTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ProductFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_factory_command_generates_draft_only_records(): void
    {
        $this->artisan('product-factory:generate-drafts')
            ->expectsOutput('[OK] product factory templates generated: 5')
            ->expectsOutput('[OK] product factory drafts generated: 6')
            ->expectsOutput('[OK] no external website or automatic sale created')
            ->assertExitCode(0);

        $this->assertSame(5, ProductFactoryTemplate::count());
        $this->assertSame(6, ProductFactoryDraft::count());
        $this->assertSame(1, ProductFactoryLaunchChecklist::count());
        $this->assertTrue(ProductFactoryTemplate::firstOrFail()->simulation_mode);
        $this->assertTrue(ProductFactoryDraft::firstOrFail()->requires_approval);
        $this->assertTrue(ProductFactoryLaunchChecklist::firstOrFail()->simulation_mode);
    }

    public function test_admin_can_view_product_factory_resources(): void
    {
        $this->artisan('product-factory:generate-drafts')->assertExitCode(0);

        $admin = User::create([
            'name' => 'Product Factory Admin',
            'email' => 'product-factory-admin@example.com',
            'password' => 'password123',
            'status' => 'active',
            'is_admin' => true,
        ]);
        $headers = $this->bearerHeaders($admin->createToken('admin')->plainTextToken);

        $this->getJson('/api/v1/admin/product-factory/dashboard', $headers)
            ->assertOk()
            ->assertJsonPath('data.simulation_mode', true)
            ->assertJsonPath('data.templates_count', 5)
            ->assertJsonPath('data.drafts_count', 6);

        $this->getJson('/api/v1/admin/product-factory/product-templates', $headers)
            ->assertOk()
            ->assertJsonFragment(['type' => 'product']);

        $this->getJson('/api/v1/admin/product-factory/plugin-templates', $headers)
            ->assertOk()
            ->assertJsonFragment(['type' => 'plugin']);

        $this->getJson('/api/v1/admin/product-factory/landing-page-templates', $headers)
            ->assertOk()
            ->assertJsonFragment(['type' => 'landing_page']);

        $this->getJson('/api/v1/admin/product-factory/package-templates', $headers)
            ->assertOk()
            ->assertJsonFragment(['type' => 'pricing_package'])
            ->assertJsonFragment(['type' => 'license_package']);

        $this->getJson('/api/v1/admin/product-factory/launch-checklists', $headers)
            ->assertOk()
            ->assertJsonFragment(['title' => '产品发布清单草案'])
            ->assertJsonFragment(['requires_approval' => true]);

        $this->flushHeaders();
        Auth::forgetGuards();
    }

    public function test_customer_cannot_access_product_factory_admin_api(): void
    {
        $customer = User::create([
            'name' => 'Product Factory Customer',
            'email' => 'product-factory-customer@example.com',
            'password' => 'password123',
            'status' => 'active',
            'is_admin' => false,
        ]);

        $this->getJson('/api/v1/admin/product-factory/dashboard')
            ->assertUnauthorized();

        $this->getJson(
            '/api/v1/admin/product-factory/dashboard',
            $this->bearerHeaders($customer->createToken('customer')->plainTextToken)
        )->assertForbidden();
    }

    private function bearerHeaders(string $token): array
    {
        return [
            'Authorization' => 'Bearer '.$token,
        ];
    }
}
