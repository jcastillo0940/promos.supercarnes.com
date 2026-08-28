<?php

return [
    // Producto objetivo que debe aparecer en la factura para calificar. Se aprueba si coincide
    // el código O el nombre (basta con que uno de los dos matchee). Contrato confirmado contra
    // la API interna (10.128.0.12/api/verificar): datos.productos[].código / .descripción.
    'sku_code_target' => env('FANLYC_SKU_CODE_TARGET', ''),
    'sku_name_target' => env('FANLYC_SKU_NAME_TARGET', ''),

    // Rutas candidatas (estilo data_get) dentro de la respuesta cruda del verificador donde podría venir
    // el detalle de líneas/productos. Se prueban en orden; la primera que resuelva a un array se usa.
    'sku_item_list_paths' => array_filter(array_map('trim', explode(',', (string) env(
        'FANLYC_SKU_ITEM_LIST_PATHS',
        'datos.items,datos.detalle,datos.productos,datos.detalleFactura,datos.listaItems'
    )))),

    // Nombres de campo candidatos (dentro de cada item) que podrían contener el código de producto/SKU.
    // 'código' (con tilde) es el campo real que devuelve hoy la API de DGI.
    'sku_code_field_candidates' => array_filter(array_map('trim', explode(',', (string) env(
        'FANLYC_SKU_CODE_FIELD_CANDIDATES',
        'código,codigo,sku,codigoProducto,cod_articulo,codItem'
    )))),

    // Nombres de campo candidatos que podrían contener el nombre/descripción del producto.
    // 'descripción' (con tilde) es el campo real que devuelve hoy la API de DGI.
    'sku_name_field_candidates' => array_filter(array_map('trim', explode(',', (string) env(
        'FANLYC_SKU_NAME_FIELD_CANDIDATES',
        'descripción,descripcion,nombre,nombreProducto,desc'
    )))),
];
