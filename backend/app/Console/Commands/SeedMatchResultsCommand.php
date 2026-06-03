<?php

namespace App\Console\Commands;

use App\Models\TournamentMatch;
use App\Models\TournamentPhase;
use App\Support\TournamentScoring;
use Illuminate\Console\Command;

class SeedMatchResultsCommand extends Command
{
    protected $signature = 'contest:seed-results
                            {--phase=fase-grupos : Slug de la fase a finalizar}
                            {--force : Omitir confirmacion}';

    protected $description = 'Asigna resultados aleatorios a los partidos de una fase y calcula puntos de predicciones.';

    public function handle(): int
    {
        $phaseSlug = $this->option('phase');
        $phase     = TournamentPhase::where('slug', $phaseSlug)->first();

        if (! $phase) {
            $this->error("No se encontro la fase: {$phaseSlug}");
            $this->info('Fases disponibles:');
            TournamentPhase::all(['name', 'slug'])->each(
                fn ($p) => $this->line("  {$p->slug}  ({$p->name})")
            );
            return self::FAILURE;
        }

        $matches = TournamentMatch::where('phase_id', $phase->id)
            ->whereNotNull('home_team_id')
            ->whereNotNull('away_team_id')
            ->get();

        if ($matches->isEmpty()) {
            $this->error('No hay partidos con equipos asignados en esta fase.');
            return self::FAILURE;
        }

        $alreadyFinal = $matches->where('status', 'final')->count();

        $this->info("Fase: {$phase->name}");
        $this->info("Partidos encontrados: {$matches->count()} ({$alreadyFinal} ya finalizados)");
        $this->warn('Se asignaran resultados aleatorios a TODOS los partidos de la fase y se recalcularan los puntos de predicciones.');

        if (! $this->option('force') && ! $this->confirm('Confirmas?')) {
            $this->info('Cancelado.');
            return self::SUCCESS;
        }

        // Finalizar partidos
        $this->info('Finalizando partidos...');
        $bar = $this->output->createProgressBar($matches->count());
        $bar->start();

        foreach ($matches as $match) {
            $match->update([
                'status'        => 'final',
                'home_score'    => rand(0, 3),
                'away_score'    => rand(0, 3),
                'favorite_side' => in_array($match->favorite_side, ['home', 'away'])
                    ? $match->favorite_side
                    : (rand(0, 1) ? 'home' : 'away'),
            ]);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // Recalcular puntos de predicciones
        $this->info('Calculando puntos de predicciones...');
        $scoring      = new TournamentScoring();
        $bar2         = $this->output->createProgressBar($matches->count());
        $bar2->start();
        $totalUpdated = 0;

        foreach ($matches as $match) {
            $match->refresh();
            $scoring->recalculateForMatch($match);
            $totalUpdated += $match->predictions()->count();
            $bar2->advance();
        }

        $bar2->finish();
        $this->newLine();

        $this->info('Listo.');
        $this->table(
            ['Concepto', 'Total'],
            [
                ['Fase',                       $phase->name],
                ['Partidos finalizados',        $matches->count()],
                ['Predicciones recalculadas',   $totalUpdated],
            ]
        );

        return self::SUCCESS;
    }
}
