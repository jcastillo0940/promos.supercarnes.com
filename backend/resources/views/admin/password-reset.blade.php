@extends('admin.auth-layout')

@section('title', 'Crear contraseña')

@section('content')
    <h2>Crea tu contraseña</h2>
    <p class="sub">Elige una contraseña segura para tu cuenta de operador</p>

    @if(session('status'))
        <p style="color:#16a34a; font-size:.88rem; margin-bottom:1rem;">{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ route('admin.password.reset.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">

        <div class="field">
            <label for="password">Nueva contraseña</label>
            <input type="password" id="password" name="password" minlength="8" autofocus autocomplete="new-password" required>
            @error('password')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>
        <div class="field">
            <label for="password_confirmation">Confirmar contraseña</label>
            <input type="password" id="password_confirmation" name="password_confirmation" minlength="8" autocomplete="new-password" required>
        </div>

        @error('email')
            <p class="error" style="margin-bottom:.75rem;">{{ $message }}</p>
        @enderror

        <button type="submit">Guardar contraseña</button>
    </form>
@endsection
