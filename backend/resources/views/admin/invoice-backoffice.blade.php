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

@push('styles')
    <style>
        .config-section-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: .25rem;
        }
        .config-section-head h2 { margin: 0; font-size: 1.05rem; }
        .config-section-head p { margin: .2rem 0 0; color: #64748b; font-size: .85rem; }
        .field-hint { display: block; margin-top: .35rem; color: #94a3b8; font-size: .78rem; }

        .campaign-list { display: grid; gap: 1rem; }
        .campaign-card {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            overflow: hidden;
            background: #fff;
        }
        .campaign-card > summary {
            list-style: none;
            cursor: pointer;
            padding: 1rem 1.1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            background: #f8fafc;
        }
        .campaign-card > summary::-webkit-details-marker { display: none; }
        .campaign-card > summary::marker { content: ''; }
        .campaign-summary-main { display: flex; align-items: center; gap: .65rem; flex-wrap: wrap; min-width: 0; }
        .campaign-summary-main strong { font-size: 1rem; }
        .campaign-summary-main .mono { color: #64748b; font-size: .82rem; }
        .campaign-summary-right { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
        .campaign-chevron {
            display: inline-flex;
            width: 24px; height: 24px;
            align-items: center; justify-content: center;
            border-radius: 8px;
            background: #e2e8f0;
            color: #475569;
            transition: transform .18s ease;
            flex-shrink: 0;
        }
        .campaign-card[open] .campaign-chevron { transform: rotate(180deg); }
        .campaign-body { padding: 1.1rem; border-top: 1px solid #e5e7eb; }
        .field-group { margin-bottom: 1.25rem; }
        .field-group:last-of-type { margin-bottom: 0; }
        .field-group-title {
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #94a3b8;
            margin: 0 0 .7rem;
            padding-bottom: .5rem;
            border-bottom: 1px dashed #e2e8f0;
        }
        .toggle-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: .6rem .9rem; }
        .switch-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .55rem .7rem;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #f8fafc;
        }
        .switch-row span { font-size: .85rem; font-weight: 600; color: #334155; }
        .switch { position: relative; display: inline-block; width: 40px; height: 22px; flex-shrink: 0; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .switch-track {
            position: absolute; inset: 0; cursor: pointer;
            background: #cbd5e1; border-radius: 999px; transition: background .15s ease;
        }
        .switch-track::before {
            content: ''; position: absolute; height: 16px; width: 16px; left: 3px; top: 3px;
            background: #fff; border-radius: 50%; transition: transform .15s ease;
            box-shadow: 0 1px 2px rgba(0,0,0,.25);
        }
        .switch input:checked + .switch-track { background: #16a34a; }
        .switch input:checked + .switch-track::before { transform: translateX(18px); }
        .mode-fields { border-radius: 12px; padding: .9rem; background: #fafaff; border: 1px dashed #ddd6fe; }
        .mode-fields + .mode-fields { margin-top: .8rem; }
        .campaign-actions {
            display: flex; justify-content: flex-end; gap: .6rem; flex-wrap: wrap;
            margin-top: 1.2rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;
        }
        .new-campaign-toggle > summary {
            list-style: none; cursor: pointer;
            display: flex; align-items: center; gap: .6rem;
            font-weight: 700; color: #b91c1c; padding: .2rem 0;
        }
        .new-campaign-toggle > summary::-webkit-details-marker { display: none; }
        .new-campaign-toggle > summary::marker { content: ''; }
    </style>
@endpush

@push('scripts')
    <script>
        (function () {
            function applyMode(select) {
                var card = select.closest('[data-campaign-scope]');
                if (!card) return;
                var mode = select.value;
                card.querySelectorAll('[data-mode-group]').forEach(function (group) {
                    group.style.display = group.getAttribute('data-mode-group') === mode ? '' : 'none';
                });
            }
            document.querySelectorAll('[data-participation-mode]').forEach(function (select) {
                applyMode(select);
                select.addEventListener('change', function () { applyMode(select); });
            });
        })();
    </script>
@endpush

@section('content')
    @if (session('status'))
        <div class="alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <div class="page-card">
        <div class="page-title">
            <div>
                <h1>Configuración de la promoción</h1>
                <p>Activa el registro de facturas y ajusta las reglas de validación global.</p>
            </div>
        </div>

        <div class="page-section">
            <form method="POST" action="{{ route('admin.invoice-backoffice.update') }}">
                @csrf
                <div class="field-group">
                    <p class="field-group-title">Estado del registro de facturas</p>
                    <div class="switch-row" style="max-width:340px;">
                        <span>Registro de facturas activo</span>
                        <select id="is_enabled" name="is_enabled" style="width:auto;min-height:36px;">
                            <option value="1" @selected($settings->is_enabled)>Activo</option>
                            <option value="0" @selected(! $settings->is_enabled)>Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="field-group">
                    <p class="field-group-title">Reglas de validación</p>
                    <div class="form-grid" style="grid-template-columns: repeat(4, minmax(0, 1fr));">
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
                <div class="responsive-actions">
                    <button type="submit" class="btn btn-red">Guardar configuración</button>
                </div>
            </form>
        </div>
    </div>

    <div class="page-card" style="margin-top:18px;">
        <div class="page-section">
            <div class="config-section-head">
                <div>
                    <h2>Promociones</h2>
                    <p>Cada promoción se edita y guarda por separado. Haz clic en una tarjeta para ver o cambiar sus reglas.</p>
                </div>
            </div>
        </div>

        <div class="page-section" style="border-top:1px solid #e5e7eb;">
            <details class="new-campaign-toggle">
                <summary>＋ Crear nueva promoción</summary>
                <form method="POST" action="{{ route('admin.invoice-backoffice.campaigns.store') }}" style="margin-top:14px;">
                    @csrf
                    <input type="hidden" name="key" value="{{ $backofficeKey }}">
                    <div class="page-card" style="box-shadow:none;border:1px solid #cbd5e1;background:linear-gradient(180deg,#f8fbff 0%,#ffffff 100%);">
                        <div class="page-section">
                            <div class="field-group">
                                <p class="field-group-title">Información general</p>
                                <div class="form-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                                    <div class="field">
                                        <label for="new_campaign_name">Nombre</label>
                                        <input id="new_campaign_name" name="name" type="text" value="{{ old('name') }}" placeholder="Del sueño al puesto">
                                    </div>
                                    <div class="field">
                                        <label for="new_campaign_slug">Slug</label>
                                        <input id="new_campaign_slug" name="slug" type="text" value="{{ old('slug') }}" placeholder="del-sueno-al-puesto">
                                        <span class="field-hint">Se usa en la URL pública. Si lo dejas vacío, se genera solo.</span>
                                    </div>
                                    <div class="field">
                                        <label for="new_campaign_description">Descripción</label>
                                        <input id="new_campaign_description" name="description" type="text" value="{{ old('description') }}" placeholder="Resumen de la promoción">
                                    </div>
                                    <div class="field">
                                        <label for="new_campaign_status">Estado inicial</label>
                                        <select id="new_campaign_status" name="status">
                                            <option value="draft" @selected(old('status', 'draft') === 'draft')>Borrador</option>
                                            <option value="active" @selected(old('status') === 'active')>Activa</option>
                                            <option value="paused" @selected(old('status') === 'paused')>Pausada</option>
                                            <option value="archived" @selected(old('status') === 'archived')>Archivada</option>
                                        </select>
                                    </div>
                                    <div class="field">
                                        <label for="new_campaign_sort_order">Orden en el catálogo</label>
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
                                        <label for="new_campaign_card_image_url">Imagen card</label>
                                        <input id="new_campaign_card_image_url" name="card_image_url" type="text" value="{{ old('card_image_url') }}" placeholder="/images/promo-card.png">
                                    </div>
                                    <div class="field">
                                        <label for="new_campaign_hero_image_url">Imagen hero</label>
                                        <input id="new_campaign_hero_image_url" name="hero_image_url" type="text" value="{{ old('hero_image_url') }}" placeholder="/images/promo-hero.png">
                                    </div>
                                </div>
                            </div>

                            <div class="field-group" data-campaign-scope>
                                <p class="field-group-title">Modo de participación</p>
                                <div class="field" style="max-width:360px;margin-bottom:.9rem;">
                                    <select id="new_campaign_participation_mode" name="participation_mode" data-participation-mode>
                                        <option value="points" @selected(old('participation_mode', 'points') === 'points')>Puntos y ranking</option>
                                        <option value="threshold_form" @selected(old('participation_mode') === 'threshold_form')>Umbral de facturación (formulario)</option>
                                    </select>
                                    <span class="field-hint">"Puntos y ranking" da tiros/puntos por factura. "Umbral" acumula un monto en dólares para calificar (ej. Del sueño al puesto).</span>
                                </div>

                                <div class="mode-fields" data-mode-group="points">
                                    <div class="form-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
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
                                            <label for="new_campaign_coupon_ttl_hours">Vigencia cupón (horas)</label>
                                            <input id="new_campaign_coupon_ttl_hours" name="coupon_ttl_hours" type="number" min="1" value="{{ old('coupon_ttl_hours', 72) }}">
                                        </div>
                                    </div>
                                    <div class="toggle-grid" style="margin-top:.9rem;">
                                        <label class="switch-row">
                                            <span>Habilitar juegos</span>
                                            <span class="switch"><input type="checkbox" name="games_enabled" value="1" @checked(old('games_enabled'))><span class="switch-track"></span></span>
                                        </label>
                                        <label class="switch-row">
                                            <span>Habilitar redención</span>
                                            <span class="switch"><input type="checkbox" name="redemption_enabled" value="1" @checked(old('redemption_enabled'))><span class="switch-track"></span></span>
                                        </label>
                                        <label class="switch-row">
                                            <span>Habilitar premios mayores</span>
                                            <span class="switch"><input type="checkbox" name="major_prizes_enabled" value="1" @checked(old('major_prizes_enabled'))><span class="switch-track"></span></span>
                                        </label>
                                    </div>
                                </div>

                                <div class="mode-fields" data-mode-group="threshold_form">
                                    <div class="form-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                                        <div class="field">
                                            <label for="new_campaign_entry_threshold_amount">Meta de facturación ($)</label>
                                            <input id="new_campaign_entry_threshold_amount" name="entry_threshold_amount" type="number" min="0" step="0.01" value="{{ old('entry_threshold_amount') }}" placeholder="100">
                                            <span class="field-hint">Monto acumulado en facturas para calificar.</span>
                                        </div>
                                        <label class="switch-row" style="align-self:end;">
                                            <span>Requiere aprobación manual</span>
                                            <span class="switch"><input type="checkbox" name="entry_requires_approval" value="1" @checked(old('entry_requires_approval'))><span class="switch-track"></span></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="field-group">
                                <p class="field-group-title">Reglas comunes de factura</p>
                                <div class="form-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                                    <div class="field">
                                        <label for="new_campaign_invoice_min_amount_for_shot">Monto mínimo por factura</label>
                                        <input id="new_campaign_invoice_min_amount_for_shot" name="invoice_min_amount_for_shot" type="number" min="0" step="0.01" value="{{ old('invoice_min_amount_for_shot', 25) }}">
                                    </div>
                                    <div class="field">
                                        <label for="new_campaign_daily_max_invoices">Máximo facturas por día</label>
                                        <input id="new_campaign_daily_max_invoices" name="daily_max_invoices" type="number" min="1" value="{{ old('daily_max_invoices', 100) }}">
                                    </div>
                                </div>
                                <div class="toggle-grid" style="margin-top:.9rem;">
                                    <label class="switch-row">
                                        <span>Visible en el catálogo</span>
                                        <span class="switch"><input type="checkbox" name="is_listed" value="1" @checked(old('is_listed', true))><span class="switch-track"></span></span>
                                    </label>
                                    <label class="switch-row">
                                        <span>Habilitar registro de facturas</span>
                                        <span class="switch"><input type="checkbox" name="invoice_scan_enabled" value="1" @checked(old('invoice_scan_enabled', true))><span class="switch-track"></span></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="page-section" style="border-top:1px solid #e5e7eb;">
                            <button type="submit" class="btn btn-red">Crear promoción</button>
                        </div>
                    </div>
                </form>
            </details>
        </div>

        <div class="page-section campaign-list" style="border-top:1px solid #e5e7eb;">
            @foreach ($campaigns as $campaign)
                @php
                    $mode = old("campaigns.{$campaign->id}.participation_mode", $campaign->participation_mode ?? 'points');
                    $statusLabels = ['active' => ['green', 'Activa'], 'paused' => ['gray', 'Pausada'], 'draft' => ['yellow', 'Borrador'], 'archived' => ['red', 'Archivada']];
                    [$statusColor, $statusLabel] = $statusLabels[$campaign->status] ?? ['gray', $campaign->status];
                @endphp
                <form method="POST" action="{{ route('admin.invoice-backoffice.campaigns.update') }}">
                    @csrf
                    <input type="hidden" name="key" value="{{ $backofficeKey }}">
                    <input type="hidden" name="campaigns[{{ $campaign->id }}][id]" value="{{ $campaign->id }}">
                    <details class="campaign-card" data-campaign-scope @if($campaign->status === 'active') open @endif>
                        <summary>
                            <div class="campaign-summary-main">
                                <span class="campaign-chevron">⌄</span>
                                <strong>{{ $campaign->name }}</strong>
                                <span class="mono">/{{ $campaign->slug }}</span>
                            </div>
                            <div class="campaign-summary-right">
                                <span class="badge badge-{{ $statusColor }}">{{ $statusLabel }}</span>
                                <span class="badge badge-{{ $mode === 'threshold_form' ? 'yellow' : 'gray' }}">{{ $mode === 'threshold_form' ? 'Umbral $' . number_format((float) ($campaign->entry_threshold_amount ?: 100), 0) : 'Puntos y ranking' }}</span>
                            </div>
                        </summary>
                        <div class="campaign-body">
                            <div class="field-group">
                                <p class="field-group-title">Información general</p>
                                <div class="form-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
                                    <div class="field">
                                        <label>Nombre</label>
                                        <input type="text" name="campaigns[{{ $campaign->id }}][name]" value="{{ old("campaigns.{$campaign->id}.name", $campaign->name) }}">
                                    </div>
                                    <div class="field">
                                        <label>Slug</label>
                                        <input type="text" name="campaigns[{{ $campaign->id }}][slug]" value="{{ old("campaigns.{$campaign->id}.slug", $campaign->slug) }}">
                                    </div>
                                    <div class="field">
                                        <label>Estado</label>
                                        <select name="campaigns[{{ $campaign->id }}][status]">
                                            <option value="draft" @selected(old("campaigns.{$campaign->id}.status", $campaign->status) === 'draft')>Borrador</option>
                                            <option value="active" @selected(old("campaigns.{$campaign->id}.status", $campaign->status) === 'active')>Activa</option>
                                            <option value="paused" @selected(old("campaigns.{$campaign->id}.status", $campaign->status) === 'paused')>Pausada</option>
                                            <option value="archived" @selected(old("campaigns.{$campaign->id}.status", $campaign->status) === 'archived')>Archivada</option>
                                        </select>
                                    </div>
                                    <div class="field">
                                        <label>Orden en el catálogo</label>
                                        <input type="number" name="campaigns[{{ $campaign->id }}][sort_order]" value="{{ old("campaigns.{$campaign->id}.sort_order", $campaign->sort_order ?? 0) }}">
                                    </div>
                                    <div class="field">
                                        <label>Inicio</label>
                                        <input type="datetime-local" name="campaigns[{{ $campaign->id }}][starts_at]" value="{{ old("campaigns.{$campaign->id}.starts_at", optional($campaign->starts_at)->format('Y-m-d\TH:i')) }}">
                                    </div>
                                    <div class="field">
                                        <label>Fin</label>
                                        <input type="datetime-local" name="campaigns[{{ $campaign->id }}][ends_at]" value="{{ old("campaigns.{$campaign->id}.ends_at", optional($campaign->ends_at)->format('Y-m-d\TH:i')) }}">
                                    </div>
                                    <div class="field" style="grid-column: 1 / -1;">
                                        <label>Descripción</label>
                                        <input type="text" name="campaigns[{{ $campaign->id }}][description]" value="{{ old("campaigns.{$campaign->id}.description", $campaign->description) }}">
                                    </div>
                                    <div class="field">
                                        <label>Imagen card</label>
                                        <input type="text" name="campaigns[{{ $campaign->id }}][card_image_url]" value="{{ old("campaigns.{$campaign->id}.card_image_url", $campaign->card_image_url) }}">
                                    </div>
                                    <div class="field">
                                        <label>Imagen hero</label>
                                        <input type="text" name="campaigns[{{ $campaign->id }}][hero_image_url]" value="{{ old("campaigns.{$campaign->id}.hero_image_url", $campaign->hero_image_url) }}">
                                    </div>
                                </div>
                            </div>

                            <div class="field-group">
                                <p class="field-group-title">Modo de participación</p>
                                <div class="field" style="max-width:360px;margin-bottom:.9rem;">
                                    <select name="campaigns[{{ $campaign->id }}][participation_mode]" data-participation-mode>
                                        <option value="points" @selected($mode === 'points')>Puntos y ranking</option>
                                        <option value="threshold_form" @selected($mode === 'threshold_form')>Umbral de facturación (formulario)</option>
                                    </select>
                                    <span class="field-hint">"Puntos y ranking" da tiros/puntos por factura. "Umbral" acumula un monto en dólares para calificar (ej. Del sueño al puesto).</span>
                                </div>

                                <div class="mode-fields" data-mode-group="points">
                                    <div class="form-grid" style="grid-template-columns: repeat(4, minmax(0, 1fr));">
                                        <div class="field">
                                            <label>Monto por punto</label>
                                            <input type="number" min="0" step="0.01" name="campaigns[{{ $campaign->id }}][amount_per_point]" value="{{ old("campaigns.{$campaign->id}.amount_per_point", $campaign->amount_per_point ?? 25) }}">
                                        </div>
                                        <div class="field">
                                            <label>Puntos por bloque</label>
                                            <input type="number" min="1" name="campaigns[{{ $campaign->id }}][points_per_block]" value="{{ old("campaigns.{$campaign->id}.points_per_block", $campaign->points_per_block ?? 1) }}">
                                        </div>
                                        <div class="field">
                                            <label>Máximo puntos por día</label>
                                            <input type="number" min="1" name="campaigns[{{ $campaign->id }}][daily_max_points]" value="{{ old("campaigns.{$campaign->id}.daily_max_points", $campaign->daily_max_points ?? 1000) }}">
                                        </div>
                                        <div class="field">
                                            <label>Vigencia cupón (horas)</label>
                                            <input type="number" min="1" name="campaigns[{{ $campaign->id }}][coupon_ttl_hours]" value="{{ old("campaigns.{$campaign->id}.coupon_ttl_hours", $campaign->coupon_ttl_hours ?? 72) }}">
                                        </div>
                                    </div>
                                    <div class="toggle-grid" style="margin-top:.9rem;">
                                        <label class="switch-row">
                                            <span>Habilitar juegos</span>
                                            <span class="switch"><input type="checkbox" name="campaigns[{{ $campaign->id }}][games_enabled]" value="1" @checked(old("campaigns.{$campaign->id}.games_enabled", $campaign->games_enabled ?? false))><span class="switch-track"></span></span>
                                        </label>
                                        <label class="switch-row">
                                            <span>Habilitar redención</span>
                                            <span class="switch"><input type="checkbox" name="campaigns[{{ $campaign->id }}][redemption_enabled]" value="1" @checked(old("campaigns.{$campaign->id}.redemption_enabled", $campaign->redemption_enabled ?? false))><span class="switch-track"></span></span>
                                        </label>
                                        <label class="switch-row">
                                            <span>Habilitar premios mayores</span>
                                            <span class="switch"><input type="checkbox" name="campaigns[{{ $campaign->id }}][major_prizes_enabled]" value="1" @checked(old("campaigns.{$campaign->id}.major_prizes_enabled", $campaign->major_prizes_enabled ?? false))><span class="switch-track"></span></span>
                                        </label>
                                    </div>
                                </div>

                                <div class="mode-fields" data-mode-group="threshold_form">
                                    <div class="form-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                                        <div class="field">
                                            <label>Meta de facturación ($)</label>
                                            <input type="number" min="0" step="0.01" name="campaigns[{{ $campaign->id }}][entry_threshold_amount]" value="{{ old("campaigns.{$campaign->id}.entry_threshold_amount", $campaign->entry_threshold_amount) }}" placeholder="100">
                                            <span class="field-hint">Monto acumulado en facturas para calificar.</span>
                                        </div>
                                        <label class="switch-row" style="align-self:end;">
                                            <span>Requiere aprobación manual</span>
                                            <span class="switch"><input type="checkbox" name="campaigns[{{ $campaign->id }}][entry_requires_approval]" value="1" @checked(old("campaigns.{$campaign->id}.entry_requires_approval", $campaign->entry_requires_approval ?? false))><span class="switch-track"></span></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="field-group">
                                <p class="field-group-title">Reglas comunes de factura</p>
                                <div class="form-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                                    <div class="field">
                                        <label>Monto mínimo por factura</label>
                                        <input type="number" min="0" step="0.01" name="campaigns[{{ $campaign->id }}][invoice_min_amount_for_shot]" value="{{ old("campaigns.{$campaign->id}.invoice_min_amount_for_shot", $campaign->invoice_min_amount_for_shot ?? 25) }}">
                                    </div>
                                    <div class="field">
                                        <label>Máximo facturas por día</label>
                                        <input type="number" min="1" name="campaigns[{{ $campaign->id }}][daily_max_invoices]" value="{{ old("campaigns.{$campaign->id}.daily_max_invoices", $campaign->daily_max_invoices ?? 100) }}">
                                    </div>
                                </div>
                                <div class="toggle-grid" style="margin-top:.9rem;">
                                    <label class="switch-row">
                                        <span>Visible en el catálogo</span>
                                        <span class="switch"><input type="checkbox" name="campaigns[{{ $campaign->id }}][is_listed]" value="1" @checked(old("campaigns.{$campaign->id}.is_listed", $campaign->is_listed ?? true))><span class="switch-track"></span></span>
                                    </label>
                                    <label class="switch-row">
                                        <span>Habilitar registro de facturas</span>
                                        <span class="switch"><input type="checkbox" name="campaigns[{{ $campaign->id }}][invoice_scan_enabled]" value="1" @checked(old("campaigns.{$campaign->id}.invoice_scan_enabled", $campaign->invoice_scan_enabled ?? true))><span class="switch-track"></span></span>
                                    </label>
                                </div>
                            </div>

                            <div class="campaign-actions">
                                <button
                                    type="submit"
                                    class="btn {{ $campaign->status === 'active' ? 'btn-gray' : 'btn-green' }}"
                                    formaction="{{ route('admin.invoice-backoffice.campaigns.toggle-status', $campaign) }}"
                                    formmethod="POST"
                                    name="status"
                                    value="{{ $campaign->status === 'active' ? 'paused' : 'active' }}"
                                >
                                    {{ $campaign->status === 'active' ? 'Pausar promo' : 'Activar promo' }}
                                </button>
                                <button type="submit" class="btn btn-red">Guardar cambios</button>
                            </div>
                        </div>
                    </details>
                </form>
            @endforeach
        </div>
    </div>
@endsection
