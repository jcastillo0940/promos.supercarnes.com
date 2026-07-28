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
                'status' => 'paused',
                'is_listed' => false,
                'invoice_scan_enabled' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // No revertimos automaticamente para evitar reactivar promos cerradas en produccion.
    }
};
