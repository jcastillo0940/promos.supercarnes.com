<?php

namespace App\Support\Nivea;

use App\Mail\NiveaRegistrationConfirmation;
use App\Models\Branch;
use App\Models\Campaign;
use App\Models\NiveaEntry;
use App\Models\User;
use App\Support\Audit;
use App\Support\BlacklistService;
use App\Support\ContestInvoiceVerifier;
use App\Support\CufeParser;
use App\Support\ProductRankingService;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Promocion "Empollerando a Mama" con NIVEA y Super Carnes.
 *
 * Modulo independiente: guarda participaciones en nivea_entries
 * (una por cedula del registrante, sin cuenta ni login).
 * El registrante postula a una mama — nominee_name — y escribe su historia.
 */
class NiveaPromoService
{
    public const SLUG = 'nivea';
    public const TIMEZONE = 'America/Panama';

    public function __construct(
        private readonly CufeParser $cufeParser,
        private readonly ContestInvoiceVerifier $verifier,
        private readonly ProductRankingService $productRanking,
        private readonly BlacklistService $blacklist,
    ) {
    }

    public function campaign(): Campaign
    {
        return Campaign::query()->where('slug', self::SLUG)->firstOrFail();
    }

    public function isOpen(Campaign $campaign): bool
    {
        return $campaign->status === 'active'
            && (! $campaign->starts_at || now()->gte($campaign->starts_at))
            && (! $campaign->ends_at || now()->lte($campaign->ends_at));
    }

    public function rule(Campaign $campaign, string $key, mixed $default = null): mixed
    {
        return data_get($campaign->rules, $key, $default);
    }

    /** Datos que la pagina publica necesita para pintarse. */
    public function publicPayload(Campaign $campaign): array
    {
        $isActive = $campaign->status === 'active';

        return [
            'name' => $campaign->name,
            'description' => $campaign->description,
            'status' => $campaign->status,
            'is_open' => $this->isOpen($campaign),
            'phase' => match (true) {
                $this->isOpen($campaign) => 'open',
                $isActive && $campaign->starts_at && now()->lt($campaign->starts_at) => 'upcoming',
                $isActive && $campaign->ends_at && now()->gt($campaign->ends_at) => 'closed',
                default => 'unavailable',
            },
            'starts_at' => $campaign->starts_at?->toIso8601String(),
            'ends_at' => $campaign->ends_at?->toIso8601String(),
            'invoice_issued_from' => $this->rule($campaign, 'invoice_issued_from'),
            'invoice_issued_to' => $this->rule($campaign, 'invoice_issued_to'),
            'winner_slots' => (int) $this->rule($campaign, 'winner_slots', 10),
            'cash_prizes' => $this->rule($campaign, 'cash_prizes', []),
            'minimum_age' => (int) $this->rule($campaign, 'minimum_age', 18),
            'story_min_chars' => (int) $this->rule($campaign, 'story_min_chars', 80),
            'story_max_chars' => (int) $this->rule($campaign, 'story_max_chars', 2000),
            'logo_url' => $this->rule($campaign, 'logo_url'),
            'hero_url' => $this->rule($campaign, 'hero_url'),
            'product_images' => array_values(array_filter((array) $this->rule($campaign, 'product_images', []))),
            'terms_text' => $campaign->terms_text,
            'terms_version' => $campaign->terms_version,
            'terms_pdf_url' => $this->rule($campaign, 'terms_pdf_url'),
            'delivery_location' => $campaign->delivery_location,
            'delivery_requirements' => $campaign->delivery_requirements,
        ];
    }

