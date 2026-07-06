<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $campaigns = DB::table('campaigns')->orderBy('id')->get();

        foreach ($campaigns as $campaign) {
            if (! empty($campaign->slug)) {
                continue;
            }

            $slug = match ($campaign->id) {
                1 => 'polla-mundialista-2026',
                default => \Illuminate\Support\Str::slug((string) $campaign->name),
            };

            DB::table('campaigns')->where('id', $campaign->id)->update([
                'slug' => $slug,
                'is_listed' => true,
                'sort_order' => $campaign->sort_order ?? 0,
                'updated_at' => now(),
            ]);
        }

        $dream = DB::table('campaigns')->where('slug', 'del-sueno-al-puesto')->first();
        if (! $dream) {
            DB::table('campaigns')->insert([
                'name' => 'Del sueño al puesto',
                'slug' => 'del-sueno-al-puesto',
                'description' => 'Promoción para emprendedores que acumulan $300 o más en facturas para participar por una tolda.',
                'status' => 'active',
                'is_listed' => true,
                'hero_image_url' => '/auth-slogan.webp',
                'card_image_url' => '/auth-slogan.webp',
                'sort_order' => 50,
                'entry_threshold_amount' => 300,
                'entry_requires_approval' => true,
                'invoice_scan_enabled' => true,
                'starts_at' => now(),
                'ends_at' => now()->addMonths(3),
                'invoice_min_amount_for_shot' => 25,
                'amount_per_point' => 25,
                'points_per_block' => 1,
                'daily_max_points' => 1000,
                'daily_max_invoices' => 100,
                'coupon_ttl_hours' => 72,
                'games_enabled' => false,
                'major_prizes_enabled' => false,
                'redemption_enabled' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Intencionalmente no revertimos los slugs para no perder referencias publicas.
    }
};
