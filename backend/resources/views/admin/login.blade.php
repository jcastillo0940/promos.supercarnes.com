<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Super Carnes</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #f1f5f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 380px;
        }
        h1 { font-size: 1.25rem; font-weight: 700; color: #0f172a; margin-bottom: .25rem; }
        p.sub { font-size: .875rem; color: #64748b; margin-bottom: 1.75rem; }
        label { display: block; font-size: .8125rem; font-weight: 600; color: #374151; margin-bottom: .375rem; }
        input {
            width: 100%; padding: .625rem .875rem;
            border: 1px solid #d1d5db; border-radius: 8px;
            font-size: .9375rem; color: #111827;
            outline: none; transition: border-color .15s;
        }
        input:focus { border-color: #dc2626; box-shadow: 0 0 0 3px rgba(220,38,38,.12); }
        .field { margin-bottom: 1.125rem; }
        .error { font-size: .8125rem; color: #dc2626; margin-top: .375rem; }
        button {
            width: 100%; padding: .75rem;
            background: #dc2626; color: #fff;
            border: none; border-radius: 8px;
            font-size: 1rem; font-weight: 600;
            cursor: pointer; margin-top: .5rem;
            transition: background .15s;
        }
        button:hover { background: #b91c1c; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Super Carnes Admin</h1>
        <p class="sub">Acceso exclusivo para administradores</p>

        <form method="POST" action="{{ route('admin.login.post') }}">
            @csrf
            <div class="field">
                <label for="email">Correo electrónico</label>
                <input type="email" id="email" name="email"
                       value="{{ old('email') }}" autofocus autocomplete="email">
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
    </div>
</body>
</html>
