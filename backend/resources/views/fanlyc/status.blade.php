<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mis cupones Fanlyc - Super Carnes</title>
    <style>
        :root{
            --sky:#45b9e6;
            --purple:#8c57ff;
            --orange:#ff7a2f;
            --yellow:#ffca16;
            --ink:#16324f;
        }
        *{box-sizing:border-box}
        body{
            margin:0;
            font-family:Inter, Arial, sans-serif;
            color:var(--ink);
            background:
                radial-gradient(circle at 8% 10%, rgba(255,255,255,.32), transparent 16%),
                linear-gradient(180deg, #59c2eb 0%, var(--sky) 100%);
            min-height:100vh;
        }
        .page{width:min(920px, calc(100vw - 20px)); margin:0 auto; padding:16px 0 36px}
        .hero{
            border-radius:30px;
            padding:24px;
            background:rgba(255,255,255,.92);
            box-shadow:0 18px 36px rgba(18,58,86,.14);
        }
        .hero h1{
            margin:0;
            font-size:clamp(34px, 6vw, 58px);
            line-height:.95;
            color:#ff5e52;
            font-weight:900;
        }
        .hero p{margin:10px 0 0; line-height:1.55}
        .hero .logo{display:block; width:min(170px, 38vw); margin:0 0 12px auto}
        .card{
            margin-top:14px;
            border-radius:28px;
            padding:20px;
            background:rgba(255,255,255,.92);
            box-shadow:0 16px 30px rgba(18,58,86,.12);
        }
        .status{padding:12px 14px; border-radius:16px; background:#e7fff3; color:#0f6b47; border:1px solid #b9f2d2; margin-bottom:14px}
        .form-row{display:grid; grid-template-columns:1fr 1fr auto; gap:10px; align-items:end}
        label{display:grid; gap:6px; font-size:13px; font-weight:800; color:#23486b}
        input{
            border:0;
            border-radius:16px;
            padding:13px 14px;
            font:inherit;
            background:#fff;
            box-shadow:inset 0 0 0 2px rgba(22,50,79,.08);
        }
        .btn{
            cursor:pointer;
            border:0;
            border-radius:16px;
            padding:13px 18px;
            font-weight:900;
            background:linear-gradient(180deg, #ffd31a, #f3b500);
            color:#543800;
        }
        .coupon{
            display:grid;
            grid-template-columns:120px 1fr;
            gap:14px;
            align-items:center;
            padding:14px 0;
            border-bottom:1px solid rgba(22,50,79,.08);
        }
        .coupon:last-child{border-bottom:none}
        .coupon img{
            width:100%;
            border-radius:16px;
            border:4px solid rgba(255,255,255,.95);
            box-shadow:0 12px 20px rgba(0,0,0,.08);
            background:#fff;
        }
        .badge{
            display:inline-flex;
            border-radius:999px;
            padding:4px 10px;
            font-size:12px;
            font-weight:900;
        }
        .badge-issued{background:rgba(255,202,22,.22); color:#7a4c00}
        .badge-redeemed{background:rgba(53,214,74,.16); color:#0f6b47}
        .badge-void{background:rgba(140,87,255,.15); color:#5735a8}
        .empty{padding:18px 0; color:#35516e; line-height:1.55}
        .back{
            margin-top:14px;
            display:inline-flex;
            text-decoration:none;
            font-weight:900;
            color:#fff;
            background:rgba(255,255,255,.16);
            padding:12px 14px;
            border-radius:999px;
        }
        @media (max-width: 640px){
            .form-row{grid-template-columns:1fr}
            .coupon{grid-template-columns:92px 1fr}
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="hero">
            <img class="logo" src="/logo_web.jpg" alt="Super Carnes">
            <h1>Mis cupones Fanlyc</h1>
            <p>Consulta tus cupones acumulados con tu cédula y teléfono.</p>
        </section>

        @if (session('status'))
            <div class="status" style="margin-top:14px;">{{ session('status') }}</div>
        @endif

        <div class="card">
            <form method="POST" action="{{ route('fanlyc.status.search') }}" class="form-row">
                @csrf
                <label>Cédula
                    <input name="cedula" value="{{ $cedula }}" placeholder="8-123-4567" required>
                </label>
                <label>Teléfono
                    <input name="phone" value="{{ $phone }}" placeholder="6000-0000" required>
                </label>
                <button class="btn" type="submit">Buscar</button>
            </form>
        </div>

        @if($searched)
            <div class="card">
                @if(! $participant)
                    <div class="empty">No encontramos cupones con esos datos. Verifica tu cédula y teléfono.</div>
                @elseif($coupons->isEmpty())
                    <div class="empty">Aún no tienes cupones. Registra una factura para obtener tu primer cupón QR.</div>
                @else
                    @foreach($coupons as $coupon)
                        <div class="coupon">
                            @if($coupon->status === 'issued')
                                <img src="{{ route('fanlyc.coupon.qr', $coupon->code) }}" alt="QR cupón {{ $coupon->code }}">
                            @else
                                <div style="width:100%;aspect-ratio:1;border-radius:16px;background:#eef4fa;display:flex;align-items:center;justify-content:center;color:#7f94aa;font-size:12px;text-align:center;border:4px solid rgba(255,255,255,.95);">
                                    QR no disponible
                                </div>
                            @endif
                            <div>
                                <div style="font-weight:900;font-size:16px;">{{ $coupon->code }}</div>
                                <div style="color:#64748b;font-size:13px;margin:4px 0 8px;">Zona: {{ $coupon->fanlycZone?->name ?? '—' }}</div>
                                <span class="badge badge-{{ $coupon->status }}">
                                    {{ match($coupon->status) {
                                        'issued' => 'Disponible para canjear',
                                        'redeemed' => 'Canjeado',
                                        'void' => 'Anulado',
                                        default => $coupon->status,
                                    } }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        @endif

        <div class="card" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
            <a href="{{ route('fanlyc.landing') }}" style="color:#ff5e52;font-weight:900;text-decoration:none;">&larr; Registrar otra factura</a>
            <a class="back" href="{{ route('fanlyc.landing') }}">Volver a Fanlyc</a>
        </div>
    </main>
</body>
</html>
