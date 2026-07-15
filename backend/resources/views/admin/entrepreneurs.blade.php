@extends('admin.layout')

@section('title', 'Del sueño al puesto')
@section('subtitle', 'Emprendedores')

@section('topbar-actions')
    <a class="topbar-action hide-mobile" href="{{ route('admin.invoice-backoffice') }}">Configuración</a>
    <a class="topbar-action hide-mobile" href="{{ route('admin.invoices') }}">Facturas</a>
    <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit">Cerrar sesión</button>
    </form>
@endsection

@section('sidebar-actions')
    <a href="{{ route('admin.invoices') }}">Ver facturas <small>Registro general</small></a>
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
                <h1>Emprendedores — Del sueño al puesto</h1>
                <p>{{ number_format($entrepreneurs->total()) }} persona(s) inscrita(s) o calificada(s)@if($dreamCampaign) · Meta ${{ number_format((float) ($dreamCampaign->entry_threshold_amount ?: 300), 2) }}@endif</p>
            </div>
        </div>

        <div class="page-section" style="border-top:1px solid #e5e7eb;">
            <form method="GET" action="{{ route('admin.entrepreneurs') }}" class="form-grid" style="grid-template-columns: repeat(5, minmax(0, 1fr));">
                <div class="field">
                    <label for="name">Nombre</label>
                    <input id="name" name="name" type="text" value="{{ request('name') }}" placeholder="Nombre o emprendimiento">
                </div>
                <div class="field">
                    <label for="cedula">Cédula</label>
                    <input id="cedula" name="cedula" type="text" value="{{ request('cedula') }}" placeholder="Buscar por cédula">
                </div>
                <div class="field">
                    <label for="province">Provincia</label>
                    <input id="province" name="province" type="text" value="{{ request('province') }}" placeholder="Provincia">
                </div>
                <div class="field">
                    <label for="qualified">Estado</label>
                    <select id="qualified" name="qualified">
                        <option value="">Todos</option>
                        <option value="yes" @selected(request('qualified') === 'yes')>Calificados</option>
                        <option value="no" @selected(request('qualified') === 'no')>Pendientes</option>
                    </select>
                </div>
                <div class="responsive-actions" style="align-self:end;">
                    <button class="btn btn-red" type="submit">Filtrar</button>
                    <a class="btn btn-gray" href="{{ route('admin.entrepreneurs') }}">Limpiar</a>
                </div>
            </form>
        </div>

        <div class="page-section">
            @if($entrepreneurs->isEmpty())
                <div class="empty">No hay emprendedores registrados todavía.</div>
            @else
                <div class="table-shell">
                    <table class="wide">
                        <thead>
                            <tr>
                                <th>Persona</th><th>Emprendimiento</th><th>Provincia</th><th>Sucursal cercana</th><th>Tipo</th><th>Acumulado</th><th>Estado</th><th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($entrepreneurs as $person)
                                @php
                                    $total = (float) ($totalsByUser[$person->id] ?? 0);
                                @endphp
                                <tr>
                                    <td data-label="Persona"><strong>{{ $person->full_name ?? $person->name ?? '—' }}</strong><br><span style="color:#64748b">{{ $person->cedula ?? '—' }} · {{ $person->email ?? '—' }}</span></td>
                                    <td data-label="Emprendimiento">{{ $person->entrepreneur_name ?? '—' }}</td>
                                    <td data-label="Provincia">{{ $person->entrepreneur_province ?? '—' }}</td>
                                    <td data-label="Sucursal cercana">{{ $person->branch?->name ?? '—' }}</td>
                                    <td data-label="Tipo">{{ $person->entrepreneur_type ?? '—' }}</td>
                                    <td data-label="Acumulado">${{ number_format($total, 2) }}</td>
                                    <td data-label="Estado">
                                        @if($person->dream_promo_qualified_at)
                                            <span class="badge badge-green">Calificado</span>
                                        @else
                                            <span class="badge badge-yellow">Pendiente</span>
                                        @endif
                                    </td>
                                    <td data-label="Acciones">
                                        <a class="btn btn-gray" href="{{ route('admin.entrepreneurs.edit', $person) }}">Ver / Editar</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($entrepreneurs->hasPages())
                    <div style="padding-top:1rem;">
                        {{ $entrepreneurs->links('pagination::simple-bootstrap-4') }}
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection
