<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plugin_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plugin_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plugin_release_id')->constrained()->cascadeOnDelete();
            $table->string('file_name');
            $table->string('storage_path');
            $table->string('checksum', 128);
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['plugin_release_id', 'checksum']);
        });

        Schema::create('plugin_download_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plugin_release_id')->constrained()->cascadeOnDelete();
            $table->foreignId('license_id')->nullable()->constrained()->nullOnDelete();
            $table->string('token_hash', 128)->unique();
            $table->string('status', 32)->default('active')->index();
            $table->timestamp('expires_at')->index();
            $table->timestamp('used_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin_download_tokens');
        Schema::dropIfExists('plugin_packages');
    }
};
