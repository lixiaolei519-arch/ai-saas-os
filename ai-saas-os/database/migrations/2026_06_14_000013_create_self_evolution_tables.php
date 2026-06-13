<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('self_evolution_scans', function (Blueprint $table) {
            $table->id();
            $table->string('version', 32)->nullable()->index();
            $table->string('status', 32)->default('draft')->index();
            $table->text('summary')->nullable();
            $table->json('findings')->nullable();
            $table->json('metrics')->nullable();
            $table->boolean('simulation_mode')->default(true)->index();
            $table->timestamp('generated_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('self_evolution_scores', function (Blueprint $table) {
            $table->id();
            $table->string('version', 32)->nullable()->index();
            $table->string('status', 32)->default('draft')->index();
            $table->unsignedSmallInteger('overall_score')->default(0)->index();
            $table->json('dimensions')->nullable();
            $table->json('recommendations')->nullable();
            $table->boolean('simulation_mode')->default(true)->index();
            $table->timestamp('generated_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('self_evolution_plans', function (Blueprint $table) {
            $table->id();
            $table->string('target_version', 32)->index();
            $table->string('title');
            $table->string('status', 32)->default('draft')->index();
            $table->json('tasks')->nullable();
            $table->json('version_plan')->nullable();
            $table->boolean('requires_approval')->default(true)->index();
            $table->boolean('simulation_mode')->default(true)->index();
            $table->timestamp('generated_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('self_evolution_release_reviews', function (Blueprint $table) {
            $table->id();
            $table->string('version', 32)->index();
            $table->string('status', 32)->default('draft')->index();
            $table->string('decision', 32)->default('needs_review')->index();
            $table->json('checklist')->nullable();
            $table->json('rollback_suggestions')->nullable();
            $table->json('deployment_suggestions')->nullable();
            $table->json('testing_suggestions')->nullable();
            $table->json('security_suggestions')->nullable();
            $table->json('business_suggestions')->nullable();
            $table->boolean('requires_approval')->default(true)->index();
            $table->boolean('simulation_mode')->default(true)->index();
            $table->timestamp('generated_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('self_evolution_suggestions', function (Blueprint $table) {
            $table->id();
            $table->string('version', 32)->nullable()->index();
            $table->string('category', 64)->index();
            $table->string('priority', 32)->default('medium')->index();
            $table->string('status', 32)->default('draft')->index();
            $table->string('title');
            $table->text('body')->nullable();
            $table->boolean('requires_approval')->default(true)->index();
            $table->boolean('simulation_mode')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('self_evolution_suggestions');
        Schema::dropIfExists('self_evolution_release_reviews');
        Schema::dropIfExists('self_evolution_plans');
        Schema::dropIfExists('self_evolution_scores');
        Schema::dropIfExists('self_evolution_scans');
    }
};
