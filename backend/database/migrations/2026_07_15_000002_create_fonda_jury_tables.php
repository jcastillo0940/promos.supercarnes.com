<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fonda_jury_assignments')) {
            Schema::create('fonda_jury_assignments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
                $table->foreignId('registration_id')->constrained('fonda_registrations')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('status', 30)->default('assigned');
                $table->timestamp('assigned_at')->useCurrent();
                $table->timestamp('conflicted_at')->nullable();
                $table->timestamps();
                $table->unique(['registration_id', 'user_id']);
                $table->index(['campaign_id', 'user_id', 'status']);
            });
        }

        if (! Schema::hasTable('fonda_jury_evaluations')) {
            Schema::create('fonda_jury_evaluations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
                $table->foreignId('registration_id')->constrained('fonda_registrations')->cascadeOnDelete();
                $table->foreignId('assignment_id')->constrained('fonda_jury_assignments')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->decimal('sabor', 4, 1)->nullable();
                $table->decimal('tecnica', 4, 1)->nullable();
                $table->decimal('presentacion', 4, 1)->nullable();
                $table->decimal('originalidad', 4, 1)->nullable();
                $table->decimal('uso_producto', 4, 1)->nullable();
                $table->decimal('final_score', 6, 2)->nullable();
                $table->text('commentary')->nullable();
                $table->string('status', 30)->default('draft');
                $table->timestamp('started_at')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();
                $table->unique(['assignment_id']);
                $table->index(['campaign_id', 'registration_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fonda_jury_evaluations');
        Schema::dropIfExists('fonda_jury_assignments');
    }
};
