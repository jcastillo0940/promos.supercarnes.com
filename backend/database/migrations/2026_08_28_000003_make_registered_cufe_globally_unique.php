<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('registered_invoices')) {
            return;
        }

        $duplicates = DB::table('registered_invoices')
            ->select('cufe')
            ->whereNotNull('cufe')
            ->groupBy('cufe')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('cufe');

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException('No se puede crear unicidad global de CUFE; existen duplicados: '.$duplicates->implode(', '));
        }

        Schema::table('registered_invoices', function (Blueprint $table): void {
            $table->dropUnique('registered_invoices_campaign_cufe_unique');
            $table->unique('cufe', 'registered_invoices_cufe_unique');
        });
    }

    public function down(): void
    {
        Schema::table('registered_invoices', function (Blueprint $table): void {
            $table->dropUnique('registered_invoices_cufe_unique');
            $table->unique(['campaign_id', 'cufe'], 'registered_invoices_campaign_cufe_unique');
        });
    }
};
