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
            overflow: auto;
        }
        .sidebar-section { margin-bottom: 1.25rem; }
        .sidebar-title {
            font-size: .72rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #94a3b8;
            margin: 0 0 .65rem;
        }
        .sidebar-nav { display: grid; gap: .4rem; }
        .sidebar-nav a, .sidebar-nav button {
            display: flex;
            align-items: center;
            gap: .75rem;
            width: 100%;
            padding: .8rem .85rem;
            border-radius: 12px;
            text-decoration: none;
            color: #e2e8f0;
            background: transparent;
            border: 0;
            font-size: .93rem;
            font-weight: 600;
            cursor: pointer;
            text-align: left;
        }
        .sidebar-nav a:hover, .sidebar-nav button:hover { background: rgba(148, 163, 184, .12); }
        .sidebar-nav a.active { background: rgba(239, 68, 68, .18); color: #fff; }
        .sidebar-nav small {
            display: block;
            font-size: .72rem;
            font-weight: 500;
            color: #94a3b8;
        }
        .sidebar-foot {
            margin-top: 1.25rem;
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
                <label class="sidebar-button show-mobile" for="sidebar-toggle" aria-label="Abrir menú">
                    <span>☰</span>
                </label>
                <div class="brand">
                    <strong>Super Carnes Admin</strong>
                    <span>@yield('subtitle', 'Backoffice')</span>
                </div>
            </div>
            <div class="topbar-right">
                @yield('topbar-actions')
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
</body>
</html>