    /**
     * Valida la factura (CUFE, emisor, sucursal, fecha y productos NIVEA) sin guardar nada.
     *
     * @return array{cufe: string, resolved: array, branch_id: ?int, branch_name: ?string, product: array}
     */
    public function inspectInvoice(Campaign $campaign, string $rawText): array
    {
        $cufe = $this->cufeParser->extract($rawText);
        if (! $cufe) {
            throw ValidationException::withMessages([
                'qr_raw_text' => 'No pudimos leer un CUFE válido. Revisa el QR o los números ingresados.',
            ]);
        }

        try {
            $cached = Cache::remember('dgi_v2_cufe_'.strtolower($cufe), now()->addMinutes(15), function () use ($cufe) {
                $resolved = $this->verifier->resolve($cufe);

                return array_merge($resolved, ['issued_at' => $resolved['issued_at']->toIso8601String()]);
            });
        } catch (ConnectionException) {
            throw ValidationException::withMessages([
                'qr_raw_text' => 'No fue posible validar tu factura en este momento. Intenta de nuevo en unos segundos.',
            ]);
        }

        $resolved = array_merge($cached, [
            'issued_at' => CarbonImmutable::parse((string) $cached['issued_at'])->setTimezone(self::TIMEZONE),
        ]);
        $canonicalCufe = strtoupper((string) $resolved['cufe']);

        $this->assertOfficialIssuer($resolved);

        $branchId = $this->resolveBranchId($resolved['issuer_branch_number'] ?? null);
        if (! $branchId) {
            throw ValidationException::withMessages([
                'qr_raw_text' => 'No fue posible identificar una sucursal de Super Carnes en la factura.',
            ]);
        }

        $this->assertIssuedWithinPromo($campaign, $resolved['issued_at']);

        if (NiveaEntry::query()->where('cufe', $canonicalCufe)->exists()) {
            throw ValidationException::withMessages([
                'qr_raw_text' => 'Esta factura ya fue registrada en Empollerando a Mamá.',
            ]);
        }

        $product = $this->productRanking->evaluate($campaign, $resolved);
        if ($product['status'] === 'matched' && $product['eligible_units'] < 1) {
            throw ValidationException::withMessages([
                'qr_raw_text' => 'Esta factura no contiene productos NIVEA participantes.',
            ]);
        }

        return [
            'cufe' => $canonicalCufe,
            'resolved' => $resolved,
            'branch_id' => $branchId,
            'branch_name' => Branch::query()->whereKey($branchId)->value('name'),
            'product' => $product,
        ];
    }

