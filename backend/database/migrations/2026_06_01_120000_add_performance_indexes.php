<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Leaderboard query: users WHERE role = 'client' AND disqualified_at IS NULL
        Schema::table('users', function (Blueprint $table) {
            $table->index(['role', 'disqualified_at'], 'idx_users_role_disqualified');
        });

        // Leaderboard subquery: match_predictions WHERE phase_id = ? GROUP BY user_id
        // Invoice queries: WHERE user_id = ? AND phase_id = ?
        Schema::table('match_predictions', function (Blueprint $table) {
            $table->index(['phase_id', 'user_id'], 'idx_predictions_phase_user');
            $table->index('user_id', 'idx_predictions_user');
        });

        // Leaderboard subquery: registered_invoices WHERE validation_status = 'approved' GROUP BY user_id
        // Invoice index + filter queries
        Schema::table('registered_invoices', function (Blueprint $table) {
            $table->index(['user_id', 'validation_status'], 'idx_invoices_user_status');
            $table->index('validation_status', 'idx_invoices_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_role_disqualified');
        });

        Schema::table('match_predictions', function (Blueprint $table) {
            $table->dropIndex('idx_predictions_phase_user');
            $table->dropIndex('idx_predictions_user');
        });

        Schema::table('registered_invoices', function (Blueprint $table) {
            $table->dropIndex('idx_invoices_user_status');
            $table->dropIndex('idx_invoices_status');
        });
    }
};
