@extends('admin.layout')

@section('title', 'Dashboard')
@section('subtitle', 'Análisis general')

@section('topbar-actions')
    <a class="topbar-action hide-mobile" href="{{ route('admin.invoices') }}">Facturas</a>
    <a class="topbar-action hide-mobile" href="{{ route('admin.winners') }}">Ganadores</a>
    <a class="topbar-action hide-mobile" href="{{ route('admin.prize-delivery') }}">Entrega de premio</a>
    <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit">Cerrar sesión</button>
    </form>
@endsection

@section('content')
    @if (session('status'))
        <div class="alert-success">{{ session('status') }}</div>
    @endif

    <div class="page-card">
        <div class="page-title">
            <div>
                <h1>Dashboard del backoffice</h1>
                <p>Resumen de ganadores, participación, facturación y sucursal líder.</p>
            </div>
        </div>

        <div class="page-section">
            <div class="stats-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
                <div class="stat-box">
                    <strong>{{ number_format($dashboard['kpis']['winners']) }}</strong>
                    <span>Ganadores escogidos</span>
                </div>
                <div class="stat-box">
                    <strong>{{ number_format($dashboard['kpis']['delivered']) }}</strong>
                    <span>Premios entregados</span>
                </div>
                <div class="stat-box">
                    <strong>{{ $dashboard['kpis']['participation_pct'] }}%</strong>
                    <span>Tasa de participación ganadora</span>
                </div>
                <div class="stat-box">
                    <strong>{{ number_format($dashboard['kpis']['participants']) }}</strong>
                    <span>Personas participantes</span>
                </div>
                <div class="stat-box">
                    <strong>{{ number_format($dashboard['kpis']['non_winners']) }}</strong>
                    <span>Personas no ganadoras</span>
                </div>
                <div class="stat-box">
                    <strong>${{ number_format($dashboard['kpis']['total_invoice_amount'], 2) }}</strong>
                    <span>Monto total de facturas</span>
                </div>
            </div>
        </div>

        <div class="page-section">
            <div class="form-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                <div class="page-card" style="box-shadow:none;border:1px solid #e5e7eb;">
                    <div class="page-section">
                        <div class="sidebar-title">Sucursal con más facturas</div>
                        <h2 style="margin:.3rem 0 0;">{{ $dashboard['kpis']['top_branch'] }}</h2>
                        <p style="margin:.25rem 0 0;color:#64748b;">{{ number_format($dashboard['kpis']['top_branch_total']) }} facturas registradas</p>
                    </div>
                </div>
                <div class="page-card" style="box-shadow:none;border:1px solid #e5e7eb;">
                    <div class="page-section">
                        <div class="sidebar-title">Comparativo rápido</div>
                        <h2 style="margin:.3rem 0 0;">{{ $dashboard['kpis']['winners'] }} ganadores vs {{ $dashboard['kpis']['non_winners'] }} no ganadores</h2>
                        <p style="margin:.25rem 0 0;color:#64748b;">La tasa actual de ganadores es {{ $dashboard['kpis']['participation_pct'] }}% sobre participantes únicos.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-section">
            <div class="form-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                <div class="page-card" style="box-shadow:none;border:1px solid #e5e7eb;">
                    <div class="page-section">
                        <div class="sidebar-title">Facturas por sucursal</div>
                        <canvas id="branch-chart" height="220"></canvas>
                    </div>
                </div>
                <div class="page-card" style="box-shadow:none;border:1px solid #e5e7eb;">
                    <div class="page-section">
                        <div class="sidebar-title">Facturación últimos 7 días</div>
                        <canvas id="daily-chart" height="220"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-section">
            <form method="POST" action="{{ route('admin.invoice-backoffice.update') }}">
                @csrf
                <div class="page-card" style="box-shadow:none;border:1px solid #e5e7eb;">
                    <div class="page-section stack">
                        <div>
                            <p class="sidebar-title">Estado de la promoción</p>
                            <div class="field">
                                <label for="is_enabled">Registro de facturas activo</label>
                                <select id="is_enabled" name="is_enabled">
                                    <option value="1" @selected($settings->is_enabled)>Activo</option>
                                    <option value="0" @selected(! $settings->is_enabled)>Inactivo</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <p class="sidebar-title">Reglas de validación</p>
                            <div class="form-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                                <div class="field">
                                    <label for="min_purchase_amount">Monto mínimo de compra</label>
                                    <input id="min_purchase_amount" name="min_purchase_amount" type="number" min="0" step="0.01" value="{{ old('min_purchase_amount', $settings->min_purchase_amount) }}">
                                </div>
                                <div class="field">
                                    <label for="invoice_age_policy">Política de fecha de factura</label>
                                    <select id="invoice_age_policy" name="invoice_age_policy">
                                        <option value="none" @selected(old('invoice_age_policy', $settings->invoice_age_policy) === 'none')>Sin filtro de fecha</option>
                                        <option value="same_day" @selected(old('invoice_age_policy', $settings->invoice_age_policy) === 'same_day')>Solo del mismo día</option>
                                        <option value="last_24_hours" @selected(old('invoice_age_policy', $settings->invoice_age_policy) === 'last_24_hours')>Últimas 24 horas</option>
                                        <option value="days" @selected(!in_array(old('invoice_age_policy', $settings->invoice_age_policy), ['none','same_day','last_24_hours']))>Ventana de días</option>
                                    </select>
                                </div>
                                <div class="field">
                                    <label for="max_invoice_age_days">Máximo de días</label>
                                    <input id="max_invoice_age_days" name="max_invoice_age_days" type="number" min="0" max="30" value="{{ old('max_invoice_age_days', $settings->max_invoice_age_days) }}">
                                </div>
                                <div class="field">
                                    <label for="validation_mode">Modo de validación DGI</label>
                                    <select id="validation_mode" name="validation_mode">
                                        <option value="api" @selected(old('validation_mode', $settings->validation_mode) === 'api')>API</option>
                                        <option value="manual" @selected(old('validation_mode', $settings->validation_mode) === 'manual')>Manual</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="page-section" style="border-top:1px solid #e5e7eb;">
                        <button type="submit" class="btn btn-red">Guardar configuración</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
    (() => {
        const branchData = @json($dashboard['charts']['branches']);
        const dailyData = @json($dashboard['charts']['daily']);

        const drawBarChart = (canvasId, data, valueKey, labelKey, color) => {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            if (!ctx) return;
            const rect = canvas.getBoundingClientRect();
            canvas.width = rect.width * window.devicePixelRatio;
            canvas.height = 220 * window.devicePixelRatio;
            ctx.scale(window.devicePixelRatio, window.devicePixelRatio);
            ctx.clearRect(0, 0, rect.width, 220);

            const padding = { top: 20, right: 16, bottom: 44, left: 42 };
            const width = rect.width - padding.left - padding.right;
            const height = 220 - padding.top - padding.bottom;
            const maxValue = Math.max(...data.map(item => item[valueKey] || 0), 1);
            const barWidth = data.length > 0 ? width / data.length - 10 : width;

            ctx.fillStyle = '#64748b';
            ctx.font = '12px system-ui, sans-serif';
            ctx.textAlign = 'center';

            data.forEach((item, index) => {
                const value = item[valueKey] || 0;
                const barHeight = (value / maxValue) * height;
                const x = padding.left + index * (barWidth + 10);
                const y = padding.top + (height - barHeight);

                ctx.fillStyle = color;
                ctx.fillRect(x, y, barWidth, barHeight);
                ctx.fillStyle = '#0f172a';
                ctx.fillText(String(value), x + barWidth / 2, y - 6);
                ctx.save();
                ctx.translate(x + barWidth / 2, 206);
                ctx.rotate(-Math.PI / 8);
                ctx.fillText(String(item[labelKey] || ''), 0, 0);
                ctx.restore();
            });
        };

        drawBarChart('branch-chart', branchData, 'total', 'label', '#dc2626');
        drawBarChart('daily-chart', dailyData, 'amount', 'day', '#16a34a');
        window.addEventListener('resize', () => {
            drawBarChart('branch-chart', branchData, 'total', 'label', '#dc2626');
            drawBarChart('daily-chart', dailyData, 'amount', 'day', '#16a34a');
        });
    })();
    </script>
    @endpush
@endsection
