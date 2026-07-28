@extends('admin.layout')

@section('title', 'Blacklist')
@section('subtitle', 'Personas bloqueadas')

@section('topbar-actions')
    <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit">Cerrar sesión</button>
    </form>
@endsection

@section('sidebar-actions')
    <a href="{{ route('admin.invoices') }}">Facturas <small>Volver al listado</small></a>
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
                <h1>Blacklist</h1>
                <p>{{ number_format($activeCount) }} persona(s) bloqueada(s) actualmente de todas las promociones (registro de facturas, Fonda Challenge y cupones/juegos).</p>
            </div>
        </div>
        <div class="page-section">
            <form method="POST" action="{{ route('admin.blacklist.store') }}" class="form-grid" style="grid-template-columns: repeat(4, minmax(0, 1fr));">
                @csrf
                <div class="field">
                    <label>Cédula</label>
                    <input type="text" name="cedula" value="{{ old('cedula') }}" placeholder="8-123-4567">
                </div>
                <div class="field">
                    <label>Teléfono</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" placeholder="6000-0000">
                </div>
                <div class="field">
                    <label>Nombre (referencia)</label>
                    <input type="text" name="full_name" value="{{ old('full_name') }}" placeholder="Opcional">
                </div>
                <div class="field">
                    <label>ID de cuenta (opcional)</label>
                    <input type="number" name="user_id" value="{{ old('user_id') }}" placeholder="Si ya tiene cuenta">
                </div>
                <div class="field" style="grid-column: 1 / -1;">
                    <label>Motivo del bloqueo</label>
                    <input type="text" name="reason" value="{{ old('reason') }}" placeholder="Ej: Facturas duplicadas confirmadas, apoyo de cajero, etc." required>
                </div>
                <div style="grid-column: 1 / -1;">
                    <button class="btn btn-red" type="submit">Agregar a la blacklist</button>
                </div>
            </form>
        </div>
    </div>

    <div class="page-card">
        <div class="page-section">
            <form method="GET" action="{{ route('admin.blacklist') }}" class="filter-toolbar" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end;">
                <div class="field" style="min-width:220px;">
                    <label>Buscar</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Nombre, cédula, teléfono o motivo">
                </div>
                <div class="field" style="min-width:180px;">
                    <label>Estado</label>
                    <select name="status">
                        <option value="">Todos</option>
                        <option value="active" @selected(request('status') === 'active')>Activos</option>
                        <option value="removed" @selected(request('status') === 'removed')>Removidos</option>
                    </select>
                </div>
                <div>
                    <button class="btn btn-gray" type="submit">Filtrar</button>
                </div>
            </form>
        </div>

        <div class="page-section">
            @if($entries->isEmpty())
                <div class="empty">No hay registros en la blacklist.</div>
            @else
                <div class="table-shell">
                    <table class="wide">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Cédula</th>
                                <th>Teléfono</th>
                                <th>Cuenta</th>
                                <th>Motivo</th>
                                <th>Estado</th>
                                <th>Agregado por</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($entries as $entry)
                                <tr>
                                    <td data-label="Nombre">{{ $entry->full_name ?? $entry->user?->full_name ?? $entry->user?->name ?? '—' }}</td>
                                    <td data-label="Cédula">{{ $entry->cedula ?? '—' }}</td>
                                    <td data-label="Teléfono">{{ $entry->phone ?? '—' }}</td>
                                    <td data-label="Cuenta">
                                        @if($entry->user)
                                            <a href="{{ route('admin.customers.history', $entry->user) }}">Ver cliente</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td data-label="Motivo">{{ $entry->reason }}</td>
                                    <td data-label="Estado">
                                        <span class="badge badge-{{ $entry->status === 'active' ? 'red' : 'gray' }}">{{ $entry->status === 'active' ? 'Bloqueado' : 'Removido' }}</span>
                                    </td>
                                    <td data-label="Agregado por">{{ $entry->createdBy?->full_name ?? $entry->createdBy?->name ?? '—' }}</td>
                                    <td data-label="Fecha">{{ $entry->created_at?->format('d/m/Y H:i') }}</td>
                                    <td data-label="Acciones">
                                        @if($entry->status === 'active')
                                            <form method="POST" action="{{ route('admin.blacklist.remove', $entry) }}" onsubmit="return confirm('¿Quitar a esta persona de la blacklist?');">
                                                @csrf
                                                <button class="btn btn-gray" type="submit">Quitar</button>
                                            </form>
                                        @else
                                            <small style="color:#94a3b8;">Removido {{ $entry->removed_at?->format('d/m/Y') }}</small>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="margin-top:1rem;">{{ $entries->links() }}</div>
            @endif
        </div>
    </div>
@endsection
