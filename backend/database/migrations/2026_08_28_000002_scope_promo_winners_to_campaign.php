<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promo_winners', function (Blueprint $table): void {
            $table->dropUnique('promo_winners_phase_id_user_id_unique');
            $table->unsignedBigInteger('phase_id')->nullable()->change();
            $table->unique(['campaign_id', 'user_id'], 'promo_winners_campaign_user_unique');
        });
    }

    public function down(): void
    {
        Schema::table('promo_winners', function (Blueprint $table): void {
            $table->dropUnique('promo_winners_campaign_user_unique');
            $table->unique(['phase_id', 'user_id'], 'promo_winners_phase_id_user_id_unique');
        });
    }
};
