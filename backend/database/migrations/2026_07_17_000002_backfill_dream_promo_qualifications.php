<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('campaigns') || ! Schema::hasTable('registered_invoices')) {
            return;
        }

        $campaign = DB::table('campaigns')->where('slug', 'del-sueno-al-puesto')->first();

        if (! $campaign) {
            return;
        }

        $threshold = (float) ($campaign->entry_threshold_amount ?? 0);
        $threshold = $threshold > 0 ? $threshold : 100.0;

        $userIds = DB::table('registered_invoices')
            ->where('campaign_id', $campaign->id)
            ->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('SUM(purchase_amount) >= ?', [$threshold])
            ->pluck('user_id');

        if ($userIds->isEmpty()) {
            return;
        }

        DB::table('users')
            ->whereIn('id', $userIds)
            ->whereNull('dream_promo_qualified_at')
            ->update(['dream_promo_qualified_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Data backfill: not reversible without losing track of organically-qualified users.
    }
};
