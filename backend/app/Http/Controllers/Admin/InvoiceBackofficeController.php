<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Campaign;
use App\Models\FraudFlag;
use App\Models\InvoiceGoalSetting;
use App\Models\AuditLog;
use App\Models\PromoWinner;
use App\Models\RegisteredInvoice;
use App\Models\User;
use App\Models\SiteSetting;
use App\Support\Audit;
use App\Support\BlacklistService;
use App\Support\ContestInvoiceRegistrationService;
use App\Support\FraudDetectionService;
use App\Support\ProductRankingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Support\CampaignLaunchGuard;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InvoiceBackofficeController extends Controller
{
    public function dashboard(): View
    {
        return view('admin.dashboard', [
            'dashboard' => $this->dashboardData(),
        ]);
    }

    public function index(Request $request): View
    {
        return view('admin.invoice-backoffice-list', [
            'settings' => $this->settings(),
            'campaigns' => Campaign::query()->with('productRules')->orderByDesc('status')->orderBy('sort_order')->orderByDesc('starts_at')->get(),
            'backofficeKey' => (string) config('contest.backoffice_key', ''),
        ]);
    }

    public function editCampaign(Request $request, Campaign $campaign): View
    {
        return view('admin.invoice-backoffice', [
            'settings' => $this->settings(),
            'campaigns' => Campaign::query()->with('productRules')->whereKey($campaign->id)->get(),
            'backofficeKey' => (string) config('contest.backoffice_key', ''),
            'editingCampaign' => true,
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
            'campaigns.*.participation_mode' => ['required', 'in:points,threshold_form,product_ranking'],
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
            'campaigns.*.terms_text' => ['nullable', 'string'],
            'campaigns.*.terms_version' => ['nullable', 'string', 'max:80'],
            'campaigns.*.terms_approved_at' => ['nullable', 'date'],
            'campaigns.*.delivery_location' => ['nullable', 'string', 'max:255'],
            'campaigns.*.delivery_deadline' => ['nullable', 'date'],
            'campaigns.*.delivery_requirements' => ['nullable', 'string', 'max:500'],
            'campaigns.*.product_rules_text' => ['nullable', 'string'],
        ]);

        foreach ($payload['campaigns'] as $campaignData) {
            $campaign = Campaign::query()->findOrFail($campaignData['id']);
            $mode = $campaignData['participation_mode'] ?? $campaign->participation_mode ?? 'points';
            $thresholdAmount = $campaignData['entry_threshold_amount'] ?? $campaign->entry_threshold_amount;

            if ($mode === 'product_ranking' && (($campaignData['status'] ?? $campaign->status) === 'active' || (bool) ($campaignData['is_listed'] ?? false))) {
                $hasRules = trim((string) ($campaignData['product_rules_text'] ?? '')) !== '' || $campaign->productRules()->where('is_active', true)->exists();
                $hasTerms = ($campaignData['terms_text'] ?? $campaign->terms_text) && ($campaignData['terms_version'] ?? $campaign->terms_version) && ($campaignData['terms_approved_at'] ?? $campaign->terms_approved_at);
                $hasDelivery = ($campaignData['delivery_location'] ?? $campaign->delivery_location) && ($campaignData['delivery_deadline'] ?? $campaign->delivery_deadline);
                if (! $hasRules || ! $hasTerms || ! $hasDelivery) {
                    throw ValidationException::withMessages(['campaign' => 'Completa códigos oficiales, términos aprobados y datos de entrega antes de publicar esta promoción.']);
                }
            }

            if ($mode === 'threshold_form' && ($thresholdAmount === null || $thresholdAmount === '')) {
                $thresholdAmount = 100;
            }

            $campaign->forceFill([
                'name' => $campaignData['name'],
                'slug' => $campaignData['slug'],
                'description' => $campaignData['description'] ?? null,
                'status' => $campaignData['status'],
                'participation_mode' => $mode,
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
                'entry_threshold_amount' => $thresholdAmount,
                'entry_requires_approval' => $mode === 'threshold_form'
                    ? (bool) ($campaignData['entry_requires_approval'] ?? false)
                    : (bool) ($campaignData['entry_requires_approval'] ?? false),
                'terms_text' => $campaignData['terms_text'] ?? null,
                'terms_version' => $campaignData['terms_version'] ?? null,
                'terms_approved_at' => $campaignData['terms_approved_at'] ?? null,
                'delivery_location' => $campaignData['delivery_location'] ?? null,
                'delivery_deadline' => $campaignData['delivery_deadline'] ?? null,
                'delivery_requirements' => $campaignData['delivery_requirements'] ?? null,
            ])->save();

            if ($mode === 'product_ranking') {
                $this->syncProductRules($campaign, (string) ($campaignData['product_rules_text'] ?? ''));
            }

            if ($campaign->status === 'active' || $campaign->is_listed) {
                app(CampaignLaunchGuard::class)->assertCanPublish($campaign);
            }
        }

        return redirect()
            ->route('admin.invoice-backoffice')
            ->with('status', 'Promociones actualizadas.');
    }

    public function toggleCampaignStatus(Request $request, Campaign $campaign): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:active,paused'],
        ]);

        if ($validated['status'] === 'active') {
            app(CampaignLaunchGuard::class)->assertCanPublish($campaign);
        }

        $campaign->forceFill([
            'status' => $validated['status'],
            'is_listed' => $validated['status'] === 'active',
            'invoice_scan_enabled' => $validated['status'] === 'active' && $campaign->participation_mode === 'product_ranking'
                ? true
                : $campaign->invoice_scan_enabled,
        ])->save();

        return redirect()
            ->route('admin.invoice-backoffice')
            ->with('status', $validated['status'] === 'active' ? 'Promocion activada.' : 'Promocion desactivada.');
    }

    public function storeCampaign(Request $request): RedirectResponse
    {
        $this->authorizeAccess($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:120', 'alpha_dash', Rule::unique('campaigns', 'slug')],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,active,paused,archived'],
            'participation_mode' => ['required', 'in:points,threshold_form,product_ranking'],
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
        $mode = $validated['participation_mode'];
        $thresholdAmount = $validated['entry_threshold_amount'] ?? null;

        if ($mode === 'threshold_form' && ($thresholdAmount === null || $thresholdAmount === '')) {
            $thresholdAmount = 100;
        }

        if (Campaign::query()->where('slug', $slug)->exists()) {
            return back()
                ->withErrors(['slug' => 'Ya existe una promocion con ese slug.'])
                ->withInput();
        }

        if ($mode === 'product_ranking' && ($validated['status'] === 'active' || $request->boolean('is_listed'))) {
            throw ValidationException::withMessages(['campaign' => 'Crea la promoción como borrador y completa primero los códigos, términos y datos de entrega.']);
        }

        $newCampaign = Campaign::query()->create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'participation_mode' => $mode,
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
            'entry_threshold_amount' => $thresholdAmount,
            'entry_requires_approval' => $request->boolean('entry_requires_approval'),
        ]);

        if ($newCampaign->status === 'active' || $newCampaign->is_listed) {
            app(CampaignLaunchGuard::class)->assertCanPublish($newCampaign);
        }

        return redirect()
            ->route('admin.invoice-backoffice')
            ->with('status', 'Promocion creada correctamente.');
    }

    private function syncProductRules(Campaign $campaign, string $raw): void
    {
        $rows = [];
        foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            [$barcode, $presentation, $name] = array_pad(array_map('trim', explode('|', $line, 3)), 3, null);
            if ($barcode === '') {
                continue;
            }
            $rows[strtoupper($barcode)] = [
                'barcode' => strtoupper($barcode),
                'presentation' => $presentation ?: null,
                'product_name' => $name ?: 'Malta Vigor',
                'is_active' => true,
            ];
        }

        $campaign->productRules()->delete();
        if ($rows !== []) {
            $campaign->productRules()->createMany(array_values($rows));
        }
    }

    public function productRanking(Request $request, Campaign $campaign): \Illuminate\Http\JsonResponse
    {
        $this->authorizeAccess($request);
        abort_unless($campaign->participation_mode === 'product_ranking', 404);

        $rows = app(\App\Support\ProductRankingService::class)->leaderboard($campaign);
        $limit = (int) data_get($campaign->rules, 'winner_slots', 5);

        return response()->json([
            'campaign' => $campaign->only(['id', 'name', 'slug', 'status', 'starts_at', 'ends_at']),
            'winner_slots' => $limit,
            'data' => $rows->values()->map(fn ($user, $index) => [
                'position' => $index + 1,
                'user_id' => $user->id,
                'name' => $user->full_name ?: $user->name,
                'email' => $user->email,
                'birthdate' => optional($user->birthdate)->toDateString(),
                'total_units' => (int) $user->total_units,
                'first_reached_at' => $user->first_reached_at,
                'eligible' => $user->birthdate !== null && $user->birthdate->age >= (int) data_get($campaign->rules, 'minimum_age', 18) && ! $user->is_employee,
            ]),
        ]);
    }

    public function freezeProductRanking(Request $request, Campaign $campaign): \Illuminate\Http\JsonResponse|RedirectResponse
    {
        $this->authorizeAccess($request);
        abort_unless($campaign->participation_mode === 'product_ranking', 404);

        abort_if($campaign->ranking_frozen_at, 422, 'El ranking de esta campaña ya fue congelado.');

        $ranking = app(ProductRankingService::class)->leaderboard($campaign);
        $minimumAge = (int) data_get($campaign->rules, 'minimum_age', 18);
        $eligible = $ranking->filter(fn ($user) => $user->email && ! $user->is_employee && $user->birthdate?->age >= $minimumAge)->values();
        $slots = (int) data_get($campaign->rules, 'winner_slots', 5);

        DB::transaction(function () use ($campaign, $eligible, $slots): void {
            $campaign->forceFill(['ranking_frozen_at' => now()])->save();
            foreach ($eligible->take($slots) as $index => $user) {
                PromoWinner::query()->updateOrCreate(
                    ['campaign_id' => $campaign->id, 'user_id' => $user->id],
                    [
                        'phase_id' => null,
                        'leaderboard_position' => $index + 1,
                        'total_units' => (int) $user->total_units,
                        'total_points' => 0,
                        'invoice_count' => $user->invoices()->where('campaign_id', $campaign->id)->count(),
                        'invoice_total_amount' => (float) $user->invoices()->where('campaign_id', $campaign->id)->sum('purchase_amount'),
                        'first_reached_at' => $user->first_reached_at,
                        'ranking_timestamp' => now(),
                        'selection_reason' => 'product_ranking',
                        'status' => 'selected',
                        'selected_at' => now(),
                        'created_by' => auth()->id(),
                    ],
                );
            }
            foreach ($eligible->slice($slots, 10)->values() as $index => $user) {
                PromoWinner::query()->updateOrCreate(
                    ['campaign_id' => $campaign->id, 'user_id' => $user->id],
                    [
                        'phase_id' => null,
                        'leaderboard_position' => $slots + $index + 1,
                        'alternate_position' => $index + 1,
                        'total_units' => (int) $user->total_units,
                        'total_points' => 0,
                        'selection_reason' => 'product_ranking_alternate',
                        'status' => 'alternate',
                        'ranking_timestamp' => now(),
                        'created_by' => auth()->id(),
                    ],
                );
            }
        });

        Audit::log('campaign.product_ranking.frozen', 'campaign', $campaign->id, $request->user(), $request, [
            'campaign_id' => $campaign->id,
            'winner_slots' => $slots,
            'eligible_participants' => $eligible->count(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Ranking congelado y ganadores seleccionados.', 'winner_slots' => $slots]);
        }

        return back()->with('status', 'Ranking congelado: se seleccionaron los ganadores y suplentes en orden.');
    }

    public function productRankingOperations(Request $request, Campaign $campaign, ProductRankingService $rankingService): View
    {
        $this->authorizeAccess($request);
        abort_unless($campaign->participation_mode === 'product_ranking', 404);

        $ranking = $rankingService->leaderboard($campaign);
        $winnerSlots = (int) data_get($campaign->rules, 'winner_slots', 5);
        $minimumAge = (int) data_get($campaign->rules, 'minimum_age', 18);
        $winners = PromoWinner::query()
            ->with('user')
            ->where('campaign_id', $campaign->id)
            ->whereIn('status', ['selected', 'alternate', 'disqualified'])
            ->orderByRaw("CASE status WHEN 'selected' THEN 1 WHEN 'alternate' THEN 2 ELSE 3 END")
            ->orderBy('leaderboard_position')
            ->get();

        $invoices = RegisteredInvoice::query()
            ->with(['user', 'items', 'fraudFlags'])
            ->where('campaign_id', $campaign->id)
            ->latest()
            ->limit(40)
            ->get();

        $fraudFlags = FraudFlag::query()
            ->with(['user', 'invoice', 'reviewer'])
            ->whereHas('invoice', fn ($query) => $query->where('campaign_id', $campaign->id))
            ->orderByRaw("CASE severity WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->latest()
            ->limit(40)
            ->get();

        $invoiceIds = $invoices->pluck('id');
        $auditEntries = AuditLog::query()
            ->with('user')
            ->where(function ($query) use ($campaign, $invoiceIds): void {
                $query->where(fn ($inner) => $inner->where('entity_type', 'campaign')->where('entity_id', $campaign->id));
                if ($invoiceIds->isNotEmpty()) {
                    $query->orWhere(fn ($inner) => $inner->where('entity_type', 'registered_invoice')->whereIn('entity_id', $invoiceIds));
                }
            })
            ->latest('id')
            ->limit(40)
            ->get();

        return view('admin.product-ranking-operations', [
            'campaign' => $campaign->load('productRules'),
            'ranking' => $ranking,
            'winnerSlots' => $winnerSlots,
            'minimumAge' => $minimumAge,
            'winners' => $winners,
            'invoices' => $invoices,
            'fraudFlags' => $fraudFlags,
            'auditEntries' => $auditEntries,
        ]);
    }

    public function storeManualProductInvoice(
        Request $request,
        Campaign $campaign,
        BlacklistService $blacklist,
        FraudDetectionService $fraudDetection,
    ): RedirectResponse {
        $this->authorizeAccess($request);
        abort_unless($campaign->participation_mode === 'product_ranking', 404);

        if ($campaign->ranking_frozen_at) {
            throw ValidationException::withMessages(['invoice_number' => 'No se pueden añadir facturas después de congelar el ranking.']);
        }

        $products = collect($request->input('products', []))
            ->map(fn ($row) => [
                'barcode' => strtoupper(trim((string) data_get($row, 'barcode'))),
                'quantity' => data_get($row, 'quantity'),
            ])
            ->filter(fn ($row) => $row['barcode'] !== '' || $row['quantity'] !== null && $row['quantity'] !== '')
            ->values()
            ->all();
        $request->merge(['products' => $products]);

        $validated = $request->validate([
            'cedula' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:255'],
            'full_name' => ['nullable', 'string', 'max:150'],
            'invoice_number' => ['required', 'string', 'max:80'],
            'issued_at' => ['required', 'date'],
            'reason' => ['required', 'string', 'min:8', 'max:500'],
            'products' => ['required', 'array', 'min:1', 'max:10'],
            'products.*.barcode' => [
                'required',
                'string',
                Rule::exists('campaign_product_rules', 'barcode')->where(fn ($query) => $query
                    ->where('campaign_id', $campaign->id)
                    ->where('is_active', true)),
            ],
            'products.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        $issuedAt = Carbon::parse($validated['issued_at'], 'America/Panama');
        if (($campaign->starts_at && $issuedAt->lt($campaign->starts_at)) || ($campaign->ends_at && $issuedAt->gt($campaign->ends_at))) {
            throw ValidationException::withMessages(['issued_at' => 'La fecha de compra debe estar dentro de la vigencia de la promoción.']);
        }

        $email = Str::lower(trim($validated['email']));
        $cedula = trim($validated['cedula']);
        $byCedula = User::query()->where('cedula', $cedula)->first();
        $byEmail = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($byCedula && $byEmail && $byCedula->id !== $byEmail->id) {
            throw ValidationException::withMessages(['email' => 'La cédula y el correo pertenecen a cuentas diferentes. Requiere revisión antifraude.']);
        }

        $participant = $byCedula ?: $byEmail;
        if ($participant) {
            if ($participant->role !== 'client') {
                throw ValidationException::withMessages(['cedula' => 'La identidad corresponde a una cuenta interna y no puede participar.']);
            }
            if ($participant->cedula && $participant->cedula !== $cedula) {
                throw ValidationException::withMessages(['cedula' => 'El correo ya está asociado a otra cédula.']);
            }
            if ($participant->email && Str::lower($participant->email) !== $email) {
                throw ValidationException::withMessages(['email' => 'La cédula ya está asociada a otro correo.']);
            }
            $participant->forceFill([
                'cedula' => $participant->cedula ?: $cedula,
                'email' => $participant->email ?: $email,
                'full_name' => $participant->full_name ?: ($validated['full_name'] ?? null),
                'name' => $participant->name ?: ($validated['full_name'] ?? null),
            ])->save();
        } else {
            if (blank($validated['full_name'] ?? null)) {
                throw ValidationException::withMessages(['full_name' => 'Escribe el nombre completo porque este participante aún no existe.']);
            }
            $participant = User::query()->create([
                'name' => $validated['full_name'],
                'full_name' => $validated['full_name'],
                'role' => 'client',
                'document_type' => 'cedula',
                'cedula' => $cedula,
                'email' => $email,
                'is_active' => true,
            ]);
        }

        if ($participant->is_employee || $participant->disqualified_at || $blacklist->activeEntryForUser($participant)) {
            throw ValidationException::withMessages(['cedula' => 'El participante está excluido o bloqueado. No se registró la factura.']);
        }

        $invoiceNumber = trim($validated['invoice_number']);
        if (RegisteredInvoice::query()->where('campaign_id', $campaign->id)->where('invoice_number', $invoiceNumber)->exists()) {
            throw ValidationException::withMessages(['invoice_number' => 'Ese número de factura ya existe en esta promoción.']);
        }

        $rules = $campaign->productRules()->where('is_active', true)->get()->keyBy(fn ($rule) => strtoupper($rule->barcode));
        $matchedProducts = collect($validated['products'])->map(function (array $row) use ($rules): array {
            $rule = $rules->get(strtoupper($row['barcode']));
            return [
                'barcode' => $rule->barcode,
                'description' => $rule->product_name,
                'presentation' => $rule->presentation,
                'quantity' => (int) $row['quantity'],
                'is_eligible' => true,
            ];
        })->values();
        $eligibleUnits = (int) $matchedProducts->sum('quantity');
        $manualCufe = 'MANUAL-'.$campaign->id.'-'.strtoupper(hash('sha256', $invoiceNumber));

        $invoice = DB::transaction(function () use ($request, $campaign, $participant, $validated, $issuedAt, $invoiceNumber, $manualCufe, $matchedProducts, $eligibleUnits): RegisteredInvoice {
            $invoice = RegisteredInvoice::query()->create([
                'user_id' => $participant->id,
                'campaign_id' => $campaign->id,
                'cufe' => $manualCufe,
                'qr_raw_text' => $manualCufe,
                'invoice_number' => $invoiceNumber,
                'issuer_name' => 'Super Carnes (entrada manual)',
                'issued_at' => $issuedAt,
                'purchase_amount' => 0,
                'points_awarded' => 0,
                'shots_awarded' => 0,
                'status' => 'approved',
                'validation_status' => 'approved',
                'validation_notes' => 'Factura cargada manualmente por backoffice. Motivo: '.$validated['reason'],
                'dgi_response_payload' => [
                    'source' => 'admin_manual_entry',
                    'entered_by_user_id' => $request->user()->id,
                    'reason' => $validated['reason'],
                ],
                'eligible_units' => $eligibleUnits,
                'product_validation_status' => 'matched',
                'matched_products' => $matchedProducts->all(),
            ]);

            foreach ($matchedProducts as $product) {
                $invoice->items()->create([
                    'barcode' => $product['barcode'],
                    'description' => $product['description'].' '.($product['presentation'] ?? ''),
                    'quantity' => $product['quantity'],
                    'is_eligible' => true,
                    'source_payload' => ['source' => 'admin_manual_entry', 'entered_by_user_id' => $request->user()->id],
                ]);
            }

            Audit::log('invoice.manual_product_entry', 'registered_invoice', $invoice->id, $request->user(), $request, [
                'campaign_id' => $campaign->id,
                'participant_user_id' => $participant->id,
                'invoice_number' => $invoiceNumber,
                'eligible_units' => $eligibleUnits,
                'products' => $matchedProducts->all(),
                'reason' => $validated['reason'],
            ]);

            return $invoice;
        });

        $fraudDetection->inspectApprovedInvoice($participant, $invoice, $request);

        return back()->with('status', "Factura manual registrada: {$eligibleUnits} unidades Malta Vigor acreditadas.");
    }

    public function replaceProductRankingWinner(Request $request, Campaign $campaign, PromoWinner $winner): RedirectResponse
    {
        $this->authorizeAccess($request);
        abort_unless($campaign->participation_mode === 'product_ranking' && $winner->campaign_id === $campaign->id, 404);
        abort_unless($winner->status === 'selected', 422, 'Solo se puede reemplazar un ganador principal.');
        $validated = $request->validate(['reason' => ['required', 'string', 'min:8', 'max:500']]);
        DB::transaction(function () use ($request, $campaign, $winner, $validated): void {
            $alternate = PromoWinner::query()
                ->where('campaign_id', $campaign->id)
                ->where('status', 'alternate')
                ->orderBy('alternate_position')
                ->lockForUpdate()
                ->first();
            if (! $alternate) {
                throw ValidationException::withMessages(['reason' => 'No hay suplentes disponibles para efectuar el reemplazo.']);
            }
            $winner->forceFill([
                'status' => 'disqualified',
                'disqualified_at' => now(),
                'notes' => trim(($winner->notes ? $winner->notes."\n" : '').'Reemplazado: '.$validated['reason']),
            ])->save();
            $alternate->forceFill([
                'status' => 'selected',
                'alternate_position' => null,
                'replacement_for_winner_id' => $winner->id,
                'selected_at' => now(),
                'selection_reason' => 'ordered_alternate_replacement',
            ])->save();
            Audit::log('campaign.product_ranking.winner_replaced', 'campaign', $campaign->id, $request->user(), $request, [
                'campaign_id' => $campaign->id,
                'replaced_winner_id' => $winner->id,
                'replacement_winner_id' => $alternate->id,
                'reason' => $validated['reason'],
            ]);
        });

        return back()->with('status', 'Ganador reemplazado por el siguiente suplente elegible.');
    }

    public function resolveProductRankingFraudFlag(Request $request, Campaign $campaign, FraudFlag $flag): RedirectResponse
    {
        $this->authorizeAccess($request);
        abort_unless($campaign->participation_mode === 'product_ranking' && $flag->invoice?->campaign_id === $campaign->id, 404);
        $validated = $request->validate([
            'status' => ['required', Rule::in(['resolved', 'dismissed'])],
            'resolution_notes' => ['required', 'string', 'min:8', 'max:1000'],
        ]);
        $flag->forceFill([
            'status' => $validated['status'],
            'resolution_notes' => $validated['resolution_notes'],
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
        ])->save();
        Audit::log('fraud.flag.reviewed', 'registered_invoice', $flag->registered_invoice_id, $request->user(), $request, [
            'campaign_id' => $campaign->id,
            'fraud_flag_id' => $flag->id,
            'status' => $validated['status'],
            'resolution_notes' => $validated['resolution_notes'],
        ]);

        return back()->with('status', 'Alerta antifraude revisada y registrada en auditoría.');
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

    public function customerHistory(User $user, BlacklistService $blacklist): View
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
            'blacklistEntry' => $blacklist->activeEntryForUser($user),
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

    private function entrepreneursFilteredQuery(Request $request)
    {
        return User::query()
            ->where(function ($query) {
                $query->whereNotNull('entrepreneur_name')
                    ->orWhereNotNull('dream_promo_qualified_at');
            })
            ->when($request->filled('name'), function ($query) use ($request) {
                $term = trim((string) $request->input('name'));
                $query->where(function ($nameQuery) use ($term) {
                    $nameQuery->where('full_name', 'like', "%{$term}%")
                        ->orWhere('name', 'like', "%{$term}%")
                        ->orWhere('entrepreneur_name', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('cedula'), function ($query) use ($request) {
                $term = trim((string) $request->input('cedula'));
                $query->where('cedula', 'like', "%{$term}%");
            })
            ->when($request->filled('phone'), function ($query) use ($request) {
                $term = trim((string) $request->input('phone'));
                $query->where('phone', 'like', "%{$term}%");
            })
            ->when($request->filled('province'), function ($query) use ($request) {
                $term = trim((string) $request->input('province'));
                $query->where('entrepreneur_province', 'like', "%{$term}%");
            })
            ->when($request->filled('qualified'), function ($query) use ($request) {
                if ($request->input('qualified') === 'yes') {
                    $query->whereNotNull('dream_promo_qualified_at');
                } elseif ($request->input('qualified') === 'no') {
                    $query->whereNull('dream_promo_qualified_at');
                }
            });
    }

    private function applyEntrepreneursSort($query, Request $request, ?Campaign $dreamCampaign)
    {
        $sort = (string) $request->input('sort', '');
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';

        $directColumns = [
            'phone' => 'phone',
            'entrepreneur_name' => 'entrepreneur_name',
            'province' => 'entrepreneur_province',
            'type' => 'entrepreneur_type',
            'status' => 'dream_promo_qualified_at',
        ];

        if ($sort === 'branch') {
            return $query->leftJoin('branches', 'branches.id', '=', 'users.nearest_branch_id')
                ->select('users.*')
                ->orderBy('branches.name', $direction)
                ->orderByDesc('users.id');
        }

        if ($sort === 'total') {
            if ($dreamCampaign) {
                $totalsSub = RegisteredInvoice::query()
                    ->select('user_id', DB::raw('SUM(purchase_amount) as total_amount'))
                    ->where('campaign_id', $dreamCampaign->id)
                    ->groupBy('user_id');

                $query->leftJoinSub($totalsSub, 'invoice_totals', function ($join) {
                    $join->on('invoice_totals.user_id', '=', 'users.id');
                })
                    ->select('users.*', DB::raw('COALESCE(invoice_totals.total_amount, 0) as total_amount'))
                    ->orderBy('total_amount', $direction);
            }

            return $query->orderByDesc('users.id');
        }

        if ($sort === 'name') {
            return $query->orderByRaw('COALESCE(full_name, name) ' . $direction)->orderByDesc('id');
        }

        if (array_key_exists($sort, $directColumns)) {
            return $query->orderBy($directColumns[$sort], $direction)->orderByDesc('id');
        }

        return $query->orderByDesc('dream_promo_qualified_at')->orderByDesc('id');
    }

    public function entrepreneurs(Request $request): View
    {
        $dreamCampaign = Campaign::query()->where('slug', 'del-sueno-al-puesto')->first();

        $counts = (clone $this->entrepreneursFilteredQuery($request))
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN dream_promo_qualified_at IS NOT NULL THEN 1 ELSE 0 END) as qualified')
            ->first();

        $totalAmount = 0.0;
        if ($dreamCampaign) {
            $filteredUserIds = (clone $this->entrepreneursFilteredQuery($request))->pluck('id');
            $totalAmount = (float) RegisteredInvoice::query()
                ->where('campaign_id', $dreamCampaign->id)
                ->whereIn('user_id', $filteredUserIds)
                ->sum('purchase_amount');
        }

        $stats = [
            'total' => (int) ($counts->total ?? 0),
            'qualified' => (int) ($counts->qualified ?? 0),
            'pending' => (int) ($counts->total ?? 0) - (int) ($counts->qualified ?? 0),
            'totalAmount' => $totalAmount,
        ];

        $entrepreneurs = $this->applyEntrepreneursSort(
            $this->entrepreneursFilteredQuery($request)->with('branch'),
            $request,
            $dreamCampaign
        )->paginate(30);

        $entrepreneurs->appends($request->only(['name', 'cedula', 'phone', 'province', 'qualified', 'sort', 'direction']));

        $totalsByUser = collect();
        if ($dreamCampaign) {
            $totalsByUser = RegisteredInvoice::query()
                ->where('campaign_id', $dreamCampaign->id)
                ->whereIn('user_id', $entrepreneurs->pluck('id'))
                ->selectRaw('user_id, SUM(purchase_amount) as total')
                ->groupBy('user_id')
                ->pluck('total', 'user_id');
        }

        return view('admin.entrepreneurs', [
            'entrepreneurs' => $entrepreneurs,
            'totalsByUser' => $totalsByUser,
            'dreamCampaign' => $dreamCampaign,
            'stats' => $stats,
        ]);
    }

    public function entrepreneursExport(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $dreamCampaign = Campaign::query()->where('slug', 'del-sueno-al-puesto')->first();

        $entrepreneurs = $this->applyEntrepreneursSort(
            $this->entrepreneursFilteredQuery($request)->with('branch'),
            $request,
            $dreamCampaign
        )->get();

        $totalsByUser = collect();
        if ($dreamCampaign) {
            $totalsByUser = RegisteredInvoice::query()
                ->where('campaign_id', $dreamCampaign->id)
                ->whereIn('user_id', $entrepreneurs->pluck('id'))
                ->selectRaw('user_id, SUM(purchase_amount) as total')
                ->groupBy('user_id')
                ->pluck('total', 'user_id');
        }

        $filename = 'emprendedores-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($entrepreneurs, $totalsByUser) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Nombre', 'Cedula', 'Telefono', 'Correo', 'Emprendimiento', 'Provincia', 'Sucursal cercana', 'Tipo', 'Acumulado', 'Estado', 'Fecha calificacion']);

            foreach ($entrepreneurs as $person) {
                $total = (float) ($totalsByUser[$person->id] ?? 0);

                fputcsv($handle, [
                    $person->full_name ?? $person->name ?? '',
                    $person->cedula ?? '',
                    $person->phone ?? '',
                    $person->email ?? '',
                    $person->entrepreneur_name ?? '',
                    $person->entrepreneur_province ?? '',
                    $person->branch?->name ?? '',
                    $person->entrepreneur_type ?? '',
                    number_format($total, 2, '.', ''),
                    $person->dream_promo_qualified_at ? 'Calificado' : 'Pendiente',
                    $person->dream_promo_qualified_at?->format('d/m/Y H:i') ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function entrepreneurEdit(User $user, BlacklistService $blacklist): View
    {
        $dreamCampaign = Campaign::query()->where('slug', 'del-sueno-al-puesto')->first();

        $user->load('branch');

        $invoices = collect();
        $total = 0.0;
        if ($dreamCampaign) {
            $invoices = RegisteredInvoice::query()
                ->where('user_id', $user->id)
                ->where('campaign_id', $dreamCampaign->id)
                ->orderByDesc('created_at')
                ->get();
            $total = (float) $invoices->sum('purchase_amount');
        }

        return view('admin.entrepreneur-edit', [
            'entrepreneur' => $user,
            'branches' => Branch::query()->orderBy('name')->get(),
            'dreamCampaign' => $dreamCampaign,
            'invoices' => $invoices,
            'total' => $total,
            'blacklistEntry' => $blacklist->activeEntryForUser($user),
        ]);
    }

    public function entrepreneurUpdate(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:180'],
            'cedula' => ['nullable', 'string', 'max:40', Rule::unique('users', 'cedula')->ignore($user->id)],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'entrepreneur_name' => ['required', 'string', 'max:180'],
            'entrepreneur_province' => ['required', 'string', 'max:120'],
            'entrepreneur_type' => ['nullable', 'string', 'max:120'],
            'entrepreneur_story' => ['nullable', 'string'],
            'entrepreneur_reason' => ['required', 'string', 'max:2000'],
            'nearest_branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'dream_promo_qualified' => ['nullable', 'boolean'],
        ]);

        $wasQualified = $user->dream_promo_qualified_at !== null;
        $isQualified = $request->boolean('dream_promo_qualified');

        $user->forceFill([
            'full_name' => $validated['full_name'],
            'cedula' => $validated['cedula'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'entrepreneur_name' => $validated['entrepreneur_name'],
            'entrepreneur_province' => $validated['entrepreneur_province'],
            'entrepreneur_type' => $validated['entrepreneur_type'] ?? null,
            'entrepreneur_story' => $validated['entrepreneur_story'] ?? null,
            'entrepreneur_reason' => $validated['entrepreneur_reason'],
            'nearest_branch_id' => $validated['nearest_branch_id'] ?? null,
            'dream_promo_qualified_at' => $isQualified
                ? ($wasQualified ? $user->dream_promo_qualified_at : now())
                : null,
        ])->save();

        return redirect()
            ->route('admin.entrepreneurs.edit', $user)
            ->with('status', 'Datos del emprendedor actualizados.');
    }

    public function entrepreneurInvoiceStore(Request $request, User $user, ContestInvoiceRegistrationService $registrationService): RedirectResponse
    {
        $validated = $request->validate([
            'qr_raw_text' => ['required', 'string', 'max:2048'],
        ]);

        if (! $user->cedula) {
            return back()->withErrors(['qr_raw_text' => 'Este emprendedor no tiene cédula registrada. Agrégala antes de ingresar una factura.']);
        }

        try {
            $registrationService->registerGuest([
                'campaign_slug' => 'del-sueno-al-puesto',
                'document_type' => $user->document_type ?? 'cedula',
                'document_number' => $user->cedula,
                'full_name' => $user->full_name ?? $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'qr_raw_text' => $validated['qr_raw_text'],
            ]);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        return redirect()
            ->route('admin.entrepreneurs.edit', $user)
            ->with('status', 'Factura ingresada manualmente para el emprendedor.');
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
        if ($request->user()?->isAdmin()) {
            return;
        }

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
