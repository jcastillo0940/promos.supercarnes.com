@extends('admin.layout')

@section('title', 'Fanlyc')
@section('subtitle', 'Revision de facturas')

@section('topbar-actions')
    <a class="topbar-action hide-mobile" href="{{ route('admin.fanlyc.zones') }}">Zonas</a>
    <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit">Cerrar sesión</button>
    </form>
@endsection

@section('sidebar-actions')
    <a href="{{ route('admin.fanlyc.zones') }}">Zonas <small>Asignar sucursales a zonas</small></a>
    <a href="{{ route('admin.fanlyc-staff') }}">Staff de canje <small>Cuentas del rol staff_fanlyc</small></a>
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
                <h1>Registrar factura por WhatsApp</h1>
                <p>Usa esto cuando un cliente te escribio por WhatsApp en vez de registrar su factura en la pagina.</p>
            </div>
        </div>
        <div class="page-section" style="border-top:1px solid #e5e7eb;">
            <form method="POST" action="{{ route('admin.fanlyc.manual-register') }}" class="form-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                @csrf
                <div class="field">
                    <label for="manual_full_name">Nombre completo</label>
                    <input id="manual_full_name" name="full_name" type="text" value="{{ old('full_name') }}" required>
                </div>
                <div class="field">
                    <label for="manual_cedula">Cedula</label>
                    <input id="manual_cedula" name="cedula" type="text" value="{{ old('cedula') }}" required>
                </div>
                <div class="field">
                    <label for="manual_email">Correo (opcional)</label>
                    <input id="manual_email" name="email" type="email" value="{{ old('email') }}">
                </div>
                <div class="field">
                    <label for="manual_phone">Telefono</label>
                    <input id="manual_phone" name="phone" type="text" value="{{ old('phone') }}" required>
                </div>
                <div class="field" style="grid-column: 1 / -1;">
                    <label for="manual_qr_raw_text">CUFE o contenido del QR de la factura</label>
                    <input id="manual_qr_raw_text" name="qr_raw_text" type="text" value="{{ old('qr_raw_text') }}" placeholder="Pide al cliente que te envie el CUFE o una foto del QR de la factura" required>
                </div>
                <div style="grid-column: 1 / -1;">
                    <button class="btn btn-red" type="submit">Registrar factura</button>
                </div>
            </form>
        </div>
    </div>

    <div class="page-card" style="margin-top:1rem;">
        <div class="page-title">
            <div>
                <h1>Fanlyc — {{ $campaign->name }}</h1>
                <p>Facturas registradas: {{ number_format($counts->sum()) }} · Pendientes de revision: {{ number_format($counts['pending_review'] ?? 0) }}</p>
            </div>
        </div>

        <div class="page-section">
            <form method="GET" action="{{ route('admin.fanlyc') }}" class="filter-toolbar" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end;">
                <div class="field" style="min-width:220px;">
                    <label>Buscar</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cedula, nombre, correo o CUFE">
                </div>
                <div class="field" style="min-width:200px;">
                    <label>Estado</label>
                    <select name="status">
                        <option value="">Todos</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }} ({{ $counts[$status] ?? 0 }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="field" style="min-width:180px;">
                    <label>Zona</label>
                    <select name="fanlyc_zone_id">
                        <option value="">Todas</option>
                        @foreach($zones as $zone)
                            <option value="{{ $zone->id }}" @selected((string) request('fanlyc_zone_id') === (string) $zone->id)>{{ $zone->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button class="btn btn-gray" type="submit">Filtrar</button>
                </div>
            </form>
        </div>

        <div class="page-section">
            @if($invoices->isEmpty())
                <div class="empty">No hay facturas registradas todavia.</div>
            @else
                <div class="table-shell">
                    <table class="wide">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>CUFE / Factura</th>
                                <th>Sucursal</th>
                                <th>Zona</th>
                                <th>SKU</th>
                                <th>Estado</th>
                                <th>Cupon</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoices as $invoice)
                                <tr>
                                    <td data-label="Cliente">
                                        {{ $invoice->user?->full_name ?? $invoice->user?->name ?? '—' }}<br>
                                        <small style="color:#94a3b8;">{{ $invoice->user?->cedula }}</small>
                                    </td>
                                    <td data-label="CUFE" style="word-break:break-all;max-width:220px;">
                                        <a href="{{ route('admin.fanlyc.show', $invoice) }}">{{ $invoice->invoice_number ?? $invoice->cufe }}</a>
                                    </td>
                                    <td data-label="Sucursal">{{ $invoice->branch?->name ?? '—' }}</td>
                                    <td data-label="Zona">{{ $invoice->fanlycZone?->name ?? '—' }}</td>
                                    <td data-label="SKU">
                                        <span class="badge badge-{{ match($invoice->sku_check_status) { 'matched' => 'green', 'not_matched' => 'red', default => 'yellow' } }}">
                                            {{ $invoice->sku_check_status }}
                                        </span>
                                    </td>
                                    <td data-label="Estado">
                                        <span class="badge badge-{{ $invoice->status === 'approved' ? 'green' : ($invoice->status === 'pending_review' ? 'yellow' : 'red') }}">
                                            {{ $invoice->status }}
                                        </span>
                                    </td>
                                    <td data-label="Cupon">{{ $invoice->coupon?->code ?? '—' }}</td>
                                    <td data-label="Fecha">{{ $invoice->created_at?->format('d/m/Y H:i') }}</td>
                                    <td data-label="Acciones">
                                        <a class="btn btn-gray" href="{{ route('admin.fanlyc.show', $invoice) }}">Ver</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="margin-top:1rem;">{{ $invoices->links() }}</div>
            @endif
        </div>
    </div>
@endsection
