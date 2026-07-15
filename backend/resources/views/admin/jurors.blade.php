@extends('admin.layout')

@section('title', 'Jurados')
@section('subtitle', 'Fonda Challenge')

@section('topbar-actions')
    <a class="topbar-action hide-mobile" href="{{ route('admin.fonda-challenge') }}">Fonda Challenge</a>
    <a class="topbar-action hide-mobile" href="{{ route('admin.fonda-jury') }}">Asignación de jurados</a>
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
                <h1>Crear jurado</h1>
                <p>El jurado inicia sesión en /admin/login con estas credenciales y solo ve la sección de evaluación.</p>
            </div>
        </div>
        <div class="page-section" style="border-top:1px solid #e5e7eb;">
            <form method="POST" action="{{ route('admin.jurors.store') }}" class="form-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
                @csrf
                <div class="field">
                    <label for="full_name">Nombre completo</label>
                    <input id="full_name" name="full_name" type="text" value="{{ old('full_name') }}" required>
                </div>
                <div class="field">
                    <label for="email">Correo</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required>
                </div>
                <div class="field">
                    <label for="password">Contraseña</label>
                    <input id="password" name="password" type="text" minlength="8" required>
                </div>
                <div class="responsive-actions" style="align-self:end;">
                    <button class="btn btn-red" type="submit">Crear jurado</button>
                </div>
            </form>
        </div>
    </div>

    <div class="page-card" style="margin-top:1rem;">
        <div class="page-title">
            <div>
                <h1>Jurados registrados</h1>
                <p>{{ $jurors->count() }} cuenta(s) de jurado</p>
            </div>
        </div>
        <div class="page-section">
            @if($jurors->isEmpty())
                <div class="empty">Todavía no hay jurados creados.</div>
            @else
                <div class="table-shell">
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre</th><th>Correo</th><th>Estado</th><th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($jurors as $juror)
                                <tr>
                                    <td data-label="Nombre">{{ $juror->full_name ?? $juror->name }}</td>
                                    <td data-label="Correo">{{ $juror->email }}</td>
                                    <td data-label="Estado">
                                        @if($juror->is_active)
                                            <span class="badge badge-green">Activo</span>
                                        @else
                                            <span class="badge badge-gray">Desactivado</span>
                                        @endif
                                    </td>
                                    <td data-label="Acciones">
                                        <form method="POST" action="{{ route('admin.jurors.toggle-status', $juror) }}">
                                            @csrf
                                            <button class="btn btn-gray" type="submit">
                                                {{ $juror->is_active ? 'Desactivar' : 'Activar' }}
                                            </button>
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
@endsection
