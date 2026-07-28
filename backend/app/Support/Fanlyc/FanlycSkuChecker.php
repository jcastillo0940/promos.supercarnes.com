<?php

namespace App\Support\Fanlyc;

class FanlycSkuChecker
{
    /**
     * Busca el SKU objetivo dentro de la respuesta cruda del verificador de facturas.
     *
     * El contrato real de la API interna (10.128.0.12/api/verificar) para el detalle de
     * productos no está confirmado hoy. Este metodo prueba una lista configurable de rutas
     * candidatas y nombres de campo; si ninguna resuelve a un arreglo de items, el resultado
     * es "undetermined" (nunca "not_matched") para que la factura caiga en revisión manual
     * en vez de rechazar a un cliente legítimo por un contrato de API que aún no se confirma.
     */
    public function check(array $resolvedInvoice): FanlycSkuCheckResult
    {
        $targetSku = trim((string) config('fanlyc.sku_target', ''));

        if ($targetSku === '') {
            return new FanlycSkuCheckResult('undetermined', null, null);
        }

        $payload = $resolvedInvoice['payload'] ?? null;

        if (! is_array($payload)) {
            return new FanlycSkuCheckResult('undetermined', null, $targetSku);
        }

        $listPaths = config('fanlyc.sku_item_list_paths', []);
        $fieldCandidates = config('fanlyc.sku_code_field_candidates', []);

        foreach ($listPaths as $path) {
            $items = data_get($payload, $path);

            if (! is_array($items) || $items === []) {
                continue;
            }

            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }

                foreach ($fieldCandidates as $field) {
                    if (! array_key_exists($field, $item)) {
                        continue;
                    }

                    $code = strtoupper(trim((string) $item[$field]));

                    if ($code !== '' && $code === strtoupper($targetSku)) {
                        return new FanlycSkuCheckResult('matched', $item, $targetSku);
                    }
                }
            }

            // Encontramos un arreglo de items parseable pero el SKU objetivo no aparece en ninguno.
            return new FanlycSkuCheckResult('not_matched', ['items_checked' => $items], $targetSku);
        }

        return new FanlycSkuCheckResult('undetermined', null, $targetSku);
    }
}
