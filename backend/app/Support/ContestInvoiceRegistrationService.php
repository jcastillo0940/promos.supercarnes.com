<?php

namespace App\Support;

use App\Mail\InvoiceParticipationReceipt;
use App\Models\Branch;
use App\Models\Campaign;
use App\Models\DailyInvoiceGoal;
use App\Models\InvoiceGoalSetting;
use App\Models\RegisteredInvoice;
use App\Models\TournamentPhase;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use App\Support\Audit;

class ContestInvoiceRegistrationService
{
    public function __construct(
        private readonly CampaignManager $campaignManager,
        private readonly CufeParser $cufeParser,
        private readonly ContestInvoiceVerifier $verifier,
        private readonly ContestRules $rules,
        private readonly WalletService $walletService,
        private readonly InvoicePeriodResolver $phaseResolver,
    ) {
    }

    public function registerGuest(array $data, ?Request $request = null): array
    {
        $campaign = ! empty($data['campaign_slug'])
            ? $this->campaignManager->bySlugOrFail((string) $data['campaign_slug'])
            : $this->campaignManager->activeOrFail();
        $settings = InvoiceGoalSetting::query()->first();

        if (! $campaign->invoice_scan_enabled || ($settings && ! $settings->is_enabled)) {
            throw ValidationException::withMessages([
                'invoice' => 'El registro de facturas esta deshabilitado en este momento.',
            ]);
        }

        $participant = $this->findOrCreateParticipant($data);

        $cufe = $this->cufeParser->extract($data['qr_raw_text']);
        if (! $cufe) {
            throw ValidationException::withMessages([
                'qr_raw_text' => 'No fue posible extraer un CUFE valido del QR enviado.',
            ]);
        }

        try {
            $cacheKey = 'dgi_v2_cufe_' . strtolower($cufe);
            $cached = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($cufe) {
                $r = $this->verifier->resolve($cufe);
                return array_merge($r, ['issued_at' => $r['issued_at']->toIso8601String()]);
            });
            $issuedAtRaw = $cached['issued_at'];
            $resolvedInvoice = array_merge($cached, [
                'issued_at' => is_string($issuedAtRaw)
                    ? CarbonImmutable::parse($issuedAtRaw, 'America/Panama')
                    : CarbonImmutable::now('America/Panama'),
            ]);
        } catch (ConnectionException) {
            throw ValidationException::withMessages([
                'qr_raw_text' => 'No fue posible conectar con el servicio DGI. Intenta de nuevo en unos segundos.',
            ]);
        }

        $canonicalCufe = strtoupper((string) $resolvedInvoice['cufe']);
        $issuedAt = $resolvedInvoice['issued_at'];
        $minimumAmount = $settings ? (float) $settings->min_purchase_amount : $this->rules->minimumInvoiceAmount();
        $purchaseAmount = round((float) $resolvedInvoice['purchase_amount'], 2);
        $now = now('America/Panama');
        $officialIssuerRucs = config('contest.official_issuer_rucs', []);
        $issuerRuc = strtoupper(trim((string) ($resolvedInvoice['issuer_ruc'] ?? '')));

        if ($officialIssuerRucs && ! in_array($issuerRuc, array_map(fn ($ruc) => strtoupper(trim((string) $ruc)), $officialIssuerRucs), true)) {
            throw ValidationException::withMessages([
                'issuer_ruc' => 'La factura no corresponde a un emisor autorizado de Super Carnes.',
            ]);
        }

        if ($purchaseAmount <= $minimumAmount) {
            throw ValidationException::withMessages([
                'purchase_amount' => 'La factura debe ser mayor a $'.number_format($minimumAmount, 2).' para ser valida.',
            ]);
        }

        $invoiceAgePolicy = $settings?->invoice_age_policy ?? 'none';
        $maxInvoiceAgeDays = $settings ? (int) $settings->max_invoice_age_days : $this->rules->maxInvoiceAgeDays();

        if ($issuedAt->gt($now->copy()->endOfDay())) {
            throw ValidationException::withMessages([
                'issued_at' => 'La fecha de la factura no puede ser futura.',
            ]);
        }

