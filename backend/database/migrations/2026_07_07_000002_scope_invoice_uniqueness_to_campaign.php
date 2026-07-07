<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('registered_invoices')) {
            return;
        }

        Schema::table('registered_invoices', function (Blueprint $table): void {
            $table->dropUnique('registered_invoices_cufe_unique');
            $table->unique(['campaign_id', 'cufe'], 'registered_invoices_campaign_cufe_unique');
        });
    }

    public function down(): void
    {
        Schema::table('registered_invoices', function (Blueprint $table): void {
            $table->dropUnique('registered_invoices_campaign_cufe_unique');
            $table->unique('cufe', 'registered_invoices_cufe_unique');
        });
    }
};
