<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plugin_download_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plugin_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plugin_release_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plugin_package_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('plugin_download_token_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 32)->default('authorized')->index();
            $table->json('metadata')->nullable();
            $table->timestamp('downloaded_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin_download_records');
    }
};