        if ($invoiceAgePolicy !== 'none') {
            $oldestAllowed = match ($invoiceAgePolicy) {
                'same_day' => $now->copy()->startOfDay(),
                'last_24_hours' => $now->copy()->subHours(24),
                default => $now->copy()->startOfDay()->subDays(max(0, $maxInvoiceAgeDays)),
            };

            if ($issuedAt->lt($oldestAllowed)) {
                throw ValidationException::withMessages([
                    'issued_at' => 'Factura expirada para la promocion.',
                ]);
            }
        }

        $verification = $this->verifier->verify($participant, [
            'cufe' => $canonicalCufe,
            'purchase_amount' => $purchaseAmount,
            'issued_at' => $issuedAt,
        ], $resolvedInvoice);
        $canonicalCufe = strtoupper((string) ($verification['canonical_cufe'] ?? $canonicalCufe));
        $invoicePeriod = $this->phaseResolver->periodForDate($issuedAt);

        if (RegisteredInvoice::query()->where('cufe', $canonicalCufe)->exists()) {
            throw ValidationException::withMessages([
                'qr_raw_text' => 'Este CUFE ya fue registrado y no puede participar dos veces.',
            ]);
        }

        $duplicateInvoice = RegisteredInvoice::query()
            ->where('invoice_number', (string) ($resolvedInvoice['invoice_number'] ?? ''))
            ->where('issuer_ruc', (string) ($resolvedInvoice['issuer_ruc'] ?? ''))
            ->first();

        if ($duplicateInvoice) {
            throw ValidationException::withMessages([
                'qr_raw_text' => 'Esta factura ya fue registrada y no puede participar dos veces.',
            ]);
        }

        $branchId = $this->resolveBranchId($resolvedInvoice['issuer_branch_number'] ?? null)
            ?? ($data['branch_id'] ?? null);

        if ($campaign->slug !== 'del-sueno-al-puesto' && $this->dreamPromoBalance($participant) < 300 && $this->dreamPromoInvoiceExists($participant)) {
            throw ValidationException::withMessages([
                'campaign_slug' => 'Este participante debe completar la promo Del sueño al puesto antes de entrar a otras promociones.',
            ]);
        }

        $dreamThreshold = (float) ($campaign->entry_threshold_amount ?? 0);
        $campaignQualified = true;
        if ($campaign->slug === 'del-sueno-al-puesto') {
            $totalAfter = $this->dreamPromoBalance($participant) + $purchaseAmount;
            $campaignQualified = $totalAfter >= ($dreamThreshold > 0 ? $dreamThreshold : 300);
        }

