<?php

namespace Database\Seeders;

use App\Models\AiAccount;
use App\Models\MarketingChannel;
use App\Models\ProductPlan;
use App\Models\PromotionLink;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate([
            'email' => env('ADMIN_DEMO_EMAIL', 'admin@example.com'),
        ], [
            'name' => 'Demo Admin',
            'password' => Hash::make(env('ADMIN_DEMO_PASSWORD', 'password123')),
            'status' => 'active',
            'is_admin' => true,
        ]);

        $customer = User::updateOrCreate([
            'email' => env('CUSTOMER_DEMO_EMAIL', 'customer@example.com'),
        ], [
            'name' => 'Demo Customer',
            'password' => Hash::make(env('CUSTOMER_DEMO_PASSWORD', 'password123')),
            'status' => 'active',
            'is_admin' => false,
        ]);

        $tenant = Tenant::updateOrCreate([
            'slug' => 'demo-customer',
        ], [
            'uuid' => (string) Str::uuid(),
            'name' => 'Demo Customer Tenant',
            'contact_name' => $customer->name,
            'contact_email' => $customer->email,
            'status' => 'active',
            'plan_code' => 'demo_monthly',
            'metadata' => ['seeded' => true],
        ]);

        $tenant->users()->syncWithoutDetaching([
            $customer->id => [
                'role' => 'owner',
                'status' => 'active',
                'joined_at' => now(),
            ],
        ]);

        AiAccount::updateOrCreate([
            'tenant_id' => $tenant->id,
        ], [
            'balance_amount' => 100,
            'balance_tokens' => 100000,
            'currency' => 'CNY',
        ]);

        $plan = ProductPlan::updateOrCreate([
            'code' => 'demo_monthly',
        ], [
            'name' => 'Demo Monthly',
            'type' => 'subscription',
            'status' => 'active',
            'billing_cycle' => 'month',
            'price_cents' => 9900,
            'currency' => 'CNY',
            'features' => ['license', 'customer_portal', 'marketing_channel'],
            'limits' => ['ai_tokens' => 100000],
        ]);

        $channel = MarketingChannel::updateOrCreate([
            'code' => 'demo-channel',
        ], [
            'tenant_id' => $tenant->id,
            'name' => 'Demo Channel',
            'type' => 'affiliate',
            'status' => 'active',
            'commission_rate_basis_points' => 1000,
            'metadata' => ['seeded' => true],
        ]);

        PromotionLink::updateOrCreate([
            'code' => 'DEMOREF',
        ], [
            'marketing_channel_id' => $channel->id,
            'destination_url' => env('APP_URL', 'http://localhost').'/register',
            'status' => 'active',
            'metadata' => [
                'seeded' => true,
                'product_plan_id' => $plan->id,
            ],
        ]);

        $admin->tokens()->delete();
        $customer->tokens()->delete();
    }
}
