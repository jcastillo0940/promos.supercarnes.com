<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('campaigns')) {
            return;
        }

        DB::table('campaigns')
            ->where(function ($query): void {
                $query->where('slug', 'polla-mundialista-2026')
                    ->orWhere('slug', 'dia-del-padre')
                    ->orWhere('name', 'Dia del Padre')
                    ->orWhere('name', 'Día del Padre');
            })
            ->update([
                'status' => 'paused',
                'is_listed' => false,
                'updated_at' => now(),
            ]);

        DB::table('campaigns')
            ->where('slug', 'del-sueno-al-puesto')
            ->update([
                'card_image_url' => '/del-sueno-al-puesto-steps.png',
                'hero_image_url' => '/del-sueno-al-puesto-hero.png',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // No revertimos automaticamente para evitar reactivar promos cerradas en produccion.
    }
};
