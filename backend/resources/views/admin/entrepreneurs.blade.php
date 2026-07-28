@extends('admin.layout')

@section('title', 'Del sueño al puesto')
@section('subtitle', 'Emprendedores')

@section('topbar-actions')
    <a class="topbar-action hide-mobile" href="{{ route('admin.invoice-backoffice') }}">Configuración</a>
    <a class="topbar-action hide-mobile" href="{{ route('admin.invoices') }}">Facturas</a>
    <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit">Cerrar sesión</button>
    </form>
@endsection

@section('sidebar-actions')
    <a href="{{ route('admin.invoices') }}">Ver facturas <small>Registro general</small></a>
    <a href="{{ route('admin.winners') }}">Ver ganadores <small>Administrar selección</small></a>
@endsection

@push('styles')
    <style>
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 1rem;
            padding: 1rem;
        }
        .stat-card {
            border-radius: 14px;
            padding: 1rem 1.1rem;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
        }
        .stat-card strong {
            display: block;
            font-size: 1.65rem;
            line-height: 1.15;
            margin-bottom: .2rem;
        }
        .stat-card span {
            font-size: .78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #64748b;
        }
        .stat-card.stat-total { background: #eff6ff; border-color: #bfdbfe; }
        .stat-card.stat-total strong { color: #1d4ed8; }
        .stat-card.stat-qualified { background: #f0fdf4; border-color: #bbf7d0; }
        .stat-card.stat-qualified strong { color: #166534; }
        .stat-card.stat-pending { background: #fffbeb; border-color: #fde68a; }
        .stat-card.stat-pending strong { color: #92400e; }
        .stat-card.stat-goal { background: #fef2f2; border-color: #fecaca; }
        .stat-card.stat-goal strong { color: #b91c1c; }
        .stat-card.stat-amount { background: #f5f3ff; border-color: #ddd6fe; }
        .stat-card.stat-amount strong { color: #6d28d9; }
        .stat-card small { display: block; margin-top: .3rem; color: #94a3b8; font-size: .74rem; }
        .filter-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .progress-mini {
            width: 100%;
            height: 6px;
            border-radius: 999px;
            background: #e2e8f0;
            overflow: hidden;
            margin-top: .3rem;
        }
        .progress-mini i {
            display: block;
            height: 100%;
            background: #16a34a;
        }
        th.th-sort { padding: 0; }
        .th-sort-link {
            display: flex;
            align-items: center;
            gap: .3rem;
            padding: .75rem .9rem;
            color: #334155;
            text-decoration: none;
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            font-weight: 700;
            white-space: nowrap;
        }
        .th-sort-link:hover { color: #b91c1c; }
        .th-sort-link.is-active { color: #b91c1c; }
        .th-sort-arrow { font-size: .7rem; opacity: .55; }
        .th-sort-link.is-active .th-sort-arrow { opacity: 1; }
        .whatsapp-link {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            color: #166534;
            text-decoration: none;
            font-weight: 600;
        }
        .whatsapp-link:hover { text-decoration: underline; }
        .whatsapp-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #25D366;
            color: #fff;
            font-size: .72rem;
            flex-shrink: 0;
        }
        @media (max-width: 1200px) {
            .stat-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (max-width: 900px) {
            .stat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 560px) {
            .stat-grid { grid-template-columns: 1fr; }
        }
    </style>
@endpush

@section('content')
    @if (session('status'))
        <div class="success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <div class="page-card">
        <div class="page-title">
            <div>
                <h1>Emprendedores — Del sueño al puesto</h1>
                <p>Seguimiento de inscritos, calificados y pendientes@if($dreamCampaign) · Meta ${{ number_format((float) ($dreamCampaign->entry_threshold_amount ?: 100), 2) }} en facturas@endif</p>
            </div>
        </div>

        <div class="stat-grid">
            <div class="stat-card stat-total">
                <span>Registrados</span>
                <strong>{{ number_format($stats['total']) }}</strong>
                <small>Coincide con el filtro actual</small>
            </div>
            <div class="stat-card stat-qualified">
                <span>Calificados</span>
                <strong>{{ number_format($stats['qualified']) }}</strong>
                <small>Llegaron a la meta de facturación</small>
            </div>
            <div class="stat-card stat-amount">
                <span>Total facturado</span>
                <strong>${{ number_format($stats['totalAmount'], 2) }}</strong>
                <small>Suma de facturas registradas</small>
            </div>
            <div class="stat-card stat-pending">
                <span>No calificados</span>
                <strong>{{ number_format($stats['pending']) }}</strong>
                <small>Aún no alcanzan la meta</small>
            </div>
            <div class="stat-card stat-goal">
                <span>% Calificados</span>
                <strong>{{ $stats['total'] > 0 ? number_format(($stats['qualified'] / $stats['total']) * 100, 0) : 0 }}%</strong>
                <div class="progress-mini"><i style="width: {{ $stats['total'] > 0 ? min(100, ($stats['qualified'] / $stats['total']) * 100) : 0 }}%"></i></div>
            </div>
        </div>

        <div class="page-section" style="border-top:1px solid #e5e7eb;">
            <form method="GET" action="{{ route('admin.entrepreneurs') }}" class="filter-toolbar">
                <input type="hidden" name="sort" value="{{ request('sort') }}">
                <input type="hidden" name="direction" value="{{ request('direction') }}">
                <div class="form-grid" style="flex:1; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));">
                    <div class="field">
                        <label for="name">Nombre</label>
                        <input id="name" name="name" type="text" value="{{ request('name') }}" placeholder="Nombre o emprendimiento">
                    </div>
                    <div class="field">
                        <label for="cedula">Cédula</label>
                        <input id="cedula" name="cedula" type="text" value="{{ request('cedula') }}" placeholder="Buscar por cédula">
                    </div>
                    <div class="field">
                        <label for="phone">Teléfono</label>
                        <input id="phone" name="phone" type="text" value="{{ request('phone') }}" placeholder="Buscar por teléfono">
                    </div>
                    <div class="field">
                        <label for="province">Provincia</label>
                        <input id="province" name="province" type="text" value="{{ request('province') }}" placeholder="Provincia">
                    </div>
                    <div class="field">
                        <label for="qualified">Estado</label>
                        <select id="qualified" name="qualified">
                            <option value="">Todos</option>
                            <option value="yes" @selected(request('qualified') === 'yes')>Calificados</option>
                            <option value="no" @selected(request('qualified') === 'no')>No calificados</option>
                        </select>
                    </div>
                </div>
                <div class="responsive-actions">
                    <button class="btn btn-red" type="submit">Filtrar</button>
                    <a class="btn btn-gray" href="{{ route('admin.entrepreneurs') }}">Limpiar</a>
                    <a class="btn btn-green" href="{{ route('admin.entrepreneurs.export', request()->only(['name', 'cedula', 'phone', 'province', 'qualified', 'sort', 'direction'])) }}">⬇ Exportar CSV</a>
                </div>
            </form>
        </div>

        <div class="page-section">
            @if($entrepreneurs->isEmpty())
                <div class="empty">No hay emprendedores registrados todavía.</div>
            @else
                @php
                    $currentSort = request('sort');
                    $currentDir = request('direction', 'desc');
                    $sortUrl = function (string $column) use ($currentSort, $currentDir) {
                        $nextDir = ($currentSort === $column && $currentDir === 'asc') ? 'desc' : 'asc';
                        $query = array_merge(request()->except('page'), ['sort' => $column, 'direction' => $nextDir]);
                        return route('admin.entrepreneurs', $query);
                    };
                    $sortArrow = function (string $column) use ($currentSort, $currentDir) {
                        if ($currentSort !== $column) {
                            return '↕';
                        }
                        return $currentDir === 'asc' ? '▲' : '▼';
                    };
                    $sortColumns = [
                        'name' => 'Persona',
                        'phone' => 'Teléfono',
                        'entrepreneur_name' => 'Emprendimiento',
                        'province' => 'Provincia',
                        'branch' => 'Sucursal cercana',
                        'type' => 'Tipo',
                        'total' => 'Acumulado',
                        'status' => 'Estado',
                    ];
                @endphp
                <div class="table-shell">
                    <table class="wide">
                        <thead>
                            <tr>
                                @foreach($sortColumns as $column => $label)
                                    <th class="th-sort">
                                        <a class="th-sort-link {{ $currentSort === $column ? 'is-active' : '' }}" href="{{ $sortUrl($column) }}">
                                            {{ $label }}
                                            <span class="th-sort-arrow">{{ $sortArrow($column) }}</span>
                                        </a>
                                    </th>
                                @endforeach
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($entrepreneurs as $person)
                                @php
                                    $total = (float) ($totalsByUser[$person->id] ?? 0);
                                    $goal = (float) ($dreamCampaign->entry_threshold_amount ?? 100) ?: 100;
                                    $progress = min(100, ($total / $goal) * 100);
                                    $waDigits = preg_replace('/\D/', '', (string) $person->phone);
                                    if ($waDigits !== '' && strlen($waDigits) <= 8) {
                                        $waDigits = '507' . $waDigits;
                                    }
                                @endphp
                                <tr>
                                    <td data-label="Persona"><strong>{{ $person->full_name ?? $person->name ?? '—' }}</strong><br><span style="color:#64748b">{{ $person->cedula ?? '—' }} · {{ $person->email ?? '—' }}</span></td>
                                    <td data-label="Teléfono">
                                        @if($person->phone)
                                            <a class="whatsapp-link" href="https://wa.me/{{ $waDigits }}" target="_blank" rel="noopener">
                                                <span class="whatsapp-icon">✆</span>{{ $person->phone }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td data-label="Emprendimiento">{{ $person->entrepreneur_name ?? '—' }}</td>
                                    <td data-label="Provincia">{{ $person->entrepreneur_province ?? '—' }}</td>
                                    <td data-label="Sucursal cercana">{{ $person->branch?->name ?? '—' }}</td>
                                    <td data-label="Tipo">{{ $person->entrepreneur_type ?? '—' }}</td>
                                    <td data-label="Acumulado">
                                        ${{ number_format($total, 2) }} / ${{ number_format($goal, 2) }}
                                        <div class="progress-mini"><i style="width: {{ $progress }}%; background: {{ $progress >= 100 ? '#16a34a' : '#f59e0b' }};"></i></div>
                                    </td>
                                    <td data-label="Estado">
                                        @if($person->dream_promo_qualified_at)
                                            <span class="badge badge-green">Calificado</span>
                                        @else
                                            <span class="badge badge-yellow">Pendiente</span>
                                        @endif
                                    </td>
                                    <td data-label="Acciones">
                                        <a class="btn btn-gray" href="{{ route('admin.entrepreneurs.edit', $person) }}">Ver / Editar</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($entrepreneurs->hasPages())
                    <div style="padding-top:1rem;">
                        {{ $entrepreneurs->links('pagination::simple-bootstrap-4') }}
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection
