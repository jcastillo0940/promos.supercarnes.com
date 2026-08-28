<?php

namespace App\Support;

use App\Models\Campaign;
use Illuminate\Validation\ValidationException;

class CampaignLaunchGuard
{
    public function assertCanPublish(Campaign $campaign): void
    {
        if ($campaign->participation_mode !== 'product_ranking') {
            return;
        }

        $missing = [];
        if ($campaign->productRules()->where('is_active', true)->count() === 0) {
            $missing[] = 'códigos de barras oficiales';
        }
        if (! $campaign->terms_text || ! $campaign->terms_version || ! $campaign->terms_approved_at) {
            $missing[] = 'términos y condiciones aprobados';
        }
        if (! $campaign->delivery_location || ! $campaign->delivery_deadline) {
            $missing[] = 'lugar y fecha límite de entrega';
        }

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'campaign' => 'La promoción no puede publicarse. Falta: '.implode(', ', $missing).'.',
            ]);
        }
    }
}
