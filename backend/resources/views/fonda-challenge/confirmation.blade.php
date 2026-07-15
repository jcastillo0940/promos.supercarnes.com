<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inscripción recibida</title>
    <style>
        body { margin:0; font-family: Arial, sans-serif; background:#f4f6f8; color:#111; }
        .wrap { max-width: 820px; margin: 0 auto; padding: 48px 20px; }
        .card { background:#fff; border-radius:24px; padding:32px; box-shadow: 0 12px 40px rgba(0,0,0,.08); }
        .code { font-size: 32px; font-weight: 800; letter-spacing:.08em; color:#1f7a5a; }
        .muted { color:#555; line-height:1.6; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <h1>Tu inscripción quedó recibida</h1>
            <p class="muted">La fonda quedó en estado de revisión. Guarda este código para seguimiento y comunicación con el equipo.</p>
            <div class="code">{{ $registration->code }}</div>
            <p><strong>Fonda:</strong> {{ $registration->fonda_name }}</p>
            <p><strong>Plato:</strong> {{ $registration->dish_name }}</p>
            <p><strong>Estado:</strong> {{ $registration->status }}</p>
        </div>
    </div>
</body>
</html>
