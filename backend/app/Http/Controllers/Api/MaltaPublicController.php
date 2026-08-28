<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignParticipantConsent;
use App\Models\RegisteredInvoice;
use App\Models\User;
use App\Support\ProductRankingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MaltaPublicController extends Controller
{
    private const CAMPAIGN_SLUG = 'malta-vigor-honor';

    public function __construct(private readonly ProductRankingService $productRanking)
    {
    }

    public function participant(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cedula' => ['required', 'string', 'max:40'],
        ]);

        $cedula = $this->normalizeCedula($data['cedula']);
        $user = User::query()
            ->where('cedula', $cedula)
            ->where('role', 'client')
            ->first();
        $campaign = Campaign::query()->where('slug', self::CAMPAIGN_SLUG)->first();
        $hasCurrentConsent = $user && $campaign?->terms_version
            ? CampaignParticipantConsent::query()
                ->where('campaign_id', $campaign->id)
                ->where('user_id', $user->id)
                ->where('terms_version', $campaign->terms_version)
                ->exists()
            : false;
        $isReady = $user !== null
            && $user->email
            && $user->phone
            && $user->birthdate
            && $hasCurrentConsent;

        return response()->json([
            'data' => [
                'registered' => $isReady,
                'display_name' => $isReady ? $this->firstName($user) : null,
            ],
        ]);
    }

    public function progress(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cedula' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:150'],
        ]);

        $cedula = $this->normalizeCedula($data['cedula']);
        $email = strtolower(trim($data['email']));
        $user = User::query()
            ->where('cedula', $cedula)
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('role', 'client')
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'cedula' => 'No encontramos una participación con esa cédula y correo.',
            ]);
        }

        $campaign = Campaign::query()->where('slug', self::CAMPAIGN_SLUG)->firstOrFail();
        $invoiceCount = RegisteredInvoice::query()
            ->where('campaign_id', $campaign->id)
            ->where('user_id', $user->id)
            ->whereIn('validation_status', ['approved', 'pending'])
            ->count();

        return response()->json([
            'data' => [
                'display_name' => $this->firstName($user),
                'campaign_units_total' => $this->productRanking->totalFor($user, $campaign),
                'invoice_count' => $invoiceCount,
            ],
        ]);
    }

    private function normalizeCedula(string $cedula): string
    {
        return preg_replace('/[^0-9-]/', '', trim($cedula)) ?? '';
    }

    private function firstName(User $user): string
    {
        return preg_split('/\s+/', trim((string) ($user->full_name ?: $user->name)))[0] ?? 'Participante';
    }
}
