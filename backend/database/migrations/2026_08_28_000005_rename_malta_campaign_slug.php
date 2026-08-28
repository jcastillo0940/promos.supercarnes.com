<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('campaigns')
            ->where('slug', 'malta-vigor-honor')
            ->update(['slug' => 'malta-vigor', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('campaigns')
            ->where('slug', 'malta-vigor')
            ->update(['slug' => 'malta-vigor-honor', 'updated_at' => now()]);
    }
};
