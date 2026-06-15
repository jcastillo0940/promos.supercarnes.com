<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvoiceGoalSetting;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceBackofficeController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAccess($request);

        return view('admin.invoice-backoffice', [
            'settings' => $this->settings(),
            'backofficeKey' => (string) config('contest.backoffice_key', ''),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeAccess($request);

        $validated = $request->validate([
            'is_enabled' => ['nullable', 'boolean'],
            'min_purchase_amount' => ['required', 'numeric', 'min:0'],
            'invoice_age_policy' => ['required', 'in:none,same_day,last_24_hours'],
            'max_invoice_age_days' => ['nullable', 'integer', 'min:0', 'max:30'],
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
        ])->save();

        SiteSetting::set('invoice_age_policy', $settings->invoice_age_policy);
        SiteSetting::set('invoice_min_purchase_amount', (string) $settings->min_purchase_amount);
        SiteSetting::set('invoice_scan_enabled', $settings->is_enabled ? '1' : '0');

        return redirect()
            ->route('admin.invoice-backoffice', ['key' => (string) config('contest.backoffice_key', '')])
            ->with('status', 'Configuracion guardada.');
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
