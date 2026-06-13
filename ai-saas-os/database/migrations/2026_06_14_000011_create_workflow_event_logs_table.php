<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_event_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('event_name', 128)->index();
            $table->string('status', 32)->default('received')->index();
            $table->unsignedInteger('matched_workflows_count')->default(0);
            $table->json('payload')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_event_logs');
    }
};
