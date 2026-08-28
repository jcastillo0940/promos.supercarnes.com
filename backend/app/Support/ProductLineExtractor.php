<?php

namespace App\Support;

use App\Models\Campaign;

class ProductLineExtractor
{
    /** @return array{status: string, items: array<int, array<string, mixed>>} */
    public function evaluate(Campaign $campaign, array $resolvedInvoice): array
    {
        $payload = $resolvedInvoice['payload'] ?? null;
        if (! is_array($payload)) {
            return ['status' => 'undetermined', 'items' => []];
        }

        $items = $this->findItems($payload);
        if ($items === null) {
            return ['status' => 'undetermined', 'items' => []];
        }

        $rules = $campaign->productRules()->where('is_active', true)->get()->keyBy(fn ($rule) => strtoupper(trim($rule->barcode)));
        if ($rules->isEmpty()) {
            return ['status' => 'undetermined', 'items' => []];
        }

        $normalized = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $barcode = $this->firstValue($item, ['barcode', 'bar_code', 'sku', 'código', 'codigo', 'codigoProducto', 'cod_articulo', 'codItem', 'item_code']);
            $quantity = (float) ($this->firstValue($item, ['quantity', 'cantidad', 'qty', 'unidades', 'cantidadVendida']) ?? 0);
            $description = $this->firstValue($item, ['description', 'descripción', 'descripcion', 'nombre', 'product_name', 'detalle']);
            $unitPrice = $this->firstValue($item, ['unit_price', 'precio_unitario', 'precio', 'price']);
            $rule = $rules->get(strtoupper(trim((string) $barcode)));

            $normalized[] = [
                'barcode' => $barcode !== null ? strtoupper(trim((string) $barcode)) : null,
                'description' => $description !== null ? trim((string) $description) : null,
                'quantity' => max(0, $quantity),
                'unit_price' => $unitPrice !== null ? (float) $unitPrice : null,
                'is_eligible' => $rule !== null && $quantity > 0,
                'presentation' => $rule?->presentation,
                'source_payload' => $item,
            ];
        }

        return [
            'status' => $normalized === [] ? 'undetermined' : 'matched',
            'items' => $normalized,
        ];
    }

    private function findItems(array $payload): ?array
    {
        $paths = array_filter(array_map('trim', explode(',', (string) env(
            'DGI_INVOICE_ITEM_LIST_PATHS',
            'datos.items,datos.detalle,datos.productos,datos.detalleFactura,datos.listaItems,items,detalle,productos'
        ))));
        foreach ($paths as $path) {
            $items = data_get($payload, $path);
            if (is_array($items) && $items !== []) {
                return array_values($items);
            }
        }

        return null;
    }

    private function firstValue(array $item, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $item) && $item[$key] !== null && $item[$key] !== '') {
                return $item[$key];
            }
        }

        return null;
    }
}
