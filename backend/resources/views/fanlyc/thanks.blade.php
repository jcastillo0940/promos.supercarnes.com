<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>¡Gracias por registrarte! - Fanlyc</title>
    <style>
        :root{
            --sky:#45b9e6;
            --sky-deep:#2297cb;
            --green:#30b232;
            --orange:#ff7a2f;
            --yellow:#ffca16;
            --ink:#16324f;
            --brown:#7a4411;
            --white:rgba(255,255,255,.94);
            --shadow:0 18px 42px rgba(18,58,86,.16);
        }
        *{box-sizing:border-box}
        body{
            margin:0;
            font-family:Inter, Arial, sans-serif;
            color:var(--ink);
            background:
                radial-gradient(circle at 10% 12%, rgba(255,255,255,.28), transparent 18%),
                radial-gradient(circle at 86% 8%, rgba(255,202,22,.18), transparent 15%),
                linear-gradient(180deg, #5ac3eb 0%, var(--sky) 100%);
            min-height:100vh;
        }
        .wrap{
            width:min(1120px, calc(100vw - 20px));
            margin:0 auto;
            padding:14px 0 28px;
        }
        .hero{
            position:relative;
            overflow:hidden;
            border-radius:34px;
            padding:24px;
            background:
                linear-gradient(135deg, rgba(255,255,255,.22), rgba(255,255,255,.06)),
                linear-gradient(180deg, rgba(255,255,255,.14), rgba(255,255,255,.05));
            box-shadow:var(--shadow);
        }
        .brand{
            display:flex;
            justify-content:center;
            margin-bottom:12px;
        }
        .brand img{
            width:min(180px, 54vw);
            display:block;
            filter:drop-shadow(0 10px 16px rgba(0,0,0,.10));
        }
        .hero-grid{
            display:grid;
            grid-template-columns:1.12fr .88fr;
            gap:16px;
            align-items:stretch;
        }
        .hero-copy{
            color:#fff;
            padding:10px 6px 0;
        }
        .eyebrow{
            margin:0 0 8px;
            font-size:13px;
            font-weight:900;
            text-transform:uppercase;
            letter-spacing:.18em;
        }
        h1{
            margin:0;
            font-size:clamp(40px, 6vw, 74px);
            line-height:.92;
            letter-spacing:-.05em;
            font-weight:900;
        }
        h1 span{
            display:block;
            color:var(--yellow);
            text-shadow:0 8px 20px rgba(0,0,0,.12);
        }
        .lead{
            margin:12px 0 0;
            max-width:44rem;
            font-size:17px;
            line-height:1.6;
            text-shadow:0 4px 16px rgba(0,0,0,.10);
        }
        .pills{
            margin-top:18px;
            display:flex;
            flex-wrap:wrap;
            gap:10px;
        }
        .pill{
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:10px 14px;
            border-radius:999px;
            font-weight:900;
            box-shadow:0 10px 18px rgba(0,0,0,.10);
        }
        .pill.yellow{background:var(--yellow); color:#543800}
        .pill.white{background:rgba(255,255,255,.92); color:#1d4c6c}
        .pill.green{background:var(--green); color:#fff}
        .summary{
            display:grid;
            gap:12px;
            align-content:start;
        }
        .panel{
            background:var(--white);
            border-radius:30px;
            padding:20px;
            box-shadow:var(--shadow);
        }
        .panel h2{
            margin:0 0 8px;
            color:var(--brown);
            font-size:24px;
            line-height:1;
        }
        .panel p{
            margin:0;
            line-height:1.6;
            color:#35516e;
        }
        .status{
            margin-top:12px;
            padding:12px 14px;
            border-radius:16px;
            background:#e7fff3;
            color:#0f6b47;
            border:1px solid #b9f2d2;
            font-size:14px;
            line-height:1.5;
        }
        .metrics{
            display:grid;
            grid-template-columns:repeat(2,1fr);
            gap:12px;
            margin-top:12px;
        }
        .metric{
            border-radius:22px;
            padding:16px;
            background:linear-gradient(180deg, rgba(69,185,230,.14), rgba(255,255,255,.88));
            border:1px solid rgba(22,50,79,.08);
        }
        .metric small{
            display:block;
            color:#5d7590;
            font-weight:800;
            margin-bottom:4px;
        }
        .metric strong{
            font-size:18px;
            line-height:1.2;
            color:#15324f;
        }
        .actions{
            display:flex;
            flex-wrap:wrap;
            gap:10px;
            margin-top:14px;
        }
        .btn{
            display:inline-flex;
            justify-content:center;
            align-items:center;
            text-decoration:none;
            border:0;
            border-radius:16px;
            padding:13px 16px;
            font:inherit;
            font-weight:900;
            cursor:pointer;
        }
        .btn-primary{
            background:linear-gradient(180deg, #ffd31a, #f3b500);
            color:#543800;
            box-shadow:0 14px 22px rgba(243,181,0,.22);
        }
        .btn-secondary{
            background:#fff;
            color:#255b86;
            box-shadow:inset 0 0 0 2px rgba(22,50,79,.08);
        }
        .layout{
            margin-top:14px;
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:14px;
        }
        .big-card{
            position:relative;
            overflow:hidden;
            min-height:280px;
            border-radius:32px;
            background:rgba(255,255,255,.92);
            box-shadow:var(--shadow);
            padding:22px;
        }
        .big-card::before{
            content:'';
            position:absolute;
            inset:auto -60px -90px auto;
            width:220px;
            height:220px;
            border-radius:50%;
            border:18px solid rgba(255,202,22,.55);
            pointer-events:none;
        }
        .big-card h3{
            margin:0 0 8px;
            color:var(--brown);
            font-size:28px;
            line-height:1;
        }
        .big-card p{
            margin:0;
            color:#35516e;
            line-height:1.6;
        }
        .steps{
            margin-top:14px;
            display:grid;
            gap:10px;
        }
        .step{
            display:grid;
            grid-template-columns:42px 1fr;
            gap:10px;
            align-items:start;
            padding:12px 14px;
            border-radius:22px;
            background:linear-gradient(90deg, rgba(255,122,47,.12), rgba(255,255,255,.72));
            border:1px solid rgba(22,50,79,.08);
        }
        .step-num{
            width:42px;
            height:42px;
            border-radius:50%;
            background:var(--yellow);
            color:#543800;
            display:flex;
            align-items:center;
            justify-content:center;
            font-weight:900;
            font-size:19px;
        }
        .step strong{
            display:block;
            margin-bottom:3px;
        }
        .step span{
            color:#57718c;
            font-size:14px;
            line-height:1.45;
        }
        .coupon-card{
            border-radius:32px;
            padding:22px;
            background:
                radial-gradient(circle at 90% 18%, rgba(255,255,255,.34), transparent 14%),
                linear-gradient(180deg, #1ba7df, var(--sky-deep));
            color:#fff;
            box-shadow:var(--shadow);
        }
        .coupon-card h3{
            margin:0 0 6px;
            font-size:26px;
            line-height:1;
        }
        .coupon-card p{
            margin:0;
            color:rgba(255,255,255,.92);
            line-height:1.55;
        }
        .coupon{
            margin-top:14px;
            background:rgba(255,255,255,.94);
            color:var(--ink);
            border-radius:26px;
            padding:18px;
        }
        .coupon .label{
            display:inline-flex;
            margin-bottom:10px;
            padding:6px 10px;
            border-radius:999px;
            font-size:12px;
            font-weight:900;
            background:rgba(255,202,22,.22);
            color:#7a4c00;
        }
        .coupon-code{
            font-size:28px;
            font-weight:900;
            letter-spacing:.04em;
            margin:0 0 8px;
        }
        .coupon-meta{
            color:#5d7590;
            line-height:1.5;
            font-size:14px;
        }
        .qr-shell{
            margin-top:14px;
            border-radius:22px;
            padding:14px;
            background:#fff;
            box-shadow:inset 0 0 0 2px rgba(22,50,79,.08);
            display:grid;
            place-items:center;
        }
        .qr-shell img{
            width:min(240px, 100%);
            height:auto;
            display:block;
        }
        .footer{
            margin-top:14px;
            text-align:center;
            color:#fff;
            font-weight:900;
            letter-spacing:.14em;
            text-transform:uppercase;
            opacity:.94;
        }
        @media (max-width: 980px){
            .hero-grid,.layout,.metrics{grid-template-columns:1fr}
        }
        @media (max-width: 640px){
            .wrap{width:min(100vw - 14px, 1120px)}
            .hero,.panel,.big-card,.coupon-card{padding:18px}
            h1{font-size:36px}
            .lead{font-size:15px}
            .metrics{grid-template-columns:1fr}
            .step{grid-template-columns:36px 1fr}
            .step-num{width:36px;height:36px;font-size:16px}
        }
    </style>
</head>
<body>
<main class="wrap">
    <section class="hero">
        <div class="brand">
            <img src="/logo_web.jpg" alt="Super Carnes">
        </div>
        <div class="hero-grid">
            <div class="hero-copy">
                <p class="eyebrow">Fanlyc ★ Relevo por la vida</p>
                <h1>¡Gracias <span>por registrarte!</span></h1>
                <p class="lead">
                    Recibimos tu factura y tus datos con éxito. Ya quedaste dentro de Fanlyc, y en esta misma pantalla te dejamos lo que sigue, de forma simple y clara.
                </p>
                <div class="pills">
                    <span class="pill yellow">Registro recibido</span>
                    <span class="pill white">Seguimiento por cédula</span>
                    <span class="pill green">Todo en orden</span>
                </div>
            </div>
            <div class="summary">
                <div class="panel">
                    <h2>Tu estado</h2>
                    <p>
                        @if($invoice)
                            Tu factura quedó {{ $invoice->status === 'approved' ? 'aprobada' : 'registrada' }} correctamente.
                        @else
                            Ya dejamos tu registro listo para revisión.
                        @endif
                    </p>
                    @if (session('status'))
                        <div class="status">{{ session('status') }}</div>
                    @endif
                    <div class="metrics">
                        <div class="metric">
                            <small>Cédula</small>
                            <strong>{{ $cedula !== '' ? $cedula : 'No disponible' }}</strong>
                        </div>
                        <div class="metric">
                            <small>Teléfono</small>
                            <strong>{{ $phone !== '' ? $phone : 'No disponible' }}</strong>
                        </div>
                    </div>
                    <div class="actions">
                        <a class="btn btn-primary" href="{{ route('fanlyc.status') }}">Ver mi estado</a>
                        <a class="btn btn-secondary" href="{{ route('fanlyc.landing') }}">Registrar otra factura</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="layout">
        <article class="big-card">
            <h3>¿Qué sigue ahora?</h3>
            <p>
                Vamos paso a paso contigo: primero registras, luego confirmas y después revisas tu avance. Si tu factura fue aprobada, tu cupón queda listo para canje; si quedó en revisión, te avisamos por correo en cuanto se resuelva.
            </p>
            <div class="steps">
                <div class="step">
                    <div class="step-num">1</div>
                    <div>
                        <strong>Guarda tu cédula y teléfono</strong>
                        <span>Con esos datos podrás revisar tu registro cuando quieras.</span>
                    </div>
                </div>
                <div class="step">
                    <div class="step-num">2</div>
                    <div>
                        <strong>Revisa tu estado</strong>
                        <span>Ahí verás si tu cupón ya está disponible o si sigue en proceso.</span>
                    </div>
                </div>
                <div class="step">
                    <div class="step-num">3</div>
                    <div>
                        <strong>Canjea cuando esté listo</strong>
                        <span>Cuando aparezca tu cupón, ya podrás usarlo en la zona asignada.</span>
                    </div>
                </div>
            </div>
        </article>

        <article class="coupon-card">
            <h3>Tu cupón Fanlyc</h3>
            <p>
                @if($coupon)
                    Este es tu cupón más reciente, listo para mostrar cuando lo necesites.
                @else
                    Si tu factura fue aprobada, aquí aparecerá tu cupón QR.
                @endif
            </p>

            @if($coupon && $coupon->status === 'issued')
                <div class="coupon">
                    <span class="label">Disponible para canjear</span>
                    <div class="coupon-code">{{ $coupon->code }}</div>
                    <div class="coupon-meta">
                        Zona: {{ $coupon->fanlycZone?->name ?? 'Por asignar' }}<br>
                        Factura: {{ $invoice?->invoice_number ?? 'No disponible' }}
                    </div>
                    <div class="qr-shell">
                        <img src="{{ route('fanlyc.coupon.qr', $coupon->code) }}" alt="QR cupón {{ $coupon->code }}" loading="lazy">
                    </div>
                </div>
            @elseif($invoice)
                <div class="coupon">
                    <span class="label">En proceso</span>
                    <div class="coupon-code">Revisión {{ strtoupper($invoice->status) }}</div>
                    <div class="coupon-meta">
                        @if($invoice->status === 'pending_review')
                            Tu factura quedó en revisión manual. Te avisaremos por correo apenas se resuelva.
                        @else
                            Tu registro fue recibido correctamente. Puedes consultar el avance cuando quieras.
                        @endif
                    </div>
                </div>
            @else
                <div class="coupon">
                    <span class="label">Sin datos</span>
                    <div class="coupon-code">Consulta pendiente</div>
                    <div class="coupon-meta">
                        Para ver tu cupón o tu estado, vuelve a ingresar tu cédula y teléfono en la pantalla de consulta.
                    </div>
                </div>
            @endif
        </article>
    </section>

    <div class="footer">Super Carnes · Fanlyc</div>
</main>
</body>
</html>
