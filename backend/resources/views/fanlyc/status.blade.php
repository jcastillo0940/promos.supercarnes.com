<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mis cupones Fanlyc - Super Carnes</title>
    <style>
        :root { --red: #b91c1c; --paper: #f8fafc; --ink: #0f172a; --shadow: rgba(15, 23, 42, .14); }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, Arial, sans-serif; color: var(--ink); background: var(--paper); min-height: 100vh; }
        .page { width: min(900px, calc(100vw - 24px)); margin: 0 auto; padding: 24px 0 48px; }
        h1 { font-size: 28px; margin: 0 0 4px; }
        .sub { color: #64748b; margin: 0 0 20px; }
        .card { background: #fff; border-radius: 20px; padding: 22px; box-shadow: 0 12px 32px var(--shadow); margin-bottom: 16px; }
        .status { padding: 12px 14px; border-radius: 12px; background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; margin-bottom: 16px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr auto; gap: 10px; align-items: end; }
        label { display: grid; gap: 6px; font-size: 13px; font-weight: 700; color: #334155; }
        input { border: 1px solid #cbd5e1; border-radius: 12px; padding: 12px 14px; font: inherit; width: 100%; }
        .btn { cursor: pointer; border: none; border-radius: 12px; padding: 12px 16px; font-weight: 700; background: var(--red); color: #fff; }
        .coupon { display: grid; grid-template-columns: 120px 1fr; gap: 16px; align-items: center; padding: 14px 0; border-bottom: 1px solid #e2e8f0; }
        .coupon:last-child { border-bottom: none; }
        .coupon img { width: 100%; border-radius: 10px; border: 1px solid #e2e8f0; }
        .badge { display: inline-flex; border-radius: 999px; padding: 3px 10px; font-size: 12px; font-weight: 700; }
        .badge-issued { background: #fef9c3; color: #854d0e; }
        .badge-redeemed { background: #dcfce7; color: #166534; }
        .badge-void { background: #e2e8f0; color: #334155; }
        .empty { color: #64748b; padding: 20px 0; }
        @media (max-width: 640px) {
            .form-row { grid-template-columns: 1fr; }
            .coupon { grid-template-columns: 90px 1fr; }
        }
    </style>
</head>
<body>
    <main class="page">
        <h1>Mis cupones Fanlyc</h1>
        <p class="sub">Consulta tus cupones acumulados con tu cedula y telefono.</p>

        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        <div class="card">
            <form method="GET" action="{{ route('fanlyc.status') }}" class="form-row">
                <label>Cedula
                    <input name="cedula" value="{{ $cedula }}" placeholder="8-123-4567" required>
                </label>
                <label>Telefono
                    <input name="phone" value="{{ $phone }}" placeholder="6000-0000" required>
                </label>
                <button class="btn" type="submit">Buscar</button>
            </form>
        </div>

        @if($searched)
            <div class="card">
                @if(! $participant)
                    <div class="empty">No encontramos cupones con esos datos. Verifica tu cedula y telefono.</div>
                @elseif($coupons->isEmpty())
                    <div class="empty">Aun no tienes cupones. Registra una factura para obtener tu primer cupon QR.</div>
                @else
                    @foreach($coupons as $coupon)
                        <div class="coupon">
                            @if($coupon->status === 'issued')
                                <img src="{{ route('fanlyc.coupon.qr', $coupon->code) }}" alt="QR cupon {{ $coupon->code }}">
                            @else
                                <div style="width:100%;aspect-ratio:1;border-radius:10px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:12px;text-align:center;">
                                    QR no disponible
                                </div>
                            @endif
                            <div>
                                <div style="font-weight:800;font-size:16px;">{{ $coupon->code }}</div>
                                <div style="color:#64748b;font-size:13px;margin:4px 0;">Zona: {{ $coupon->fanlycZone?->name ?? '—' }}</div>
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

        <div class="card">
            <a href="{{ route('fanlyc.landing') }}" style="color:var(--red);font-weight:700;text-decoration:none;">&larr; Registrar otra factura</a>
        </div>
    </main>
</body>
</html>
