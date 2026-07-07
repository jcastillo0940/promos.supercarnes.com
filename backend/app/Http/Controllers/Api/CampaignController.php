<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\RegisteredInvoice;
use App\Models\User;
use App\Support\CampaignManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function __construct(
        private readonly CampaignManager $campaignManager,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->campaignManager->visible(),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $campaign = Campaign::query()->where('slug', $slug)->firstOrFail();

        return response()->json([
            'data' => $campaign,
        ]);
    }

    public function progress(Request $request, string $slug): JsonResponse
    {
        $validated = $request->validate([
            'document_number' => ['nullable', 'string', 'max:40'],
        ]);

        $campaign = Campaign::query()->where('slug', $slug)->firstOrFail();
        $authUser = $request->user('sanctum');
        $documentNumber = strtoupper(trim((string) ($validated['document_number'] ?? $authUser?->cedula ?? '')));
        $user = $authUser ?: ($documentNumber !== '' ? User::query()->where('cedula', $documentNumber)->first() : null);
        $total = $user
            ? (float) RegisteredInvoice::query()
                ->where('user_id', $user->id)
                ->where('campaign_id', $campaign->id)
                ->sum('purchase_amount')
            : 0.0;
        $threshold = (float) ($campaign->entry_threshold_amount ?? 0);
        $threshold = $threshold > 0 ? $threshold : 300.0;

        return response()->json([
            'data' => [
                'document_number' => $documentNumber,
                'campaign_total' => $total,
                'campaign_threshold' => $threshold,
                'campaign_qualified' => $campaign->participation_mode === 'threshold_form' ? $total >= $threshold : true,
                'participant_found' => (bool) $user,
            ],
        ]);
    }
}
