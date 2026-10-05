@extends('admin.layout')

@section('title', 'Auditoría')
@section('subtitle', 'Entregas, rechazos y reaperturas')

@push('styles')
    <style>
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
            align-items: end;
        }
        .table-wrap {
            overflow-x: auto;
            border: 1px solid var(--bo-border);
            border-radius: 12px;
            background: var(--bo-surface);
        }
        .field textarea {
            width: 100%;
            min-height: 110px;
            padding: .65rem .8rem;
            border: 1px solid var(--bo-border-dark);
            border-radius: 10px;
            font-size: .95rem;
            background: var(--bo-surface);
            color: var(--bo-fg);
            resize: vertical;
        }
        .field span {
            display: block;
            font-size: .82rem;
            font-weight: 700;
            color: var(--bo-fg2);
            margin-bottom: .35rem;
        }
        @media (max-width: 900px) {
            .stat-grid, .filters-grid { grid-template-columns: 1fr; }
        }
    </style>
@endpush

@section('content')
    <div class="page-card">
        <div class="page-title">
            <div>
                <h1>Auditoría de premios</h1>
                <p>Seguimiento de entregas, rechazos y correcciones con filtros de control.</p>
            </div>
        </div>

        <div class="stat-grid">
            <div class="stat-card">
                <small>Entregas</small>
                <h2>{{ number_format($summary['delivered']) }}</h2>
            </div>
            <div class="stat-card">
                <small>Rechazos</small>
                <h2>{{ number_format($summary['rejected']) }}</h2>
            </div>
            <div class="stat-card">
                <small>Reaperturas</small>
                <h2>{{ number_format($summary['overrides']) }}</h2>
            </div>
        </div>

        <div class="page-section stack">
            <form method="GET" class="filters-grid">
                <label class="field">
                    <span>Desde</span>
                    <input type="date" name="from" value="{{ request('from') }}">
                </label>
                <label class="field">
                    <span>Hasta</span>
                    <input type="date" name="to" value="{{ request('to') }}">
                </label>
                <label class="field">
                    <span>Usuario</span>
                    <input type="text" name="user" value="{{ request('user') }}" placeholder="Nombre o correo">
                </label>
                <label class="field">
                    <span>Cédula</span>
                    <input type="text" name="cedula" value="{{ request('cedula') }}" placeholder="Buscar cédula">
                </label>
                <label class="field">
                    <span>Sucursal</span>
                    <input type="text" name="branch" value="{{ request('branch') }}" placeholder="Nombre o código">
                </label>
                <div class="responsive-actions" style="align-items:flex-end;">
                    <button class="btn btn-red" type="submit">Filtrar</button>
                    <a class="btn btn-gray" href="{{ route('admin.audit') }}">Limpiar</a>
                </div>
            </form>

            <div class="table-wrap table-shell">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Evento</th>
                            <th>Usuario</th>
                            <th>Cédula</th>
                            <th>Sucursal</th>
                            <th>Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entries as $entry)
                            <tr>
                                <td>
                                    {{ optional($entry->created_at)->format('d/m/Y H:i') }}
                                </td>
                                <td>
                                    <span class="badge {{ $entry->event_type === 'prize_delivered' ? 'badge-green' : ($entry->event_type === 'prize_delivery_override' ? 'badge-yellow' : 'badge-red') }}">
                                        {{ match($entry->event_type) {
                                            'prize_delivered' => 'Entregado',
                                            'prize_delivery_override' => 'Reapertura',
                                            default => 'Rechazo',
                                        } }}
                                    </span>
                                </td>
                                <td>
                                    <strong>{{ $entry->user?->name ?? '—' }}</strong><br>
                                    <small>{{ $entry->user?->email ?? '—' }}</small>
                                </td>
                                <td>{{ $entry->user?->cedula ?? data_get($entry->payload, 'winner_cedula', '—') }}</td>
                                <td>{{ $entry->user?->branch?->name ?? '—' }}</td>
                                <td style="max-width: 420px;">
                                    @if($entry->event_type === 'prize_delivered')
                                        Entrega confirmada por {{ data_get($entry->payload, 'delivered_by_role', 'rol no definido') }}.
                                    @elseif($entry->event_type === 'prize_delivery_override')
                                        {{ data_get($entry->payload, 'override_reason', 'Sin justificación') }}
                                    @else
                                        {{ data_get($entry->payload, 'reason', 'Sin motivo') }}
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align:center;padding:1.5rem;">No hay registros con esos filtros.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>
                {{ $entries->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
