<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Fanlyc - Super Carnes</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        html{scroll-behavior:smooth}
        body{
            background:#1361df;
            min-height:100vh;
            font-family:'Poppins',sans-serif;
        }
        .page{
            width:100%;
            min-height:100vh;
            position:relative;
            overflow:hidden;
            background:linear-gradient(155deg,#1a72ef 0%,#1361df 55%,#0d54cc 100%);
        }
        .squiggle{
            position:absolute;
            width:220px;
            height:220px;
            opacity:.96;
            pointer-events:none;
        }
        .squiggle.orange.tl{top:-10px;left:-10px}
        .squiggle.green.bl{bottom:-20px;left:-10px;width:220px;height:260px}
        .squiggle.orange.br{bottom:-30px;right:-10px;width:260px;height:260px}
        .dots{
            position:absolute;
            right:100px;
            bottom:10px;
            width:150px;
            height:150px;
            opacity:.5;
            pointer-events:none;
        }
        .layout{
            width:1536px;
            height:1024px;
            position:relative;
            margin:0 auto;
            overflow:hidden;
        }
        .topbar{
            position:absolute;
            left:0;
            right:0;
            top:18px;
            height:118px;
            margin:0 150px;
            border-radius:28px;
            background:#f7fbff;
            box-shadow:0 12px 28px rgba(0,0,0,.10);
            display:flex;
            align-items:center;
            justify-content:space-between;
            padding:0 26px 0 14px;
            z-index:5;
        }
        .brand{
            display:flex;
            align-items:center;
            gap:18px;
            min-width:0;
        }
        .brand img{
            width:92px;
            display:block;
        }
        .brand .divider{
            width:1px;
            height:58px;
            background:rgba(23,62,131,.22);
        }
        .brand .text{
            color:#1b56aa;
            font-weight:800;
            font-size:28px;
            white-space:nowrap;
        }
        .nav{
            display:flex;
            gap:34px;
            flex-wrap:wrap;
        }
        .nav a{
            color:#1b56aa;
            text-decoration:none;
            font-weight:800;
            font-size:15px;
        }
        .left{
            position:absolute;
            top:150px;
            left:150px;
            width:650px;
            height:774px;
            border-radius:24px;
            background:#1698da;
            overflow:hidden;
            box-shadow:0 14px 30px rgba(0,0,0,.10);
        }
        .left-inner{
            position:relative;
            width:100%;
            height:100%;
            padding:28px 30px 22px 26px;
        }
        .left .star{
            position:absolute;
            right:20px;
            top:22px;
            width:120px;
            height:120px;
            z-index:1;
        }
        .left .boy{
            position:absolute;
            right:22px;
            top:184px;
            width:278px;
            z-index:2;
            filter:drop-shadow(0 20px 18px rgba(0,0,0,.14));
        }
        .left .boy img,
        .left .star img,
        .left .squiggle img{
            width:100%;
            height:auto;
            display:block;
        }
        .left .boy img{object-fit:contain}
        .left .top{
            position:relative;
            z-index:3;
        }
        .left .logo-row{
            display:flex;
            flex-direction:column;
            align-items:flex-start;
            gap:14px;
        }
        .left .logo-row img{
            width:220px;
            height:auto;
        }
        .left .lockup{
            display:flex;
            align-items:center;
            gap:14px;
            margin-top:14px;
            font-weight:800;
            color:#fff;
            font-size:26px;
        }
        .left .lockup .fan{
            color:#ffc629;
        }
        .title-image{
            margin-top:22px;
            width:min(100%, 470px);
            display:block;
        }
        .title-image img{
            width:100%;
            height:auto;
            display:block;
        }
        .desc{
            margin-top:22px;
            max-width:392px;
            color:#eaf2ff;
            font-size:19px;
            line-height:1.5;
            font-weight:500;
        }
        .cta{
            margin-top:24px;
            color:#fff;
            font-family:'Archivo Black',sans-serif;
            font-size:56px;
            line-height:1;
            letter-spacing:.5px;
            position:relative;
            z-index:3;
        }
        .steps{
            position:absolute;
            left:24px;
            right:24px;
            bottom:94px;
            display:flex;
            justify-content:space-between;
            gap:10px;
            background:#0b2a63;
            border-radius:22px;
            padding:20px 24px;
            z-index:3;
        }
        .step{
            width:150px;
            display:flex;
            flex-direction:column;
            align-items:center;
            gap:8px;
            color:#fff;
            text-align:center;
        }
        .step .num{
            width:28px;height:28px;border-radius:50%;
            background:#ffc629;
            display:grid;place-items:center;
            font-weight:800;
            font-size:14px;
            color:#fff;
        }
        .step .icon{
            width:52px;height:52px;border-radius:50%;
            background:#fff;
            display:grid;place-items:center;
            box-shadow:0 8px 18px rgba(0,0,0,.12);
        }
        .step .label{
            text-transform:uppercase;
            font-weight:800;
            font-size:14px;
            line-height:1.25;
        }
        .step .sub{
            font-size:13px;
            font-weight:500;
            color:#bcd0f5;
            line-height:1.2;
        }
        .chips{
            position:absolute;
            left:22px;
            bottom:22px;
            display:flex;
            gap:8px;
            z-index:3;
        }
        .chip{
            padding:8px 14px;
            border-radius:999px;
            font-weight:700;
            font-size:13px;
            color:#183778;
            white-space:nowrap;
        }
        .chip.yellow{background:#ffc629}
        .chip.green{background:#6dc24b}
        .chip.orange{background:#ff8a3d}
        .chip.purple{background:#8d59e8}

        .right{
            position:absolute;
            top:76px;
            right:56px;
            width:610px;
            height:872px;
            background:#0c2a63;
            border-radius:26px;
            padding:40px 44px;
            display:flex;
            flex-direction:column;
            box-shadow:0 14px 30px rgba(0,0,0,.10);
        }
        .right .head{
            display:flex;
            align-items:center;
            justify-content:center;
            gap:14px;
        }
        .right .head h2{
            font-family:'Archivo Black',sans-serif;
            color:#fff;
            font-size:34px;
            letter-spacing:.5px;
        }
        .right .sub{
            color:#dbe6fb;
            text-align:center;
            font-size:16px;
            margin:10px 0 30px;
            font-weight:500;
            line-height:1.4;
        }
        .options{
            display:flex;
            flex-direction:column;
            gap:20px;
        }
        .option{
            background:#fff;
            width:100%;
            border:0;
            border-radius:18px;
            padding:20px 22px;
            display:flex;
            align-items:center;
            gap:20px;
            text-align:left;
            cursor:pointer;
            transition:transform .18s ease, box-shadow .18s ease;
        }
        .option:hover{transform:translateY(-2px);box-shadow:0 12px 22px rgba(3,22,68,.2)}
        .option:focus-visible{outline:3px solid #ffc629;outline-offset:3px}
        .option.is-active{box-shadow:0 0 0 3px rgba(255,198,41,.95),0 12px 22px rgba(3,22,68,.2)}
        }
        .option .bubble{
            width:68px;height:68px;min-width:68px;border-radius:50%;
            display:grid;place-items:center;
        }
        .option .content{
            flex:1;
        }
        .option .title{
            font-weight:800;
            font-size:19px;
            text-transform:uppercase;
            letter-spacing:.3px;
            line-height:1.1;
            font-family:'Poppins',sans-serif;
            margin:0;
        }
        .option .desc{
            margin-top:4px;
            color:#425169;
            font-size:14.5px;
            line-height:1.4;
            font-weight:500;
            max-width:260px;
        }
        .option .arrow{
            color:currentColor;
            font-size:26px;
            font-weight:700;
        }
        .option.purple .title,.option.purple .arrow{color:#6d3fd6}
        .option.green .title,.option.green .arrow{color:#1ea34d}
        .option.blue .title,.option.blue .arrow{color:#1a72ef}
        .option.purple .bubble{background:#e7defc}
        .option.green .bubble{background:#d9f4e3}
        .option.blue .bubble{background:#dcebfc}
        .footer-lockup{
            margin-top:auto;
            display:flex;
            flex-direction:column;
            align-items:center;
            gap:6px;
        }
        .footer-lockup .mini{
            width:320px;
            max-width:100%;
            display:flex;
            justify-content:center;
        }
        .footer-lockup .mini img{
            width:100%;
            height:auto;
            display:block;
        }
        .footer-lockup .claim{
            color:#fff;
            font-size:15px;
            font-weight:600;
        }
        .footer-lockup .claim span{color:#ffc629}

        .form{
            display:none;
        }
        .scan-helper{
            display:flex;
            gap:8px;
            align-items:flex-start;
            color:rgba(255,255,255,.85);
            font-size:13px;
            line-height:1.45;
            margin-top:8px;
        }
        .scan-helper strong{color:#ffc629}
        .scan-retry{
            border:0;
            border-bottom:1px solid #ffc629;
            padding:0;
            background:transparent;
            color:#ffc629;
            font:inherit;
            font-weight:800;
            cursor:pointer;
        }
        .scanner-modal{
            position:fixed;
            inset:0;
            z-index:30;
            display:grid;
            place-items:center;
            padding:20px;
            background:rgba(4,19,57,.82);
        }
        .scanner-modal[hidden]{display:none}
        .scanner-dialog{
            width:min(100%,480px);
            border-radius:24px;
            padding:22px;
            background:#fff;
            box-shadow:0 24px 70px rgba(0,0,0,.35);
        }
        .scanner-dialog header{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:14px}
        .scanner-dialog h3{color:#0c2a63;font-size:22px;line-height:1.1}
        .scanner-close{
            width:38px;
            height:38px;
            border:0;
            border-radius:50%;
            background:#edf3ff;
            color:#0c2a63;
            font-size:24px;
            line-height:1;
            cursor:pointer;
        }
        .scanner-video-wrap{overflow:hidden;border-radius:18px;background:#081d47;aspect-ratio:3 / 4}
        .scanner-video,.scanner-video video{width:100%;height:100%;display:block;object-fit:cover}
        .scanner-status{margin:14px 0 0;color:#314c78;font-size:14px;line-height:1.45}

        @media (max-width: 1600px){
            .layout{
                transform:scale(.92);
                transform-origin:top center;
                height:940px;
            }
        }
        @media (max-width: 1400px){
            .layout{
                transform:scale(.84);
                height:860px;
            }
        }
        @media (max-width: 1200px){
            .layout{
                transform:none;
                width:100%;
                height:auto;
                min-height:100vh;
            }
            .topbar,.left,.right{position:relative;left:auto;top:auto;right:auto}
            .topbar,.left,.right{margin:14px auto 0}
            .topbar{width:calc(100vw - 24px)}
            .left,.right{width:calc(100vw - 24px)}
            .left{height:auto;min-height:0;padding-bottom:170px}
            .right{height:auto;padding-bottom:30px}
            .steps{position:relative;left:auto;right:auto;bottom:auto;margin-top:24px;flex-wrap:wrap;justify-content:center}
            .chips{position:relative;left:auto;bottom:auto;margin:16px 0 0}
            .left .boy{position:absolute;right:16px;top:210px;width:min(38vw,280px)}
        }
        @media (max-width: 760px){
            .topbar{
                height:auto;
                padding:14px;
                flex-direction:column;
                gap:10px;
                align-items:center;
            }
            .brand{
                gap:10px;
                flex-wrap:wrap;
                justify-content:center;
            }
            .brand .divider{display:none}
            .brand .text{font-size:18px;text-align:center}
            .nav{justify-content:center;gap:14px}
            .left{padding:18px 16px 24px}
            .left .logo-row img{width:168px}
            .title-image{width:min(100%, 330px)}
            .desc{font-size:16px;max-width:100%}
            .cta{font-size:28px}
            .left .boy{
                position:relative;
                top:auto;right:auto;
                width:60%;
                margin:14px auto -10px;
            }
            .steps{padding:16px;gap:12px}
            .step{width:45%;min-width:120px}
            .right{padding:22px 16px}
            .right .head h2{font-size:24px;text-align:center}
            .right .sub{font-size:14px}
            .option{padding:16px;gap:14px}
            .option .title{font-size:15px}
            .option .desc{font-size:13px;max-width:none}
            .option .bubble{width:54px;height:54px;min-width:54px}
            .footer-lockup .mini{width:240px}
            .scanner-dialog{padding:18px;border-radius:20px}
        }
    </style>
</head>
<body>
<div class="page">
    <svg class="squiggle orange tl" viewBox="0 0 220 220" fill="none" aria-hidden="true">
        <path d="M10 40 C 60 10, 60 90, 20 100 C -20 110, -10 190, 60 190" stroke="#ff8a3d" stroke-width="16" stroke-linecap="round"></path>
    </svg>
    <svg class="squiggle green bl" viewBox="0 0 220 260" fill="none" aria-hidden="true">
        <path d="M10 250 C 60 220, 40 170, 90 150 C 140 130, 120 70, 60 30" stroke="#6dc24b" stroke-width="16" stroke-linecap="round"></path>
    </svg>
    <svg class="squiggle orange br" viewBox="0 0 260 260" fill="none" aria-hidden="true">
        <path d="M10 60 C 80 30, 90 120, 160 130 C 230 140, 220 220, 260 250" stroke="#ff8a3d" stroke-width="16" stroke-linecap="round"></path>
    </svg>
    <svg class="dots" viewBox="0 0 100 100" aria-hidden="true">
        <circle cx="10" cy="10" r="3" fill="#ffc629"></circle><circle cx="30" cy="10" r="3" fill="#ffc629"></circle><circle cx="50" cy="10" r="3" fill="#ffc629"></circle>
        <circle cx="10" cy="30" r="3" fill="#ffc629"></circle><circle cx="30" cy="30" r="3" fill="#ffc629"></circle><circle cx="50" cy="30" r="3" fill="#ffc629"></circle>
        <circle cx="10" cy="50" r="3" fill="#ffc629"></circle><circle cx="30" cy="50" r="3" fill="#ffc629"></circle><circle cx="50" cy="50" r="3" fill="#ffc629"></circle>
    </svg>

    <div class="layout">
        <header class="topbar">
            <div class="brand">
                <img src="/fanlyc-assets/fanlyc-supercarnes-mark-crop.png" alt="Super Carnes">
                <div class="divider" aria-hidden="true"></div>
                <img src="/fanlyc-assets/fanlyc-fanlyc-relevo-mark-crop.png" alt="Fanlyc Relevo por la vida" style="width:min(100%,260px);height:auto;">
            </div>
            <nav class="nav" aria-label="Secciones">
                <a href="#inicio">Inicio</a>
                <a href="#premios">Premios</a>
                <a href="#zona">Zona asignada</a>
                <a href="#apoyo">Apoyo comunitario</a>
            </nav>
        </header>

        <section id="inicio" class="left" aria-label="Portada Fanlyc">
            <div class="left-inner">
                <div class="top">
                    <div class="logo-row">
                        <img src="/fanlyc-assets/fanlyc-supercarnes-mark-crop.png" alt="Super Carnes">
                    </div>
                    <img class="title-image" src="/fanlyc-assets/fanlyc-fanlyc-relevo-mark-crop.png" alt="Fanlyc Relevo por la vida">
                </div>

                <div class="star">
                    <img src="/fanlyc-assets/fanlyc-star-yellow.png" alt="" aria-hidden="true">
                </div>
                <div class="boy">
                    <img src="/fanlyc-assets/fanlyc-boy-hero-crop.png" alt="Niño celebrando Fanlyc">
                </div>

                <div class="title-image">
                    <img src="/fanlyc-assets/fanlyc-grandes-guerros-crop.png" alt="Grandes Guerreros">
                </div>

                <p class="desc">
                    Registra tu factura de Super Carnes, valida tu cupón y canjéalo en la zona que te corresponde.
                    El proceso es simple: escanea, completa tus datos y recibe tu QR.
                </p>

                <div class="cta">Corre por su futuro</div>

                <div class="steps">
                    <div class="step">
                        <div class="num">1</div>
                        <div class="icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="#1d4ea1" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                                <line x1="14" y1="14" x2="21" y2="14"></line>
                                <line x1="14" y1="21" x2="21" y2="21"></line>
                                <line x1="17.5" y1="14" x2="17.5" y2="21"></line>
                            </svg>
                        </div>
                        <div class="label">Escanea<br>tu factura</div>
                    </div>
                    <div class="step">
                        <div class="num" style="background:#1ea34d;">2</div>
                        <div class="icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="#1d4ea1" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="4" y="5" width="16" height="14" rx="2"></rect>
                                <path d="M8 19v2M16 19v2M7 9h10"></path>
                            </svg>
                        </div>
                        <div class="label">Llena<br>tus datos</div>
                    </div>
                    <div class="step">
                        <div class="num" style="background:#8d59e8;">3</div>
                        <div class="icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="#1d4ea1" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 21s7-4.6 7-11a7 7 0 0 0-14 0c0 6.4 7 11 7 11Z"></path>
                                <circle cx="12" cy="10" r="2"></circle>
                            </svg>
                        </div>
                        <div class="label">Recibe<br>tu cupón</div>
                    </div>
                </div>

                <div class="chips">
                    <span class="chip yellow">QR de factura</span>
                    <span class="chip green">Canje por zona</span>
                    <span class="chip orange">Premios</span>
                    <span class="chip purple">Apoyo comunitario</span>
                </div>
            </div>
        </section>

        <aside class="right" aria-label="Registro">
            <div class="head">
                <span style="color:#ffc629;font-size:22px;">✦</span>
                <h2>REGISTRA TU FACTURA</h2>
                <span style="color:#ffc629;font-size:22px;">✦</span>
            </div>
            <p class="sub">Elige cómo deseas registrar tu factura<br>y sigue los pasos.</p>

            <div class="options">
                <button type="button" class="option purple tab-btn is-active" data-tab="scan">
                    <div class="bubble">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#6d3fd6" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                            <line x1="14" y1="14" x2="21" y2="14"></line>
                            <line x1="14" y1="21" x2="21" y2="21"></line>
                            <line x1="17.5" y1="14" x2="17.5" y2="21"></line>
                        </svg>
                    </div>
                    <div class="content">
                        <div class="title">Escaneando el CUFE (QR)</div>
                        <div class="desc">Usa la cámara de tu celular para escanear el código QR de tu factura.</div>
                    </div>
                    <div class="arrow">›</div>
                </button>

                <button type="button" class="option green tab-btn" data-tab="whatsapp">
                    <div class="bubble">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M20 3.9A10 10 0 0 0 2.7 16.3L1 23l6.9-1.8A10 10 0 1 0 20 3.9zm-8 15.4a8.3 8.3 0 0 1-4.2-1.1l-.3-.2-3 .8.8-2.9-.2-.3a8.4 8.4 0 1 1 6.9 3.7zm4.6-6.3c-.3-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1-.2.3-.6.8-.8 1-.1.2-.3.2-.6.1-.3-.1-1.2-.4-2.2-1.4-.8-.7-1.4-1.6-1.5-1.9-.2-.3 0-.4.1-.6l.4-.5c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5-.1-.1-.6-1.5-.9-2-.2-.5-.5-.4-.6-.4h-.5c-.2 0-.5.1-.7.3-.2.3-.9.9-.9 2.1s1 2.5 1.1 2.6c.1.2 2 3 4.8 4.2.7.3 1.2.5 1.6.6.7.2 1.3.2 1.8.1.6-.1 1.7-.7 1.9-1.3.2-.7.2-1.2.2-1.3-.1-.1-.3-.2-.6-.3z"></path>
                        </svg>
                    </div>
                    <div class="content">
                        <div class="title">Por WhatsApp</div>
                        <div class="desc">Envía tu factura por WhatsApp y nosotros la registramos por ti.</div>
                    </div>
                    <div class="arrow">›</div>
                </button>

                <button type="button" class="option blue tab-btn" data-tab="manual">
                    <div class="bubble">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#1a72ef" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 20h9"></path>
                            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"></path>
                        </svg>
                    </div>
                    <div class="content">
                        <div class="title">Escribiendo el CUFE</div>
                        <div class="desc">Ingresa manualmente el código CUFE de tu factura.</div>
                    </div>
                    <div class="arrow">›</div>
                </button>
            </div>

            <div class="form" style="display:block;margin-top:18px;">
                <form id="registration-form" method="POST" action="{{ route('fanlyc.store') }}">
                    @csrf
                    <div class="field-row" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <label style="display:grid;gap:6px;color:#fff;font-size:13px;font-weight:800;">Nombre completo
                            <input name="full_name" value="{{ old('full_name') }}" placeholder="Ej. Juan Pérez" required>
                        </label>
                        <label style="display:grid;gap:6px;color:#fff;font-size:13px;font-weight:800;">Cédula
                            <input name="cedula" value="{{ old('cedula') }}" placeholder="Ej. 8-123-4567" required>
                        </label>
                    </div>
                    <div class="field-row" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <label style="display:grid;gap:6px;color:#fff;font-size:13px;font-weight:800;">Correo electrónico
                            <input type="email" name="email" value="{{ old('email') }}" placeholder="Ej. juan@gmail.com" required>
                        </label>
                        <label style="display:grid;gap:6px;color:#fff;font-size:13px;font-weight:800;">Teléfono
                            <input name="phone" value="{{ old('phone') }}" placeholder="Ej. 6000-0000" required>
                        </label>
                    </div>

                    <input type="hidden" id="qr_raw_text" name="qr_raw_text" value="{{ old('qr_raw_text') }}">

                    <div id="scan-panel" class="tab-panel" style="padding-top:10px;border-top:1px solid rgba(255,255,255,.18);">
                        <label style="display:grid;gap:6px;color:#fff;font-size:13px;font-weight:800;">Código de factura (QR / CUFE)
                            <input id="qr_raw_text_scan" value="{{ old('qr_raw_text') }}" placeholder="Pega el código QR o CUFE aquí" autocomplete="off" maxlength="2048">
                        </label>
                        <div class="scan-helper">
                            <strong aria-hidden="true">QR</strong>
                            <span>La cámara se abre al elegir esta opción. También puedes <button class="scan-retry" id="open-qr-scanner" type="button">abrirla de nuevo</button> o pegar el código.</span>
                        </div>
                        <p id="scan-status" class="scan-helper" role="status" aria-live="polite" hidden></p>
                    </div>

                    <div id="manual-panel" class="tab-panel" hidden style="padding-top:10px;border-top:1px solid rgba(255,255,255,.18);">
                        <label style="display:grid;gap:6px;color:#fff;font-size:13px;font-weight:800;">Escribe los últimos 60 dígitos del CUFE
                            <input id="cufe_manual" placeholder="Solo los últimos 60 números" autocomplete="off" inputmode="numeric" pattern="[0-9]{60}" minlength="60">
                        </label>
                        <div class="scan-helper"><strong>60</strong><span>Usa solamente los últimos 60 números que aparecen en tu factura.</span></div>
                    </div>

                    <div id="whatsapp-panel" class="tab-panel" hidden style="padding-top:10px;border-top:1px solid rgba(255,255,255,.18);">
                        <div style="padding:12px 14px;border-radius:16px;font-size:13px;line-height:1.45;background:#eafbea;color:#0d6f49;border:1px solid #b8eccb;margin-top:0;">
                            Al continuar, te llevaremos a WhatsApp con tus datos listos. Envía una foto clara de tu factura y nuestro equipo completará el registro.
                        </div>
                    </div>

                    <div id="form-field-error" style="display:none;padding:12px 14px;border-radius:16px;font-size:13px;line-height:1.45;background:#fff0f0;color:#b42318;border:1px solid #ffc7c7;"></div>

                    <label style="display:flex;gap:10px;align-items:flex-start;font-size:13px;line-height:1.45;color:#fff;font-weight:700;">
                        <input type="checkbox" name="consent_terms" value="1" required style="width:18px;height:18px;margin-top:2px;accent-color:#ffc629;">
                        <span>Acepto los términos de Fanlyc y autorizo a Super Carnes a validar mi factura.</span>
                    </label>

                    @if ($errors->any())
                        <div style="padding:12px 14px;border-radius:16px;font-size:13px;line-height:1.45;background:#fff0f0;color:#b42318;border:1px solid #ffc7c7;">{{ $errors->first() }}</div>
                    @endif
                    @if (session('status'))
                        <div style="padding:12px 14px;border-radius:16px;font-size:13px;line-height:1.45;background:#eafbea;color:#0d6f49;border:1px solid #b8eccb;">{{ session('status') }}</div>
                    @endif

                    <button id="registration-submit" class="btn" type="submit" style="background:linear-gradient(180deg,#ffcf2d 0%,#f2b70b 100%);color:#654400;box-shadow:0 16px 24px rgba(242,183,11,.20);">
                        Registrar factura
                    </button>
                    <a href="{{ route('fanlyc.status') }}" style="display:inline-block;margin-top:10px;color:#ffc629;font-weight:700;text-decoration:none;">Ver mis cupones</a>
                </form>
            </div>

            <div class="footer-lockup">
                <div class="mini">
                    <img src="/fanlyc-assets/fanlyc-supercarnes-mark-crop.png" alt="Super Carnes">
                </div>
                <div class="claim">Juntos <span>transformamos</span> vidas.</div>
            </div>
        </aside>

        <section id="premios" class="mini-grid" style="position:absolute;left:150px;right:56px;bottom:0;display:grid;grid-template-columns:repeat(3,1fr);gap:14px;">
            <article id="premios" style="min-height:172px;border-radius:22px;padding:18px;background:#f7fbff;box-shadow:0 22px 40px rgba(8,56,122,.18);">
                <div style="width:64px;height:64px;border-radius:50%;display:grid;place-items:center;margin-bottom:10px;background:rgba(109,63,214,.13);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#7d3fe6" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="width:30px;height:30px;">
                        <path d="M6 3h12v10H6z"></path><path d="M9 21h6"></path><path d="M12 13v8"></path><path d="M9 21l-1-4h8l-1 4"></path>
                    </svg>
                </div>
                <h3 style="margin:0 0 8px;font-size:20px;line-height:1;color:#7b3be6;font-family:'Poppins',sans-serif;">Tu QR</h3>
                <p style="margin:0;line-height:1.45;color:#4e6786;font-size:14px;max-width:240px;">Se genera al aprobar tu factura y queda listo para consulta y canje en tu zona.</p>
            </article>
            <article id="zona" style="min-height:172px;border-radius:22px;padding:18px;background:#f7fbff;box-shadow:0 22px 40px rgba(8,56,122,.18);">
                <div style="width:64px;height:64px;border-radius:50%;display:grid;place-items:center;margin-bottom:10px;background:rgba(85,198,59,.16);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#3ca81d" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="width:30px;height:30px;">
                        <path d="M12 21s6-4.2 6-10a6 6 0 0 0-12 0c0 5.8 6 10 6 10Z"></path><circle cx="12" cy="11" r="2.2"></circle>
                    </svg>
                </div>
                <h3 style="margin:0 0 8px;font-size:20px;line-height:1;color:#3ca81d;font-family:'Poppins',sans-serif;">Zona asignada</h3>
                <p style="margin:0;line-height:1.45;color:#4e6786;font-size:14px;max-width:240px;">El sistema detecta tu sucursal y define la zona correcta para tu cupón.</p>
            </article>
            <article id="apoyo" style="min-height:172px;border-radius:22px;padding:18px;background:#f7fbff;box-shadow:0 22px 40px rgba(8,56,122,.18);">
                <div style="width:64px;height:64px;border-radius:50%;display:grid;place-items:center;margin-bottom:10px;background:rgba(255,122,29,.16);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#f07813" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="width:30px;height:30px;">
                        <path d="M12 21s7-4.5 7-11a7 7 0 0 0-14 0c0 6.5 7 11 7 11Z"></path><path d="M9 10h6"></path>
                    </svg>
                </div>
                <h3 style="margin:0 0 8px;font-size:20px;line-height:1;color:#f07813;font-family:'Poppins',sans-serif;">Canje seguro</h3>
                <p style="margin:0;line-height:1.45;color:#4e6786;font-size:14px;max-width:240px;">El staff escanea el cupón, valida el estado y emite el ticket correspondiente.</p>
            </article>
        </section>
    </div>
</div>

<div id="qr-scanner-modal" class="scanner-modal" hidden role="dialog" aria-modal="true" aria-labelledby="qr-scanner-title">
    <div class="scanner-dialog">
        <header>
            <h3 id="qr-scanner-title">Escanea el QR de tu factura</h3>
            <button id="close-qr-scanner" class="scanner-close" type="button" aria-label="Cerrar cámara">×</button>
        </header>
        <div class="scanner-video-wrap">
            <div id="qr-camera" class="scanner-video"></div>
        </div>
        <p id="qr-scanner-status" class="scanner-status" role="status" aria-live="polite">Solicitando acceso a la cámara…</p>
    </div>
</div>

<script src="/fanlyc-assets/html5-qrcode.min.js"></script>
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
    const submitButton = document.getElementById('registration-submit');
    const scanStatus = document.getElementById('scan-status');
    const scannerModal = document.getElementById('qr-scanner-modal');
    const scannerStatus = document.getElementById('qr-scanner-status');
    const openScannerButton = document.getElementById('open-qr-scanner');
    const closeScannerButton = document.getElementById('close-qr-scanner');
    let qrScanner = null;
    let activeTab = 'scan';

    const showFieldError = (message) => {
        fieldError.textContent = message;
        fieldError.style.display = 'block';
    };

    const setScanStatus = (message) => {
        scanStatus.textContent = message;
        scanStatus.hidden = !message;
    };

    const stopScanner = async () => {
        if (!qrScanner) {
            return;
        }

        const activeScanner = qrScanner;
        qrScanner = null;

        try {
            await activeScanner.stop();
        } catch (error) {
            // It is safe to close even when the camera has already stopped.
        }

        try {
            await activeScanner.clear();
        } catch (error) {
            // The scanner container may already be empty.
        }
    };

    const closeScanner = () => {
        scannerModal.hidden = true;
        void stopScanner();
    };

    const openScanner = async () => {
        if (!navigator.mediaDevices?.getUserMedia || !window.Html5Qrcode) {
            setScanStatus('Tu navegador no permite usar la cámara. Pega el código QR de tu factura.');
            return;
        }

        await stopScanner();
        scannerModal.hidden = false;
        scannerStatus.textContent = 'Solicitando acceso a la cámara…';

        try {
            qrScanner = new window.Html5Qrcode('qr-camera');
            await qrScanner.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 250, height: 250 } },
                (decodedText) => {
                    const value = decodedText.trim();

                    if (!value) {
                        return;
                    }

                    scanInput.value = value;
                    hiddenInput.value = value;
                    setScanStatus('Código QR leído correctamente.');
                    closeScanner();
                    scanInput.focus();
                },
                () => {},
            );
            scannerStatus.textContent = 'Apunta la cámara al QR de tu factura.';
        } catch (error) {
            qrScanner = null;
            closeScanner();
            setScanStatus('No pudimos activar la cámara. Revisa el permiso y vuelve a intentarlo, o pega el código QR.');
        }
    };

    const normalizeManualCufe = () => {
        const digits = manualInput.value.replace(/\D/g, '').slice(-60);
        manualInput.value = digits;
        return digits;
    };

    const setActiveTab = (tab, openCamera = false) => {
        activeTab = tab;
        tabButtons.forEach((btn) => btn.classList.toggle('is-active', btn.dataset.tab === tab));
        scanPanel.hidden = tab !== 'scan';
        manualPanel.hidden = tab !== 'manual';
        whatsappPanel.hidden = tab !== 'whatsapp';
        fieldError.style.display = 'none';
        submitButton.textContent = tab === 'whatsapp' ? 'Continuar por WhatsApp' : 'Registrar factura';

        if (tab !== 'scan') {
            void stopScanner();
            scannerModal.hidden = true;
        }

        if (tab === 'manual') {
            manualInput.focus();
        }

        if (tab === 'scan' && openCamera) {
            openScanner();
        }
    };

    tabButtons.forEach((btn) => btn.addEventListener('click', () => setActiveTab(btn.dataset.tab, btn.dataset.tab === 'scan')));
    openScannerButton?.addEventListener('click', openScanner);
    closeScannerButton?.addEventListener('click', closeScanner);
    scannerModal?.addEventListener('click', (event) => {
        if (event.target === scannerModal) {
            closeScanner();
        }
    });
    manualInput?.addEventListener('input', normalizeManualCufe);
    window.addEventListener('pagehide', () => void stopScanner());

    registrationForm?.addEventListener('submit', (event) => {
        if (activeTab === 'whatsapp') {
            event.preventDefault();
            const values = new FormData(registrationForm);
            const message = [
                'Hola Super Carnes, quiero registrar mi factura para Fanlyc.',
                '',
                `Nombre: ${values.get('full_name')}`,
                `Cédula: ${values.get('cedula')}`,
                `Correo: ${values.get('email')}`,
                `Teléfono: ${values.get('phone')}`,
                '',
                'Adjunto una foto clara de mi factura para completar el registro.',
            ].join('\n');

            window.location.assign(`https://wa.me/50768982167?text=${encodeURIComponent(message)}`);
            return;
        }

        const value = activeTab === 'manual'
            ? normalizeManualCufe()
            : scanInput.value.trim();

        if (!value) {
            event.preventDefault();
            showFieldError(activeTab === 'manual'
                ? 'Escribe los últimos 60 números del CUFE de tu factura.'
                : 'Escanea o pega el código QR de tu factura.');
            return;
        }

        if (activeTab === 'manual' && value.length !== 60) {
            event.preventDefault();
            showFieldError('El CUFE debe contener exactamente los últimos 60 números de tu factura.');
            return;
        }

        hiddenInput.value = value;
    });
})();
</script>
</body>
</html>
