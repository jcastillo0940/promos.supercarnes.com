@extends('admin.auth-layout')

@section('title', 'Admin')

@section('content')
    <h2>Ingresar</h2>
    <p class="sub">Acceso exclusivo para administradores</p>

    @if(session('status'))
        <p style="color:#16a34a; font-size:.88rem; margin-bottom:1rem;">{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ route('admin.login.post') }}">
        @csrf
        <div class="field">
            <label for="email">Correo electrónico</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" autofocus autocomplete="email">
            @error('email')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>
        <div class="field">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" autocomplete="current-password">
            @error('password')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>
        <button type="submit">Entrar</button>
    </form>
@endsection
