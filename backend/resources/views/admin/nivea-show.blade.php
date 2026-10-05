@extends('admin.layout')

@section('title', 'NIVEA · '.$entry->full_name)
@section('subtitle', 'Participación #'.$entry->id)

@section('topbar-actions')
    <a class="topbar-action" href="{{ route('admin.nivea') }}">← Participantes</a>
@endsection

@section('content')
    <style>
        .nivea-admin textarea { width:100%; min-height:110px; border:1px solid var(--bo-border-dark); border-radius:10px; padding:.65rem .8rem; font:inherit; font-size:.95rem; line-height:1.5; resize:vertical; background:var(--bo-surface);color:var(--bo-fg); }
        .nivea-admin textarea:focus { outline:none; border-color:var(--bo-accent); box-shadow:0 0 0 2px rgba(192,18,44,.1); }
    </style>
    <div class="nivea-admin">
    @if (session('status'))
        <div class="success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <div class="page-card">
        <div class="page-title">
            <div>
                <h1>{{ $entry->full_name }}</h1>
                <p>
                    <span class="badge badge-{{ match($entry->status) { 'approved' => 'green', 'pending_review' => 'yellow', default => 'red' } }}">{{ $entry->statusLabel() }}</span>
                    @if($entry->winner_position)
                        <span class="badge badge-green">Ganadora · puesto {{ $entry->winner_position }}</span>
                    @endif
                    · Registrada {{ $entry->created_at?->timezone('America/Panama')->format('d/m/Y H:i') }}
                    · Origen: {{ $entry->source === 'whatsapp' ? 'WhatsApp (backoffice)' : 'Página web' }}
                </p>
            </div>
        </div>

        <div class="page-section" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1rem;">
            <div>
                <h3>Datos de la registrante</h3>
                <table>
                    <tr><th>Cédula</th><td>{{ $entry->cedula }}</td></tr>
                    <tr><th>Teléfono</th><td>{{ $entry->phone }}</td></tr>
                    <tr><th>Correo</th><td>{{ $entry->email }}</td></tr>
                    <tr><th>Nacimiento</th><td>{{ $entry->birthdate?->format('d/m/Y') }} ({{ $entry->birthdate?->age }} años)</td></tr>
                    <tr><th>Declaró autorización</th><td>{{ $entry->declared_eligibility_at?->timezone('America/Panama')->format('d/m/Y H:i') ?? '—' }}</td></tr>
                    <tr><th>Bases aceptadas</th><td>{{ $entry->accepted_terms_at?->timezone('America/Panama')->format('d/m/Y H:i') ?? '—' }} @if($entry->terms_version)<small>(v. {{ $entry->terms_version }})</small>@endif</td></tr>
                </table>
            </div>
            <div>
                <h3>Factura</h3>
                <table>
                    <tr><th>Sucursal</th><td>{{ $entry->branch?->name ?? '—' }}</td></tr>
                    <tr><th>Fecha</th><td>{{ $entry->issued_at?->timezone('America/Panama')->format('d/m/Y H:i') ?? '—' }}</td></tr>
                    <tr><th>Monto</th><td>${{ number_format((float) $entry->purchase_amount, 2) }}</td></tr>
                    <tr><th>Productos</th><td>
                        <span class="badge badge-{{ $entry->product_check_status === 'matched' ? 'green' : 'yellow' }}">{{ $entry->product_check_status === 'matched' ? 'NIVEA detectado' : 'Sin detalle — revisar' }}</span>
                        @foreach($entry->matched_products ?? [] as $item)
                            <br><small>{{ rtrim(rtrim(number_format((float) ($item['quantity'] ?? 0), 3), '0'), '.') }} × {{ $item['description'] ?? $item['barcode'] ?? '' }} <span style="color:#94a3b8;">({{ $item['barcode'] ?? '' }})</span></small>
                        @endforeach
                    </td></tr>
                    <tr><th>CUFE</th><td style="word-break:break-all;"><small>{{ $entry->cufe }}</small></td></tr>
                    <tr><th>Emisor</th><td>{{ $entry->issuer_name }} <small>{{ $entry->issuer_ruc }}</small></td></tr>
                </table>
            </div>
        </div>

        <div class="page-section">
            <h3>Mamá postulada</h3>
            <p style="font-size:1.05rem;font-weight:700;color:#001a4d;">{{ $entry->nominee_name }}</p>
        </div>

        <div class="page-section">
            <h3>¿Por qué merece ganar?</h3>
            <div style="white-space:pre-line;line-height:1.6;background:#f0f6ff;border:1px solid #bfdbfe;border-radius:10px;padding:1rem;">{{ $entry->story }}</div>
        </div>

        @if($entry->validation_notes)
            <div class="page-section">
                <div class="notice" style="white-space:pre-line;">{{ $entry->validation_notes }}</div>
            </div>
        @endif

        @if($entry->reviewed_at)
            <div class="page-section">
                <small style="color:#94a3b8;">Última revisión: {{ $entry->reviewed_at->timezone('America/Panama')->format('d/m/Y H:i') }} por {{ $entry->reviewedBy?->email ?? '—' }}</small>
            </div>
        @endif
    </div>

    <div class="page-card" style="margin-top:1rem;">
        <div class="page-title"><div><h1>Revisión</h1><p>Rechazar libera la cédula para que la persona pueda volver a participar con otra factura.</p></div></div>
        <div class="page-section" style="display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end;">
            @if($entry->status !== 'approved')
                <form method="POST" action="{{ route('admin.nivea.approve', $entry) }}">
                    @csrf
                    <button class="btn btn-green" type="submit">Aprobar participación</button>
                </form>
            @endif
            @if($entry->status !== 'rejected')
                <form method="POST" action="{{ route('admin.nivea.reject', $entry) }}" style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:flex-end;" data-confirm="¿Rechazar esta participación? Se liberará la cédula para que la persona pueda volver a participar.">
                    @csrf
                    <div class="field" style="min-width:280px;">
                        <label>Motivo del rechazo</label>
                        <input type="text" name="reason" required placeholder="Ej.: la factura no incluye productos NIVEA">
                    </div>
                    <button class="btn btn-red" type="submit">Rechazar</button>
                </form>
            @endif
        </div>
    </div>

    <div class="page-card" style="margin-top:1rem;">
        <div class="page-title"><div><h1>Selección de ganadora</h1><p>Puestos 1 a {{ $winnerSlots }}. Los 10 puestos son seleccionados por el jurado de NIVEA y Super Carnes.</p></div></div>
        <div class="page-section" style="display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end;">
            @if($entry->status === 'approved')
                <form method="POST" action="{{ route('admin.nivea.winner.select', $entry) }}" style="display:flex;gap:.5rem;align-items:flex-end;">
                    @csrf
                    <div class="field" style="min-width:220px;">
                        <label>Puesto</label>
                        <select name="winner_position" required>
                            <option value="">Elegir puesto…</option>
                            @for($position = 1; $position <= $winnerSlots; $position++)
                                <option value="{{ $position }}" @selected($entry->winner_position === $position) @disabled(in_array($position, $takenPositions, true))>
                                    {{ $position }} @if(in_array($position, $takenPositions, true)) (ocupado)@endif
                                </option>
                            @endfor
                        </select>
                    </div>
                    <button class="btn btn-green" type="submit">{{ $entry->winner_position ? 'Cambiar puesto' : 'Elegir como ganadora' }}</button>
                </form>
                @if($entry->winner_position)
                    <form method="POST" action="{{ route('admin.nivea.winner.remove', $entry) }}" data-confirm="¿Quitar a esta participante como ganadora? El puesto quedará libre.">
                        @csrf
                        <button class="btn btn-gray" type="submit">Quitar selección</button>
                    </form>
                @endif
            @else
                <div class="notice">Solo las participaciones aprobadas pueden elegirse como ganadoras.</div>
            @endif
        </div>
    </div>

    @if($otherEntries->isNotEmpty())
        <div class="page-card" style="margin-top:1rem;">
            <div class="page-title"><div><h1>Otras participaciones con esta cédula</h1></div></div>
            <div class="page-section">
                @foreach($otherEntries as $other)
                    <p><a href="{{ route('admin.nivea.show', $other) }}">#{{ $other->id }}</a> · {{ $other->statusLabel() }} · {{ $other->created_at?->timezone('America/Panama')->format('d/m/Y H:i') }}</p>
                @endforeach
            </div>
        </div>
    @endif
    </div>
@endsection
