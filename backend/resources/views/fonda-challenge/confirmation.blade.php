<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fonda Challenge · Confirmación</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --paper:#f5efe5; --brown:#7a4411; --brown-deep:#5d310c; --yellow:#ffd31a; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Inter', Arial, sans-serif; background: linear-gradient(180deg, #fbf6ec, #efe4d2); color: var(--brown-deep); }
        .wrap { width: min(980px, calc(100vw - 24px)); margin: 0 auto; padding: 18px 0 28px; }
        .card { border-radius: 34px; overflow: hidden; box-shadow: 0 18px 44px rgba(0,0,0,.12); background: linear-gradient(180deg, rgba(255,255,255,.88), rgba(246,238,227,.96)); }
        .hero {
            display: grid;
            grid-template-columns: .95fr 1.05fr;
            gap: 0;
            min-height: 520px;
            background:
                radial-gradient(circle at top left, rgba(255,255,255,.75), transparent 24%),
                url('/fonda-assets/hero-cover.jpeg') center/cover no-repeat;
        }
        .left { padding: 28px; display: flex; flex-direction: column; justify-content: center; background: linear-gradient(90deg, rgba(245,239,229,.82), rgba(245,239,229,.28)); }
        .kicker { font-size: 12px; text-transform: uppercase; letter-spacing: .18em; color: var(--brown); font-weight: 800; }
        h1 { margin: 10px 0 0; font-family: 'Fredoka', sans-serif; font-size: clamp(42px, 6vw, 72px); line-height: .92; color: var(--brown); }
        .status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: fit-content;
            margin-top: 14px;
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(255, 211, 26, .18);
            color: var(--brown);
            font-weight: 800;
        }
        .code {
            margin-top: 18px;
            font-family: 'Fredoka', sans-serif;
            font-size: clamp(34px, 5vw, 58px);
            line-height: .9;
            color: var(--yellow);
            text-shadow: -2px -2px 0 var(--brown-deep), 2px -2px 0 var(--brown-deep), -2px 2px 0 var(--brown-deep), 2px 2px 0 var(--brown-deep);
        }
        .meta {
            margin-top: 18px;
            display: grid;
            gap: 10px;
        }
        .meta div {
            border-radius: 20px;
            padding: 12px 14px;
            background: rgba(255,255,255,.78);
            border: 1px solid rgba(122,68,17,.10);
        }
        .right {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 28px;
        }
        .qr-card {
            width: min(360px, 100%);
            text-align: center;
            border-radius: 28px;
            padding: 22px;
            background: rgba(255,255,255,.88);
            box-shadow: 0 18px 36px rgba(0,0,0,.10);
        }
        .qr-card img { width: 100%; max-width: 300px; display: block; margin: 0 auto; }
        .qr-card h2 { margin: 0 0 10px; font-family: 'Fredoka', sans-serif; font-size: 30px; color: var(--brown); }
        .qr-card p { margin: 8px 0 0; line-height: 1.55; }
        .note {
            margin-top: 16px;
            padding: 14px 16px;
            border-radius: 20px;
            background: rgba(255, 211, 26, .16);
            color: var(--brown-deep);
            font-weight: 700;
            line-height: 1.5;
        }
        .body {
            padding: 24px 28px 30px;
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(3, 1fr);
        }
        .mini {
            border-radius: 24px;
            padding: 18px;
            background: rgba(255,255,255,.72);
            border: 1px solid rgba(122,68,17,.08);
        }
        .mini h3 { margin: 0; font-family: 'Fredoka', sans-serif; font-size: 26px; color: var(--brown); }
        .mini p { margin: 8px 0 0; line-height: 1.55; }
        @media (max-width: 900px) {
            .hero, .body { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <main class="wrap">
        <section class="card">
            <div class="hero">
                <div class="left">
                    <div class="kicker">Fonda Challenge · Registro recibido</div>
                    <h1>Tu fonda quedó registrada</h1>
                    <div class="status">Estado: {{ $registration->status }}</div>
                    <div class="code">{{ $registration->code }}</div>
                    <div class="meta">
                        <div><strong>Fonda:</strong> {{ $registration->fonda_name }}</div>
                        <div><strong>Plato:</strong> {{ $registration->dish_name }}</div>
                        <div><strong>Responsable:</strong> {{ $registration->full_name }}</div>
                    </div>
                </div>
                <div class="right">
                    <div class="qr-card">
                        <h2>Tu código QR</h2>
                        <img src="{{ route('fonda-challenge.qr', $registration->code) }}" alt="QR Fonda Challenge">
                        <p>Guárdalo o revisa tu correo: lo enviamos a {{ $registration->email }}. Este QR sirve para check-in y control operativo el día del evento, aunque tu registro esté en revisión.</p>
                        @if (session('status'))
                            <div class="note">{{ session('status') }}</div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="body">
                <div class="mini">
                    <h3>1. Revisión</h3>
                    <p>El equipo valida que la información esté completa y decide si aprueba, corrige o rechaza.</p>
                </div>
                <div class="mini">
                    <h3>2. QR</h3>
                    <p>Una vez aprobada, la fonda obtiene su código operativo para check-in y ruta de evaluación.</p>
                </div>
                <div class="mini">
                    <h3>3. Evento</h3>
                    <p>Después del check-in, la fonda entra a jurados, fotografía, ranking y resultados finales.</p>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
