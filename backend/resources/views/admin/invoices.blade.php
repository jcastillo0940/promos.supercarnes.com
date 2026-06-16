<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturas Registradas — Admin</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, sans-serif; background: #f1f5f9; color: #1e293b; }
        header {
            background: #dc2626; color: #fff;
            padding: .875rem 1.5rem;
            display: flex; align-items: center; justify-content: space-between;
        }
        header h1 { font-size: 1rem; font-weight: 700; }
        header nav a {
            color: rgba(255,255,255,.85); text-decoration: none;
            font-size: .875rem; margin-left: 1.25rem;
        }
        header nav a:hover { color: #fff; }
        main { padding: 1.5rem; max-width: 1200px; margin: 0 auto; }
        .card {
            background: #fff; border-radius: 10px;
            box-shadow: 0 1px 6px rgba(0,0,0,.07);
            overflow: hidden;
        }
        .card-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex; align-items: center; justify-content: space-between;
        }
        .card-header h2 { font-size: .9375rem; font-weight: 600; }
        .count { font-size: .8125rem; color: #64748b; }
        table { width: 100%; border-collapse: collapse; font-size: .8125rem; }
        th {
            background: #f8fafc; text-align: left;
            padding: .625rem 1rem; font-weight: 600;
            color: #475569; border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }
        td { padding: .625rem 1rem; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f8fafc; }
        .badge {
            display: inline-block; padding: .2rem .5rem;
            border-radius: 999px; font-size: .75rem; font-weight: 600;
        }
        .badge-green  { background: #dcfce7; color: #166534; }
        .badge-yellow { background: #fef9c3; color: #854d0e; }
        .badge-red    { background: #fee2e2; color: #991b1b; }
        .badge-gray   { background: #f1f5f9; color: #475569; }
        .pagination { padding: 1rem 1.25rem; display: flex; gap: .5rem; align-items: center; }
        .pagination a, .pagination span {
            padding: .375rem .75rem; border-radius: 6px;
            font-size: .8125rem; text-decoration: none; color: #374151;
            border: 1px solid #d1d5db;
        }
        .pagination span.active { background: #dc2626; color: #fff; border-color: #dc2626; }
        .empty { padding: 3rem; text-align: center; color: #94a3b8; }
        form.logout { display: inline; }
        form.logout button {
            background: none; border: none; color: rgba(255,255,255,.85);
            cursor: pointer; font-size: .875rem; padding: 0; margin-left: 1.25rem;
        }
        form.logout button:hover { color: #fff; }
    </style>
</head>
<body>
<header>
    <h1>Super Carnes Admin</h1>
    <nav>
        <a href="{{ route('admin.invoice-backoffice') }}">Configuración</a>
        <a href="{{ route('admin.invoices') }}">Facturas</a>
        <a href="{{ route('admin.winners') }}">Ganadores</a>
        <form class="logout" method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit">Cerrar sesión</button>
        </form>
    </nav>
</header>

<main>
    <div class="card">
        <div class="card-header">
            <h2>Facturas registradas</h2>
            <span class="count">{{ number_format($invoices->total()) }} en total</span>
        </div>

        @if($invoices->isEmpty())
            <div class="empty">No hay facturas registradas aún.</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Usuario</th>
                        <th>N° Factura</th>
                        <th>Emisor</th>
                        <th>Monto</th>
                        <th>Puntos</th>
                        <th>Estado</th>
                        <th>Validación DGI</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $inv)
                        <tr>
                            <td>{{ $inv->id }}</td>
                            <td>
                                <strong>{{ $inv->user?->full_name ?? '—' }}</strong><br>
                                <span style="color:#64748b">{{ $inv->user?->email ?? '—' }}</span>
                            </td>
                            <td>{{ $inv->invoice_number ?? '—' }}</td>
                            <td>
                                {{ $inv->issuer_name ?? '—' }}<br>
                                <span style="color:#64748b">{{ $inv->issuer_ruc ?? '' }}</span>
                            </td>
                            <td>${{ number_format((float)$inv->purchase_amount, 2) }}</td>
                            <td>{{ $inv->points_awarded ?? 0 }}</td>
                            <td>
                                @php
                                    $statusMap = [
                                        'approved' => ['green', 'Aprobada'],
                                        'pending'  => ['yellow', 'Pendiente'],
                                        'rejected' => ['red', 'Rechazada'],
                                    ];
                                    [$color, $label] = $statusMap[$inv->status] ?? ['gray', $inv->status ?? '—'];
                                @endphp
                                <span class="badge badge-{{ $color }}">{{ $label }}</span>
                            </td>
                            <td>
                                @php
                                    $valMap = [
                                        'approved' => ['green', 'Válida DGI'],
                                        'pending'  => ['yellow', 'Pendiente'],
                                        'failed'   => ['red', 'Falló'],
                                        'skipped'  => ['gray', 'Omitida'],
                                    ];
                                    [$vc, $vl] = $valMap[$inv->validation_status] ?? ['gray', $inv->validation_status ?? '—'];
                                @endphp
                                <span class="badge badge-{{ $vc }}">{{ $vl }}</span>
                            </td>
                            <td style="white-space:nowrap">
                                {{ $inv->created_at?->format('d/m/Y H:i') ?? '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if($invoices->hasPages())
                <div class="pagination">
                    {{ $invoices->links('pagination::simple-bootstrap-4') }}
                </div>
            @endif
        @endif
    </div>
</main>
</body>
</html>
