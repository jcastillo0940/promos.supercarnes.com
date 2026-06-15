<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Backoffice Promo</title>
    <style>
        body { margin:0; font-family: Arial, sans-serif; background:#07111f; color:#f3f7ff; }
        .wrap { max-width: 760px; margin: 0 auto; padding: 40px 20px; }
        .card { background: rgba(9,17,32,.92); border:1px solid rgba(255,255,255,.08); border-radius: 20px; padding: 24px; box-shadow: 0 20px 60px rgba(0,0,0,.35); }
        h1 { margin:0 0 8px; font-size: 32px; }
        p { color: rgba(243,247,255,.72); }
        label { display:block; margin: 16px 0 6px; font-weight: 700; }
        input, select { width:100%; padding:12px 14px; border-radius: 12px; border:1px solid rgba(255,255,255,.1); background:#0c1728; color:#fff; }
        button { margin-top: 18px; width:100%; padding: 14px; border:0; border-radius: 14px; background:#22c55e; color:#04111c; font-weight:800; cursor:pointer; }
        .status { margin-bottom: 16px; padding: 12px 14px; border-radius: 12px; background: rgba(34,197,94,.12); border: 1px solid rgba(34,197,94,.25); }
        .hint { font-size: 14px; opacity: .8; }
        .grid { display:grid; gap: 14px; grid-template-columns: 1fr 1fr; }
        @media (max-width: 720px) { .grid { grid-template-columns: 1fr; } }
        .badge { display:inline-block; padding:6px 10px; border-radius:999px; background:rgba(255,255,255,.08); margin-bottom: 16px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="badge">Backoffice promo de papá</div>
            <h1>Configuración de facturas</h1>
            <p>Desde aquí puedes activar la promo y definir qué facturas aceptamos en el registro.</p>

            @if (session('status'))
                <div class="status">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('admin.invoice-backoffice.update', ['key' => $backofficeKey]) }}">
                @csrf
                <label>
                    <input type="checkbox" name="is_enabled" value="1" @checked($settings->is_enabled)>
                    Activar registro de facturas
                </label>

                <div class="grid">
                    <div>
                        <label for="min_purchase_amount">Monto mínimo</label>
                        <input id="min_purchase_amount" name="min_purchase_amount" type="number" min="0" step="0.01" value="{{ old('min_purchase_amount', $settings->min_purchase_amount) }}">
                    </div>
                    <div>
                        <label for="invoice_age_policy">Política de fecha</label>
                        <select id="invoice_age_policy" name="invoice_age_policy">
                            <option value="none" @selected(old('invoice_age_policy', $settings->invoice_age_policy) === 'none')>Sin filtro de fecha</option>
                            <option value="same_day" @selected(old('invoice_age_policy', $settings->invoice_age_policy) === 'same_day')>Mismo día</option>
                            <option value="last_24_hours" @selected(old('invoice_age_policy', $settings->invoice_age_policy) === 'last_24_hours')>Últimas 24 horas</option>
                        </select>
                    </div>
                </div>

                <label for="max_invoice_age_days">Máximo de días si usas filtro manual</label>
                <input id="max_invoice_age_days" name="max_invoice_age_days" type="number" min="0" max="30" value="{{ old('max_invoice_age_days', $settings->max_invoice_age_days) }}">
                <div class="hint">Si eliges “Sin filtro de fecha”, este valor no se usa por ahora.</div>

                <button type="submit">Guardar configuración</button>
            </form>
        </div>
    </div>
</body>
</html>
