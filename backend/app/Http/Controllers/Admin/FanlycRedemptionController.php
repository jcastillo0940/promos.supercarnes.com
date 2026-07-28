<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FanlycCoupon;
use App\Models\FanlycZone;
use App\Support\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FanlycRedemptionController extends Controller
{
    public function index(string $zoneCode, Request $request): View
    {
        $zone = $this->zoneOrFail($zoneCode);
        $coupon = null;

        if ($request->filled('code')) {
            $coupon = FanlycCoupon::query()->where('code', $request->input('code'))->with('user', 'fanlycZone')->first();
        }

        return view('admin.fanlyc-redeem', [
            'zone' => $zone,
            'coupon' => $coupon,
        ]);
    }

    public function lookup(Request $request, string $zoneCode): View|RedirectResponse
    {
        $zone = $this->zoneOrFail($zoneCode);

        $validated = $request->validate([
            'coupon_code' => ['required', 'string', 'max:255'],
        ]);

        $code = strtoupper(trim($validated['coupon_code']));
        $coupon = FanlycCoupon::query()->where('code', $code)->with('user', 'fanlycZone')->first();

        if (! $coupon) {
            Audit::log('fanlyc.redemption_rejected', 'fanlyc_coupon', null, $request->user(), $request, [
                'reason' => 'code_not_found',
                'coupon_code' => $code,
                'zone' => $zone->code,
            ]);

            return view('admin.fanlyc-redeem-rejected', [
                'zone' => $zone,
                'reason' => 'No encontramos un cupon valido para ese codigo.',
                'reasonCode' => 'code_not_found',
                'couponCode' => $code,
                'coupon' => null,
            ]);
        }

        if ($coupon->status !== 'issued') {
            Audit::log('fanlyc.redemption_rejected', 'fanlyc_coupon', $coupon->id, $request->user(), $request, [
                'reason' => 'coupon_reused_or_void',
                'coupon_code' => $code,
                'zone' => $zone->code,
                'coupon_status' => $coupon->status,
            ]);

            return view('admin.fanlyc-redeem-rejected', [
                'zone' => $zone,
                'reason' => $coupon->status === 'redeemed' ? 'Ese cupon ya fue canjeado anteriormente.' : 'Ese cupon fue anulado y no puede canjearse.',
                'reasonCode' => 'coupon_reused_or_void',
                'couponCode' => $code,
                'coupon' => $coupon,
            ]);
        }

        if ($coupon->fanlyc_zone_id !== $zone->id) {
            Audit::log('fanlyc.redemption_rejected', 'fanlyc_coupon', $coupon->id, $request->user(), $request, [
                'reason' => 'zone_mismatch',
                'coupon_code' => $code,
                'zone' => $zone->code,
                'coupon_zone' => $coupon->fanlycZone?->code,
            ]);

            return view('admin.fanlyc-redeem-rejected', [
                'zone' => $zone,
                'reason' => 'Este cupon es de la zona '.($coupon->fanlycZone?->name ?? '—').', no de '.$zone->name.'.',
                'reasonCode' => 'zone_mismatch',
                'couponCode' => $code,
                'coupon' => $coupon,
            ]);
        }

        return view('admin.fanlyc-redeem', [
            'zone' => $zone,
            'coupon' => $coupon,
        ]);
    }

    public function findAjax(Request $request, string $zoneCode): JsonResponse
    {
        $zone = $this->zoneOrFail($zoneCode);

        $validated = $request->validate([
            'coupon_code' => ['required', 'string', 'max:255'],
        ]);

        $code = strtoupper(trim($validated['coupon_code']));
        $coupon = FanlycCoupon::query()->where('code', $code)->with('user', 'fanlycZone')->first();

        if (! $coupon) {
            return response()->json(['found' => false, 'message' => 'No encontramos un cupon valido para ese codigo.'], 404);
        }

        if ($coupon->status !== 'issued') {
            return response()->json([
                'found' => true,
                'valid' => false,
                'message' => $coupon->status === 'redeemed' ? 'Ese cupon ya fue canjeado anteriormente.' : 'Ese cupon fue anulado.',
                'coupon' => $this->couponPayload($coupon),
            ]);
        }

        if ($coupon->fanlyc_zone_id !== $zone->id) {
            return response()->json([
                'found' => true,
                'valid' => false,
                'message' => 'Este cupon es de la zona '.($coupon->fanlycZone?->name ?? '—').', no de '.$zone->name.'.',
                'coupon' => $this->couponPayload($coupon),
            ]);
        }

        return response()->json([
            'found' => true,
            'valid' => true,
            'message' => 'Cupon valido para esta zona.',
            'coupon' => $this->couponPayload($coupon),
        ]);
    }

    public function store(Request $request, string $zoneCode, FanlycCoupon $coupon): RedirectResponse
    {
        $zone = $this->zoneOrFail($zoneCode);

        abort_unless($coupon->status === 'issued', 422, 'Este cupon ya no esta disponible para canjear.');
        abort_unless($coupon->fanlyc_zone_id === $zone->id, 422, 'Este cupon no pertenece a esta zona.');

        $coupon->forceFill([
            'status' => 'redeemed',
            'redeemed_at' => now(),
            'redeemed_by_user_id' => $request->user()->id,
        ])->save();

        Audit::log('fanlyc.coupon_redeemed', 'fanlyc_coupon', $coupon->id, $request->user(), $request, [
            'code' => $coupon->code,
            'zone' => $zone->code,
        ]);

        return redirect()
            ->route('admin.fanlyc.redeem', ['zoneCode' => $zoneCode])
            ->with('status', 'Cupon '.$coupon->code.' canjeado correctamente.');
    }

    private function zoneOrFail(string $zoneCode): FanlycZone
    {
        return FanlycZone::query()->where('code', $zoneCode)->firstOrFail();
    }

    private function couponPayload(FanlycCoupon $coupon): array
    {
        return [
            'code' => $coupon->code,
            'status' => $coupon->status,
            'zone' => $coupon->fanlycZone?->name,
            'name' => $coupon->user?->full_name ?? $coupon->user?->name,
            'cedula' => $coupon->user?->cedula,
        ];
    }
}
