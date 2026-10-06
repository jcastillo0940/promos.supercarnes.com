{{--
  Partial: promo-dashboard-invoice
  Variables:
    $promoTitle    string   e.g. 'Saba · Emprende tu Sueño'
    $promoKey      string   e.g. 'saba' | 'nivea'
    $dashboardRoute string  e.g. 'admin.saba.dashboard'
    $listRoute     string   e.g. 'admin.saba'
    $campaign
    $period        int
    $total, $approved, $pending, $rejected, $winners
    $counts        Collection
    $daily         Collection  [{day, total, avg_amount}]
    $byBranch      Collection  [{branch, total}]
--}}
@push('styles')
<style>
    .kpi-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(165px,1fr)); gap:.85rem; }
    .kpi-card {
        position:relative; padding:1rem 1.1rem; border-radius:10px;
        background:var(--bo-surface); border:1px solid var(--bo-border); overflow:hidden;
    }
    .kpi-card::before {
        content:''; position:absolute; inset:0 auto 0 0; width:3px;
        background:var(--accent,var(--bo-accent));
    }
    .kpi-card strong { display:block; font-size:1.75rem; line-height:1.1; font-variant-numeric:tabular-nums; }
    .kpi-card span   { display:block; margin-top:.25rem; font-size:.78rem; color:var(--bo-fg2); font-weight:600; }
    .chart-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.85rem; }
    .chart-card { background:var(--bo-surface); border:1px solid var(--bo-border); border-radius:10px; padding:1rem 1.1rem 1.3rem; }
    .chart-card h3  { margin:0 0 .8rem; font-size:.92rem; font-weight:700; }
    .chart-canvas-wrap { position:relative; height:240px; }
    .chart-card.wide { grid-column:1/-1; }
    .period-pills { display:flex; gap:.4rem; flex-wrap:wrap; }
    .period-pill {
        padding:.3rem .75rem; border-radius:20px; font-size:.78rem; font-weight:600;
        text-decoration:none; border:1px solid var(--bo-border-dark); color:var(--bo-fg2);
        background:var(--bo-surface);
    }
    .period-pill.active { background:var(--bo-accent); color:#fff; border-color:var(--bo-accent); }
    @media(max-width:800px){ .chart-grid{grid-template-columns:1fr;} }
</style>
@endpush

@section('content')
    <div class="page-card">
        <div class="page-title" style="flex-wrap:wrap;gap:.75rem;">
            <div>
                <h1>Dashboard · {{ $promoTitle }}</h1>
                <p>{{ $campaign->name ?? $promoTitle }} · estado: <strong>{{ $campaign->status }}</strong></p>
            </div>
            <div style="display:flex;gap:.5rem;align-items:center;">
                <a href="{{ route($listRoute) }}" class="btn btn-gray" style="padding:.4rem .8rem;font-size:.8rem;">Ver participantes</a>
            </div>
        </div>

        <div class="page-section" style="border-top:1px solid var(--bo-border);">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem;margin-bottom:1rem;">
                <p style="margin:0;font-size:.82rem;font-weight:600;color:var(--bo-muted);text-transform:uppercase;letter-spacing:.05em;">Período</p>
                <div class="period-pills">
                    @foreach([7 => '7 días', 30 => '30 días', 90 => '90 días', 0 => 'Todo'] as $days => $label)
                        <a href="{{ route($dashboardRoute, ['period' => $days]) }}"
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
                    <span>Aprobadas</span>
                </div>
                <div class="kpi-card" style="--accent:#f59e0b;">
                    <strong>{{ number_format($pending) }}</strong>
                    <span>Pendientes revisión</span>
                </div>
                <div class="kpi-card" style="--accent:#dc2626;">
                    <strong>{{ number_format($rejected) }}</strong>
                    <span>Rechazadas</span>
                </div>
                <div class="kpi-card" style="--accent:#7c3aed;">
                    <strong>{{ number_format($winners) }}</strong>
                    <span>Ganadoras seleccionadas</span>
                </div>
            </div>
        </div>

        <div class="page-section" style="border-top:1px solid var(--bo-border);">
            <div class="chart-grid">
                <div class="chart-card wide">
                    <h3>Registros por día</h3>
                    <div class="chart-canvas-wrap"><canvas id="ch-daily"></canvas></div>
                </div>
                <div class="chart-card">
                    <h3>Por estado</h3>
                    <div class="chart-canvas-wrap"><canvas id="ch-status"></canvas></div>
                </div>
                <div class="chart-card">
                    <h3>Top sucursales</h3>
                    <div class="chart-canvas-wrap"><canvas id="ch-branch"></canvas></div>
                </div>
                <div class="chart-card wide">
                    <h3>Top productos / SKUs elegibles</h3>
                    <div class="chart-canvas-wrap" style="height:280px;"><canvas id="ch-skus"></canvas></div>
                </div>
                <div class="chart-card">
                    <h3>Unidades elegibles por registro</h3>
                    <div class="chart-canvas-wrap"><canvas id="ch-units"></canvas></div>
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

    const daily    = @json($daily);
    const counts   = @json($counts);
    const byBranch = @json($byBranch);
    const topSkus  = @json($topSkus ?? []);
    const byUnits  = @json($byUnits ?? []);

    const STATUS_LABELS = {
        'approved': 'Aprobadas',
        'pending_review': 'Pendientes',
        'rejected_issuer': 'Rechaz. emisor',
        'rejected_invalid_cufe': 'CUFE inválido',
        'rejected_sku_not_found': 'SKU no encontrado',
        'rejected_branch_not_in_promo': 'Sucursal fuera de promo',
        'rejected_manual': 'Rechaz. manual',
        'disqualified': 'Descalificada',
    };
    const STATUS_COLORS = {
        'approved': '#16a34a',
        'pending_review': '#f59e0b',
        'rejected_issuer': '#dc2626',
        'rejected_invalid_cufe': '#ef4444',
        'rejected_sku_not_found': '#f97316',
        'rejected_branch_not_in_promo': '#e11d48',
        'rejected_manual': '#b91c1c',
        'disqualified': '#7f1d1d',
    };
    const PALETTE = ['#C0122C','#2563eb','#16a34a','#ea580c','#9333ea','#0891b2','#ca8a04','#db2777'];
    const FONT = { family: 'system-ui, -apple-system, sans-serif', size: 11 };

    // Daily line chart
    new Chart(document.getElementById('ch-daily'), {
        type: 'bar',
        data: {
            labels: daily.map(r => r.day),
            datasets: [{
                label: 'Registros',
                data: daily.map(r => r.total),
                backgroundColor: '#C0122C',
                borderRadius: 5,
                borderSkipped: false,
            }],
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

    // Status donut
    const statusKeys = Object.keys(counts);
    new Chart(document.getElementById('ch-status'), {
        type: 'doughnut',
        data: {
            labels: statusKeys.map(k => STATUS_LABELS[k] || k),
            datasets: [{
                data: statusKeys.map(k => counts[k]),
                backgroundColor: statusKeys.map(k => STATUS_COLORS[k] || '#94a3b8'),
                borderWidth: 0,
            }],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            cutout: '65%',
            plugins: { legend: { position: 'bottom', labels: { font: FONT, boxWidth: 12, padding: 8 } } },
        },
    });

    // Top SKUs horizontal bar
    if (topSkus.length && document.getElementById('ch-skus')) {
        new Chart(document.getElementById('ch-skus'), {
            type: 'bar',
            data: {
                labels: topSkus.map(r => r.sku),
                datasets: [{
                    data: topSkus.map(r => r.total),
                    backgroundColor: topSkus.map((_, i) => PALETTE[i % PALETTE.length]),
                    borderRadius: 5,
                    borderSkipped: false,
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { font: FONT }, beginAtZero: true, grid: { display: false } },
                    y: { ticks: { font: { ...FONT, size: 10 }, maxTicksLimit: 12 } },
                },
            },
        });
    }

    // Eligible units bar
    if (byUnits.length && document.getElementById('ch-units')) {
        new Chart(document.getElementById('ch-units'), {
            type: 'bar',
            data: {
                labels: byUnits.map(r => r.eligible_units + ' und.'),
                datasets: [{
                    label: 'Registros',
                    data: byUnits.map(r => r.total),
                    backgroundColor: '#7c3aed',
                    borderRadius: 5,
                    borderSkipped: false,
                }],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { font: FONT }, grid: { display: false } },
                    y: { ticks: { font: FONT }, beginAtZero: true },
                },
            },
        });
    }

    // Branch horizontal bar
    new Chart(document.getElementById('ch-branch'), {
        type: 'bar',
        data: {
            labels: byBranch.map(r => r.branch),
            datasets: [{
                data: byBranch.map(r => r.total),
                backgroundColor: byBranch.map((_, i) => PALETTE[i % PALETTE.length]),
                borderRadius: 5,
                borderSkipped: false,
            }],
        },
        options: {
            indexAxis: 'y',
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { font: FONT }, beginAtZero: true, grid: { display: false } },
                y: { ticks: { font: FONT } },
            },
        },
    });
})();
</script>
@endpush
