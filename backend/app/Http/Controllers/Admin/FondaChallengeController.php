<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\FondaRegistration;
use App\Support\Audit;
use App\Support\FondaRankingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FondaChallengeController extends Controller
{
    private const STATUSES = ['pending_review', 'needs_correction', 'approved', 'rejected', 'checked_in', 'ready_for_judging', 'disqualified'];

    public function index(Request $request): View
    {
        $campaign = $this->campaign();

        $query = FondaRegistration::query()
            ->where('campaign_id', $campaign->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = trim((string) $request->input('search'));
                $q->where(function ($inner) use ($term) {
                    $inner->where('code', 'like', "%{$term}%")
                        ->orWhere('cedula', 'like', "%{$term}%")
                        ->orWhere('full_name', 'like', "%{$term}%")
                        ->orWhere('fonda_name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            });

        $registrations = $query->latest()->paginate(25);
        $registrations->appends($request->only(['status', 'search']));

        $counts = FondaRegistration::query()
            ->where('campaign_id', $campaign->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.fonda-challenge', [
            'registrations' => $registrations,
            'campaign' => $campaign,
            'statuses' => self::STATUSES,
            'counts' => $counts,
        ]);
    }

    public function edit(FondaRegistration $registration): View
    {
        return view('admin.fonda-challenge-edit', [
            'registration' => $registration,
            'statuses' => self::STATUSES,
        ]);
    }

    public function update(Request $request, FondaRegistration $registration): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'cedula' => ['required', 'string', 'max:40', Rule::unique('fonda_registrations', 'cedula')->where('campaign_id', $registration->campaign_id)->ignore($registration->id)],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['required', 'string', 'max:30'],
            'fonda_name' => ['required', 'string', 'max:150'],
            'fonda_location' => ['required', 'string', 'max:150'],
            'dish_name' => ['required', 'string', 'max:150'],
        ]);

        $before = $registration->only(array_keys($validated));

        $registration->forceFill($validated)->save();

        Audit::log(
            'fonda_registration_updated',
            'fonda_registration',
            $registration->id,
            $request->user(),
            $request,
            ['before' => $before, 'after' => $validated, 'code' => $registration->code]
        );

        return redirect()
            ->route('admin.fonda-challenge.edit', $registration)
            ->with('status', 'Datos del participante actualizados.');
    }

    public function updateStatus(Request $request, FondaRegistration $registration): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending_review', 'needs_correction', 'approved', 'rejected', 'checked_in', 'ready_for_judging', 'disqualified'])],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $previous = $registration->status;
        $registration->forceFill([
            'status' => $validated['status'],
            'approved_at' => $validated['status'] === 'approved' ? now() : $registration->approved_at,
            'rejected_at' => $validated['status'] === 'rejected' ? now() : $registration->rejected_at,
        ])->save();

        Audit::log(
            'fonda_registration_status_changed',
            'fonda_registration',
            $registration->id,
            $request->user(),
            $request,
            [
                'from' => $previous,
                'to' => $validated['status'],
                'reason' => $validated['reason'] ?? null,
                'code' => $registration->code,
            ]
        );

        return back()->with('status', 'Estado actualizado.');
    }

    public function approve(Request $request, FondaRegistration $registration): RedirectResponse
    {
        return $this->updateStatus($request->merge([
            'status' => 'approved',
        ]), $registration);
    }

    public function requestCorrection(Request $request, FondaRegistration $registration): RedirectResponse
    {
        return $this->updateStatus($request->merge([
            'status' => 'needs_correction',
        ]), $registration);
    }

    public function reject(Request $request, FondaRegistration $registration): RedirectResponse
    {
        return $this->updateStatus($request->merge([
            'status' => 'rejected',
        ]), $registration);
    }

    public function checkIn(Request $request, FondaRegistration $registration): RedirectResponse
    {
        abort_unless($registration->status === 'approved' || $registration->status === 'ready_for_judging', 422, 'La fonda debe estar aprobada.');

        $registration->forceFill([
            'checked_in_at' => now(),
            'status' => 'checked_in',
        ])->save();

        Audit::log(
            'fonda_registration_checked_in',
            'fonda_registration',
            $registration->id,
            $request->user(),
            $request,
            ['code' => $registration->code]
        );

        return back()->with('status', 'Check-in registrado.');
    }

    public function ranking(FondaRankingService $rankingService): View
    {
        $campaign = $this->campaign();

        return view('admin.fonda-challenge-ranking', [
            'entries' => $rankingService->buildForCampaign($campaign),
            'campaign' => $campaign,
        ]);
    }

    private function campaign(): Campaign
    {
        return Campaign::query()->where('slug', 'fonda-challenge')->firstOrFail();
    }
}
