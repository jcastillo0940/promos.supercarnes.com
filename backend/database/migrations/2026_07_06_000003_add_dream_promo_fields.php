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
            if (! Schema::hasColumn('campaigns', 'entry_threshold_amount')) {
                $table->decimal('entry_threshold_amount', 10, 2)->nullable()->after('amount_per_point');
            }
            if (! Schema::hasColumn('campaigns', 'entry_requires_approval')) {
                $table->boolean('entry_requires_approval')->default(false)->after('entry_threshold_amount');
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            foreach ([
                'entrepreneur_name' => ['string', 180],
                'entrepreneur_province' => ['string', 120],
                'nearest_branch_id' => ['unsignedBigInteger', null],
                'entrepreneur_type' => ['string', 120],
                'entrepreneur_story' => ['text', null],
                'entrepreneur_reason' => ['text', null],
                'dream_promo_qualified_at' => ['timestamp', null],
            ] as $column => [$type, $size]) {
                if (Schema::hasColumn('users', $column)) {
                    continue;
                }

                match ($type) {
                    'string' => $table->string($column, $size)->nullable(),
                    'text' => $table->text($column)->nullable(),
                    'unsignedBigInteger' => $table->unsignedBigInteger($column)->nullable(),
                    'timestamp' => $table->timestamp($column)->nullable(),
                };
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            foreach ([
                'entrepreneur_name',
                'entrepreneur_province',
                'nearest_branch_id',
                'entrepreneur_type',
                'entrepreneur_story',
                'entrepreneur_reason',
                'dream_promo_qualified_at',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('campaigns', function (Blueprint $table): void {
            foreach (['entry_requires_approval', 'entry_threshold_amount'] as $column) {
                if (Schema::hasColumn('campaigns', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
