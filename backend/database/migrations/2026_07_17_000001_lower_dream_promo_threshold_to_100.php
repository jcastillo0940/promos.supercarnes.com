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
                'entry_threshold_amount' => 100,
                'description' => 'Acumula $100 o mas en facturas de Super Carnes y participa por una de las 35 toldas gratis para tu emprendimiento.',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('campaigns')
            ->where('slug', 'del-sueno-al-puesto')
            ->update([
                'entry_threshold_amount' => 300,
                'description' => 'Acumula $300 o mas en facturas de Super Carnes y participa por una de las 35 toldas gratis para tu emprendimiento.',
                'updated_at' => now(),
            ]);
    }
};
