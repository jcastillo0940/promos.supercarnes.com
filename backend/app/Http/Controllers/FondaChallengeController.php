<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\FondaRegistration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FondaChallengeController extends Controller
{
    public function landing(): View
    {
        $campaign = Campaign::query()
            ->where('slug', 'fonda-challenge')
            ->orWhere('slug', 'fonda-challenge-2026')
            ->orderByDesc('status')
            ->first();

        return view('fonda-challenge.landing', [
            'campaign' => $campaign,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $campaign = Campaign::query()
            ->whereIn('slug', ['fonda-challenge', 'fonda-challenge-2026'])
            ->where('status', 'active')
            ->firstOrFail();

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'cedula' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['required', 'string', 'max:30'],
            'fonda_name' => ['required', 'string', 'max:150'],
            'fonda_location' => ['required', 'string', 'max:150'],
            'dish_name' => ['required', 'string', 'max:150'],
            'consent_terms' => ['accepted'],
        ]);

        $registration = FondaRegistration::query()->updateOrCreate(
            [
                'campaign_id' => $campaign->id,
                'cedula' => $validated['cedula'],
            ],
            [
                'code' => $this->generateCode(),
                'status' => 'pending_review',
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'fonda_name' => $validated['fonda_name'],
                'fonda_location' => $validated['fonda_location'],
                'dish_name' => $validated['dish_name'],
                'description' => '',
                'consent_terms' => 'v1',
                'meta' => [
                    'ip' => $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 255),
                    'referer' => $request->headers->get('referer'),
                ],
            ]
        );

        return redirect()
            ->route('fonda-challenge.show', ['code' => $registration->code])
            ->with('status', 'Tu registro quedo en revision.');
    }

    public function show(string $code): View
    {
        $registration = FondaRegistration::query()
            ->with('campaign')
            ->where('code', $code)
            ->firstOrFail();

        return view('fonda-challenge.confirmation', [
            'registration' => $registration,
        ]);
    }

    private function generateCode(): string
    {
        do {
            $code = 'FC26-'.strtoupper(Str::random(5));
        } while (FondaRegistration::query()->where('code', $code)->exists());

        return $code;
    }
}