        try {
            $invoice = DB::transaction(function () use ($participant, $campaign, $data, $branchId, $canonicalCufe, $purchaseAmount, $issuedAt, $verification, $resolvedInvoice, $invoicePeriod, $minimumAmount, $maxInvoiceAgeDays, $invoiceAgePolicy, $settings, $campaignQualified): RegisteredInvoice {
                $status = $verification['status'] === 'approved'
                    ? ($campaignQualified ? 'accepted' : 'pending_threshold')
                    : ($verification['status'] === 'pending' ? 'pending_validation' : 'rejected');
                $validationStatus = match ($verification['status']) {
                    'approved' => 'approved',
                    'pending' => 'pending',
                    default => 'rejected',
                };
                $pointsAwarded = $verification['status'] === 'approved' && $campaignQualified ? 1 : 0;

                $invoice = RegisteredInvoice::query()->create([
                    'user_id' => $participant->id,
                    'campaign_id' => $campaign->id,
                    'branch_id' => $branchId,
                    'cufe' => $canonicalCufe,
                    'qr_raw_text' => $data['qr_raw_text'],
                    'invoice_number' => $resolvedInvoice['invoice_number'],
                    'issuer_ruc' => $resolvedInvoice['issuer_ruc'] ?? null,
                    'issuer_name' => $resolvedInvoice['issuer_name'] ?? null,
                    'issued_at' => $issuedAt,
                    'purchase_amount' => $purchaseAmount,
                    'points_awarded' => $pointsAwarded,
                    'shots_awarded' => 0,
                    'daily_points_capped' => false,
                    'daily_invoice_limit_hit' => false,
                    'status' => $status,
                    'validation_status' => $validationStatus,
                    'validation_notes' => $verification['notes'],
                    'dad_reason' => $data['dad_reason'] ?? null,
                    'dgi_checked_at' => $verification['status'] === 'pending' ? null : now(),
                    'dgi_response_payload' => $verification['payload'],
                ]);

                if ($campaign->slug === 'del-sueno-al-puesto' && $campaignQualified) {
                    $participant->forceFill(['dream_promo_qualified_at' => now()])->save();
                }

                if ($verification['status'] === 'approved' && $campaignQualified) {
                    $this->syncDailyInvoiceGoal($participant, $invoice, $invoicePeriod);
                    $this->walletService->creditGoals(
                        user: $participant,
                        amount: (int) $pointsAwarded,
                        type: 'invoice_goal_awarded',
                        resourceType: 'registered_invoice',
                        resourceId: $invoice->id,
                        campaignId: $campaign->id,
                        notes: 'Factura validada y premio acreditado.',
                        meta: [
                            'source' => 'invoice',
                            'period_id' => $invoicePeriod?->id,
                            'period_slug' => $invoicePeriod?->slug,
                            'period_name' => $invoicePeriod?->name,
                            'rule_code' => 'invoice_goal_awarded',
                            'rule_label' => 'Factura aprobada mayor al minimo',
                            'rule_snapshot' => [
                                'minimum_amount' => $minimumAmount,
                                'invoice_age_policy' => $invoiceAgePolicy,
                                'max_invoice_age_days' => $maxInvoiceAgeDays,
                                'goal_value' => (int) $pointsAwarded,
                                'validation_mode' => $settings?->validation_mode ?? 'api',
                                'entry_threshold_amount' => $campaign->entry_threshold_amount,
                            ],
                            'invoice' => [
                                'invoice_number' => $invoice->invoice_number,
                                'cufe' => $invoice->cufe,
                                'purchase_amount' => (float) $invoice->purchase_amount,
                                'validation_status' => $invoice->validation_status,
                            ],
                        ],
                    );
                }

                Audit::log('invoice.registered', 'registered_invoice', $invoice->id, $participant, null, [
                    'cufe' => $invoice->cufe,
                    'validation_status' => $invoice->validation_status,
                'points_awarded' => $invoice->points_awarded,
            ]);

                return $invoice;
            });
        } catch (QueryException $exception) {
            if ((int) $exception->getCode() === 23000 || str_contains($exception->getMessage(), 'registered_invoices_cufe_unique')) {
                throw ValidationException::withMessages([
                    'qr_raw_text' => 'Esta factura ya fue registrada y no puede participar dos veces.',
                ]);
            }

            throw $exception;
        }

        $this->sendParticipationReceipt($invoice);

        $campaignTotal = (float) RegisteredInvoice::query()
            ->where('user_id', $participant->id)
            ->where('campaign_id', $campaign->id)
            ->sum('purchase_amount');

