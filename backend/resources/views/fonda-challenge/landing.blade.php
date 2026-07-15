<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fonda Challenge 2026</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --paper: #f5efe5;
            --paper-deep: #e9dcc7;
            --paper-edge: rgba(255, 255, 255, 0.9);
            --brown: #7a4411;
            --brown-deep: #5d310c;
            --yellow: #ffd31a;
            --shadow: rgba(63, 36, 10, 0.18);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Inter', Arial, sans-serif;
            color: var(--brown-deep);
            background:
                radial-gradient(circle at top left, rgba(255,255,255,.6), transparent 35%),
                radial-gradient(circle at bottom right, rgba(255, 211, 26, .12), transparent 24%),
                linear-gradient(180deg, #fbf6ec 0%, #f5efe5 46%, #efe4d2 100%);
            min-height: 100vh;
        }
        .page {
            width: min(1280px, calc(100vw - 24px));
            margin: 0 auto;
            padding: 12px 0 28px;
        }
        .masthead {
            display: grid;
            grid-template-columns: 1.25fr .75fr;
            gap: 14px;
            min-height: 680px;
        }
        .paper {
            position: relative;
            overflow: hidden;
            border-radius: 34px;
            background:
                linear-gradient(180deg, rgba(255,255,255,.72), rgba(255,255,255,.38)),
                url('/fonda-challenge/hero-cover.jpeg') center/cover no-repeat;
            box-shadow: 0 24px 60px var(--shadow);
        }
        .paper::before,
        .paper::after {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
        }
        .paper::before {
            background:
                radial-gradient(circle at 12% 10%, rgba(255,255,255,.22), transparent 18%),
                radial-gradient(circle at 70% 12%, rgba(255,255,255,.18), transparent 14%),
                linear-gradient(115deg, rgba(255,255,255,.0) 0 26%, rgba(255,255,255,.9) 26.7% 28.8%, transparent 29.2% 100%);
            opacity: .9;
        }
        .hero-copy {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: flex-end;
            justify-content: flex-start;
            padding: 28px;
            background:
                linear-gradient(90deg, rgba(245,239,229,.12) 0%, rgba(245,239,229,.04) 45%, rgba(245,239,229,.4) 100%);
        }
        .hero-copy-inner {
            width: min(540px, 100%);
            margin-left: auto;
            text-align: right;
        }
        .kicker {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 12px;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: var(--brown);
            background: rgba(255, 255, 255, .72);
            border-radius: 999px;
            padding: 10px 14px;
            box-shadow: 0 10px 22px rgba(0, 0, 0, .08);
        }
        .hero-title {
            margin: 18px 0 0;
            font-family: 'Fredoka', sans-serif;
            font-size: clamp(56px, 7vw, 104px);
            line-height: .9;
            letter-spacing: -.04em;
            color: var(--yellow);
            text-shadow:
                -2px -2px 0 var(--brown-deep),
                2px -2px 0 var(--brown-deep),
                -2px 2px 0 var(--brown-deep),
                2px 2px 0 var(--brown-deep),
                0 10px 32px rgba(0,0,0,.24);
        }
        .hero-subtitle {
            margin: 14px 0 0;
            font-family: 'Fredoka', sans-serif;
            font-size: clamp(22px, 2.7vw, 38px);
            line-height: 1.02;
            color: #fff;
            text-shadow: 0 6px 24px rgba(0,0,0,.35);
        }
        .hero-chiprow {
            margin-top: 18px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }
        .chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 999px;
            padding: 10px 14px;
            font-size: 14px;
            font-weight: 800;
            background: rgba(255,255,255,.82);
            color: var(--brown);
            box-shadow: 0 12px 24px rgba(0,0,0,.08);
        }
        .panel {
            display: grid;
            gap: 14px;
        }
        .paper-card {
            position: relative;
            border-radius: 32px;
            padding: 24px;
            background:
                radial-gradient(circle at top, rgba(255,255,255,.8), transparent 45%),
                linear-gradient(180deg, rgba(247,240,229,.98), rgba(236,225,206,.96));
            box-shadow: 0 18px 44px rgba(0, 0, 0, .10);
            overflow: hidden;
        }
        .paper-card::after {
            content: '';
            position: absolute;
            inset: auto -10px -14px auto;
            width: 72px;
            height: 72px;
            background: rgba(255, 211, 26, .16);
            transform: rotate(35deg);
            border-radius: 10px;
        }
        .story {
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 220px;
        }
        .story h2 {
            margin: 0;
            font-family: 'Fredoka', sans-serif;
            font-size: clamp(36px, 3.7vw, 62px);
            line-height: .96;
            color: var(--brown);
        }
        .story p {
            margin: 14px 0 0;
            font-size: 17px;
            line-height: 1.6;
            max-width: 32rem;
        }
        .step-grid {
            display: grid;
            gap: 12px;
        }
        .step {
            display: grid;
            grid-template-columns: 72px 1fr;
            gap: 14px;
            align-items: start;
            background: rgba(255,255,255,.54);
            border: 1px solid rgba(122, 68, 17, .08);
            border-radius: 26px;
            padding: 14px;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.5);
        }
        .step-num {
            font-family: 'Fredoka', sans-serif;
            font-size: 48px;
            line-height: .82;
            color: var(--yellow);
            text-shadow: 0 2px 0 var(--brown);
        }
        .step strong {
            display: block;
            font-family: 'Fredoka', sans-serif;
            font-size: 28px;
            line-height: 1;
            color: var(--brown);
        }
        .step p {
            margin: 6px 0 0;
            font-size: 15px;
            line-height: 1.5;
        }
        .photo-row {
            display: grid;
            grid-template-columns: 1.1fr .9fr;
            gap: 14px;
        }
        .polaroid {
            position: relative;
            overflow: hidden;
            min-height: 250px;
            border-radius: 26px;
            background: #fff;
            box-shadow: 0 16px 32px rgba(0,0,0,.14);
            transform: rotate(-2deg);
        }
        .polaroid.right { transform: rotate(2deg); }
        .polaroid img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .polaroid::after {
            content: '';
            position: absolute;
            inset: 10px;
            border: 7px solid rgba(255,255,255,.95);
            border-radius: 18px;
            pointer-events: none;
            mix-blend-mode: screen;
        }
        .form-shell {
            margin-top: 14px;
            display: grid;
            gap: 16px;
            grid-template-columns: 1.05fr .95fr;
        }
        .form-card,
        .info-card {
            border-radius: 30px;
            padding: 24px;
            background:
                linear-gradient(180deg, rgba(255,255,255,.84), rgba(246,238,227,.96));
            box-shadow: 0 18px 42px rgba(0,0,0,.10);
        }
        .form-title,
        .info-title {
            margin: 0 0 10px;
            font-family: 'Fredoka', sans-serif;
            font-size: 30px;
            line-height: 1;
            color: var(--brown);
        }
        .form-copy,
        .info-copy {
            margin: 0 0 14px;
            font-size: 15px;
            line-height: 1.55;
        }
        form { display: grid; gap: 12px; }
        label { display: grid; gap: 7px; font-size: 14px; font-weight: 800; color: var(--brown); }
        input, textarea {
            width: 100%;
            border: 1px solid rgba(122,68,17,.16);
            border-radius: 18px;
            background: rgba(255,255,255,.92);
            padding: 13px 14px;
            font: inherit;
            color: #332016;
            outline: none;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.7);
        }
        textarea { min-height: 120px; resize: vertical; }
        input:focus, textarea:focus {
            border-color: rgba(255, 211, 26, .9);
            box-shadow: 0 0 0 4px rgba(255, 211, 26, .16);
        }
        .check {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            padding: 12px 0 0;
            font-size: 14px;
            font-weight: 600;
            line-height: 1.45;
            color: #5d3a18;
        }
        .check input { width: 18px; height: 18px; margin-top: 2px; accent-color: var(--yellow); }
        .submit {
            cursor: pointer;
            border: 0;
            border-radius: 18px;
            padding: 14px 18px;
            background: linear-gradient(180deg, #ffd31a, #eab80a);
            color: #3a240d;
            font-family: 'Fredoka', sans-serif;
            font-size: 22px;
            font-weight: 700;
            box-shadow: 0 16px 24px rgba(234, 184, 10, .28);
        }
        .submit:hover { filter: brightness(1.02); transform: translateY(-1px); }
        .status {
            margin-top: 4px;
            padding: 12px 14px;
            border-radius: 16px;
            background: rgba(255,255,255,.9);
            border: 1px solid rgba(122,68,17,.12);
            color: #7a4411;
        }
        .alert {
            background: rgba(255, 239, 239, .98);
            color: #8e1d1d;
        }
        .section-title {
            margin: 0 0 10px;
            font-family: 'Fredoka', sans-serif;
            font-size: 36px;
            line-height: .95;
            color: var(--brown);
        }
        .small-note {
            margin: 10px 0 0;
            font-size: 13px;
            line-height: 1.5;
            color: #7d6550;
        }
        .footer-band {
            margin-top: 14px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }
        .footer-band .paper-card {
            min-height: 170px;
        }
        .footer-band h3 {
            margin: 0;
            font-family: 'Fredoka', sans-serif;
            font-size: 28px;
            color: var(--brown);
        }
        .footer-band p {
            margin: 8px 0 0;
            font-size: 15px;
            line-height: 1.5;
        }
        @media (max-width: 1080px) {
            .masthead,
            .form-shell,
            .footer-band,
            .photo-row {
                grid-template-columns: 1fr;
            }
            .hero-copy { align-items: flex-start; }
            .hero-copy-inner { margin-left: 0; text-align: left; }
            .hero-chiprow { justify-content: flex-start; }
        }
        @media (max-width: 720px) {
            .page { width: min(100vw - 18px, 1280px); padding-top: 9px; }
            .paper-card, .form-card, .info-card { padding: 18px; border-radius: 24px; }
            .masthead { min-height: 560px; }
            .hero-copy { padding: 18px; }
            .story h2 { font-size: 34px; }
            .section-title { font-size: 30px; }
            .step { grid-template-columns: 54px 1fr; }
            .step-num { font-size: 38px; }
            .submit { font-size: 18px; }
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="masthead">
            <div class="paper">
                <div class="hero-copy">
                    <div class="hero-copy-inner">
                        <div class="kicker">Super Carnes · Fonda Challenge 2026</div>
                        <h1 class="hero-title">YA LLEGÓ EL FONDA CHALLENGE</h1>
                        <p class="hero-subtitle">Si tienes fonda, prepárate para competir, mostrar tu sazón y llevarte uno de los premios.</p>
                        <div class="hero-chiprow">
                            <span class="chip">31 de julio de 2026</span>
                            <span class="chip">Santiago de Veraguas</span>
                            <span class="chip">Premios en efectivo</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel">
                <section class="paper-card story">
                    <h2>¿Cómo participar?</h2>
                    <p>Inscríbete gratis en esta sección, llena los datos básicos y recibe las instrucciones por correo. El equipo revisará cada inscripción antes del evento.</p>
                    @if ($campaign)
                        <p class="small-note">Campaña activa: <strong>{{ $campaign->name }}</strong></p>
                    @endif
                </section>

                <section class="paper-card">
                    <div class="photo-row">
                        <div class="polaroid">
                            <img src="/fonda-challenge/step-1.jpeg" alt="Fonda Challenge" loading="lazy">
                        </div>
                        <div class="polaroid right">
                            <img src="/fonda-challenge/step-2.jpeg" alt="Evaluación Fonda Challenge" loading="lazy">
                        </div>
                    </div>
                </section>
            </div>
        </section>

        <section class="footer-band">
            <article class="paper-card">
                <h3>Premios</h3>
                <p><strong>1er lugar:</strong> $500.00</p>
                <p><strong>2do lugar:</strong> $300.00</p>
                <p><strong>3er lugar:</strong> $200.00</p>
            </article>
            <article class="paper-card">
                <h3>Jurado y evaluación</h3>
                <p>Un grupo de jueces seleccionados degustará y evaluará el mejor plato de cada fonda.</p>
                <p class="small-note">Los resultados se congelan y publican desde el panel admin cuando el evento cierre.</p>
            </article>
            <article class="paper-card">
                <h3>Kit fonda</h3>
                <p>Inscríbete y recibe orientación para participar con los mejores productos de Super Carnes.</p>
                <p class="small-note">Si necesitas ayuda, el equipo confirmará tu inscripción por correo.</p>
            </article>
        </section>

        <section class="form-shell">
            <section class="form-card">
                <h2 class="form-title">Inscripción</h2>
                <p class="form-copy">Completa los campos obligatorios para registrar tu fonda.</p>
                <form method="POST" action="{{ route('fonda-challenge.store') }}">
                    @csrf
                    <label>Nombre completo
                        <input name="full_name" value="{{ old('full_name') }}" required>
                    </label>
                    <label>Cédula
                        <input name="cedula" value="{{ old('cedula') }}" required>
                    </label>
                    <label>Correo electrónico
                        <input type="email" name="email" value="{{ old('email') }}" required>
                    </label>
                    <label>Teléfono
                        <input name="phone" value="{{ old('phone') }}" required>
                    </label>
                    <label>Nombre de la fonda
                        <input name="fonda_name" value="{{ old('fonda_name') }}" required>
                    </label>
                    <label>Ubicación de la fonda
                        <input name="fonda_location" value="{{ old('fonda_location') }}" required>
                    </label>
                    <label>Plato a presentar
                        <input name="dish_name" value="{{ old('dish_name') }}" required>
                    </label>
                    <label class="check">
                        <input type="checkbox" name="consent_terms" value="1" required>
                        <span>Acepto los términos y autorizo el uso de mi imagen para fines promocionales de Super Carnes.</span>
                    </label>
                    @if ($errors->any())
                        <div class="status alert">{{ $errors->first() }}</div>
                    @endif
                    @if (session('status'))
                        <div class="status">{{ session('status') }}</div>
                    @endif
                    <button class="submit" type="submit">Enviar inscripción</button>
                </form>
            </section>

            <aside class="info-card">
                <h2 class="info-title">Pasos</h2>
                <div class="step-grid">
                    <div class="step">
                        <div class="step-num">1</div>
                        <div>
                            <strong>Regístrate</strong>
                            <p>Llena el formulario con los datos de la fonda y su responsable.</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-num">2</div>
                        <div>
                            <strong>Espera revisión</strong>
                            <p>Recibirás un correo de confirmación mientras el equipo valida la información.</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-num">3</div>
                        <div>
                            <strong>Participa</strong>
                            <p>El día del evento habrá check-in, evaluación y cierre desde el módulo interno.</p>
                        </div>
                    </div>
                </div>
                <div class="photo-row" style="margin-top:14px;">
                    <div class="polaroid">
                        <img src="/fonda-challenge/step-3.jpeg" alt="Premios Fonda Challenge" loading="lazy">
                    </div>
                    <div class="polaroid right">
                        <img src="/fonda-challenge/step-4.jpeg" alt="Inscripción Fonda Challenge" loading="lazy">
                    </div>
                </div>
            </aside>
        </section>
    </main>
</body>
</html>
