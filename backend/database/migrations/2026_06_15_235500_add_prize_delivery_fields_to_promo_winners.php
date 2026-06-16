<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promo_winners', function (Blueprint $table): void {
            if (! Schema::hasColumn('promo_winners', 'delivery_status')) {
                $table->string('delivery_status', 30)->default('pending')->after('status');
            }
            if (! Schema::hasColumn('promo_winners', 'delivery_qr_scanned_at')) {
                $table->timestamp('delivery_qr_scanned_at')->nullable()->after('delivery_status');
            }
            if (! Schema::hasColumn('promo_winners', 'id_card_photo_path')) {
                $table->string('id_card_photo_path', 255)->nullable()->after('delivery_qr_scanned_at');
            }
            if (! Schema::hasColumn('promo_winners', 'delivery_photo_path')) {
                $table->string('delivery_photo_path', 255)->nullable()->after('id_card_photo_path');
            }
            if (! Schema::hasColumn('promo_winners', 'delivery_notes')) {
                $table->text('delivery_notes')->nullable()->after('delivery_photo_path');
            }
            if (! Schema::hasColumn('promo_winners', 'delivered_by')) {
                $table->unsignedBigInteger('delivered_by')->nullable()->after('delivery_notes');
            }
            if (! Schema::hasColumn('promo_winners', 'prize_delivered_at')) {
                $table->timestamp('prize_delivered_at')->nullable()->after('delivered_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('promo_winners', function (Blueprint $table): void {
            foreach ([
                'delivery_status',
                'delivery_qr_scanned_at',
                'id_card_photo_path',
                'delivery_photo_path',
                'delivery_notes',
                'delivered_by',
                'prize_delivered_at',
            ] as $column) {
                if (Schema::hasColumn('promo_winners', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
