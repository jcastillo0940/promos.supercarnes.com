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
            --sky-deep:#1fa0d7;
            --green:#30b232;
            --orange:#ff7a2f;
            --purple:#8c57ff;
            --yellow:#ffca16;
            --red:#ff5e52;
            --ink:#16324f;
            --brown:#7a4411;
            --paper:#f8f6ef;
            --shadow: rgba(18, 58, 86, .16);
        }
        *{box-sizing:border-box}
        body{
            margin:0;
            font-family:Inter, Arial, sans-serif;
            color:var(--ink);
            background:
                radial-gradient(circle at 8% 12%, rgba(255,255,255,.35), transparent 18%),
                radial-gradient(circle at 88% 8%, rgba(255,255,255,.25), transparent 14%),
                linear-gradient(180deg, #59c2eb 0%, var(--sky) 60%, #42b4e2 100%);
            min-height:100vh;
        }
        .wrap{width:min(1180px, calc(100vw - 20px)); margin:0 auto; padding:12px 0 28px;}
        .hero{
            position:relative;
            overflow:hidden;
            border-radius:34px;
            background:
                radial-gradient(circle at 15% 18%, rgba(255,255,255,.18), transparent 18%),
                radial-gradient(circle at 86% 20%, rgba(255,255,255,.18), transparent 16%),
                linear-gradient(180deg, rgba(255,255,255,.08), rgba(255,255,255,.02));
            box-shadow:0 20px 50px var(--shadow);
            padding:28px 24px 24px;
        }
        .hero::before,.hero::after{
            content:'';
            position:absolute;
            inset:auto;
            width:220px;height:220px;
            border-radius:50%;
            border:18px solid rgba(255,196,0,.75);
            opacity:.6;
            pointer-events:none;
        }
        .hero::before{left:-130px; top:120px; border-color:rgba(255,130,43,.75); transform:rotate(22deg)}
        .hero::after{right:-120px; bottom:-90px; border-color:rgba(45,145,188,.55)}
        .brand{
            display:flex; justify-content:center; margin-bottom:10px;
        }
        .brand img{
            width:min(210px, 56vw);
            display:block;
            filter: drop-shadow(0 10px 16px rgba(0,0,0,.12));
        }
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
            color:#ff2c5c;
            background:linear-gradient(90deg,#ff2c5c 0%, #ff7d2f 24%, #ffcc17 48%, #35d64a 72%, #63d4ff 100%);
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
        .grid{
            margin-top:14px;
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:14px;
        }
        .card{
            position:relative;
            overflow:hidden;
            border-radius:30px;
            background:rgba(255,255,255,.9);
            box-shadow:0 18px 38px var(--shadow);
            padding:22px;
        }
        .card::after{
            content:'';
            position:absolute;
            inset:auto -30px -30px auto;
            width:160px;height:160px;
            background:rgba(255,255,255,.12);
            border-radius:50%;
            pointer-events:none;
        }
        .card h2{
            margin:0 0 8px;
            font-size:28px;
            line-height:1;
            color:var(--brown);
        }
        .card p.copy{margin:0 0 14px; color:#35516e; line-height:1.55}
        .steps{display:grid; gap:10px}
        .step{
            display:grid;
            grid-template-columns:38px 1fr;
            gap:10px;
            padding:12px 14px;
            border-radius:22px;
            background:linear-gradient(90deg, rgba(69,185,230,.10), rgba(255,255,255,.68));
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
        .photo-grid{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:12px;
        }
        .photo{
            min-height:190px;
            border-radius:24px;
            overflow:hidden;
            position:relative;
            box-shadow:0 14px 24px rgba(0,0,0,.12);
            border:6px solid rgba(255,255,255,.8);
            transform:rotate(-1.5deg);
        }
        .photo:nth-child(2){transform:rotate(2deg)}
        .photo img{width:100%; height:100%; object-fit:cover; display:block}
        .photo.badge::before{
            content:'Fanlyc';
            position:absolute; left:14px; top:14px;
            background:#fff; color:#ff5e52;
            font-weight:900; letter-spacing:.06em;
            padding:8px 12px; border-radius:999px;
            box-shadow:0 10px 18px rgba(0,0,0,.12);
            z-index:1;
        }
        .form-card{margin-top:14px}
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
        .tabs{display:flex; gap:10px; flex-wrap:wrap; margin-bottom:14px}
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
        .status,.error{
            padding:12px 14px;
            border-radius:16px;
            font-size:14px;
            line-height:1.45;
        }
        .status{background:#e7fff3; color:#0f6b47; border:1px solid #b9f2d2}
        .error{background:#fff0f0; color:#b42318; border:1px solid #ffc7c7}
        .callouts{
            margin-top:14px;
            display:grid;
            grid-template-columns:repeat(3,1fr);
            gap:14px;
        }
        .callout{
            min-height:155px;
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
        .rule{
            margin-top:14px;
            padding:14px 16px;
            border-radius:20px;
            background:rgba(255,255,255,.78);
            color:#23486b;
            font-size:14px;
            line-height:1.55;
        }
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
            .grid,.callouts,.field-row,.photo-grid{grid-template-columns:1fr}
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
                <span class="chip purple">Puntos de apoyo</span>
            </div>
        </div>
    </section>

    <section class="grid">
        <article class="card">
            <h2>Descubre cómo apoyar</h2>
            <p class="copy">Usamos una composición simple, colorida y muy visual, inspirada en las piezas de redes: bloques grandes, tipografía amable y colores intensos.</p>
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

        <article class="card form-card">
            <h2>Registrar factura</h2>
            <p class="copy">Elige como quieres darnos el código de tu factura.</p>

            <div class="tabs">
                <button type="button" class="tab-btn is-active" data-tab="scan">Escanear QR</button>
                <button type="button" class="tab-btn" data-tab="manual">Escribir CUFE</button>
                <button type="button" class="tab-btn" data-tab="whatsapp">WhatsApp</button>
            </div>

            <div id="whatsapp-panel" class="tab-panel" hidden>
                <div class="rule" style="margin-top:0;">
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
                    <label>Codigo de factura (QR)
                        <input id="qr_raw_text_scan" value="{{ old('qr_raw_text') }}" placeholder="Pega el contenido del QR o usa la cámara" autocomplete="off">
                    </label>
                    <div class="rule">
                        Usa la cámara si deseas, o pega el código QR directamente. Esta vista ya está pensada para móvil.
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

    <section class="grid" style="margin-top:14px;">
        <article class="card">
            <h2>Fanlyc en 4 pasos</h2>
            <div class="photo-grid">
                <div class="photo badge"><img src="/fonda-assets/step-1.jpeg" alt="Paso 1 Fanlyc" loading="lazy"></div>
                <div class="photo badge"><img src="/fonda-assets/step-2.jpeg" alt="Paso 2 Fanlyc" loading="lazy"></div>
                <div class="photo badge"><img src="/fonda-assets/step-3.jpeg" alt="Paso 3 Fanlyc" loading="lazy"></div>
                <div class="photo badge"><img src="/fonda-assets/step-4.jpeg" alt="Paso 4 Fanlyc" loading="lazy"></div>
            </div>
        </article>

        <article class="card">
            <h2>Importante</h2>
            <div class="rule">
                Fanlyc usa una línea visual alegre y muy legible para redes, pero aquí la llevamos a una experiencia web clara y rápida. El objetivo es que el registro se sienta igual de cercano, solo que más funcional.
            </div>
            @if ($campaign)
                <div class="rule" style="margin-top:12px;">
                    Campaña activa: <strong>{{ $campaign->name }}</strong>
                </div>
            @endif
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
