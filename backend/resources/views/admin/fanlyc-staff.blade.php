@extends('admin.layout')

@section('title', 'Staff Fanlyc')
@section('subtitle', 'Canje en zona')

@section('topbar-actions')
    <a class="topbar-action hide-mobile" href="{{ route('admin.fanlyc') }}">Fanlyc</a>
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
                <h1>Crear cuenta de staff</h1>
                <p>La cuenta inicia sesion en /admin/login y solo ve la pantalla de canje de cupones.</p>
            </div>
        </div>
        <div class="page-section" style="border-top:1px solid #e5e7eb;">
            <form method="POST" action="{{ route('admin.fanlyc-staff.store') }}" class="form-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
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
                    <button class="btn btn-red" type="submit">Crear cuenta</button>
                </div>
            </form>
        </div>
    </div>

    <div class="page-card" style="margin-top:1rem;">
        <div class="page-title">
            <div>
                <h1>Cuentas de staff</h1>
                <p>{{ $staff->count() }} cuenta(s)</p>
            </div>
        </div>
        <div class="page-section">
            @if($staff->isEmpty())
                <div class="empty">Todavía no hay cuentas de staff creadas.</div>
            @else
                <div class="table-shell">
                    <table>
                        <thead>
                            <tr><th>Nombre</th><th>Correo</th><th>Estado</th><th>Acciones</th></tr>
                        </thead>
                        <tbody>
                            @foreach($staff as $account)
                                <tr>
                                    <td data-label="Nombre">{{ $account->full_name ?? $account->name }}</td>
                                    <td data-label="Correo">{{ $account->email }}</td>
                                    <td data-label="Estado">
                                        @if($account->is_active)
                                            <span class="badge badge-green">Activo</span>
                                        @else
                                            <span class="badge badge-gray">Desactivado</span>
                                        @endif
                                    </td>
                                    <td data-label="Acciones">
                                        <form method="POST" action="{{ route('admin.fanlyc-staff.toggle-status', $account) }}">
                                            @csrf
                                            <button class="btn btn-gray" type="submit">
                                                {{ $account->is_active ? 'Desactivar' : 'Activar' }}
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
