@extends('admin.layout')

@section('title', 'Historial del cliente')
@section('subtitle', 'Detalle de cliente')

@section('topbar-actions')
    <a class="topbar-action hide-mobile" href="{{ route('admin.invoices') }}">Facturas</a>
    <a class="topbar-action hide-mobile" href="{{ route('admin.winners') }}">Ganadores</a>
    <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit">Cerrar sesión</button>
    </form>
@endsection

@section('sidebar-actions')
    <a href="{{ route('admin.invoices') }}">Volver a facturas <small>Regresar al listado</small></a>
    <a href="{{ route('admin.winners') }}">Ver ganadores <small>Administrar selección</small></a>
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
                <h1>{{ $user->full_name ?? $user->name ?? 'Cliente' }}</h1>
                <p>Cédula: {{ $user->cedula ?? '—' }} · Correo: {{ $user->email ?? '—' }}</p>
            </div>
            <div class="responsive-actions">
                <a class="btn btn-gray" href="{{ route('admin.invoices') }}">Volver a facturas</a>
                @if($winner)
                    <form method="POST" action="{{ route('admin.customers.unmark-winner', $user) }}">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-red" type="submit">Marcar como no ganador</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.customers.mark-winner', $user) }}">
                        @csrf
                        <button class="btn btn-green" type="submit">Marcar como ganador</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="page-card">
        <div class="page-title">
            <div>
                <h1>Perfil de emprendimiento</h1>
                <p>Datos cargados por el participante para la promo Del sueño al puesto.</p>
            </div>
        </div>
        <div class="page-section">
            <div class="form-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                <div class="field">
                    <label>Nombre del emprendimiento</label>
                    <div>{{ $user->entrepreneur_name ?? '—' }}</div>
                </div>
                <div class="field">
                    <label>Provincia</label>
                    <div>{{ $user->entrepreneur_province ?? '—' }}</div>
                </div>
                <div class="field">
                    <label>Sucursal cercana</label>
                    <div>{{ optional($user->branch)->name ?? '—' }}</div>
                </div>
                <div class="field">
                    <label>Tipo de emprendimiento</label>
                    <div>{{ $user->entrepreneur_type ?? '—' }}</div>
                </div>
            </div>
            <div class="field" style="margin-top:1rem;">
                <label>Historia del emprendimiento</label>
                <div style="white-space:pre-wrap;">{{ $user->entrepreneur_story ?? '—' }}</div>
            </div>
            <div class="field" style="margin-top:1rem;">
                <label>Por qué deben ganar la tolda</label>
                <div style="white-space:pre-wrap;">{{ $user->entrepreneur_reason ?? '—' }}</div>
            </div>
        </div>
    </div>

    <div class="page-card">
        <div class="page-title">
            <div>
                <h1>Historia de facturas</h1>
                <p>{{ count($invoices) }} registro(s)</p>
            </div>
        </div>
        <div class="page-section">
            @if($invoices->isEmpty())
                <div class="empty">Este cliente no tiene facturas registradas.</div>
            @else
                <div class="table-shell">
                    <table class="wide">
                        <thead>
                            <tr><th>Promo</th><th>Factura</th><th>Monto</th><th>Puntos</th><th>Estado</th><th>Fecha</th><th>Notas</th></tr>
                        </thead>
                        <tbody>
                            @foreach($invoices as $invoice)
                                <tr>
                                    <td data-label="Promo">{{ $invoice->campaign?->name ?? '—' }}</td>
                                    <td data-label="Factura">{{ $invoice->invoice_number ?? '—' }}</td>
                                    <td data-label="Monto">${{ number_format((float) $invoice->purchase_amount, 2) }}</td>
                                    <td data-label="Puntos">{{ $invoice->points_awarded ?? 0 }}</td>
                                    <td data-label="Estado">
                                        @php
                                            $statusMap = ['approved' => ['green', 'Aprobada'], 'pending' => ['gray', 'Pendiente'], 'rejected' => ['red', 'Rechazada']];
                                            [$color, $label] = $statusMap[$invoice->status] ?? ['gray', $invoice->status ?? '—'];
                                        @endphp
                                        <span class="badge badge-{{ $color }}">{{ $label }}</span>
                                    </td>
                                    <td data-label="Fecha">{{ $invoice->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td data-label="Notas">{{ $invoice->dad_reason ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="page-card">
        <div class="page-title">
            <div>
                <h1>Acumulado por promo</h1>
                <p>Resumen rápido para ver en cuál promoción participa o ya fue elegido.</p>
            </div>
        </div>
        <div class="page-section">
            <div class="table-shell">
                <table class="wide">
                    <thead>
                        <tr><th>Promo</th><th>Total acumulado</th></tr>
                    </thead>
                    <tbody>
                        @foreach($campaigns as $campaign)
                            <tr>
                                <td>{{ $campaign->name }}</td>
                                <td>${{ number_format((float) ($campaignTotals[$campaign->id] ?? 0), 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
