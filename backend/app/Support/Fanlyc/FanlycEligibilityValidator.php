<?php

namespace App\Support\Fanlyc;

use App\Models\Branch;
use App\Support\ContestInvoiceVerifier;
use App\Support\CufeParser;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

/**
 * Validador de elegibilidad para Fanlyc: dado el texto crudo escaneado de una factura,
 * determina en orden si el CUFE es valido, si el emisor es Super Carnes, a que sucursal
 * y zona pertenece, y si el SKU objetivo esta presente. No persiste nada ni tiene efectos
 * secundarios: es una funcion pura, facil de probar con un verificador simulado.
 */
class FanlycEligibilityValidator
{
    public function __construct(
        private readonly CufeParser $cufeParser,
        private readonly ContestInvoiceVerifier $verifier,
        private readonly FanlycZoneResolver $zoneResolver,
        private readonly FanlycSkuChecker $skuChecker,
    ) {
    }

    public function evaluate(string $rawQrOrCufeText): FanlycEligibilityResult
    {
        $cufe = $this->cufeParser->extract($rawQrOrCufeText);

        if (! $cufe) {
            return new FanlycEligibilityResult(
                outcome: 'rejected_invalid_cufe',
                notes: 'No fue posible extraer un CUFE valido del contenido enviado.',
            );
        }

        $resolved = $this->resolve($cufe);

        $issuerRuc = strtoupper(trim((string) ($resolved['issuer_ruc'] ?? '')));
        $officialIssuerRucs = array_map(
            fn ($ruc) => strtoupper(trim((string) $ruc)),
            config('contest.official_issuer_rucs', [])
        );

        if ($officialIssuerRucs !== [] && ! in_array($issuerRuc, $officialIssuerRucs, true)) {
            return new FanlycEligibilityResult(
                outcome: 'rejected_issuer',
                cufe: $cufe,
                resolvedInvoice: $resolved,
                notes: 'La factura no corresponde a un emisor autorizado de Super Carnes.',
            );
        }

        $branch = $this->resolveBranch($resolved['issuer_branch_number'] ?? null);

        if (! $branch) {
            return new FanlycEligibilityResult(
                outcome: 'pending_review',
                cufe: $cufe,
                resolvedInvoice: $resolved,
                notes: 'No fue posible determinar automaticamente la sucursal de la factura. Requiere revision manual.',
            );
        }

        $zone = $this->zoneResolver->zoneForBranch($branch->id);

        if (! $zone) {
            return new FanlycEligibilityResult(
                outcome: 'rejected_branch_not_in_promo',
                cufe: $cufe,
                resolvedInvoice: $resolved,
                branchId: $branch->id,
                notes: 'La sucursal de la factura no participa en esta promocion.',
            );
        }

        $skuResult = $this->skuChecker->check($resolved);

        if ($skuResult->status === 'not_matched') {
            return new FanlycEligibilityResult(
                outcome: 'rejected_sku_not_found',
                cufe: $cufe,
                resolvedInvoice: $resolved,
                branchId: $branch->id,
                fanlycZoneId: $zone->id,
                skuCheckStatus: $skuResult->status,
                skuCheckPayload: $skuResult->payload,
                notes: 'El producto requerido no aparece en el detalle de la factura.',
            );
        }

        if ($skuResult->status === 'undetermined') {
            return new FanlycEligibilityResult(
                outcome: 'pending_review',
                cufe: $cufe,
                resolvedInvoice: $resolved,
                branchId: $branch->id,
                fanlycZoneId: $zone->id,
                skuCheckStatus: $skuResult->status,
                skuCheckPayload: $skuResult->payload,
                notes: 'No fue posible confirmar automaticamente el producto en la factura. Requiere revision manual.',
            );
        }

        return new FanlycEligibilityResult(
            outcome: 'approved',
            cufe: $cufe,
            resolvedInvoice: $resolved,
            branchId: $branch->id,
            fanlycZoneId: $zone->id,
            skuCheckStatus: $skuResult->status,
            skuCheckPayload: $skuResult->payload,
            notes: 'Factura aprobada.',
        );
    }

    private function resolve(string $cufe): array
    {
        try {
            $cacheKey = 'dgi_v2_cufe_'.strtolower($cufe);
            $cached = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($cufe) {
                $resolved = $this->verifier->resolve($cufe);

                return array_merge($resolved, ['issued_at' => $resolved['issued_at']->toIso8601String()]);
            });
        } catch (ConnectionException) {
            throw ValidationException::withMessages([
                'qr_raw_text' => 'No fue posible conectar con el servicio de verificacion de facturas. Intenta de nuevo en unos segundos.',
            ]);
        }

        $issuedAtRaw = $cached['issued_at'];

        return array_merge($cached, [
            'issued_at' => is_string($issuedAtRaw)
                ? CarbonImmutable::parse($issuedAtRaw, 'America/Panama')
                : CarbonImmutable::now('America/Panama'),
        ]);
    }

    private function resolveBranch(?int $storeNumber): ?Branch
    {
        if (! $storeNumber) {
            return null;
        }

        return Branch::query()->where('store_number', $storeNumber)->first();
    }
}
