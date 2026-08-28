<?php

namespace App\Support\Fanlyc;

class FanlycSkuChecker
{
    /**
     * Busca el producto objetivo (por código o por nombre) dentro de la respuesta cruda del
     * verificador de facturas. Aprueba si CUALQUIERA de los dos matchea, ya que la API de DGI
     * no garantiza que el código de producto sea estable entre sucursales.
     *
     * Este metodo prueba una lista configurable de rutas candidatas y nombres de campo; si
     * ninguna resuelve a un arreglo de items, el resultado es "undetermined" (nunca
     * "not_matched") para que la factura caiga en revisión manual en vez de rechazar a un
     * cliente legítimo por un contrato de API que cambie.
     */
    public function check(array $resolvedInvoice): FanlycSkuCheckResult
    {
        $targetCode = trim((string) config('fanlyc.sku_code_target', ''));
        $targetName = trim((string) config('fanlyc.sku_name_target', ''));

        if ($targetCode === '' && $targetName === '') {
            return new FanlycSkuCheckResult('undetermined', null, null);
        }

        $checkedLabel = trim(
            ($targetCode !== '' ? "codigo={$targetCode}" : '').
            ($targetCode !== '' && $targetName !== '' ? '|' : '').
            ($targetName !== '' ? "nombre={$targetName}" : '')
        );

        $payload = $resolvedInvoice['payload'] ?? null;

        if (! is_array($payload)) {
            return new FanlycSkuCheckResult('undetermined', null, $checkedLabel);
        }

        $listPaths = config('fanlyc.sku_item_list_paths', []);
        $codeFieldCandidates = config('fanlyc.sku_code_field_candidates', []);
        $nameFieldCandidates = config('fanlyc.sku_name_field_candidates', []);

        foreach ($listPaths as $path) {
            $items = data_get($payload, $path);

            if (! is_array($items) || $items === []) {
                continue;
            }

            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }

                if ($targetCode !== '' && $this->fieldMatches($item, $codeFieldCandidates, $targetCode)) {
                    return new FanlycSkuCheckResult('matched', $item, $checkedLabel);
                }

                if ($targetName !== '' && $this->fieldMatches($item, $nameFieldCandidates, $targetName)) {
                    return new FanlycSkuCheckResult('matched', $item, $checkedLabel);
                }
            }

            // Encontramos un arreglo de items parseable pero el producto objetivo no aparece en ninguno.
            return new FanlycSkuCheckResult('not_matched', ['items_checked' => $items], $checkedLabel);
        }

        return new FanlycSkuCheckResult('undetermined', null, $checkedLabel);
    }

    private function fieldMatches(array $item, array $fieldCandidates, string $target): bool
    {
        foreach ($fieldCandidates as $field) {
            if (! array_key_exists($field, $item)) {
                continue;
            }

            $value = strtoupper(trim((string) $item[$field]));

            if ($value !== '' && $value === strtoupper($target)) {
                return true;
            }
        }

        return false;
    }
}
