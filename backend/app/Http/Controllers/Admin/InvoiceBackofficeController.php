<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvoiceGoalSetting;
use App\Models\RegisteredInvoice;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceBackofficeController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.invoice-backoffice', [
            'settings' => $this->settings(),
            'backofficeKey' => '',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {

        $validated = $request->validate([
            'is_enabled' => ['nullable', 'boolean'],
            'min_purchase_amount' => ['required', 'numeric', 'min:0'],
            'invoice_age_policy' => ['required', 'in:none,same_day,last_24_hours,days'],
            'max_invoice_age_days' => ['nullable', 'integer', 'min:0', 'max:30'],
            'validation_mode' => ['nullable', 'in:api,manual'],
        ]);

        $settings = InvoiceGoalSetting::query()->firstOrCreate([], [
            'is_enabled' => true,
            'goal_value' => 1,
            'min_purchase_amount' => 25,
            'invoice_age_policy' => 'none',
            'max_invoice_age_days' => 1,
            'one_invoice_per_day' => false,
            'validation_mode' => 'api',
        ]);

        $settings->forceFill([
            'is_enabled' => $request->boolean('is_enabled'),
            'min_purchase_amount' => $validated['min_purchase_amount'],
            'invoice_age_policy' => $validated['invoice_age_policy'],
            'max_invoice_age_days' => $validated['max_invoice_age_days'] ?? 0,
            'validation_mode' => $validated['validation_mode'] ?? 'api',
        ])->save();

        SiteSetting::set('invoice_age_policy', $settings->invoice_age_policy);
        SiteSetting::set('invoice_min_purchase_amount', (string) $settings->min_purchase_amount);
        SiteSetting::set('invoice_scan_enabled', $settings->is_enabled ? '1' : '0');

        return redirect()
            ->route('admin.invoice-backoffice')
            ->with('status', 'Configuracion guardada.');
    }

    public function invoices(Request $request): View
    {
        $invoices = RegisteredInvoice::with('user')
            ->orderByDesc('created_at')
            ->paginate(50);

        return view('admin.invoices', compact('invoices'));
    }

    public function winners(Request $request): View
    {
        // Primeros 100: únicos por cédula y por número de factura, ordenados por fecha
        $seen_cedulas  = [];
        $seen_invoices = [];
        $winners       = [];

        RegisteredInvoice::with('user')
            ->whereNotNull('invoice_number')
            ->whereHas('user', fn ($q) => $q->whereNotNull('cedula'))
            ->orderBy('created_at')
            ->chunk(500, function ($batch) use (&$seen_cedulas, &$seen_invoices, &$winners) {
                foreach ($batch as $inv) {
                    if (count($winners) >= 100) return false;

                    $cedula  = $inv->user?->cedula;
                    $invoice = $inv->invoice_number;

                    if (! $cedula || ! $invoice) continue;
                    if (isset($seen_cedulas[$cedula]))  continue;
                    if (isset($seen_invoices[$invoice])) continue;

                    $seen_cedulas[$cedula]   = true;
                    $seen_invoices[$invoice] = true;
                    $winners[] = $inv;
                }
            });

        return view('admin.winners', ['winners' => $winners]);
    }

    private function authorizeAccess(Request $request): void
    {
        $expectedKey = (string) config('contest.backoffice_key', '');
        $providedKey = (string) $request->query('key', $request->input('key', ''));

        abort_unless($expectedKey !== '' && hash_equals($expectedKey, $providedKey), 403);
    }

    private function settings(): InvoiceGoalSetting
    {
        return InvoiceGoalSetting::query()->firstOrCreate([], [
            'is_enabled' => true,
            'goal_value' => 1,
            'min_purchase_amount' => 25,
            'invoice_age_policy' => 'none',
            'max_invoice_age_days' => 1,
            'one_invoice_per_day' => false,
            'validation_mode' => 'api',
        ]);
    }
}
