<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración — Admin</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, sans-serif; background: #f1f5f9; color: #1e293b; }

        header {
            background: #dc2626; color: #fff;
            padding: .875rem 1.5rem;
            display: flex; align-items: center; justify-content: space-between;
            gap: 1rem;
        }
        header h1 { font-size: 1rem; font-weight: 700; white-space: nowrap; }
        header nav { display: flex; align-items: center; flex-wrap: wrap; gap: .25rem; }
        header nav a {
            color: rgba(255,255,255,.80); text-decoration: none;
            font-size: .875rem; padding: .375rem .75rem;
            border-radius: 4px; transition: background .15s;
        }
        header nav a:hover { color: #fff; background: rgba(255,255,255,.12); }
        header nav a.active { color: #fff; background: rgba(0,0,0,.20); font-weight: 600; }
        form.logout { display: inline; }
        form.logout button {
            background: none; border: 1px solid rgba(255,255,255,.35);
            color: rgba(255,255,255,.85); cursor: pointer; font-size: .875rem;
            padding: .375rem .75rem; border-radius: 4px; transition: all .15s;
        }
        form.logout button:hover { background: rgba(255,255,255,.12); color: #fff; }

        main { padding: 2rem 1.5rem; max-width: 860px; margin: 0 auto; }

        .page-title { margin-bottom: 1.5rem; }
        .page-title h2 { font-size: 1.375rem; font-weight: 700; color: #0f172a; }
        .page-title p { margin-top: .25rem; font-size: .9rem; color: #64748b; }

        .card {
            background: #fff;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }
        .card-section {
            padding: 1.5rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .card-section:last-child { border-bottom: none; }
        .section-title {
            font-size: .8125rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .06em; color: #94a3b8; margin-bottom: 1rem;
        }

        .toggle-row {
            display: flex; align-items: center; justify-content: space-between;
            gap: 1rem;
        }
        .toggle-row label { font-size: .9375rem; font-weight: 600; color: #1e293b; cursor: pointer; }
        .toggle-row p { font-size: .8125rem; color: #64748b; margin-top: .2rem; }
        .toggle {
            position: relative; width: 48px; height: 26px; flex-shrink: 0;
        }
        .toggle input { opacity: 0; width: 0; height: 0; }
        .toggle-slider {
            position: absolute; inset: 0; background: #cbd5e1;
            cursor: pointer; transition: .2s;
        }
        .toggle input:checked + .toggle-slider { background: #dc2626; }
        .toggle-slider::before {
            content: ''; position: absolute;
            width: 20px; height: 20px; background: #fff;
            left: 3px; top: 3px; transition: .2s;
        }
        .toggle input:checked + .toggle-slider::before { transform: translateX(22px); }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media (max-width: 560px) { .grid-2 { grid-template-columns: 1fr; } }

        .field { display: flex; flex-direction: column; gap: .375rem; }
        .field label { font-size: .875rem; font-weight: 600; color: #374151; }
        .field .hint { font-size: .8rem; color: #94a3b8; }
        .field input, .field select {
            width: 100%; padding: .625rem .875rem;
            border: 1px solid #d1d5db; background: #fff; color: #1e293b;
            font-size: .9375rem; outline: none;
            transition: border-color .15s;
        }
        .field input:focus, .field select:focus { border-color: #dc2626; }

        .alert-success {
            padding: .875rem 1.25rem; background: #f0fdf4;
            border-left: 3px solid #22c55e; color: #166534;
            font-size: .9rem; margin-bottom: 1.25rem;
        }

        .actions {
            display: flex; justify-content: flex-end; padding: 1.25rem 1.5rem;
            background: #f8fafc; border-top: 1px solid #e2e8f0;
        }
        .btn-save {
            background: #dc2626; color: #fff; border: none;
            padding: .625rem 1.75rem; font-size: .9375rem; font-weight: 700;
            cursor: pointer; transition: background .15s;
        }
        .btn-save:hover { background: #b91c1c; }

        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
        @media (max-width: 560px) { .stats-grid { grid-template-columns: 1fr; } }
        .stat-box { padding: 1rem 1.25rem; background: #f8fafc; border-left: 3px solid #dc2626; }
        .stat-box strong { display: block; font-size: 1.5rem; font-weight: 800; color: #0f172a; }
        .stat-box span { font-size: .8125rem; color: #64748b; }

        .shortcut-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: .75rem; }
        @media (max-width: 560px) { .shortcut-grid { grid-template-columns: 1fr; } }
        .shortcut-link {
            display: flex; align-items: center; gap: .75rem;
            padding: 1rem 1.25rem; background: #f8fafc;
            border: 1px solid #e2e8f0; text-decoration: none;
            color: #1e293b; transition: all .15s;
            border-left: 3px solid transparent;
        }
        .shortcut-link:hover { background: #fff; border-left-color: #dc2626; }
        .shortcut-icon { font-size: 1.25rem; line-height: 1; }
        .shortcut-link strong { display: block; font-size: .9375rem; font-weight: 700; }
        .shortcut-link span { font-size: .8125rem; color: #64748b; }
    </style>
</head>
<body>

<header>
    <h1>Super Carnes Admin</h1>
    <nav>
        <a href="{{ route('admin.invoice-backoffice') }}" class="active">Configuración</a>
        <a href="{{ route('admin.invoices') }}">Facturas</a>
        <a href="{{ route('admin.winners') }}">Ganadores</a>
        <form class="logout" method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit">Cerrar sesión</button>
        </form>
    </nav>
</header>

<main>
    <div class="page-title">
        <h2>Panel de administración</h2>
        <p>Promo Balón Trionda — Super Carnes 2026</p>
    </div>

    @if (session('status'))
        <div class="alert-success">{{ session('status') }}</div>
    @endif

    {{-- Shortcuts --}}
    <div class="shortcut-grid" style="margin-bottom: 1.5rem;">
        <a href="{{ route('admin.invoices') }}" class="shortcut-link">
            <span class="shortcut-icon">🧾</span>
            <div>
                <strong>Facturas registradas</strong>
                <span>Ver todas las participaciones</span>
            </div>
        </a>
        <a href="{{ route('admin.winners') }}" class="shortcut-link">
            <span class="shortcut-icon">🏆</span>
            <div>
                <strong>Acta de ganadores</strong>
                <span>Los 100 primeros — sin repetir cédula</span>
            </div>
        </a>
    </div>

    {{-- Settings form --}}
    <form method="POST" action="{{ route('admin.invoice-backoffice.update') }}">
        @csrf
        <div class="card">
            <div class="card-section">
                <p class="section-title">Estado de la promoción</p>
                <div class="toggle-row">
                    <div>
                        <label for="is_enabled">Registro de facturas activo</label>
                        <p>Cuando está desactivado, nadie puede registrar una factura nueva.</p>
                    </div>
                    <label class="toggle">
                        <input type="checkbox" id="is_enabled" name="is_enabled" value="1" @checked($settings->is_enabled)>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>

            <div class="card-section">
                <p class="section-title">Reglas de validación</p>
                <div class="grid-2">
                    <div class="field">
                        <label for="min_purchase_amount">Monto mínimo de compra</label>
                        <input id="min_purchase_amount" name="min_purchase_amount" type="number"
                               min="0" step="0.01"
                               value="{{ old('min_purchase_amount', $settings->min_purchase_amount) }}">
                        <span class="hint">Facturas por debajo de este valor son rechazadas.</span>
                    </div>
                    <div class="field">
                        <label for="invoice_age_policy">Política de fecha de factura</label>
                        <select id="invoice_age_policy" name="invoice_age_policy">
                            <option value="none" @selected(old('invoice_age_policy', $settings->invoice_age_policy) === 'none')>Sin filtro de fecha</option>
                            <option value="same_day" @selected(old('invoice_age_policy', $settings->invoice_age_policy) === 'same_day')>Solo del mismo día</option>
                            <option value="last_24_hours" @selected(old('invoice_age_policy', $settings->invoice_age_policy) === 'last_24_hours')>Últimas 24 horas</option>
                            <option value="days" @selected(!in_array(old('invoice_age_policy', $settings->invoice_age_policy), ['none','same_day','last_24_hours']))>Ventana de días (ver abajo)</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="max_invoice_age_days">Máximo de días (si aplica)</label>
                        <input id="max_invoice_age_days" name="max_invoice_age_days" type="number"
                               min="0" max="30"
                               value="{{ old('max_invoice_age_days', $settings->max_invoice_age_days) }}">
                        <span class="hint">Solo se usa si eliges "Ventana de días" arriba.</span>
                    </div>
                    <div class="field">
                        <label for="validation_mode">Modo de validación DGI</label>
                        <select id="validation_mode" name="validation_mode">
                            <option value="api" @selected(old('validation_mode', $settings->validation_mode) === 'api')>API — verificar contra DGI</option>
                            <option value="manual" @selected(old('validation_mode', $settings->validation_mode) === 'manual')>Manual — solo guardar</option>
                        </select>
                        <span class="hint">En modo API, cada CUFE se confirma en tiempo real.</span>
                    </div>
                </div>
            </div>

            <div class="actions">
                <button type="submit" class="btn-save">Guardar configuración</button>
            </div>
        </div>
    </form>
</main>

</body>
</html>
