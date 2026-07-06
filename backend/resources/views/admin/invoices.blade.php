@extends('admin.layout')

@section('title', 'Facturas')
@section('subtitle', 'Gestión de facturas')

@section('topbar-actions')
    <a class="topbar-action hide-mobile" href="{{ route('admin.invoice-backoffice') }}">Configuración</a>
    <a class="topbar-action hide-mobile" href="{{ route('admin.winners') }}">Ganadores</a>
    <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit">Cerrar sesión</button>
    </form>
@endsection

@section('sidebar-actions')
    <a href="{{ route('admin.invoice-backoffice') }}">Volver a configuración <small>Ajustar reglas de validación</small></a>
    <a href="{{ route('admin.winners') }}">Ir a ganadores <small>Ver selección actual</small></a>
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
                <h1>Facturas registradas</h1>
                <p>{{ number_format($invoices->total()) }} facturas en total</p>
            </div>
        </div>

        <div class="page-section" style="border-top:1px solid #e5e7eb;">
            <form method="GET" action="{{ route('admin.invoices') }}" class="form-grid" style="grid-template-columns: repeat(6, minmax(0, 1fr));">
                <div class="field">
                    <label for="campaign_id">Promoción</label>
                    <select id="campaign_id" name="campaign_id">
                        <option value="">Todas</option>
                        @foreach($campaigns as $campaign)
                            <option value="{{ $campaign->id }}" @selected((string) request('campaign_id') === (string) $campaign->id)>{{ $campaign->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="name">Nombre</label>
                    <input id="name" name="name" type="text" value="{{ request('name') }}" placeholder="Buscar por nombre">
                </div>
                <div class="field">
                    <label for="cedula">Cédula</label>
                    <input id="cedula" name="cedula" type="text" value="{{ request('cedula') }}" placeholder="Buscar por cédula">
                </div>
                <div class="field">
                    <label for="date_from">Fecha desde</label>
                    <input id="date_from" name="date_from" type="date" value="{{ request('date_from') }}">
                </div>
                <div class="field">
                    <label for="date_to">Fecha hasta</label>
                    <input id="date_to" name="date_to" type="date" value="{{ request('date_to') }}">
                </div>
                <div class="responsive-actions" style="align-self:end;">
                    <button class="btn btn-red" type="submit">Filtrar</button>
                    <a class="btn btn-gray" href="{{ route('admin.invoices') }}">Limpiar</a>
                </div>
            </form>
        </div>

        <div class="page-section">
            @if($invoices->isEmpty())
                <div class="empty">No hay facturas registradas aún.</div>
            @else
                <div class="table-shell">
                    <table class="wide">
                        <thead>
                            <tr>
                                <th>#</th><th>Usuario</th><th>N° Factura</th><th>Emisor</th><th>Sucursal</th><th>Monto</th><th>Puntos</th><th>Estado</th><th>Validación DGI</th><th>Fecha</th><th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoices as $inv)
                                <tr>
                                    <td data-label="#">{{ $inv->id }}</td>
                                    <td data-label="Usuario"><strong>{{ $inv->user?->full_name ?? '—' }}</strong><br><span style="color:#64748b">{{ $inv->user?->email ?? '—' }}</span></td>
                                    <td data-label="N° Factura">{{ $inv->invoice_number ?? '—' }}</td>
                                    <td data-label="Emisor">{{ $inv->issuer_name ?? '—' }}<br><span style="color:#64748b">{{ $inv->issuer_ruc ?? '' }}</span></td>
                                    <td data-label="Sucursal">{{ $inv->branch?->name ?? '—' }}</td>
                                    <td data-label="Monto">${{ number_format((float)$inv->purchase_amount, 2) }}</td>
                                    <td data-label="Puntos">{{ $inv->points_awarded ?? 0 }}</td>
                                    <td data-label="Estado">
                                        @php
                                            $statusMap = ['approved' => ['green', 'Aprobada'], 'pending' => ['yellow', 'Pendiente'], 'rejected' => ['red', 'Rechazada']];
                                            [$color, $label] = $statusMap[$inv->status] ?? ['gray', $inv->status ?? '—'];
                                        @endphp
                                        <span class="badge badge-{{ $color }}">{{ $label }}</span>
                                    </td>
                                    <td data-label="Validación DGI">
                                        @php
                                            $valMap = ['approved' => ['green', 'Válida DGI'], 'pending' => ['yellow', 'Pendiente'], 'failed' => ['red', 'Falló'], 'skipped' => ['gray', 'Omitida']];
                                            [$vc, $vl] = $valMap[$inv->validation_status] ?? ['gray', $inv->validation_status ?? '—'];
                                        @endphp
                                        <span class="badge badge-{{ $vc }}">{{ $vl }}</span>
                                    </td>
                                    <td data-label="Fecha">{{ $inv->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td data-label="Acciones">
                                        <a class="btn btn-gray" href="{{ route('admin.customers.history', $inv->user) }}">Ver cliente</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($invoices->hasPages())
                    <div style="padding-top:1rem;">
                        {{ $invoices->links('pagination::simple-bootstrap-4') }}
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection
