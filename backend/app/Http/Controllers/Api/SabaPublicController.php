<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SabaEntry;
use App\Support\Saba\SabaPromoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SabaPublicController extends Controller
{
    public function __construct(private readonly SabaPromoService $saba)
    {
    }

    public function show(): JsonResponse
    {
        return response()->json([
            'data' => $this->saba->publicPayload($this->saba->campaign()),
        ]);
    }

    public function resolve(Request $request): JsonResponse
    {
        $data = $request->validate([
            'qr_raw_text' => ['required', 'string', 'max:2048'],
        ]);

        $campaign = $this->saba->campaign();
        $this->assertOpen($campaign);

        $inspection = $this->saba->inspectInvoice($campaign, $data['qr_raw_text']);

        return response()->json([
            'data' => $this->saba->invoiceSummary($inspection),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'qr_raw_text' => ['required', 'string', 'max:2048'],
            'full_name' => ['required', 'string', 'max:150'],
            'cedula' => ['required', 'string', 'max:40'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'string', 'max:150'],
            'birthdate' => ['required', 'date'],
            'story' => ['required', 'string', 'max:5000'],
            'declares_eligibility' => ['required', 'boolean'],
            'accepted_terms' => ['required', 'boolean'],
            'terms_version' => ['nullable', 'string', 'max:80'],
        ]);

        $entry = $this->saba->register($data, $request);

        return response()->json([
            'data' => [
                'first_name' => $entry->firstName(),
                'status' => $entry->status,
                'status_label' => $entry->statusLabel(),
                'message' => $entry->status === 'approved'
                    ? '¡Listo! Tu participación quedó registrada.'
                    : 'Recibimos tu participación. Revisaremos tu factura y te avisaremos por correo.',
            ],
        ], 201);
    }

    /** Consulta de estado: requiere cedula + correo para no exponer datos. */
    public function status(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cedula' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:150'],
        ]);

        $campaign = $this->saba->campaign();
        $entry = SabaEntry::query()
            ->where('campaign_id', $campaign->id)
            ->where('cedula', $this->saba->normalizeCedula($data['cedula']))
            ->where('email', strtolower(trim($data['email'])))
            ->latest('id')
            ->first();

        if (! $entry) {
            throw ValidationException::withMessages([
                'cedula' => 'No encontramos una participación con esa cédula y correo.',
            ]);
        }

        return response()->json([
            'data' => [
                'first_name' => $entry->firstName(),
                'status' => $entry->status,
                'status_label' => $entry->statusLabel(),
                'registered_at' => $entry->created_at?->timezone(SabaPromoService::TIMEZONE)->format('d/m/Y'),
                'can_register_again' => $entry->status === 'rejected',
            ],
        ]);
    }

    private function assertOpen($campaign): void
    {
        if (! $this->saba->isOpen($campaign)) {
            throw ValidationException::withMessages([
                'campaign' => 'La promoción Emprende tu Sueño con Saba no está recibiendo participaciones en este momento.',
            ]);
        }
    }
}
