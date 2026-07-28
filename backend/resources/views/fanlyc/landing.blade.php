<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Fanlyc - Super Carnes</title>
    <style>
        :root {
            --red: #b91c1c;
            --red-deep: #7f1d1d;
            --paper: #f8fafc;
            --ink: #0f172a;
            --shadow: rgba(15, 23, 42, .14);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: system-ui, -apple-system, Arial, sans-serif;
            color: var(--ink);
            background: linear-gradient(180deg, #fff 0%, var(--paper) 60%);
            min-height: 100vh;
        }
        .page { width: min(1100px, calc(100vw - 24px)); margin: 0 auto; padding: 24px 0 48px; }
        .hero {
            border-radius: 28px;
            padding: 36px;
            background: linear-gradient(135deg, var(--red), var(--red-deep));
            color: #fff;
            box-shadow: 0 24px 50px var(--shadow);
        }
        .hero h1 { margin: 0 0 8px; font-size: clamp(30px, 4vw, 46px); }
        .hero p { margin: 0; font-size: 16px; max-width: 46rem; opacity: .95; }
        .zones { margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap; }
        .zone-chip { background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.3); border-radius: 999px; padding: 8px 14px; font-weight: 700; font-size: 14px; }
        .grid { margin-top: 24px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .card { background: #fff; border-radius: 22px; padding: 26px; box-shadow: 0 16px 40px var(--shadow); }
        .card h2 { margin: 0 0 8px; font-size: 22px; }
        .card p.copy { margin: 0 0 16px; color: #475569; font-size: 14px; line-height: 1.5; }
        .steps { display: grid; gap: 12px; }
        .step { display: grid; grid-template-columns: 32px 1fr; gap: 12px; align-items: start; }
        .step-num { width: 32px; height: 32px; border-radius: 50%; background: var(--red); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; }
        .step strong { display: block; }
        .step span { color: #64748b; font-size: 14px; }
        form { display: grid; gap: 12px; margin-top: 6px; }
        label { display: grid; gap: 6px; font-size: 13px; font-weight: 700; color: #334155; }
        input { width: 100%; border: 1px solid #cbd5e1; border-radius: 12px; padding: 12px 14px; font: inherit; }
        input:focus { outline: 2px solid rgba(185, 28, 28, .18); border-color: var(--red); }
        .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .scan-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn { cursor: pointer; border: none; border-radius: 12px; padding: 10px 14px; font-weight: 700; font-size: 14px; }
        .btn-gray { background: #e2e8f0; color: #0f172a; }
        .btn-red { background: var(--red); color: #fff; padding: 14px 16px; font-size: 16px; }
        #scanner-preview { display:none; max-width: 100%; border-radius: 14px; overflow: hidden; border: 1px solid #e2e8f0; background: #0f172a; }
        video { width: 100%; display: block; }
        .check { display: flex; gap: 8px; align-items: flex-start; font-size: 13px; color: #334155; font-weight: 600; }
        .check input { width: 18px; height: 18px; margin-top: 2px; }
        .status, .error { padding: 12px 14px; border-radius: 12px; font-size: 14px; }
        .status { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .link-row { margin-top: 14px; font-size: 14px; }
        .link-row a { color: var(--red); font-weight: 700; }
        .tabs { display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap; }
        .tab-btn { cursor: pointer; border: 1px solid #cbd5e1; background: #fff; color: #334155; border-radius: 999px; padding: 8px 14px; font-weight: 700; font-size: 13px; }
        .tab-btn.is-active { background: var(--red); border-color: var(--red); color: #fff; }
        .tab-panel[hidden] { display: none; }
        .whatsapp-panel { display: grid; gap: 12px; }
        .btn-whatsapp { display: inline-flex; align-items: center; justify-content: center; text-decoration: none; background: #16a34a; color: #fff; padding: 14px 16px; border-radius: 12px; font-weight: 700; font-size: 16px; }
        .field-error { color: #991b1b; font-size: 12px; font-weight: 700; }
        @media (max-width: 900px) {
            .grid { grid-template-columns: 1fr; }
            .field-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="hero">
            <h1>Fanlyc</h1>
            <p>Registra tu factura de Super Carnes, valida el producto participante y consigue tu cupon QR para canjear por un tiket en el evento de tu zona.</p>
            <div class="zones">
                <span class="zone-chip">Azuero</span>
                <span class="zone-chip">Santiago</span>
                <span class="zone-chip">Panama</span>
            </div>
        </section>

        <section class="grid">
            <div class="card">
                <h2>Como funciona</h2>
                <p class="copy">Tu zona se define por la sucursal donde compraste. Solo puedes canjear en la zona de tu factura.</p>
                <div class="steps">
                    <div class="step">
                        <div class="step-num">1</div>
                        <div><strong>Escanea o pega tu factura</strong><span>Usamos el mismo codigo QR que trae tu factura de Super Carnes.</span></div>
                    </div>
                    <div class="step">
                        <div class="step-num">2</div>
                        <div><strong>Validamos automaticamente</strong><span>Confirmamos que sea Super Carnes, tu sucursal y el producto participante.</span></div>
                    </div>
                    <div class="step">
                        <div class="step-num">3</div>
                        <div><strong>Recibe tu cupon QR</strong><span>Puedes acumular varios cupones registrando varias facturas.</span></div>
                    </div>
                    <div class="step">
                        <div class="step-num">4</div>
                        <div><strong>Canjea en tu zona</strong><span>Lleva tu QR al evento de tu zona y cambialo por un tiket.</span></div>
                    </div>
                </div>
                <div class="link-row">
                    <p style="margin:0 0 8px;font-weight:700;">Consultar mis cupones</p>
                    <form method="GET" action="{{ route('fanlyc.status') }}" class="field-row" style="margin-top:0;">
                        <label style="margin:0;">Cedula
                            <input name="cedula" placeholder="8-123-4567" required>
                        </label>
                        <label style="margin:0;">Telefono
                            <input name="phone" placeholder="6000-0000" required>
                        </label>
                        <button class="btn btn-gray" type="submit" style="grid-column: 1 / -1;">Ver mis cupones</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <h2>Registrar factura</h2>
                <p class="copy">Elige como quieres darnos el codigo de tu factura.</p>

                <div class="tabs">
                    <button type="button" class="tab-btn is-active" data-tab="scan">Escanear QR</button>
                    <button type="button" class="tab-btn" data-tab="manual">Escribir CUFE</button>
                    <button type="button" class="tab-btn" data-tab="whatsapp">WhatsApp</button>
                </div>

                <div id="whatsapp-panel" class="whatsapp-panel" hidden>
                    <p class="copy" style="margin:0;">¿Prefieres que te ayudemos? Escribenos por WhatsApp con tus datos y la foto de tu factura, y un asesor la registrara por ti.</p>
                    <a class="btn-whatsapp" target="_blank" rel="noopener"
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
                        <label>Cedula
                            <input name="cedula" value="{{ old('cedula') }}" required>
                        </label>
                    </div>
                    <div class="field-row">
                        <label>Correo electronico
                            <input type="email" name="email" value="{{ old('email') }}" required>
                        </label>
                        <label>Telefono
                            <input name="phone" value="{{ old('phone') }}" required>
                        </label>
                    </div>

                    <input type="hidden" id="qr_raw_text" name="qr_raw_text" value="{{ old('qr_raw_text') }}">

                    <div id="scan-panel" class="tab-panel">
                        <label>Codigo de factura (QR)
                            <input id="qr_raw_text_scan" value="{{ old('qr_raw_text') }}" placeholder="Pega el contenido del QR o usa la camara" autocomplete="off">
                        </label>
                        <div class="scan-actions">
                            <button class="btn btn-gray" type="button" id="start-camera">Abrir camara</button>
                            <button class="btn btn-gray" type="button" id="stop-camera" disabled>Detener camara</button>
                        </div>
                        <div id="scanner-status" class="status" style="display:none;"></div>
                        <div id="scanner-preview">
                            <video id="scanner-video" autoplay playsinline></video>
                        </div>
                    </div>

                    <div id="manual-panel" class="tab-panel" hidden>
                        <label>Escribe el CUFE de tu factura
                            <input id="cufe_manual" placeholder="Ej: FE0120000000032812-2-249262-..." autocomplete="off">
                        </label>
                    </div>

                    <div id="form-field-error" class="field-error" style="display:none;"></div>

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
                    <button class="btn btn-red" type="submit">Registrar factura</button>
                </form>
            </div>
        </section>
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

        const panels = { scan: scanPanel, manual: manualPanel, whatsapp: whatsappPanel };

        const setActiveTab = (tab) => {
            activeTab = tab;
            tabButtons.forEach((btn) => btn.classList.toggle('is-active', btn.dataset.tab === tab));
            Object.entries(panels).forEach(([key, panel]) => {
                if (!panel) return;
                if (key === 'whatsapp') return;
                panel.hidden = key !== tab;
            });
            registrationForm.style.display = tab === 'whatsapp' ? 'none' : 'grid';
            whatsappPanel.hidden = tab !== 'whatsapp';
            fieldError.style.display = 'none';
        };

        tabButtons.forEach((btn) => btn.addEventListener('click', () => setActiveTab(btn.dataset.tab)));

        registrationForm?.addEventListener('submit', (event) => {
            const value = activeTab === 'manual' ? manualInput.value.trim() : scanInput.value.trim();

            if (!value) {
                event.preventDefault();
                fieldError.textContent = activeTab === 'manual'
                    ? 'Escribe el CUFE de tu factura.'
                    : 'Escanea o pega el codigo de tu factura.';
                fieldError.style.display = 'block';
                return;
            }

            hiddenInput.value = value;
        });

        const startButton = document.getElementById('start-camera');
        const stopButton = document.getElementById('stop-camera');
        const preview = document.getElementById('scanner-preview');
        const video = document.getElementById('scanner-video');
        const statusBox = document.getElementById('scanner-status');
        const input = scanInput;
        let stream = null;
        let detector = null;
        let active = false;
        let loopId = null;

        const setStatus = (message) => {
            statusBox.textContent = message;
            statusBox.style.display = 'block';
        };

        const stopCamera = () => {
            active = false;
            if (loopId) { cancelAnimationFrame(loopId); loopId = null; }
            if (stream) { stream.getTracks().forEach((track) => track.stop()); stream = null; }
            preview.style.display = 'none';
            stopButton.disabled = true;
            startButton.disabled = false;
        };

        const scanFrame = async () => {
            if (!active || !detector || !video.videoWidth) {
                loopId = requestAnimationFrame(scanFrame);
                return;
            }
            try {
                const codes = await detector.detect(video);
                if (codes && codes.length > 0) {
                    const raw = codes[0].rawValue || '';
                    if (raw) {
                        input.value = raw;
                        setStatus('Codigo detectado. Ya puedes registrar tu factura.');
                        stopCamera();
                        return;
                    }
                }
            } catch (error) {
                setStatus('No se pudo leer el QR en este navegador. Pega el codigo manualmente.');
                stopCamera();
                return;
            }
            loopId = requestAnimationFrame(scanFrame);
        };

        startButton?.addEventListener('click', async () => {
            if (!('BarcodeDetector' in window)) {
                setStatus('Este navegador no soporta escaneo nativo. Pega el codigo de tu factura manualmente.');
                return;
            }
            try {
                detector = detector || new BarcodeDetector({ formats: ['qr_code'] });
                stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
                video.srcObject = stream;
                preview.style.display = 'block';
                startButton.disabled = true;
                stopButton.disabled = false;
                active = true;
                setStatus('Camara activa. Apunta al QR de tu factura.');
                scanFrame();
            } catch (error) {
                setStatus('No se pudo abrir la camara. Verifica permisos del navegador.');
            }
        });

        stopButton?.addEventListener('click', stopCamera);
    })();
    </script>
</body>
</html>
