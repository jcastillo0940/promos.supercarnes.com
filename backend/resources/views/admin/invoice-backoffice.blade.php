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
                    <p class="small">Edita el slug para cada promoción. Ejemplo: <code>/dia-del-padre</code>.</p>

                    <form method="POST" action="{{ route('admin.invoice-backoffice.campaigns.update') }}">
                        @csrf
                        <div class="stack">
                            @foreach ($campaigns as $campaign)
                                <div class="page-card" style="box-shadow:none;border:1px solid #e5e7eb;">
                                    <input type="hidden" name="campaigns[{{ $loop->index }}][id]" value="{{ $campaign->id }}">
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
                                            <label>Imagen card</label>
                                            <input type="text" name="campaigns[{{ $loop->index }}][card_image_url]" value="{{ old("campaigns.$loop->index.card_image_url", $campaign->card_image_url) }}">
                                        </div>
                                        <div class="field">
                                            <label>Imagen hero</label>
                                            <input type="text" name="campaigns[{{ $loop->index }}][hero_image_url]" value="{{ old("campaigns.$loop->index.hero_image_url", $campaign->hero_image_url) }}">
                                        </div>
                                    </div>
                                    <div class="field">
                                        <label>Descripción</label>
                                        <input type="text" name="campaigns[{{ $loop->index }}][description]" value="{{ old("campaigns.$loop->index.description", $campaign->description) }}">
                                    </div>
                                    <label>
                                        <input type="checkbox" name="campaigns[{{ $loop->index }}][is_listed]" value="1" @checked(old("campaigns.$loop->index.is_listed", $campaign->is_listed ?? true))>
                                        Mostrar en el catálogo
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