    /** Resumen de la factura seguro para mostrar al publico. */
    public function invoiceSummary(array $inspection): array
    {
        return [
            'invoice_number' => $inspection['resolved']['invoice_number'] ?? null,
            'purchase_amount' => number_format((float) ($inspection['resolved']['purchase_amount'] ?? 0), 2, '.', ''),
            'issued_at' => $inspection['resolved']['issued_at']->toDateString(),
            'branch_name' => $inspection['branch_name'],
            'product_check_status' => $inspection['product']['status'],
            'eligible_units' => $inspection['product']['eligible_units'],
            'matched_products' => collect($inspection['product']['matched_products'])
                ->map(fn ($item) => [
                    'description' => $item['description'] ?? null,
                    'quantity' => (float) ($item['quantity'] ?? 0),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * Registra la participacion completa.
     *
     * @param  string  $source  web | whatsapp
     */
    public function register(array $data, ?Request $request = null, string $source = 'web', ?User $admin = null): NiveaEntry
    {
        $campaign = $this->campaign();

        if ($source === 'web' && ! $this->isOpen($campaign)) {
            throw ValidationException::withMessages([
                'campaign' => 'La promoción Empollerando a Mamá no está recibiendo participaciones en este momento.',
            ]);
        }

        $participant = $this->validateParticipant($campaign, $data, $source);

        if ($this->activeEntryFor($campaign, $participant['cedula'])) {
            throw ValidationException::withMessages([
                'cedula' => 'Esta cédula ya tiene una participación activa. Solo se permite una participación por persona.',
            ]);
        }

        $inspection = $this->inspectInvoice($campaign, (string) ($data['qr_raw_text'] ?? ''));

        $verification = $this->verifier->verify(null, [
            'cufe' => $inspection['cufe'],
            'purchase_amount' => (float) $inspection['resolved']['purchase_amount'],
            'issued_at' => $inspection['resolved']['issued_at'],
        ], $inspection['resolved']);

        if (! in_array($verification['status'], ['approved', 'pending'], true)) {
            throw ValidationException::withMessages([
                'qr_raw_text' => 'La factura no pudo ser validada con la DGI. '.($verification['notes'] ?? ''),
            ]);
        }

        $notes = [];
        $status = 'approved';
        if ($verification['status'] === 'pending') {
            $status = 'pending_review';
            $notes[] = $verification['notes'] ?? 'Validación DGI pendiente.';
        }
        if ($inspection['product']['status'] === 'undetermined') {
            $status = 'pending_review';
            $notes[] = 'No se pudo leer el detalle de productos; revisar manualmente que incluya un producto NIVEA.';
        }
        if ($source === 'whatsapp' && $admin) {
            $notes[] = 'Registrada manualmente desde el backoffice (WhatsApp) por '.$admin->email.'.';
        }

        try {
            $entry = DB::transaction(function () use ($campaign, $participant, $inspection, $verification, $status, $notes, $data, $request, $source, $admin): NiveaEntry {
                $entry = NiveaEntry::query()->create([
                    'campaign_id' => $campaign->id,
                    'cedula' => $participant['cedula'],
                    'active_cedula' => $participant['cedula'],
                    'full_name' => $participant['full_name'],
                    'phone' => $participant['phone'],
                    'email' => $participant['email'],
                    'birthdate' => $participant['birthdate'],
                    'nominee_name' => $participant['nominee_name'],
                    'story' => $participant['story'],
                    'declared_eligibility_at' => now(),
                    'terms_version' => $campaign->terms_version,
                    'accepted_terms_at' => now(),
                    'cufe' => strtoupper((string) ($verification['canonical_cufe'] ?? $inspection['cufe'])),
                    'qr_raw_text' => $data['qr_raw_text'] ?? null,
                    'invoice_number' => $inspection['resolved']['invoice_number'] ?? null,
                    'issuer_ruc' => $inspection['resolved']['issuer_ruc'] ?? null,
                    'issuer_name' => $inspection['resolved']['issuer_name'] ?? null,
                    'branch_id' => $inspection['branch_id'],
                    'issued_at' => $inspection['resolved']['issued_at'],
                    'purchase_amount' => $inspection['resolved']['purchase_amount'] ?? null,
                    'eligible_units' => $inspection['product']['eligible_units'],
                    'product_check_status' => $inspection['product']['status'],
                    'matched_products' => collect($inspection['product']['matched_products'])
                        ->map(fn ($item) => collect($item)->except('source_payload')->all())
                        ->values()
                        ->all(),
                    'dgi_response_payload' => $inspection['resolved']['payload'] ?? null,
                    'status' => $status,
                    'validation_notes' => $notes === [] ? null : implode(' ', $notes),
                    'source' => $source,
                    'ip_address' => $request?->ip(),
                    'user_agent' => $request?->userAgent(),
                ]);

                Audit::log('nivea.entry.registered', 'nivea_entry', $entry->id, $admin, $request, [
                    'cedula' => $entry->cedula,
                    'nominee_name' => $entry->nominee_name,
                    'cufe' => $entry->cufe,
                    'status' => $entry->status,
                    'source' => $source,
                ]);

                return $entry;
            });
        } catch (QueryException $exception) {
            if ((int) $exception->getCode() === 23000 || str_contains($exception->getMessage(), 'Duplicate')) {
                throw ValidationException::withMessages(str_contains($exception->getMessage(), 'active_cedula')
                    ? ['cedula' => 'Esta cédula ya tiene una participación activa. Solo se permite una por persona.']
                    : ['qr_raw_text' => 'Esta factura ya fue registrada en Empollerando a Mamá.']);
            }

            throw $exception;
        }

        $this->sendConfirmation($entry);

        return $entry;
    }

    public function activeEntryFor(Campaign $campaign, string $cedula): ?NiveaEntry
    {
        return NiveaEntry::query()
            ->where('campaign_id', $campaign->id)
            ->where('active_cedula', $cedula)
            ->first();
    }

    public function normalizeCedula(string $value): string
    {
        $value = strtoupper(preg_replace('/[^A-Za-z0-9-]/', '', trim($value)) ?? '');
        $value = preg_replace('/^([0-9]{1,2})-?(AV|PI)-/', '$1$2-', $value) ?? $value;

        return collect(explode('-', $value))
            ->map(fn (string $part) => ctype_digit($part) ? (ltrim($part, '0') ?: '0') : $part)
            ->implode('-');
    }

    public function isValidCedula(string $cedula): bool
    {
        return (bool) preg_match('/^(?:PE|E|N|[0-9]{1,2}(?:AV|PI)?)-[0-9]{1,4}-[0-9]{1,6}$/', $cedula);
    }

    public function normalizePhone(string $value): string
    {
        $digits = preg_replace('/\D/', '', $value) ?? '';
        if (strlen($digits) === 11 && str_starts_with($digits, '507')) {
            $digits = substr($digits, 3);
        }

        return $digits;
    }

    /**
     * Interpreta la carga de SKUs del backoffice.
     *
     * @return array<string, array{barcode: string, presentation: ?string, product_name: string}>
     */
    public function parseProductCodes(string $raw): array
    {
        $rows = [];
        foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_contains($line, '|')) {
                $parts = array_map('trim', explode('|', $line));
                $barcode = $this->normalizeBarcode($parts[0]);
                [$presentation, $name] = count($parts) >= 3
                    ? [$parts[1] ?: null, $parts[2] ?: null]
                    : [null, $parts[1] ?: null];
                if (strlen($barcode) >= 4) {
                    $rows[$barcode] = ['barcode' => $barcode, 'presentation' => $presentation, 'product_name' => $name ?: 'Producto NIVEA'];
                }
                continue;
            }

            foreach (preg_split('/[,;]+/', $line) ?: [] as $token) {
                $barcode = $this->normalizeBarcode($token);
                if (strlen($barcode) >= 4) {
                    $rows[$barcode] ??= ['barcode' => $barcode, 'presentation' => null, 'product_name' => 'Producto NIVEA'];
                }
            }
        }

        return $rows;
    }

    private function normalizeBarcode(string $value): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9-]/', '', trim($value)) ?? '');
    }

