<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fanlyc_coupons')) {
            Schema::create('fanlyc_coupons', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('fanlyc_invoice_id')->constrained('fanlyc_invoices')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('fanlyc_zone_id')->constrained('fanlyc_zones')->cascadeOnDelete();
                $table->string('code', 30)->unique();
                $table->string('status', 20)->default('issued');
                $table->timestamp('redeemed_at')->nullable();
                $table->foreignId('redeemed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('redemption_notes')->nullable();
                $table->text('void_reason')->nullable();
                $table->foreignId('voided_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('voided_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['fanlyc_zone_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fanlyc_coupons');
    }
};
