<aside class="sidebar">
    <div class="sidebar-section">
        <p class="sidebar-title">Navegación</p>
        <nav class="sidebar-nav">
            @if(auth()->user()?->isAdmin())
                <a href="{{ route('admin.dashboard') }}" @class(['active' => request()->routeIs('admin.dashboard')])>
                    Dashboard
                </a>
                <a href="{{ route('admin.invoice-backoffice') }}" @class(['active' => request()->routeIs('admin.invoice-backoffice')])>
                    Configuración
                </a>
                <a href="{{ route('admin.invoices') }}" @class(['active' => request()->routeIs('admin.invoices')])>
                    Facturas
                </a>
                <a href="{{ route('admin.winners') }}" @class(['active' => request()->routeIs('admin.winners')])>
                    Ganadores
                </a>
                <a href="{{ route('admin.entrepreneurs') }}" @class(['active' => request()->routeIs('admin.entrepreneurs*')])>
                    Del sueño al puesto
                </a>
                <a href="{{ route('admin.audit') }}" @class(['active' => request()->routeIs('admin.audit')])>
                    Auditoría
                </a>
            @endif
            <a href="{{ route('admin.prize-delivery') }}" @class(['active' => request()->routeIs('admin.prize-delivery')])>
                Entrega de premio
            </a>
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
