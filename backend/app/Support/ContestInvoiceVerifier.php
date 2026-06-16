<?php

namespace App\Support;

use App\Models\InvoiceGoalSetting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class ContestInvoiceVerifier
{
    public function resolve(string $cufe): array
    {
        $endpoint = config('contest.dgi_verifier_url');

        if (! $endpoint) {
            return [
                'cufe' => strtoupper($cufe),
                'invoice_number' => strtoupper($cufe),
                'purchase_amount' => 25.00,
                'issued_at' => CarbonImmutable::now('America/Panama'),
                'issuer_ruc' => '0000000000',
                'issuer_name' => 'Emisor de prueba',
                'issuer_address' => null,
                'issuer_phone' => null,
                'issuer_branch_number' => null,
                'payload' => [
                    'demo_mode' => true,
                    'valid' => true,
                    'status' => 'approved',
                    'notes' => 'Modo demo local porque DGI no esta configurado.',
                ],
            ];
        }

        $request = Http::acceptJson()->timeout(45)->connectTimeout(10)->withHeaders(['Connection' => 'close']);
        $token = (string) config('contest.dgi_verifier_token');

        if ($token !== '') {
            $request = $request->withToken($token);
        }

        $response = $request->get($endpoint, [
            'cufe' => $cufe,
        ]);

        /** @var array<string, mixed> $body */
        $body = $response->json() ?? [];

        // Si la API devuelve un error (HTTP o en el cuerpo), lo mostramos tal cual al usuario
        $apiError = data_get($body, 'error') ?? data_get($body, 'mensaje') ?? data_get($body, 'message');

        if (! $response->successful() || $apiError) {
            throw ValidationException::withMessages([
                'cufe' => $apiError ? 'La factura no pudo ser validada por DGI.' : 'No fue posible consultar la factura en este momento.',
            ]);
        }

        // La API es la autoridad — si devuelve datos los usamos sin validación adicional
        $datos = data_get($body, 'datos') ?? [];
        $emisor = $this->parseEmisorBlob((string) ($datos['emisor_nombre'] ?? ''));

        return [
            'cufe'             => strtoupper((string) ($datos['cufe'] ?? $cufe)),
            'invoice_number'   => strtoupper((string) ($datos['cufe'] ?? $cufe)),
            'purchase_amount'  => round((float) ($datos['total_pagado'] ?? 0), 2),
            'issued_at'        => $this->parseInvoiceDate((string) ($datos['fecha_autorizacion'] ?? '')),
            'issuer_ruc'        => (string) ($datos['emisor_ruc'] ?? $datos['ruc_emisor'] ?? $datos['ruc'] ?? ''),
            'issuer_name'      => $emisor['name'],
            'issuer_address'   => $emisor['address'],
            'issuer_phone'     => $emisor['phone'],
            'issuer_branch_number' => $emisor['store_number'],
            'payload'          => $body,
        ];
    }

    /**
     * El campo emisor_nombre que devuelve DGI viene como nombre, dirección y
     * teléfono concatenados sin separador, ej:
     * "IMPORTADORA VIRZI S.A.DIRECCIÓNSUPER CARNES NO. 5, CALLE 10A...TELÉFONO994-8514"
     *
     * @return array{name: string, address: ?string, phone: ?string, store_number: ?int}
     */
    public function parseEmisorBlob(string $raw): array
    {
        $raw = trim($raw);
        $name = $raw;
        $address = null;
        $phone = null;
        $storeNumber = null;

        if ($raw !== '' && preg_match('/^(.*?)DIRECCI[OÓ]N(.*)$/u', $raw, $matches)) {
            $name = trim($matches[1]);
            $rest = $matches[2];

            if (preg_match('/^(.*?)TEL[EÉ]FONO(.*)$/u', $rest, $restMatches)) {
                $address = trim($restMatches[1], " ,.\t\n\r");
                $phone = trim($restMatches[2]);
            } else {
                $address = trim($rest, " ,.\t\n\r");
            }
        }

        if ($address && preg_match('/(?:SUPER\s*CARNES|SUCURSAL)\s*NO\.?\s*(\d+)/iu', $address, $storeMatches)) {
            $storeNumber = (int) $storeMatches[1];
        }

        return [
            'name' => $name !== '' ? $name : $raw,
            'address' => $address,
            'phone' => $phone,
            'store_number' => $storeNumber,
        ];
    }

    public function verify(User $user, array $payload, ?array $resolved = null): array
    {
        $settings = InvoiceGoalSetting::query()->first();
        $validationMode = $settings?->validation_mode ?? 'manual';
        $endpoint = config('contest.dgi_verifier_url');

        if ($validationMode !== 'api' || ! $endpoint) {
            if ($validationMode !== 'api') {
                return [
                    'status' => 'rejected',
                    'notes' => 'La validacion oficial exige confirmacion directa contra DGI.',
                    'canonical_cufe' => $payload['cufe'],
                    'payload' => null,
                ];
            }

            return [
                'status' => 'approved',
                'notes' => 'Modo demo local.',
                'canonical_cufe' => $payload['cufe'],
                'payload' => [
                    'demo_mode' => true,
                    'valid' => true,
                ],
            ];
        }

        if ($resolved === null) {
            try {
                $resolved = $this->resolve($payload['cufe']);
            } catch (ValidationException) {
                return [
                    'status' => 'pending',
                    'notes' => 'No fue posible confirmar la factura con DGI en este momento.',
                    'canonical_cufe' => $payload['cufe'],
                    'payload' => null,
                ];
            }
        }

        $body = $resolved['payload'];
        $canonicalCufe = (string) $resolved['cufe'];
        $status = strtolower((string) data_get($body, 'status', data_get($body, 'datos.status', '')));
        $isValid = data_get($body, 'valid', data_get($body, 'datos.valid'));
        $ownerMatches = data_get($body, 'owner_matches', data_get($body, 'datos.owner_matches'));
        $notes = (string) (data_get($body, 'notes') ?? data_get($body, 'datos.notes') ?? 'Factura validada contra DGI.');

        if (in_array($status, ['invalid', 'rejected', 'failed'], true) || ($isValid !== null && ! filter_var($isValid, FILTER_VALIDATE_BOOL))) {
            return [
                'status' => 'disqualify',
                'notes' => $notes !== 'Factura validada contra DGI.' ? $notes : 'DGI marco el CUFE como invalido.',
                'canonical_cufe' => $payload['cufe'],
                'payload' => $body,
            ];
        }

        if ($status === 'mismatch' || ($ownerMatches !== null && ! filter_var($ownerMatches, FILTER_VALIDATE_BOOL))) {
            return [
                'status' => 'disqualify',
                'notes' => $notes !== 'Factura validada contra DGI.' ? $notes : 'La factura no pertenece al participante registrado.',
                'canonical_cufe' => $payload['cufe'],
                'payload' => $body,
            ];
        }

        if ($canonicalCufe === '') {
            return [
                'status' => 'pending',
                'notes' => 'DGI no devolvio un CUFE canonico para confirmar la factura.',
                'canonical_cufe' => $payload['cufe'],
                'payload' => $body,
            ];
        }

        return [
            'status' => 'approved',
            'notes' => $notes,
            'canonical_cufe' => strtoupper($canonicalCufe),
            'payload' => $body,
        ];
    }

    private function parseInvoiceDate(string $rawDate): CarbonImmutable
    {
        $timezone = 'America/Panama';
        $trimmed = trim($rawDate);

        if ($trimmed === '') {
            throw ValidationException::withMessages([
                'issued_at' => 'DGI no devolvio una fecha de emision valida para la factura.',
            ]);
        }

        $formats = ['d/m/Y H:i:s', 'Y-m-d H:i:s', DATE_ATOM];

        foreach ($formats as $format) {
            try {
                return CarbonImmutable::createFromFormat($format, $trimmed, $timezone);
            } catch (\Throwable) {
                continue;
            }
        }

        return CarbonImmutable::parse($trimmed, $timezone);
    }
}
