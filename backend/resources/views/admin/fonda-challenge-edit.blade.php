@extends('admin.layout')

@section('title', 'Participante Fonda Challenge')
@section('subtitle', $registration->code)

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
    [$color, $label] = $statusMeta[$registration->status] ?? ['gray', $registration->status];
@endphp

@section('topbar-actions')
    <a class="topbar-action hide-mobile" href="{{ route('admin.fonda-challenge') }}">Volver al listado</a>
    <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit">Cerrar sesión</button>
    </form>
@endsection

@section('sidebar-actions')
    <a href="{{ route('admin.fonda-challenge') }}">Volver al listado <small>Fonda Challenge</small></a>
    <a href="{{ route('fonda-challenge.show', $registration->code) }}" target="_blank" rel="noopener">Ver confirmación pública <small>Nueva pestaña</small></a>
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
                <h1>{{ $registration->fonda_name }}</h1>
                <p>Código <strong>{{ $registration->code }}</strong> · <span class="badge badge-{{ $color }}">{{ $label }}</span></p>
            </div>
            <div class="responsive-actions">
                <a class="btn btn-gray" href="{{ route('admin.fonda-challenge') }}">Volver al listado</a>
            </div>
        </div>

        <div class="page-section" style="border-top:1px solid #e5e7eb;">
            <h2 style="margin:0 0 .5rem;font-size:1rem;">Cambiar estado</h2>
            <form method="POST" action="{{ route('admin.fonda-challenge.status', $registration) }}" class="form-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
                @csrf
                <div class="field">
                    <label for="status">Estado</label>
                    <select id="status" name="status" required>
                        @foreach($statuses as $statusOption)
                            <option value="{{ $statusOption }}" @selected($registration->status === $statusOption)>{{ $statusMeta[$statusOption][1] ?? $statusOption }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field" style="grid-column: span 2;">
                    <label for="reason">Motivo (opcional, queda en auditoría)</label>
                    <input id="reason" name="reason" type="text" maxlength="2000" placeholder="Ej: falta foto del local">
                </div>
                <div class="responsive-actions" style="align-self:end;">
                    <button class="btn btn-red" type="submit">Guardar estado</button>
                </div>
            </form>
        </div>

        <div class="page-section" style="border-top:1px solid #e5e7eb;">
            <h2 style="margin:0 0 .5rem;font-size:1rem;">Datos del participante</h2>
            <form method="POST" action="{{ route('admin.fonda-challenge.update', $registration) }}" class="stack">
                @csrf
                <div class="form-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                    <div class="field">
                        <label for="full_name">Nombre completo</label>
                        <input id="full_name" name="full_name" type="text" value="{{ old('full_name', $registration->full_name) }}" required>
                    </div>
                    <div class="field">
                        <label for="cedula">Cédula</label>
                        <input id="cedula" name="cedula" type="text" value="{{ old('cedula', $registration->cedula) }}" required>
                    </div>
                    <div class="field">
                        <label for="email">Correo</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $registration->email) }}" required>
                    </div>
                    <div class="field">
                        <label for="phone">Teléfono</label>
                        <input id="phone" name="phone" type="text" value="{{ old('phone', $registration->phone) }}" required>
                    </div>
                    <div class="field">
                        <label for="fonda_name">Nombre de la fonda</label>
                        <input id="fonda_name" name="fonda_name" type="text" value="{{ old('fonda_name', $registration->fonda_name) }}" required>
                    </div>
                    <div class="field">
                        <label for="fonda_location">Ubicación de la fonda</label>
                        <input id="fonda_location" name="fonda_location" type="text" value="{{ old('fonda_location', $registration->fonda_location) }}" required>
                    </div>
                    <div class="field">
                        <label for="dish_name">Plato a presentar</label>
                        <input id="dish_name" name="dish_name" type="text" value="{{ old('dish_name', $registration->dish_name) }}" required>
                    </div>
                </div>
                <div class="responsive-actions">
                    <button class="btn btn-red" type="submit">Guardar cambios</button>
                </div>
            </form>
        </div>

        <div class="page-section" style="border-top:1px solid #e5e7eb;">
            <h2 style="margin:0 0 .5rem;font-size:1rem;">Código QR</h2>
            <img src="{{ route('fonda-challenge.qr', $registration->code) }}" alt="QR {{ $registration->code }}" style="width:180px;height:180px;border:1px solid #e5e7eb;border-radius:12px;">
            <p style="margin:.5rem 0 0;color:#64748b;font-size:.85rem;">Se envió por correo a {{ $registration->email }} al momento de la inscripción.</p>
        </div>
    </div>
@endsection
