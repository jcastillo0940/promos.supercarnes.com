<?php

namespace App\Support\Fanlyc;

use App\Mail\FanlycRegistrationConfirmation;
use App\Models\Campaign;
use App\Models\FanlycCoupon;
use App\Models\FanlycInvoice;
use App\Models\User;
use App\Support\Audit;
use App\Support\BlacklistService;
use App\Support\CampaignManager;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class FanlycRegistrationService
{
    public function __construct(
        private readonly CampaignManager $campaignManager,
        private readonly FanlycEligibilityValidator $validator,
        private readonly FanlycCouponIssuer $couponIssuer,
        private readonly BlacklistService $blacklist,
    ) {
    }

    public function registerInvoice(array $data): array
    {
        $campaign = $this->activeCampaignOrFail();
        $participant = $this->findOrCreateParticipant($data);

        $result = $this->validator->evaluate((string) ($data['qr_raw_text'] ?? ''));

        if ($result->cufe === null) {
            throw ValidationException::withMessages([
                'qr_raw_text' => $result->notes ?? 'No fue posible extraer un CUFE valido del contenido enviado.',
            ]);
        }

        if (FanlycInvoice::query()->where('campaign_id', $campaign->id)->where('cufe', $result->cufe)->exists()) {
            throw ValidationException::withMessages([
                'qr_raw_text' => 'Esta factura ya fue registrada en Fanlyc y no puede participar dos veces.',
            ]);
        }

        try {
            [$invoice, $coupon] = DB::transaction(function () use ($campaign, $participant, $result, $data): array {
                $resolved = $result->resolvedInvoice ?? [];

                $invoice = FanlycInvoice::query()->create([
                    'user_id' => $participant->id,
                    'campaign_id' => $campaign->id,
                    'branch_id' => $result->branchId,
                    'fanlyc_zone_id' => $result->fanlycZoneId,
                    'cufe' => $result->cufe,
                    'qr_raw_text' => $data['qr_raw_text'] ?? null,
                    'invoice_number' => $resolved['invoice_number'] ?? null,
                    'issuer_ruc' => $resolved['issuer_ruc'] ?? null,
                    'issuer_name' => $resolved['issuer_name'] ?? null,
                    'issued_at' => $resolved['issued_at'] ?? null,
                    'purchase_amount' => $resolved['purchase_amount'] ?? null,
                    'sku_check_status' => $result->skuCheckStatus,
                    'sku_check_payload' => $result->skuCheckPayload,
                    'status' => $result->outcome,
                    'validation_notes' => $result->notes,
                    'dgi_response_payload' => $resolved['payload'] ?? null,
                ]);

                Audit::log(
                    $result->isApproved() ? 'fanlyc.invoice.approved' : "fanlyc.invoice.{$result->outcome}",
                    'fanlyc_invoice',
                    $invoice->id,
                    $participant,
                    null,
                    ['cufe' => $invoice->cufe, 'outcome' => $result->outcome]
                );

                $coupon = null;

                if ($result->isApproved()) {
                    $coupon = $this->couponIssuer->issueFor($invoice);

                    Audit::log('fanlyc.coupon.issued', 'fanlyc_coupon', $coupon->id, $participant, null, [
                        'code' => $coupon->code,
                        'fanlyc_zone_id' => $coupon->fanlyc_zone_id,
                    ]);
                }

                return [$invoice, $coupon];
            });
        } catch (QueryException $exception) {
            if ((int) $exception->getCode() === 23000) {
                throw ValidationException::withMessages([
                    'qr_raw_text' => 'Esta factura ya fue registrada en Fanlyc y no puede participar dos veces.',
                ]);
            }

            throw $exception;
        }

        $this->sendConfirmationEmail($participant, $invoice, $coupon);

        return [
            'participant' => $participant,
            'invoice' => $invoice,
            'coupon' => $coupon,
            'outcome' => $result->outcome,
            'message' => $this->messageForOutcome($result->outcome),
        ];
    }

    private function activeCampaignOrFail(): Campaign
    {
        $campaign = $this->campaignManager->bySlugOrFail('fanlyc');

        if (
            $campaign->status !== 'active'
            || ($campaign->starts_at && now()->lt($campaign->starts_at))
            || ($campaign->ends_at && now()->gt($campaign->ends_at))
        ) {
            throw ValidationException::withMessages([
                'campaign' => 'La promocion Fanlyc no esta activa en este momento.',
            ]);
        }

        return $campaign;
    }

    private function findOrCreateParticipant(array $data): User
    {
        $documentNumber = strtoupper(preg_replace('/[^0-9-]/', '', trim((string) ($data['cedula'] ?? ''))) ?? '');
        $fullName = trim((string) ($data['full_name'] ?? ''));
        $email = trim((string) ($data['email'] ?? '')) ?: null;
        $phone = trim((string) ($data['phone'] ?? '')) ?: null;

        if ($documentNumber === '' || ! preg_match('/^[0-9]-?[0-9]{1,3}-?[0-9]{1,5}$/', $documentNumber)) {
            throw ValidationException::withMessages([
                'cedula' => 'Debes ingresar una cedula valida.',
            ]);
        }

        if ($this->blacklist->isBlocked($documentNumber, $phone)) {
            throw ValidationException::withMessages([
                'cedula' => 'No es posible completar tu registro en este momento. Contacta a servicio al cliente.',
            ]);
        }

        $user = User::query()->firstOrCreate(
            ['cedula' => $documentNumber],
            [
                'name' => $fullName,
                'full_name' => $fullName,
                'document_type' => 'cedula',
                'email' => $email,
                'phone' => $phone,
                'role' => 'client',
                'password' => Hash::make(str()->random(40)),
                'is_active' => true,
                'resides_in_panama' => true,
                'is_employee' => false,
            ],
        );

        $user->forceFill([
            'name' => $fullName ?: $user->name,
            'full_name' => $fullName ?: $user->full_name,
            'email' => $email ?? $user->email,
            'phone' => $phone ?? $user->phone,
        ])->save();

        return $user;
    }

    private function sendConfirmationEmail(User $participant, FanlycInvoice $invoice, ?FanlycCoupon $coupon): void
    {
        if (! $participant->email) {
            return;
        }

        try {
            Mail::to($participant->email)->send(new FanlycRegistrationConfirmation($invoice, $coupon));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function messageForOutcome(string $outcome): string
    {
        return match ($outcome) {
            'approved' => 'Factura aprobada. Ya puedes ver y descargar tu cupon QR.',
            'pending_review' => 'Tu factura quedo en revision manual. Te avisaremos por correo cuando se resuelva.',
            'rejected_issuer' => 'La factura no corresponde a un emisor autorizado de Super Carnes.',
            'rejected_branch_not_in_promo' => 'La sucursal de tu factura no participa en esta promocion.',
            'rejected_sku_not_found' => 'El producto requerido no aparece en tu factura.',
            default => 'Tu factura fue procesada.',
        };
    }
}
