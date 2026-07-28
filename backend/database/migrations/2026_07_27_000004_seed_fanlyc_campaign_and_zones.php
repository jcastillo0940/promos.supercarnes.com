<?php

use App\Models\Branch;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $zones = [
            ['code' => 'azuero', 'name' => 'Azuero', 'sort_order' => 1, 'branches' => ['LAS_TABLAS', 'CHITRE']],
            ['code' => 'santiago', 'name' => 'Santiago', 'sort_order' => 2, 'branches' => ['CALLE10_SGO', 'CENTRAL_SGO', 'MERCADO_SGO', 'PALERMO_SGO']],
            ['code' => 'panama', 'name' => 'Panamá', 'sort_order' => 3, 'branches' => []],
        ];

        foreach ($zones as $zone) {
            $zoneId = DB::table('fanlyc_zones')->where('code', $zone['code'])->value('id');

            if (! $zoneId) {
                $zoneId = DB::table('fanlyc_zones')->insertGetId([
                    'code' => $zone['code'],
                    'name' => $zone['name'],
                    'is_active' => 1,
                    'sort_order' => $zone['sort_order'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($zone['branches'] as $branchCode) {
                $branchId = Branch::query()->where('code', $branchCode)->value('id');

                if (! $branchId) {
                    continue;
                }

                $exists = DB::table('fanlyc_zone_branches')
                    ->where('fanlyc_zone_id', $zoneId)
                    ->where('branch_id', $branchId)
                    ->exists();

                if (! $exists) {
                    DB::table('fanlyc_zone_branches')->insert([
                        'fanlyc_zone_id' => $zoneId,
                        'branch_id' => $branchId,
                        'is_active' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        if (Schema::hasTable('campaigns') && ! DB::table('campaigns')->where('slug', 'fanlyc')->exists()) {
            DB::table('campaigns')->insert([
                'name' => 'Fanlyc',
                'slug' => 'fanlyc',
                'description' => 'Registra tu factura, valida el producto y canjea tu QR por un tiket en el evento de tu zona.',
                'status' => 'active',
                'participation_mode' => 'external_module',
                // is_listed=0: Fanlyc is a standalone Blade module reached only via the direct /fanlyc link.
                // Listing it would let the SPA catalog "open" it via client-side pushState instead of a real
                // navigation, rendering the generic invoice-form UI for an unrecognized participation_mode.
                'is_listed' => 0,
                'sort_order' => 0,
                'starts_at' => now(),
                'ends_at' => now()->addMonths(3),
                'entry_threshold_amount' => 0,
                'entry_requires_approval' => 0,
                'invoice_min_amount_for_shot' => 0,
                'amount_per_point' => 0,
                'points_per_block' => 1,
                'daily_max_points' => 0,
                'daily_max_invoices' => 0,
                'coupon_ttl_hours' => 0,
                'games_enabled' => 0,
                'major_prizes_enabled' => 0,
                'invoice_scan_enabled' => 0,
                'redemption_enabled' => 0,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('campaigns')->where('slug', 'fanlyc')->delete();
        DB::table('fanlyc_zone_branches')->delete();
        DB::table('fanlyc_zones')->delete();
    }
};
