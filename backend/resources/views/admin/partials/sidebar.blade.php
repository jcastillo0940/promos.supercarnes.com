<aside class="sidebar">
    <div class="sidebar-section">
        <p class="sidebar-title">NavegaciÃ³n</p>
        <nav class="sidebar-nav">
            @if(auth()->user()?->isAdmin())
                <a href="{{ route('admin.dashboard') }}" @class(['active' => request()->routeIs('admin.dashboard')])>
                    Dashboard
                </a>
                <a href="{{ route('admin.invoice-backoffice') }}" @class(['active' => request()->routeIs('admin.invoice-backoffice')])>
                    ConfiguraciÃ³n
                </a>
                @php($maltaCampaign = \App\Models\Campaign::query()->where('slug', 'malta-vigor')->where('participation_mode', 'product_ranking')->first())
                @if($maltaCampaign)
                    <a href="{{ route('admin.campaigns.product-ranking.operations', $maltaCampaign) }}" @class(['active' => request()->routeIs('admin.campaigns.product-ranking.operations') && request()->route('campaign')?->id === $maltaCampaign->id])>
                        Malta Vigor <small>Usuarios, ranking y facturas</small>
                    </a>
                @endif
                <a href="{{ route('admin.invoices') }}" @class(['active' => request()->routeIs('admin.invoices')])>
                    Facturas
                </a>
                <a href="{{ route('admin.winners') }}" @class(['active' => request()->routeIs('admin.winners')])>
                    Ganadores
                </a>
                <a href="{{ route('admin.entrepreneurs') }}" @class(['active' => request()->routeIs('admin.entrepreneurs*')])>
                    Del sueÃ±o al puesto
                </a>
                <a href="{{ route('admin.fonda-challenge') }}" @class(['active' => request()->routeIs('admin.fonda-challenge')])>
                    Fonda Challenge
                </a>
                <a href="{{ route('admin.fonda-jury') }}" @class(['active' => request()->routeIs('admin.fonda-jury')])>
                    Jurado Fonda Challenge
                </a>
                <a href="{{ route('admin.jurors') }}" @class(['active' => request()->routeIs('admin.jurors')])>
                    Jurados
                </a>
                <a href="{{ route('admin.blacklist') }}" @class(['active' => request()->routeIs('admin.blacklist*')])>
                    Blacklist
                </a>
                <a href="{{ route('admin.fanlyc') }}" @class(['active' => request()->routeIs('admin.fanlyc') || request()->routeIs('admin.fanlyc.show') || request()->routeIs('admin.fanlyc.zones') || request()->routeIs('admin.fanlyc-staff')])>
                    Fanlyc
                </a>
                <a href="{{ route('admin.audit') }}" @class(['active' => request()->routeIs('admin.audit')])>
                    AuditorÃ­a
                </a>
            @endif
            @if(auth()->user()?->isJury())
                <a href="{{ route('admin.fonda-jury') }}" @class(['active' => request()->routeIs('admin.fonda-jury')])>
                    Fonda Challenge · Evaluación
                </a>
            @endif
            @if(auth()->user()?->isFanlycStaff())
                @foreach(\App\Models\FanlycZone::query()->where('is_active', true)->orderBy('sort_order')->get() as $fanlycZone)
                    <a href="{{ route('admin.fanlyc.redeem', $fanlycZone->code) }}" @class(['active' => request()->route('zoneCode') === $fanlycZone->code])>
                        Fanlyc · Canje {{ $fanlycZone->name }}
                    </a>
                @endforeach
            @endif
            @if(auth()->user()?->isAdmin() || auth()->user()?->isSupervisor())
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
        Panel optimizado para escritorio y mÃ³vil.
    </div>
</aside>