    private function validateParticipant(Campaign $campaign, array $data, string $source): array
    {
        $fullName = trim(preg_replace('/\s+/', ' ', (string) ($data['full_name'] ?? '')) ?? '');
        $cedula = $this->normalizeCedula((string) ($data['cedula'] ?? ''));
        $phone = $this->normalizePhone((string) ($data['phone'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $nomineeName = trim(preg_replace('/\s+/', ' ', (string) ($data['nominee_name'] ?? '')) ?? '');
        $story = trim((string) ($data['story'] ?? ''));
        $minStory = (int) $this->rule($campaign, 'story_min_chars', 80);
        $maxStory = (int) $this->rule($campaign, 'story_max_chars', 2000);
        $minimumAge = (int) $this->rule($campaign, 'minimum_age', 18);

        $errors = [];

        if (mb_strlen($fullName) < 5 || ! str_contains($fullName, ' ')) {
            $errors['full_name'] = 'Escribe tu nombre y apellido.';
        }
        if (! $this->isValidCedula($cedula)) {
            $errors['cedula'] = 'Escribe una cédula válida, por ejemplo 8-123-4567.';
        }
        if (! preg_match('/^[0-9]{7,8}$/', $phone)) {
            $errors['phone'] = 'Escribe un teléfono de Panamá válido (7 u 8 dígitos).';
        }
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Escribe un correo electrónico válido.';
        }
        if (mb_strlen($nomineeName) < 3 || ! str_contains($nomineeName, ' ')) {
            $errors['nominee_name'] = 'Escribe el nombre y apellido de la mamá que postulas.';
        }

        $birthdate = null;
        try {
            $birthdate = CarbonImmutable::parse((string) ($data['birthdate'] ?? ''), self::TIMEZONE)->startOfDay();
        } catch (\Throwable) {
            $errors['birthdate'] = 'Escribe tu fecha de nacimiento.';
        }
        if ($birthdate) {
            $age = (int) floor(abs($birthdate->diffInYears(CarbonImmutable::now(self::TIMEZONE))));
            if ($birthdate->isFuture() || $age > 110) {
                $errors['birthdate'] = 'Escribe una fecha de nacimiento válida.';
            } elseif ($age < $minimumAge) {
                $errors['birthdate'] = "Debes ser mayor de {$minimumAge} años para participar.";
            }
        }

        if (mb_strlen($story) < $minStory) {
            $errors['story'] = "Cuéntanos un poco más: la historia debe tener al menos {$minStory} caracteres.";
        } elseif (mb_strlen($story) > $maxStory) {
            $errors['story'] = "La historia no puede superar {$maxStory} caracteres.";
        }

        if ($source === 'web') {
            if (! filter_var($data['declares_eligibility'] ?? false, FILTER_VALIDATE_BOOL)) {
                $errors['declares_eligibility'] = 'Confirma que la persona postulada es madre y que cuentas con su autorización para participar.';
            }
            if (! filter_var($data['accepted_terms'] ?? false, FILTER_VALIDATE_BOOL)) {
                $errors['accepted_terms'] = 'Debes aceptar las bases de la promoción.';
            } elseif ($campaign->terms_version && ($data['terms_version'] ?? null) !== $campaign->terms_version) {
                $errors['accepted_terms'] = 'Las bases de la promoción se actualizaron. Recarga la página y acéptalas de nuevo.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        if ($this->blacklist->isBlocked($cedula, $phone)) {
            throw ValidationException::withMessages([
                'cedula' => 'No es posible completar tu registro en este momento. Contacta a servicio al cliente.',
            ]);
        }

        return [
            'full_name' => mb_convert_case(mb_strtolower($fullName), MB_CASE_TITLE),
            'cedula' => $cedula,
            'phone' => $phone,
            'email' => $email,
            'nominee_name' => mb_convert_case(mb_strtolower($nomineeName), MB_CASE_TITLE),
            'birthdate' => $birthdate->toDateString(),
            'story' => $story,
        ];
    }

    private function assertIssuedWithinPromo(Campaign $campaign, CarbonImmutable $issuedAt): void
    {
        $from = $this->rule($campaign, 'invoice_issued_from');
        $to = $this->rule($campaign, 'invoice_issued_to');
        $fromDate = $from ? CarbonImmutable::parse($from, self::TIMEZONE)->startOfDay() : null;
        $toDate = $to ? CarbonImmutable::parse($to, self::TIMEZONE)->endOfDay() : null;

        if ($issuedAt->gt(CarbonImmutable::now(self::TIMEZONE)->endOfDay())) {
            throw ValidationException::withMessages([
                'qr_raw_text' => 'La fecha de la factura no puede ser futura.',
            ]);
        }

        if (($fromDate && $issuedAt->lt($fromDate)) || ($toDate && $issuedAt->gt($toDate))) {
            $range = collect([$fromDate?->format('d/m/Y'), $toDate?->format('d/m/Y')])->filter()->implode(' al ');
            throw ValidationException::withMessages([
                'qr_raw_text' => "Solo participan facturas emitidas del {$range}.",
            ]);
        }
    }

    private function assertOfficialIssuer(array $resolved): void
    {
        $normalize = fn (string $value) => preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($value))) ?? '';
        $issuerRuc = strtoupper(trim((string) ($resolved['issuer_ruc'] ?? '')));
        $issuerName = $normalize((string) ($resolved['issuer_name'] ?? ''));
        $officialRucs = array_map(fn ($ruc) => strtoupper(trim((string) $ruc)), config('contest.official_issuer_rucs', []));
        $officialNames = array_map(fn ($name) => $normalize((string) $name), config('contest.official_issuer_names', []));

        $rucMatches = $issuerRuc !== '' && in_array($issuerRuc, $officialRucs, true);
        $nameMatches = $issuerName !== '' && collect($officialNames)->contains(fn ($name) => $name !== '' && str_contains($issuerName, $name));

        if (! $rucMatches && ! $nameMatches) {
            throw ValidationException::withMessages([
                'qr_raw_text' => 'La factura no corresponde a una compra en Super Carnes.',
            ]);
        }
    }

    private function resolveBranchId(mixed $storeNumber): ?int
    {
        if (! $storeNumber) {
            return null;
        }

        return Branch::query()->where('store_number', (int) $storeNumber)->value('id');
    }

    private function sendConfirmation(NiveaEntry $entry): void
    {
        try {
            Mail::to($entry->email)->send(new NiveaRegistrationConfirmation($entry));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
