<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registered_invoices', function (Blueprint $table) {
            $table->text('dad_reason')->nullable()->after('validation_notes');
        });
    }

    public function down(): void
    {
        Schema::table('registered_invoices', function (Blueprint $table) {
            $table->dropColumn('dad_reason');
        });
    }
};
