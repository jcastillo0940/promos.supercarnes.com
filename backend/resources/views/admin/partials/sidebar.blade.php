@php
    $u = auth()->user();

    // ── Detectar contexto de promoción desde la ruta activa ──────────────────
    $promoCtx = null;

    if (request()->routeIs('admin.campaigns.product-ranking*')) {
        $ctxCampaign = request()->route('campaign');
        if ($ctxCampaign) {
            $promoCtx = [
                'key'    => 'malta-' . $ctxCampaign->id,
                'label'  => $ctxCampaign->name,
                'status' => $ctxCampaign->status,
                'type'   => 'Ranking/und.',
                'back'   => route('admin.invoice-backoffice'),
            ];
        }
    } elseif (request()->routeIs('admin.saba*')) {
        $promoCtx = ['key' => 'saba', 'label' => 'Saba', 'status' => null, 'type' => 'Umbral $', 'back' => route('admin.invoice-backoffice')];
    } elseif (request()->routeIs('admin.nivea*')) {
        $promoCtx = ['key' => 'nivea', 'label' => 'NIVEA', 'status' => null, 'type' => 'Umbral $', 'back' => route('admin.invoice-backoffice')];
    } elseif (request()->routeIs('admin.fanlyc*') || request()->routeIs('admin.fanlyc-staff') || request()->routeIs('admin.fanlyc.zones') || request()->routeIs('admin.fanlyc.redeem*')) {
        $promoCtx = ['key' => 'fanlyc', 'label' => 'Fanlyc', 'status' => null, 'type' => 'Puntos', 'back' => route('admin.invoice-backoffice')];
    } elseif (request()->routeIs('admin.fonda*') || request()->routeIs('admin.jurors')) {
        $promoCtx = ['key' => 'fonda', 'label' => 'Fonda Challenge', 'status' => null, 'type' => 'Jurado', 'back' => route('admin.invoice-backoffice')];
    } elseif (request()->routeIs('admin.entrepreneurs*')) {
        $promoCtx = ['key' => 'entrepreneurs', 'label' => 'Del sueño al puesto', 'status' => null, 'type' => 'Umbral', 'back' => route('admin.invoice-backoffice')];
    }

    // ── Lista de promos accesibles para el selector ──────────────────────────
    $ctxPromos = collect();

    if ($u?->canAccessModule('malta')) {
        foreach (\App\Models\Campaign::query()->where('participation_mode', 'product_ranking')->orderByRaw("CASE status WHEN 'active' THEN 1 WHEN 'paused' THEN 2 WHEN 'draft' THEN 3 ELSE 4 END")->orderBy('name')->get() as $c) {
            $ctxPromos->push(['key' => 'malta-' . $c->id, 'label' => $c->name, 'status' => $c->status, 'type' => 'Ranking/und.', 'href' => route('admin.campaigns.product-ranking.operations', $c)]);
        }
    }

    $modulePromos = [
        'saba'          => ['label' => 'Saba',               'type' => 'Umbral $',  'href' => route('admin.saba')],
        'nivea'         => ['label' => 'NIVEA',              'type' => 'Umbral $',  'href' => route('admin.nivea')],
        'fanlyc'        => ['label' => 'Fanlyc',             'type' => 'Puntos',    'href' => route('admin.fanlyc')],
        'fonda'         => ['label' => 'Fonda Challenge',    'type' => 'Jurado',    'href' => route('admin.fonda-challenge')],
        'entrepreneurs' => ['label' => 'Del sueño al puesto','type' => 'Umbral',    'href' => route('admin.entrepreneurs')],
    ];
    foreach ($modulePromos as $mod => $info) {
        if ($u?->canAccessModule($mod)) {
            $ctxPromos->push(array_merge($info, ['key' => $mod, 'status' => null]));
        }
    }

    $statusLabel = ['active' => 'Activa', 'paused' => 'Pausada', 'draft' => 'Borrador', 'archived' => 'Archivada'];
    $statusClass = ['active' => 'ctx-active', 'paused' => 'ctx-paused', 'draft' => 'ctx-draft', 'archived' => 'ctx-archived'];

    // ctx-btn no interactivo si solo hay una promo accesible
    $ctxSingle = $ctxPromos->count() <= 1;

    // ── Helper de íconos SVG (Lucide-style) ──────────────────────────────────
    $icoPaths = [
        'home'       => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
        'bar-chart2' => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
        'bar-chart'  => '<line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/>',
        'tag'        => '<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/>',
        'file-text'  => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>',
        'award'      => '<circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/>',
        'gift'       => '<polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/>',
        'shield'     => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'activity'   => '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>',
        'users'      => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'list'       => '<line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>',
        'download'   => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',
        'settings'   => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
        'map-pin'    => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
        'terminal'   => '<polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/>',
        'clipboard'  => '<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>',
        'user-check' => '<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><polyline points="17 11 19 13 23 9"/>',
        'zap'        => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',
        'chevron-left'  => '<polyline points="15 18 9 12 15 6"/>',
        'chevrons-left' => '<polyline points="11 17 6 12 11 7"/><polyline points="17 17 12 12 17 7"/>',
        'grid'          => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>',
    ];
    $ico = fn(string $n) => '<svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($icoPaths[$n] ?? '').'</svg>';