        return [
            'invoice' => $invoice,
            'verification_status' => $verification['status'],
            'message' => $campaign->slug === 'del-sueno-al-puesto' && ! $campaignQualified
                ? 'Factura guardada. Sigue acumulando hasta llegar a $300 para activar tu participación.'
                : $this->messageForStatus($verification['status']),
            'campaign_total' => $campaignTotal,
            'campaign_threshold' => (float) ($campaign->entry_threshold_amount ?? 0),
            'campaign_qualified' => $campaignQualified,
        ];
    }

    private function sendParticipationReceipt(RegisteredInvoice $invoice): void
    {
        if (! $invoice->user?->email) {
            return;
        }

        try {
            Mail::to($invoice->user->email)->send(new InvoiceParticipationReceipt($invoice));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public function resolveInvoiceData(string $rawText): array
    {
        $settings = InvoiceGoalSetting::query()->first();
        $cufe = $this->cufeParser->extract($rawText);

        if (! $cufe) {
            throw ValidationException::withMessages([
                'qr_raw_text' => 'No fue posible extraer un CUFE valido del contenido enviado.',
            ]);
        }

        try {
            $cacheKey = 'dgi_v2_cufe_' . strtolower($cufe);
            $cached = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($cufe) {
                $r = $this->verifier->resolve($cufe);
                return array_merge($r, ['issued_at' => $r['issued_at']->toIso8601String()]);
            });
            $issuedAtRaw = $cached['issued_at'];
            $resolved = array_merge($cached, [
                'issued_at' => is_string($issuedAtRaw)
                    ? CarbonImmutable::parse($issuedAtRaw, 'America/Panama')
                    : CarbonImmutable::now('America/Panama'),
            ]);
        } catch (ConnectionException) {
            throw ValidationException::withMessages([
                'qr_raw_text' => 'No fue posible conectar con el servicio DGI. Intenta de nuevo en unos segundos.',
            ]);
        }

        $minimumAmount = $settings ? (float) $settings->min_purchase_amount : $this->rules->minimumInvoiceAmount();

        return [
            'cufe' => $resolved['cufe'],
            'invoice_number' => $resolved['invoice_number'],
            'purchase_amount' => number_format((float) $resolved['purchase_amount'], 2, '.', ''),
            'issued_at' => $resolved['issued_at']->toDateString(),
            'issuer_name' => $resolved['issuer_name'],
            'issuer_ruc' => $resolved['issuer_ruc'] ?? null,
            'is_valid' => (float) $resolved['purchase_amount'] >= $minimumAmount,
            'minimum_amount' => $minimumAmount,
        ];
    }

    private function findOrCreateParticipant(array $data): User
    {
        $documentType = strtolower((string) ($data['document_type'] ?? 'cedula'));
        $documentNumber = $this->normalizeDocumentNumber($documentType, (string) ($data['document_number'] ?? $data['cedula'] ?? ''));
        $cedula = $documentType === 'cedula' ? $documentNumber : $documentNumber;
        $firstName = trim((string) ($data['first_name'] ?? ''));
        $lastName = trim((string) ($data['last_name'] ?? ''));
        $fullName = trim((string) ($data['full_name'] ?? trim($firstName.' '.$lastName)));

        if ($documentType === 'cedula' && ! preg_match('/^[0-9]-?[0-9]{3}-?[0-9]{4}$/', $cedula) && ! preg_match('/^[0-9]-?[0-9]{3}-?[0-9]{4,5}$/', $cedula) && ! preg_match('/^[0-9]-?[0-9]{1,3}-?[0-9]{1,4}-?[0-9]{1,4}$/', $cedula)) {
            throw ValidationException::withMessages([
                'document_number' => 'Debes ingresar una cedula valida.',
            ]);
        }

        if ($documentNumber === '') {
            throw ValidationException::withMessages([
                'document_number' => 'Debes ingresar un numero de documento valido.',
            ]);
        }

        if (RegisteredInvoice::query()->whereHas('user', fn ($query) => $query->where('cedula', $documentNumber))->exists()) {
            throw ValidationException::withMessages([
                'document_number' => 'Este documento ya registro una factura y no puede participar dos veces.',
            ]);
        }

        $requestedEmail = isset($data['email']) && $data['email'] !== '' ? $data['email'] : null;
        $safeEmail = $requestedEmail;
        if ($safeEmail !== null) {
            $emailTakenByOther = User::query()
                ->where('email', $safeEmail)
                ->where('cedula', '!=', $documentNumber)
                ->exists();
            if ($emailTakenByOther) {
                throw ValidationException::withMessages([
                    'email' => 'Este correo ya esta registrado con otro documento.',
                ]);
            }
        }

        $user = User::query()->firstOrCreate(
            ['cedula' => $documentNumber],
            [
                'name' => $fullName,
                'document_type' => $documentType,
                'email' => $safeEmail,
                'phone' => $data['phone'] ?? null,
                'role' => 'client',
                'password' => Hash::make(str()->random(40)),
                'is_active' => true,
                'resides_in_panama' => true,
                'is_employee' => false,
                'entrepreneur_name' => $data['entrepreneur_name'] ?? null,
                'entrepreneur_province' => $data['entrepreneur_province'] ?? null,
                'nearest_branch_id' => $data['nearest_branch_id'] ?? null,
                'entrepreneur_type' => $data['entrepreneur_type'] ?? null,
                'entrepreneur_story' => $data['entrepreneur_story'] ?? null,
                'entrepreneur_reason' => $data['entrepreneur_reason'] ?? null,
            ],
        );

        $user->forceFill([
            'name' => $fullName,
            'email' => $safeEmail ?? $user->email,
            'phone' => $data['phone'] ?? $user->phone,
            'cedula' => $documentNumber,
            'document_type' => $documentType,
            'is_active' => true,
            'entrepreneur_name' => $data['entrepreneur_name'] ?? $user->entrepreneur_name,
            'entrepreneur_province' => $data['entrepreneur_province'] ?? $user->entrepreneur_province,
            'nearest_branch_id' => $data['nearest_branch_id'] ?? $user->nearest_branch_id,
            'entrepreneur_type' => $data['entrepreneur_type'] ?? $user->entrepreneur_type,
            'entrepreneur_story' => $data['entrepreneur_story'] ?? $user->entrepreneur_story,
            'entrepreneur_reason' => $data['entrepreneur_reason'] ?? $user->entrepreneur_reason,
        ])->save();

        if (! $user->wallet()->exists()) {
            $this->walletService->creditGoals(
                user: $user,
                amount: 0,
                type: 'wallet_initialized',
                resourceType: 'user',
                resourceId: $user->id,
                campaignId: null,
                notes: 'Wallet inicializada para participante de promo.',
                meta: [],
            );
        }

        return $user;
    }

    private function dreamPromoBalance(User $user): float
    {
        return (float) RegisteredInvoice::query()
            ->where('user_id', $user->id)
            ->whereHas('campaign', fn ($query) => $query->where('slug', 'del-sueno-al-puesto'))
            ->sum('purchase_amount');
    }

    private function dreamPromoInvoiceExists(User $user): bool
    {
        return RegisteredInvoice::query()
            ->where('user_id', $user->id)
            ->whereHas('campaign', fn ($query) => $query->where('slug', 'del-sueno-al-puesto'))
            ->exists();
    }

    private function resolveBranchId(?int $storeNumber): ?int
    {
        if (! $storeNumber) {
            return null;
        }

        return Branch::query()->where('store_number', $storeNumber)->value('id');
    }

    private function normalizeCedula(string $cedula): string
    {
        return strtoupper(preg_replace('/[^A-Z0-9-]/', '', trim($cedula)) ?? '');
    }

    private function normalizeDocumentNumber(string $documentType, string $documentNumber): string
    {
        $trimmed = strtoupper(trim($documentNumber));

        return match ($documentType) {
            'passport', 'residente' => preg_replace('/[^A-Z0-9-]/', '', $trimmed) ?? '',
            default => preg_replace('/[^0-9-]/', '', $trimmed) ?? '',
        };
    }

    private function syncDailyInvoiceGoal(User $user, RegisteredInvoice $invoice, ?TournamentPhase $phase): void
    {
        DailyInvoiceGoal::query()->create([
            'user_id' => $user->id,
            'phase_id' => $phase?->id,
            'invoice_number' => $invoice->invoice_number ?? $invoice->cufe,
            'purchase_amount' => $invoice->purchase_amount,
            'invoice_date' => optional($invoice->issued_at)->toDateString() ?? now()->toDateString(),
            'goal_points_awarded' => 1,
            'validation_status' => 'approved',
            'validation_notes' => 'Factura aprobada para la promo de facturas.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function messageForStatus(string $status): string
    {
        return match ($status) {
            'approved' => 'Factura validada. Completa el registro para participar.',
            'pending' => 'Factura recibida. Se revisara la validacion.',
            'disqualify' => 'Factura enviada a revision antifraude.',
            default => 'Factura rechazada durante la validacion.',
        };
    }
}
