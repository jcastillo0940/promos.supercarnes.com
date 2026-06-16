<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') — Super Carnes</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            font-family: system-ui, -apple-system, sans-serif;
            background:
                radial-gradient(circle at top, rgba(220,38,38,.18), transparent 40%),
                linear-gradient(180deg, #f8fafc, #eef2ff);
            color: #0f172a;
            display: grid;
            place-items: center;
            padding: 1rem;
        }
        .auth-shell {
            width: 100%;
            max-width: 980px;
            display: grid;
            grid-template-columns: 1.1fr .9fr;
            overflow: hidden;
            border-radius: 24px;
            box-shadow: 0 24px 70px rgba(15, 23, 42, .16);
            background: #fff;
        }
        .auth-hero {
            padding: 2.5rem;
            background:
                linear-gradient(135deg, rgba(185,28,28,.94), rgba(239,68,68,.88)),
                url('/gaby-torres-celebration.webp') center/cover no-repeat;
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 560px;
        }
        .auth-badge {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            width: fit-content;
            padding: .45rem .8rem;
            border-radius: 999px;
            background: rgba(255,255,255,.14);
            border: 1px solid rgba(255,255,255,.16);
            font-size: .82rem;
            font-weight: 700;
        }
        .auth-hero h1 { font-size: 2.4rem; line-height: 1.02; margin-top: 1rem; max-width: 10ch; }
        .auth-hero p { margin-top: .85rem; max-width: 34ch; color: rgba(255,255,255,.88); font-size: 1rem; line-height: 1.55; }
        .auth-card {
            padding: 2.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
        }
        .card {
            width: 100%;
            max-width: 380px;
        }
        .card h2 { font-size: 1.45rem; margin-bottom: .35rem; }
        .card .sub { color: #64748b; margin-bottom: 1.5rem; }
        label { display: block; font-size: .82rem; font-weight: 700; color: #334155; margin-bottom: .35rem; }
        input {
            width: 100%;
            min-height: 46px;
            padding: .65rem .85rem;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            font-size: .95rem;
            outline: none;
        }
        input:focus { border-color: #dc2626; box-shadow: 0 0 0 4px rgba(220,38,38,.12); }
        .field { margin-bottom: 1rem; }
        .error { font-size: .82rem; color: #dc2626; margin-top: .35rem; }
        button {
            width: 100%;
            min-height: 48px;
            padding: .75rem 1rem;
            border: none;
            border-radius: 12px;
            background: linear-gradient(90deg, #dc2626, #ef4444);
            color: #fff;
            font-size: 1rem;
            font-weight: 800;
            cursor: pointer;
        }
        button:hover { filter: brightness(.98); }
        @media (max-width: 900px) {
            .auth-shell { grid-template-columns: 1fr; max-width: 520px; }
            .auth-hero { min-height: 240px; padding: 1.5rem; }
            .auth-hero h1 { font-size: 1.8rem; max-width: none; }
            .auth-card { padding: 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="auth-shell">
        <section class="auth-hero">
            <div>
                <div class="auth-badge">Super Carnes Admin</div>
                <h1>Acceso seguro al backoffice</h1>
                <p>Gestiona facturas, ganadores y la entrega de premios desde un panel pensado para escritorio y móvil.</p>
            </div>
        </section>
        <section class="auth-card">
            <div class="card">
                @yield('content')
            </div>
        </section>
    </div>
</body>
</html>
