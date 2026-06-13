<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_factory_templates', function (Blueprint $table) {
            $table->id();
            $table->string('type', 64)->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('schema')->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->boolean('requires_approval')->default(true)->index();
            $table->boolean('simulation_mode')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_factory_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_factory_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 64)->index();
            $table->string('title');
            $table->longText('content')->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->boolean('requires_approval')->default(true)->index();
            $table->boolean('simulation_mode')->default(true)->index();
            $table->timestamp('generated_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_factory_launch_checklists', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('status', 32)->default('draft')->index();
            $table->json('items')->nullable();
            $table->boolean('requires_approval')->default(true)->index();
            $table->boolean('simulation_mode')->default(true)->index();
            $table->timestamp('generated_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_factory_launch_checklists');
        Schema::dropIfExists('product_factory_drafts');
        Schema::dropIfExists('product_factory_templates');
    }
};
