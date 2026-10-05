@extends('admin.layout')

@section('title', 'Saba · Configuración')
@section('subtitle', 'SKUs, términos, imágenes y fechas')

@section('topbar-actions')
    <a class="topbar-action" href="{{ route('admin.saba') }}">← Participantes</a>
@endsection

@php
    $tz = 'America/Panama';
    $prizes = (array) ($rules['cash_prizes'] ?? []);
@endphp

@section('content')
    <style>
        .saba-admin textarea { width:100%; min-height:110px; border:1px solid var(--bo-border-dark); border-radius:10px; padding:.65rem .8rem; font:inherit; font-size:.95rem; line-height:1.5; resize:vertical; background:var(--bo-surface);color:var(--bo-fg); }
        .saba-admin textarea:focus { outline:none; border-color:var(--bo-accent); box-shadow:0 0 0 2px rgba(192,18,44,.1); }
    </style>
    <div class="saba-admin">
    @if (session('status'))
        <div class="success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    {{-- Estado --}}
    <div class="page-card">
        <div class="page-title">
            <div>
                <h1>Estado de la promoción</h1>
                <p>
                    <span class="badge badge-{{ $isOpen ? 'green' : ($campaign->status === 'active' ? 'yellow' : 'gray') }}">
                        {{ $isOpen ? 'Recibiendo participaciones' : ($campaign->status === 'active' ? 'Activa fuera de fechas' : (['draft' => 'Borrador', 'paused' => 'Pausada', 'archived' => 'Archivada', 'active' => 'Activa'][$campaign->status] ?? $campaign->status)) }}
                    </span>
                    · {{ $products->count() }} SKU(s) cargados
                    · {{ $campaign->terms_text || ($rules['terms_pdf_url'] ?? null) ? 'Términos cargados' : 'Sin términos (se muestran las bases de la página)' }}
                </p>
            </div>
            <div class="responsive-actions">
                <form method="POST" action="{{ route('admin.saba.status') }}">
                    @csrf
                    @if($campaign->status === 'active')
                        <input type="hidden" name="status" value="paused">
                        <button class="btn btn-gray" type="submit">Pausar promoción</button>
                    @else
                        <input type="hidden" name="status" value="active">
                        <button class="btn btn-green" type="submit" @disabled($products->isEmpty())>Activar promoción</button>
                    @endif
                </form>
                <a class="btn btn-gray" href="/saba" target="_blank" rel="noopener">Ver página pública</a>
            </div>
        </div>
    </div>

    {{-- SKUs --}}
    <div class="page-card" style="margin-top:1rem;">
        <div class="page-title">
            <div>
                <h1>SKUs Saba participantes</h1>
                <p>La factura participa si contiene al menos uno de estos códigos de barras.</p>
            </div>
        </div>
        <div class="page-section">
            <form method="POST" action="{{ route('admin.saba.products.update') }}" class="form-grid">
                @csrf
                <div class="field">
                    <label for="products_text">Códigos de barras</label>
                    <textarea id="products_text" name="products_text" rows="7" placeholder="7501019006210&#10;7501019006227&#10;o separados por coma: 7501019006210, 7501019006227&#10;Opcional con nombre: 7501019006210|Saba Buenas Noches 10u">{{ old('products_text') }}</textarea>
                    <small style="color:#64748b;">Uno por línea <strong>o</strong> separados por coma. Si quieres ponerle nombre, usa una línea por producto con el formato <code>código|nombre</code>. Las líneas que empiezan con # se ignoran.</small>
                </div>
                <div class="field">
                    <label>¿Qué hacer con la lista actual?</label>
                    <label style="display:flex;gap:.5rem;align-items:center;font-weight:400;"><input type="radio" name="mode" value="append" checked style="width:auto;"> Agregar a los SKUs existentes (o actualizar su nombre)</label>
                    <label style="display:flex;gap:.5rem;align-items:center;font-weight:400;"><input type="radio" name="mode" value="replace" style="width:auto;"> Reemplazar toda la lista por la que estoy pegando</label>
                </div>
                <div>
                    <button class="btn btn-red" type="submit">Guardar SKUs</button>
                </div>
            </form>
        </div>
        <div class="page-section">
            @if($products->isEmpty())
                <div class="empty">No hay SKUs cargados todavía.</div>
            @else
                <div class="table-shell">
                    <table>
                        <thead><tr><th>Código</th><th>Nombre</th><th>Presentación</th><th></th></tr></thead>
                        <tbody>
                            @foreach($products as $product)
                                <tr>
                                    <td data-label="Código"><code>{{ $product->barcode }}</code></td>
                                    <td data-label="Nombre">{{ $product->product_name }}</td>
                                    <td data-label="Presentación">{{ $product->presentation ?? '—' }}</td>
                                    <td data-label="">
                                        <form method="POST" action="{{ route('admin.saba.products.destroy', $product->id) }}">
                                            @csrf
                                            <button class="btn btn-gray" type="submit">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Archivos --}}
    <div class="page-card" style="margin-top:1rem;">
        <div class="page-title">
            <div>
                <h1>Logo, productos, arte y términos en PDF</h1>
                <p>Mientras no subas archivos, la página usa un diseño provisional con el nombre de Saba en texto.</p>
            </div>
        </div>
        <div class="page-section">
            <form method="POST" action="{{ route('admin.saba.assets.update') }}" enctype="multipart/form-data" class="form-grid" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr));">
                @csrf
                @foreach([
                    'logo' => ['logo_url', 'Logo de Saba', 'PNG, SVG o WEBP con fondo transparente, máx. 4 MB.', 'image/png,image/jpeg,image/webp,image/svg+xml'],
                    'hero' => ['hero_url', 'Imagen principal (arte de campaña)', 'JPG, PNG o WEBP horizontal, máx. 8 MB.', 'image/png,image/jpeg,image/webp'],
                    'terms_pdf' => ['terms_pdf_url', 'Términos y condiciones (PDF)', 'PDF, máx. 10 MB. Se enlaza desde la página.', 'application/pdf'],
                ] as $input => [$key, $label, $help, $accept])
                    <div class="field">
                        <label for="{{ $input }}">{{ $label }}</label>
                        @if(!empty($rules[$key]))
                            @if($input === 'terms_pdf')
                                <p><a href="{{ $rules[$key] }}" target="_blank" rel="noopener">Ver PDF actual</a></p>
                            @else
                                <img src="{{ $rules[$key] }}" alt="{{ $label }}" style="max-height:90px;max-width:100%;border-radius:8px;border:1px solid #e5e7eb;background:#f8fafc;padding:4px;">
                            @endif
                            <label style="display:flex;gap:.4rem;align-items:center;font-weight:400;"><input type="checkbox" name="remove[]" value="{{ $key }}" style="width:auto;"> Quitar archivo actual</label>
                        @else
                            <p><small style="color:#94a3b8;">Sin archivo (provisional).</small></p>
                        @endif
                        <input id="{{ $input }}" name="{{ $input }}" type="file" accept="{{ $accept }}">
                        <small style="color:#64748b;">{{ $help }}</small>
                    </div>
                @endforeach
                <div class="field" style="grid-column:1 / -1;border-top:1px solid #e5e7eb;padding-top:1rem;">
                    <label for="product_images">Fotos de productos flotantes (hasta 5)</label>
                    <small style="color:#64748b;">Los productos que flotan alrededor del formulario, como las botellas de Malta. Usa PNG o WEBP <strong>sin fondo</strong> (recortados), máx. 4 MB cada una. Mientras no subas ninguna se muestran empaques ilustrados provisionales.</small>
                    @php($productImages = array_values(array_filter((array) ($rules['product_images'] ?? []))))
                    @if($productImages !== [])
                        <div style="display:flex;gap:.75rem;flex-wrap:wrap;margin:.5rem 0;">
                            @foreach($productImages as $imageUrl)
                                <label style="display:grid;gap:.35rem;justify-items:center;font-weight:400;border:1px solid #e5e7eb;border-radius:10px;padding:.5rem;background:#1f0b2e;">
                                    <img src="{{ $imageUrl }}" alt="Producto" style="height:90px;width:auto;max-width:120px;object-fit:contain;">
                                    <span style="display:flex;gap:.3rem;align-items:center;color:#fff;font-size:.8rem;"><input type="checkbox" name="remove_product_images[]" value="{{ $imageUrl }}" style="width:auto;"> Quitar</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                    <input id="product_images" name="product_images[]" type="file" accept="image/png,image/webp" multiple @disabled(count($productImages) >= 5)>
                </div>
                <div style="grid-column:1 / -1;">
                    <button class="btn btn-red" type="submit">Guardar archivos</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Configuracion general --}}
    <div class="page-card" style="margin-top:1rem;">
        <div class="page-title">
            <div>
                <h1>Fechas, premios, términos y entrega</h1>
                <p>Horas en horario de Panamá.</p>
            </div>
        </div>
        <div class="page-section">
            <form method="POST" action="{{ route('admin.saba.settings.update') }}" class="form-grid" style="grid-template-columns:repeat(2,minmax(0,1fr));">
                @csrf
                <div class="field" style="grid-column:1 / -1;">
                    <label for="name">Nombre de la promoción</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $campaign->name) }}" required>
                </div>
                <div class="field" style="grid-column:1 / -1;">
                    <label for="description">Descripción corta</label>
                    <textarea id="description" name="description" rows="2">{{ old('description', $campaign->description) }}</textarea>
                </div>
                <div class="field">
                    <label for="starts_at">Recibe participaciones desde</label>
                    <input id="starts_at" name="starts_at" type="datetime-local" value="{{ old('starts_at', $campaign->starts_at?->timezone($tz)->format('Y-m-d\TH:i')) }}" required>
                </div>
                <div class="field">
                    <label for="ends_at">Recibe participaciones hasta</label>
                    <input id="ends_at" name="ends_at" type="datetime-local" value="{{ old('ends_at', $campaign->ends_at?->timezone($tz)->format('Y-m-d\TH:i')) }}" required>
                </div>
                <div class="field">
                    <label for="invoice_issued_from">Facturas emitidas desde</label>
                    <input id="invoice_issued_from" name="invoice_issued_from" type="date" value="{{ old('invoice_issued_from', $rules['invoice_issued_from'] ?? '') }}" required>
                </div>
                <div class="field">
                    <label for="invoice_issued_to">Facturas emitidas hasta</label>
                    <input id="invoice_issued_to" name="invoice_issued_to" type="date" value="{{ old('invoice_issued_to', $rules['invoice_issued_to'] ?? '') }}" required>
                </div>
                <div class="field">
                    <label for="winner_slots">Cantidad de ganadoras</label>
                    <input id="winner_slots" name="winner_slots" type="number" min="1" max="500" value="{{ old('winner_slots', $rules['winner_slots'] ?? 50) }}" required>
                </div>
                <div class="field">
                    <label for="minimum_age">Edad mínima</label>
                    <input id="minimum_age" name="minimum_age" type="number" min="18" max="99" value="{{ old('minimum_age', $rules['minimum_age'] ?? 18) }}" required>
                </div>
                <div class="field">
                    <label>Premios en efectivo (1°, 2°, 3°)</label>
                    <div style="display:flex;gap:.5rem;">
                        <input name="prize_1" type="number" min="0" step="1" value="{{ old('prize_1', $prizes['1'] ?? '') }}" placeholder="1°">
                        <input name="prize_2" type="number" min="0" step="1" value="{{ old('prize_2', $prizes['2'] ?? '') }}" placeholder="2°">
                        <input name="prize_3" type="number" min="0" step="1" value="{{ old('prize_3', $prizes['3'] ?? '') }}" placeholder="3°">
                    </div>
                </div>
                <div class="field">
                    <label>Largo de la historia (mín. / máx. caracteres)</label>
                    <div style="display:flex;gap:.5rem;">
                        <input name="story_min_chars" type="number" min="10" max="1000" value="{{ old('story_min_chars', $rules['story_min_chars'] ?? 80) }}" required>
                        <input name="story_max_chars" type="number" min="100" max="5000" value="{{ old('story_max_chars', $rules['story_max_chars'] ?? 2000) }}" required>
                    </div>
                </div>
                <div class="field" style="grid-column:1 / -1;">
                    <label for="terms_text">Términos y condiciones (texto)</label>
                    <textarea id="terms_text" name="terms_text" rows="10" placeholder="Pega aquí el reglamento cuando lo tengas. Mientras esté vacío, la participante acepta las bases que se muestran en la página.">{{ old('terms_text', $campaign->terms_text) }}</textarea>
                    <small style="color:#64748b;">
                        @if($campaign->terms_version)
                            Versión vigente: {{ $campaign->terms_version }}. Si cambias el texto se crea una versión nueva automáticamente.
                        @else
                            Sin términos cargados.
                        @endif
                    </small>
                </div>
                <div class="field" style="grid-column:1 / -1;">
                    <label for="delivery_location">Lugar de entrega de premios</label>
                    <input id="delivery_location" name="delivery_location" type="text" value="{{ old('delivery_location', $campaign->delivery_location) }}">
                </div>
                <div class="field">
                    <label for="delivery_requirements">Condiciones de entrega</label>
                    <input id="delivery_requirements" name="delivery_requirements" type="text" value="{{ old('delivery_requirements', $campaign->delivery_requirements) }}">
                </div>
                <div class="field">
                    <label for="delivery_deadline">Fecha límite de entrega (opcional)</label>
                    <input id="delivery_deadline" name="delivery_deadline" type="datetime-local" value="{{ old('delivery_deadline', $campaign->delivery_deadline?->timezone($tz)->format('Y-m-d\TH:i')) }}">
                    <small style="color:#64748b;">Déjala vacía mientras aplique "30 días hábiles después del anuncio".</small>
                </div>
                <div style="grid-column:1 / -1;">
                    <button class="btn btn-red" type="submit">Guardar configuración</button>
                </div>
            </form>
        </div>
    </div>
    </div>
@endsection
