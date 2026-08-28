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

        $this->rememberLookup($outcome['participant']->cedula, $outcome['participant']->phone);

        return redirect()
            ->route('fanlyc.thanks')
            ->with('status', $outcome['message']);
    }

    public function thanks(): View
    {
        [$cedula, $phone] = $this->recalledLookup();

        $participant = null;
        $invoice = null;
        $coupon = null;

        if ($cedula !== '' && $phone !== '') {
            $participant = User::query()
                ->where('cedula', $cedula)
                ->where('phone', $phone)
                ->first();

            if ($participant) {
                $invoice = $participant->fanlycInvoices()
                    ->with('fanlycZone', 'coupon.fanlycZone')
                    ->latest()
                    ->first();

                $coupon = $participant->fanlycCoupons()
                    ->with('fanlycZone', 'fanlycInvoice')
                    ->latest()
                    ->first();
            }
        }

        return view('fanlyc.thanks', [
            'participant' => $participant,
            'invoice' => $invoice,
            'coupon' => $coupon,
            'cedula' => $cedula,
            'phone' => $phone,
        ]);
    }

    public function searchStatus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cedula' => ['required', 'string', 'max:40'],
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $this->rememberLookup($validated['cedula'], $validated['phone']);

        return redirect()->route('fanlyc.status');
    }

    public function status(): View
    {
        [$cedula, $phone] = $this->recalledLookup();

        $participant = null;
        $coupons = collect();

        if ($cedula !== '' && $phone !== '') {
            $participant = User::query()
                ->where('cedula', $cedula)
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
            'searched' => $cedula !== '' && $phone !== '',
            'cedula' => $cedula,
            'phone' => $phone,
        ]);
    }

    private function rememberLookup(string $cedula, string $phone): void
    {
        $normalizedCedula = strtoupper(preg_replace('/[^0-9-]/', '', trim($cedula)) ?? '');

        session(['fanlyc_lookup' => [
            'cedula' => $normalizedCedula,
            'phone' => trim($phone),
        ]]);
    }

    /**
     * @return array{0: string, 1: string} [cedula, phone]
     */
    private function recalledLookup(): array
    {
        $lookup = (array) session('fanlyc_lookup', []);

        return [
            trim((string) ($lookup['cedula'] ?? '')),
            trim((string) ($lookup['phone'] ?? '')),
        ];
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
