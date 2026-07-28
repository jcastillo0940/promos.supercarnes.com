<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Fanlyc - Super Carnes</title>
    <style>
        :root{
            --sky:#13a6e0;
            --sky-soft:#d9f4ff;
            --panel:#f8f6fb;
            --panel-strong:#ffffff;
            --ink:#173e83;
            --purple:#7d3fe6;
            --green:#4ac126;
            --orange:#ff7b22;
            --yellow:#f5bf1b;
            --shadow:0 18px 32px rgba(17,69,133,.18);
        }
        *{box-sizing:border-box}
        body{
            margin:0;
            font-family:Inter, Arial, sans-serif;
            color:var(--ink);
            background:
                radial-gradient(circle at 8% 10%, rgba(255,255,255,.22), transparent 18%),
                radial-gradient(circle at 92% 12%, rgba(255,255,255,.12), transparent 16%),
                linear-gradient(180deg, #0ca0db 0%, #1699cf 100%);
            min-height:100vh;
        }
        .wrap{
            width:min(1440px, calc(100vw - 24px));
            margin:0 auto;
            padding:14px 0 20px;
        }
        .topbar{
            display:grid;
            grid-template-columns:auto 1fr auto;
            gap:18px;
            align-items:center;
            padding:14px 20px;
            border-radius:26px;
            background:rgba(255,255,255,.95);
            box-shadow:var(--shadow);
        }
        .brand{
            display:flex;
            align-items:center;
            gap:16px;
            min-width:0;
        }
        .brand img.logo{
            width:112px;
            display:block;
        }
        .brand-sep{
            width:1px;
            height:48px;
            background:rgba(23,62,131,.15);
        }
        .brand-mark{
            font-weight:900;
            font-size:clamp(18px, 2.1vw, 30px);
            letter-spacing:-.04em;
            line-height:1;
            color:#1852a7;
            white-space:nowrap;
        }
        .nav{
            display:flex;
            justify-content:flex-end;
            gap:30px;
            flex-wrap:wrap;
        }
        .nav a{
            color:#1852a7;
            text-decoration:none;
            font-weight:900;
            font-size:15px;
        }
        .main-grid{
            display:grid;
            grid-template-columns:1.03fr .97fr;
            gap:14px;
            margin-top:14px;
        }
        .panel{
            position:relative;
            overflow:hidden;
            border-radius:32px;
            box-shadow:var(--shadow);
        }
        .hero-panel{
            min-height:640px;
            padding:28px 28px 22px;
            background:
                radial-gradient(circle at 8% 18%, rgba(255,122,34,.95) 0 5px, transparent 6px),
                radial-gradient(circle at 92% 20%, rgba(70,194,36,.95) 0 5px, transparent 6px),
                radial-gradient(circle at 90% 82%, rgba(25,100,189,.95) 0 5px, transparent 6px),
                linear-gradient(180deg, #92def6 0%, #a9e6fb 100%);
        }
        .hero-panel::before,
        .hero-panel::after{
            content:'';
            position:absolute;
            pointer-events:none;
        }
        .hero-panel::before{
            left:-22px; top:104px;
            width:120px; height:260px;
            border-left:14px solid var(--orange);
            border-top:14px solid var(--orange);
            border-bottom:14px solid var(--orange);
            border-radius:120px 0 0 120px;
            transform:rotate(-8deg);
        }
        .hero-panel::after{
            right:22px; top:14px;
            width:96px; height:96px;
            background:linear-gradient(135deg, rgba(102, 47, 214,.18), rgba(102, 47, 214,.18));
            clip-path:polygon(50% 0%, 62% 36%, 100% 50%, 62% 64%, 50% 100%, 38% 64%, 0% 50%, 38% 36%);
            opacity:.9;
        }
        .hero-copy{
            position:relative;
            z-index:1;
            height:100%;
            display:grid;
            grid-template-rows:auto auto auto 1fr auto;
            align-content:start;
        }
        .hero-title{
            margin:40px 0 0;
            font-weight:900;
            letter-spacing:-.06em;
            line-height:.9;
            font-size:clamp(58px, 5.8vw, 96px);
            color:#1d4da7;
        }
        .hero-title .rainbow{
            display:inline-block;
            background:linear-gradient(90deg, #7a3bf1 0%, #f13c76 25%, #ff8f19 50%, #57c62a 75%, #ff3f7d 100%);
            -webkit-background-clip:text;
            background-clip:text;
            -webkit-text-fill-color:transparent;
        }
        .hero-sub{
            margin:12px 0 0;
            max-width:520px;
            font-size:clamp(18px, 1.6vw, 22px);
            line-height:1.35;
            color:#1c4c95;
        }
        .hero-slogan{
            margin:28px 0 0;
            font-size:clamp(34px, 3vw, 58px);
            line-height:.95;
            font-weight:900;
            color:#fff;
            text-shadow:0 4px 0 rgba(21,76,155,.12);
            font-family:"Comic Sans MS", "Trebuchet MS", cursive;
            letter-spacing:.02em;
        }
        .how{
            margin-top:32px;
        }
        .how h2{
            margin:0 0 12px;
            text-align:center;
            color:#1c4c95;
            font-size:clamp(24px, 2vw, 30px);
        }
        .steps{
            display:grid;
            grid-template-columns:repeat(3, 1fr);
            gap:12px;
        }
        .step{
            display:grid;
            justify-items:center;
            gap:8px;
            text-align:center;
            color:#173e83;
        }
        .step-num{
            width:36px;
            height:36px;
            border-radius:50%;
            display:grid;
            place-items:center;
            color:#fff;
            font-weight:900;
            box-shadow:0 10px 18px rgba(0,0,0,.12);
        }
        .step:nth-child(1) .step-num{background:#ffbf0a}
        .step:nth-child(2) .step-num{background:#5bc022}
        .step:nth-child(3) .step-num{background:#7d3fe6}
        .step-icon{
            width:90px;
            height:90px;
            border-radius:50%;
            background:rgba(255,255,255,.96);
            box-shadow:0 14px 24px rgba(17,69,133,.10);
            display:grid;
            place-items:center;
            font-size:38px;
        }
        .step-title{
            font-weight:900;
            font-size:16px;
            line-height:1.15;
        }
        .chips{
            display:flex;
            flex-wrap:wrap;
            gap:10px;
            margin-top:24px;
            justify-content:flex-start;
        }
        .chip{
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:10px 14px;
            border-radius:999px;
            color:#163b78;
            background:#fff;
            font-weight:900;
            box-shadow:0 10px 18px rgba(17,69,133,.12);
        }
        .chip.yellow{background:var(--yellow)}
        .chip.green{background:#62c83a}
        .chip.orange{background:#ff8c3a}
        .chip.purple{background:#8b5cf6}
        .form-panel{
            min-height:640px;
            padding:24px;
            background:
                radial-gradient(circle at 92% 10%, rgba(125,63,230,.22), transparent 11%),
                linear-gradient(180deg, #fbfbfd 0%, #f3f0f8 100%);
        }
        .form-head{
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
            gap:12px;
            margin-bottom:16px;
        }
        .form-head h2{
            margin:0;
            font-size:clamp(32px, 3vw, 50px);
            line-height:1;
            color:var(--purple);
            font-weight:900;
        }
        .sticky{
            width:74px;
            height:20px;
            background:rgba(125,63,230,.5);
            border-radius:3px;
            transform:rotate(-12deg);
            margin-top:4px;
        }
        .tabs{
            display:grid;
            grid-template-columns:repeat(3, 1fr);
            gap:12px;
            margin:14px 0 12px;
        }
        .tab-btn{
            border:0;
            border-radius:14px;
            background:#fff;
            color:#1d4ea1;
            font-weight:900;
            padding:14px 10px;
            cursor:pointer;
            box-shadow:0 8px 18px rgba(17,69,133,.08);
            display:flex;
            justify-content:center;
            align-items:center;
            gap:8px;
            min-height:58px;
        }
        .tab-btn.is-active{
            background:linear-gradient(180deg, #8e44ff 0%, #6e2dd4 100%);
            color:#fff;
            box-shadow:0 12px 20px rgba(125,63,230,.26);
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
            color:#1e3f7e;
        }
        input{
            width:100%;
            border:1px solid rgba(23,62,131,.12);
            border-radius:16px;
            padding:13px 14px;
            font:inherit;
            background:#fff;
            box-shadow:inset 0 0 0 1px rgba(255,255,255,.7);
        }
        input:focus{outline:none; box-shadow:0 0 0 4px rgba(125,63,230,.14)}
        .code-row{
            margin-top:4px;
            padding-top:8px;
            border-top:2px solid rgba(23,62,131,.10);
        }
        .helper{
            display:flex;
            gap:8px;
            align-items:flex-start;
            color:#5b6f91;
            font-size:13px;
            line-height:1.45;
            margin-top:8px;
        }
        .helper .icon{color:#7d3fe6; font-weight:900}
        .check{
            display:flex;
            gap:10px;
            align-items:flex-start;
            font-size:13px;
            line-height:1.45;
            font-weight:700;
            color:#1e3f7e;
        }
        .check input{
            width:18px;
            height:18px;
            margin-top:2px;
            accent-color:var(--purple);
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
            background:linear-gradient(180deg, #ffcf2d 0%, #f3b10a 100%);
            color:#5b3f00;
            box-shadow:0 16px 24px rgba(243,177,10,.22);
        }
        .btn-secondary{
            background:#fff;
            color:#1d4ea1;
            box-shadow:inset 0 0 0 1px rgba(23,62,131,.12);
        }
        .status,.error{
            padding:12px 14px;
            border-radius:16px;
            font-size:13px;
            line-height:1.45;
        }
        .status{background:#e8fff2; color:#0d6f49; border:1px solid #b7f0cf}
        .error{background:#fff0f0; color:#b42318; border:1px solid #ffc7c7}
        .mini-grid{
            display:grid;
            grid-template-columns:repeat(3, 1fr);
            gap:14px;
            margin-top:14px;
        }
        .mini-card{
            min-height:130px;
            border-radius:22px;
            padding:18px;
            background:rgba(255,255,255,.92);
            box-shadow:var(--shadow);
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
        }
        .mini-card.purple h3{color:#7d3fe6}
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
        @media (max-width: 1180px){
            .main-grid,.mini-grid{grid-template-columns:1fr}
            .hero-panel,.form-panel{min-height:auto}
        }
        @media (max-width: 760px){
            .wrap{width:min(100vw - 14px, 1440px)}
            .topbar{
                grid-template-columns:1fr;
                justify-items:center;
                text-align:center;
            }
            .brand{justify-content:center; flex-wrap:wrap}
            .brand-sep{display:none}
            .nav{justify-content:center; gap:14px}
            .hero-panel,.form-panel{padding:18px}
            .hero-title{font-size:48px}
            .hero-sub{font-size:16px}
            .hero-slogan{font-size:30px}
            .steps,.tabs,.field-row{grid-template-columns:1fr}
        }
    </style>
</head>
<body>
<main class="wrap">
    <header class="topbar">
        <div class="brand">
            <img class="logo" src="/logo_web.jpg" alt="Super Carnes">
            <div class="brand-sep" aria-hidden="true"></div>
            <div class="brand-mark">Fanlyc ★ Relevo por la vida</div>
        </div>
        <nav class="nav" aria-label="Secciones">
            <a href="#inicio">Inicio</a>
            <a href="#premios">Premios</a>
            <a href="#zona">Zona asignada</a>
            <a href="#apoyo">Apoyo comunitario</a>
        </nav>
    </header>

    <section id="inicio" class="main-grid">
        <article class="panel hero-panel">
            <div class="hero-copy">
                <h1 class="hero-title"><span class="rainbow">Fanlyc</span><br>para tu factura</h1>
                <p class="hero-sub">
                    Registra tu factura de Super Carnes, valida tu cupón y canjéalo en la zona que te corresponde.
                </p>
                <div class="hero-slogan">Corre por su futuro</div>

                <div class="how">
                    <h2>Cómo participar</h2>
                    <div class="steps">
                        <div class="step">
                            <div class="step-num">1</div>
                            <div class="step-icon">▣</div>
                            <div class="step-title">Escanea<br>tu factura</div>
                        </div>
                        <div class="step">
                            <div class="step-num">2</div>
                            <div class="step-icon">▣</div>
                            <div class="step-title">Llena<br>tus datos</div>
                        </div>
                        <div class="step">
                            <div class="step-num">3</div>
                            <div class="step-icon">★</div>
                            <div class="step-title">Recibe<br>tu cupón</div>
                        </div>
                    </div>
                </div>

                <div class="chips">
                    <span class="chip yellow">QR de factura</span>
                    <span class="chip green">Canje por zona</span>
                    <span class="chip orange">Premios</span>
                    <span class="chip purple">Apoyo comunitario</span>
                </div>
            </div>
        </article>

        <article class="panel form-panel">
            <div class="form-head">
                <h2>Registra tu factura</h2>
                <div class="sticky" aria-hidden="true"></div>
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
                        <input name="full_name" value="{{ old('full_name') }}" placeholder="Ej. Juan Perez" required>
                    </label>
                    <label>Cedula
                        <input name="cedula" value="{{ old('cedula') }}" placeholder="Ej. 8-123-4567" required>
                    </label>
                </div>
                <div class="field-row">
                    <label>Correo electronico
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="Ej. juan@gmail.com" required>
                    </label>
                    <label>Telefono
                        <input name="phone" value="{{ old('phone') }}" placeholder="Ej. 6000-0000" required>
                    </label>
                </div>

                <input type="hidden" id="qr_raw_text" name="qr_raw_text" value="{{ old('qr_raw_text') }}">

                <div id="scan-panel" class="tab-panel code-row">
                    <label>Codigo de factura (QR / CUFE)
                        <input id="qr_raw_text_scan" value="{{ old('qr_raw_text') }}" placeholder="Pega el codigo QR o CUFE aqui" autocomplete="off">
                    </label>
                    <div class="helper"><span class="icon">⌁</span><span>Puedes usar la camara para escanear o pegar el codigo directamente.</span></div>
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
                    <a class="btn btn-primary" style="display:inline-flex;align-items:center;justify-content:center;text-decoration:none;margin-top:12px;" target="_blank" rel="noopener"
                       href="https://wa.me/50768982167?text=Hola%20Super%20Carnes,%20quiero%20registrar%20mi%20factura%20para%20Fanlyc">
                        Contactar por WhatsApp
                    </a>
                </div>

                <div id="form-field-error" class="error" style="display:none;"></div>

                <label class="check">
                    <input type="checkbox" name="consent_terms" value="1" required>
                    <span>Acepto los terminos de Fanlyc y autorizo a Super Carnes a validar mi factura.</span>
                </label>

                @if ($errors->any())
                    <div class="error">{{ $errors->first() }}</div>
                @endif
                @if (session('status'))
                    <div class="status">{{ session('status') }}</div>
                @endif

                <button class="btn btn-primary" type="submit">Registrar factura</button>
            </form>
        </article>
    </section>

    <section id="premios" class="mini-grid">
        <article class="mini-card purple">
            <h3>Tu QR</h3>
            <p>Se genera al aprobar tu factura y queda listo para consulta y canje en tu zona.</p>
        </article>
        <article id="zona" class="mini-card green">
            <h3>Zona asignada</h3>
            <p>El sistema detecta tu sucursal y define la zona correcta para tu cupón.</p>
        </article>
        <article id="apoyo" class="mini-card orange">
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

        if (!value) {
            event.preventDefault();
            fieldError.textContent = activeTab === 'manual'
                ? 'Escribe el CUFE de tu factura.'
                : activeTab === 'scan'
                    ? 'Escanea o pega el codigo de tu factura.'
                    : 'Usa WhatsApp si prefieres ayuda directa.';
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
