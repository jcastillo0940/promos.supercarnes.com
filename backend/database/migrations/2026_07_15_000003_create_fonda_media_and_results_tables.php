<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fonda_media_sessions')) {
            Schema::create('fonda_media_sessions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
                $table->foreignId('registration_id')->constrained('fonda_registrations')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 30)->default('open');
                $table->timestamp('started_at')->useCurrent();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('fonda_media_files')) {
            Schema::create('fonda_media_files', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('session_id')->constrained('fonda_media_sessions')->cascadeOnDelete();
                $table->string('kind', 30)->default('original');
                $table->string('path', 255);
                $table->string('mime_type', 100)->nullable();
                $table->string('checksum', 100)->nullable();
                $table->string('label', 50)->nullable();
                $table->boolean('is_featured')->default(false);
                $table->timestamps();
                $table->index(['session_id', 'kind']);
            });
        }

        if (! Schema::hasTable('fonda_result_locks')) {
            Schema::create('fonda_result_locks', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
                $table->timestamp('frozen_at')->nullable();
                $table->timestamp('published_at')->nullable();
                $table->foreignId('frozen_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('freeze_reason')->nullable();
                $table->text('publish_reason')->nullable();
                $table->timestamps();
                $table->unique('campaign_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fonda_result_locks');
        Schema::dropIfExists('fonda_media_files');
        Schema::dropIfExists('fonda_media_sessions');
    }
};
