<?php

use App\Models\Branch;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->unsignedSmallInteger('store_number')->nullable()->unique()->after('code');
        });

        $storeNumbers = [
            'AGUADULCE' => 1,
            'CENTRAL_SGO' => 2,
            'PALERMO_SGO' => 3,
            'MERCADO_SGO' => 4,
            'CALLE10_SGO' => 5,
            'CANTO_LLANO' => 6,
            'LAS_TABLAS' => 7,
            'PENONOME' => 8,
            'VISTA_ALEGRE' => 9,
            'CHITRE' => 10,
            'LA_CHORRERA' => 11,
            'ALBROOK' => 12,
            'TRAPICHITO' => 15,
        ];

        foreach ($storeNumbers as $code => $number) {
            Branch::query()->where('code', $code)->update(['store_number' => $number]);
        }

        Branch::query()->firstOrCreate(['code' => 'COSTA_VERDE'], [
            'name' => 'Costa Verde',
            'store_number' => 13,
            'is_active' => true,
        ]);

        Branch::query()->firstOrCreate(['code' => 'MAX_SUPER_CARNES'], [
            'name' => 'Max Super Carnes',
            'store_number' => 14,
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        Branch::query()->whereIn('code', ['COSTA_VERDE', 'MAX_SUPER_CARNES'])->delete();

        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('store_number');
        });
    }
};
