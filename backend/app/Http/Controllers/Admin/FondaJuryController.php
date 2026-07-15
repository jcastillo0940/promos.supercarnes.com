<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\FondaJuryAssignment;
use App\Models\FondaJuryEvaluation;
use App\Models\FondaRegistration;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FondaJuryController extends Controller
{
    public function index(Request $request): View
    {
        $campaign = $this->campaign();
        $actingUser = $request->user();
        $isAdmin = $actingUser?->isAdmin() ?? false;

        $assignments = FondaJuryAssignment::query()->where('campaign_id', $campaign->id)
            ->when(! $isAdmin, fn ($query) => $query->where('user_id', $actingUser->id))
            ->latest()
            ->get();

        $evaluations = FondaJuryEvaluation::query()->where('campaign_id', $campaign->id)
            ->when(! $isAdmin, fn ($query) => $query->where('user_id', $actingUser->id))
            ->latest()
            ->get();

        return view('admin.fonda-jury', [
            'campaign' => $campaign,
            'isAdmin' => $isAdmin,
            'registrations' => $isAdmin
                ? FondaRegistration::query()->where('campaign_id', $campaign->id)->where('status', 'approved')->get()
                : FondaRegistration::query()->whereIn('id', $assignments->pluck('registration_id'))->get(),
            'jurors' => $isAdmin ? User::query()->where('role', 'jurado')->where('is_active', true)->orderBy('full_name')->get() : collect(),
            'assignments' => $assignments,
            'evaluations' => $evaluations,
        ]);
    }

    public function assign(Request $request, FondaRegistration $registration): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'jurado')],
        ]);

        FondaJuryAssignment::query()->firstOrCreate([
            'registration_id' => $registration->id,
            'user_id' => $validated['user_id'],
        ], [
            'campaign_id' => $registration->campaign_id,
            'status' => 'assigned',
        ]);

        Audit::log('fonda_jury_assigned', 'fonda_registration', $registration->id, $request->user(), $request, [
            'jury_user_id' => $validated['user_id'],
            'code' => $registration->code,
        ]);

        return back()->with('status', 'Jurado asignado.');
    }

    public function evaluate(Request $request, FondaJuryAssignment $assignment): RedirectResponse
    {
        $actingUser = $request->user();
        abort_unless($actingUser?->isAdmin() || $assignment->user_id === $actingUser?->id, 403);

        $validated = $request->validate([
            'sabor' => ['required', 'numeric', 'min:1', 'max:10'],
            'tecnica' => ['required', 'numeric', 'min:1', 'max:10'],
            'presentacion' => ['required', 'numeric', 'min:1', 'max:10'],
            'originalidad' => ['required', 'numeric', 'min:1', 'max:10'],
            'uso_producto' => ['required', 'numeric', 'min:1', 'max:10'],
            'commentary' => ['nullable', 'string', 'max:2000'],
        ]);

        $score = (($validated['sabor'] * 0.30) + ($validated['tecnica'] * 0.20) + ($validated['presentacion'] * 0.15) + ($validated['originalidad'] * 0.20) + ($validated['uso_producto'] * 0.15)) * 10;

        $evaluation = FondaJuryEvaluation::query()->updateOrCreate(
            ['assignment_id' => $assignment->id],
            [
                'campaign_id' => $assignment->campaign_id,
                'registration_id' => $assignment->registration_id,
                'user_id' => $assignment->user_id,
                'sabor' => $validated['sabor'],
                'tecnica' => $validated['tecnica'],
                'presentacion' => $validated['presentacion'],
                'originalidad' => $validated['originalidad'],
                'uso_producto' => $validated['uso_producto'],
                'final_score' => round($score, 2),
                'commentary' => $validated['commentary'] ?? null,
                'status' => 'submitted',
                'started_at' => $assignment->created_at ?? now(),
                'submitted_at' => now(),
            ]
        );

        Audit::log('fonda_jury_evaluation_submitted', 'fonda_jury_evaluation', $evaluation->id, $request->user(), $request, [
            'assignment_id' => $assignment->id,
            'registration_id' => $assignment->registration_id,
            'final_score' => $evaluation->final_score,
        ]);

        return back()->with('status', 'Evaluación guardada.');
    }

    private function campaign(): Campaign
    {
        return Campaign::query()->where('slug', 'fonda-challenge')->firstOrFail();
    }
}
