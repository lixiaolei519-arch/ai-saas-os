<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->string('plan_code', 64)->default('free')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tenant_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 64)->default('member')->index();
            $table->string('status', 32)->default('active')->index();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id']);
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('scope', 32)->default('tenant')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('module', 64)->index();
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['permission_id', 'role_id']);
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'role_id', 'user_id']);
        });

        Schema::create('product_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type', 32)->default('subscription')->index();
            $table->string('status', 32)->default('active')->index();
            $table->string('billing_cycle', 32)->default('month');
            $table->unsignedBigInteger('price_cents')->default(0);
            $table->string('currency', 8)->default('CNY');
            $table->json('features')->nullable();
            $table->json('limits')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('license_key_hash', 128)->unique();
            $table->text('signed_payload')->nullable();
            $table->string('domain')->nullable()->index();
            $table->string('domain_hash', 128)->nullable()->index();
            $table->string('status', 32)->default('active')->index();
            $table->unsignedInteger('max_activations')->default(1);
            $table->unsignedInteger('activation_count')->default(0);
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('last_verified_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('license_activations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained()->cascadeOnDelete();
            $table->string('fingerprint_hash', 128)->index();
            $table->string('domain')->nullable()->index();
            $table->string('ip_address', 45)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['license_id', 'fingerprint_hash']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('order_no')->unique();
            $table->string('status', 32)->default('pending')->index();
            $table->unsignedBigInteger('subtotal_cents')->default(0);
            $table->unsignedBigInteger('discount_cents')->default(0);
            $table->unsignedBigInteger('total_cents')->default(0);
            $table->string('currency', 8)->default('CNY');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item_type', 64)->default('plan')->index();
            $table->string('sku')->nullable()->index();
            $table->string('description');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('unit_amount_cents')->default(0);
            $table->unsignedBigInteger('total_amount_cents')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 32)->index();
            $table->string('out_trade_no')->unique();
            $table->string('provider_trade_no')->nullable()->index();
            $table->string('status', 32)->default('pending')->index();
            $table->unsignedBigInteger('amount_cents')->default(0);
            $table->string('currency', 8)->default('CNY');
            $table->timestamp('paid_at')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('callback_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_callbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 32)->index();
            $table->string('out_trade_no')->nullable()->index();
            $table->boolean('signature_valid')->default(false)->index();
            $table->string('status', 32)->default('received')->index();
            $table->json('headers')->nullable();
            $table->json('payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('balance_amount', 18, 6)->default(0);
            $table->decimal('frozen_amount', 18, 6)->default(0);
            $table->unsignedBigInteger('balance_tokens')->default(0);
            $table->string('currency', 8)->default('CNY');
            $table->timestamps();
        });

        Schema::create('balance_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_account_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32)->index();
            $table->decimal('amount_delta', 18, 6)->default(0);
            $table->bigInteger('token_delta')->default(0);
            $table->decimal('balance_after', 18, 6)->default(0);
            $table->unsignedBigInteger('tokens_after')->default(0);
            $table->nullableMorphs('related');
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('ai_usage_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('request_id')->unique();
            $table->string('provider', 64)->index();
            $table->string('model', 128)->index();
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);
            $table->decimal('unit_price_per_1k', 18, 6)->default(0);
            $table->decimal('total_cost_amount', 18, 6)->default(0);
            $table->string('status', 32)->default('charged')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('plugins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('developer_tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category', 64)->default('general')->index();
            $table->string('status', 32)->default('draft')->index();
            $table->unsignedBigInteger('price_cents')->default(0);
            $table->string('currency', 8)->default('CNY');
            $table->text('description')->nullable();
            $table->json('manifest')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('plugin_releases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plugin_id')->constrained()->cascadeOnDelete();
            $table->string('version', 64);
            $table->string('status', 32)->default('draft')->index();
            $table->string('package_path');
            $table->string('checksum', 128)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['plugin_id', 'version']);
        });

        Schema::create('plugin_installations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plugin_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plugin_release_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 32)->default('installed')->index();
            $table->json('config')->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'plugin_id']);
        });

        Schema::create('workflow_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('trigger_event', 128)->index();
            $table->string('status', 32)->default('draft')->index();
            $table->json('nodes')->nullable();
            $table->json('edges')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('workflow_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workflow_definition_id')->constrained()->cascadeOnDelete();
            $table->string('status', 32)->default('queued')->index();
            $table->string('trigger_event', 128)->index();
            $table->json('payload')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('workflow_run_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_run_id')->constrained()->cascadeOnDelete();
            $table->string('node_key', 128)->index();
            $table->string('status', 32)->default('pending')->index();
            $table->json('input')->nullable();
            $table->json('output')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('risk_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('scope', 64)->default('global')->index();
            $table->string('severity', 32)->default('medium')->index();
            $table->string('status', 32)->default('active')->index();
            $table->string('action', 32)->default('review')->index();
            $table->json('conditions')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('risk_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('license_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type', 128)->index();
            $table->string('severity', 32)->default('medium')->index();
            $table->nullableMorphs('subject');
            $table->string('ip_address', 45)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->string('fingerprint_hash', 128)->nullable()->index();
            $table->string('decision', 32)->default('allow')->index();
            $table->json('context')->nullable();
            $table->timestamp('occurred_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('risk_blacklist_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('value_type', 32)->index();
            $table->string('value_hash', 128)->index();
            $table->string('value')->nullable();
            $table->string('reason')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['tenant_id', 'value_type', 'value_hash']);
        });

        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 128)->index();
            $table->nullableMorphs('subject');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
        Schema::dropIfExists('risk_blacklist_entries');
        Schema::dropIfExists('risk_events');
        Schema::dropIfExists('risk_rules');
        Schema::dropIfExists('workflow_run_steps');
        Schema::dropIfExists('workflow_runs');
        Schema::dropIfExists('workflow_definitions');
        Schema::dropIfExists('plugin_installations');
        Schema::dropIfExists('plugin_releases');
        Schema::dropIfExists('plugins');
        Schema::dropIfExists('ai_usage_records');
        Schema::dropIfExists('balance_transactions');
        Schema::dropIfExists('ai_accounts');
        Schema::dropIfExists('payment_callbacks');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('license_activations');
        Schema::dropIfExists('licenses');
        Schema::dropIfExists('product_plans');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('tenants');
    }
};
