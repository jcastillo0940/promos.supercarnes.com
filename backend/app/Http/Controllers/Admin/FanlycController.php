<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\FanlycCoupon;
use App\Models\FanlycInvoice;
use App\Models\FanlycZone;
use App\Support\Audit;
use App\Support\Fanlyc\FanlycCouponIssuer;
use App\Support\Fanlyc\FanlycRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FanlycController extends Controller
{
    private const STATUSES = [
        'pending_review',
        'approved',
        'rejected_issuer',
        'rejected_branch_not_in_promo',
        'rejected_sku_not_found',
        'rejected_invalid_cufe',
        'rejected_manual',
        'disqualified',
    ];

    public function index(Request $request): View
    {
        $campaign = $this->campaign();

        $query = FanlycInvoice::query()
            ->where('campaign_id', $campaign->id)
            ->with('user', 'branch', 'fanlycZone', 'coupon')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('fanlyc_zone_id'), fn ($q) => $q->where('fanlyc_zone_id', $request->input('fanlyc_zone_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = trim((string) $request->input('search'));
                $q->where(function ($inner) use ($term) {
                    $inner->where('cufe', 'like', "%{$term}%")
                        ->orWhere('invoice_number', 'like', "%{$term}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery
                            ->where('cedula', 'like', "%{$term}%")
                            ->orWhere('full_name', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%"));
                });
            });

        $invoices = $query->latest()->paginate(25)->appends($request->only(['status', 'fanlyc_zone_id', 'search']));

        $counts = FanlycInvoice::query()
            ->where('campaign_id', $campaign->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.fanlyc', [
            'invoices' => $invoices,
            'campaign' => $campaign,
            'statuses' => self::STATUSES,
            'counts' => $counts,
            'zones' => FanlycZone::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function show(FanlycInvoice $invoice): View
    {
        return view('admin.fanlyc-show', [
            'invoice' => $invoice->load('user', 'branch', 'fanlycZone', 'coupon', 'reviewedBy'),
        ]);
    }

    public function approve(Request $request, FanlycInvoice $invoice, FanlycCouponIssuer $couponIssuer): RedirectResponse
    {
        abort_unless($invoice->status === 'pending_review', 422, 'Solo se puede aprobar una factura en revision.');

        $invoice->forceFill([
            'status' => 'approved',
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
        ])->save();

        $coupon = $couponIssuer->issueFor($invoice);

        Audit::log('fanlyc.invoice.manually_approved', 'fanlyc_invoice', $invoice->id, $request->user(), $request, [
            'cufe' => $invoice->cufe,
        ]);
        Audit::log('fanlyc.coupon.issued', 'fanlyc_coupon', $coupon->id, $request->user(), $request, [
            'code' => $coupon->code,
        ]);

        return back()->with('status', 'Factura aprobada y cupon emitido.');
    }

    public function reject(Request $request, FanlycInvoice $invoice): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        abort_unless($invoice->status === 'pending_review', 422, 'Solo se puede rechazar una factura en revision.');

        $invoice->forceFill([
            'status' => 'rejected_manual',
            'validation_notes' => $validated['reason'],
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
        ])->save();

        Audit::log('fanlyc.invoice.manually_rejected', 'fanlyc_invoice', $invoice->id, $request->user(), $request, [
            'reason' => $validated['reason'],
        ]);

        return back()->with('status', 'Factura rechazada.');
    }

    public function voidCoupon(Request $request, FanlycCoupon $coupon): RedirectResponse
    {
        $validated = $request->validate([
            'void_reason' => ['required', 'string', 'max:2000'],
        ]);

        abort_if($coupon->status === 'redeemed', 422, 'No se puede anular un cupon ya canjeado.');

        $coupon->forceFill([
            'status' => 'void',
            'void_reason' => $validated['void_reason'],
            'voided_by_user_id' => $request->user()->id,
            'voided_at' => now(),
        ])->save();

        Audit::log('fanlyc.coupon.voided', 'fanlyc_coupon', $coupon->id, $request->user(), $request, [
            'reason' => $validated['void_reason'],
        ]);

        return back()->with('status', 'Cupon anulado.');
    }

    public function manualRegister(Request $request, FanlycRegistrationService $registrationService): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'cedula' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['required', 'string', 'max:30'],
            'qr_raw_text' => ['required', 'string', 'max:2048'],
        ]);

        $outcome = $registrationService->registerInvoice($validated);

        Audit::log('fanlyc.invoice.manual_intake', 'fanlyc_invoice', $outcome['invoice']->id, $request->user(), $request, [
            'source' => 'whatsapp',
            'cedula' => $validated['cedula'],
        ]);

        return back()->with('status', 'Factura registrada a nombre del cliente. '.$outcome['message']);
    }

    private function campaign(): Campaign
    {
        return Campaign::query()->where('slug', 'fanlyc')->firstOrFail();
    }
}
