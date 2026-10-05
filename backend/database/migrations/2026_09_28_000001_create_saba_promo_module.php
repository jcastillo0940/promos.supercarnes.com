<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('saba_entries')) {
            Schema::create('saba_entries', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();

                // Una sola participacion por cedula. active_cedula es una copia de la
                // cedula mientras la participacion esta vigente; se limpia al rechazarla
                // para que la persona pueda volver a registrarse.
                $table->string('cedula', 40);
                $table->string('active_cedula', 40)->nullable();
                $table->string('full_name', 150);
                $table->string('phone', 20);
                $table->string('email', 150);
                $table->date('birthdate');
                $table->text('story');
                $table->timestamp('declared_eligibility_at')->nullable();
                $table->string('terms_version', 80)->nullable();
                $table->timestamp('accepted_terms_at')->nullable();

                $table->string('cufe', 255);
                $table->text('qr_raw_text')->nullable();
                $table->string('invoice_number', 255)->nullable();
                $table->string('issuer_ruc', 40)->nullable();
                $table->string('issuer_name', 255)->nullable();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->timestamp('issued_at')->nullable();
                $table->decimal('purchase_amount', 10, 2)->nullable();
                $table->unsignedInteger('eligible_units')->default(0);
                $table->string('product_check_status', 20)->default('undetermined');
                $table->json('matched_products')->nullable();
                $table->json('dgi_response_payload')->nullable();

                $table->string('status', 30)->default('pending_review');
                $table->text('validation_notes')->nullable();
                $table->string('source', 20)->default('web');
                $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->unsignedSmallInteger('winner_position')->nullable();
                $table->timestamp('winner_selected_at')->nullable();
                $table->foreignId('winner_selected_by_user_id')->nullable()->constrained('users')->nullOnDelete();

                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();

                $table->unique('cufe');
                $table->unique(['campaign_id', 'active_cedula']);
                $table->unique(['campaign_id', 'winner_position']);
                $table->index(['campaign_id', 'status']);
                $table->index('cedula');
            });
        }

        if (! DB::table('campaigns')->where('slug', 'saba')->exists()) {
            DB::table('campaigns')->insert([
                'name' => 'Emprende tu Sueño con Saba',
                'slug' => 'saba',
                'description' => 'Compra 1 producto Saba en Super Carnes, registra tu factura y cuéntanos por qué deberías ganar. ¡50 ganadoras!',
                'status' => 'draft',
                'participation_mode' => 'external_module',
                'is_listed' => false,
                'sort_order' => 20,
                // 28/09/2026 00:00 a 25/10/2026 23:59:59 hora de Panama (UTC-5), guardado en UTC.
                'starts_at' => '2026-09-28 05:00:00',
                'ends_at' => '2026-10-26 04:59:59',
                'rules' => json_encode([
                    'invoice_issued_from' => '2026-09-28',
                    'invoice_issued_to' => '2026-10-25',
                    'winner_slots' => 50,
                    'cash_prizes' => ['1' => 750, '2' => 500, '3' => 300],
                    'minimum_age' => 18,
                    'story_min_chars' => 80,
                    'story_max_chars' => 2000,
                    'logo_url' => null,
                    'hero_url' => null,
                    'terms_pdf_url' => null,
                ], JSON_THROW_ON_ERROR),
                'delivery_location' => 'En cualquiera de nuestras 15 sucursales Super Carnes, coordinado con cada ganadora.',
                'delivery_requirements' => 'Presentar cédula vigente. Entrega dentro de los 30 días hábiles siguientes al anuncio de las ganadoras.',
                'invoice_min_amount_for_shot' => 0,
                'amount_per_point' => 0,
                'entry_threshold_amount' => 0,
                'entry_requires_approval' => false,
                'points_per_block' => 1,
                'daily_max_points' => 0,
                'daily_max_invoices' => 0,
                'coupon_ttl_hours' => 0,
                'games_enabled' => false,
                'major_prizes_enabled' => true,
                'invoice_scan_enabled' => true,
                'redemption_enabled' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('saba_entries');
        DB::table('campaigns')->where('slug', 'saba')->delete();
    }
};
