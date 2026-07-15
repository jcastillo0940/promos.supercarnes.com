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

        /* Primary fold: 50% info / 50% form */
        .hero-split {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            align-items: stretch;
        }
        .hero-info {
            position: relative;
            overflow: hidden;
            border-radius: 34px;
            padding: 32px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background:
                linear-gradient(180deg, rgba(93, 49, 12, .58), rgba(93, 49, 12, .82)),
                url('/fonda-assets/hero-cover.jpeg') center/cover no-repeat;
            box-shadow: 0 24px 60px var(--shadow);
            min-height: 560px;
        }
        .kicker {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 12px;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: var(--brown);
            background: rgba(255, 255, 255, .84);
            border-radius: 999px;
            padding: 10px 14px;
            box-shadow: 0 10px 22px rgba(0, 0, 0, .08);
            width: fit-content;
        }
        .hero-title {
            margin: 18px 0 0;
            font-family: 'Fredoka', sans-serif;
            font-size: clamp(42px, 4.6vw, 68px);
            line-height: .96;
            letter-spacing: -.03em;
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
            font-size: 17px;
            line-height: 1.55;
            color: #fff;
            max-width: 34rem;
            text-shadow: 0 4px 14px rgba(0,0,0,.35);
        }
        .hero-chiprow {
            margin-top: 20px;
            display: flex;
            gap: 10px;
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
            background: rgba(255,255,255,.9);
            color: var(--brown);
            box-shadow: 0 12px 24px rgba(0,0,0,.08);
        }
        .hero-mini-facts {
            margin-top: 26px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }
        .hero-mini-facts div {
            background: rgba(255,255,255,.14);
            border: 1px solid rgba(255,255,255,.28);
            border-radius: 18px;
            padding: 12px 14px;
        }
        .hero-mini-facts strong {
            display: block;
            font-family: 'Fredoka', sans-serif;
            font-size: 15px;
            color: var(--yellow);
        }
        .hero-mini-facts span {
            display: block;
            margin-top: 4px;
            font-size: 13px;
            line-height: 1.4;
            color: #fff;
        }

        .form-card {
            border-radius: 34px;
            padding: 28px;
            background:
                linear-gradient(180deg, rgba(255,255,255,.9), rgba(246,238,227,.98));
            box-shadow: 0 24px 60px var(--shadow);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .form-title {
            margin: 0 0 6px;
            font-family: 'Fredoka', sans-serif;
            font-size: 32px;
            line-height: 1;
            color: var(--brown);
        }
        .form-copy {
            margin: 0 0 16px;
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
        .field-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
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
            padding: 15px 18px;
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

        /* Secondary content below the fold */
        .detail-band { margin-top: 22px; }
        .section-title {
            margin: 0 0 14px;
            font-family: 'Fredoka', sans-serif;
            font-size: 32px;
            line-height: .95;
            color: var(--brown);
        }
        .step-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }
        .step {
            display: grid;
            grid-template-columns: 56px 1fr;
            gap: 12px;
            align-items: start;
            background: rgba(255,255,255,.62);
            border: 1px solid rgba(122, 68, 17, .08);
            border-radius: 24px;
            padding: 14px;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.5);
        }
        .step-num {
            font-family: 'Fredoka', sans-serif;
            font-size: 36px;
            line-height: .82;
            color: var(--yellow);
            text-shadow: 0 2px 0 var(--brown);
        }
        .step strong {
            display: block;
            font-family: 'Fredoka', sans-serif;
            font-size: 22px;
            line-height: 1;
            color: var(--brown);
        }
        .step p {
            margin: 6px 0 0;
            font-size: 14px;
            line-height: 1.5;
        }
        .footer-band {
            margin-top: 16px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }
        .paper-card {
            position: relative;
            border-radius: 28px;
            padding: 22px;
            background:
                radial-gradient(circle at top, rgba(255,255,255,.8), transparent 45%),
                linear-gradient(180deg, rgba(247,240,229,.98), rgba(236,225,206,.96));
            box-shadow: 0 16px 40px rgba(0, 0, 0, .08);
        }
        .paper-card h3 {
            margin: 0;
            font-family: 'Fredoka', sans-serif;
            font-size: 26px;
            color: var(--brown);
        }
        .paper-card p {
            margin: 8px 0 0;
            font-size: 15px;
            line-height: 1.5;
        }
        .small-note {
            margin: 10px 0 0;
            font-size: 13px;
            line-height: 1.5;
            color: #7d6550;
        }
        .photo-row {
            margin-top: 16px;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
        }
        .polaroid {
            position: relative;
            overflow: hidden;
            min-height: 160px;
            border-radius: 22px;
            background: #fff;
            box-shadow: 0 12px 26px rgba(0,0,0,.12);
        }
        .polaroid img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        @media (max-width: 1080px) {
            .hero-split, .footer-band { grid-template-columns: 1fr; }
            .step-grid { grid-template-columns: 1fr; }
            .photo-row { grid-template-columns: repeat(2, 1fr); }
            .hero-info { min-height: 420px; }
        }
        @media (max-width: 640px) {
            .page { width: min(100vw - 18px, 1280px); padding-top: 9px; }
            .hero-info, .form-card, .paper-card { padding: 20px; border-radius: 24px; }
            .field-row { grid-template-columns: 1fr; }
            .hero-mini-facts { grid-template-columns: 1fr; }
            .submit { font-size: 18px; }
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="hero-split">
            <div class="hero-info">
                <div class="kicker">Super Carnes · Fonda Challenge 2026</div>
                <h1 class="hero-title">YA LLEGÓ EL FONDA CHALLENGE</h1>
                <p class="hero-subtitle">Si tienes fonda, prepárate para competir, mostrar tu sazón y llevarte uno de los premios.</p>
                <div class="hero-chiprow">
                    <span class="chip">31 de julio de 2026</span>
                    <span class="chip">Santiago de Veraguas</span>
                    <span class="chip">Premios en efectivo</span>
                </div>
                <div class="hero-mini-facts">
                    <div>
                        <strong>Cómo participar</strong>
                        <span>Llena el formulario, el equipo revisa tu inscripción.</span>
                    </div>
                    <div>
                        <strong>Premios</strong>
                        <span>$500, $300 y $200 para los primeros 3 lugares.</span>
                    </div>
                    <div>
                        <strong>Jurado</strong>
                        <span>Degustación y evaluación el día del evento.</span>
                    </div>
                </div>
                @if ($campaign)
                    <p class="small-note" style="color:rgba(255,255,255,.85);">Campaña activa: <strong>{{ $campaign->name }}</strong></p>
                @endif
            </div>

            <section class="form-card">
                <h2 class="form-title">Inscripción</h2>
                <p class="form-copy">Completa tus datos para participar. Es gratis.</p>
                <form method="POST" action="{{ route('fonda-challenge.store') }}">
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
                    <label>Nombre de la fonda
                        <input name="fonda_name" value="{{ old('fonda_name') }}" required>
                    </label>
                    <div class="field-row">
                        <label>Ubicación de la fonda
                            <input name="fonda_location" value="{{ old('fonda_location') }}" required>
                        </label>
                        <label>Plato a presentar
                            <input name="dish_name" value="{{ old('dish_name') }}" required>
                        </label>
                    </div>
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
        </section>

        <section class="detail-band">
            <h2 class="section-title">¿Cómo funciona?</h2>
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
                        <p>Recibirás confirmación mientras el equipo valida la información.</p>
                    </div>
                </div>
                <div class="step">
                    <div class="step-num">3</div>
                    <div>
                        <strong>Participa</strong>
                        <p>El día del evento habrá check-in, evaluación y cierre.</p>
                    </div>
                </div>
            </div>

            <div class="footer-band">
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
            </div>

            <div class="photo-row">
                <div class="polaroid"><img src="/fonda-assets/step-1.jpeg" alt="Fonda Challenge" loading="lazy"></div>
                <div class="polaroid"><img src="/fonda-assets/step-2.jpeg" alt="Evaluación Fonda Challenge" loading="lazy"></div>
                <div class="polaroid"><img src="/fonda-assets/step-3.jpeg" alt="Premios Fonda Challenge" loading="lazy"></div>
                <div class="polaroid"><img src="/fonda-assets/step-4.jpeg" alt="Inscripción Fonda Challenge" loading="lazy"></div>
            </div>
        </section>
    </main>
</body>
</html>
