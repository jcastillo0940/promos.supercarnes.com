<?php

namespace App\Support;

use App\Models\TournamentPhase;
use Carbon\CarbonInterface;

class InvoicePeriodResolver
{
    public function periodForDate(?CarbonInterface $date): ?TournamentPhase
    {
        if (! $date) {
            return $this->currentPeriod();
        }

        return TournamentPhase::query()
            ->where('starts_at', '<=', $date)
            ->where('ends_at', '>=', $date)
            ->orderBy('stage_order')
            ->first()
            ?? $this->currentPeriod();
    }

    public function currentPeriod(): ?TournamentPhase
    {
        return TournamentPhase::query()
            ->where('is_active', true)
            ->orderByRaw('CASE WHEN ? BETWEEN starts_at AND ends_at THEN 0 ELSE 1 END', [now()])
            ->orderBy('stage_order')
            ->first();
    }
}
