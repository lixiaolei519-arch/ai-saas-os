<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('autonomous_operation_drafts', function (Blueprint $table) {
            $table->id();
            $table->string('type', 64)->index();
            $table->string('title');
            $table->longText('content')->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->string('channel', 64)->nullable()->index();
            $table->string('target_audience', 128)->nullable();
            $table->boolean('requires_approval')->default(true)->index();
            $table->boolean('simulation_mode')->default(true)->index();
            $table->timestamp('generated_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('autonomous_operation_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('type', 64)->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('priority', 32)->default('medium')->index();
            $table->string('status', 32)->default('draft')->index();
            $table->boolean('requires_approval')->default(true)->index();
            $table->boolean('simulation_mode')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('autonomous_operation_tasks');
        Schema::dropIfExists('autonomous_operation_drafts');
    }
};
