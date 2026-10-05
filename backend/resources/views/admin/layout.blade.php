<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — Super Carnes</title>
    @stack('styles')
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #f3f4f6;
            color: #0f172a;
            min-height: 100vh;
        }
        a { color: inherit; }
        .app-shell { min-height: 100vh; }
        .sidebar-toggle { position: fixed; left: -9999px; }
        .topbar {
            position: sticky;
            top: 0;
            z-index: 40;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0 1rem;
            background: linear-gradient(90deg, #b91c1c, #ef4444);
            color: #fff;
            box-shadow: 0 8px 24px rgba(185, 28, 28, .18);
        }
        .topbar-left, .topbar-right { display: flex; align-items: center; gap: .75rem; }
        .topbar-ctx { display: flex; align-items: center; gap: .4rem; font-size: .82rem; opacity: .85; }
        .topbar-ctx-sep { opacity: .5; }
        .topbar-ctx-name { font-weight: 700; }
        .topbar-ctx-badge { font-size: .68rem; background: rgba(255,255,255,.15); border-radius: 999px; padding: .1rem .45rem; }
        /* ── User menu ──────────────────────────────────────────────────────── */
        .user-menu { position: relative; }
        .user-btn {
            display: flex; align-items: center; gap: .4rem;
            min-height: 42px; padding: 0 .75rem 0 .3rem;
            border-radius: 10px; border: 1px solid rgba(255,255,255,.2);
            background: rgba(255,255,255,.10); color: #fff;
            cursor: pointer; font: inherit; font-size: .875rem; font-weight: 600;
        }
        .user-btn:hover { background: rgba(255,255,255,.17); }
        .user-avatar {
            width: 28px; height: 28px; border-radius: 50%;
            background: rgba(255,255,255,.22); display: grid; place-items: center;
            font-size: .78rem; font-weight: 800; flex-shrink: 0;
        }
        .user-name { max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .user-chevron { font-size: .72rem; opacity: .7; }
        .user-btn[aria-expanded="true"] .user-chevron { transform: rotate(180deg); }
        .user-dropdown {
            position: absolute; top: calc(100% + 6px); right: 0; z-index: 200;
            background: #1e293b; border: 1px solid rgba(148,163,184,.2);
            border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,.35);
            min-width: 200px; overflow: hidden;
        }
        .user-dropdown-header { padding: .75rem 1rem; }
        .user-dropdown-header strong { display: block; color: #e2e8f0; font-size: .875rem; }
        .user-dropdown-header span { display: block; color: #64748b; font-size: .78rem; margin-top: .1rem; }
        .user-dropdown-sep { height: 1px; background: rgba(148,163,184,.15); }
        .user-dropdown-item {
            display: block; width: 100%; padding: .7rem 1rem;
            background: none; border: none; cursor: pointer;
            color: #cbd5e1; font: inherit; font-size: .875rem; font-weight: 600;
            text-align: left;
        }
        .user-dropdown-item:hover { background: rgba(255,255,255,.07); color: #fff; }
        .user-logout { color: #fca5a5; }
        .user-logout:hover { background: rgba(239,68,68,.15); color: #fca5a5; }
        .brand { display: flex; flex-direction: column; line-height: 1.1; }
        .brand strong { font-size: .98rem; }
        .brand span { font-size: .75rem; opacity: .85; }
        .sidebar-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border: 1px solid rgba(255,255,255,.25);
            border-radius: 10px;
            background: rgba(255,255,255,.10);
            color: #fff;
            cursor: pointer;
            user-select: none;
        }
        .sidebar-button span { font-size: 1.15rem; line-height: 1; }
        .topbar-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 0 .9rem;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,.22);
            background: rgba(255,255,255,.10);
            color: #fff;
            text-decoration: none;
            font-size: .875rem;
            font-weight: 600;
        }
        .topbar form { margin: 0; }
        .topbar button {
            min-height: 42px;
            padding: 0 .9rem;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,.22);
            background: rgba(255,255,255,.10);
            color: #fff;
            cursor: pointer;
            font-size: .875rem;
            font-weight: 600;
        }
        .layout {
            display: grid;
            grid-template-columns: 280px minmax(0, 1fr);
            min-height: calc(100vh - 64px);
        }
        .sidebar {
            background: #0f172a;
            color: #e2e8f0;
            padding: 1rem;
            border-right: 1px solid rgba(148, 163, 184, .15);
            position: sticky;
            top: 64px;
            height: calc(100vh - 64px);
            overflow-y: auto;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
        }
        /* ── Context selector ─────────────────────────────────────────────── */
        .ctx-selector { position: relative; margin-bottom: .75rem; }
        .ctx-btn {
            display: flex; align-items: center; justify-content: space-between; gap: .5rem;
            width: 100%; padding: .7rem .85rem;
            background: rgba(255,255,255,.08); border: 1px solid rgba(148,163,184,.2);
            border-radius: 12px; cursor: pointer; color: #e2e8f0; text-align: left;
        }
        .ctx-btn:hover { background: rgba(255,255,255,.13); }
        .ctx-info { display: flex; flex-direction: column; gap: .15rem; min-width: 0; }
        .ctx-name { font-size: .88rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .ctx-general-name { color: #94a3b8; }
        .ctx-type { font-size: .72rem; color: #94a3b8; }
        .ctx-badge { display: inline-block; font-size: .68rem; font-weight: 700; border-radius: 999px; padding: .1rem .45rem; }
        .ctx-active  { background: rgba(22,163,74,.25);  color: #86efac; }
        .ctx-paused  { background: rgba(100,116,139,.22); color: #94a3b8; }
        .ctx-draft   { background: rgba(234,179,8,.2);   color: #fde047; }
        .ctx-archived{ background: rgba(239,68,68,.18);  color: #fca5a5; }
        .ctx-chevron { font-size: .75rem; color: #64748b; flex-shrink: 0; transition: transform .15s; }
        .ctx-btn[aria-expanded="true"] .ctx-chevron { transform: rotate(180deg); }
        .ctx-dropdown {
            position: fixed; z-index: 400;
            background: #1e293b; border: 1px solid rgba(148,163,184,.2);
            border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,.35);
            overflow: hidden; max-height: 340px; display: flex; flex-direction: column;
        }
        .ctx-search-wrap { padding: .5rem; border-bottom: 1px solid rgba(148,163,184,.12); flex-shrink: 0; }
        .ctx-search {
            width: 100%; padding: .5rem .7rem; background: rgba(255,255,255,.07);
            border: 1px solid rgba(148,163,184,.18); border-radius: 8px;
            color: #e2e8f0; font: inherit; font-size: .85rem;
        }
        .ctx-search::placeholder { color: #64748b; }
        .ctx-search:focus { outline: none; border-color: rgba(239,68,68,.5); }
        .ctx-dropdown { overflow-y: auto; }
        .ctx-item {
            display: flex; align-items: center; gap: .6rem;
            padding: .65rem .85rem; text-decoration: none;
            color: #cbd5e1; font-size: .875rem; font-weight: 600;
        }
        .ctx-item:hover, .ctx-item:focus { background: rgba(255,255,255,.07); color: #fff; outline: none; }
        .ctx-item-current { background: rgba(239,68,68,.15); color: #fff; }
        .ctx-item-general { color: #94a3b8; font-weight: 700; }
        .ctx-item-icon { font-size: 1rem; width: 1.1rem; text-align: center; flex-shrink: 0; }
        .ctx-item-label { display: flex; flex-direction: column; gap: .05rem; }
        .ctx-item-label small { font-size: .7rem; font-weight: 400; color: #64748b; }
        .ctx-dot { font-size: .6rem; flex-shrink: 0; color: #475569; }
        .ctx-dot.ctx-active  { color: #4ade80; }
        .ctx-dot.ctx-paused  { color: #64748b; }
        .ctx-dot.ctx-draft   { color: #facc15; }
        .ctx-dot.ctx-archived{ color: #f87171; }
        .ctx-divider { height: 1px; background: rgba(148,163,184,.12); margin: .25rem 0; flex-shrink: 0; }
        .ctx-item[hidden] { display: none !important; }

        /* ── Sidebar nav ──────────────────────────────────────────────────── */
        .sidebar-section { margin-bottom: 1.25rem; }
        .sidebar-title {
            font-size: .72rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #94a3b8;
            margin: 0 0 .65rem;
        }
        .sidebar-nav { display: grid; gap: .25rem; padding: 0; }
        .sidebar-nav a, .sidebar-nav button {
            display: flex;
            flex-direction: column;
            gap: .1rem;
            width: 100%;
            padding: .7rem .85rem;
            border-radius: 12px;
            text-decoration: none;
            color: #cbd5e1;
            background: transparent;
            border: 0;
            font-size: .875rem;
            font-weight: 600;
            cursor: pointer;
            text-align: left;
        }
        .sidebar-nav a:hover, .sidebar-nav button:hover { background: rgba(148, 163, 184, .10); color: #fff; }
        .sidebar-nav a.active { background: rgba(239, 68, 68, .18); color: #fff; }
        .sidebar-nav small {
            font-size: .7rem;
            font-weight: 400;
            color: #64748b;
        }
        .sidebar-nav a.active small { color: rgba(255,255,255,.6); }
        .sidebar-group { margin-bottom: .25rem; }
        .sidebar-group-title {
            font-size: .65rem; letter-spacing: .1em; text-transform: uppercase;
            color: #475569; margin: .75rem 0 .2rem .85rem; padding: 0;
        }
        .sidebar-sep { height: 1px; background: rgba(148,163,184,.12); margin: .5rem 0; }
        .sidebar-back { color: #64748b !important; font-size: .82rem !important; }
        .sidebar-back:hover { color: #94a3b8 !important; }
        .sidebar-foot {
            margin-top: auto;
            padding-top: 1rem;
            border-top: 1px solid rgba(148, 163, 184, .14);
            color: #94a3b8;
            font-size: .8rem;
        }
        .content {
            min-width: 0;
            padding: 1rem;
        }
        .content-inner {
            max-width: 1400px;
            margin: 0 auto;
        }
        .page-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .06);
            overflow: hidden;
        }
        .page-section { padding: 1rem; }
        .page-title {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
            padding: 1rem 1rem 0;
        }
        .page-title h1, .page-title h2 { margin: 0; }
        .page-title p { margin: .25rem 0 0; color: #64748b; }
        .stack { display: grid; gap: 1rem; }
        .success, .error, .notice, .alert-success {
            padding: .9rem 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            border: 1px solid transparent;
        }
        .success, .alert-success { background: #f0fdf4; color: #166534; border-color: #bbf7d0; }
        .error { background: #fef2f2; color: #991b1b; border-color: #fecaca; }
        .notice { background: #fffbeb; color: #92400e; border-color: #fde68a; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            border-radius: 10px;
            padding: .7rem 1rem;
            font-size: .875rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            white-space: nowrap;
        }
        .btn-red { background: #dc2626; color: #fff; }
        .btn-gray { background: #e2e8f0; color: #0f172a; }
        .btn-green { background: #16a34a; color: #fff; }
        .btn-red:hover { background: #b91c1c; }
        .btn-gray:hover { background: #cbd5e1; }
        .btn-green:hover { background: #15803d; }
        .table-shell { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .table-shell table { min-width: 760px; }
        .table-shell table.wide { min-width: 1080px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: .75rem .9rem; border-bottom: 1px solid #e5e7eb; text-align: left; vertical-align: top; }
        th { background: #f8fafc; color: #334155; font-size: .78rem; text-transform: uppercase; letter-spacing: .05em; }
        .badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: .18rem .55rem;
            font-size: .72rem;
            font-weight: 700;
        }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-yellow { background: #fef9c3; color: #854d0e; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-gray { background: #e2e8f0; color: #334155; }
        .form-grid {
            display: grid;
            gap: 1rem;
        }
        .field label { display: block; font-size: .82rem; font-weight: 700; color: #334155; margin-bottom: .35rem; }
        .field input, .field select {
            width: 100%;
            min-height: 44px;
            padding: .65rem .8rem;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-size: .95rem;
            background: #fff;
        }
        .field input:focus, .field select:focus { outline: 2px solid rgba(220, 38, 38, .18); border-color: #dc2626; }
        .responsive-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
        .responsive-actions form, .responsive-actions a { margin: 0; }
        .empty {
            padding: 1.5rem 1rem;
            color: #94a3b8;
            font-size: .88rem;
            text-align: center;
            border: 1px dashed #e2e8f0;
            border-radius: 10px;
            background: #f8fafc;
        }
        .hide-mobile { display: block; }
        .show-mobile { display: none; }

        @media (max-width: 1024px) {
            .layout { grid-template-columns: 240px minmax(0, 1fr); }
        }
        @media (max-width: 900px) {
            .layout { grid-template-columns: 1fr; }
            .sidebar {
                position: fixed;
                inset: 64px auto 0 0;
                width: min(88vw, 300px);
                transform: translateX(-102%);
                transition: transform .2s ease;
                z-index: 60;
            }
            .sidebar-overlay {
                position: fixed;
                inset: 64px 0 0 0;
                background: rgba(15, 23, 42, .45);
                opacity: 0;
                pointer-events: none;
                transition: opacity .2s ease;
                z-index: 50;
            }
            .sidebar-toggle:checked ~ .layout .sidebar { transform: translateX(0); }
            .sidebar-toggle:checked ~ .sidebar-overlay {
                opacity: 1;
                pointer-events: auto;
            }
            .content { padding: .75rem; }
        }
        @media (max-width: 640px) {
            .topbar { padding: 0 .75rem; }
            .brand span { display: none; }
            .topbar-action, .topbar button { padding: 0 .75rem; }
            .hide-mobile { display: none; }
            .show-mobile { display: inline-flex; }
            .page-card { border-radius: 14px; }
        }
    </style>
</head>
<body>
    <input id="sidebar-toggle" class="sidebar-toggle" type="checkbox">
    <div class="app-shell">
        <header class="topbar">
            <div class="topbar-left">
                <label class="sidebar-button show-mobile" for="sidebar-toggle" aria-label="Abrir menú" aria-expanded="false" aria-controls="sidebar-nav" role="button">
                    <span>☰</span>
                </label>
                <div class="brand">
                    <strong>Super Carnes Admin</strong>
                    <span>@yield('subtitle', 'Backoffice')</span>
                </div>
                @hasSection('promo-context')
                    <div class="topbar-ctx hide-mobile">
                        <span class="topbar-ctx-sep">›</span>
                        @yield('promo-context')
                    </div>
                @endif
            </div>
            <div class="topbar-right">
                @yield('topbar-actions')
                {{-- Menú de usuario ──────────────────────────────────────────── --}}
                @auth
                <div class="user-menu" id="user-menu">
                    <button class="user-btn" id="user-btn" aria-expanded="false" aria-controls="user-dropdown" aria-label="Menú de usuario" type="button">
                        <span class="user-avatar" aria-hidden="true">{{ strtoupper(substr(auth()->user()->full_name ?? auth()->user()->name ?? 'U', 0, 1)) }}</span>
                        <span class="user-name hide-mobile">{{ auth()->user()->full_name ?? auth()->user()->name }}</span>
                        <span class="user-chevron hide-mobile" aria-hidden="true">▾</span>
                    </button>
                    <div class="user-dropdown" id="user-dropdown" hidden>
                        <div class="user-dropdown-header">
                            <strong>{{ auth()->user()->full_name ?? auth()->user()->name }}</strong>
                            <span>{{ ['admin' => 'Administrador', 'operator' => 'Operador', 'supervisor' => 'Supervisor', 'manager' => 'Supervisor', 'cashier' => 'Cajero', 'jurado' => 'Jurado', 'staff_fanlyc' => 'Staff Fanlyc'][auth()->user()->role] ?? ucfirst(auth()->user()->role) }}</span>
                        </div>
                        <div class="user-dropdown-sep"></div>
                        <form method="POST" action="{{ route('admin.logout') }}" style="margin:0;">
                            @csrf
                            <button type="submit" class="user-dropdown-item user-logout" data-no-loading-text>
                                Cerrar sesión
                            </button>
                        </form>
                    </div>
                </div>
                @endauth
            </div>
        </header>

        <div class="sidebar-overlay" aria-hidden="true"></div>

        <div class="layout">
            @include('admin.partials.sidebar')

            <main class="content">
                <div class="content-inner">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>
    @stack('scripts')
    <script>
    (function () {
        var flash = document.querySelector('.success, .error, .notice, .alert-success');
        if (flash) flash.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        var sidebarToggle = document.getElementById('sidebar-toggle');
        var sidebarLabel = document.querySelector('[aria-controls="sidebar-nav"]');
        if (sidebarToggle && sidebarLabel) {
            sidebarToggle.addEventListener('change', function () {
                sidebarLabel.setAttribute('aria-expanded', this.checked ? 'true' : 'false');
                sidebarLabel.setAttribute('aria-label', this.checked ? 'Cerrar menú' : 'Abrir menú');
            });
        }

        // ── Context selector ─────────────────────────────────────────────────
        (function () {
            var btn      = document.getElementById('ctx-btn');
            var dropdown = document.getElementById('ctx-dropdown');
            var search   = document.getElementById('ctx-search');
            if (!btn || !dropdown) return;

            function position() {
                var r = btn.getBoundingClientRect();
                dropdown.style.top   = (r.bottom + 4) + 'px';
                dropdown.style.left  = r.left + 'px';
                dropdown.style.width = r.width + 'px';
            }
            function open() {
                position();
                dropdown.hidden = false;
                btn.setAttribute('aria-expanded', 'true');
                if (search) { search.value = ''; filterItems(''); search.focus(); }
            }
            function close() {
                dropdown.hidden = true;
                btn.setAttribute('aria-expanded', 'false');
            }

            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                dropdown.hidden ? open() : close();
            });

            document.addEventListener('click', function (e) {
                if (!dropdown.hidden && !dropdown.contains(e.target) && e.target !== btn) close();
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !dropdown.hidden) { close(); btn.focus(); }
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    dropdown.hidden ? open() : close();
                }
            });
            window.addEventListener('resize', function () { if (!dropdown.hidden) position(); });
            var sidebar = document.getElementById('sidebar-nav');
            if (sidebar) sidebar.addEventListener('scroll', function () { if (!dropdown.hidden) position(); });

            function filterItems(term) {
                dropdown.querySelectorAll('.ctx-item[data-search]').forEach(function (item) {
                    item.hidden = term !== '' && !item.dataset.search.includes(term.toLowerCase());
                });
            }
            if (search) {
                search.addEventListener('input', function () { filterItems(this.value.trim()); });
            }

            // Save last visited promo context to localStorage
            dropdown.querySelectorAll('.ctx-item[data-search]').forEach(function (item) {
                item.addEventListener('click', function () {
                    try { localStorage.setItem('sc_last_ctx', item.href); } catch(e) {}
                });
            });
        })();

        // ── User menu ────────────────────────────────────────────────────────
        (function () {
            var btn = document.getElementById('user-btn');
            var dd  = document.getElementById('user-dropdown');
            if (!btn || !dd) return;
            function open() { dd.hidden = false; btn.setAttribute('aria-expanded', 'true'); }
            function close(){ dd.hidden = true;  btn.setAttribute('aria-expanded', 'false'); }
            btn.addEventListener('click', function (e) { e.stopPropagation(); dd.hidden ? open() : close(); });
            document.addEventListener('click', function (e) {
                if (!dd.hidden && !dd.contains(e.target) && e.target !== btn) close();
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !dd.hidden) { close(); btn.focus(); }
            });
        })();

        document.addEventListener('submit', function (e) {
            var form = e.target;
            var msg = (e.submitter && e.submitter.dataset.confirm) || form.dataset.confirm;
            if (msg && !window.confirm(msg)) { e.preventDefault(); return; }
            if (form.dataset.noSubmitLock) return;
            form.querySelectorAll('button[type="submit"], button:not([type])').forEach(function (btn) {
                btn.disabled = true;
                if (!btn.dataset.noLoadingText) {
                    btn.dataset.originalText = btn.innerHTML;
                    btn.innerHTML = btn.dataset.loadingText || 'Procesando…';
                }
            });
        });
    })();
    </script>
</body>
</html>
