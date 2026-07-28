<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Fanlyc - Super Carnes</title>
    <style>
        :root{
            --sky:#45b9e6;
            --green:#30b232;
            --orange:#ff7a2f;
            --purple:#8c57ff;
            --yellow:#ffca16;
            --ink:#16324f;
            --brown:#7a4411;
            --shadow: rgba(18, 58, 86, .16);
        }
        *{box-sizing:border-box}
        body{
            margin:0;
            font-family:Inter, Arial, sans-serif;
            color:var(--ink);
            background:linear-gradient(180deg, #59c2eb 0%, var(--sky) 100%);
            min-height:100vh;
        }
        .wrap{width:min(1160px, calc(100vw - 20px)); margin:0 auto; padding:12px 0 24px}
        .hero{
            position:relative;
            overflow:hidden;
            border-radius:34px;
            background:
                radial-gradient(circle at 12% 14%, rgba(255,255,255,.32), transparent 14%),
                radial-gradient(circle at 90% 12%, rgba(255,255,255,.2), transparent 12%),
                linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.02));
            box-shadow:0 20px 50px var(--shadow);
            padding:26px 24px 24px;
        }
        .hero::before,.hero::after{
            content:'';
            position:absolute;
            pointer-events:none;
            border-radius:50%;
            border:16px solid rgba(255,202,22,.7);
            opacity:.55;
        }
        .hero::before{left:-126px; top:110px; width:220px; height:220px; border-color:rgba(255,122,47,.65)}
        .hero::after{right:-122px; bottom:-100px; width:240px; height:240px; border-color:rgba(45,145,188,.5)}
        .brand{display:flex; justify-content:center; margin-bottom:10px}
        .brand img{width:min(210px, 56vw); display:block; filter: drop-shadow(0 10px 16px rgba(0,0,0,.12))}
        .hero-copy{
            text-align:center;
            color:#fff;
            text-shadow:0 4px 18px rgba(0,0,0,.14);
            padding:4px 0 10px;
        }
        .hero-kicker{
            margin:0 0 8px;
            font-size:13px;
            font-weight:900;
            letter-spacing:.18em;
            text-transform:uppercase;
        }
        .hero-title{
            margin:0;
            font-size:clamp(38px, 6vw, 74px);
            line-height:.92;
            font-weight:900;
            letter-spacing:-.05em;
        }
        .hero-title .fanlyc{
            background:linear-gradient(90deg,#ff2c5c 0%, #ff7d2f 25%, #ffcc17 50%, #35d64a 75%, #63d4ff 100%);
            -webkit-background-clip:text;
            background-clip:text;
            -webkit-text-fill-color:transparent;
        }
        .hero-sub{
            margin:12px auto 0;
            width:min(700px, 100%);
            font-size:17px;
            line-height:1.55;
            max-width:44rem;
        }
        .chip-row{
            display:flex; justify-content:center; flex-wrap:wrap; gap:10px;
            margin-top:18px;
        }
        .chip{
            display:inline-flex; align-items:center; gap:8px;
            padding:10px 14px;
            border-radius:999px;
            color:#fff;
            font-weight:900;
            box-shadow:0 10px 18px rgba(0,0,0,.12);
        }
        .chip.yellow{background:var(--yellow); color:#4d3400}
        .chip.green{background:var(--green)}
        .chip.orange{background:var(--orange)}
        .chip.purple{background:var(--purple)}
        .content{
            margin-top:14px;
            display:grid;
            grid-template-columns:1.1fr .9fr;
            gap:14px;
        }
        .card{
            position:relative;
            overflow:hidden;
            border-radius:30px;
            background:rgba(255,255,255,.92);
            box-shadow:0 18px 38px var(--shadow);
            padding:22px;
        }
        .card h2{margin:0 0 8px; font-size:28px; line-height:1; color:var(--brown)}
        .card p.copy{margin:0 0 14px; color:#35516e; line-height:1.55}
        .steps{display:grid; gap:10px}
        .step{
            display:grid;
            grid-template-columns:38px 1fr;
            gap:10px;
            padding:12px 14px;
            border-radius:22px;
            background:linear-gradient(90deg, rgba(69,185,230,.12), rgba(255,255,255,.72));
            border:1px solid rgba(22,50,79,.08);
        }
        .step-num{
            width:38px; height:38px; border-radius:50%;
            background:var(--yellow); color:#4d3400;
            display:flex; align-items:center; justify-content:center;
            font-weight:900; font-size:18px;
            box-shadow:0 8px 16px rgba(255,202,22,.26);
        }
        .step strong{display:block; margin-bottom:3px}
        .step span{color:#56718b; font-size:14px; line-height:1.45}
        .form-card{display:grid; gap:12px}
        .tabs{display:flex; gap:10px; flex-wrap:wrap; margin-bottom:4px}
        .tab-btn{
            border:0;
            border-radius:999px;
            padding:10px 14px;
            font-weight:900;
            background:rgba(255,255,255,.72);
            color:#22506f;
            cursor:pointer;
        }
        .tab-btn.is-active{background:var(--purple); color:#fff; box-shadow:0 12px 20px rgba(140,87,255,.24)}
        .tab-panel[hidden]{display:none}
        form{display:grid; gap:12px}
        .field-row{display:grid; grid-template-columns:1fr 1fr; gap:12px}
        label{display:grid; gap:6px; font-size:13px; font-weight:800; color:#23486b}
        input{
            width:100%;
            border:0;
            border-radius:16px;
            padding:13px 14px;
            font:inherit;
            background:#fff;
            box-shadow:inset 0 0 0 2px rgba(22,50,79,.08);
        }
        input:focus{outline:none; box-shadow:inset 0 0 0 2px rgba(255,202,22,.95), 0 0 0 4px rgba(255,202,22,.2)}
        .check{display:flex; gap:10px; align-items:flex-start; font-size:13px; line-height:1.5; font-weight:600; color:#23486b}
        .check input{width:18px; height:18px; margin-top:2px; accent-color:var(--yellow)}
        .btn{
            border:0;
            border-radius:16px;
            padding:14px 18px;
            font:inherit;
            font-weight:900;
            cursor:pointer;
        }
        .btn-primary{
            background:linear-gradient(180deg, #ffd31a, #f3b500);
            color:#543800;
            box-shadow:0 14px 22px rgba(243,181,0,.22);
        }
        .btn-secondary{background:#fff; color:#255b86}
        .status,.error{
            padding:12px 14px;
            border-radius:16px;
            font-size:14px;
            line-height:1.45;
        }
        .status{background:#e7fff3; color:#0f6b47; border:1px solid #b9f2d2}
        .error{background:#fff0f0; color:#b42318; border:1px solid #ffc7c7}
        .aside{
            display:grid;
            gap:14px;
        }
        .poster{
            min-height:220px;
            border-radius:28px;
            overflow:hidden;
            position:relative;
            box-shadow:0 16px 28px rgba(0,0,0,.12);
            border:6px solid rgba(255,255,255,.85);
            background:#fff;
        }
        .poster img{width:100%; height:100%; object-fit:cover; display:block}
        .poster .tag{
            position:absolute; left:14px; top:14px;
            background:#fff; color:#ff5e52;
            font-weight:900;
            padding:8px 12px;
            border-radius:999px;
            box-shadow:0 10px 18px rgba(0,0,0,.12);
        }
        .info-card{
            border-radius:28px;
            padding:20px;
            background:rgba(255,255,255,.92);
            box-shadow:0 16px 30px rgba(18,58,86,.12);
        }
        .info-card h3{margin:0 0 8px; font-size:22px; color:var(--brown)}
        .info-card p{margin:0; line-height:1.55; color:#35516e}
        .callouts{
            margin-top:14px;
            display:grid;
            grid-template-columns:repeat(3,1fr);
            gap:14px;
        }
        .callout{
            min-height:148px;
            padding:18px;
            border-radius:26px;
            color:#fff;
            box-shadow:0 16px 26px rgba(0,0,0,.12);
        }
        .callout h3{margin:0 0 8px; font-size:22px; line-height:1}
        .callout p{margin:0; line-height:1.45; font-size:14px}
        .callout.blue{background:linear-gradient(180deg, #44c0e8, #2ca0d4)}
        .callout.green{background:linear-gradient(180deg, #35b65d, #279944)}
        .callout.orange{background:linear-gradient(180deg, #ff8536, #f36d21)}
        .link-row{margin-top:12px; font-size:14px}
        .link-row a{color:#0f6b47; font-weight:900; text-decoration:none}
        .footer-badge{
            margin-top:16px;
            display:flex;
            justify-content:center;
            color:#fff;
            font-weight:900;
            letter-spacing:.14em;
            text-transform:uppercase;
            opacity:.95;
        }
        @media (max-width: 980px){
            .content,.callouts,.field-row{grid-template-columns:1fr}
        }
        @media (max-width: 640px){
            .wrap{width:min(100vw - 14px, 1160px)}
            .hero,.card,.info-card{padding:18px}
            .hero-title{font-size:34px}
            .hero-sub{font-size:15px}
            .step{grid-template-columns:34px 1fr}
            .step-num{width:34px;height:34px;font-size:16px}
        }
    </style>
</head>
<body>
<main class="wrap">
    <section class="hero">
        <div class="brand">
            <img src="/logo_web.jpg" alt="Super Carnes">
        </div>
        <div class="hero-copy">
            <div class="hero-kicker">Fanlyc ★ Relevo por la vida</div>
            <h1 class="hero-title"><span class="fanlyc">Fanlyc</span> para tu factura</h1>
            <p class="hero-sub">Registra tu factura de Super Carnes, valida tu cupón y canjéalo en la zona que te corresponde. El proceso es simple: escanea, completa tus datos y recibe tu QR.</p>
            <div class="chip-row">
                <span class="chip yellow">QR de factura</span>
                <span class="chip green">Canje por zona</span>
                <span class="chip orange">Premios</span>
                <span class="chip purple">Apoyo comunitario</span>
            </div>
        </div>
    </section>

    <section class="content">
        <article class="card">
            <h2>Cómo participar</h2>
            <p class="copy">La experiencia debe sentirse rápida y clara, igual de alegre que la línea social, pero enfocada en convertir.</p>
            <div class="steps">
                <div class="step"><div class="step-num">1</div><div><strong>Escanea tu factura</strong><span>Puedes abrir la cámara o pegar el código del QR.</span></div></div>
                <div class="step"><div class="step-num">2</div><div><strong>Llena tus datos</strong><span>Nombre, cédula, correo y teléfono para darte seguimiento.</span></div></div>
                <div class="step"><div class="step-num">3</div><div><strong>Recibe tu cupón</strong><span>Te confirmamos el registro y tu cupón queda listo para el canje.</span></div></div>
            </div>
            <div class="link-row">
                <p style="margin:0 0 8px;font-weight:900;">Consultar mis cupones</p>
                <form method="GET" action="{{ route('fanlyc.status') }}" class="field-row" style="margin-top:0;">
                    <label style="margin:0;">Cédula
                        <input name="cedula" placeholder="8-123-4567" required>
                    </label>
                    <label style="margin:0;">Teléfono
                        <input name="phone" placeholder="6000-0000" required>
                    </label>
                    <button class="btn btn-secondary" type="submit">Ver cupones</button>
                </form>
            </div>
        </article>

        <aside class="aside">
            <article class="poster">
                <img src="/fonda-assets/step-1.jpeg" alt="Fanlyc" loading="lazy">
                <div class="tag">Fanlyc</div>
            </article>
            <article class="info-card">
                <h3>Registro directo</h3>
                <p>Escanea el QR de tu factura o escribe el CUFE. Esta sección es el punto de entrada, así que la dejamos clara, simple y con énfasis visual fuerte.</p>
            </article>
        </aside>
    </section>

    <section class="content" style="margin-top:14px;">
        <article class="card form-card">
            <h2>Registrar factura</h2>
            <p class="copy">Elige cómo quieres darnos el código de tu factura.</p>

            <div class="tabs">
                <button type="button" class="tab-btn is-active" data-tab="scan">Escanear QR</button>
                <button type="button" class="tab-btn" data-tab="manual">Escribir CUFE</button>
                <button type="button" class="tab-btn" data-tab="whatsapp">WhatsApp</button>
            </div>

            <div id="whatsapp-panel" class="tab-panel" hidden>
                <div class="status" style="margin-top:0;">
                    ¿Prefieres ayuda? Escríbenos por WhatsApp con tus datos y un asesor te orienta.
                </div>
                <a class="btn btn-primary" style="display:inline-flex;align-items:center;justify-content:center;text-decoration:none;margin-top:12px;" target="_blank" rel="noopener"
                   href="https://wa.me/50768982167?text=Hola%20Super%20Carnes,%20quiero%20registrar%20mi%20factura%20para%20Fanlyc">
                    Contactar por WhatsApp
                </a>
            </div>

            <form id="registration-form" method="POST" action="{{ route('fanlyc.store') }}">
                @csrf
                <div class="field-row">
                    <label>Nombre completo
                        <input name="full_name" value="{{ old('full_name') }}" required>
                    </label>
                    <label>Cédula
                        <input name="cedula" value="{{ old('cedula') }}" required>
                    </label>
                </div>
                <div class="field-row">
                    <label>Correo electrónico
                        <input type="email" name="email" value="{{ old('email') }}" required>
                    </label>
                    <label>Teléfono
                        <input name="phone" value="{{ old('phone') }}" required>
                    </label>
                </div>

                <input type="hidden" id="qr_raw_text" name="qr_raw_text" value="{{ old('qr_raw_text') }}">

                <div id="scan-panel" class="tab-panel">
                    <label>Código de factura (QR)
                        <input id="qr_raw_text_scan" value="{{ old('qr_raw_text') }}" placeholder="Pega el contenido del QR o usa la cámara" autocomplete="off">
                    </label>
                    <div class="status">
                        Usa la cámara si deseas, o pega el código QR directamente.
                    </div>
                </div>

                <div id="manual-panel" class="tab-panel" hidden>
                    <label>Escribe el CUFE de tu factura
                        <input id="cufe_manual" placeholder="Ej: FE0120000000032812-2-249262-..." autocomplete="off">
                    </label>
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
            </form>
        </article>

        <aside class="aside">
            <article class="info-card">
                <h3>Fanlyc en corto</h3>
                <p>Subimos la emoción de las piezas sociales a una navegación más funcional. Se siente promocional, pero no se vuelve pesada.</p>
            </article>
            <article class="poster">
                <img src="/fonda-assets/step-2.jpeg" alt="Fanlyc paso 2" loading="lazy">
            </article>
        </aside>
    </section>

    <section class="callouts">
        <article class="callout blue">
            <h3>Tu QR</h3>
            <p>Se genera al aprobar tu factura y queda listo para consulta y canje en tu zona.</p>
        </article>
        <article class="callout green">
            <h3>Zona asignada</h3>
            <p>El sistema detecta la sucursal y define la zona correcta para tu cupón.</p>
        </article>
        <article class="callout orange">
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
        registrationForm.style.display = tab === 'whatsapp' ? 'none' : 'grid';
        fieldError.style.display = 'none';
    };

    tabButtons.forEach((btn) => btn.addEventListener('click', () => setActiveTab(btn.dataset.tab)));

    registrationForm?.addEventListener('submit', (event) => {
        const value = activeTab === 'manual' ? manualInput.value.trim() : scanInput.value.trim();
        if (!value) {
            event.preventDefault();
            fieldError.textContent = activeTab === 'manual'
                ? 'Escribe el CUFE de tu factura.'
                : 'Escanea o pega el código de tu factura.';
            fieldError.style.display = 'block';
            return;
        }
        hiddenInput.value = value;
    });
})();
</script>
</body>
</html>
