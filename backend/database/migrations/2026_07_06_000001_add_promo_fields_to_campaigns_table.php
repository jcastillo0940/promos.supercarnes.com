<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table): void {
            if (! Schema::hasColumn('campaigns', 'slug')) {
                $table->string('slug', 120)->nullable()->unique()->after('name');
            }

            if (! Schema::hasColumn('campaigns', 'is_listed')) {
                $table->boolean('is_listed')->default(true)->after('status');
            }

            if (! Schema::hasColumn('campaigns', 'hero_image_url')) {
                $table->string('hero_image_url', 255)->nullable()->after('is_listed');
            }

            if (! Schema::hasColumn('campaigns', 'card_image_url')) {
                $table->string('card_image_url', 255)->nullable()->after('hero_image_url');
            }

            if (! Schema::hasColumn('campaigns', 'sort_order')) {
                $table->unsignedSmallInteger('sort_order')->default(0)->after('card_image_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table): void {
            if (Schema::hasColumn('campaigns', 'sort_order')) {
                $table->dropColumn('sort_order');
            }
            if (Schema::hasColumn('campaigns', 'card_image_url')) {
                $table->dropColumn('card_image_url');
            }
            if (Schema::hasColumn('campaigns', 'hero_image_url')) {
                $table->dropColumn('hero_image_url');
            }
            if (Schema::hasColumn('campaigns', 'is_listed')) {
                $table->dropColumn('is_listed');
            }
            if (Schema::hasColumn('campaigns', 'slug')) {
                $table->dropUnique(['slug']);
                $table->dropColumn('slug');
            }
        });
    }
};
