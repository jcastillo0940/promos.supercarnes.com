<?php

namespace App\Support;

use App\Models\Campaign;
use Illuminate\Validation\ValidationException;

class CampaignManager
{
    public function activeOrFail(): Campaign
    {
        $campaign = Campaign::query()
            ->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->latest('id')
            ->first();

        if (! $campaign) {
            throw ValidationException::withMessages([
                'campaign' => 'No hay una promocion activa en este momento.',
            ]);
        }

        return $campaign;
    }

    public function visible(): \Illuminate\Database\Eloquent\Collection
    {
        return Campaign::query()
            ->where('is_listed', true)
            ->orderByDesc('status')
            ->orderBy('sort_order')
            ->orderByDesc('starts_at')
            ->get();
    }

    public function bySlugOrFail(string $slug): Campaign
    {
        $campaign = Campaign::query()
            ->where('slug', $slug)
            ->first();

        if (! $campaign) {
            throw ValidationException::withMessages([
                'campaign' => 'No encontramos esa promocion.',
            ]);
        }

        return $campaign;
    }
}
