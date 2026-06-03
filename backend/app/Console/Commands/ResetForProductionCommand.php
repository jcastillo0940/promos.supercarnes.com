<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetForProductionCommand extends Command
{
    protected $signature = 'contest:reset-for-production
                            {--force : Omitir confirmacion interactiva}';

    protected $description = 'Borra todos los datos transaccionales de prueba y deja el sistema listo para produccion.';

    private array $transactionalTables = [
        'match_predictions',
        'wallet_movements',
        'wallets',
        'promo_winner_contacts',
        'promo_winners',
        'prize_tokens',
        'prize_inventory_movements',
        'fraud_flags',
        'game_plays',
        'audit_logs',
        'registered_invoices',
        'personal_access_tokens',
        'sessions',
        'cache',
        'cache_locks',
    ];

    public function handle(): int
    {
        $this->warn('Este comando borrara TODOS los datos de usuarios, facturas, predicciones y ganadores.');
        $this->warn('Las tablas de configuracion (fases, partidos, premios, sucursales) se conservan.');

        if (! $this->option('force') && ! $this->confirm('Confirmas el reset completo para produccion?')) {
            $this->info('Cancelado.');
            return self::SUCCESS;
        }

        $this->info('Iniciando reset...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Borrar usuarios clientes
        $deleted = DB::table('users')->where('role', 'client')->delete();
        $this->line("  users (clientes): {$deleted} eliminados");

        // Truncar tablas transaccionales
        foreach ($this->transactionalTables as $table) {
            DB::table($table)->truncate();
            $this->line("  {$table}: truncada");
        }

        // Resetear partidos a estado original
        $resetted = DB::table('tournament_matches')->update([
            'status'     => 'scheduled',
            'home_score' => null,
            'away_score' => null,
            'live_score_last_synced_at'  => null,
            'commentary_last_synced_at'  => null,
            'raw_provider_payload'       => null,
        ]);
        $this->line("  tournament_matches: {$resetted} partidos reseteados a 'scheduled'");

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->newLine();
        $this->info('Reset completado. Sistema listo para produccion.');

        return self::SUCCESS;
    }
}
