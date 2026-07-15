<?php

namespace App\Support;

use App\Models\Campaign;
use App\Models\FondaRegistration;
use Illuminate\Support\Collection;

class FondaRankingService
{
    public function buildForCampaign(Campaign $campaign): Collection
    {
        return FondaRegistration::query()
            ->where('campaign_id', $campaign->id)
            ->where('status', 'approved')
            ->whereNotNull('checked_in_at')
            ->orderByDesc('approved_at')
            ->orderBy('code')
            ->get()
            ->map(function (FondaRegistration $registration): array {
                return [
                    'id' => $registration->id,
                    'code' => $registration->code,
                    'fonda_name' => $registration->fonda_name,
                    'dish_name' => $registration->dish_name,
                    'score' => 0.0,
                    'status' => $registration->status,
                ];
            });
    }
}
