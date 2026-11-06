<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Campaign;
use App\Models\SabaEntry;
use App\Support\Audit;
use App\Support\Saba\SabaPromoService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SabaController extends Controller
{
    public function __construct(private readonly SabaPromoService $saba)
    {
    }

    public function index(Request $request): View
    {
        abort_unless($request->user()->canDoPromo('saba', 'view_participants'), 403);
        $campaign = $this->saba->campaign();

        $entries = $this->filteredEntries($request, $campaign)
            ->with('branch')
            ->orderByRaw('winner_position IS NULL, winner_position')
            ->latest('id')
            ->paginate(25)
            ->appends($request->only(['status', 'search', 'branch_id', 'winners']));

        $counts = SabaEntry::query()
            ->where('campaign_id', $campaign->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.saba', [
            'campaign' => $campaign,
            'entries' => $entries,
            'counts' => $counts,
            'winnerCount' => SabaEntry::query()->where('campaign_id', $campaign->id)->whereNotNull('winner_position')->count(),
            'winnerSlots' => (int) $this->saba->rule($campaign, 'winner_slots', 50),
            'productCount' => $campaign->productRules()->where('is_active', true)->count(),
            'branches' => Branch::query()->orderBy('name')->get(['id', 'name']),
            'products' => $campaign->productRules()->where('is_active', true)->orderBy('product_name')->get(['id', 'barcode', 'presentation', 'product_name']),
            'isOpen' => $this->saba->isOpen($campaign),
        ]);
    }

    public function show(SabaEntry $entry): View
    {
        abort_unless(auth()->user()->canDoPromo('saba', 'view_participants'), 403);
        $campaign = $this->saba->campaign();
        abort_unless($entry->campaign_id === $campaign->id, 404);

        $takenPositions = SabaEntry::query()
            ->where('campaign_id', $campaign->id)
            ->whereNotNull('winner_position')
            ->where('id', '!=', $entry->id)
            ->pluck('winner_position')
            ->all();

        return view('admin.saba-show', [
            'campaign' => $campaign,
            'entry' => $entry->load('branch', 'reviewedBy'),
            'winnerSlots' => (int) $this->saba->rule($campaign, 'winner_slots', 50),
            'cashPrizes' => (array) $this->saba->rule($campaign, 'cash_prizes', []),
            'takenPositions' => $takenPositions,
            'otherEntries' => SabaEntry::query()
                ->where('campaign_id', $campaign->id)
                ->where('cedula', $entry->cedula)
                ->where('id', '!=', $entry->id)
                ->latest('id')
                ->get(),
        ]);
    }

    public function settings(): View
    {
        abort_unless(auth()->user()->canDoPromo('saba', 'modify_config'), 403);
        $campaign = $this->saba->campaign();

        return view('admin.saba-settings', [
            'campaign' => $campaign,
            'products' => $campaign->productRules()->orderBy('product_name')->orderBy('barcode')->get(),
            'rules' => (array) ($campaign->rules ?? []),
            'isOpen' => $this->saba->isOpen($campaign),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        abort_unless($request->user()->canDoPromo('saba', 'modify_config'), 403);
        $campaign = $this->saba->campaign();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'invoice_issued_from' => ['required', 'date'],
            'invoice_issued_to' => ['required', 'date', 'after_or_equal:invoice_issued_from'],
            'winner_slots' => ['required', 'integer', 'min:1', 'max:500'],
            'prize_1' => ['nullable', 'numeric', 'min:0'],
            'prize_2' => ['nullable', 'numeric', 'min:0'],
            'prize_3' => ['nullable', 'numeric', 'min:0'],
            'minimum_age' => ['required', 'integer', 'min:18', 'max:99'],
            'story_min_chars' => ['required', 'integer', 'min:10', 'max:1000'],
            'story_max_chars' => ['required', 'integer', 'min:100', 'max:5000', 'gt:story_min_chars'],
            'terms_text' => ['nullable', 'string', 'max:100000'],
            'delivery_location' => ['nullable', 'string', 'max:255'],
            'delivery_requirements' => ['nullable', 'string', 'max:500'],
            'delivery_deadline' => ['nullable', 'date'],
        ]);

        $termsText = trim((string) ($validated['terms_text'] ?? '')) ?: null;
        $termsChanged = $termsText !== $campaign->terms_text;

        $rules = array_merge((array) ($campaign->rules ?? []), [
            'invoice_issued_from' => CarbonImmutable::parse($validated['invoice_issued_from'])->toDateString(),
            'invoice_issued_to' => CarbonImmutable::parse($validated['invoice_issued_to'])->toDateString(),
            'winner_slots' => (int) $validated['winner_slots'],
            'cash_prizes' => collect(['1' => 'prize_1', '2' => 'prize_2', '3' => 'prize_3'])
                ->map(fn (string $field) => isset($validated[$field]) ? (float) $validated[$field] : null)
                ->filter(fn ($value) => $value !== null)
                ->all(),
            'minimum_age' => (int) $validated['minimum_age'],
            'story_min_chars' => (int) $validated['story_min_chars'],
            'story_max_chars' => (int) $validated['story_max_chars'],
        ]);

        $campaign->forceFill([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'starts_at' => $this->panamaToUtc($validated['starts_at']),
            'ends_at' => $this->panamaToUtc($validated['ends_at']),
            'rules' => $rules,
            'terms_text' => $termsText,
            // Cada cambio de texto genera una version nueva; quien participe acepta la vigente.
            'terms_version' => $termsChanged ? ($termsText ? now(SabaPromoService::TIMEZONE)->format('Y-m-d-His') : null) : $campaign->terms_version,
            'terms_approved_at' => $termsChanged ? ($termsText ? now() : null) : $campaign->terms_approved_at,
            'delivery_location' => $validated['delivery_location'] ?? null,
            'delivery_requirements' => $validated['delivery_requirements'] ?? null,
            'delivery_deadline' => isset($validated['delivery_deadline']) ? $this->panamaToUtc($validated['delivery_deadline']) : null,
        ])->save();

        Audit::log('saba.settings.updated', 'campaign', $campaign->id, $request->user(), $request, [
            'terms_changed' => $termsChanged,
        ]);

        return redirect()->route('admin.saba.settings')->with('status', 'Configuración de Saba guardada.');
    }

    public function updateProducts(Request $request): RedirectResponse
    {
        abort_unless($request->user()->canDoPromo('saba', 'modify_config'), 403);
        $campaign = $this->saba->campaign();

        $validated = $request->validate([
            'products_text' => ['nullable', 'string', 'max:100000'],
            'mode' => ['required', 'in:append,replace'],
        ]);

        $rows = $this->saba->parseProductCodes((string) ($validated['products_text'] ?? ''));

        if ($validated['mode'] === 'replace' && $rows === [] && $campaign->status === 'active') {
            throw ValidationException::withMessages([
                'products_text' => 'La promoción está activa: no puedes dejarla sin SKUs. Pausa la promoción primero.',
            ]);
        }

        DB::transaction(function () use ($campaign, $rows, $validated): void {
            if ($validated['mode'] === 'replace') {
                $campaign->productRules()->delete();
            }

            foreach ($rows as $row) {
                $campaign->productRules()->updateOrCreate(
                    ['barcode' => $row['barcode']],
                    [
                        'presentation' => $row['presentation'],
                        'product_name' => $row['product_name'],
                        'is_active' => true,
                    ],
                );
            }
        });

        Audit::log('saba.products.updated', 'campaign', $campaign->id, $request->user(), $request, [
            'mode' => $validated['mode'],
            'count' => count($rows),
        ]);

        return redirect()->route('admin.saba.settings')
            ->with('status', count($rows).' SKU(s) '.($validated['mode'] === 'replace' ? 'cargados (lista reemplazada).' : 'agregados o actualizados.'));
    }

    public function destroyProduct(Request $request, int $rule): RedirectResponse
    {
        abort_unless($request->user()->canDoPromo('saba', 'modify_config'), 403);
        $campaign = $this->saba->campaign();
        $product = $campaign->productRules()->whereKey($rule)->firstOrFail();

        if ($campaign->status === 'active' && $campaign->productRules()->where('is_active', true)->count() <= 1) {
            throw ValidationException::withMessages([
                'products_text' => 'La promoción está activa: debe quedar al menos un SKU.',
            ]);
        }

        $product->delete();

        Audit::log('saba.products.removed', 'campaign', $campaign->id, $request->user(), $request, [
            'barcode' => $product->barcode,
        ]);

        return redirect()->route('admin.saba.settings')->with('status', "SKU {$product->barcode} eliminado.");
    }

    public function updateAssets(Request $request): RedirectResponse
    {
        abort_unless($request->user()->canDoPromo('saba', 'modify_config'), 403);
        $campaign = $this->saba->campaign();

        $request->validate([
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:4096'],
            'hero' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:8192'],
            'terms_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'remove' => ['nullable', 'array'],
            'remove.*' => ['in:logo_url,hero_url,terms_pdf_url'],
            'product_images' => ['nullable', 'array', 'max:5'],
            'product_images.*' => ['image', 'mimes:png,webp', 'max:4096'],
            'remove_product_images' => ['nullable', 'array'],
            'remove_product_images.*' => ['string'],
        ]);

        $rules = (array) ($campaign->rules ?? []);
        $changed = [];

        // Fotos de productos que flotan en la pagina (PNG/WEBP sin fondo, maximo 5).
        $productImages = array_values(array_filter((array) ($rules['product_images'] ?? [])));
        $toRemove = (array) $request->input('remove_product_images', []);
        if ($toRemove !== []) {
            foreach (array_intersect($productImages, $toRemove) as $url) {
                $this->deleteStoredAsset($url);
            }
            $productImages = array_values(array_diff($productImages, $toRemove));
            $changed[] = 'product_images';
        }
        foreach ((array) $request->file('product_images', []) as $file) {
            if (count($productImages) >= 5) {
                break;
            }
            $productImages[] = '/storage/'.$file->store('saba/productos', 'public');
            $changed[] = 'product_images';
        }
        $rules['product_images'] = $productImages;
        $changed = array_values(array_unique($changed));

        foreach (['logo' => 'logo_url', 'hero' => 'hero_url', 'terms_pdf' => 'terms_pdf_url'] as $input => $key) {
            if ($request->hasFile($input)) {
                $this->deleteStoredAsset($rules[$key] ?? null);
                $path = $request->file($input)->store('saba', 'public');
                $rules[$key] = '/storage/'.$path;
                $changed[] = $key;
            } elseif (in_array($key, (array) $request->input('remove', []), true)) {
                $this->deleteStoredAsset($rules[$key] ?? null);
                $rules[$key] = null;
                $changed[] = $key;
            }
        }

        $campaign->forceFill(['rules' => $rules])->save();

        Audit::log('saba.assets.updated', 'campaign', $campaign->id, $request->user(), $request, [
            'changed' => $changed,
        ]);

        return redirect()->route('admin.saba.settings')
            ->with('status', $changed === [] ? 'No se seleccionó ningún archivo.' : 'Archivos de Saba actualizados.');
    }

    public function toggleStatus(Request $request): RedirectResponse
    {
        abort_unless($request->user()->canDoPromo('saba', 'activate_pause'), 403);
        $campaign = $this->saba->campaign();
        $validated = $request->validate([
            'status' => ['required', 'in:active,paused'],
        ]);

        if ($validated['status'] === 'active' && $campaign->productRules()->where('is_active', true)->doesntExist()) {
            throw ValidationException::withMessages([
                'status' => 'Carga al menos un SKU de Saba antes de activar la promoción.',
            ]);
        }

        $campaign->forceFill([
            'status' => $validated['status'],
            'invoice_scan_enabled' => true,
        ])->save();

        Audit::log('saba.status.changed', 'campaign', $campaign->id, $request->user(), $request, [
            'status' => $validated['status'],
        ]);

        return back()->with('status', $validated['status'] === 'active' ? 'Promoción Saba activada.' : 'Promoción Saba pausada.');
    }

    public function approve(Request $request, SabaEntry $entry): RedirectResponse
    {
        abort_unless($request->user()->canDoPromo('saba', 'view_participants'), 403);
        $this->assertBelongsToSaba($entry);
        abort_if($entry->status === 'approved', 422, 'La participación ya está aprobada.');

        if ($entry->status === 'rejected') {
            $conflict = $this->saba->activeEntryFor($entry->campaign, $entry->cedula);
            if ($conflict && $conflict->id !== $entry->id) {
                throw ValidationException::withMessages([
                    'entry' => 'Esta cédula ya tiene otra participación vigente (#'.$conflict->id.').',
                ]);
            }
        }

        $entry->forceFill([
            'status' => 'approved',
            'active_cedula' => $entry->cedula,
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
        ])->save();

        Audit::log('saba.entry.approved', 'saba_entry', $entry->id, $request->user(), $request);

        return back()->with('status', 'Participación aprobada.');
    }

    public function reject(Request $request, SabaEntry $entry): RedirectResponse
    {
        abort_unless($request->user()->canDoPromo('saba', 'view_participants'), 403);
        $this->assertBelongsToSaba($entry);
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $entry->forceFill([
            'status' => 'rejected',
            // Libera la cedula para que pueda volver a participar con una factura valida.
            'active_cedula' => null,
            'winner_position' => null,
            'winner_selected_at' => null,
            'winner_selected_by_user_id' => null,
            'validation_notes' => trim(($entry->validation_notes ? $entry->validation_notes."\n" : '').'Rechazada: '.$validated['reason']),
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
        ])->save();

        Audit::log('saba.entry.rejected', 'saba_entry', $entry->id, $request->user(), $request, [
            'reason' => $validated['reason'],
        ]);

        return back()->with('status', 'Participación rechazada. La cédula puede volver a participar.');
    }

    public function selectWinner(Request $request, SabaEntry $entry): RedirectResponse
    {
        abort_unless($request->user()->canDoPromo('saba', 'view_winners'), 403);
        $this->assertBelongsToSaba($entry);
        $slots = (int) $this->saba->rule($entry->campaign, 'winner_slots', 50);

        $validated = $request->validate([
            'winner_position' => ['required', 'integer', 'min:1', "max:{$slots}"],
        ]);

        if ($entry->status !== 'approved') {
            throw ValidationException::withMessages([
                'winner_position' => 'Solo puedes elegir como ganadora una participación aprobada.',
            ]);
        }

        $taken = SabaEntry::query()
            ->where('campaign_id', $entry->campaign_id)
            ->where('winner_position', $validated['winner_position'])
            ->where('id', '!=', $entry->id)
            ->first();
        if ($taken) {
            throw ValidationException::withMessages([
                'winner_position' => "El puesto {$validated['winner_position']} ya está asignado a {$taken->full_name}.",
            ]);
        }

        $entry->forceFill([
            'winner_position' => (int) $validated['winner_position'],
            'winner_selected_at' => now(),
            'winner_selected_by_user_id' => $request->user()->id,
        ])->save();

        Audit::log('saba.winner.selected', 'saba_entry', $entry->id, $request->user(), $request, [
            'position' => $entry->winner_position,
        ]);

        return back()->with('status', "{$entry->full_name} quedó como ganadora en el puesto {$entry->winner_position}.");
    }

    public function removeWinner(Request $request, SabaEntry $entry): RedirectResponse
    {
        abort_unless($request->user()->canDoPromo('saba', 'view_winners'), 403);
        $this->assertBelongsToSaba($entry);
        $position = $entry->winner_position;

        $entry->forceFill([
            'winner_position' => null,
            'winner_selected_at' => null,
            'winner_selected_by_user_id' => null,
        ])->save();

        Audit::log('saba.winner.removed', 'saba_entry', $entry->id, $request->user(), $request, [
            'position' => $position,
        ]);

        return back()->with('status', 'Se quitó la selección de ganadora.');
    }

    public function manualRegister(Request $request): RedirectResponse
    {
        abort_unless($request->user()->canDoPromo('saba', 'upload_invoice_manual'), 403);
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'cedula' => ['required', 'string', 'max:40'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'string', 'max:150'],
            'birthdate' => ['required', 'date'],
            'story' => ['required', 'string', 'max:5000'],
            'invoice_type' => ['nullable', 'in:cufe,series'],
            'qr_raw_text' => ['exclude_if:invoice_type,series', 'required', 'string', 'max:2048'],
            'invoice_series' => ['exclude_unless:invoice_type,series', 'required', 'string', 'max:60'],
            'invoice_date' => ['exclude_unless:invoice_type,series', 'required', 'date'],
            'branch_id' => ['exclude_unless:invoice_type,series', 'required', 'integer', 'exists:branches,id'],
            'purchase_amount' => ['exclude_unless:invoice_type,series', 'required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'product_rule_id' => ['exclude_unless:invoice_type,series', 'required', 'integer'],
            'product_quantity' => ['exclude_unless:invoice_type,series', 'required', 'integer', 'min:1', 'max:9999'],
            'confirmed_eligibility' => ['accepted'],
        ]);

        $entry = ($validated['invoice_type'] ?? 'cufe') === 'series'
            ? $this->saba->registerManualInvoice($validated, $request, $request->user())
            : $this->saba->register($validated, $request, 'whatsapp', $request->user());

        return redirect()->route('admin.saba.show', $entry)
            ->with('status', 'Participación registrada a nombre de '.$entry->full_name.' ('.$entry->statusLabel().').');
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless($request->user()->canDoPromo('saba', 'export_data'), 403);
        $campaign = $this->saba->campaign();
        $query = $this->filteredEntries($request, $campaign)->with('branch')->orderBy('id');

        Audit::log('saba.entries.exported', 'campaign', $campaign->id, $request->user(), $request, [
            'filters' => $request->only(['status', 'search', 'branch_id', 'winners']),
        ]);

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'ID', 'Registrada', 'Estado', 'Puesto ganadora', 'Nombre completo', 'Cédula', 'Teléfono', 'Correo',
                'Fecha de nacimiento', 'Edad', 'Historia', 'Sucursal', 'Fecha factura', 'Monto', 'Unidades Saba',
                'Productos', 'CUFE', 'Origen', 'Notas',
            ]);

            $query->chunk(500, function ($entries) use ($out): void {
                foreach ($entries as $entry) {
                    fputcsv($out, [
                        $entry->id,
                        $entry->created_at?->timezone(SabaPromoService::TIMEZONE)->format('Y-m-d H:i'),
                        $entry->statusLabel(),
                        $entry->winner_position,
                        $entry->full_name,
                        $entry->cedula,
                        $entry->phone,
                        $entry->email,
                        $entry->birthdate?->format('Y-m-d'),
                        $entry->birthdate?->age,
                        $entry->story,
                        $entry->branch?->name,
                        $entry->issued_at?->timezone(SabaPromoService::TIMEZONE)->format('Y-m-d'),
                        $entry->purchase_amount,
                        $entry->eligible_units,
                        collect($entry->matched_products ?? [])->map(fn ($item) => ($item['quantity'] ?? '').' x '.($item['description'] ?? $item['barcode'] ?? ''))->implode('; '),
                        $entry->cufe,
                        $entry->source,
                        $entry->validation_notes,
                    ]);
                }
            });

            fclose($out);
        }, 'saba-participantes-'.now(SabaPromoService::TIMEZONE)->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function filteredEntries(Request $request, Campaign $campaign)
    {
        return SabaEntry::query()
            ->where('campaign_id', $campaign->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->input('branch_id')))
            ->when($request->input('winners') === '1', fn ($q) => $q->whereNotNull('winner_position'))
            ->when($request->filled('search'), function ($q) use ($request): void {
                $term = trim((string) $request->input('search'));
                $q->where(function ($inner) use ($term): void {
                    $inner->where('cedula', 'like', "%{$term}%")
                        ->orWhere('full_name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%")
                        ->orWhere('cufe', 'like', "%{$term}%");
                });
            });
    }

    private function assertBelongsToSaba(SabaEntry $entry): void
    {
        abort_unless($entry->campaign?->slug === SabaPromoService::SLUG, 404);
    }

    private function panamaToUtc(string $value): CarbonImmutable
    {
        return CarbonImmutable::parse($value, SabaPromoService::TIMEZONE)->utc();
    }

    private function deleteStoredAsset(?string $url): void
    {
        if ($url && str_starts_with($url, '/storage/saba/')) {
            Storage::disk('public')->delete(substr($url, strlen('/storage/')));
        }
    }

    private function aggregateTopSkus(\Illuminate\Support\Collection $rows, int $limit = 10): array
    {
        $counts = [];
        foreach ($rows as $json) {
            $products = is_array($json) ? $json : json_decode($json, true);
            if (! is_array($products)) {
                continue;
            }
            foreach ($products as $p) {
                if (empty($p['is_eligible'])) {
                    continue;
                }
                $key = trim($p['description'] ?? $p['barcode'] ?? 'Desconocido');
                $counts[$key] = ($counts[$key] ?? 0) + (int) ($p['quantity'] ?? 1);
            }
        }
        arsort($counts);
        $top = array_slice($counts, 0, $limit, true);

        return array_map(
            fn($desc, $qty) => ['sku' => $desc, 'total' => $qty],
            array_keys($top),
            array_values($top)
        );
    }

    public function dashboard(Request $request): View
    {
        abort_unless($request->user()->canDoPromo('saba', 'view_dashboard'), 403);

        $campaign = $this->saba->campaign();
        $period   = (int) $request->input('period', 30);
        $since    = $period > 0 ? now()->subDays($period) : null;

        $base = SabaEntry::query()->where('saba_entries.campaign_id', $campaign->id);
        if ($since) {
            $base->where('saba_entries.created_at', '>=', $since);
        }

        $counts = (clone $base)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $total    = $counts->sum();
        $approved = (int) ($counts['approved'] ?? 0);
        $pending  = (int) ($counts['pending_review'] ?? 0);
        $rejected = $counts->except(['approved', 'pending_review'])->sum();
        $winners  = SabaEntry::query()->where('campaign_id', $campaign->id)->whereNotNull('winner_position')->count();

        $daily = (clone $base)
            ->selectRaw('DATE(created_at) as day, count(*) as total, COALESCE(AVG(purchase_amount), 0) as avg_amount')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $byBranch = (clone $base)
            ->join('branches', 'saba_entries.branch_id', '=', 'branches.id')
            ->selectRaw('branches.name as branch, count(*) as total')
            ->groupBy('branches.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $byUnits = (clone $base)
            ->whereNotNull('eligible_units')
            ->selectRaw('eligible_units, count(*) as total')
            ->groupBy('eligible_units')
            ->orderBy('eligible_units')
            ->get();

        $topSkus = $this->aggregateTopSkus(
            (clone $base)->whereNotNull('matched_products')->pluck('matched_products')
        );

        return view('admin.saba-dashboard', [
            'campaign'  => $campaign,
            'period'    => $period,
            'total'     => $total,
            'approved'  => $approved,
            'pending'   => $pending,
            'rejected'  => $rejected,
            'winners'   => $winners,
            'counts'    => $counts,
            'daily'     => $daily,
            'byBranch'  => $byBranch,
            'byUnits'   => $byUnits,
            'topSkus'   => $topSkus,
        ]);
    }
}