@endphp

<aside class="sidebar" id="sidebar-nav" aria-label="Menú de navegación">

    {{-- ── Logotipo ────────────────────────────────────────────────────────── --}}
    <div class="sidebar-logo">
        <span class="sidebar-logo-mark" aria-hidden="true">SC</span>
        <div>
            <div class="sidebar-logo-name">Super Carnes</div>
            <div class="sidebar-logo-sub">Panel administrativo</div>
        </div>
    </div>

    {{-- ── Selector de contexto ───────────────────────────────────────────── --}}
    <div class="ctx-selector">
        <button class="ctx-btn"
                {{ !$ctxSingle ? 'id=ctx-btn aria-expanded=false aria-controls=ctx-dropdown' : 'data-ctx-single' }}
                aria-label="{{ $promoCtx ? $promoCtx['label'] : 'Vista general' }}">
            <span class="ctx-avatar {{ $promoCtx ? '' : 'ctx-avatar-general' }}" aria-hidden="true">
                @if($promoCtx)
                    {{ strtoupper(mb_substr($promoCtx['label'], 0, 1)) }}
                @else
                    {!! $ico('grid') !!}
                @endif
            </span>
            <div class="ctx-info">
                @if($promoCtx)
                    <span class="ctx-name">{{ $promoCtx['label'] }}</span>
                    @if($promoCtx['status'])
                        <span class="ctx-badge {{ $statusClass[$promoCtx['status']] ?? 'ctx-draft' }}">
                            {{ $statusLabel[$promoCtx['status']] ?? $promoCtx['status'] }}
                        </span>
                    @else
                        <span class="ctx-type">{{ $promoCtx['type'] }}</span>
                    @endif
                @else
                    <span class="ctx-name ctx-general-name">Vista general</span>
                @endif
            </div>
            @if(!$ctxSingle)
                <span class="ctx-chevron" aria-hidden="true">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </span>
            @endif
        </button>

        @if(!$ctxSingle)
            <div class="ctx-kbd">
                <span>Cambiar promo</span>
                <kbd>Ctrl</kbd><kbd>K</kbd>
            </div>
        @endif

        <div class="ctx-dropdown" id="ctx-dropdown" hidden role="listbox" aria-label="Seleccionar contexto">
            <div class="ctx-search-wrap">
                <input class="ctx-search" id="ctx-search" type="search"
                       placeholder="Buscar promoción…" aria-label="Buscar promoción" autocomplete="off">
            </div>
            <a class="ctx-item ctx-item-general" href="{{ route('admin.inicio') }}" role="option">
                <span class="ctx-item-icon">⊞</span>
                <span>Vista general</span>
            </a>
            @if($ctxPromos->isNotEmpty())
                <div class="ctx-divider"></div>
                @foreach($ctxPromos as $promo)
                    <a class="ctx-item {{ $promoCtx && $promoCtx['key'] === $promo['key'] ? 'ctx-item-current' : '' }}"
                       href="{{ $promo['href'] }}"
                       role="option"
                       data-search="{{ strtolower($promo['label'] . ' ' . $promo['type']) }}"
                       {{ $promoCtx && $promoCtx['key'] === $promo['key'] ? 'aria-selected=true' : '' }}>
                        <span class="ctx-dot {{ $statusClass[$promo['status'] ?? ''] ?? '' }}" aria-hidden="true">●</span>
                        <span class="ctx-item-label">
                            {{ $promo['label'] }}
                            <small>{{ $promo['type'] }}</small>
                        </span>
                    </a>
                @endforeach
            @endif
        </div>
    </div>

    {{-- ── Navegación ─────────────────────────────────────────────────────── --}}
    <div class="sidebar-nav-wrap">
        <nav class="sidebar-nav" aria-label="Navegación principal">

            @if($promoCtx)
                {{-- ── MODO PROMOCIÓN ──────────────────────────────────────────── --}}

                {{-- Malta Vigor --}}
                @if(str_starts_with($promoCtx['key'], 'malta-') && $u?->canAccessModule('malta'))
                    @php($ctxCamp = request()->route('campaign') ?? \App\Models\Campaign::query()->where('participation_mode','product_ranking')->orderByRaw("CASE status WHEN 'active' THEN 1 WHEN 'paused' THEN 2 WHEN 'draft' THEN 3 ELSE 4 END")->first())
                    @if($ctxCamp)
                        <a href="{{ route('admin.campaigns.product-ranking.operations', $ctxCamp) }}"
                           title="Operaciones"
                           @class(['active' => request()->routeIs('admin.campaigns.product-ranking.operations')])
                           aria-current="{{ request()->routeIs('admin.campaigns.product-ranking.operations') ? 'page' : 'false' }}">
                            {!! $ico('activity') !!}
                            <span class="nav-label">Operaciones <small>Ranking y control</small></span>
                        </a>
                        @if($u?->isAdmin())
                            <a href="{{ route('admin.invoices', ['campaign_id' => $ctxCamp->id]) }}"
                               title="Facturas"
                               @class(['active' => request()->routeIs('admin.invoices') && request('campaign_id') == $ctxCamp->id])
                               aria-current="{{ (request()->routeIs('admin.invoices') && request('campaign_id') == $ctxCamp->id) ? 'page' : 'false' }}">
                                {!! $ico('file-text') !!}
                                <span class="nav-label">Facturas <small>de esta promo</small></span>
                            </a>
                            <a href="{{ route('admin.invoice-backoffice.campaigns.edit', $ctxCamp) }}"
                               title="Configuración"
                               @class(['active' => request()->routeIs('admin.invoice-backoffice.campaigns.edit')])
                               aria-current="{{ request()->routeIs('admin.invoice-backoffice.campaigns.edit') ? 'page' : 'false' }}">
                                {!! $ico('settings') !!}
                                <span class="nav-label">Configuración</span>
                            </a>
                        @endif
                    @endif
                @endif

                {{-- Saba --}}
                @if($promoCtx['key'] === 'saba' && $u?->canAccessModule('saba'))
                    <a href="{{ route('admin.saba') }}"
                       title="Participantes"
                       @class(['active' => request()->routeIs('admin.saba') && !request()->routeIs('admin.saba.settings')])
                       aria-current="{{ (request()->routeIs('admin.saba') && !request()->routeIs('admin.saba.settings')) ? 'page' : 'false' }}">
                        {!! $ico('list') !!}
                        <span class="nav-label">Participantes</span>
                    </a>
                    <a href="{{ route('admin.saba.export') }}"
                       title="Exportar"
                       aria-current="false">
                        {!! $ico('download') !!}
                        <span class="nav-label">Exportar <small>CSV de participantes</small></span>
                    </a>
                    @if($u?->isAdmin())
                        <a href="{{ route('admin.saba.settings') }}"
                           title="Configuración"
                           @class(['active' => request()->routeIs('admin.saba.settings')])
                           aria-current="{{ request()->routeIs('admin.saba.settings') ? 'page' : 'false' }}">
                            {!! $ico('settings') !!}
                            <span class="nav-label">Configuración</span>
                        </a>
                    @endif
                @endif

                {{-- NIVEA --}}
                @if($promoCtx['key'] === 'nivea' && $u?->canAccessModule('nivea'))
                    <a href="{{ route('admin.nivea') }}"
                       title="Participantes"
                       @class(['active' => request()->routeIs('admin.nivea') && !request()->routeIs('admin.nivea.settings')])
                       aria-current="{{ (request()->routeIs('admin.nivea') && !request()->routeIs('admin.nivea.settings')) ? 'page' : 'false' }}">
                        {!! $ico('list') !!}
                        <span class="nav-label">Participantes</span>
                    </a>
                    <a href="{{ route('admin.nivea.export') }}"
                       title="Exportar"
                       aria-current="false">
                        {!! $ico('download') !!}
                        <span class="nav-label">Exportar <small>CSV de participantes</small></span>
                    </a>
                    @if($u?->isAdmin())
                        <a href="{{ route('admin.nivea.settings') }}"
                           title="Configuración"
                           @class(['active' => request()->routeIs('admin.nivea.settings')])
                           aria-current="{{ request()->routeIs('admin.nivea.settings') ? 'page' : 'false' }}">
                            {!! $ico('settings') !!}
                            <span class="nav-label">Configuración</span>
                        </a>
                    @endif
                @endif

                {{-- Fanlyc --}}
                @if($promoCtx['key'] === 'fanlyc')
                    @if($u?->canAccessModule('fanlyc'))
                        <a href="{{ route('admin.fanlyc') }}"
                           title="Facturas Fanlyc"
                           @class(['active' => request()->routeIs('admin.fanlyc') || request()->routeIs('admin.fanlyc.show')])
                           aria-current="{{ (request()->routeIs('admin.fanlyc') || request()->routeIs('admin.fanlyc.show')) ? 'page' : 'false' }}">
                            {!! $ico('file-text') !!}
                            <span class="nav-label">Facturas Fanlyc</span>
                        </a>
                        <a href="{{ route('admin.fanlyc-staff') }}"
                           title="Staff"
                           @class(['active' => request()->routeIs('admin.fanlyc-staff')])
                           aria-current="{{ request()->routeIs('admin.fanlyc-staff') ? 'page' : 'false' }}">
                            {!! $ico('users') !!}
                            <span class="nav-label">Staff</span>
                        </a>
                        <a href="{{ route('admin.fanlyc.zones') }}"
                           title="Zonas de canje"
                           @class(['active' => request()->routeIs('admin.fanlyc.zones')])
                           aria-current="{{ request()->routeIs('admin.fanlyc.zones') ? 'page' : 'false' }}">
                            {!! $ico('map-pin') !!}
                            <span class="nav-label">Zonas de canje</span>
                        </a>
                    @endif
                    @if($u?->isAdmin() || $u?->isFanlycStaff())
                        @foreach(\App\Models\FanlycZone::query()->where('is_active', true)->orderBy('sort_order')->get() as $fanlycZone)
                            <a href="{{ route('admin.fanlyc.redeem', $fanlycZone->code) }}"
                               title="Canje · {{ $fanlycZone->name }}"
                               @class(['active' => request()->route('zoneCode') === $fanlycZone->code])
                               aria-current="{{ request()->route('zoneCode') === $fanlycZone->code ? 'page' : 'false' }}">
                                {!! $ico('terminal') !!}
                                <span class="nav-label">Canje · {{ $fanlycZone->name }} <small>Terminal</small></span>
                            </a>
                        @endforeach
                    @endif
                @endif

                {{-- Fonda Challenge --}}
                @if($promoCtx['key'] === 'fonda')
                    @if($u?->canAccessModule('fonda'))
                        <a href="{{ route('admin.fonda-challenge') }}"
                           title="Participantes"
                           @class(['active' => request()->routeIs('admin.fonda-challenge') || request()->routeIs('admin.fonda-challenge.edit') || request()->routeIs('admin.fonda-challenge.status') || request()->routeIs('admin.fonda-challenge.approve') || request()->routeIs('admin.fonda-challenge.reject')])
                           aria-current="{{ request()->routeIs('admin.fonda-challenge*') ? 'page' : 'false' }}">
                            {!! $ico('list') !!}
                            <span class="nav-label">Participantes</span>
                        </a>
                        <a href="{{ route('admin.fonda-challenge.ranking') }}"
                           title="Ranking"
                           @class(['active' => request()->routeIs('admin.fonda-challenge.ranking')])
                           aria-current="{{ request()->routeIs('admin.fonda-challenge.ranking') ? 'page' : 'false' }}">
                            {!! $ico('bar-chart') !!}
                            <span class="nav-label">Ranking</span>
                        </a>
                        <a href="{{ route('admin.jurors') }}"
                           title="Jurados"
                           @class(['active' => request()->routeIs('admin.jurors')])
                           aria-current="{{ request()->routeIs('admin.jurors') ? 'page' : 'false' }}">
                            {!! $ico('user-check') !!}
                            <span class="nav-label">Jurados</span>
                        </a>
                    @endif
                    @if($u?->isAdmin() || $u?->isJury() || $u?->canAccessModule('fonda'))
                        <a href="{{ route('admin.fonda-jury') }}"
                           title="Evaluación del jurado"
                           @class(['active' => request()->routeIs('admin.fonda-jury')])
                           aria-current="{{ request()->routeIs('admin.fonda-jury') ? 'page' : 'false' }}">
                            {!! $ico('clipboard') !!}
                            <span class="nav-label">Evaluación del jurado</span>
                        </a>
                    @endif
                @endif

                {{-- Emprendedores --}}
                @if($promoCtx['key'] === 'entrepreneurs' && $u?->canAccessModule('entrepreneurs'))
                    <a href="{{ route('admin.entrepreneurs') }}"
                       title="Participantes"
                       @class(['active' => request()->routeIs('admin.entrepreneurs*')])
                       aria-current="{{ request()->routeIs('admin.entrepreneurs*') ? 'page' : 'false' }}">
                        {!! $ico('list') !!}
                        <span class="nav-label">Participantes</span>
                    </a>
                    @if($u?->isAdmin())
                        <a href="{{ route('admin.entrepreneurs.export') }}"
                           title="Exportar"
                           aria-current="false">
                            {!! $ico('download') !!}
                            <span class="nav-label">Exportar <small>CSV de participantes</small></span>
                        </a>
                    @endif
                @endif

                {{-- Volver a Vista general (solo admin) --}}
                @if($u?->isAdmin())
                    <div class="sidebar-sep"></div>
                    <a class="sidebar-back" href="{{ route('admin.invoice-backoffice') }}"
                       title="Vista general"
                       aria-current="false">
                        {!! $ico('chevron-left') !!}
                        <span class="nav-label">Vista general</span>
                    </a>
                @endif

            @else
                {{-- ── VISTA GENERAL ───────────────────────────────────────────── --}}

                {{-- Admin: inicio + dashboard + promociones --}}
                @if($u?->isAdmin())
                    <a href="{{ route('admin.inicio') }}"
                       title="Inicio"
                       @class(['active' => request()->routeIs('admin.inicio')])
                       aria-current="{{ request()->routeIs('admin.inicio') ? 'page' : 'false' }}">
                        {!! $ico('home') !!}
                        <span class="nav-label">Inicio</span>
                    </a>
                    <a href="{{ route('admin.dashboard') }}"
                       title="Dashboard"
                       @class(['active' => request()->routeIs('admin.dashboard')])
                       aria-current="{{ request()->routeIs('admin.dashboard') ? 'page' : 'false' }}">
                        {!! $ico('bar-chart2') !!}
                        <span class="nav-label">Dashboard</span>
                    </a>
                    <a href="{{ route('admin.invoice-backoffice') }}"
                       title="Promociones"
                       @class(['active' => request()->routeIs('admin.invoice-backoffice') || request()->routeIs('admin.invoice-backoffice.campaigns.edit') || request()->routeIs('admin.invoice-backoffice.campaigns.store')])
                       aria-current="{{ request()->routeIs('admin.invoice-backoffice*') ? 'page' : 'false' }}">
                        {!! $ico('tag') !!}
                        <span class="nav-label">Promociones</span>
                    </a>
                @endif

                {{-- Reportes (admin + facturas operator) --}}
                @if($u?->isAdmin() || $u?->canAccessModule('facturas'))
                    <div class="sidebar-group">
                        <p class="sidebar-group-title">Reportes</p>
                        @if($u?->isAdmin() || $u?->canAccessModule('facturas'))
                            <a href="{{ route('admin.invoices') }}"
                               title="Facturas"
                               @class(['active' => request()->routeIs('admin.invoices')])
                               aria-current="{{ request()->routeIs('admin.invoices') ? 'page' : 'false' }}">
                                {!! $ico('file-text') !!}
                                <span class="nav-label">Facturas</span>
                            </a>
                            <a href="{{ route('admin.winners') }}"
                               title="Ganadores"
                               @class(['active' => request()->routeIs('admin.winners')])
                               aria-current="{{ request()->routeIs('admin.winners') ? 'page' : 'false' }}">
                                {!! $ico('award') !!}
                                <span class="nav-label">Ganadores</span>
                            </a>
                        @endif
                        @if($u?->isAdmin() || $u?->isSupervisor())
                            <a href="{{ route('admin.prize-delivery') }}"
                               title="Entrega de premio"
                               @class(['active' => request()->routeIs('admin.prize-delivery')])
                               aria-current="{{ request()->routeIs('admin.prize-delivery') ? 'page' : 'false' }}">
                                {!! $ico('gift') !!}
                                <span class="nav-label">Entrega de premio</span>
                            </a>
                        @endif
                    </div>
                @elseif($u?->isSupervisor())
                    <a href="{{ route('admin.prize-delivery') }}"
                       title="Entrega de premio"
                       @class(['active' => request()->routeIs('admin.prize-delivery')])
                       aria-current="{{ request()->routeIs('admin.prize-delivery') ? 'page' : 'false' }}">
                        {!! $ico('gift') !!}
                        <span class="nav-label">Entrega de premio</span>
                    </a>
                @endif

                {{-- Control (admin + blacklist/audit operators) --}}
                @if($u?->isAdmin() || $u?->canAccessModule('blacklist') || $u?->canAccessModule('audit'))
                    <div class="sidebar-group">
                        <p class="sidebar-group-title">Control</p>
                        @if($u?->isAdmin() || $u?->canAccessModule('blacklist'))
                            <a href="{{ route('admin.blacklist') }}"
                               title="Blacklist"
                               @class(['active' => request()->routeIs('admin.blacklist*')])
                               aria-current="{{ request()->routeIs('admin.blacklist*') ? 'page' : 'false' }}">
                                {!! $ico('shield') !!}
                                <span class="nav-label">Blacklist</span>
                            </a>
                        @endif
                        @if($u?->isAdmin() || $u?->canAccessModule('audit'))
                            <a href="{{ route('admin.audit') }}"
                               title="Auditoría"
                               @class(['active' => request()->routeIs('admin.audit')])
                               aria-current="{{ request()->routeIs('admin.audit') ? 'page' : 'false' }}">
                                {!! $ico('activity') !!}
                                <span class="nav-label">Auditoría</span>
                            </a>
                        @endif
                    </div>
                @endif

                {{-- Administración (admin only) --}}
                @if($u?->isAdmin())
                    <div class="sidebar-group">
                        <p class="sidebar-group-title">Administración</p>
                        <a href="{{ route('admin.operators') }}"
                           title="Operadores"
                           @class(['active' => request()->routeIs('admin.operators*')])
                           aria-current="{{ request()->routeIs('admin.operators*') ? 'page' : 'false' }}">
                            {!! $ico('users') !!}
                            <span class="nav-label">Operadores</span>
                        </a>
                        <a href="{{ route('admin.customers.history', 0) }}"
                           style="display:none"
                           aria-current="false">Clientes</a>
                    </div>
                @endif

                {{-- Jurado (sin Vista General propia, solo aparece aquí si no tiene promo context) --}}
                @if($u?->isJury())
                    <a href="{{ route('admin.fonda-jury') }}"
                       title="Fonda Challenge · Evaluación"
                       @class(['active' => request()->routeIs('admin.fonda-jury')])
                       aria-current="{{ request()->routeIs('admin.fonda-jury') ? 'page' : 'false' }}">
                        {!! $ico('clipboard') !!}
                        <span class="nav-label">Fonda Challenge · Evaluación</span>
                    </a>
                @endif

                {{-- Staff Fanlyc --}}
                @if($u?->isFanlycStaff())
                    @foreach(\App\Models\FanlycZone::query()->where('is_active', true)->orderBy('sort_order')->get() as $fanlycZone)
                        <a href="{{ route('admin.fanlyc.redeem', $fanlycZone->code) }}"
                           title="Fanlyc · Canje {{ $fanlycZone->name }}"
                           @class(['active' => request()->route('zoneCode') === $fanlycZone->code])
                           aria-current="{{ request()->route('zoneCode') === $fanlycZone->code ? 'page' : 'false' }}">
                            {!! $ico('zap') !!}
                            <span class="nav-label">Fanlyc · Canje {{ $fanlycZone->name }}</span>
                        </a>
                    @endforeach
                @endif

            @endif

            {{-- sidebar-actions slot (mantener compatibilidad con vistas existentes) --}}
            @if(trim($__env->yieldContent('sidebar-actions')) !== '')
                <div class="sidebar-sep"></div>
                @yield('sidebar-actions')
            @endif

        </nav>
    </div>

    {{-- ── Pie del sidebar ────────────────────────────────────────────────── --}}
    <div class="sidebar-foot">
        @php($me = auth()->user())
        @if($me)
            <strong>{{ $me->full_name ?? $me->name }}</strong>
            <span>{{ [
                'admin'        => 'Administrador',
                'operator'     => 'Operador',
                'supervisor'   => 'Supervisor',
                'manager'      => 'Supervisor',
                'cashier'      => 'Cajero',
                'jurado'       => 'Jurado',
                'staff_fanlyc' => 'Staff Fanlyc',
            ][$me->role] ?? ucfirst($me->role) }}</span>
        @endif
    </div>

    {{-- ── Botón colapsar ─────────────────────────────────────────────────── --}}
    <button class="sidebar-collapse-btn" id="sidebar-collapse-btn"
            aria-label="Colapsar menú" title="Colapsar menú" type="button">
        <svg class="nav-icon collapse-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="11 17 6 12 11 7"/><polyline points="17 17 12 12 17 7"/></svg>
        <span class="collapse-label">Colapsar</span>
    </button>

</aside>
