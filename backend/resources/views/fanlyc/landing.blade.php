<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Fanlyc - Super Carnes</title>
    <style>
        :root{
            --blue:#0f84d7;
            --blue-deep:#0b4f9a;
            --panel:#0d4d97;
            --white:#ffffff;
            --ink:#123d87;
            --text:#173e83;
            --yellow:#ffcc11;
            --green:#55c63b;
            --orange:#ff7a1d;
            --pink:#ef3b8f;
            --purple:#8148e8;
            --shadow:0 22px 40px rgba(8,56,122,.18);
        }
        *{box-sizing:border-box}
        html{scroll-behavior:smooth}
        body{
            margin:0;
            font-family:Inter, Arial, sans-serif;
            color:var(--text);
            background:
                radial-gradient(circle at 10% 10%, rgba(255,255,255,.10), transparent 20%),
                linear-gradient(180deg, #1297d8 0%, #0786d0 100%);
            min-height:100vh;
        }
        .wrap{
            width:min(1536px, calc(100vw - 26px));
            margin:0 auto;
            padding:14px 0 18px;
        }
        .topbar{
            display:grid;
            grid-template-columns:auto 1fr auto;
            gap:18px;
            align-items:center;
            padding:12px 18px;
            border-radius:28px;
            background:rgba(255,255,255,.96);
            box-shadow:var(--shadow);
        }
        .brand{
            display:flex;
            align-items:center;
            gap:18px;
            min-width:0;
        }
        .brand img{
            width:120px;
            display:block;
        }
        .brand-line{
            width:1px;
            height:42px;
            background:rgba(18,61,135,.18);
        }
        .brand-text{
            display:flex;
            align-items:center;
            gap:12px;
            font-weight:900;
            white-space:nowrap;
            color:#1950a1;
            font-size:clamp(18px, 2vw, 30px);
        }
        .nav{
            display:flex;
            justify-content:flex-end;
            gap:36px;
            flex-wrap:wrap;
        }
        .nav a{
            color:#1950a1;
            text-decoration:none;
            font-weight:900;
            font-size:15px;
        }
        .main-grid{
            display:grid;
            grid-template-columns:minmax(0, 1.05fr) minmax(440px, .95fr);
            gap:14px;
            margin-top:14px;
            align-items:stretch;
        }
        .poster,
        .form{
            position:relative;
            overflow:hidden;
            border-radius:32px;
            box-shadow:var(--shadow);
        }
        .poster{
            min-height:780px;
            padding:26px 28px 22px;
            background:
                radial-gradient(circle at 12% 16%, rgba(255,148,31,.95) 0 5px, transparent 6px),
                radial-gradient(circle at 92% 82%, rgba(255,170,0,.85) 0 5px, transparent 6px),
                linear-gradient(180deg, #1596d8 0%, #0a84d1 100%);
        }
        .poster-inner{
            position:relative;
            z-index:1;
            display:grid;
            grid-template-rows:auto auto auto 1fr auto;
            height:100%;
        }
        .hero-accents{
            position:absolute;
            inset:0;
            z-index:0;
            pointer-events:none;
        }
        .hero-accents .squiggle{
            position:absolute;
            left:-54px;
            top:-18px;
            width:260px;
            height:160px;
            overflow:hidden;
        }
        .hero-accents .squiggle img{
            width:100%;
            max-width:none;
            height:auto;
            transform:rotate(-6deg);
        }
        .hero-accents .star{
            position:absolute;
            right:24px;
            top:18px;
            width:112px;
            height:112px;
            overflow:hidden;
        }
        .hero-accents .green{
            position:absolute;
            right:-14px;
            top:56px;
            width:220px;
            height:120px;
            overflow:hidden;
        }
        .hero-accents .burst{
            position:absolute;
            right:26px;
            bottom:28px;
            width:140px;
            height:100px;
            overflow:hidden;
        }
        .hero-accents .star img{
            width:100%;
            height:auto;
            display:block;
            transform:rotate(-2deg);
        }
        .hero-accents .green img,
        .hero-accents .burst img{
            width:100%;
            height:auto;
            display:block;
        }
        .hero-brand{
            display:flex;
            flex-direction:column;
            align-items:flex-start;
            gap:10px;
        }
        .hero-brand .mark{
            display:flex;
            align-items:center;
            gap:14px;
            flex-wrap:wrap;
        }
        .hero-brand .mark img{
            width:230px;
            max-width:65vw;
        }
        .hero-brand .line{
            display:flex;
            align-items:center;
            gap:18px;
            flex-wrap:wrap;
            margin-top:8px;
            color:#fff;
            font-weight:900;
        }
        .fanlyc-word{
            font-size:clamp(60px, 5.9vw, 112px);
            line-height:.9;
            font-weight:900;
            color:#fff;
            letter-spacing:-.06em;
            margin:18px 0 0;
        }
        .fanlyc-word .white{
            display:block;
            color:#fff;
            text-shadow:0 8px 20px rgba(0,0,0,.06);
        }
        .fanlyc-word .yellow{
            color:var(--yellow);
            display:block;
            margin-top:4px;
        }
        .hero-desc{
            margin-top:14px;
            max-width:520px;
            color:#ffffff;
            font-size:clamp(17px, 1.5vw, 22px);
            line-height:1.45;
        }
        .hero-boy{
            position:absolute;
            right:10px;
            bottom:120px;
            width:min(37vw, 460px);
            z-index:0;
            pointer-events:none;
        }
        .hero-boy img{
            width:100%;
            height:auto;
            display:block;
            filter:drop-shadow(0 20px 24px rgba(0,0,0,.18));
        }
        .hero-cta{
            margin-top:18px;
            font-size:clamp(32px, 2.7vw, 58px);
            line-height:.95;
            color:#fff;
            font-weight:900;
            font-family:"Comic Sans MS", "Trebuchet MS", cursive;
            letter-spacing:.02em;
        }
        .how{
            margin-top:30px;
        }
        .how h2{
            margin:0 0 16px;
            color:#fff;
            font-size:clamp(22px, 2vw, 32px);
            text-align:center;
        }
        .steps{
            display:grid;
            grid-template-columns:repeat(3, 1fr);
            gap:14px;
        }
        .step{
            display:grid;
            justify-items:center;
            gap:8px;
            text-align:center;
            color:#fff;
        }
        .step-num{
            width:32px;height:32px;
            border-radius:50%;
            display:grid;
            place-items:center;
            font-weight:900;
            color:#fff;
            box-shadow:0 10px 18px rgba(0,0,0,.12);
        }
        .step:nth-child(1) .step-num{background:#ffbf0a}
        .step:nth-child(2) .step-num{background:#55c63b}
        .step:nth-child(3) .step-num{background:#7d3fe6}
        .step-icon{
            width:92px;height:92px;
            border-radius:50%;
            background:rgba(255,255,255,.96);
            box-shadow:0 14px 24px rgba(8,56,122,.10);
            display:grid;
            place-items:center;
            overflow:hidden;
        }
        .step-icon svg{width:42px;height:42px}
        .step-title{
            font-weight:900;
            font-size:16px;
            line-height:1.12;
            text-shadow:0 1px 0 rgba(0,0,0,.08);
        }
        .chips{
            display:flex;
            flex-wrap:wrap;
            gap:10px;
            margin-top:24px;
        }
        .chip{
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:10px 14px;
            border-radius:999px;
            background:#fff;
            color:#173e83;
            font-weight:900;
            box-shadow:0 10px 18px rgba(8,56,122,.14);
        }
        .chip:nth-child(1){background:var(--yellow)}
        .chip:nth-child(2){background:#5acb3d}
        .chip:nth-child(3){background:#ff7f34}
        .chip:nth-child(4){background:#8a55ed}

        .form{
            min-height:780px;
            padding:26px 24px 24px;
            background:
                radial-gradient(circle at 92% 10%, rgba(125,63,230,.16), transparent 10%),
                linear-gradient(180deg, #183f7a 0%, #12478f 100%);
        }
        .form-head{
            text-align:center;
            color:#fff;
            padding:6px 0 10px;
        }
        .form-head h2{
            margin:0;
            font-size:clamp(30px, 3vw, 48px);
            line-height:1;
            font-weight:900;
            letter-spacing:-.04em;
            text-transform:uppercase;
        }
        .form-head p{
            margin:10px auto 0;
            max-width:430px;
            font-size:16px;
            line-height:1.4;
            color:rgba(255,255,255,.93);
        }
        .tabs{
            display:grid;
            grid-template-columns:repeat(3, 1fr);
            gap:12px;
            margin:18px 0 12px;
        }
        .tab-btn{
            border:0;
            border-radius:16px;
            background:#fff;
            color:#1d4ea1;
            font-weight:900;
            padding:14px 10px;
            cursor:pointer;
            box-shadow:0 10px 18px rgba(0,0,0,.10);
            display:flex;
            align-items:center;
            justify-content:center;
            gap:10px;
            min-height:62px;
        }
        .tab-btn.is-active{
            background:linear-gradient(180deg, #8d48ff 0%, #6b2cd6 100%);
            color:#fff;
            box-shadow:0 12px 20px rgba(123,59,230,.22);
        }
        .tab-panel[hidden]{display:none}
        form{
            display:grid;
            gap:14px;
        }
        .field-row{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:12px;
        }
        label{
            display:grid;
            gap:6px;
            font-size:13px;
            font-weight:900;
            color:#fff;
        }
        input{
            width:100%;
            border:1px solid rgba(255,255,255,.14);
            border-radius:16px;
            padding:13px 14px;
            font:inherit;
            background:#fff;
            color:#19366f;
            box-shadow:inset 0 0 0 1px rgba(255,255,255,.65);
        }
        input::placeholder{color:#8d97b3}
        input:focus{outline:none; box-shadow:0 0 0 4px rgba(255,255,255,.14)}
        .code-row{
            padding-top:10px;
            border-top:1px solid rgba(255,255,255,.18);
        }
        .helper{
            display:flex;
            gap:8px;
            align-items:flex-start;
            color:rgba(255,255,255,.85);
            font-size:13px;
            line-height:1.45;
            margin-top:8px;
        }
        .helper .icon{color:var(--yellow); font-weight:900}
        .check{
            display:flex;
            gap:10px;
            align-items:flex-start;
            font-size:13px;
            line-height:1.45;
            color:#fff;
            font-weight:700;
        }
        .check input{
            width:18px;
            height:18px;
            margin-top:2px;
            accent-color:var(--yellow);
        }
        .btn{
            border:0;
            border-radius:18px;
            padding:15px 18px;
            font:inherit;
            font-weight:900;
            cursor:pointer;
        }
        .btn-primary{
            background:linear-gradient(180deg, #ffcf2d 0%, #f2b70b 100%);
            color:#654400;
            box-shadow:0 16px 24px rgba(242,183,11,.20);
        }
        .btn-secondary{
            background:#fff;
            color:#1d4ea1;
            box-shadow:0 10px 18px rgba(0,0,0,.10);
        }
        .status,.error{
            padding:12px 14px;
            border-radius:16px;
            font-size:13px;
            line-height:1.45;
        }
        .status{background:#eafbea; color:#0d6f49; border:1px solid #b8eccb}
        .error{background:#fff0f0; color:#b42318; border:1px solid #ffc7c7}
        .status-link{
            margin-top:12px;
            color:#ffcf2d;
            text-decoration:none;
            font-weight:900;
        }
        .mini-grid{
            margin-top:14px;
            display:grid;
            grid-template-columns:repeat(3, 1fr);
            gap:14px;
        }
        .mini-card{
            min-height:130px;
            border-radius:22px;
            padding:18px;
            background:rgba(255,255,255,.92);
            box-shadow:var(--shadow);
            position:relative;
            overflow:hidden;
        }
        .mini-card h3{
            margin:0 0 8px;
            font-size:20px;
            line-height:1;
        }
        .mini-card p{
            margin:0;
            line-height:1.45;
            color:#4e6786;
            font-size:14px;
            max-width:240px;
        }
        .mini-card .icon-badge{
            width:74px;height:74px;
            border-radius:50%;
            display:grid;
            place-items:center;
            margin-bottom:10px;
            background:rgba(125,63,230,.13);
        }
        .mini-card.green .icon-badge{background:rgba(85,198,59,.16)}
        .mini-card.orange .icon-badge{background:rgba(255,122,29,.16)}
        .mini-card.purple h3{color:#7b3be6}
        .mini-card.green h3{color:#3ca81d}
        .mini-card.orange h3{color:#f07813}
        .footer-badge{
            margin-top:14px;
            color:#fff;
            text-align:center;
            font-weight:900;
            letter-spacing:.14em;
            text-transform:uppercase;
            opacity:.95;
        }
        .logo-lock{
            display:flex;
            justify-content:center;
            margin:10px auto 0;
            max-width:280px;
        }
        .logo-lock img{width:100%; display:block}
        @media (max-width: 1180px){
            .main-grid,.mini-grid{grid-template-columns:1fr}
            .poster,.form{min-height:auto}
        }
        @media (max-width: 760px){
            .wrap{width:min(100vw - 14px, 1536px)}
            .topbar{
                grid-template-columns:1fr;
                justify-items:center;
                text-align:center;
            }
            .brand{
                justify-content:center;
                flex-wrap:wrap;
            }
            .brand-line{display:none}
            .nav{justify-content:center; gap:14px}
            .poster,.form{padding:18px}
            .fanlyc-word{font-size:50px}
            .tabs,.field-row,.steps{grid-template-columns:1fr}
            .how{margin-top:22px}
            .step-title{font-size:15px}
        }
    </style>
</head>
<body>
<main class="wrap">
    <header class="topbar">
        <div class="brand">
            <img src="/logo_web.jpg" alt="Super Carnes">
            <div class="brand-line" aria-hidden="true"></div>
            <div class="brand-text">Fanlyc ★ Relevo por la vida</div>
        </div>
        <nav class="nav" aria-label="Secciones">
            <a href="#inicio">Inicio</a>
            <a href="#premios">Premios</a>
            <a href="#zona">Zona asignada</a>
            <a href="#apoyo">Apoyo comunitario</a>
        </nav>
    </header>

    <section id="inicio" class="main-grid">
        <article class="poster">
            <div class="hero-accents" aria-hidden="true">
                <div class="squiggle"><img src="/fanlyc-assets/fanlyc-squiggle-orange.png" alt=""></div>
                <div class="green"><img src="/fanlyc-assets/fanlyc-squiggle-green.png" alt=""></div>
                <div class="star"><img src="/fanlyc-assets/fanlyc-star-yellow.png" alt=""></div>
                <div class="burst"><img src="/fanlyc-assets/fanlyc-burst-yellow.png" alt=""></div>
            </div>
            <div class="poster-inner">
                <div class="hero-brand">
                    <div class="mark">
                        <img src="/logo_web.jpg" alt="Super Carnes">
                    </div>
                    <div class="line">
                        <span style="font-size:28px;color:#ffd21a;">FANLYC</span>
                        <span style="font-size:28px;color:#ffd21a;">★</span>
                        <span style="font-size:28px;color:#ffffff;">RELEVO POR LA VIDA</span>
                    </div>
                </div>

                <h1 class="fanlyc-word">
                    <span class="white">FANLYC</span>
                    <span class="yellow">PARA TU FACTURA</span>
                </h1>

                <p class="hero-desc">
                    Registra tu factura de Super Carnes, valida tu cupón y canjéalo en la zona que te corresponde.
                    El proceso es simple: escanea, completa tus datos y recibe tu QR.
                </p>
                <div class="hero-boy">
                    <img src="/fanlyc-assets/fanlyc-boy-hero-transparent.png" alt="Niño celebrando Fanlyc">
                </div>

                <div class="hero-cta">Corre por su futuro</div>

                <div class="how">
                    <h2>Cómo participar</h2>
                    <div class="steps">
                        <div class="step">
                            <div class="step-num">1</div>
                            <div class="step-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="#1d4ea1" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 4h16v16H4z"></path>
                                    <path d="M8 8h8v8H8z"></path>
                                    <path d="M10 10h4v4h-4z"></path>
                                </svg>
                            </div>
                            <div class="step-title">Escanea<br>tu factura</div>
                        </div>
                        <div class="step">
                            <div class="step-num">2</div>
                            <div class="step-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="#1d4ea1" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="14" rx="2"></rect>
                                    <path d="M7 18v2M17 18v2M8 8h8"></path>
                                </svg>
                            </div>
                            <div class="step-title">Llena<br>tus datos</div>
                        </div>
                        <div class="step">
                            <div class="step-num">3</div>
                            <div class="step-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="#1d4ea1" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 21s7-4.5 7-11a7 7 0 0 0-14 0c0 6.5 7 11 7 11Z"></path>
                                    <circle cx="12" cy="10" r="2"></circle>
                                </svg>
                            </div>
                            <div class="step-title">Recibe<br>tu cupón</div>
                        </div>
                    </div>
                </div>

                <div class="chips">
                    <span class="chip">QR de factura</span>
                    <span class="chip">Canje por zona</span>
                    <span class="chip">Premios</span>
                    <span class="chip">Apoyo comunitario</span>
                </div>
            </div>
        </article>

        <article class="form">
            <div class="form-head">
                <h2>Registra tu factura</h2>
                <p>Elige cómo deseas registrar tu factura y sigue los pasos.</p>
            </div>

            <div class="tabs">
                <button type="button" class="tab-btn is-active" data-tab="scan">Escanear QR</button>
                <button type="button" class="tab-btn" data-tab="manual">Escribir CUFE</button>
                <button type="button" class="tab-btn" data-tab="whatsapp">WhatsApp</button>
            </div>

            <form id="registration-form" method="POST" action="{{ route('fanlyc.store') }}">
                @csrf
                <div class="field-row">
                    <label>Nombre completo
                        <input name="full_name" value="{{ old('full_name') }}" placeholder="Ej. Juan Pérez" required>
                    </label>
                    <label>Cédula
                        <input name="cedula" value="{{ old('cedula') }}" placeholder="Ej. 8-123-4567" required>
                    </label>
                </div>
                <div class="field-row">
                    <label>Correo electrónico
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="Ej. juan@gmail.com" required>
                    </label>
                    <label>Teléfono
                        <input name="phone" value="{{ old('phone') }}" placeholder="Ej. 6000-0000" required>
                    </label>
                </div>

                <input type="hidden" id="qr_raw_text" name="qr_raw_text" value="{{ old('qr_raw_text') }}">

                <div id="scan-panel" class="tab-panel code-row">
                    <label>Código de factura (QR / CUFE)
                        <input id="qr_raw_text_scan" value="{{ old('qr_raw_text') }}" placeholder="Pega el código QR o CUFE aquí" autocomplete="off">
                    </label>
                    <div class="helper"><span class="icon">⌁</span><span>Puedes usar la cámara para escanear o pegar el código directamente.</span></div>
                </div>

                <div id="manual-panel" class="tab-panel code-row" hidden>
                    <label>Escribe el CUFE de tu factura
                        <input id="cufe_manual" placeholder="Ej: FE0120000000032812-2-249262-..." autocomplete="off">
                    </label>
                </div>

                <div id="whatsapp-panel" class="tab-panel code-row" hidden>
                    <div class="status" style="margin-top:0;">
                        ¿Prefieres ayuda? Escríbenos por WhatsApp y te guiamos para registrar tu factura.
                    </div>
                    <a class="btn btn-secondary" style="display:inline-flex;align-items:center;justify-content:center;text-decoration:none;margin-top:12px;"
                       target="_blank" rel="noopener"
                       href="https://wa.me/50768982167?text=Hola%20Super%20Carnes,%20quiero%20registrar%20mi%20factura%20para%20Fanlyc">
                        Contactar por WhatsApp
                    </a>
                </div>

                <div id="form-field-error" class="error" style="display:none;"></div>

                <label class="check">
                    <input type="checkbox" name="consent_terms" value="1" required>
                    <span>Acepto los términos de Fanlyc y autorizo a Super Carnes a validar mi factura.</span>
                </label>

                @if ($errors->any())
                    <div class="error">{{ $errors->first() }}</div>
                @endif
                @if (session('status'))
                    <div class="status">{{ session('status') }}</div>
                @endif

                <button class="btn btn-primary" type="submit">Registrar factura</button>
                <a class="status-link" href="{{ route('fanlyc.status') }}">Ver mis cupones</a>
            </form>
        </article>
    </section>

    <section id="premios" class="mini-grid">
        <article class="mini-card purple">
            <div class="icon-badge" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="#7d3fe6" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 4h16v12H4z"></path>
                    <path d="M7 20h10"></path>
                    <path d="M12 8v4M10 10h4"></path>
                </svg>
            </div>
            <h3>Tu QR</h3>
            <p>Se genera al aprobar tu factura y queda listo para consulta y canje en tu zona.</p>
        </article>
        <article id="zona" class="mini-card green">
            <div class="icon-badge" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="#3ca81d" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 21s6-4.2 6-10a6 6 0 0 0-12 0c0 5.8 6 10 6 10Z"></path>
                    <circle cx="12" cy="11" r="2.2"></circle>
                </svg>
            </div>
            <h3>Zona asignada</h3>
            <p>El sistema detecta tu sucursal y define la zona correcta para tu cupón.</p>
        </article>
        <article id="apoyo" class="mini-card orange">
            <div class="icon-badge" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="#f07813" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 21s7-4.5 7-11a7 7 0 0 0-14 0c0 6.5 7 11 7 11Z"></path>
                    <path d="M9 10h6"></path>
                </svg>
            </div>
            <h3>Canje seguro</h3>
            <p>El staff escanea el cupón, valida el estado y emite el ticket correspondiente.</p>
        </article>
    </section>

    <div class="footer-badge">Super Carnes · Fanlyc</div>
</main>

<script>
(() => {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const scanPanel = document.getElementById('scan-panel');
    const manualPanel = document.getElementById('manual-panel');
    const whatsappPanel = document.getElementById('whatsapp-panel');
    const registrationForm = document.getElementById('registration-form');
    const hiddenInput = document.getElementById('qr_raw_text');
    const scanInput = document.getElementById('qr_raw_text_scan');
    const manualInput = document.getElementById('cufe_manual');
    const fieldError = document.getElementById('form-field-error');
    let activeTab = 'scan';

    const setActiveTab = (tab) => {
        activeTab = tab;
        tabButtons.forEach((btn) => btn.classList.toggle('is-active', btn.dataset.tab === tab));
        scanPanel.hidden = tab !== 'scan';
        manualPanel.hidden = tab !== 'manual';
        whatsappPanel.hidden = tab !== 'whatsapp';
        fieldError.style.display = 'none';
    };

    tabButtons.forEach((btn) => btn.addEventListener('click', () => setActiveTab(btn.dataset.tab)));

    registrationForm?.addEventListener('submit', (event) => {
        const value = activeTab === 'manual'
            ? manualInput.value.trim()
            : activeTab === 'scan'
                ? scanInput.value.trim()
                : hiddenInput.value.trim();

        if (activeTab !== 'whatsapp' && !value) {
            event.preventDefault();
            fieldError.textContent = activeTab === 'manual'
                ? 'Escribe el CUFE de tu factura.'
                : 'Escanea o pega el código de tu factura.';
            fieldError.style.display = 'block';
            return;
        }

        if (activeTab !== 'whatsapp') {
            hiddenInput.value = value;
        }
    });
})();
</script>
</body>
</html>
