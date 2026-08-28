<?php

use App\Models\Branch;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Branch::query()
            ->where('store_number', 9)
            ->update(['name' => 'Arraiján']);
    }

    public function down(): void
    {
        Branch::query()
            ->where('store_number', 9)
            ->update(['name' => 'Vista Alegre']);
    }
};
