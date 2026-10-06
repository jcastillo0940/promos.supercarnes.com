@extends('admin.layout')

@section('title', 'Dashboard Fonda')
@section('subtitle', 'Fonda Challenge')

@push('styles')
<style>
    .kpi-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(165px,1fr)); gap:.85rem; }
    .kpi-card {
        position:relative; padding:1rem 1.1rem; border-radius:10px;
        background:var(--bo-surface); border:1px solid var(--bo-border); overflow:hidden;
    }
    .kpi-card::before { content:''; position:absolute; inset:0 auto 0 0; width:3px; background:var(--accent,var(--bo-accent)); }
    .kpi-card strong { display:block; font-size:1.75rem; line-height:1.1; font-variant-numeric:tabular-nums; }
    .kpi-card span   { display:block; margin-top:.25rem; font-size:.78rem; color:var(--bo-fg2); font-weight:600; }
    .chart-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.85rem; }
    .chart-card { background:var(--bo-surface); border:1px solid var(--bo-border); border-radius:10px; padding:1rem 1.1rem 1.3rem; }
    .chart-card h3  { margin:0 0 .8rem; font-size:.92rem; font-weight:700; }
    .chart-canvas-wrap { position:relative; height:240px; }
    .chart-card.wide { grid-column:1/-1; }
    .period-pills { display:flex; gap:.4rem; flex-wrap:wrap; }
    .period-pill { padding:.3rem .75rem; border-radius:20px; font-size:.78rem; font-weight:600; text-decoration:none; border:1px solid var(--bo-border-dark); color:var(--bo-fg2); background:var(--bo-surface); }
    .period-pill.active { background:var(--bo-accent); color:#fff; border-color:var(--bo-accent); }
    @media(max-width:800px){ .chart-grid{grid-template-columns:1fr;} }
</style>
@endpush

@section('content')
    <div class="page-card">
        <div class="page-title" style="flex-wrap:wrap;gap:.75rem;">
            <div>
                <h1>Dashboard · Fonda Challenge</h1>
                <p>{{ $campaign->name }} · estado: <strong>{{ $campaign->status }}</strong></p>
            </div>
            <a href="{{ route('admin.fonda-challenge') }}" class="btn btn-gray" style="padding:.4rem .8rem;font-size:.8rem;">Ver participantes</a>
        </div>

        <div class="page-section" style="border-top:1px solid var(--bo-border);">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem;margin-bottom:1rem;">
                <p style="margin:0;font-size:.82rem;font-weight:600;color:var(--bo-muted);text-transform:uppercase;letter-spacing:.05em;">Período</p>
                <div class="period-pills">
                    @foreach([7 => '7 días', 30 => '30 días', 90 => '90 días', 0 => 'Todo'] as $days => $label)
                        <a href="{{ route('admin.fonda-challenge.dashboard', ['period' => $days]) }}"
                           class="period-pill {{ $period == $days ? 'active' : '' }}">{{ $label }}</a>
                    @endforeach
                </div>
            </div>

            <div class="kpi-grid">
                <div class="kpi-card" style="--accent:#64748b;">
                    <strong>{{ number_format($total) }}</strong>
                    <span>Registros en período</span>
                </div>
                <div class="kpi-card" style="--accent:#16a34a;">
                    <strong>{{ number_format($approved) }}</strong>
                    <span>Aprobadas / en proceso</span>
                </div>
                <div class="kpi-card" style="--accent:#f59e0b;">
                    <strong>{{ number_format($pending) }}</strong>
                    <span>Pendientes revisión</span>
                </div>
                <div class="kpi-card" style="--accent:#2563eb;">
                    <strong>{{ number_format($checkedIn) }}</strong>
                    <span>Check-ins realizados</span>
                </div>
                <div class="kpi-card" style="--accent:#dc2626;">
                    <strong>{{ number_format($rejected) }}</strong>
                    <span>Rechazadas</span>
                </div>
            </div>
        </div>

        <div class="page-section" style="border-top:1px solid var(--bo-border);">
            <div class="chart-grid">
                <div class="chart-card wide">
                    <h3>Inscripciones por día</h3>
                    <div class="chart-canvas-wrap"><canvas id="ch-daily"></canvas></div>
                </div>
                <div class="chart-card">
                    <h3>Por estado</h3>
                    <div class="chart-canvas-wrap"><canvas id="ch-status"></canvas></div>
                </div>
                <div class="chart-card">
                    <h3>Check-ins por día</h3>
                    <div class="chart-canvas-wrap"><canvas id="ch-checkins"></canvas></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(() => {
    if (typeof Chart === 'undefined') return;

    const daily         = @json($daily);
    const counts        = @json($counts);
    const dailyCheckins = @json($dailyCheckins);

    const STATUS_LABELS = {
        'pending_review': 'Pendiente',
        'needs_correction': 'Corrección requerida',
        'approved': 'Aprobada',
        'checked_in': 'Check-in',
        'ready_for_judging': 'Lista para jurado',
        'rejected': 'Rechazada',
        'disqualified': 'Descalificada',
    };
    const STATUS_COLORS = {
        'approved': '#16a34a', 'checked_in': '#2563eb', 'ready_for_judging': '#0891b2',
        'pending_review': '#f59e0b', 'needs_correction': '#f97316',
        'rejected': '#dc2626', 'disqualified': '#7f1d1d',
    };
    const FONT = { family: 'system-ui,-apple-system,sans-serif', size: 11 };

    new Chart(document.getElementById('ch-daily'), {
        type: 'bar',
        data: {
            labels: daily.map(r => r.day),
            datasets: [{ label: 'Inscripciones', data: daily.map(r => r.total), backgroundColor: '#C0122C', borderRadius: 5, borderSkipped: false }],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { font: FONT, maxTicksLimit: 14 }, grid: { display: false } },
                y: { ticks: { font: FONT }, beginAtZero: true },
            },
        },
    });

    const statusKeys = Object.keys(counts);
    new Chart(document.getElementById('ch-status'), {
        type: 'doughnut',
        data: {
            labels: statusKeys.map(k => STATUS_LABELS[k] || k),
            datasets: [{ data: statusKeys.map(k => counts[k]), backgroundColor: statusKeys.map(k => STATUS_COLORS[k] || '#94a3b8'), borderWidth: 0 }],
        },
        options: {
            responsive: true, maintainAspectRatio: false, cutout: '65%',
            plugins: { legend: { position: 'bottom', labels: { font: FONT, boxWidth: 12, padding: 8 } } },
        },
    });

    new Chart(document.getElementById('ch-checkins'), {
        type: 'bar',
        data: {
            labels: dailyCheckins.map(r => r.day),
            datasets: [{ label: 'Check-ins', data: dailyCheckins.map(r => r.total), backgroundColor: '#2563eb', borderRadius: 5, borderSkipped: false }],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { font: FONT, maxTicksLimit: 14 }, grid: { display: false } },
                y: { ticks: { font: FONT }, beginAtZero: true },
            },
        },
    });
})();
</script>
@endpush
