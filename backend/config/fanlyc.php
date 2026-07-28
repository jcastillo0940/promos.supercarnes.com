<?php

return [
    // SKU objetivo que debe aparecer en la factura para calificar. Confirmar formato exacto
    // una vez el equipo de la API interna (10.128.0.12/api/verificar) confirme el contrato de detalle de productos.
    'sku_target' => env('FANLYC_SKU_TARGET', ''),

    // Rutas candidatas (estilo data_get) dentro de la respuesta cruda del verificador donde podría venir
    // el detalle de líneas/productos. Se prueban en orden; la primera que resuelva a un array se usa.
    'sku_item_list_paths' => array_filter(array_map('trim', explode(',', (string) env(
        'FANLYC_SKU_ITEM_LIST_PATHS',
        'datos.items,datos.detalle,datos.productos,datos.detalleFactura,datos.listaItems'
    )))),

    // Nombres de campo candidatos (dentro de cada item) que podrían contener el código de producto/SKU.
    'sku_code_field_candidates' => array_filter(array_map('trim', explode(',', (string) env(
        'FANLYC_SKU_CODE_FIELD_CANDIDATES',
        'sku,codigo,codigoProducto,cod_articulo,codItem'
    )))),
];
