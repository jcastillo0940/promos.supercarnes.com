<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fonda Challenge 2026</title>
    <style>
        body { margin:0; font-family: Arial, sans-serif; background: linear-gradient(135deg, #1f1c2c, #928dab); color:#111; }
        .wrap { max-width: 1080px; margin: 0 auto; padding: 32px 20px 64px; }
        .hero { background: rgba(255,255,255,.96); border-radius: 28px; padding: 32px; box-shadow: 0 20px 60px rgba(0,0,0,.18); }
        .grid { display:grid; gap:24px; grid-template-columns: 1.1fr .9fr; margin-top:24px; }
        .card { background:#fff; border-radius:24px; padding:24px; box-shadow: 0 16px 40px rgba(0,0,0,.14); }
        label { display:block; font-weight:700; margin:14px 0 6px; }
        input, textarea { width:100%; box-sizing:border-box; padding:12px 14px; border:1px solid #d7d7de; border-radius:14px; font-size:16px; }
        textarea { min-height:120px; resize:vertical; }
        button { width:100%; margin-top:18px; padding:14px 16px; border:0; border-radius:14px; background:#1f7a5a; color:#fff; font-size:16px; font-weight:700; }
        .kicker { text-transform: uppercase; letter-spacing:.12em; color:#1f7a5a; font-weight:800; font-size:12px; }
        .status { margin-top:16px; padding:12px 14px; border-radius:12px; background:#ecfff6; color:#155c40; }
        .muted { color:#555; line-height:1.55; }
        @media (max-width: 900px) { .grid { grid-template-columns: 1fr; } .hero { padding: 22px; } }
    </style>
</head>
<body>
    <div class="wrap">
        <section class="hero">
            <div class="kicker">Super Carnes · Promoción modular</div>
            <h1>Fonda Challenge 2026</h1>
            <p class="muted">Un módulo independiente para registrar fondas, revisar inscripciones, operar el evento y cerrar resultados con trazabilidad.</p>
            @if ($campaign)
                <p class="muted">Campaña vinculada: <strong>{{ $campaign->name }}</strong></p>
            @endif
            @if (session('status'))
                <div class="status">{{ session('status') }}</div>
            @endif
        </section>

        <div class="grid">
            <section class="card">
                <h2>Registro de fonda</h2>
                <form method="POST" action="{{ route('fonda-challenge.store') }}">
                    @csrf
                    <label>Nombre completo</label>
                    <input name="full_name" value="{{ old('full_name') }}" required>

                    <label>Cédula</label>
                    <input name="cedula" value="{{ old('cedula') }}" required>

                    <label>Correo</label>
                    <input type="email" name="email" value="{{ old('email') }}" required>

                    <label>Teléfono</label>
                    <input name="phone" value="{{ old('phone') }}">

                    <label>Nombre de la fonda</label>
                    <input name="fonda_name" value="{{ old('fonda_name') }}" required>

                    <label>Ubicación</label>
                    <input name="fonda_location" value="{{ old('fonda_location') }}">

                    <label>Plato principal</label>
                    <input name="dish_name" value="{{ old('dish_name') }}" required>

                    <label>Descripción / historia</label>
                    <textarea name="description" required>{{ old('description') }}</textarea>

                    <label style="display:flex; gap:10px; align-items:flex-start; font-weight:600;">
                        <input type="checkbox" name="consent_terms" value="1" required style="width:auto; margin-top:4px;">
                        Acepto términos, uso de imagen y revisión administrativa.
                    </label>

                    @if ($errors->any())
                        <div class="status" style="background:#fff1f1; color:#8a1f1f;">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <button type="submit">Enviar inscripción</button>
                </form>
            </section>

            <aside class="card">
                <h2>Cómo funciona</h2>
                <p class="muted">1. Registras la fonda con su responsable.</p>
                <p class="muted">2. El equipo valida la información y aprueba o corrige.</p>
                <p class="muted">3. El día del evento se hace check-in, evaluación y cierre.</p>
                <p class="muted">4. Todo queda auditado para resultados y ganadores.</p>
            </aside>
        </div>
    </div>
</body>
</html>
