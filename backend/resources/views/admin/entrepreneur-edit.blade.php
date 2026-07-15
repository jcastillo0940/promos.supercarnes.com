@extends('admin.layout')

@section('title', 'Emprendedor')
@section('subtitle', 'Del sueño al puesto')

@section('topbar-actions')
    <a class="topbar-action hide-mobile" href="{{ route('admin.entrepreneurs') }}">Emprendedores</a>
    <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit">Cerrar sesión</button>
    </form>
@endsection

@section('sidebar-actions')
    <a href="{{ route('admin.entrepreneurs') }}">Volver al listado <small>Del sueño al puesto</small></a>
    <a href="{{ route('admin.customers.history', $entrepreneur) }}">Historial de facturas <small>Ver todas sus promos</small></a>
@endsection

@section('content')
    @if (session('status'))
        <div class="success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <div class="page-card">
        <div class="page-title">
            <div>
                <h1>{{ $entrepreneur->entrepreneur_name ?? $entrepreneur->full_name ?? 'Emprendedor' }}</h1>
                <p>
                    @if($entrepreneur->dream_promo_qualified_at)
                        <span class="badge badge-green">Calificado el {{ $entrepreneur->dream_promo_qualified_at->format('d/m/Y H:i') }}</span>
                    @else
                        <span class="badge badge-yellow">Pendiente de calificar</span>
                    @endif
                </p>
            </div>
            <div class="responsive-actions">
                <a class="btn btn-gray" href="{{ route('admin.entrepreneurs') }}">Volver al listado</a>
            </div>
        </div>

        <div class="page-section" style="border-top:1px solid #e5e7eb;">
            <form method="POST" action="{{ route('admin.entrepreneurs.update', $entrepreneur) }}" class="stack">
                @csrf

                <div>
                    <h2 style="margin:0 0 .5rem;font-size:1rem;">Datos de contacto</h2>
                    <div class="form-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                        <div class="field">
                            <label for="full_name">Nombre completo</label>
                            <input id="full_name" name="full_name" type="text" value="{{ old('full_name', $entrepreneur->full_name ?? $entrepreneur->name) }}" required>
                        </div>
                        <div class="field">
                            <label for="cedula">Cédula</label>
                            <input id="cedula" name="cedula" type="text" value="{{ old('cedula', $entrepreneur->cedula) }}">
                        </div>
                        <div class="field">
                            <label for="email">Correo</label>
                            <input id="email" name="email" type="email" value="{{ old('email', $entrepreneur->email) }}">
                        </div>
                        <div class="field">
                            <label for="phone">Teléfono</label>
                            <input id="phone" name="phone" type="text" value="{{ old('phone', $entrepreneur->phone) }}">
                        </div>
                    </div>
                </div>

                <div>
                    <h2 style="margin:0 0 .5rem;font-size:1rem;">Perfil de emprendimiento</h2>
                    <div class="form-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                        <div class="field">
                            <label for="entrepreneur_name">Nombre del emprendimiento</label>
                            <input id="entrepreneur_name" name="entrepreneur_name" type="text" value="{{ old('entrepreneur_name', $entrepreneur->entrepreneur_name) }}" required>
                        </div>
                        <div class="field">
                            <label for="entrepreneur_province">Provincia</label>
                            <input id="entrepreneur_province" name="entrepreneur_province" type="text" value="{{ old('entrepreneur_province', $entrepreneur->entrepreneur_province) }}" required>
                        </div>
                        <div class="field">
                            <label for="nearest_branch_id">Sucursal cercana</label>
                            <select id="nearest_branch_id" name="nearest_branch_id">
                                <option value="">Sin especificar</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected((string) old('nearest_branch_id', $entrepreneur->nearest_branch_id) === (string) $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="entrepreneur_type">Tipo de emprendimiento</label>
                            <input id="entrepreneur_type" name="entrepreneur_type" type="text" value="{{ old('entrepreneur_type', $entrepreneur->entrepreneur_type) }}" placeholder="Comida, artesanías, belleza...">
                        </div>
                    </div>
                    <div class="field" style="margin-top:1rem;">
                        <label for="entrepreneur_story">Historia del emprendimiento</label>
                        <textarea id="entrepreneur_story" name="entrepreneur_story" rows="4">{{ old('entrepreneur_story', $entrepreneur->entrepreneur_story) }}</textarea>
                    </div>
                    <div class="field" style="margin-top:1rem;">
                        <label for="entrepreneur_reason">Por qué debe ganar la tolda</label>
                        <textarea id="entrepreneur_reason" name="entrepreneur_reason" rows="4" required>{{ old('entrepreneur_reason', $entrepreneur->entrepreneur_reason) }}</textarea>
                    </div>
                </div>

                <div>
                    <label style="display:flex;align-items:center;gap:.5rem;font-weight:700;font-size:.9rem;color:#334155;">
                        <input type="checkbox" name="dream_promo_qualified" value="1" style="width:auto;min-height:auto;" @checked(old('dream_promo_qualified', $entrepreneur->dream_promo_qualified_at !== null))>
                        Calificado para el premio (35 toldas)
                    </label>
                </div>

                <div class="responsive-actions">
                    <button class="btn btn-red" type="submit">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>

    <div class="page-card">
        <div class="page-title">
            <div>
                <h1>Facturas de la promo</h1>
                <p>{{ count($invoices) }} factura(s) · Acumulado ${{ number_format($total, 2) }}@if($dreamCampaign) de ${{ number_format((float) ($dreamCampaign->entry_threshold_amount ?: 300), 2) }} @endif</p>
            </div>
        </div>

        <div class="page-section" style="border-top:1px solid #e5e7eb;">
            <h2 style="margin:0 0 .5rem;font-size:1rem;">Ingresar factura manualmente</h2>
            <p style="margin:0 0 .75rem;color:#64748b;font-size:.85rem;">
                Usa esto cuando el cliente no pueda registrar su factura desde la app (por ejemplo, si DGI no responde). Pega el código QR completo o los últimos 60 números del CUFE.
            </p>
            <form method="POST" action="{{ route('admin.entrepreneurs.invoices.store', $entrepreneur) }}" class="stack">
                @csrf
                <div class="field">
                    <label for="qr_raw_text">Código QR o CUFE</label>
                    <textarea id="qr_raw_text" name="qr_raw_text" rows="2" required>{{ old('qr_raw_text') }}</textarea>
                </div>
                <div class="responsive-actions">
                    <button class="btn btn-red" type="submit">Registrar factura</button>
                </div>
            </form>
        </div>

        <div class="page-section" style="border-top:1px solid #e5e7eb;">
            @if($invoices->isEmpty())
                <div class="empty">Esta persona no tiene facturas registradas en esta promo.</div>
            @else
                <div class="table-shell">
                    <table class="wide">
                        <thead>
                            <tr><th>Factura</th><th>Monto</th><th>Estado</th><th>Fecha</th></tr>
                        </thead>
                        <tbody>
                            @foreach($invoices as $invoice)
                                <tr>
                                    <td data-label="Factura">{{ $invoice->invoice_number ?? '—' }}</td>
                                    <td data-label="Monto">${{ number_format((float) $invoice->purchase_amount, 2) }}</td>
                                    <td data-label="Estado">
                                        @php
                                            $statusMap = ['approved' => ['green', 'Aprobada'], 'pending' => ['gray', 'Pendiente'], 'pending_threshold' => ['yellow', 'Pendiente de umbral'], 'rejected' => ['red', 'Rechazada']];
                                            [$color, $label] = $statusMap[$invoice->status] ?? ['gray', $invoice->status ?? '—'];
                                        @endphp
                                        <span class="badge badge-{{ $color }}">{{ $label }}</span>
                                    </td>
                                    <td data-label="Fecha">{{ $invoice->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
