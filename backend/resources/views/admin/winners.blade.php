@extends('admin.layout')

@section('title', 'Ganadores')
@section('subtitle', 'Gestión de ganadores')

@section('topbar-actions')
    <a class="topbar-action hide-mobile" href="{{ route('admin.invoice-backoffice') }}">Configuración</a>
    <a class="topbar-action hide-mobile" href="{{ route('admin.invoices') }}">Facturas</a>
    <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit">Cerrar sesión</button>
    </form>
@endsection

@section('sidebar-actions')
    <a href="{{ route('admin.invoices') }}">Buscar facturas <small>Filtrar por cliente o fecha</small></a>
    <a href="{{ route('admin.invoice-backoffice') }}">Ajustes de promo <small>Reglas y validación</small></a>
@endsection

@section('content')
    @if (session('status'))
        <div class="success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <div class="stack">
        <div class="page-card">
            <div class="page-section">
                <form method="GET" action="{{ route('admin.winners') }}" class="form-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
                    <div class="field">
                        <label for="campaign_id">Promoción</label>
                        <select id="campaign_id" name="campaign_id">
                            <option value="">Todas</option>
                            @foreach($campaigns as $campaign)
                                <option value="{{ $campaign->id }}" @selected((string) request('campaign_id') === (string) $campaign->id)>{{ $campaign->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="responsive-actions" style="align-self:end;">
                        <button class="btn btn-red" type="submit">Filtrar</button>
                        <a class="btn btn-gray" href="{{ route('admin.winners') }}">Limpiar</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="page-card">
            <div class="page-title">
                <div>
                    <h1>Ganadores seleccionados</h1>
                    <p>{{ count($winners) }} / 100</p>
                </div>
            </div>
            <div class="page-section">
                <div class="notice">Aquí el admin decide manualmente quién entra y quién sale. El servidor bloquea cualquier intento de pasar de 100 ganadores.</div>
                @if($winners->isEmpty())
                    <div class="empty">No hay ganadores seleccionados todavía.</div>
                @else
                    <div class="table-shell">
                        <table class="wide">
                            <thead>
                                <tr><th>#</th><th>Nombre</th><th>Cédula</th><th>Correo</th><th>N° Factura</th><th>Monto</th><th>Fecha selección</th><th>Acción</th></tr>
                            </thead>
                            <tbody>
                                @foreach($winners as $i => $winner)
                                    <tr>
                                        <td data-label="#">{{ $i + 1 }}</td>
                                        <td data-label="Nombre"><strong>{{ $winner->user?->full_name ?? '—' }}</strong></td>
                                        <td data-label="Cédula">{{ $winner->user?->cedula ?? '—' }}</td>
                                        <td data-label="Correo">{{ $winner->user?->email ?? '—' }}</td>
                                        <td data-label="N° Factura">{{ optional($winner->user?->invoices?->first())->invoice_number ?? '—' }}</td>
                                        <td data-label="Monto">${{ number_format((float) $winner->invoice_total_amount, 2) }}</td>
                                        <td data-label="Fecha selección">{{ $winner->selected_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                        <td data-label="Acción">
                                            <form method="POST" action="{{ route('admin.winners.remove', $winner) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-gray" type="submit">Quitar</button>
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

        <div class="page-card">
            <div class="page-title">
                <div>
                    <h1>Facturas elegibles para agregar</h1>
                    <p>{{ number_format($availableInvoices->total()) }} disponibles</p>
                </div>
            </div>
            <div class="page-section">
                @if($availableInvoices->isEmpty())
                    <div class="empty">No hay facturas elegibles para agregar como ganadores.</div>
                @else
                    <div class="table-shell">
                        <table class="wide">
                            <thead>
                                <tr><th>Nombre</th><th>Cédula</th><th>Promo</th><th>Factura</th><th>Monto</th><th>Fecha</th><th>Acción</th></tr>
                            </thead>
                            <tbody>
                                @foreach($availableInvoices as $inv)
                                    <tr>
                                        <td data-label="Nombre"><strong>{{ $inv->user?->full_name ?? '—' }}</strong></td>
                                        <td data-label="Cédula">{{ $inv->user?->cedula ?? '—' }}</td>
                                        <td data-label="Promo">{{ $inv->campaign?->name ?? '—' }}</td>
                                        <td data-label="Factura">{{ $inv->invoice_number ?? '—' }}</td>
                                        <td data-label="Monto">${{ number_format((float) $inv->purchase_amount, 2) }}</td>
                                        <td data-label="Fecha">{{ $inv->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                        <td data-label="Acción">
                                            <form method="POST" action="{{ route('admin.winners.select', $inv) }}">
                                                @csrf
                                                <button class="btn btn-green" type="submit">Marcar ganador</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div style="padding-top:1rem;">
                        {{ $availableInvoices->links('pagination::simple-bootstrap-4') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
