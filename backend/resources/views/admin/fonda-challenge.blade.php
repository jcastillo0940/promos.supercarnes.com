@extends('admin.layout')

@section('title', 'Fonda Challenge')
@section('subtitle', 'Inscripciones')

@php
    $statusMeta = [
        'pending_review' => ['yellow', 'En revisión'],
        'needs_correction' => ['yellow', 'Requiere corrección'],
        'approved' => ['green', 'Aprobada'],
        'rejected' => ['red', 'Rechazada'],
        'checked_in' => ['gray', 'Check-in hecho'],
        'ready_for_judging' => ['green', 'Lista para jurado'],
        'disqualified' => ['red', 'Descalificada'],
    ];
@endphp

@section('topbar-actions')
    <a class="topbar-action hide-mobile" href="{{ route('admin.fonda-jury') }}">Jurado</a>
    <a class="topbar-action hide-mobile" href="{{ route('admin.jurors') }}">Jurados</a>
    <a class="topbar-action hide-mobile" href="{{ route('admin.fonda-challenge.ranking') }}">Ranking</a>
    <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit">Cerrar sesión</button>
    </form>
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
                <h1>Fonda Challenge — Inscripciones</h1>
                <p>{{ number_format($registrations->total()) }} inscripción(es) · {{ $campaign->name }}</p>
            </div>
        </div>

        <div class="page-section" style="border-top:1px solid #e5e7eb; display:flex; gap:.5rem; flex-wrap:wrap;">
            @foreach($statusMeta as $key => [$color, $label])
                <span class="badge badge-{{ $color }}">{{ $label }}: {{ $counts[$key] ?? 0 }}</span>
            @endforeach
        </div>

        <div class="page-section" style="border-top:1px solid #e5e7eb;">
            <form method="GET" action="{{ route('admin.fonda-challenge') }}" class="form-grid" style="grid-template-columns: repeat(4, minmax(0, 1fr));">
                <div class="field" style="grid-column: span 2;">
                    <label for="search">Buscar</label>
                    <input id="search" name="search" type="text" value="{{ request('search') }}" placeholder="Código, cédula, nombre, fonda o correo">
                </div>
                <div class="field">
                    <label for="status">Estado</label>
                    <select id="status" name="status">
                        <option value="">Todos</option>
                        @foreach($statuses as $statusOption)
                            <option value="{{ $statusOption }}" @selected(request('status') === $statusOption)>{{ $statusMeta[$statusOption][1] ?? $statusOption }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="responsive-actions" style="align-self:end;">
                    <button class="btn btn-red" type="submit">Filtrar</button>
                    <a class="btn btn-gray" href="{{ route('admin.fonda-challenge') }}">Limpiar</a>
                </div>
            </form>
        </div>

        <div class="page-section">
            @if($registrations->isEmpty())
                <div class="empty">No hay inscripciones que coincidan con el filtro.</div>
            @else
                <div class="table-shell">
                    <table class="wide">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Fonda</th>
                                <th>Responsable</th>
                                <th>Contacto</th>
                                <th>Ubicación</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($registrations as $registration)
                                @php [$color, $label] = $statusMeta[$registration->status] ?? ['gray', $registration->status]; @endphp
                                <tr>
                                    <td data-label="Código"><strong>{{ $registration->code }}</strong></td>
                                    <td data-label="Fonda">{{ $registration->fonda_name }}<br><span style="color:#64748b">{{ $registration->dish_name }}</span></td>
                                    <td data-label="Responsable">{{ $registration->full_name }}<br><span style="color:#64748b">{{ $registration->cedula }}</span></td>
                                    <td data-label="Contacto">{{ $registration->email }}<br><span style="color:#64748b">{{ $registration->phone }}</span></td>
                                    <td data-label="Ubicación">{{ $registration->fonda_location ?? '—' }}</td>
                                    <td data-label="Estado"><span class="badge badge-{{ $color }}">{{ $label }}</span></td>
                                    <td data-label="Acciones">
                                        <div class="responsive-actions">
                                            <a class="btn btn-gray" href="{{ route('admin.fonda-challenge.edit', $registration) }}">Ver / Editar</a>
                                            @if($registration->status !== 'approved')
                                                <form method="POST" action="{{ route('admin.fonda-challenge.approve', $registration) }}">
                                                    @csrf
                                                    <button class="btn btn-green" type="submit">Aprobar</button>
                                                </form>
                                            @endif
                                            @if($registration->status !== 'rejected')
                                                <form method="POST" action="{{ route('admin.fonda-challenge.reject', $registration) }}" onsubmit="return confirm('¿Rechazar esta inscripción?');">
                                                    @csrf
                                                    <button class="btn btn-red" type="submit">Rechazar</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($registrations->hasPages())
                    <div style="padding-top:1rem;">
                        {{ $registrations->links('pagination::simple-bootstrap-4') }}
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection
