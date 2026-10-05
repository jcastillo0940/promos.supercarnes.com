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
@endphp

<aside class="sidebar" id="sidebar-nav" aria-label="Menú de navegación">

    {{-- ── Selector de contexto ───────────────────────────────────────────── --}}
    <div class="ctx-selector">
        <button class="ctx-btn" id="ctx-btn" aria-expanded="false" aria-controls="ctx-dropdown"
                aria-label="{{ $promoCtx ? $promoCtx['label'] : 'Vista general' }}">
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
            <span class="ctx-chevron" aria-hidden="true">▾</span>
        </button>

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
    <nav class="sidebar-nav" aria-label="Navegación principal">

        @if($promoCtx)
            {{-- ── MODO PROMOCIÓN ──────────────────────────────────────────── --}}

            {{-- Malta Vigor --}}
            @if(str_starts_with($promoCtx['key'], 'malta-') && $u?->canAccessModule('malta'))
                @php($ctxCamp = request()->route('campaign') ?? \App\Models\Campaign::query()->where('participation_mode','product_ranking')->orderByRaw("CASE status WHEN 'active' THEN 1 WHEN 'paused' THEN 2 WHEN 'draft' THEN 3 ELSE 4 END")->first())
                @if($ctxCamp)
                    <a href="{{ route('admin.campaigns.product-ranking.operations', $ctxCamp) }}"
                       @class(['active' => request()->routeIs('admin.campaigns.product-ranking.operations')])
                       aria-current="{{ request()->routeIs('admin.campaigns.product-ranking.operations') ? 'page' : 'false' }}">
                        Operaciones <small>Ranking y control</small>
                    </a>
                    @if($u?->isAdmin())
                        <a href="{{ route('admin.invoices', ['campaign_id' => $ctxCamp->id]) }}"
                           @class(['active' => request()->routeIs('admin.invoices') && request('campaign_id') == $ctxCamp->id])
                           aria-current="{{ (request()->routeIs('admin.invoices') && request('campaign_id') == $ctxCamp->id) ? 'page' : 'false' }}">
                            Facturas <small>de esta promo</small>
                        </a>
                        <a href="{{ route('admin.invoice-backoffice.campaigns.edit', $ctxCamp) }}"
                           @class(['active' => request()->routeIs('admin.invoice-backoffice.campaigns.edit')])
                           aria-current="{{ request()->routeIs('admin.invoice-backoffice.campaigns.edit') ? 'page' : 'false' }}">
                            Configuración
                        </a>
                    @endif
                @endif
            @endif

            {{-- Saba --}}
            @if($promoCtx['key'] === 'saba' && $u?->canAccessModule('saba'))
                <a href="{{ route('admin.saba') }}"
                   @class(['active' => request()->routeIs('admin.saba') && !request()->routeIs('admin.saba.settings')])
                   aria-current="{{ (request()->routeIs('admin.saba') && !request()->routeIs('admin.saba.settings')) ? 'page' : 'false' }}">
                    Participantes
                </a>
                <a href="{{ route('admin.saba.export') }}"
                   aria-current="false">
                    Exportar <small>CSV de participantes</small>
                </a>
                @if($u?->isAdmin())
                    <a href="{{ route('admin.saba.settings') }}"
                       @class(['active' => request()->routeIs('admin.saba.settings')])
                       aria-current="{{ request()->routeIs('admin.saba.settings') ? 'page' : 'false' }}">
                        Configuración
                    </a>
                @endif
            @endif

            {{-- NIVEA --}}
            @if($promoCtx['key'] === 'nivea' && $u?->canAccessModule('nivea'))
                <a href="{{ route('admin.nivea') }}"
                   @class(['active' => request()->routeIs('admin.nivea') && !request()->routeIs('admin.nivea.settings')])
                   aria-current="{{ (request()->routeIs('admin.nivea') && !request()->routeIs('admin.nivea.settings')) ? 'page' : 'false' }}">
                    Participantes
                </a>
                <a href="{{ route('admin.nivea.export') }}"
                   aria-current="false">
                    Exportar <small>CSV de participantes</small>
                </a>
                @if($u?->isAdmin())
                    <a href="{{ route('admin.nivea.settings') }}"
                       @class(['active' => request()->routeIs('admin.nivea.settings')])
                       aria-current="{{ request()->routeIs('admin.nivea.settings') ? 'page' : 'false' }}">
                        Configuración
                    </a>
                @endif
            @endif

            {{-- Fanlyc --}}
            @if($promoCtx['key'] === 'fanlyc')
                @if($u?->canAccessModule('fanlyc'))
                    <a href="{{ route('admin.fanlyc') }}"
                       @class(['active' => request()->routeIs('admin.fanlyc') || request()->routeIs('admin.fanlyc.show')])
                       aria-current="{{ (request()->routeIs('admin.fanlyc') || request()->routeIs('admin.fanlyc.show')) ? 'page' : 'false' }}">
                        Facturas Fanlyc
                    </a>
                    <a href="{{ route('admin.fanlyc-staff') }}"
                       @class(['active' => request()->routeIs('admin.fanlyc-staff')])
                       aria-current="{{ request()->routeIs('admin.fanlyc-staff') ? 'page' : 'false' }}">
                        Staff
                    </a>
                    <a href="{{ route('admin.fanlyc.zones') }}"
                       @class(['active' => request()->routeIs('admin.fanlyc.zones')])
                       aria-current="{{ request()->routeIs('admin.fanlyc.zones') ? 'page' : 'false' }}">
                        Zonas de canje
                    </a>
                @endif
                @if($u?->isAdmin() || $u?->isFanlycStaff())
                    @foreach(\App\Models\FanlycZone::query()->where('is_active', true)->orderBy('sort_order')->get() as $fanlycZone)
                        <a href="{{ route('admin.fanlyc.redeem', $fanlycZone->code) }}"
                           @class(['active' => request()->route('zoneCode') === $fanlycZone->code])
                           aria-current="{{ request()->route('zoneCode') === $fanlycZone->code ? 'page' : 'false' }}">
                            Canje · {{ $fanlycZone->name }} <small>Terminal</small>
                        </a>
                    @endforeach
                @endif
            @endif

            {{-- Fonda Challenge --}}
            @if($promoCtx['key'] === 'fonda')
                @if($u?->canAccessModule('fonda'))
                    <a href="{{ route('admin.fonda-challenge') }}"
                       @class(['active' => request()->routeIs('admin.fonda-challenge') || request()->routeIs('admin.fonda-challenge.edit') || request()->routeIs('admin.fonda-challenge.status') || request()->routeIs('admin.fonda-challenge.approve') || request()->routeIs('admin.fonda-challenge.reject')])
                       aria-current="{{ request()->routeIs('admin.fonda-challenge*') ? 'page' : 'false' }}">
                        Participantes
                    </a>
                    <a href="{{ route('admin.fonda-challenge.ranking') }}"
                       @class(['active' => request()->routeIs('admin.fonda-challenge.ranking')])
                       aria-current="{{ request()->routeIs('admin.fonda-challenge.ranking') ? 'page' : 'false' }}">
                        Ranking
                    </a>
                    <a href="{{ route('admin.jurors') }}"
                       @class(['active' => request()->routeIs('admin.jurors')])
                       aria-current="{{ request()->routeIs('admin.jurors') ? 'page' : 'false' }}">
                        Jurados
                    </a>
                @endif
                @if($u?->isAdmin() || $u?->isJury() || $u?->canAccessModule('fonda'))
                    <a href="{{ route('admin.fonda-jury') }}"
                       @class(['active' => request()->routeIs('admin.fonda-jury')])
                       aria-current="{{ request()->routeIs('admin.fonda-jury') ? 'page' : 'false' }}">
                        Evaluación del jurado
                    </a>
                @endif
            @endif

            {{-- Emprendedores --}}
            @if($promoCtx['key'] === 'entrepreneurs' && $u?->canAccessModule('entrepreneurs'))
                <a href="{{ route('admin.entrepreneurs') }}"
                   @class(['active' => request()->routeIs('admin.entrepreneurs*')])
                   aria-current="{{ request()->routeIs('admin.entrepreneurs*') ? 'page' : 'false' }}">
                    Participantes
                </a>
                @if($u?->isAdmin())
                    <a href="{{ route('admin.entrepreneurs.export') }}"
                       aria-current="false">
                        Exportar <small>CSV de participantes</small>
                    </a>
                @endif
            @endif

            {{-- Volver a Vista general (solo admin) --}}
            @if($u?->isAdmin())
                <div class="sidebar-sep"></div>
                <a class="sidebar-back" href="{{ route('admin.invoice-backoffice') }}"
                   aria-current="false">
                    ← Vista general
                </a>
            @endif

        @else
            {{-- ── VISTA GENERAL ───────────────────────────────────────────── --}}

            {{-- Admin: inicio + dashboard + promociones --}}
            @if($u?->isAdmin())
                <a href="{{ route('admin.inicio') }}"
                   @class(['active' => request()->routeIs('admin.inicio')])
                   aria-current="{{ request()->routeIs('admin.inicio') ? 'page' : 'false' }}">
                    Inicio
                </a>
                <a href="{{ route('admin.dashboard') }}"
                   @class(['active' => request()->routeIs('admin.dashboard')])
                   aria-current="{{ request()->routeIs('admin.dashboard') ? 'page' : 'false' }}">
                    Dashboard
                </a>
                <a href="{{ route('admin.invoice-backoffice') }}"
                   @class(['active' => request()->routeIs('admin.invoice-backoffice') || request()->routeIs('admin.invoice-backoffice.campaigns.edit') || request()->routeIs('admin.invoice-backoffice.campaigns.store')])
                   aria-current="{{ request()->routeIs('admin.invoice-backoffice*') ? 'page' : 'false' }}">
                    Promociones
                </a>
            @endif

            {{-- Reportes (admin + facturas operator) --}}
            @if($u?->isAdmin() || $u?->canAccessModule('facturas'))
                <div class="sidebar-group">
                    <p class="sidebar-group-title">Reportes</p>
                    @if($u?->isAdmin() || $u?->canAccessModule('facturas'))
                        <a href="{{ route('admin.invoices') }}"
                           @class(['active' => request()->routeIs('admin.invoices')])
                           aria-current="{{ request()->routeIs('admin.invoices') ? 'page' : 'false' }}">
                            Facturas
                        </a>
                        <a href="{{ route('admin.winners') }}"
                           @class(['active' => request()->routeIs('admin.winners')])
                           aria-current="{{ request()->routeIs('admin.winners') ? 'page' : 'false' }}">
                            Ganadores
                        </a>
                    @endif
                    @if($u?->isAdmin() || $u?->isSupervisor())
                        <a href="{{ route('admin.prize-delivery') }}"
                           @class(['active' => request()->routeIs('admin.prize-delivery')])
                           aria-current="{{ request()->routeIs('admin.prize-delivery') ? 'page' : 'false' }}">
                            Entrega de premio
                        </a>
                    @endif
                </div>
            @elseif($u?->isSupervisor())
                <a href="{{ route('admin.prize-delivery') }}"
                   @class(['active' => request()->routeIs('admin.prize-delivery')])
                   aria-current="{{ request()->routeIs('admin.prize-delivery') ? 'page' : 'false' }}">
                    Entrega de premio
                </a>
            @endif

            {{-- Control (admin + blacklist/audit operators) --}}
            @if($u?->isAdmin() || $u?->canAccessModule('blacklist') || $u?->canAccessModule('audit'))
                <div class="sidebar-group">
                    <p class="sidebar-group-title">Control</p>
                    @if($u?->isAdmin() || $u?->canAccessModule('blacklist'))
                        <a href="{{ route('admin.blacklist') }}"
                           @class(['active' => request()->routeIs('admin.blacklist*')])
                           aria-current="{{ request()->routeIs('admin.blacklist*') ? 'page' : 'false' }}">
                            Blacklist
                        </a>
                    @endif
                    @if($u?->isAdmin() || $u?->canAccessModule('audit'))
                        <a href="{{ route('admin.audit') }}"
                           @class(['active' => request()->routeIs('admin.audit')])
                           aria-current="{{ request()->routeIs('admin.audit') ? 'page' : 'false' }}">
                            Auditoría
                        </a>
                    @endif
                </div>
            @endif

            {{-- Administración (admin only) --}}
            @if($u?->isAdmin())
                <div class="sidebar-group">
                    <p class="sidebar-group-title">Administración</p>
                    <a href="{{ route('admin.operators') }}"
                       @class(['active' => request()->routeIs('admin.operators*')])
                       aria-current="{{ request()->routeIs('admin.operators*') ? 'page' : 'false' }}">
                        Operadores
                    </a>
                    <a href="{{ route('admin.customers.history', 0) }}"
                       style="display:none"
                       aria-current="false">Clientes</a>
                </div>
            @endif

            {{-- Jurado (sin Vista General propia, solo aparece aquí si no tiene promo context) --}}
            @if($u?->isJury())
                <a href="{{ route('admin.fonda-jury') }}"
                   @class(['active' => request()->routeIs('admin.fonda-jury')])
                   aria-current="{{ request()->routeIs('admin.fonda-jury') ? 'page' : 'false' }}">
                    Fonda Challenge · Evaluación
                </a>
            @endif

            {{-- Staff Fanlyc --}}
            @if($u?->isFanlycStaff())
                @foreach(\App\Models\FanlycZone::query()->where('is_active', true)->orderBy('sort_order')->get() as $fanlycZone)
                    <a href="{{ route('admin.fanlyc.redeem', $fanlycZone->code) }}"
                       @class(['active' => request()->route('zoneCode') === $fanlycZone->code])
                       aria-current="{{ request()->route('zoneCode') === $fanlycZone->code ? 'page' : 'false' }}">
                        Fanlyc · Canje {{ $fanlycZone->name }}
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

    {{-- ── Pie del sidebar ────────────────────────────────────────────────── --}}
    <div class="sidebar-foot">
        @php($me = auth()->user())
        @if($me)
            <strong style="display:block;color:#e2e8f0;font-size:.82rem;margin-bottom:.2rem;">{{ $me->full_name ?? $me->name }}</strong>
            <span style="font-size:.75rem;">{{ [
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

</aside>
