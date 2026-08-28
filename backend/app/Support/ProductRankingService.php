<?php

namespace App\Support;

use App\Models\Campaign;
use App\Models\RegisteredInvoice;
use App\Models\User;
use Illuminate\Support\Carbon;

class ProductRankingService
{
    public function __construct(private readonly ProductLineExtractor $extractor)
    {
    }

    public function evaluate(Campaign $campaign, array $resolvedInvoice): array
    {
        $result = $this->extractor->evaluate($campaign, $resolvedInvoice);
        $eligibleUnits = (int) collect($result['items'])->where('is_eligible', true)->sum(fn ($item) => (float) $item['quantity']);

        return [
            'status' => $result['status'],
            'items' => $result['items'],
            'eligible_units' => $eligibleUnits,
            'matched_products' => collect($result['items'])->where('is_eligible', true)->values()->all(),
        ];
    }

    public function totalFor(User $user, Campaign $campaign): int
    {
        return (int) RegisteredInvoice::query()
            ->where('user_id', $user->id)
            ->where('campaign_id', $campaign->id)
            ->where('validation_status', 'approved')
            ->sum('eligible_units');
    }

    public function leaderboard(Campaign $campaign): \Illuminate\Support\Collection
    {
        return User::query()
            ->select('users.*')
            ->selectSub(RegisteredInvoice::query()
                ->selectRaw('COALESCE(SUM(eligible_units), 0)')
                ->whereColumn('user_id', 'users.id')
                ->where('campaign_id', $campaign->id)
                ->where('validation_status', 'approved'), 'total_units')
            ->selectSub(RegisteredInvoice::query()
                ->selectRaw('MIN(created_at)')
                ->whereColumn('user_id', 'users.id')
                ->where('campaign_id', $campaign->id)
                ->where('validation_status', 'approved')
                ->where('eligible_units', '>', 0), 'first_reached_at')
            ->whereHas('invoices', fn ($query) => $query
                ->where('campaign_id', $campaign->id)
                ->where('validation_status', 'approved')
                ->where('eligible_units', '>', 0))
            ->orderByDesc('total_units')
            ->orderBy('first_reached_at')
            ->orderBy('users.id')
            ->get();
    }

    public function isAdult(User $user, Campaign $campaign): bool
    {
        $minimumAge = (int) data_get($campaign->rules, 'minimum_age', 18);
        return $user->birthdate !== null && $user->birthdate->age >= $minimumAge;
    }
}
