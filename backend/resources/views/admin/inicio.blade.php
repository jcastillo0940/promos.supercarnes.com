@extends('admin.layout')

@section('title', 'Inicio')
@section('subtitle', 'Mi trabajo')

@push('styles')
<style>
    .inicio-header { margin-bottom: 1.25rem; }
    .inicio-header h1 { font-size: 1.4rem; margin: 0 0 .2rem; }
    .inicio-header p { margin: 0; color: #64748b; font-size: .9rem; }

    .inicio-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; }

    .wi-card {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 16px;
        padding: 0; overflow: hidden;
    }
    .wi-card-head {
        padding: .85rem 1.1rem; border-bottom: 1px solid #f1f5f9;
        display: flex; align-items: center; justify-content: space-between; gap: .5rem;
    }
    .wi-card-head h3 { margin: 0; font-size: .9rem; color: #0f172a; }
    .wi-card-head p  { margin: 0; font-size: .76rem; color: #64748b; }
    .wi-badge {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 24px; height: 24px; padding: 0 .4rem;
        border-radius: 999px; font-size: .75rem; font-weight: 800;
        background: #fef2f2; color: #dc2626; flex-shrink: 0;
    }
    .wi-badge.ok { background: #f0fdf4; color: #16a34a; }
    .wi-badge.warn { background: #fffbeb; color: #d97706; }
    .wi-badge.muted { background: #f1f5f9; color: #475569; }

    .wi-links { display: grid; }
    .wi-links a {
        display: flex; align-items: center; justify-content: space-between; gap: .5rem;
        padding: .65rem 1.1rem; text-decoration: none; color: #334155;
        font-size: .875rem; font-weight: 600; border-bottom: 1px solid #f8fafc;
    }
    .wi-links a:last-child { border-bottom: none; }
    .wi-links a:hover { background: #f8fafc; color: #0f172a; }
    .wi-links a .wi-arrow { color: #94a3b8; font-size: .8rem; }
    .wi-links a .wi-count {
        font-size: .72rem; font-weight: 700; background: #fee2e2; color: #991b1b;
        border-radius: 999px; padding: .1rem .45rem; min-width: 20px; text-align: center;
    }
    .wi-links a .wi-count.ok { background: #dcfce7; color: #15803d; }

    .wi-stat-row {
        display: flex; align-items: center; justify-content: space-between;
        padding: .6rem 1.1rem; border-bottom: 1px solid #f8fafc; font-size: .875rem;
    }
    .wi-stat-row:last-child { border-bottom: none; }
    .wi-stat-row span { color: #475569; }
    .wi-stat-row strong { color: #0f172a; }

    .wi-empty { padding: 1.25rem 1.1rem; color: #94a3b8; font-size: .85rem; text-align: center; }
</style>
@endpush

@section('content')
@php($u = auth()->user())

<div class="inicio-header">
    <h1>Hola, {{ $u?->full_name ?? $u?->name }}</h1>
    <p>{{ now()->timezone('America/Panama')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}</p>
</div>

<div class="inicio-grid">

    {{-- ── Admin ──────────────────────────────────────────────────────── --}}
    @if($u?->isAdmin())

        {{-- Campañas activas --}}
        <div class="wi-card">
            <div class="wi-card-head">
                <div>
                    <h3>Promociones</h3>
                    <p>Estado actual de tus campañas</p>
                </div>
                <span class="wi-badge {{ ($stats['campaigns_active'] ?? 0) > 0 ? 'ok' : 'muted' }}">
                    {{ $stats['campaigns_active'] ?? 0 }}
                </span>
            </div>
            <div class="wi-links">
                <a href="{{ route('admin.invoice-backoffice') }}">
                    <span>{{ $stats['campaigns_active'] ?? 0 }} activa{{ ($stats['campaigns_active'] ?? 0) !== 1 ? 's' : '' }}</span>
                    <span class="wi-arrow">→</span>
                </a>
                <a href="{{ route('admin.dashboard') }}">
                    <span>Dashboard comparativo</span>
                    <span class="wi-arrow">→</span>
                </a>
            </div>
        </div>

        {{-- Revisiones pendientes --}}
        @php($totalPending = ($stats['saba_pending'] ?? 0) + ($stats['nivea_pending'] ?? 0) + ($stats['fonda_pending'] ?? 0))
        <div class="wi-card">
            <div class="wi-card-head">
                <div>
                    <h3>Revisiones pendientes</h3>
                    <p>Participaciones en revisión</p>
                </div>
                <span class="wi-badge {{ $totalPending > 0 ? '' : 'ok' }}">{{ $totalPending }}</span>
            </div>
            <div class="wi-links">
                @if(isset($stats['saba_pending']))
                    <a href="{{ route('admin.saba', ['status' => 'pending_review']) }}">
                        <span>Saba</span>
                        <span class="wi-count {{ $stats['saba_pending'] === 0 ? 'ok' : '' }}">{{ $stats['saba_pending'] }}</span>
                    </a>
                @endif
                @if(isset($stats['nivea_pending']))
                    <a href="{{ route('admin.nivea', ['status' => 'pending_review']) }}">
                        <span>NIVEA</span>
                        <span class="wi-count {{ $stats['nivea_pending'] === 0 ? 'ok' : '' }}">{{ $stats['nivea_pending'] }}</span>
                    </a>
                @endif
                @if(isset($stats['fonda_pending']))
                    <a href="{{ route('admin.fonda-challenge', ['status' => 'pending_review']) }}">
                        <span>Fonda Challenge</span>
                        <span class="wi-count {{ $stats['fonda_pending'] === 0 ? 'ok' : '' }}">{{ $stats['fonda_pending'] }}</span>
                    </a>
                @endif
                @if($totalPending === 0)
                    <div class="wi-empty">Sin revisiones pendientes ✓</div>
                @endif
            </div>
        </div>

        {{-- Alertas y premios --}}
        <div class="wi-card">
            <div class="wi-card-head">
                <div>
                    <h3>Alertas y entregas</h3>
                    <p>Fraude y premios pendientes de entrega</p>
                </div>
                @php($alertTotal = ($stats['fraud_open'] ?? 0) + ($stats['prize_pending'] ?? 0))
                <span class="wi-badge {{ $alertTotal > 0 ? '' : 'ok' }}">{{ $alertTotal }}</span>
            </div>
            <div class="wi-links">
                @php($maltaCampaign = \App\Models\Campaign::query()->where('participation_mode','product_ranking')->orderByRaw("CASE status WHEN 'active' THEN 1 WHEN 'paused' THEN 2 WHEN 'draft' THEN 3 ELSE 4 END")->first())
                @if($maltaCampaign)
                    <a href="{{ route('admin.campaigns.product-ranking.operations', $maltaCampaign) }}">
                        <span>Alertas de fraude Malta</span>
                        <span class="wi-count {{ ($stats['fraud_open'] ?? 0) === 0 ? 'ok' : '' }}">{{ $stats['fraud_open'] ?? 0 }}</span>
                    </a>
                @endif
                <a href="{{ route('admin.prize-delivery') }}">
                    <span>Premios pendientes de entrega</span>
                    <span class="wi-count {{ ($stats['prize_pending'] ?? 0) === 0 ? 'ok' : '' }}">{{ $stats['prize_pending'] ?? 0 }}</span>
                </a>
            </div>
        </div>

        {{-- Administración --}}
        <div class="wi-card">
            <div class="wi-card-head">
                <div><h3>Administración</h3><p>Control del sistema</p></div>
            </div>
            <div class="wi-links">
                <a href="{{ route('admin.operators') }}"><span>Operadores</span><span class="wi-arrow">→</span></a>
                <a href="{{ route('admin.blacklist') }}"><span>Blacklist</span><span class="wi-arrow">→</span></a>
                <a href="{{ route('admin.audit') }}"><span>Auditoría</span><span class="wi-arrow">→</span></a>
            </div>
        </div>

    {{-- ── Supervisor / Manager ────────────────────────────────────────── --}}
    @elseif($u?->isSupervisor())
        <div class="wi-card">
            <div class="wi-card-head">
                <div>
                    <h3>Entrega de premios</h3>
                    <p>Ganadores con premio pendiente</p>
                </div>
                <span class="wi-badge {{ ($stats['prize_pending'] ?? 0) > 0 ? '' : 'ok' }}">
                    {{ $stats['prize_pending'] ?? 0 }}
                </span>
            </div>
            <div class="wi-links">
                <a href="{{ route('admin.prize-delivery') }}">
                    <span>Ir a Entrega de premio</span>
                    <span class="wi-arrow">→</span>
                </a>
            </div>
        </div>

    {{-- ── Jurado ──────────────────────────────────────────────────────── --}}
    @elseif($u?->isJury())
        <div class="wi-card">
            <div class="wi-card-head">
                <div>
                    <h3>Fonda Challenge</h3>
                    <p>Evaluaciones asignadas a ti</p>
                </div>
                <span class="wi-badge {{ ($stats['jury_pending'] ?? 0) > 0 ? '' : 'ok' }}">
                    {{ $stats['jury_pending'] ?? 0 }}
                </span>
            </div>
            <div class="wi-links">
                <a href="{{ route('admin.fonda-jury') }}">
                    <span>{{ ($stats['jury_pending'] ?? 0) > 0 ? ($stats['jury_pending'] . ' evaluación' . (($stats['jury_pending'] ?? 0) !== 1 ? 'es' : '') . ' pendiente' . (($stats['jury_pending'] ?? 0) !== 1 ? 's' : '')) : 'Ir a mis evaluaciones' }}</span>
                    <span class="wi-arrow">→</span>
                </a>
            </div>
        </div>

    {{-- ── Staff Fanlyc ────────────────────────────────────────────────── --}}
    @elseif($u?->isFanlycStaff())
        <div class="wi-card">
            <div class="wi-card-head">
                <div><h3>Terminales de canje Fanlyc</h3><p>Selecciona tu zona de operación</p></div>
            </div>
            <div class="wi-links">
                @forelse(\App\Models\FanlycZone::query()->where('is_active', true)->orderBy('sort_order')->get() as $zone)
                    <a href="{{ route('admin.fanlyc.redeem', $zone->code) }}">
                        <span>{{ $zone->name }}</span>
                        <span class="wi-arrow">→</span>
                    </a>
                @empty
                    <div class="wi-empty">No hay zonas activas configuradas.</div>
                @endforelse
            </div>
        </div>

    {{-- ── Operador ────────────────────────────────────────────────────── --}}
    @elseif($u?->isOperator())

        @if(isset($stats['saba_pending']) || isset($stats['nivea_pending']) || isset($stats['fonda_pending']))
            <div class="wi-card">
                <div class="wi-card-head">
                    <div><h3>Revisiones pendientes</h3><p>Participaciones esperando revisión</p></div>
                    @php($opPending = ($stats['saba_pending'] ?? 0) + ($stats['nivea_pending'] ?? 0) + ($stats['fonda_pending'] ?? 0))
                    <span class="wi-badge {{ $opPending > 0 ? '' : 'ok' }}">{{ $opPending }}</span>
                </div>
                <div class="wi-links">
                    @isset($stats['saba_pending'])
                        <a href="{{ route('admin.saba', ['status' => 'pending_review']) }}">
                            <span>Saba — En revisión</span>
                            <span class="wi-count {{ $stats['saba_pending'] === 0 ? 'ok' : '' }}">{{ $stats['saba_pending'] }}</span>
                        </a>
                    @endisset
                    @isset($stats['nivea_pending'])
                        <a href="{{ route('admin.nivea', ['status' => 'pending_review']) }}">
                            <span>NIVEA — En revisión</span>
                            <span class="wi-count {{ $stats['nivea_pending'] === 0 ? 'ok' : '' }}">{{ $stats['nivea_pending'] }}</span>
                        </a>
                    @endisset
                    @isset($stats['fonda_pending'])
                        <a href="{{ route('admin.fonda-challenge', ['status' => 'pending_review']) }}">
                            <span>Fonda — En revisión</span>
                            <span class="wi-count {{ $stats['fonda_pending'] === 0 ? 'ok' : '' }}">{{ $stats['fonda_pending'] }}</span>
                        </a>
                    @endisset
                </div>
            </div>
        @endif

        @if(isset($stats['fraud_open']))
            <div class="wi-card">
                <div class="wi-card-head">
                    <div><h3>Alertas Malta Vigor</h3><p>Alertas antifraude abiertas</p></div>
                    <span class="wi-badge {{ $stats['fraud_open'] > 0 ? '' : 'ok' }}">{{ $stats['fraud_open'] }}</span>
                </div>
                @php($mc = \App\Models\Campaign::query()->where('participation_mode','product_ranking')->orderByRaw("CASE status WHEN 'active' THEN 1 WHEN 'paused' THEN 2 WHEN 'draft' THEN 3 ELSE 4 END")->first())
                @if($mc)
                    <div class="wi-links">
                        <a href="{{ route('admin.campaigns.product-ranking.operations', $mc) }}">
                            <span>Ir a operaciones</span>
                            <span class="wi-arrow">→</span>
                        </a>
                    </div>
                @endif
            </div>
        @endif

        {{-- Links directos a los módulos asignados --}}
        @foreach([
            'malta'         => ['Malta Vigor', null],
            'fanlyc'        => ['Fanlyc', 'admin.fanlyc'],
            'entrepreneurs' => ['Del sueño al puesto', 'admin.entrepreneurs'],
            'facturas'      => ['Facturas', 'admin.invoices'],
            'blacklist'     => ['Blacklist', 'admin.blacklist'],
            'audit'         => ['Auditoría', 'admin.audit'],
        ] as $mod => [$label, $routeName])
            @if($u->canAccessModule($mod) && !isset($stats[$mod . '_pending']) && $mod !== 'saba' && $mod !== 'nivea' && $mod !== 'fonda')
                <div class="wi-card">
                    <div class="wi-card-head"><div><h3>{{ $label }}</h3></div></div>
                    <div class="wi-links">
                        @if($mod === 'malta')
                            @php($mc2 = \App\Models\Campaign::query()->where('participation_mode','product_ranking')->orderByRaw("CASE status WHEN 'active' THEN 1 WHEN 'paused' THEN 2 WHEN 'draft' THEN 3 ELSE 4 END")->first())
                            @if($mc2)
                                <a href="{{ route('admin.campaigns.product-ranking.operations', $mc2) }}"><span>Operaciones</span><span class="wi-arrow">→</span></a>
                            @endif
                        @elseif($routeName)
                            <a href="{{ route($routeName) }}"><span>Ir a {{ $label }}</span><span class="wi-arrow">→</span></a>
                        @endif
                    </div>
                </div>
            @endif
        @endforeach

    @endif

</div>
@endsection
