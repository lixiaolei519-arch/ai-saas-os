<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_company_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('category', 64)->default('operations')->index();
            $table->string('priority', 32)->default('medium')->index();
            $table->string('status', 32)->default('draft')->index();
            $table->boolean('requires_approval')->default(true)->index();
            $table->boolean('simulation_mode')->default(true)->index();
            $table->string('source', 64)->default('ai-company:plan')->index();
            $table->text('recommendation')->nullable();
            $table->text('codex_prompt')->nullable();
            $table->timestamp('due_at')->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ai_company_ideas', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('source', 64)->default('ai-company:scan')->index();
            $table->string('status', 32)->default('draft')->index();
            $table->unsignedSmallInteger('score')->default(50)->index();
            $table->boolean('simulation_mode')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ai_company_roadmaps', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('version', 32)->nullable()->index();
            $table->string('status', 32)->default('draft')->index();
            $table->text('summary')->nullable();
            $table->json('items')->nullable();
            $table->boolean('simulation_mode')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ai_company_release_plans', function (Blueprint $table) {
            $table->id();
            $table->string('version', 32)->index();
            $table->string('title');
            $table->string('status', 32)->default('draft')->index();
            $table->json('scope')->nullable();
            $table->json('quality_gate')->nullable();
            $table->text('deployment_notes')->nullable();
            $table->boolean('requires_approval')->default(true)->index();
            $table->boolean('simulation_mode')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ai_company_quality_reports', function (Blueprint $table) {
            $table->id();
            $table->string('version', 32)->nullable()->index();
            $table->string('status', 32)->default('draft')->index();
            $table->unsignedSmallInteger('score')->default(0)->index();
            $table->json('checks')->nullable();
            $table->json('gaps')->nullable();
            $table->json('recommendations')->nullable();
            $table->boolean('simulation_mode')->default(true)->index();
            $table->timestamp('generated_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_company_risk_reports', function (Blueprint $table) {
            $table->id();
            $table->string('version', 32)->nullable()->index();
            $table->string('status', 32)->default('draft')->index();
            $table->string('severity', 32)->default('medium')->index();
            $table->json('risks')->nullable();
            $table->json('mitigations')->nullable();
            $table->boolean('simulation_mode')->default(true)->index();
            $table->timestamp('generated_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_company_codex_prompts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('target_version', 32)->nullable()->index();
            $table->text('prompt');
            $table->string('status', 32)->default('draft')->index();
            $table->boolean('requires_approval')->default(true)->index();
            $table->boolean('simulation_mode')->default(true)->index();
            $table->string('source_type', 64)->nullable()->index();
            $table->unsignedBigInteger('source_id')->nullable()->index();
            $table->timestamp('generated_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ai_company_daily_reports', function (Blueprint $table) {
            $table->id();
            $table->date('report_date')->unique();
            $table->string('status', 32)->default('draft')->index();
            $table->text('summary')->nullable();
            $table->json('product')->nullable();
            $table->json('technology')->nullable();
            $table->json('sales')->nullable();
            $table->json('risks')->nullable();
            $table->json('next_steps')->nullable();
            $table->boolean('simulation_mode')->default(true)->index();
            $table->timestamp('generated_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_company_daily_reports');
        Schema::dropIfExists('ai_company_codex_prompts');
        Schema::dropIfExists('ai_company_risk_reports');
        Schema::dropIfExists('ai_company_quality_reports');
        Schema::dropIfExists('ai_company_release_plans');
        Schema::dropIfExists('ai_company_roadmaps');
        Schema::dropIfExists('ai_company_ideas');
        Schema::dropIfExists('ai_company_tasks');
    }
};
