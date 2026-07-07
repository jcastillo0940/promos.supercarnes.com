@extends('admin.layout')

@section('title', 'Configuración')
@section('subtitle', 'Reglas de la promoción')

@section('topbar-actions')
    <a class="topbar-action hide-mobile" href="{{ route('admin.dashboard') }}">Dashboard</a>
    <a class="topbar-action hide-mobile" href="{{ route('admin.invoices') }}">Facturas</a>
    <a class="topbar-action hide-mobile" href="{{ route('admin.winners') }}">Ganadores</a>
    <a class="topbar-action hide-mobile" href="{{ route('admin.prize-delivery') }}">Entrega de premio</a>
    <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit">Cerrar sesión</button>
    </form>
@endsection

@section('content')
    @if (session('status'))
        <div class="alert-success">{{ session('status') }}</div>
    @endif

    <div class="page-card">
        <div class="page-title">
            <div>
                <h1>Configuración de la promoción</h1>
                <p>Activa el registro de facturas y ajusta las reglas de validación.</p>
            </div>
        </div>

        <div class="page-section">
            <form method="POST" action="{{ route('admin.invoice-backoffice.update') }}">
                @csrf
                <div class="page-card" style="box-shadow:none;border:1px solid #e5e7eb;">
                    <div class="page-section stack">
                        <div>
                            <p class="sidebar-title">Estado de la promoción</p>
                            <div class="field">
                                <label for="is_enabled">Registro de facturas activo</label>
                                <select id="is_enabled" name="is_enabled">
                                    <option value="1" @selected($settings->is_enabled)>Activo</option>
                                    <option value="0" @selected(! $settings->is_enabled)>Inactivo</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <p class="sidebar-title">Reglas de validación</p>
                            <div class="form-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                                <div class="field">
                                    <label for="min_purchase_amount">Monto mínimo de compra</label>
                                    <input id="min_purchase_amount" name="min_purchase_amount" type="number" min="0" step="0.01" value="{{ old('min_purchase_amount', $settings->min_purchase_amount) }}">
                                </div>
                                <div class="field">
                                    <label for="invoice_age_policy">Política de fecha de factura</label>
                                    <select id="invoice_age_policy" name="invoice_age_policy">
                                        <option value="none" @selected(old('invoice_age_policy', $settings->invoice_age_policy) === 'none')>Sin filtro de fecha</option>
                                        <option value="same_day" @selected(old('invoice_age_policy', $settings->invoice_age_policy) === 'same_day')>Solo del mismo día</option>
                                        <option value="last_24_hours" @selected(old('invoice_age_policy', $settings->invoice_age_policy) === 'last_24_hours')>Últimas 24 horas</option>
                                        <option value="days" @selected(!in_array(old('invoice_age_policy', $settings->invoice_age_policy), ['none','same_day','last_24_hours']))>Ventana de días</option>
                                    </select>
                                </div>
                                <div class="field">
                                    <label for="max_invoice_age_days">Máximo de días</label>
                                    <input id="max_invoice_age_days" name="max_invoice_age_days" type="number" min="0" max="30" value="{{ old('max_invoice_age_days', $settings->max_invoice_age_days) }}">
                                </div>
                                <div class="field">
                                    <label for="validation_mode">Modo de validación DGI</label>
                                    <select id="validation_mode" name="validation_mode">
                                        <option value="api" @selected(old('validation_mode', $settings->validation_mode) === 'api')>API</option>
                                        <option value="manual" @selected(old('validation_mode', $settings->validation_mode) === 'manual')>Manual</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="page-section" style="border-top:1px solid #e5e7eb;">
                        <button type="submit" class="btn btn-red">Guardar configuración</button>
                    </div>
                </div>
            </form>

            <div class="page-card" style="box-shadow:none;border:1px solid #e5e7eb;margin-top:18px;">
                <div class="page-section">
                    <p class="sidebar-title">Promociones</p>
                    <p class="small">Crea nuevas promociones con sus propias reglas o edita las existentes. Ejemplo: <code>/dia-del-padre</code>.</p>

                    <form method="POST" action="{{ route('admin.invoice-backoffice.campaigns.store') }}" style="margin:18px 0 24px;">
                        @csrf
                        <input type="hidden" name="key" value="{{ $backofficeKey }}">
                        <div class="page-card" style="box-shadow:none;border:1px solid #cbd5e1;background:linear-gradient(180deg,#f8fbff 0%,#ffffff 100%);">
                            <div class="page-section stack">
                                <div>
                                    <p class="sidebar-title">Nueva promoción</p>
                                    <p class="small">Define el nombre, el slug y las reglas base. Si dejas el slug vacío, se genera automáticamente.</p>
                                </div>
                                <div class="form-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                                    <div class="field">
                                        <label for="new_campaign_name">Nombre</label>
                                        <input id="new_campaign_name" name="name" type="text" value="{{ old('name') }}" placeholder="Del sueño al puesto">
                                    </div>
                                    <div class="field">
                                        <label for="new_campaign_slug">Slug</label>
                                        <input id="new_campaign_slug" name="slug" type="text" value="{{ old('slug') }}" placeholder="del-sueno-al-puesto">
                                    </div>
                                    <div class="field">
                                        <label for="new_campaign_status">Estado</label>
                                        <select id="new_campaign_status" name="status">
                                            <option value="draft" @selected(old('status', 'draft') === 'draft')>Borrador</option>
                                            <option value="active" @selected(old('status') === 'active')>Activa</option>
                                            <option value="paused" @selected(old('status') === 'paused')>Pausada</option>
                                            <option value="archived" @selected(old('status') === 'archived')>Archivada</option>
                                        </select>
                                    </div>
                                    <div class="field">
                                        <label for="new_campaign_participation_mode">Modo de participación</label>
                                        <select id="new_campaign_participation_mode" name="participation_mode">
                                            <option value="points" @selected(old('participation_mode', 'points') === 'points')>Puntos y ranking</option>
                                            <option value="threshold_form" @selected(old('participation_mode') === 'threshold_form')>Umbral de formulario</option>
                                        </select>
                                    </div>
                                    <div class="field">
                                        <label for="new_campaign_sort_order">Orden</label>
                                        <input id="new_campaign_sort_order" name="sort_order" type="number" min="0" max="9999" value="{{ old('sort_order', 0) }}">
                                    </div>
                                    <div class="field">
                                        <label for="new_campaign_starts_at">Inicio</label>
                                        <input id="new_campaign_starts_at" name="starts_at" type="datetime-local" value="{{ old('starts_at') }}">
                                    </div>
                                    <div class="field">
                                        <label for="new_campaign_ends_at">Fin</label>
                                        <input id="new_campaign_ends_at" name="ends_at" type="datetime-local" value="{{ old('ends_at') }}">
                                    </div>
                                    <div class="field">
                                        <label for="new_campaign_description">Descripción</label>
                                        <input id="new_campaign_description" name="description" type="text" value="{{ old('description') }}" placeholder="Resumen de la promoción">
                                    </div>
                                    <div class="field">
                                        <label for="new_campaign_card_image_url">Imagen card</label>
                                        <input id="new_campaign_card_image_url" name="card_image_url" type="text" value="{{ old('card_image_url') }}" placeholder="/images/promo-card.png">
                                    </div>
                                    <div class="field">
                                        <label for="new_campaign_hero_image_url">Imagen hero</label>
                                        <input id="new_campaign_hero_image_url" name="hero_image_url" type="text" value="{{ old('hero_image_url') }}" placeholder="/images/promo-hero.png">
                                    </div>
                                </div>
                                <div class="form-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
                                    <div class="field">
                                        <label for="new_campaign_invoice_min_amount_for_shot">Monto mínimo por factura</label>
                                        <input id="new_campaign_invoice_min_amount_for_shot" name="invoice_min_amount_for_shot" type="number" min="0" step="0.01" value="{{ old('invoice_min_amount_for_shot', 25) }}">
                                    </div>
                                    <div class="field">
                                        <label for="new_campaign_amount_per_point">Monto por punto</label>
                                        <input id="new_campaign_amount_per_point" name="amount_per_point" type="number" min="0" step="0.01" value="{{ old('amount_per_point', 25) }}">
                                    </div>
                                    <div class="field">
                                        <label for="new_campaign_points_per_block">Puntos por bloque</label>
                                        <input id="new_campaign_points_per_block" name="points_per_block" type="number" min="1" value="{{ old('points_per_block', 1) }}">
                                    </div>
                                    <div class="field">
                                        <label for="new_campaign_daily_max_points">Máximo puntos por día</label>
                                        <input id="new_campaign_daily_max_points" name="daily_max_points" type="number" min="1" value="{{ old('daily_max_points', 1000) }}">
                                    </div>
                                    <div class="field">
                                        <label for="new_campaign_daily_max_invoices">Máximo facturas por día</label>
                                        <input id="new_campaign_daily_max_invoices" name="daily_max_invoices" type="number" min="1" value="{{ old('daily_max_invoices', 100) }}">
                                    </div>
                                    <div class="field">
                                        <label for="new_campaign_coupon_ttl_hours">Vigencia cupón (horas)</label>
                                        <input id="new_campaign_coupon_ttl_hours" name="coupon_ttl_hours" type="number" min="1" value="{{ old('coupon_ttl_hours', 72) }}">
                                    </div>
                                </div>
                                <div class="form-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                                    <div class="field">
                                        <label for="new_campaign_entry_threshold_amount">Límite de participación</label>
                                        <input id="new_campaign_entry_threshold_amount" name="entry_threshold_amount" type="number" min="0" step="0.01" value="{{ old('entry_threshold_amount') }}" placeholder="300">
                                        <small class="small">Usa este valor para promos como Del sueño al puesto. No activa puntos ni ranking.</small>
                                    </div>
                                    <div class="field">
                                        <label>&nbsp;</label>
                                        <label style="display:flex;align-items:center;gap:10px;">
                                            <input type="checkbox" name="entry_requires_approval" value="1" @checked(old('entry_requires_approval'))>
                                            Requiere aprobación manual para entrar
                                        </label>
                                    </div>
                                </div>
                                <div class="form-grid" style="grid-template-columns: repeat(4, minmax(0, 1fr));">
                                    <label class="small" style="display:flex;align-items:center;gap:10px;">
                                        <input type="checkbox" name="is_listed" value="1" @checked(old('is_listed', true))>
                                        Visible en el catálogo
                                    </label>
                                    <label class="small" style="display:flex;align-items:center;gap:10px;">
                                        <input type="checkbox" name="invoice_scan_enabled" value="1" @checked(old('invoice_scan_enabled', true))>
                                        Habilitar facturas
                                    </label>
                                    <label class="small" style="display:flex;align-items:center;gap:10px;">
                                        <input type="checkbox" name="games_enabled" value="1" @checked(old('games_enabled'))>
                                        Habilitar juegos
                                    </label>
                                    <label class="small" style="display:flex;align-items:center;gap:10px;">
                                        <input type="checkbox" name="redemption_enabled" value="1" @checked(old('redemption_enabled'))>
                                        Habilitar redención
                                    </label>
                                </div>
                                <label class="small" style="display:flex;align-items:center;gap:10px;">
                                    <input type="checkbox" name="major_prizes_enabled" value="1" @checked(old('major_prizes_enabled'))>
                                    Habilitar premios mayores
                                </label>
                            </div>
                            <div class="page-section" style="border-top:1px solid #e5e7eb;">
                                <button type="submit" class="btn btn-red">Crear promoción</button>
                            </div>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('admin.invoice-backoffice.campaigns.update') }}">
                        @csrf
                        <input type="hidden" name="key" value="{{ $backofficeKey }}">
                        <div class="stack">
                            @foreach ($campaigns as $campaign)
                                <div class="page-card" style="box-shadow:none;border:1px solid #e5e7eb;">
                                    <input type="hidden" name="campaigns[{{ $loop->index }}][id]" value="{{ $campaign->id }}">
                                    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px;">
                                        <div>
                                            <strong>{{ $campaign->name }}</strong>
                                            <div class="small">/{{ $campaign->slug }} · Estado actual: {{ $campaign->status }}</div>
                                        </div>
                                        <button
                                            type="submit"
                                            class="btn {{ $campaign->status === 'active' ? 'btn-gray' : 'btn-red' }}"
                                            formaction="{{ route('admin.invoice-backoffice.campaigns.toggle-status', $campaign) }}"
                                            formmethod="POST"
                                            name="status"
                                            value="{{ $campaign->status === 'active' ? 'paused' : 'active' }}"
                                        >
                                            {{ $campaign->status === 'active' ? 'Desactivar promo' : 'Activar promo' }}
                                        </button>
                                    </div>
                                    <div class="form-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                                        <div class="field">
                                            <label>Nombre</label>
                                            <input type="text" name="campaigns[{{ $loop->index }}][name]" value="{{ old("campaigns.$loop->index.name", $campaign->name) }}">
                                        </div>
                                        <div class="field">
                                            <label>Slug</label>
                                            <input type="text" name="campaigns[{{ $loop->index }}][slug]" value="{{ old("campaigns.$loop->index.slug", $campaign->slug) }}">
                                        </div>
                                        <div class="field">
                                            <label>Orden</label>
                                            <input type="number" name="campaigns[{{ $loop->index }}][sort_order]" value="{{ old("campaigns.$loop->index.sort_order", $campaign->sort_order ?? 0) }}">
                                        </div>
                                    <div class="field">
                                        <label>Status</label>
                                        <select name="campaigns[{{ $loop->index }}][status]">
                                            <option value="draft" @selected(old("campaigns.$loop->index.status", $campaign->status) === 'draft')>Draft</option>
                                            <option value="active" @selected(old("campaigns.$loop->index.status", $campaign->status) === 'active')>Active</option>
                                                <option value="paused" @selected(old("campaigns.$loop->index.status", $campaign->status) === 'paused')>Paused</option>
                                            <option value="archived" @selected(old("campaigns.$loop->index.status", $campaign->status) === 'archived')>Archived</option>
                                        </select>
                                    </div>
                                    <div class="field">
                                        <label>Tipo de promo</label>
                                        <div style="min-height:44px;display:flex;align-items:center;">
                                            <span class="badge badge-{{ ($campaign->participation_mode ?? 'points') === 'threshold_form' ? 'yellow' : 'green' }}">
                                                {{ ($campaign->participation_mode ?? 'points') === 'threshold_form' ? 'Umbral de formulario' : 'Puntos y ranking' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="field">
                                        <label>Modo de participación</label>
                                        <select name="campaigns[{{ $loop->index }}][participation_mode]">
                                            <option value="points" @selected(old("campaigns.$loop->index.participation_mode", $campaign->participation_mode ?? 'points') === 'points')>Puntos y ranking</option>
                                            <option value="threshold_form" @selected(old("campaigns.$loop->index.participation_mode", $campaign->participation_mode ?? 'points') === 'threshold_form')>Umbral de formulario</option>
                                            </select>
                                        </div>
                                        <div class="field">
                                            <label>Imagen card</label>
                                            <input type="text" name="campaigns[{{ $loop->index }}][card_image_url]" value="{{ old("campaigns.$loop->index.card_image_url", $campaign->card_image_url) }}">
                                        </div>
                                    <div class="field">
                                        <label>Imagen hero</label>
                                        <input type="text" name="campaigns[{{ $loop->index }}][hero_image_url]" value="{{ old("campaigns.$loop->index.hero_image_url", $campaign->hero_image_url) }}">
                                    </div>
                                    <div class="field">
                                        <label>Inicio</label>
                                        <input type="datetime-local" name="campaigns[{{ $loop->index }}][starts_at]" value="{{ old("campaigns.$loop->index.starts_at", optional($campaign->starts_at)->format('Y-m-d\TH:i')) }}">
                                    </div>
                                    <div class="field">
                                        <label>Fin</label>
                                        <input type="datetime-local" name="campaigns[{{ $loop->index }}][ends_at]" value="{{ old("campaigns.$loop->index.ends_at", optional($campaign->ends_at)->format('Y-m-d\TH:i')) }}">
                                    </div>
                                </div>
                                    <div class="form-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
                                        <div class="field">
                                            <label>Monto mínimo por factura</label>
                                            <input type="number" min="0" step="0.01" name="campaigns[{{ $loop->index }}][invoice_min_amount_for_shot]" value="{{ old("campaigns.$loop->index.invoice_min_amount_for_shot", $campaign->invoice_min_amount_for_shot ?? 25) }}">
                                        </div>
                                        <div class="field">
                                            <label>Monto por punto</label>
                                            <input type="number" min="0" step="0.01" name="campaigns[{{ $loop->index }}][amount_per_point]" value="{{ old("campaigns.$loop->index.amount_per_point", $campaign->amount_per_point ?? 25) }}">
                                        </div>
                                        <div class="field">
                                            <label>Puntos por bloque</label>
                                            <input type="number" min="1" name="campaigns[{{ $loop->index }}][points_per_block]" value="{{ old("campaigns.$loop->index.points_per_block", $campaign->points_per_block ?? 1) }}">
                                        </div>
                                        <div class="field">
                                            <label>Máximo puntos por día</label>
                                            <input type="number" min="1" name="campaigns[{{ $loop->index }}][daily_max_points]" value="{{ old("campaigns.$loop->index.daily_max_points", $campaign->daily_max_points ?? 1000) }}">
                                        </div>
                                        <div class="field">
                                            <label>Máximo facturas por día</label>
                                            <input type="number" min="1" name="campaigns[{{ $loop->index }}][daily_max_invoices]" value="{{ old("campaigns.$loop->index.daily_max_invoices", $campaign->daily_max_invoices ?? 100) }}">
                                        </div>
                                        <div class="field">
                                            <label>Vigencia cupón (horas)</label>
                                            <input type="number" min="1" name="campaigns[{{ $loop->index }}][coupon_ttl_hours]" value="{{ old("campaigns.$loop->index.coupon_ttl_hours", $campaign->coupon_ttl_hours ?? 72) }}">
                                        </div>
                                    </div>
                                    <div class="form-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                                        <div class="field">
                                            <label>Descripción</label>
                                            <input type="text" name="campaigns[{{ $loop->index }}][description]" value="{{ old("campaigns.$loop->index.description", $campaign->description) }}">
                                        </div>
                                        <div class="field">
                                            <label>Límite de participación</label>
                                            <input type="number" min="0" step="0.01" name="campaigns[{{ $loop->index }}][entry_threshold_amount]" value="{{ old("campaigns.$loop->index.entry_threshold_amount", $campaign->entry_threshold_amount) }}" placeholder="300">
                                        </div>
                                    </div>
                                    <div class="form-grid" style="grid-template-columns: repeat(4, minmax(0, 1fr));">
                                        <label class="small" style="display:flex;align-items:center;gap:10px;">
                                            <input type="checkbox" name="campaigns[{{ $loop->index }}][is_listed]" value="1" @checked(old("campaigns.$loop->index.is_listed", $campaign->is_listed ?? true))>
                                            Mostrar en el catálogo
                                        </label>
                                        <label class="small" style="display:flex;align-items:center;gap:10px;">
                                            <input type="checkbox" name="campaigns[{{ $loop->index }}][invoice_scan_enabled]" value="1" @checked(old("campaigns.$loop->index.invoice_scan_enabled", $campaign->invoice_scan_enabled ?? true))>
                                            Habilitar facturas
                                        </label>
                                        <label class="small" style="display:flex;align-items:center;gap:10px;">
                                            <input type="checkbox" name="campaigns[{{ $loop->index }}][games_enabled]" value="1" @checked(old("campaigns.$loop->index.games_enabled", $campaign->games_enabled ?? false))>
                                            Habilitar juegos
                                        </label>
                                        <label class="small" style="display:flex;align-items:center;gap:10px;">
                                            <input type="checkbox" name="campaigns[{{ $loop->index }}][redemption_enabled]" value="1" @checked(old("campaigns.$loop->index.redemption_enabled", $campaign->redemption_enabled ?? false))>
                                            Habilitar redención
                                        </label>
                                    </div>
                                    <label class="small" style="display:flex;align-items:center;gap:10px;">
                                        <input type="checkbox" name="campaigns[{{ $loop->index }}][major_prizes_enabled]" value="1" @checked(old("campaigns.$loop->index.major_prizes_enabled", $campaign->major_prizes_enabled ?? false))>
                                        Habilitar premios mayores
                                    </label>
                                    <label class="small" style="display:flex;align-items:center;gap:10px;">
                                        <input type="checkbox" name="campaigns[{{ $loop->index }}][entry_requires_approval]" value="1" @checked(old("campaigns.$loop->index.entry_requires_approval", $campaign->entry_requires_approval ?? false))>
                                        Requiere aprobación manual para entrar
                                    </label>
                                </div>
                            @endforeach
                        </div>

                        <button type="submit" class="btn btn-red">Guardar promociones</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
