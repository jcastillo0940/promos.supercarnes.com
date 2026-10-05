<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — Super Carnes</title>
    @stack('styles')
    <style>
        /* ── Design tokens ───────────────────────────────────────────────────── */
        :root {
            --bo-bg:           #F7F8FA;
            --bo-surface:      #FFFFFF;
            --bo-border:       #E8EBF0;
            --bo-border-dark:  #D1D5DB;
            --bo-fg:           #0F172A;
            --bo-fg2:          #475569;
            --bo-muted:        #94A3B8;
            --bo-accent:       #C0122C;
            --bo-accent-h:     #A00F24;
            --bo-accent-soft:  #FEF2F4;
            /* Nav active — Variante A (fondo sólido, texto blanco)
               Para Variante B (fondo suave, texto acento) cambia a:
               --nav-a-bg: var(--bo-accent-soft); --nav-a-fg: var(--bo-accent); --nav-a-icon: var(--bo-accent); */
            --nav-a-bg:   var(--bo-accent);
            --nav-a-fg:   #FFFFFF;
            --nav-a-icon: #FFFFFF;
        }

        *, *::before, *::after { box-sizing: border-box; }
        [hidden] { display: none !important; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: var(--bo-bg);
            color: var(--bo-fg);
            min-height: 100vh;
            font-size: 14px;
        }
        a { color: inherit; }
        .app-shell { min-height: 100vh; }
        .sidebar-toggle { position: fixed; left: -9999px; }

        /* ── Topbar ──────────────────────────────────────────────────────────── */
        .topbar {
            position: sticky;
            top: 0;
            z-index: 40;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0 1.25rem;
            background: var(--bo-surface);
            border-bottom: 1px solid var(--bo-border);
            color: var(--bo-fg);
        }
        .topbar-left, .topbar-right { display: flex; align-items: center; gap: .6rem; }
        .topbar-ctx { display: flex; align-items: center; gap: .4rem; font-size: .82rem; color: var(--bo-fg2); }
        .topbar-ctx-sep { color: var(--bo-muted); }
        .topbar-ctx-name { font-weight: 700; color: var(--bo-fg); }
        .topbar-ctx-badge { font-size: .68rem; background: var(--bo-accent-soft); color: var(--bo-accent); border-radius: 999px; padding: .1rem .45rem; }

        /* ── User menu ───────────────────────────────────────────────────────── */
        .user-menu { position: relative; }
        .user-btn {
            display: flex; align-items: center; gap: .4rem;
            min-height: 36px; padding: 0 .65rem 0 .3rem;
            border-radius: 8px; border: 1px solid var(--bo-border);
            background: var(--bo-surface); color: var(--bo-fg);
            cursor: pointer; font: inherit; font-size: .875rem; font-weight: 600;
            transition: background .12s;
        }
        .user-btn:hover { background: var(--bo-bg); }
        .user-avatar {
            width: 26px; height: 26px; border-radius: 50%;
            background: var(--bo-accent-soft); display: grid; place-items: center;
            font-size: .72rem; font-weight: 800; flex-shrink: 0; color: var(--bo-accent);
        }
        .user-name { max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--bo-fg2); font-size: .82rem; }
        .user-chevron { font-size: .68rem; color: var(--bo-muted); transition: transform .15s; }
        .user-btn[aria-expanded="true"] .user-chevron { transform: rotate(180deg); }
        .user-dropdown {
            position: absolute; top: calc(100% + 6px); right: 0; z-index: 200;
            background: var(--bo-surface); border: 1px solid var(--bo-border);
            border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,.10);
            min-width: 200px; overflow: hidden;
        }
        .user-dropdown-header { padding: .75rem 1rem; border-bottom: 1px solid var(--bo-border); }
        .user-dropdown-header strong { display: block; color: var(--bo-fg); font-size: .875rem; }
        .user-dropdown-header span { display: block; color: var(--bo-muted); font-size: .78rem; margin-top: .1rem; }
        .user-dropdown-sep { height: 1px; background: var(--bo-border); }
        .user-dropdown-item {
            display: block; width: 100%; padding: .65rem 1rem;
            background: none; border: none; cursor: pointer;
            color: var(--bo-fg2); font: inherit; font-size: .875rem; font-weight: 600;
            text-align: left; transition: background .1s;
        }
        .user-dropdown-item:hover { background: var(--bo-bg); color: var(--bo-fg); }
        .user-logout { color: #991b1b; }
        .user-logout:hover { background: #fef2f2; color: #991b1b; }

        /* ── Brand (topbar) ──────────────────────────────────────────────────── */
        .brand { display: flex; flex-direction: column; line-height: 1.15; }
        .brand strong { font-size: .9rem; color: var(--bo-fg); }
        .brand span { font-size: .72rem; color: var(--bo-muted); }
        .sidebar-button {
            display: inline-flex; align-items: center; justify-content: center;
            width: 36px; height: 36px;
            border: 1px solid var(--bo-border); border-radius: 8px;
            background: transparent; color: var(--bo-fg2); cursor: pointer; user-select: none;
            transition: background .1s;
        }
        .sidebar-button:hover { background: var(--bo-bg); }
        .sidebar-button span { font-size: 1rem; line-height: 1; }
        .topbar-action {
            display: inline-flex; align-items: center; justify-content: center;
            min-height: 34px; padding: 0 .8rem;
            border-radius: 8px; border: 1px solid var(--bo-border);
            background: var(--bo-surface); color: var(--bo-fg2);
            text-decoration: none; font-size: .82rem; font-weight: 600;
            transition: background .1s;
        }
        .topbar-action:hover { background: var(--bo-bg); color: var(--bo-fg); }
        .topbar form { margin: 0; }
        .topbar button:not(.user-btn):not(.user-dropdown-item) {
            min-height: 34px; padding: 0 .8rem;
            border-radius: 8px; border: 1px solid var(--bo-border);
            background: var(--bo-surface); color: var(--bo-fg2);
            cursor: pointer; font-size: .82rem; font-weight: 600;
        }
        .topbar button:not(.user-btn):not(.user-dropdown-item):hover { background: var(--bo-bg); }

        /* ── Layout ──────────────────────────────────────────────────────────── */
        .layout {
            display: grid;
            grid-template-columns: 248px minmax(0, 1fr);
            min-height: calc(100vh - 56px);
            transition: grid-template-columns .2s ease;
        }
        .layout.sidebar-collapsed {
            grid-template-columns: 60px minmax(0, 1fr);
        }

        /* ── Sidebar ─────────────────────────────────────────────────────────── */
        .sidebar {
            background: var(--bo-surface);
            color: var(--bo-fg2);
            padding: 0;
            border-right: 1px solid var(--bo-border);
            position: sticky;
            top: 56px;
            height: calc(100vh - 56px);
            overflow-y: auto;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
            transition: width .2s ease;
            width: 248px;
        }

        /* ── Sidebar logo ────────────────────────────────────────────────────── */
        .sidebar-logo {
            display: flex; align-items: center; gap: .6rem;
            padding: .85rem .85rem .75rem;
            border-bottom: 1px solid var(--bo-border);
            flex-shrink: 0;
        }
        .sidebar-logo-mark {
            width: 28px; height: 28px; border-radius: 7px;
            background: var(--bo-accent); display: grid; place-items: center;
            font-size: .65rem; font-weight: 900; color: #fff; flex-shrink: 0;
            letter-spacing: -.02em;
        }
        .sidebar-logo-name { font-size: .82rem; font-weight: 700; color: var(--bo-fg); white-space: nowrap; overflow: hidden; }
        .sidebar-logo-sub { font-size: .68rem; color: var(--bo-muted); }
        .sidebar.collapsed .sidebar-logo { justify-content: center; padding: .85rem .5rem .75rem; }
        .sidebar.collapsed .sidebar-logo-name,
        .sidebar.collapsed .sidebar-logo-sub { display: none; }

        /* ── Context selector ────────────────────────────────────────────────── */
        .ctx-selector { padding: .65rem .75rem .5rem; flex-shrink: 0; }
        .ctx-btn {
            display: flex; align-items: center; gap: .55rem;
            width: 100%; padding: .55rem .65rem;
            background: var(--bo-bg); border: 1px solid var(--bo-border);
            border-radius: 9px; cursor: pointer; color: var(--bo-fg); text-align: left;
            transition: background .12s, border-color .12s;
        }
        .ctx-btn:hover { background: #eef0f5; border-color: var(--bo-border-dark); }
        .ctx-avatar {
            width: 26px; height: 26px; border-radius: 7px;
            background: var(--bo-accent); display: grid; place-items: center;
            font-size: .72rem; font-weight: 800; color: #fff; flex-shrink: 0;
        }
        .ctx-avatar.ctx-avatar-general {
            background: var(--bo-border); color: var(--bo-muted);
        }
        .ctx-info { display: flex; flex-direction: column; gap: .05rem; min-width: 0; flex: 1; }
        .ctx-name { font-size: .82rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--bo-fg); }
        .ctx-general-name { color: var(--bo-fg2); }
        .ctx-type { font-size: .68rem; color: var(--bo-muted); }
        .ctx-badge { display: inline-block; font-size: .65rem; font-weight: 700; border-radius: 999px; padding: .08rem .4rem; }
        .ctx-active   { background: #dcfce7; color: #166534; }
        .ctx-paused   { background: #f3f4f6; color: #6b7280; }
        .ctx-draft    { background: #fef3c7; color: #92400e; }
        .ctx-archived { background: #fee2e2; color: #991b1b; }
        .ctx-chevron { flex-shrink: 0; color: var(--bo-muted); transition: transform .15s; display: flex; align-items: center; }
        .ctx-btn[aria-expanded="true"] .ctx-chevron { transform: rotate(180deg); }
        .ctx-kbd {
            display: flex; align-items: center; gap: .35rem;
            padding: .28rem .65rem .4rem;
            font-size: .68rem; color: var(--bo-muted);
        }
        .ctx-kbd kbd {
            display: inline-flex; align-items: center; justify-content: center;
            padding: .1rem .3rem; border: 1px solid var(--bo-border);
            border-radius: 4px; font: inherit; font-size: .65rem; color: var(--bo-muted);
            background: var(--bo-bg);
        }
        .sidebar.collapsed .ctx-selector { display: none; }

        /* ── Context dropdown (position: fixed, positioned via JS) ───────────── */
        .ctx-dropdown {
            position: fixed; z-index: 400;
            background: var(--bo-surface); border: 1px solid var(--bo-border);
            border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,.12);
            overflow: hidden; max-height: 340px; display: flex; flex-direction: column;
        }
        .ctx-search-wrap { padding: .5rem; border-bottom: 1px solid var(--bo-border); flex-shrink: 0; }
        .ctx-search {
            width: 100%; padding: .45rem .7rem; background: var(--bo-bg);
            border: 1px solid var(--bo-border); border-radius: 7px;
            color: var(--bo-fg); font: inherit; font-size: .85rem;
        }
        .ctx-search::placeholder { color: var(--bo-muted); }
        .ctx-search:focus { outline: none; border-color: var(--bo-accent); }
        .ctx-dropdown { overflow-y: auto; }
        .ctx-group-label {
            font-size: .62rem; font-weight: 700; letter-spacing: .08em;
            text-transform: uppercase; color: var(--bo-muted);
            padding: .55rem .85rem .25rem;
        }
        .ctx-item {
            display: flex; align-items: center; gap: .6rem;
            padding: .55rem .85rem; text-decoration: none;
            color: var(--bo-fg2); font-size: .875rem; font-weight: 600;
            transition: background .1s;
        }
        .ctx-item:hover, .ctx-item:focus { background: var(--bo-bg); color: var(--bo-fg); outline: none; }
        .ctx-item-current { background: var(--bo-accent-soft); color: var(--bo-accent); }
        .ctx-item-current:hover { background: #fde8ec; }
        .ctx-item-general { color: var(--bo-fg2); }
        .ctx-item-icon { font-size: .95rem; width: 1.1rem; text-align: center; flex-shrink: 0; }
        .ctx-item-label { display: flex; flex-direction: column; gap: .04rem; }
        .ctx-item-label small { font-size: .68rem; font-weight: 400; color: var(--bo-muted); }
        .ctx-item-current .ctx-item-label small { color: rgba(192,18,44,.6); }
        .ctx-dot { font-size: .55rem; flex-shrink: 0; color: var(--bo-border-dark); }
        .ctx-dot.ctx-active   { color: #16a34a; }
        .ctx-dot.ctx-paused   { color: #9ca3af; }
        .ctx-dot.ctx-draft    { color: #d97706; }
        .ctx-dot.ctx-archived { color: #dc2626; }
        .ctx-divider { height: 1px; background: var(--bo-border); margin: .25rem 0; flex-shrink: 0; }
        .ctx-item[hidden] { display: none !important; }

        /* ── Sidebar nav ─────────────────────────────────────────────────────── */
        .sidebar-nav-wrap {
            flex: 1; overflow-y: auto; overflow-x: hidden;
            padding: .25rem .75rem .5rem;
        }
        .sidebar.collapsed .sidebar-nav-wrap { padding: .25rem .5rem .5rem; }
        .sidebar-nav { display: grid; gap: 2px; padding: 0; }
        .sidebar-nav a, .sidebar-nav button {
            display: flex; flex-direction: row; align-items: center;
            gap: .6rem;
            width: 100%; padding: .55rem .75rem;
            border-radius: 8px; text-decoration: none;
            color: var(--bo-fg2); background: transparent; border: 0;
            font-size: .875rem; font-weight: 500; cursor: pointer; text-align: left;
            transition: background .1s, color .1s;
            white-space: nowrap; overflow: hidden;
        }
        .sidebar-nav a:hover, .sidebar-nav button:hover { background: var(--bo-bg); color: var(--bo-fg); }
        .sidebar-nav a.active {
            background: var(--nav-a-bg); color: var(--nav-a-fg); font-weight: 600;
        }
        .sidebar-nav a.active .nav-icon { stroke: var(--nav-a-icon); }
        .nav-icon {
            width: 16px; height: 16px; flex-shrink: 0;
            stroke: currentColor; fill: none;
            stroke-linecap: round; stroke-linejoin: round;
        }
        .nav-label { display: flex; flex-direction: column; gap: .04rem; min-width: 0; overflow: hidden; }
        .sidebar-nav small {
            font-size: .68rem; font-weight: 400; color: var(--bo-muted);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .sidebar-nav a.active small { color: rgba(255,255,255,.65); }
        .sidebar-group { margin-top: .5rem; }
        .sidebar-group-title {
            font-size: .62rem; letter-spacing: .08em; text-transform: uppercase;
            color: var(--bo-muted); margin: 0 0 .2rem .75rem; padding: 0; font-weight: 700;
        }
        .sidebar-sep { height: 1px; background: var(--bo-border); margin: .5rem 0; }
        .sidebar-back { color: var(--bo-muted) !important; font-size: .82rem !important; font-weight: 500 !important; }
        .sidebar-back:hover { color: var(--bo-fg2) !important; }

        /* Collapsed nav */
        .sidebar.collapsed .sidebar-nav a,
        .sidebar.collapsed .sidebar-nav button { justify-content: center; padding: .6rem; }
        .sidebar.collapsed .nav-label,
        .sidebar.collapsed .sidebar-group-title,
        .sidebar.collapsed .sidebar-sep { display: none; }
        .sidebar.collapsed .sidebar-nav a { position: relative; }
        .sidebar.collapsed .sidebar-nav a[title]:hover::after {
            content: attr(title);
            position: fixed; left: 68px;
            background: var(--bo-fg); color: #fff;
            padding: .3rem .6rem; border-radius: 6px;
            font-size: .78rem; font-weight: 600; white-space: nowrap;
            pointer-events: none; z-index: 500;
        }

        /* ── Sidebar foot ────────────────────────────────────────────────────── */
        .sidebar-foot {
            margin-top: auto; flex-shrink: 0;
            padding: .65rem .85rem;
            border-top: 1px solid var(--bo-border);
            color: var(--bo-muted); font-size: .78rem;
        }
        .sidebar-foot strong { display: block; color: var(--bo-fg2); font-size: .8rem; margin-bottom: .15rem; }
        .sidebar.collapsed .sidebar-foot { display: none; }

        /* ── Sidebar collapse button ─────────────────────────────────────────── */
        .sidebar-collapse-btn {
            display: flex; align-items: center; gap: .5rem;
            width: 100%; padding: .55rem .85rem;
            background: none; border: none; border-top: 1px solid var(--bo-border);
            cursor: pointer; color: var(--bo-muted); font: inherit;
            font-size: .78rem; font-weight: 600; flex-shrink: 0;
            transition: background .1s, color .1s;
        }
        .sidebar-collapse-btn:hover { background: var(--bo-bg); color: var(--bo-fg2); }
        .sidebar-collapse-btn .nav-icon { stroke: currentColor; }
        .sidebar.collapsed .sidebar-collapse-btn { justify-content: center; padding: .55rem; border-top: 1px solid var(--bo-border); }
        .sidebar.collapsed .collapse-label { display: none; }

        /* ── Content ─────────────────────────────────────────────────────────── */
        .content { min-width: 0; padding: 1.25rem; }
        .content-inner { max-width: 1400px; margin: 0 auto; }
        .page-card {
            background: var(--bo-surface);
            border-radius: 12px;
            border: 1px solid var(--bo-border);
            box-shadow: 0 1px 4px rgba(15,23,42,.05);
            overflow: hidden;
        }
        .page-section { padding: 1rem 1.1rem; }
        .page-title {
            display: flex; justify-content: space-between; gap: 1rem;
            flex-wrap: wrap; align-items: center; padding: 1.1rem 1.1rem .5rem;
        }
        .page-title h1, .page-title h2 { margin: 0; }
        .page-title h1 { font-size: 1.15rem; font-weight: 700; color: var(--bo-fg); }
        .page-title p { margin: .2rem 0 0; color: var(--bo-fg2); font-size: .82rem; }
        .stack { display: grid; gap: 1rem; }
        .success, .error, .notice, .alert-success {
            padding: .85rem 1rem; border-radius: 10px;
            margin-bottom: 1rem; border: 1px solid transparent;
        }
        .success, .alert-success { background: #f0fdf4; color: #166534; border-color: #bbf7d0; }
        .error { background: #fef2f2; color: #991b1b; border-color: #fecaca; }
        .notice { background: #fffbeb; color: #92400e; border-color: #fde68a; }

        /* ── Buttons ─────────────────────────────────────────────────────────── */
        .btn {
            display: inline-flex; align-items: center; justify-content: center;
            border: none; border-radius: 8px; padding: .6rem 1rem;
            font-size: .875rem; font-weight: 600; cursor: pointer;
            text-decoration: none; white-space: nowrap; transition: background .12s;
        }
        .btn-red   { background: var(--bo-accent); color: #fff; }
        .btn-gray  { background: #e2e8f0; color: #0f172a; }
        .btn-green { background: #16a34a; color: #fff; }
        .btn-red:hover   { background: var(--bo-accent-h); }
        .btn-gray:hover  { background: #cbd5e1; }
        .btn-green:hover { background: #15803d; }

        /* ── Tables ──────────────────────────────────────────────────────────── */
        .table-shell { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .table-shell table { min-width: 760px; }
        .table-shell table.wide { min-width: 1080px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: .7rem .9rem; border-bottom: 1px solid var(--bo-border); text-align: left; vertical-align: top; }
        th { background: var(--bo-bg); color: var(--bo-fg2); font-size: .72rem; text-transform: uppercase; letter-spacing: .05em; font-weight: 700; }

        /* ── Badges ──────────────────────────────────────────────────────────── */
        .badge {
            display: inline-flex; align-items: center;
            border-radius: 999px; padding: .18rem .55rem;
            font-size: .72rem; font-weight: 700;
        }
        .badge-green  { background: #dcfce7; color: #166534; }
        .badge-yellow { background: #fef9c3; color: #854d0e; }
        .badge-red    { background: #fee2e2; color: #991b1b; }
        .badge-gray   { background: #f3f4f6; color: #374151; }
        .badge-blue   { background: #dbeafe; color: #1e40af; }

        /* ── Forms ───────────────────────────────────────────────────────────── */
        .form-grid { display: grid; gap: 1rem; }
        .field label { display: block; font-size: .8rem; font-weight: 600; color: var(--bo-fg2); margin-bottom: .3rem; }
        .field input, .field select, .field textarea {
            width: 100%; min-height: 40px; padding: .55rem .8rem;
            border: 1px solid var(--bo-border-dark); border-radius: 8px;
            font-size: .9rem; background: var(--bo-surface); color: var(--bo-fg);
            transition: border-color .12s;
        }
        .field textarea { min-height: auto; }
        .field input:focus, .field select:focus, .field textarea:focus {
            outline: none; border-color: var(--bo-accent);
            box-shadow: 0 0 0 2px rgba(192,18,44,.1);
        }
        .responsive-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
        .responsive-actions form, .responsive-actions a { margin: 0; }
        .empty {
            padding: 1.5rem 1rem; color: var(--bo-muted); font-size: .88rem;
            text-align: center; border: 1px dashed var(--bo-border);
            border-radius: 10px; background: var(--bo-bg);
        }
        .hide-mobile { display: block; }
        .show-mobile { display: none; }

        /* ── Mobile ──────────────────────────────────────────────────────────── */
        @media (max-width: 1024px) {
            .layout { grid-template-columns: 220px minmax(0, 1fr); }
            .sidebar { width: 220px; }
        }
        @media (max-width: 900px) {
            .layout { grid-template-columns: 1fr; }
            .layout.sidebar-collapsed { grid-template-columns: 1fr; }
            .sidebar {
                position: fixed;
                inset: 56px auto 0 0;
                width: min(88vw, 300px);
                transform: translateX(-102%);
                transition: transform .2s ease;
                z-index: 60;
            }
            .sidebar.collapsed { width: min(88vw, 300px); transform: translateX(-102%); }
            .sidebar-overlay {
                position: fixed; inset: 56px 0 0 0;
                background: rgba(15,23,42,.35);
                opacity: 0; pointer-events: none;
                transition: opacity .2s ease; z-index: 50;
            }
            .sidebar-toggle:checked ~ .layout .sidebar { transform: translateX(0); }
            .sidebar-toggle:checked ~ .sidebar-overlay { opacity: 1; pointer-events: auto; }
            .sidebar-collapse-btn { display: none; }
            .content { padding: .75rem; }
        }
        @media (max-width: 640px) {
            .topbar { padding: 0 .75rem; }
            .brand span { display: none; }
            .topbar-action, .topbar button:not(.user-btn):not(.user-dropdown-item) { padding: 0 .6rem; font-size: .78rem; }
            .hide-mobile { display: none; }
            .show-mobile { display: inline-flex; }
            .page-card { border-radius: 10px; }
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

        <div class="layout" id="layout">
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

        // ── Sidebar collapse ─────────────────────────────────────────────────
        (function () {
            var btn    = document.getElementById('sidebar-collapse-btn');
            var sidebar = document.getElementById('sidebar-nav');
            var layout  = document.getElementById('layout');
            if (!btn || !sidebar || !layout) return;

            var collapsed = false;
            try { collapsed = localStorage.getItem('sc_sidebar_collapsed') === '1'; } catch(e) {}

            function apply() {
                if (collapsed) {
                    sidebar.classList.add('collapsed');
                    layout.classList.add('sidebar-collapsed');
                    btn.setAttribute('aria-label', 'Expandir menú');
                    btn.title = 'Expandir menú';
                } else {
                    sidebar.classList.remove('collapsed');
                    layout.classList.remove('sidebar-collapsed');
                    btn.setAttribute('aria-label', 'Colapsar menú');
                    btn.title = 'Colapsar menú';
                }
                // Update collapse icon
                var ico = btn.querySelector('.collapse-icon');
                if (ico) ico.style.transform = collapsed ? 'scaleX(-1)' : '';
            }

            apply();

            btn.addEventListener('click', function () {
                collapsed = !collapsed;
                try { localStorage.setItem('sc_sidebar_collapsed', collapsed ? '1' : '0'); } catch(e) {}
                apply();
            });
        })();

        // ── Submit lock + confirm ────────────────────────────────────────────
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
