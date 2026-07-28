<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\FanlycCoupon;
use App\Models\User;
use App\Support\Fanlyc\FanlycRegistrationService;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FanlycController extends Controller
{
    public function __construct(private readonly FanlycRegistrationService $registrationService)
    {
    }

    public function landing(): View
    {
        $campaign = Campaign::query()->where('slug', 'fanlyc')->first();

        return view('fanlyc.landing', [
            'campaign' => $campaign,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'cedula' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['required', 'string', 'max:30'],
            'qr_raw_text' => ['required', 'string', 'max:2048'],
            'consent_terms' => ['accepted'],
        ]);

        $outcome = $this->registrationService->registerInvoice($validated);

        return redirect()
            ->route('fanlyc.status', [
                'cedula' => $outcome['participant']->cedula,
                'phone' => $outcome['participant']->phone,
            ])
            ->with('status', $outcome['message']);
    }

    public function status(Request $request): View
    {
        $cedula = trim((string) $request->query('cedula', ''));
        $phone = trim((string) $request->query('phone', ''));
        $normalizedCedula = strtoupper(preg_replace('/[^0-9-]/', '', $cedula) ?? '');

        $participant = null;
        $coupons = collect();

        if ($normalizedCedula !== '' && $phone !== '') {
            $participant = User::query()
                ->where('cedula', $normalizedCedula)
                ->where('phone', $phone)
                ->first();

            $coupons = $participant
                ? FanlycCoupon::query()
                    ->where('user_id', $participant->id)
                    ->with('fanlycZone', 'fanlycInvoice')
                    ->latest()
                    ->get()
                : collect();
        }

        return view('fanlyc.status', [
            'participant' => $participant,
            'coupons' => $coupons,
            'searched' => $normalizedCedula !== '' && $phone !== '',
            'cedula' => $normalizedCedula,
            'phone' => $phone,
        ]);
    }

    public function couponQr(string $code)
    {
        $coupon = FanlycCoupon::query()->where('code', $code)->firstOrFail();

        $qrCode = new QrCode(
            data: $coupon->code,
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 320,
            margin: 16,
        );

        $result = (new SvgWriter())->write($qrCode);

        return response($result->getString(), 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
