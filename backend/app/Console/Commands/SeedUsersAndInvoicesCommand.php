<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Campaign;
use App\Models\TournamentPhase;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeedUsersAndInvoicesCommand extends Command
{
    protected $signature = 'contest:seed-users
                            {--count=100 : Numero de usuarios a crear}
                            {--force : Omitir confirmacion}';

    protected $description = 'Crea usuarios de prueba con facturas aprobadas y wallets.';

    public function handle(): int
    {
        $count = (int) $this->option('count');

        $this->warn("Se crearan {$count} usuarios con facturas de prueba.");

        if (! $this->option('force') && ! $this->confirm('Confirmas?')) {
            $this->info('Cancelado.');
            return self::SUCCESS;
        }

        $campaign  = Campaign::first();
        $branchIds = Branch::pluck('id')->toArray();
        $phase     = TournamentPhase::where('slug', 'fase-grupos')->firstOrFail();
        $now       = now()->toDateTimeString();
        $phaseEnd  = Carbon::now()->subDays(1);

        // Crear usuarios
        $this->info("Creando {$count} usuarios...");
        $users = User::factory($count)->create();

        foreach ($users as $i => $user) {
            $registeredAt = Carbon::now()->subDays(rand(1, 30))->subSeconds($i);
            $user->update([
                'registration_completed_at' => $registeredAt,
                'predictions_completed_at'  => $registeredAt->addMinutes(rand(1, 30)),
                'registration_order_key'    => $registeredAt->format('YmdHis') . str_pad((string) $user->id, 10, '0', STR_PAD_LEFT),
                'branch_id'                 => $branchIds[array_rand($branchIds)],
            ]);
        }

        // Crear wallets
        $walletInserts = [];
        foreach ($users as $user) {
            $walletInserts[] = [
                'user_id'               => $user->id,
                'goals_balance'         => 0,
                'shots_balance'         => 0,
                'lifetime_goals_earned' => 0,
                'lifetime_shots_earned' => 0,
                'created_at'            => $now,
                'updated_at'            => $now,
            ];
        }
        foreach (array_chunk($walletInserts, 200) as $chunk) {
            DB::table('wallets')->insert($chunk);
        }

        $walletByUser = DB::table('wallets')
            ->whereIn('user_id', $users->pluck('id'))
            ->pluck('id', 'user_id');

        // Crear facturas y movimientos
        $this->info('Creando facturas y movimientos de wallet...');
        $invoiceInserts  = [];
        $movementInserts = [];
        $walletTotals    = [];

        foreach ($users as $user) {
            $invoiceCount = rand(2, 6);
            $totalPoints  = 0;
            $walletId     = $walletByUser[$user->id];

            for ($j = 0; $j < $invoiceCount; $j++) {
                $amount      = rand(25, 200) + (rand(0, 99) / 100);
                $points      = (int) floor($amount);
                $issuedAt    = Carbon::createFromTimestamp(
                    rand($phase->starts_at->timestamp, $phaseEnd->timestamp)
                );
                $totalPoints += $points;

                $invoiceInserts[] = [
                    'user_id'                  => $user->id,
                    'campaign_id'              => $campaign->id,
                    'branch_id'                => $user->branch_id,
                    'cufe'                     => 'TEST-' . strtoupper(Str::random(32)),
                    'qr_raw_text'              => 'TEST-QR-' . strtoupper(Str::random(16)),
                    'invoice_number'           => 'TEST-' . strtoupper(Str::random(10)),
                    'issuer_ruc'               => '123456-' . rand(1, 9) . '-' . rand(100000, 999999),
                    'issuer_name'              => 'SUPER CARNES TEST',
                    'fiscal_document_type'     => 'FE',
                    'issued_at'                => $issuedAt->toDateTimeString(),
                    'purchase_amount'          => number_format($amount, 2, '.', ''),
                    'points_awarded'           => $points,
                    'shots_awarded'            => 0,
                    'status'                   => 'registered',
                    'validation_status'        => 'approved',
                    'validation_notes'         => 'Datos de prueba',
                    'dgi_checked_at'           => $issuedAt->toDateTimeString(),
                    'daily_points_capped'      => 0,
                    'daily_invoice_limit_hit'  => 0,
                    'created_at'               => $now,
                    'updated_at'               => $now,
                ];

                $movementInserts[] = [
                    'wallet_id'   => $walletId,
                    'user_id'     => $user->id,
                    'campaign_id' => $campaign->id,
                    'type'        => 'invoice_approved',
                    'goals_delta' => $points,
                    'shots_delta' => 0,
                    'notes'       => 'Factura de prueba registrada',
                    'created_at'  => $now,
                ];
            }

            $walletTotals[$user->id] = $totalPoints;
        }

        foreach (array_chunk($invoiceInserts, 200) as $chunk) {
            DB::table('registered_invoices')->insert($chunk);
        }
        foreach (array_chunk($movementInserts, 200) as $chunk) {
            DB::table('wallet_movements')->insert($chunk);
        }
        foreach ($walletTotals as $userId => $points) {
            DB::table('wallets')->where('user_id', $userId)->update([
                'goals_balance'         => $points,
                'lifetime_goals_earned' => $points,
                'updated_at'            => $now,
            ]);
        }

        $this->info('Listo.');
        $this->table(
            ['Concepto', 'Total'],
            [
                ['Usuarios creados',  $count],
                ['Facturas creadas',  count($invoiceInserts)],
                ['Total clientes DB', User::where('role', 'client')->count()],
            ]
        );

        return self::SUCCESS;
    }
}
