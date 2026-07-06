<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\InvoiceGoalSetting;
use App\Models\AuditLog;
use App\Models\PromoWinner;
use App\Models\RegisteredInvoice;
use App\Models\User;
use App\Models\SiteSetting;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InvoiceBackofficeController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.invoice-backoffice', [
            'settings' => $this->settings(),
            'campaigns' => Campaign::query()->orderByDesc('status')->orderBy('sort_order')->orderByDesc('starts_at')->get(),
            'backofficeKey' => (string) config('contest.backoffice_key', ''),
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

    public function updateCampaigns(Request $request): RedirectResponse
    {
        $this->authorizeAccess($request);

        $payload = $request->validate([
            'campaigns' => ['required', 'array'],
            'campaigns.*.id' => ['required', 'integer', 'exists:campaigns,id'],
            'campaigns.*.name' => ['required', 'string', 'max:150'],
            'campaigns.*.slug' => ['required', 'string', 'max:120'],
            'campaigns.*.description' => ['nullable', 'string'],
            'campaigns.*.status' => ['required', 'in:draft,active,paused,archived'],
            'campaigns.*.is_listed' => ['nullable', 'boolean'],
            'campaigns.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'campaigns.*.hero_image_url' => ['nullable', 'string', 'max:255'],
            'campaigns.*.card_image_url' => ['nullable', 'string', 'max:255'],
            'campaigns.*.starts_at' => ['nullable', 'date'],
            'campaigns.*.ends_at' => ['nullable', 'date'],
            'campaigns.*.invoice_min_amount_for_shot' => ['nullable', 'numeric', 'min:0'],
            'campaigns.*.amount_per_point' => ['nullable', 'numeric', 'min:0'],
            'campaigns.*.points_per_block' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'campaigns.*.daily_max_points' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'campaigns.*.daily_max_invoices' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'campaigns.*.coupon_ttl_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
            'campaigns.*.games_enabled' => ['nullable', 'boolean'],
            'campaigns.*.major_prizes_enabled' => ['nullable', 'boolean'],
            'campaigns.*.invoice_scan_enabled' => ['nullable', 'boolean'],
            'campaigns.*.redemption_enabled' => ['nullable', 'boolean'],
            'campaigns.*.entry_threshold_amount' => ['nullable', 'numeric', 'min:0'],
            'campaigns.*.entry_requires_approval' => ['nullable', 'boolean'],
        ]);

        foreach ($payload['campaigns'] as $campaignData) {
            $campaign = Campaign::query()->findOrFail($campaignData['id']);

            $campaign->forceFill([
                'name' => $campaignData['name'],
                'slug' => $campaignData['slug'],
                'description' => $campaignData['description'] ?? null,
                'status' => $campaignData['status'],
                'is_listed' => (bool) ($campaignData['is_listed'] ?? false),
                'sort_order' => (int) ($campaignData['sort_order'] ?? 0),
                'hero_image_url' => $campaignData['hero_image_url'] ?? null,
                'card_image_url' => $campaignData['card_image_url'] ?? null,
                'starts_at' => $campaignData['starts_at'] ?? $campaign->starts_at,
                'ends_at' => $campaignData['ends_at'] ?? $campaign->ends_at,
                'invoice_min_amount_for_shot' => $campaignData['invoice_min_amount_for_shot'] ?? $campaign->invoice_min_amount_for_shot,
                'amount_per_point' => $campaignData['amount_per_point'] ?? $campaign->amount_per_point,
                'points_per_block' => $campaignData['points_per_block'] ?? $campaign->points_per_block,
                'daily_max_points' => $campaignData['daily_max_points'] ?? $campaign->daily_max_points,
                'daily_max_invoices' => $campaignData['daily_max_invoices'] ?? $campaign->daily_max_invoices,
                'coupon_ttl_hours' => $campaignData['coupon_ttl_hours'] ?? $campaign->coupon_ttl_hours,
                'games_enabled' => (bool) ($campaignData['games_enabled'] ?? false),
                'major_prizes_enabled' => (bool) ($campaignData['major_prizes_enabled'] ?? false),
                'invoice_scan_enabled' => (bool) ($campaignData['invoice_scan_enabled'] ?? false),
                'redemption_enabled' => (bool) ($campaignData['redemption_enabled'] ?? false),
                'entry_threshold_amount' => $campaignData['entry_threshold_amount'] ?? $campaign->entry_threshold_amount,
                'entry_requires_approval' => (bool) ($campaignData['entry_requires_approval'] ?? false),
            ])->save();
        }

        return redirect()
            ->route('admin.invoice-backoffice')
            ->with('status', 'Promociones actualizadas.');
    }

    public function storeCampaign(Request $request): RedirectResponse
    {
        $this->authorizeAccess($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:120', 'alpha_dash', Rule::unique('campaigns', 'slug')],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,active,paused,archived'],
            'is_listed' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'hero_image_url' => ['nullable', 'string', 'max:255'],
            'card_image_url' => ['nullable', 'string', 'max:255'],
            'invoice_min_amount_for_shot' => ['nullable', 'numeric', 'min:0'],
            'amount_per_point' => ['nullable', 'numeric', 'min:0'],
            'points_per_block' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'daily_max_points' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'daily_max_invoices' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'coupon_ttl_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
            'games_enabled' => ['nullable', 'boolean'],
            'major_prizes_enabled' => ['nullable', 'boolean'],
            'invoice_scan_enabled' => ['nullable', 'boolean'],
            'redemption_enabled' => ['nullable', 'boolean'],
            'entry_threshold_amount' => ['nullable', 'numeric', 'min:0'],
            'entry_requires_approval' => ['nullable', 'boolean'],
        ]);

        $slug = $validated['slug'] ?: str($validated['name'])->slug()->toString();

        if (Campaign::query()->where('slug', $slug)->exists()) {
            return back()
                ->withErrors(['slug' => 'Ya existe una promocion con ese slug.'])
                ->withInput();
        }

        Campaign::query()->create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'is_listed' => $request->boolean('is_listed'),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'starts_at' => $validated['starts_at'] ?? now(),
            'ends_at' => $validated['ends_at'] ?? now()->addYear(),
            'hero_image_url' => $validated['hero_image_url'] ?? null,
            'card_image_url' => $validated['card_image_url'] ?? null,
            'invoice_min_amount_for_shot' => $validated['invoice_min_amount_for_shot'] ?? 25,
            'amount_per_point' => $validated['amount_per_point'] ?? 25,
            'points_per_block' => $validated['points_per_block'] ?? 1,
            'daily_max_points' => $validated['daily_max_points'] ?? 1000,
            'daily_max_invoices' => $validated['daily_max_invoices'] ?? 100,
            'coupon_ttl_hours' => $validated['coupon_ttl_hours'] ?? 72,
            'games_enabled' => $request->boolean('games_enabled'),
            'major_prizes_enabled' => $request->boolean('major_prizes_enabled'),
            'invoice_scan_enabled' => $request->boolean('invoice_scan_enabled', true),
            'redemption_enabled' => $request->boolean('redemption_enabled'),
            'entry_threshold_amount' => $validated['entry_threshold_amount'] ?? null,
            'entry_requires_approval' => $request->boolean('entry_requires_approval'),
        ]);

        return redirect()
            ->route('admin.invoice-backoffice')
            ->with('status', 'Promocion creada correctamente.');
    }

    public function invoices(Request $request): View
    {
        $query = RegisteredInvoice::with(['user', 'branch', 'campaign'])
            ->when($request->filled('campaign_id'), function ($query) use ($request) {
                $query->where('campaign_id', (int) $request->input('campaign_id'));
            })
            ->when($request->filled('name'), function ($query) use ($request) {
                $term = trim((string) $request->input('name'));
                $query->whereHas('user', function ($userQuery) use ($term) {
                    $userQuery->where('full_name', 'like', "%{$term}%")
                        ->orWhere('name', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('cedula'), function ($query) use ($request) {
                $term = trim((string) $request->input('cedula'));
                $query->whereHas('user', fn ($userQuery) => $userQuery->where('cedula', 'like', "%{$term}%"));
            })
            ->when($request->filled('date_from'), function ($query) use ($request) {
                $query->whereDate('created_at', '>=', $request->input('date_from'));
            })
            ->when($request->filled('date_to'), function ($query) use ($request) {
                $query->whereDate('created_at', '<=', $request->input('date_to'));
            });

        $invoices = $query
            ->orderByDesc('created_at')
            ->paginate(50);

        $invoices->appends($request->only(['campaign_id', 'name', 'cedula', 'date_from', 'date_to']));

        return view('admin.invoices', [
            'invoices' => $invoices,
            'campaigns' => Campaign::query()->orderBy('name')->get(),
        ]);
    }

    public function winners(Request $request): View
    {
        $campaignId = $request->filled('campaign_id') ? (int) $request->input('campaign_id') : null;

        $winners = PromoWinner::query()
            ->with(['user'])
            ->when($campaignId, function ($query) use ($campaignId) {
                $query->whereHas('user.invoices', fn ($invoiceQuery) => $invoiceQuery->where('campaign_id', $campaignId));
            })
            ->where('status', 'selected')
            ->orderBy('selected_at')
            ->orderBy('id')
            ->limit(100)
            ->get();

        $selectedUserIds = $winners->pluck('user_id')->all();

        $selectedInvoiceNumbers = RegisteredInvoice::query()
            ->whereIn('user_id', $selectedUserIds)
            ->whereNotNull('invoice_number')
            ->pluck('invoice_number')
            ->all();

        $availableInvoices = RegisteredInvoice::with(['user', 'campaign'])
            ->with('campaign')
            ->when($campaignId, fn ($query) => $query->where('campaign_id', $campaignId))
            ->whereNotNull('invoice_number')
            ->whereHas('user', fn ($q) => $q->whereNotNull('cedula'))
            ->whereNotIn('user_id', $selectedUserIds)
            ->when(count($selectedInvoiceNumbers) > 0, function ($query) use ($selectedInvoiceNumbers) {
                $query->whereNotIn('invoice_number', $selectedInvoiceNumbers);
            })
            ->orderByDesc('created_at')
            ->paginate(25, ['*'], 'available_page');

        return view('admin.winners', [
            'winners' => $winners,
            'availableInvoices' => $availableInvoices,
            'campaigns' => Campaign::query()->orderBy('name')->get(),
            'selectedCampaignId' => $campaignId,
        ]);
    }

    public function selectWinner(Request $request, RegisteredInvoice $invoice): RedirectResponse
    {
        $invoice->loadMissing('user');

        abort_unless($invoice->invoice_number && $invoice->user?->cedula, 422, 'La factura no cumple los criterios mínimos.');

        if (PromoWinner::query()->where('status', 'selected')->where('user_id', $invoice->user_id)->exists()) {
            return back()->withErrors(['winner' => 'Ese participante ya está marcado como ganador.']);
        }

        if (PromoWinner::query()->where('status', 'selected')->count() >= 100) {
            return back()->withErrors(['winner' => 'Ya hay 100 ganadores seleccionados.']);
        }

        $alreadyUsedInvoice = PromoWinner::query()
            ->where('status', 'selected')
            ->whereHas('user.invoices', fn ($query) => $query->where('invoice_number', $invoice->invoice_number))
            ->exists();

        if ($alreadyUsedInvoice) {
            return back()->withErrors(['winner' => 'Ese número de factura ya está asociado a un ganador.']);
        }

        DB::transaction(function () use ($invoice): void {
            $position = PromoWinner::query()->where('status', 'selected')->count() + 1;

            PromoWinner::query()->create([
                'phase_id' => 1,
                'user_id' => $invoice->user_id,
                'leaderboard_position' => $position,
                'total_points' => 0,
                'exact_hits' => 0,
                'invoice_count' => 1,
                'invoice_total_amount' => $invoice->purchase_amount,
                'selection_reason' => 'manual',
                'status' => 'selected',
                'ranking_timestamp' => $invoice->created_at,
                'selected_at' => now(),
                'created_by' => auth()->id(),
                'notes' => $invoice->dad_reason,
            ]);
        });

        return back()->with('status', 'Ganador agregado correctamente.');
    }

    public function removeWinner(PromoWinner $winner): RedirectResponse
    {
        $winner->delete();

        return back()->with('status', 'Ganador removido correctamente.');
    }

    public function customerHistory(User $user): View
    {
        $user->load([
            'invoices.campaign',
            'invoices' => fn ($query) => $query->orderByDesc('created_at'),
        ]);

        $winner = PromoWinner::query()
            ->where('user_id', $user->id)
            ->where('status', 'selected')
            ->first();

        $campaignTotals = RegisteredInvoice::query()
            ->where('user_id', $user->id)
            ->selectRaw('campaign_id, SUM(purchase_amount) as total')
            ->groupBy('campaign_id')
            ->pluck('total', 'campaign_id');

        return view('admin.customer-history', [
            'user' => $user,
            'invoices' => $user->invoices,
            'winner' => $winner,
            'campaignTotals' => $campaignTotals,
            'campaigns' => Campaign::query()->orderBy('name')->get(),
        ]);
    }

    public function markCustomerAsWinner(User $user): RedirectResponse
    {
        if (PromoWinner::query()->where('status', 'selected')->count() >= 100) {
            return back()->withErrors(['winner' => 'Ya hay 100 ganadores seleccionados.']);
        }

        if (PromoWinner::query()->where('status', 'selected')->where('user_id', $user->id)->exists()) {
            return back()->with('status', 'Este cliente ya está marcado como ganador.');
        }

        $invoice = $user->invoices()->whereNotNull('invoice_number')->orderByDesc('created_at')->first();

        if (! $invoice) {
            return back()->withErrors(['winner' => 'El cliente no tiene facturas elegibles.']);
        }

        DB::transaction(function () use ($user, $invoice): void {
            $position = PromoWinner::query()->where('status', 'selected')->count() + 1;

            PromoWinner::query()->create([
                'phase_id' => 1,
                'user_id' => $user->id,
                'leaderboard_position' => $position,
                'total_points' => 0,
                'exact_hits' => 0,
                'invoice_count' => $user->invoices()->count(),
                'invoice_total_amount' => (float) $user->invoices()->sum('purchase_amount'),
                'selection_reason' => 'manual',
                'status' => 'selected',
                'ranking_timestamp' => $invoice->created_at,
                'selected_at' => now(),
                'created_by' => auth()->id(),
                'notes' => 'Marcado manualmente desde el historial del cliente.',
            ]);
        });

        return back()->with('status', 'Cliente marcado como ganador.');
    }

    public function unmarkCustomerAsWinner(User $user): RedirectResponse
    {
        PromoWinner::query()
            ->where('user_id', $user->id)
            ->where('status', 'selected')
            ->delete();

        return back()->with('status', 'Cliente marcado como no ganador.');
    }

    public function prizeDeliveryIndex(Request $request): View
    {
        $winner = null;
        if ($request->filled('qr') || $request->filled('code')) {
            $winner = $this->findDeliveryWinner((string) $request->input('qr', $request->input('code', '')));
        }

        return view('admin.prize-delivery', [
            'winner' => $winner,
        ]);
    }

    public function audit(Request $request): View
    {
        $query = AuditLog::query()
            ->with(['user.branch'])
            ->whereIn('event_type', [
                'prize_delivered',
                'prize_delivery_rejected',
                'prize_delivery_override',
            ])
            ->when($request->filled('from'), fn ($query) => $query->whereDate('created_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('created_at', '<=', $request->input('to')))
            ->when($request->filled('user'), function ($query) use ($request): void {
                $term = trim((string) $request->input('user'));
                $query->whereHas('user', function ($userQuery) use ($term): void {
                    $userQuery->where('name', 'like', "%{$term}%")
                        ->orWhere('full_name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('cedula'), function ($query) use ($request): void {
                $term = trim((string) $request->input('cedula'));
                $query->whereHas('user', fn ($userQuery) => $userQuery->where('cedula', 'like', "%{$term}%"));
            })
            ->when($request->filled('branch'), function ($query) use ($request): void {
                $term = trim((string) $request->input('branch'));
                $query->whereHas('user.branch', function ($branchQuery) use ($term): void {
                    $branchQuery->where('name', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%");
                });
            });

        $entries = $query->orderByDesc('created_at')->paginate(30);
        $entries->appends($request->only(['from', 'to', 'user', 'cedula', 'branch']));

        $summary = [
            'delivered' => (clone $query)->where('event_type', 'prize_delivered')->count(),
            'rejected' => (clone $query)->where('event_type', 'prize_delivery_rejected')->count(),
            'overrides' => (clone $query)->where('event_type', 'prize_delivery_override')->count(),
        ];

        return view('admin.audit', [
            'entries' => $entries,
            'summary' => $summary,
        ]);
    }

    public function prizeDeliveryLookup(Request $request): View|RedirectResponse
    {
        $validated = $request->validate([
            'qr_code' => ['required', 'string', 'max:255'],
            'cedula' => ['nullable', 'string', 'max:40'],
        ]);

        $winner = $this->findDeliveryWinner($validated['qr_code']);

        if (! $winner) {
            Audit::log(
                'prize_delivery_rejected',
                'promo_winner',
                null,
                $request->user(),
                $request,
                [
                    'reason' => 'qr_not_found',
                    'qr_code' => $validated['qr_code'],
                    'cedula' => $validated['cedula'] ?? null,
                ]
            );
            return view('admin.prize-delivery-rejected', [
                'reason' => 'No encontramos un ganador válido para ese QR.',
                'reason_code' => 'qr_not_found',
                'qrCode' => $validated['qr_code'],
                'cedula' => $validated['cedula'] ?? null,
            ]);
        }

        if ($winner->delivery_status === 'delivered' || $winner->prize_delivered_at) {
            Audit::log(
                'prize_delivery_rejected',
                'promo_winner',
                $winner->id,
                $request->user(),
                $request,
                [
                    'reason' => 'qr_reused',
                    'qr_code' => $validated['qr_code'],
                    'cedula' => $validated['cedula'] ?? null,
                ]
            );
            return view('admin.prize-delivery-rejected', [
                'reason' => 'Ese premio ya fue entregado anteriormente.',
                'reason_code' => 'qr_reused',
                'qrCode' => $validated['qr_code'],
                'winner' => $winner,
                'cedula' => $validated['cedula'] ?? null,
            ]);
        }

        if (! empty($validated['cedula']) && trim((string) $validated['cedula']) !== trim((string) $winner->user?->cedula)) {
            Audit::log(
                'prize_delivery_rejected',
                'promo_winner',
                $winner->id,
                $request->user(),
                $request,
                [
                    'reason' => 'cedula_mismatch',
                    'qr_code' => $validated['qr_code'],
                    'cedula' => $validated['cedula'],
                    'winner_cedula' => $winner->user?->cedula,
                ]
            );
            return view('admin.prize-delivery-rejected', [
                'reason' => 'La cédula no coincide con la del ganador.',
                'reason_code' => 'cedula_mismatch',
                'qrCode' => $validated['qr_code'],
                'winner' => $winner,
                'cedula' => $validated['cedula'],
            ]);
        }

        return view('admin.prize-delivery', [
            'winner' => $winner,
        ]);
    }

    public function prizeDeliveryOverride(Request $request, PromoWinner $winner): RedirectResponse|View
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Solo el super admin puede reabrir un rechazo.');
        abort_unless($winner->status === 'selected', 422, 'Solo se puede reabrir un ganador válido.');
        abort_if($winner->delivery_status === 'delivered' || $winner->prize_delivered_at, 422, 'El premio ya fue entregado y no puede reabrirse.');

        $validated = $request->validate([
            'override_reason' => ['required', 'string', 'min:10', 'max:2000'],
            'corrected_cedula' => ['nullable', 'string', 'max:40'],
        ]);

        Audit::log(
            'prize_delivery_override',
            'promo_winner',
            $winner->id,
            $request->user(),
            $request,
            [
                'override_reason' => $validated['override_reason'],
                'corrected_cedula' => $validated['corrected_cedula'] ?? null,
                'winner_cedula' => $winner->user?->cedula,
                'delivery_status' => $winner->delivery_status,
                'prize_delivered_at' => $winner->prize_delivered_at?->toIso8601String(),
            ]
        );

        return view('admin.prize-delivery', [
            'winner' => $winner->load('user.invoices'),
            'overrideReason' => $validated['override_reason'],
            'correctedCedula' => $validated['corrected_cedula'] ?? null,
        ]);
    }

    public function prizeDeliveryStore(Request $request, PromoWinner $winner): RedirectResponse
    {
        abort_unless($winner->status === 'selected', 422, 'Solo se puede entregar premio a ganadores.');

        $validated = $request->validate([
            'id_card_photo' => ['required', 'image', 'max:8192'],
            'delivery_photo' => ['required', 'image', 'max:8192'],
            'delivery_notes' => ['nullable', 'string', 'max:2000'],
            'winner_cedula' => ['required', 'string', 'max:40'],
            'delivery_confirmation' => ['accepted'],
        ]);

        abort_unless(trim((string) $validated['winner_cedula']) === trim((string) $winner->user?->cedula), 422, 'La cédula no coincide con la del ganador.');
        abort_if($winner->delivery_status === 'delivered' || $winner->prize_delivered_at, 422, 'Ese premio ya fue entregado previamente.');
        abort_unless($request->user()?->isSupervisor() || $request->user()?->isAdmin(), 403, 'Solo supervisor o gerente pueden confirmar la entrega.');

        $idCardPath = $request->file('id_card_photo')->store('prize-deliveries/id-card');
        $deliveryPath = $request->file('delivery_photo')->store('prize-deliveries/delivery');

        $winner->forceFill([
            'delivery_status' => 'delivered',
            'delivery_qr_scanned_at' => now(),
            'id_card_photo_path' => $idCardPath,
            'delivery_photo_path' => $deliveryPath,
            'delivery_notes' => $validated['delivery_notes'] ?? null,
            'delivered_by' => auth()->id(),
            'prize_delivered_at' => now(),
        ])->save();

        Audit::log(
            'prize_delivered',
            'promo_winner',
            $winner->id,
            $request->user(),
            $request,
            [
                'winner_name' => $winner->user?->full_name ?? $winner->user?->name,
                'cedula' => $winner->user?->cedula,
                'invoice_number' => optional($winner->user?->invoices?->first())->invoice_number,
                'id_card_photo_path' => $idCardPath,
                'delivery_photo_path' => $deliveryPath,
                'delivery_status' => $winner->delivery_status,
                'prize_delivered_at' => $winner->prize_delivered_at?->toIso8601String(),
                'delivered_by_role' => $request->user()?->role,
            ]
        );

        return redirect()
            ->route('admin.prize-delivery')
            ->with('status', 'Premio marcado como entregado.');
    }

    public function prizeDeliveryFind(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'qr_code' => ['required', 'string', 'max:255'],
            'cedula' => ['nullable', 'string', 'max:40'],
        ]);

        $winner = $this->findDeliveryWinner($validated['qr_code']);

        if (! $winner) {
            Audit::log(
                'prize_delivery_rejected',
                'promo_winner',
                null,
                $request->user(),
                $request,
                [
                    'reason' => 'qr_not_found',
                    'qr_code' => $validated['qr_code'],
                    'cedula' => $validated['cedula'] ?? null,
                ]
            );
            return response()->json([
                'found' => false,
                'message' => 'No encontramos un ganador válido para ese QR.',
            ], 404);
        }

        if (! empty($validated['cedula']) && trim((string) $validated['cedula']) !== trim((string) $winner->user?->cedula)) {
            Audit::log(
                'prize_delivery_rejected',
                'promo_winner',
                $winner->id,
                $request->user(),
                $request,
                [
                    'reason' => 'cedula_mismatch',
                    'qr_code' => $validated['qr_code'],
                    'cedula' => $validated['cedula'],
                    'winner_cedula' => $winner->user?->cedula,
                ]
            );
            return response()->json([
                'found' => false,
                'message' => 'La cédula no coincide con la del ganador.',
            ], 422);
        }

        return response()->json([
            'found' => true,
            'message' => 'Ganador validado correctamente.',
            'winner' => [
                'id' => $winner->id,
                'name' => $winner->user?->full_name ?? $winner->user?->name ?? '—',
                'cedula' => $winner->user?->cedula ?? '—',
                'email' => $winner->user?->email ?? '—',
                'invoice_number' => optional($winner->user?->invoices?->first())->invoice_number ?? '—',
                'status_label' => $winner->delivery_status === 'delivered' ? 'Entregado' : 'Pendiente',
                'delivery_status' => $winner->delivery_status,
                'prize_delivered_at' => optional($winner->prize_delivered_at)?->format('d/m/Y H:i'),
                'id_card_photo_url' => $winner->id_card_photo_path ? route('admin.media', ['path' => $winner->id_card_photo_path]) : null,
                'delivery_photo_url' => $winner->delivery_photo_path ? route('admin.media', ['path' => $winner->delivery_photo_path]) : null,
                'delivery_notes' => $winner->delivery_notes,
                'qr_used' => (bool) $winner->prize_delivered_at,
            ],
        ]);
    }

    private function findDeliveryWinner(string $code): ?PromoWinner
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }

        $normalizedCode = preg_replace('/\s+/', '', $code);

        return PromoWinner::query()
            ->with('user.invoices')
            ->where('status', 'selected')
            ->where(function ($query) use ($normalizedCode): void {
                $query->whereHas('user.invoices', function ($invoiceQuery) use ($normalizedCode): void {
                    $invoiceQuery->where('cufe', $normalizedCode)
                        ->orWhere('invoice_number', $normalizedCode);
                });
            })
            ->first();
    }

    public function media(Request $request, string $path)
    {
        abort_unless($request->user()?->isAdmin() || $request->user()?->isSupervisor(), 403);

        $baseDirectory = realpath(storage_path('app'));
        abort_unless($baseDirectory, 404);

        $relativePath = ltrim($path, '/\\');
        $resolvedPath = realpath(storage_path('app').DIRECTORY_SEPARATOR.$relativePath);
        abort_unless($resolvedPath && str_starts_with($resolvedPath, $baseDirectory.DIRECTORY_SEPARATOR), 404);
        abort_unless(is_file($resolvedPath), 404);

        Audit::log(
            'prize_media_viewed',
            'promo_winner',
            null,
            $request->user(),
            $request,
            [
                'path' => $relativePath,
            ]
        );

        return response()->file($resolvedPath, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function dashboardData(): array
    {
        $invoicesByBranch = RegisteredInvoice::query()
            ->selectRaw('COALESCE(branches.name, "Sin sucursal") as label, COUNT(*) as total')
            ->leftJoin('branches', 'branches.id', '=', 'registered_invoices.branch_id')
            ->groupBy('label')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($row) => ['label' => $row->label, 'total' => (int) $row->total])
            ->values()
            ->all();

        $dailyInvoices = RegisteredInvoice::query()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total, SUM(purchase_amount) as amount')
            ->whereDate('created_at', '>=', now()->subDays(7)->startOfDay())
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($row) => [
                'day' => $row->day,
                'total' => (int) $row->total,
                'amount' => (float) $row->amount,
            ])
            ->values()
            ->all();

        $winnersCount = PromoWinner::query()->where('status', 'selected')->count();
        $deliveredCount = PromoWinner::query()->whereNotNull('prize_delivered_at')->count();
        $participantsCount = RegisteredInvoice::query()->distinct('user_id')->count('user_id');
        $nonWinnersCount = max($participantsCount - $winnersCount, 0);
        $totalInvoiceAmount = (float) RegisteredInvoice::query()->sum('purchase_amount');
        $topBranch = RegisteredInvoice::query()
            ->selectRaw('COALESCE(branches.name, "Sin sucursal") as label, COUNT(*) as total')
            ->leftJoin('branches', 'branches.id', '=', 'registered_invoices.branch_id')
            ->groupBy('label')
            ->orderByDesc('total')
            ->first();

        return [
            'kpis' => [
                'winners' => $winnersCount,
                'delivered' => $deliveredCount,
                'participants' => $participantsCount,
                'non_winners' => $nonWinnersCount,
                'participation_pct' => $participantsCount > 0 ? round(($winnersCount / $participantsCount) * 100, 1) : 0,
                'total_invoice_amount' => $totalInvoiceAmount,
                'top_branch' => $topBranch?->label ?? 'Sin datos',
                'top_branch_total' => (int) ($topBranch->total ?? 0),
            ],
            'charts' => [
                'branches' => $invoicesByBranch,
                'daily' => $dailyInvoices,
            ],
        ];
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
