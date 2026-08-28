<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $campaigns = DB::table('campaigns')
            ->whereIn('slug', ['malta-vigor', 'malta-vigor-honor'])
            ->where('participation_mode', 'product_ranking')
            ->get();

        foreach ($campaigns as $campaign) {
            $rules = json_decode((string) $campaign->rules, true) ?: [];
            $rules['participant_identifier'] = 'cedula';

            DB::table('campaigns')
                ->where('id', $campaign->id)
                ->update(['rules' => json_encode($rules, JSON_THROW_ON_ERROR), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // This data correction is intentionally not reversed: the prior value
        // is not available reliably, and restoring a guessed value could
        // reintroduce the wrong participant identifier.
    }
};
