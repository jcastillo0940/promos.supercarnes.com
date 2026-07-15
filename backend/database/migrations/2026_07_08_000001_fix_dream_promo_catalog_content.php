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
            ->where('slug', 'del-sueno-al-puesto')
            ->update([
                'description' => 'Acumula $300 o mas en facturas de Super Carnes y participa por una de las 35 toldas gratis para tu emprendimiento.',
                'card_image_url' => '/del-sueno-al-puesto-hero.png',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('campaigns')
            ->where('slug', 'del-sueno-al-puesto')
            ->update([
                'description' => null,
                'card_image_url' => '/del-sueno-al-puesto-steps.png',
                'updated_at' => now(),
            ]);
    }
};
