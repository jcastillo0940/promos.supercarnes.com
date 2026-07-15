<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FondaMediaSession;
use App\Models\FondaRegistration;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FondaMediaController extends Controller
{
    public function create(Request $request, FondaRegistration $registration): RedirectResponse
    {
        $session = FondaMediaSession::query()->firstOrCreate([
            'registration_id' => $registration->id,
        ], [
            'campaign_id' => $registration->campaign_id,
            'user_id' => $request->user()?->id,
            'status' => 'open',
            'started_at' => now(),
        ]);

        Audit::log('fonda_media_session_started', 'fonda_media_session', $session->id, $request->user(), $request, [
            'registration_id' => $registration->id,
            'code' => $registration->code,
        ]);

        return back()->with('status', 'Sesión de medios creada.');
    }
}
