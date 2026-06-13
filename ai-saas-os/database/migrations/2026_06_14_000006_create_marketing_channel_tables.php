<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type', 32)->default('affiliate')->index();
            $table->string('status', 32)->default('active')->index();
            $table->unsignedInteger('commission_rate_basis_points')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('promotion_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_channel_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('destination_url', 2048);
            $table->string('status', 32)->default('active')->index();
            $table->unsignedBigInteger('click_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('promotion_attributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('marketing_channel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('promotion_link_id')->constrained()->cascadeOnDelete();
            $table->string('status', 32)->default('active')->index();
            $table->json('metadata')->nullable();
            $table->timestamp('attributed_at')->index();
            $table->timestamps();

            $table->unique(['tenant_id', 'promotion_link_id', 'status']);
        });

        Schema::create('commission_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('marketing_channel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('promotion_attribution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('base_amount_cents');
            $table->unsignedInteger('commission_rate_basis_points');
            $table->unsignedBigInteger('commission_amount_cents');
            $table->string('currency', 8)->default('CNY');
            $table->string('status', 32)->default('pending')->index();
            $table->json('metadata')->nullable();
            $table->timestamp('calculated_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_records');
        Schema::dropIfExists('promotion_attributions');
        Schema::dropIfExists('promotion_links');
        Schema::dropIfExists('marketing_channels');
    }
};
