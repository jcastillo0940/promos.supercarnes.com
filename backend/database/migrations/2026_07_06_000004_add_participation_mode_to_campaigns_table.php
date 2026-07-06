<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table): void {
            if (! Schema::hasColumn('campaigns', 'participation_mode')) {
                $table->string('participation_mode', 40)->default('points')->after('status');
            }
        });

        $campaigns = DB::table('campaigns')->orderBy('id')->get();

        foreach ($campaigns as $campaign) {
            $mode = $campaign->participation_mode ?? 'points';
            $update = [
                'participation_mode' => $mode,
                'entry_requires_approval' => (bool) ($campaign->entry_requires_approval ?? false),
                'is_listed' => (bool) ($campaign->is_listed ?? true),
                'updated_at' => now(),
            ];

            if ((string) $campaign->slug === 'del-sueno-al-puesto') {
                $mode = 'threshold_form';
                $update['participation_mode'] = $mode;
                $update['entry_threshold_amount'] = 300;
                $update['entry_requires_approval'] = false;
            } elseif ($campaign->entry_threshold_amount !== null) {
                $update['entry_threshold_amount'] = $campaign->entry_threshold_amount;
            }

            DB::table('campaigns')
                ->where('id', $campaign->id)
                ->update($update);
        }

        if (! DB::table('campaigns')->where('slug', 'del-sueno-al-puesto')->exists()) {
            DB::table('campaigns')->insert([
                'name' => 'Del sueño al puesto',
                'slug' => 'del-sueno-al-puesto',
                'description' => 'Promoción para emprendedores que acumulan $300 o más en facturas para activar su participación.',
                'status' => 'active',
                'participation_mode' => 'threshold_form',
                'is_listed' => true,
                'hero_image_url' => '/auth-slogan.webp',
                'card_image_url' => '/auth-slogan.webp',
                'sort_order' => 50,
                'starts_at' => now(),
                'ends_at' => now()->addMonths(3),
                'invoice_min_amount_for_shot' => 25,
                'amount_per_point' => 25,
                'entry_threshold_amount' => 300,
                'entry_requires_approval' => false,
                'points_per_block' => 1,
                'daily_max_points' => 1000,
                'daily_max_invoices' => 100,
                'coupon_ttl_hours' => 72,
                'games_enabled' => false,
                'major_prizes_enabled' => false,
                'invoice_scan_enabled' => true,
                'redemption_enabled' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table): void {
            if (Schema::hasColumn('campaigns', 'participation_mode')) {
                $table->dropColumn('participation_mode');
            }
        });
    }
};
