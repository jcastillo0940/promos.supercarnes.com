<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fonda_registrations')) {
            Schema::create('fonda_registrations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
                $table->string('code', 30)->unique();
                $table->string('status', 30)->default('pending_review');
                $table->string('full_name', 150);
                $table->string('cedula', 40);
                $table->string('email', 150);
                $table->string('phone', 30)->nullable();
                $table->string('fonda_name', 150);
                $table->string('fonda_location', 150)->nullable();
                $table->string('dish_name', 150);
                $table->text('description');
                $table->text('consent_terms')->nullable();
                $table->json('meta')->nullable();
                $table->timestamp('checked_in_at')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
                $table->index(['campaign_id', 'status']);
                $table->index(['campaign_id', 'cedula']);
            });
        }

        if (Schema::hasTable('campaigns') && ! DB::table('campaigns')->where('slug', 'fonda-challenge')->exists()) {
            DB::table('campaigns')->insert([
                'name' => 'Fonda Challenge 2026',
                'slug' => 'fonda-challenge',
                'description' => 'Modulo de registro, revision, evaluacion y ranking de fondas.',
                'status' => 'active',
                'participation_mode' => 'threshold_form',
                'is_listed' => 1,
                'sort_order' => 0,
                'starts_at' => now(),
                'ends_at' => now()->addMonths(3),
                'entry_threshold_amount' => 0,
                'entry_requires_approval' => 1,
                'invoice_min_amount_for_shot' => 0,
                'amount_per_point' => 0,
                'points_per_block' => 1,
                'daily_max_points' => 0,
                'daily_max_invoices' => 0,
                'coupon_ttl_hours' => 72,
                'games_enabled' => 0,
                'major_prizes_enabled' => 0,
                'invoice_scan_enabled' => 0,
                'redemption_enabled' => 0,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fonda_registrations');
    }
};
