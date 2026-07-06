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
    }

    public function down(): void
    {
        // Intencionalmente no revertimos los slugs para no perder referencias publicas.
    }
};
