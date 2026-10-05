<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operator_promo_permissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('operator_id')->constrained('users')->cascadeOnDelete();
            $table->string('promo_key', 60);
            $table->json('permissions');
            $table->timestamps();

            $table->unique(['operator_id', 'promo_key']);
            $table->index('operator_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operator_promo_permissions');
    }
};
