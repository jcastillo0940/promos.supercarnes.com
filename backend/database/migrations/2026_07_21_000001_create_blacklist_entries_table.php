<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('blacklist_entries')) {
            return;
        }

        Schema::create('blacklist_entries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('cedula', 40)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('full_name', 180)->nullable();
            $table->string('status', 20)->default('active');
            $table->text('reason');
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->unsignedBigInteger('removed_by_user_id')->nullable();
            $table->text('removal_note')->nullable();
            $table->timestamp('removed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'cedula'], 'idx_blacklist_status_cedula');
            $table->index(['status', 'phone'], 'idx_blacklist_status_phone');
            $table->index(['status', 'user_id'], 'idx_blacklist_status_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blacklist_entries');
    }
};
