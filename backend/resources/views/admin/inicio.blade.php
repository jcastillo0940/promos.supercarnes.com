@extends('admin.layout')

@section('title', 'Inicio')
@section('subtitle', 'Mi trabajo')

@section('content')
@php($u = auth()->user())

@push('styles')
<style>
    .inicio-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem; }
    .inicio-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 1.25rem 1.4rem; display: grid; gap: .5rem; }
    .inicio-card h3 { margin: 0; font-size: .95rem; color: #0f172a; }
    .inicio-card p { margin: 0; font-size: .85rem; color: #64748b; }
    .inicio-links { display: grid; gap: .35rem; margin-top: .5rem; }
    .inicio-links a { display: flex; align-items: center; gap: .5rem; padding: .6rem .75rem; border-radius: 10px; background: #f8fafc; border: 1px solid #e5e7eb; text-decoration: none; color: #0f172a; font-size: .875rem; font-weight: 600; }
    .inicio-links a:hover { background: #f1f5f9; }
    .inicio-links a .arrow { margin-left: auto; color: #94a3b8; }
    .inicio-greeting { margin-bottom: 1rem; }
    .inicio-greeting h1 { font-size: 1.4rem; margin: 0 0 .25rem; }
    .inicio-greeting p { margin: 0; color: #64748b; }
</style>
@endpush

<div class="inicio-greeting">
    <h1>Hola, {{ $u?->full_name ?? $u?->name }} 👋</h1>
    <p>{{ now()->timezone('America/Panama')->isoFormat('dddd, D [de] MMMM') }} · {{ ['admin' => 'Administrador', 'operator' => 'Operador', 'supervisor' => 'Supervisor', 'manager' => 'Supervisor', 'cashier' => 'Cajero', 'jurado' => 'Jurado', 'staff_fanlyc' => 'Staff Fanlyc'][$u?->role] ?? ucfirst($u?->role ?? '') }}</p>
</div>

<div class="inicio-grid">

    {{-- Admin --}}
    @if($u?->isAdmin())
        <div class="inicio-card">
            <h3>Promociones</h3>
            <p>Gestiona campañas activas, pausadas y borradores.</p>
            <div class="inicio-links">
                <a href="{{ route('admin.invoice-backoffice') }}">Ver todas las promociones <span class="arrow">→</span></a>
                <a href="{{ route('admin.dashboard') }}">Dashboard comparativo <span class="arrow">→</span></a>
            </div>
        </div>
        <div class="inicio-card">
            <h3>Reportes</h3>
            <p>Facturas registradas, ganadores seleccionados y entregas.</p>
            <div class="inicio-links">
                <a href="{{ route('admin.invoices') }}">Facturas <span class="arrow">→</span></a>
                <a href="{{ route('admin.winners') }}">Ganadores <span class="arrow">→</span></a>
                <a href="{{ route('admin.prize-delivery') }}">Entrega de premios <span class="arrow">→</span></a>
            </div>
        </div>
        <div class="inicio-card">
            <h3>Control y seguridad</h3>
            <p>Blacklist, auditoría y operadores del sistema.</p>
            <div class="inicio-links">
                <a href="{{ route('admin.blacklist') }}">Blacklist <span class="arrow">→</span></a>
                <a href="{{ route('admin.audit') }}">Auditoría <span class="arrow">→</span></a>
                <a href="{{ route('admin.operators') }}">Operadores <span class="arrow">→</span></a>
            </div>
        </div>
    @endif

    {{-- Supervisor / Manager --}}
    @if($u?->isSupervisor())
        <div class="inicio-card">
            <h3>Entrega de premios</h3>
            <p>Busca ganadores y confirma la entrega de su premio.</p>
            <div class="inicio-links">
                <a href="{{ route('admin.prize-delivery') }}">Ir a Entrega de premio <span class="arrow">→</span></a>
            </div>
        </div>
    @endif

    {{-- Jurado --}}
    @if($u?->isJury())
        <div class="inicio-card">
            <h3>Fonda Challenge · Evaluación</h3>
            <p>Revisa y evalúa las participaciones que te fueron asignadas.</p>
            <div class="inicio-links">
                <a href="{{ route('admin.fonda-jury') }}">Ir a mis evaluaciones <span class="arrow">→</span></a>
            </div>
        </div>
    @endif

    {{-- Staff Fanlyc --}}
    @if($u?->isFanlycStaff())
        <div class="inicio-card">
            <h3>Terminales de canje Fanlyc</h3>
            <p>Selecciona la zona de canje en la que estás operando.</p>
            <div class="inicio-links">
                @foreach(\App\Models\FanlycZone::query()->where('is_active', true)->orderBy('sort_order')->get() as $zone)
                    <a href="{{ route('admin.fanlyc.redeem', $zone->code) }}">{{ $zone->name }} <span class="arrow">→</span></a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Operator: accesos directos según módulos --}}
    @if($u?->isOperator())
        @if($u->canAccessModule('malta'))
            @php($maltaCampaign = \App\Models\Campaign::query()->where('participation_mode','product_ranking')->orderByRaw("CASE status WHEN 'active' THEN 1 WHEN 'paused' THEN 2 WHEN 'draft' THEN 3 ELSE 4 END")->first())
            @if($maltaCampaign)
                <div class="inicio-card">
                    <h3>{{ $maltaCampaign->name }}</h3>
                    <p>Ranking, operaciones y facturas.</p>
                    <div class="inicio-links">
                        <a href="{{ route('admin.campaigns.product-ranking.operations', $maltaCampaign) }}">Ir a operaciones <span class="arrow">→</span></a>
                    </div>
                </div>
            @endif
        @endif
        @foreach(['saba' => ['Saba', 'admin.saba'], 'nivea' => ['NIVEA', 'admin.nivea'], 'fanlyc' => ['Fanlyc', 'admin.fanlyc'], 'fonda' => ['Fonda Challenge', 'admin.fonda-challenge'], 'entrepreneurs' => ['Del sueño al puesto', 'admin.entrepreneurs'], 'facturas' => ['Facturas', 'admin.invoices'], 'blacklist' => ['Blacklist', 'admin.blacklist'], 'audit' => ['Auditoría', 'admin.audit']] as $mod => [$label, $routeName])
            @if($u->canAccessModule($mod))
                <div class="inicio-card">
                    <h3>{{ $label }}</h3>
                    <div class="inicio-links">
                        <a href="{{ route($routeName) }}">Ir a {{ $label }} <span class="arrow">→</span></a>
                    </div>
                </div>
            @endif
        @endforeach
    @endif

</div>
@endsection
