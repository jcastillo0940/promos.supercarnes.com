<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Campaign;
use App\Models\TournamentMatch;
use App\Models\TournamentPhase;
use App\Models\User;
use App\Support\TournamentScoring;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $campaign  = Campaign::first();
        $branchIds = Branch::pluck('id')->toArray();
        $phase     = TournamentPhase::where('slug', 'fase-grupos')->firstOrFail();
        $matches   = TournamentMatch::where('phase_id', $phase->id)
            ->whereNotNull('home_team_id')
            ->whereNotNull('away_team_id')
            ->get();

        // 1. Finalizar partidos con resultados
        $this->command->info('Finalizando ' . $matches->count() . ' partidos...');
        foreach ($matches as $match) {
            $match->update([
                'status'        => 'final',
                'home_score'    => rand(0, 3),
                'away_score'    => rand(0, 3),
                'favorite_side' => $match->favorite_side === 'none' ? (rand(0, 1) ? 'home' : 'away') : $match->favorite_side,
            ]);
        }

        // 2. Crear 100 usuarios
        $this->command->info('Creando 100 usuarios...');
        $users = User::factory(100)->create();

        foreach ($users as $i => $user) {
            $registeredAt = Carbon::now()->subDays(rand(1, 30))->subSeconds($i);
            $user->update([
                'registration_completed_at' => $registeredAt,
                'predictions_completed_at'  => $registeredAt->addMinutes(rand(1, 30)),
                'registration_order_key'    => $registeredAt->format('YmdHis') . str_pad((string) $user->id, 10, '0', STR_PAD_LEFT),
                'branch_id'                 => $branchIds[array_rand($branchIds)],
            ]);
        }

        // 3. Wallets
        $this->command->info('Creando wallets, facturas y predicciones...');
        $now        = now()->toDateTimeString();
        $phaseStart = $phase->starts_at;
        $phaseEnd   = Carbon::now()->subDays(1);

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

        // Cargar wallets para tener los IDs
        $walletByUser = DB::table('wallets')
            ->whereIn('user_id', $users->pluck('id'))
            ->pluck('id', 'user_id');

        // 4. Facturas, movimientos y actualizar wallets
        $invoiceInserts  = [];
        $movementInserts = [];
        $walletTotals    = [];

        foreach ($users as $user) {
            $invoiceCount  = rand(2, 6);
            $totalPoints   = 0;
            $walletId      = $walletByUser[$user->id];

            for ($j = 0; $j < $invoiceCount; $j++) {
                $amount      = rand(25, 200) + (rand(0, 99) / 100);
                $points      = (int) floor($amount);
                $issuedAt    = Carbon::createFromTimestamp(
                    rand($phaseStart->timestamp, $phaseEnd->timestamp)
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

        // Actualizar saldos de wallets
        foreach ($walletTotals as $userId => $points) {
            DB::table('wallets')->where('user_id', $userId)->update([
                'goals_balance'         => $points,
                'lifetime_goals_earned' => $points,
                'updated_at'            => $now,
            ]);
        }

        // 5. Predicciones
        $predictionInserts = [];
        foreach ($users as $user) {
            foreach ($matches as $match) {
                $predictionInserts[] = [
                    'match_id'             => $match->id,
                    'user_id'              => $user->id,
                    'phase_id'             => $phase->id,
                    'predicted_home_score' => rand(0, 3),
                    'predicted_away_score' => rand(0, 3),
                    'points_awarded'       => 0,
                    'result_type'          => 'miss',
                    'created_at'           => $now,
                    'updated_at'           => $now,
                ];
            }
        }
        foreach (array_chunk($predictionInserts, 500) as $chunk) {
            DB::table('match_predictions')->insert($chunk);
        }

        // 6. Calcular puntos de predicciones
        $this->command->info('Calculando puntos de predicciones...');
        $scoring = new TournamentScoring();
        foreach ($matches as $match) {
            $match->refresh();
            $scoring->recalculateForMatch($match);
        }

        $this->command->info('Listo.');
        $this->command->table(
            ['Concepto', 'Total'],
            [
                ['Usuarios (clientes)',   User::where('role', 'client')->count()],
                ['Facturas',              DB::table('registered_invoices')->count()],
                ['Predicciones',          DB::table('match_predictions')->count()],
                ['Partidos finalizados',  $matches->count()],
            ]
        );
    }
}
