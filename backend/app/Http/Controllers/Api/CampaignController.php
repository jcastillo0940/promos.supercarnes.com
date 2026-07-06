<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Support\CampaignManager;
use Illuminate\Http\JsonResponse;

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
}
