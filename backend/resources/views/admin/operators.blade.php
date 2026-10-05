@extends('admin.layout')

@section('title', 'Operadores')
@section('subtitle', 'Acceso por módulo')

@section('topbar-actions')
@endsection

@section('content')
    @if (session('status'))
        <div class="success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    {{-- Create operator --}}
    <div class="page-card">
        <div class="page-title">
            <div>
                <h1>Crear operador</h1>
                <p>Un operador solo ve los módulos que le asignes. Inicia sesión en /admin/login.</p>
            </div>
        </div>
        <div class="page-section" style="border-top:1px solid #e5e7eb;">
            <form method="POST" action="{{ route('admin.operators.store') }}" class="form-grid">
                @csrf
                <div class="form-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
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
                </div>
                <div class="field">
                    <label>Módulos</label>
                    <div style="display:flex;flex-wrap:wrap;gap:.65rem .5rem;margin-top:.25rem;">
                        @foreach($modules as $key => $label)
                            <label style="display:flex;align-items:center;gap:.4rem;font-size:.88rem;font-weight:500;cursor:pointer;">
                                <input type="checkbox" name="modules[]" value="{{ $key }}"
                                    {{ in_array($key, old('modules', [])) ? 'checked' : '' }}>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <button class="btn btn-red" type="submit">Crear operador</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Operators list --}}
    <div class="page-card" style="margin-top:1rem;">
        <div class="page-title">
            <div>
                <h1>Operadores</h1>
                <p>{{ $operators->count() }} operador(es)</p>
            </div>
        </div>

        @if($operators->isEmpty())
            <div class="page-section">
                <p style="color:#64748b;">No hay operadores creados todavía.</p>
            </div>
        @else
            @foreach($operators as $op)
                <div class="page-section" style="border-top:1px solid #e5e7eb;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:.75rem;margin-bottom:.75rem;">
                        <div>
                            <strong>{{ $op->full_name ?? $op->name }}</strong>
                            <span style="margin-left:.5rem;color:#64748b;font-size:.88rem;">{{ $op->email }}</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:.5rem;">
                            @if($op->is_active)
                                <span class="badge badge-green">Activo</span>
                            @else
                                <span class="badge badge-gray">Desactivado</span>
                            @endif
                            <form method="POST" action="{{ route('admin.operators.toggle-status', $op) }}"
                                data-confirm="{{ $op->is_active ? '¿Desactivar a '.$op->full_name.'? Ya no podrá iniciar sesión.' : '¿Activar a '.$op->full_name.'?' }}">
                                @csrf
                                <button class="btn btn-gray" type="submit" style="padding:.4rem .75rem;font-size:.8rem;">
                                    {{ $op->is_active ? 'Desactivar' : 'Activar' }}
                                </button>
                            </form>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.operators.modules', $op) }}" style="display:flex;flex-wrap:wrap;align-items:center;gap:.65rem .5rem;">
                        @csrf
                        @foreach($modules as $key => $label)
                            <label style="display:flex;align-items:center;gap:.4rem;font-size:.85rem;font-weight:500;cursor:pointer;">
                                <input type="checkbox" name="modules[]" value="{{ $key }}"
                                    {{ in_array($key, (array) $op->admin_modules) ? 'checked' : '' }}>
                                {{ $label }}
                            </label>
                        @endforeach
                        <button class="btn btn-gray" type="submit" style="padding:.4rem .75rem;font-size:.8rem;margin-left:.25rem;">
                            Guardar módulos
                        </button>
                    </form>
                </div>
            @endforeach
        @endif
    </div>
@endsection
