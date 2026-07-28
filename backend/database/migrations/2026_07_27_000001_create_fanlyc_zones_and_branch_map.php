<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fanlyc_zones')) {
            Schema::create('fanlyc_zones', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 20)->unique();
                $table->string('name', 80);
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('fanlyc_zone_branches')) {
            Schema::create('fanlyc_zone_branches', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('fanlyc_zone_id')->constrained('fanlyc_zones')->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['branch_id', 'fanlyc_zone_id']);
                $table->index(['fanlyc_zone_id', 'is_active']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fanlyc_zone_branches');
        Schema::dropIfExists('fanlyc_zones');
    }
};
