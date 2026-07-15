<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\FondaResultLock;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FondaResultsController extends Controller
{
    public function freeze(Request $request): RedirectResponse
    {
        $campaign = Campaign::query()->where('slug', 'fonda-challenge')->firstOrFail();

        $lock = FondaResultLock::query()->firstOrCreate(['campaign_id' => $campaign->id]);
        $lock->forceFill([
            'frozen_at' => now(),
            'frozen_by' => $request->user()?->id,
            'freeze_reason' => $request->input('reason', 'Freeze operativo'),
        ])->save();

        Audit::log('fonda_results_frozen', 'campaign', $campaign->id, $request->user(), $request, [
            'reason' => $lock->freeze_reason,
        ]);

        return back()->with('status', 'Resultados congelados.');
    }

    public function publish(Request $request): RedirectResponse
    {
        $campaign = Campaign::query()->where('slug', 'fonda-challenge')->firstOrFail();

        $lock = FondaResultLock::query()->firstOrCreate(['campaign_id' => $campaign->id]);
        $lock->forceFill([
            'published_at' => now(),
            'published_by' => $request->user()?->id,
            'publish_reason' => $request->input('reason', 'Publicación autorizada'),
        ])->save();

        Audit::log('fonda_results_published', 'campaign', $campaign->id, $request->user(), $request, [
            'reason' => $lock->publish_reason,
        ]);

        return back()->with('status', 'Resultados publicados.');
    }
}
