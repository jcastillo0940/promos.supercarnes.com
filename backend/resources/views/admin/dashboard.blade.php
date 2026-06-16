@extends('admin.layout')

@section('title', 'Dashboard')
@section('subtitle', 'Análisis general')

@section('topbar-actions')
    <a class="topbar-action hide-mobile" href="{{ route('admin.invoice-backoffice') }}">Configuración</a>
    <a class="topbar-action hide-mobile" href="{{ route('admin.invoices') }}">Facturas</a>
    <a class="topbar-action hide-mobile" href="{{ route('admin.winners') }}">Ganadores</a>
    <a class="topbar-action hide-mobile" href="{{ route('admin.prize-delivery') }}">Entrega de premio</a>
    <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit">Cerrar sesión</button>
    </form>
@endsection

@push('styles')
<style>
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        gap: 1rem;
    }
    .kpi-card {
        position: relative;
        padding: 1.1rem 1.2rem;
        border-radius: 16px;
        background: #fff;
        border: 1px solid #e5e7eb;
        overflow: hidden;
    }
    .kpi-card::before {
        content: '';
        position: absolute;
        inset: 0 auto 0 0;
        width: 4px;
        background: var(--accent, #dc2626);
    }
    .kpi-card strong { display:block; font-size: 1.9rem; line-height:1.1; color:#0f172a; }
    .kpi-card span { display:block; margin-top:.3rem; font-size:.8rem; color:#64748b; font-weight:600; }
    .chart-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }
    .chart-card {
        background:#fff;
        border:1px solid #e5e7eb;
        border-radius:16px;
        padding:1.1rem 1.1rem 1.4rem;
    }
    .chart-card h3 { margin:0 0 .9rem; font-size: .95rem; color:#0f172a; }
    .chart-card .chart-canvas-wrap { position: relative; height: 260px; }
    .chart-card.wide { grid-column: 1 / -1; }
    @media (max-width: 860px) {
        .chart-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

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
            <div class="kpi-grid">
                <div class="kpi-card" style="--accent:#dc2626;">
                    <strong>{{ number_format($dashboard['kpis']['winners']) }}</strong>
                    <span>Ganadores escogidos</span>
                </div>
                <div class="kpi-card" style="--accent:#16a34a;">
                    <strong>{{ number_format($dashboard['kpis']['delivered']) }}</strong>
                    <span>Premios entregados</span>
                </div>
                <div class="kpi-card" style="--accent:#2563eb;">
                    <strong>{{ $dashboard['kpis']['participation_pct'] }}%</strong>
                    <span>Tasa de participación ganadora</span>
                </div>
                <div class="kpi-card" style="--accent:#9333ea;">
                    <strong>{{ number_format($dashboard['kpis']['participants']) }}</strong>
                    <span>Personas participantes</span>
                </div>
                <div class="kpi-card" style="--accent:#ea580c;">
                    <strong>{{ number_format($dashboard['kpis']['non_winners']) }}</strong>
                    <span>Personas no ganadoras</span>
                </div>
                <div class="kpi-card" style="--accent:#0891b2;">
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
            <div class="chart-grid">
                <div class="chart-card">
                    <h3>Participación: ganadores vs no ganadores</h3>
                    <div class="chart-canvas-wrap">
                        <canvas id="winners-doughnut"></canvas>
                    </div>
                </div>
                <div class="chart-card">
                    <h3>Distribución de facturas por sucursal</h3>
                    <div class="chart-canvas-wrap">
                        <canvas id="branches-doughnut"></canvas>
                    </div>
                </div>
                <div class="chart-card wide">
                    <h3>Facturas y monto registrado · últimos 7 días</h3>
                    <div class="chart-canvas-wrap">
                        <canvas id="daily-combo"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script>
    (() => {
        const branchData = @json($dashboard['charts']['branches']);
        const dailyData = @json($dashboard['charts']['daily']);
        const winnersCount = {{ (int) $dashboard['kpis']['winners'] }};
        const nonWinnersCount = {{ (int) $dashboard['kpis']['non_winners'] }};

        if (typeof Chart === 'undefined') return;

        const palette = ['#dc2626', '#2563eb', '#16a34a', '#ea580c', '#9333ea', '#0891b2', '#ca8a04', '#db2777'];

        new Chart(document.getElementById('winners-doughnut'), {
            type: 'doughnut',
            data: {
                labels: ['Ganadores', 'No ganadores'],
                datasets: [{
                    data: [winnersCount, nonWinnersCount],
                    backgroundColor: ['#dc2626', '#e2e8f0'],
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: { legend: { position: 'bottom' } },
            },
        });

        new Chart(document.getElementById('branches-doughnut'), {
            type: 'doughnut',
            data: {
                labels: branchData.map(row => row.label),
                datasets: [{
                    data: branchData.map(row => row.total),
                    backgroundColor: branchData.map((_, i) => palette[i % palette.length]),
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: { legend: { position: 'bottom' } },
            },
        });

        new Chart(document.getElementById('daily-combo'), {
            data: {
                labels: dailyData.map(row => row.day),
                datasets: [
                    {
                        type: 'bar',
                        label: 'Facturas',
                        data: dailyData.map(row => row.total),
                        backgroundColor: '#dc2626',
                        borderRadius: 6,
                        yAxisID: 'y',
                    },
                    {
                        type: 'line',
                        label: 'Monto ($)',
                        data: dailyData.map(row => row.amount),
                        borderColor: '#16a34a',
                        backgroundColor: '#16a34a',
                        tension: .35,
                        yAxisID: 'y1',
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    y: { beginAtZero: true, position: 'left', title: { display: true, text: 'Facturas' } },
                    y1: { beginAtZero: true, position: 'right', title: { display: true, text: 'Monto ($)' }, grid: { drawOnChartArea: false } },
                },
            },
        });
    })();
    </script>
    @endpush
@endsection
