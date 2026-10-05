<aside class="sidebar">
    <div class="sidebar-section">
        <p class="sidebar-title">Navegación</p>
        <nav class="sidebar-nav">
            @php($u = auth()->user())

            {{-- Super admin --}}
            @if($u?->isAdmin())
                <a href="{{ route('admin.dashboard') }}" @class(['active' => request()->routeIs('admin.dashboard')])>
                    Dashboard
                </a>
                <a href="{{ route('admin.invoice-backoffice') }}" @class(['active' => request()->routeIs('admin.invoice-backoffice')])>
                    Configuración
                </a>
            @endif

            {{-- Module: Malta Vigor --}}
            @if($u?->canAccessModule('malta'))
                @php($maltaCampaign = \App\Models\Campaign::query()->where('slug', 'malta-vigor')->where('participation_mode', 'product_ranking')->first())
                @if($maltaCampaign)
                    <a href="{{ route('admin.campaigns.product-ranking.operations', $maltaCampaign) }}" @class(['active' => request()->routeIs('admin.campaigns.product-ranking.operations') && request()->route('campaign')?->id === $maltaCampaign->id])>
                        Malta Vigor <small>Usuarios, ranking y facturas</small>
                    </a>
                @endif
            @endif

            {{-- Module: Saba --}}
            @if($u?->canAccessModule('saba'))
                <a href="{{ route('admin.saba') }}" @class(['active' => request()->routeIs('admin.saba*')])>
                    Saba <small>Emprende tu Sueño · participantes y SKUs</small>
                </a>
            @endif

            {{-- Module: NIVEA --}}
            @if($u?->canAccessModule('nivea'))
                <a href="{{ route('admin.nivea') }}" @class(['active' => request()->routeIs('admin.nivea*')])>
                    NIVEA <small>Empollerando a Mamá · participantes y SKUs</small>
                </a>
            @endif

            {{-- Module: Facturas --}}
            @if($u?->canAccessModule('facturas'))
                <a href="{{ route('admin.invoices') }}" @class(['active' => request()->routeIs('admin.invoices')])>
                    Facturas
                </a>
                <a href="{{ route('admin.winners') }}" @class(['active' => request()->routeIs('admin.winners')])>
                    Ganadores
                </a>
            @endif

            {{-- Module: Entrepreneurs --}}
            @if($u?->canAccessModule('entrepreneurs'))
                <a href="{{ route('admin.entrepreneurs') }}" @class(['active' => request()->routeIs('admin.entrepreneurs*')])>
                    Del sueño al puesto
                </a>
            @endif

            {{-- Module: Fonda --}}
            @if($u?->canAccessModule('fonda'))
                <a href="{{ route('admin.fonda-challenge') }}" @class(['active' => request()->routeIs('admin.fonda-challenge')])>
                    Fonda Challenge
                </a>
                <a href="{{ route('admin.fonda-jury') }}" @class(['active' => request()->routeIs('admin.fonda-jury')])>
                    Jurado Fonda Challenge
                </a>
                <a href="{{ route('admin.jurors') }}" @class(['active' => request()->routeIs('admin.jurors')])>
                    Jurados
                </a>
            @endif

            {{-- Module: Blacklist --}}
            @if($u?->canAccessModule('blacklist'))
                <a href="{{ route('admin.blacklist') }}" @class(['active' => request()->routeIs('admin.blacklist*')])>
                    Blacklist
                </a>
            @endif

            {{-- Module: Fanlyc --}}
            @if($u?->canAccessModule('fanlyc'))
                <a href="{{ route('admin.fanlyc') }}" @class(['active' => request()->routeIs('admin.fanlyc') || request()->routeIs('admin.fanlyc.show') || request()->routeIs('admin.fanlyc.zones') || request()->routeIs('admin.fanlyc-staff')])>
                    Fanlyc
                </a>
            @endif

            {{-- Module: Auditoría --}}
            @if($u?->canAccessModule('audit'))
                <a href="{{ route('admin.audit') }}" @class(['active' => request()->routeIs('admin.audit')])>
                    Auditoría
                </a>
            @endif

            {{-- Super admin: operators management --}}
            @if($u?->isAdmin())
                <a href="{{ route('admin.operators') }}" @class(['active' => request()->routeIs('admin.operators*')])>
                    Operadores
                </a>
            @endif

            {{-- Jury members --}}
            @if($u?->isJury())
                <a href="{{ route('admin.fonda-jury') }}" @class(['active' => request()->routeIs('admin.fonda-jury')])>
                    Fonda Challenge · Evaluación
                </a>
            @endif

            {{-- Fanlyc staff · redemption terminals --}}
            @if($u?->isFanlycStaff())
                @foreach(\App\Models\FanlycZone::query()->where('is_active', true)->orderBy('sort_order')->get() as $fanlycZone)
                    <a href="{{ route('admin.fanlyc.redeem', $fanlycZone->code) }}" @class(['active' => request()->route('zoneCode') === $fanlycZone->code])>
                        Fanlyc · Canje {{ $fanlycZone->name }}
                    </a>
                @endforeach
            @endif

            {{-- Prize delivery (admin + supervisor) --}}
            @if($u?->isAdmin() || $u?->isSupervisor())
                <a href="{{ route('admin.prize-delivery') }}" @class(['active' => request()->routeIs('admin.prize-delivery')])>
                    Entrega de premio
                </a>
            @endif
        </nav>
    </div>

    @if(trim($__env->yieldContent('sidebar-actions')) !== '')
        <div class="sidebar-section">
            <p class="sidebar-title">Acciones</p>
            <nav class="sidebar-nav">
                @yield('sidebar-actions')
            </nav>
        </div>
    @endif

    <div class="sidebar-foot">
        Panel optimizado para escritorio y móvil.
    </div>
</aside>
