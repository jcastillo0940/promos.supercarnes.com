<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fanlyc_invoices')) {
            Schema::create('fanlyc_invoices', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->foreignId('fanlyc_zone_id')->nullable()->constrained('fanlyc_zones')->nullOnDelete();
                $table->string('cufe', 255);
                $table->text('qr_raw_text')->nullable();
                $table->string('invoice_number', 255)->nullable();
                $table->string('issuer_ruc', 40)->nullable();
                $table->string('issuer_name', 255)->nullable();
                $table->timestamp('issued_at')->nullable();
                $table->decimal('purchase_amount', 10, 2)->nullable();
                $table->string('sku_check_status', 20)->default('undetermined');
                $table->json('sku_check_payload')->nullable();
                $table->string('status', 30)->default('pending_review');
                $table->text('validation_notes')->nullable();
                $table->json('dgi_response_payload')->nullable();
                $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();

                $table->unique(['campaign_id', 'cufe']);
                $table->index(['user_id', 'status']);
                $table->index(['fanlyc_zone_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fanlyc_invoices');
    }
};
