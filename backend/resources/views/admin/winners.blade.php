<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acta de Ganadores — Balón Trionda Día del Padre</title>
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
        form.logout { display: inline; }
        form.logout button {
            background: none; border: none; color: rgba(255,255,255,.85);
            cursor: pointer; font-size: .875rem; padding: 0; margin-left: 1.25rem;
        }
        form.logout button:hover { color: #fff; }

        main { padding: 1.5rem; max-width: 1100px; margin: 0 auto; }

        .acta {
            background: #fff; border-radius: 10px;
            box-shadow: 0 1px 6px rgba(0,0,0,.07);
            overflow: hidden;
        }

        .acta-header {
            padding: 2rem 2rem 1.5rem;
            border-bottom: 3px solid #dc2626;
            text-align: center;
        }
        .acta-header .logo-text {
            font-size: 1.5rem; font-weight: 800; color: #dc2626; letter-spacing: -.5px;
        }
        .acta-header h2 {
            font-size: 1.125rem; font-weight: 700; color: #0f172a; margin-top: .5rem;
        }
        .acta-header p {
            font-size: .8125rem; color: #64748b; margin-top: .375rem;
        }
        .acta-header .meta {
            display: flex; justify-content: center; gap: 2rem; margin-top: 1rem;
            font-size: .8125rem;
        }
        .acta-header .meta span { color: #475569; }
        .acta-header .meta strong { color: #0f172a; }

        .rules {
            padding: 1rem 2rem;
            background: #fef9c3;
            border-bottom: 1px solid #fde047;
            font-size: .8rem; color: #713f12;
        }
        .rules strong { display: block; margin-bottom: .25rem; }

        .toolbar {
            padding: .75rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex; align-items: center; justify-content: space-between;
        }
        .toolbar .count { font-size: .875rem; color: #64748b; }
        .toolbar button {
            background: #dc2626; color: #fff; border: none; border-radius: 6px;
            padding: .5rem 1.25rem; font-size: .875rem; font-weight: 600;
            cursor: pointer;
        }
        .toolbar button:hover { background: #b91c1c; }

        table { width: 100%; border-collapse: collapse; font-size: .8125rem; }
        th {
            background: #f8fafc; text-align: left;
            padding: .625rem 1rem; font-weight: 700;
            color: #475569; border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }
        td { padding: .625rem 1rem; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fef2f2; }

        .num {
            width: 2.5rem; text-align: center;
            font-weight: 700; color: #dc2626; font-size: .9375rem;
        }
        .reason-text {
            font-style: italic; color: #475569;
            max-width: 300px;
        }
        .empty { padding: 3rem; text-align: center; color: #94a3b8; font-size: .9375rem; }

        .acta-footer {
            padding: 1.5rem 2rem;
            border-top: 2px solid #e2e8f0;
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 2rem; margin-top: 2rem;
        }
        .sign-box { text-align: center; }
        .sign-line { border-top: 1px solid #94a3b8; margin: 2.5rem auto 0; width: 80%; }
        .sign-label { font-size: .75rem; color: #64748b; margin-top: .375rem; }

        @media print {
            header, .toolbar button { display: none !important; }
            body { background: #fff; }
            .acta { box-shadow: none; }
            main { padding: 0; max-width: 100%; }
        }
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
    <div class="acta">
        <div class="acta-header">
            <div class="logo-text">Super Carnes</div>
            <h2>Acta Oficial de Ganadores — Balón Trionda Día del Padre</h2>
            <p>Promoción: "¿Por qué tu papá merece un Balón Trionda?"</p>
            <div class="meta">
                <div><span>Total de ganadores: </span><strong>{{ count($winners) }} / 100</strong></div>
                <div><span>Generada el: </span><strong>{{ now()->format('d/m/Y H:i') }}</strong></div>
                <div><span>Premio: </span><strong>1 Balón Trionda por ganador</strong></div>
            </div>
        </div>

        <div class="rules">
            <strong>Criterios de selección:</strong>
            Primeros 100 participantes con factura válida registrada, sin repetir número de cédula ni número de factura, ordenados por fecha de registro.
        </div>

        <div class="toolbar">
            <span class="count">{{ count($winners) }} ganador(es) encontrado(s)</span>
            <button onclick="window.print()">Imprimir / Exportar PDF</button>
        </div>

        @if(empty($winners))
            <div class="empty">No hay ganadores aún. Los ganadores aparecerán conforme se registren facturas válidas.</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th class="num">#</th>
                        <th>Nombre</th>
                        <th>Cédula</th>
                        <th>Correo</th>
                        <th>N° Factura</th>
                        <th>Monto</th>
                        <th>Fecha registro</th>
                        <th>¿Por qué mi papá merece el balón?</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($winners as $i => $inv)
                        <tr>
                            <td class="num">{{ $i + 1 }}</td>
                            <td><strong>{{ $inv->user?->full_name ?? '—' }}</strong></td>
                            <td>{{ $inv->user?->cedula ?? '—' }}</td>
                            <td>{{ $inv->user?->email ?? '—' }}</td>
                            <td>{{ $inv->invoice_number }}</td>
                            <td>${{ number_format((float)$inv->purchase_amount, 2) }}</td>
                            <td style="white-space:nowrap">{{ $inv->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="reason-text">{{ $inv->dad_reason ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="acta-footer">
                <div class="sign-box">
                    <div class="sign-line"></div>
                    <div class="sign-label">Representante Super Carnes</div>
                </div>
                <div class="sign-box">
                    <div class="sign-line"></div>
                    <div class="sign-label">Testigo / Notario</div>
                </div>
            </div>
        @endif
    </div>
</main>

</body>
</html>
