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
            if (! Schema::hasColumn('campaigns', 'rules')) $table->json('rules')->nullable();
            if (! Schema::hasColumn('campaigns', 'terms_text')) $table->longText('terms_text')->nullable();
            if (! Schema::hasColumn('campaigns', 'terms_version')) $table->string('terms_version', 80)->nullable();
            if (! Schema::hasColumn('campaigns', 'terms_approved_at')) $table->timestamp('terms_approved_at')->nullable();
            if (! Schema::hasColumn('campaigns', 'delivery_location')) $table->string('delivery_location', 255)->nullable();
            if (! Schema::hasColumn('campaigns', 'delivery_deadline')) $table->dateTime('delivery_deadline')->nullable();
            if (! Schema::hasColumn('campaigns', 'delivery_requirements')) $table->string('delivery_requirements', 500)->nullable();
            if (! Schema::hasColumn('campaigns', 'ranking_frozen_at')) $table->timestamp('ranking_frozen_at')->nullable();
        });

        Schema::create('campaign_product_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->string('barcode', 80);
            $table->string('presentation', 80)->nullable();
            $table->string('product_name', 150)->default('Malta Vigor');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['campaign_id', 'barcode']);
        });

        Schema::table('registered_invoices', function (Blueprint $table): void {
            if (! Schema::hasColumn('registered_invoices', 'eligible_units')) $table->unsignedInteger('eligible_units')->default(0);
            if (! Schema::hasColumn('registered_invoices', 'product_validation_status')) $table->string('product_validation_status', 30)->default('not_applicable');
            if (! Schema::hasColumn('registered_invoices', 'matched_products')) $table->json('matched_products')->nullable();
        });

        Schema::create('registered_invoice_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('registered_invoice_id')->constrained('registered_invoices')->cascadeOnDelete();
            $table->string('barcode', 80)->nullable();
            $table->string('description', 255)->nullable();
            $table->decimal('quantity', 10, 3)->default(0);
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->boolean('is_eligible')->default(false);
            $table->json('source_payload')->nullable();
            $table->timestamps();
            $table->index(['barcode', 'is_eligible']);
        });

        Schema::table('promo_winners', function (Blueprint $table): void {
            if (! Schema::hasColumn('promo_winners', 'campaign_id')) $table->foreignId('campaign_id')->nullable()->after('id')->constrained('campaigns')->nullOnDelete();
            if (! Schema::hasColumn('promo_winners', 'total_units')) $table->unsignedInteger('total_units')->default(0);
            if (! Schema::hasColumn('promo_winners', 'first_reached_at')) $table->timestamp('first_reached_at')->nullable();
            if (! Schema::hasColumn('promo_winners', 'alternate_position')) $table->unsignedInteger('alternate_position')->nullable();
            $table->index(['campaign_id', 'status', 'leaderboard_position']);
        });

        Schema::create('campaign_participant_consents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('terms_version', 80);
            $table->timestamp('accepted_at');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->unique(['campaign_id', 'user_id']);
        });

        if (! DB::table('campaigns')->where('slug', 'malta-vigor-honor')->exists()) {
            DB::table('campaigns')->insert([
                'name' => 'Malta Vigor te premia con HONOR',
                'slug' => 'malta-vigor-honor',
                'description' => 'Compra Malta Vigor, registra tus facturas y acumula unidades para competir por uno de 5 celulares HONOR Magic7 Lite.',
                'status' => 'draft',
                'participation_mode' => 'product_ranking',
                'is_listed' => false,
                'sort_order' => 10,
                'starts_at' => '2026-09-01 00:00:00',
                'ends_at' => '2026-10-30 23:59:59',
                'rules' => json_encode([
                    'ranking_metric' => 'eligible_units',
                    'winner_slots' => 5,
                    'participant_identifier' => 'email',
                    'minimum_age' => 18,
                    'tie_break' => 'first_reached',
                    'exclude_employees' => true,
                    'exclude_wholesale' => true,
                    'exclude_corporate' => true,
                    'prize' => 'HONOR Magic7 Lite',
                ], JSON_THROW_ON_ERROR),
                'invoice_min_amount_for_shot' => 0,
                'amount_per_point' => 0,
                'points_per_block' => 1,
                'daily_max_points' => 0,
                'daily_max_invoices' => 100,
                'coupon_ttl_hours' => 0,
                'games_enabled' => false,
                'major_prizes_enabled' => true,
                'invoice_scan_enabled' => false,
                'redemption_enabled' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_participant_consents');
        Schema::table('promo_winners', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('campaign_id');
            $table->dropColumn(['total_units', 'first_reached_at', 'alternate_position']);
        });
        Schema::dropIfExists('registered_invoice_items');
        Schema::table('registered_invoices', function (Blueprint $table): void {
            $table->dropColumn(['eligible_units', 'product_validation_status', 'matched_products']);
        });
        Schema::dropIfExists('campaign_product_rules');
        Schema::table('campaigns', function (Blueprint $table): void {
            $table->dropColumn([
                'rules', 'terms_text', 'terms_version', 'terms_approved_at',
                'delivery_location', 'delivery_deadline', 'delivery_requirements', 'ranking_frozen_at',
            ]);
        });
    }
};
