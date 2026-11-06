@extends('admin.layout')

@section('title', 'Saba')
@section('subtitle', 'Emprende tu Sueño con Saba')

@section('topbar-actions')
    <a class="topbar-action hide-mobile" href="{{ route('admin.saba.export', request()->only(['status', 'search', 'branch_id', 'winners'])) }}">Exportar CSV</a>
    <a class="topbar-action hide-mobile" href="{{ route('admin.saba.settings') }}">Configuración y SKUs</a>
    <a class="topbar-action hide-mobile" href="/saba" target="_blank" rel="noopener">Ver página ↗</a>
@endsection

@section('content')
    <style>
        .saba-admin textarea { width:100%; min-height:110px; border:1px solid var(--bo-border-dark); border-radius:10px; padding:.65rem .8rem; font:inherit; font-size:.95rem; line-height:1.5; resize:vertical; background:var(--bo-surface); color:var(--bo-fg); }
        .saba-admin textarea:focus { outline:none; border-color:var(--bo-accent); box-shadow:0 0 0 2px rgba(192,18,44,.1); }
    </style>
    <div class="saba-admin">
    @if (session('status'))
        <div class="success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <div class="page-card">
        <div class="page-title">
            <div>
                <h1>{{ $campaign->name }}</h1>
                <p>
                    Estado:
                    <span class="badge badge-{{ $isOpen ? 'green' : ($campaign->status === 'active' ? 'yellow' : 'gray') }}">
                        {{ $isOpen ? 'Recibiendo participaciones' : ($campaign->status === 'active' ? 'Activa fuera de fechas' : (['draft' => 'Borrador', 'paused' => 'Pausada', 'archived' => 'Archivada', 'active' => 'Activa'][$campaign->status] ?? $campaign->status)) }}
                    </span>
                    · {{ $campaign->starts_at?->timezone('America/Panama')->format('d/m/Y') }} al {{ $campaign->ends_at?->timezone('America/Panama')->format('d/m/Y') }}
                    · {{ $productCount }} SKU(s) cargados
                </p>
            </div>
            <div class="responsive-actions">
                @if($campaign->status === 'active')
                    <form method="POST" action="{{ route('admin.saba.status') }}" data-confirm="¿Pausar la promoción Saba? Las participantes ya no podrán registrarse mientras esté pausada.">
                        @csrf
                        <input type="hidden" name="status" value="paused">
                        <button class="btn btn-gray" type="submit">Pausar promoción</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.saba.status') }}">
                        @csrf
                        <input type="hidden" name="status" value="active">
                        <button class="btn btn-green" type="submit" @disabled($productCount === 0)>Activar promoción</button>
                    </form>
                @endif
                <a class="btn btn-gray" href="{{ route('admin.saba.settings') }}">Configuración y SKUs</a>
            </div>
        </div>

        @if($productCount === 0)
            <div class="page-section">
                <div class="notice">Todavía no hay SKUs de Saba cargados. Cárgalos en <a href="{{ route('admin.saba.settings') }}">Configuración y SKUs</a> para poder activar la promoción.</div>
            </div>
        @endif

        <div class="page-section" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.75rem;">
            <div class="page-card" style="padding:1rem;"><small>Total</small><h2 style="margin:.25rem 0 0;">{{ number_format($counts->sum()) }}</h2></div>
            <div class="page-card" style="padding:1rem;"><small>Aprobadas</small><h2 style="margin:.25rem 0 0;">{{ number_format($counts['approved'] ?? 0) }}</h2></div>
            <div class="page-card" style="padding:1rem;"><small>En revisión</small><h2 style="margin:.25rem 0 0;">{{ number_format($counts['pending_review'] ?? 0) }}</h2></div>
            <div class="page-card" style="padding:1rem;"><small>Rechazadas</small><h2 style="margin:.25rem 0 0;">{{ number_format($counts['rejected'] ?? 0) }}</h2></div>
            <div class="page-card" style="padding:1rem;"><small>Ganadoras elegidas</small><h2 style="margin:.25rem 0 0;">{{ $winnerCount }} / {{ $winnerSlots }}</h2></div>
        </div>
    </div>

    <div class="page-card" style="margin-top:1rem;">
        <div class="page-title">
            <div>
                <h1>Participantes</h1>
                <p>Abre cada participación para leer la historia, revisar la factura, aprobar o rechazar y elegir ganadoras.</p>
            </div>
        </div>

        <div class="page-section">
            <form method="GET" action="{{ route('admin.saba') }}" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end;">
                <div class="field" style="min-width:220px;">
                    <label>Buscar</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cédula, nombre, correo, teléfono o CUFE">
                </div>
                <div class="field" style="min-width:170px;">
                    <label>Estado</label>
                    <select name="status">
                        <option value="">Todos</option>
                        @foreach(\App\Models\SabaEntry::STATUSES as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }} ({{ $counts[$value] ?? 0 }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="field" style="min-width:170px;">
                    <label>Sucursal</label>
                    <select name="branch_id">
                        <option value="">Todas</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field" style="min-width:150px;">
                    <label>Ganadoras</label>
                    <select name="winners">
                        <option value="">Todas las participantes</option>
                        <option value="1" @selected(request('winners') === '1')>Solo ganadoras</option>
                    </select>
                </div>
                <div style="display:flex;gap:.5rem;">
                    <button class="btn btn-gray" type="submit">Filtrar</button>
                    <a class="btn btn-gray" href="{{ route('admin.saba.export', request()->only(['status', 'search', 'branch_id', 'winners'])) }}">Exportar CSV</a>
                </div>
            </form>
        </div>

        <div class="page-section">
            @if($entries->isEmpty())
                <div class="empty">No hay participaciones con estos filtros.</div>
            @else
                <div class="table-shell">
                    <table class="wide">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Participante</th>
                                <th>Contacto</th>
                                <th>Historia</th>
                                <th>Sucursal</th>
                                <th>Estado</th>
                                <th>Ganadora</th>
                                <th>Registrada</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($entries as $entry)
                                <tr>
                                    <td data-label="#">{{ $entry->id }}</td>
                                    <td data-label="Participante">
                                        <strong>{{ $entry->full_name }}</strong><br>
                                        <small style="color:#94a3b8;">{{ $entry->cedula }} · {{ $entry->birthdate?->age }} años</small>
                                    </td>
                                    <td data-label="Contacto">
                                        {{ $entry->phone }}<br>
                                        <small style="color:#94a3b8;">{{ $entry->email }}</small>
                                    </td>
                                    <td data-label="Historia" style="max-width:320px;">
                                        <small>{{ \Illuminate\Support\Str::limit($entry->story, 140) }}</small>
                                    </td>
                                    <td data-label="Sucursal">{{ $entry->branch?->name ?? '—' }}</td>
                                    <td data-label="Estado">
                                        <span class="badge badge-{{ match($entry->status) { 'approved' => 'green', 'pending_review' => 'yellow', default => 'red' } }}">{{ $entry->statusLabel() }}</span>
                                        @if($entry->source === 'whatsapp')<br><small style="color:#94a3b8;">WhatsApp</small>@endif
                                        @if($entry->source === 'manual_invoice')<br><small style="color:#94a3b8;">Sin CUFE</small>@endif
                                    </td>
                                    <td data-label="Ganadora">
                                        @if($entry->winner_position)
                                            <span class="badge badge-green">Puesto {{ $entry->winner_position }}</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td data-label="Registrada">{{ $entry->created_at?->timezone('America/Panama')->format('d/m/Y H:i') }}</td>
                                    <td data-label="Acciones"><a class="btn btn-gray" href="{{ route('admin.saba.show', $entry) }}">Ver</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="margin-top:1rem;">{{ $entries->links() }}</div>
            @endif
        </div>
    </div>

    <div class="page-card" style="margin-top:1rem;">
        <div class="page-title">
            <div>
                <h1>Registrar participación por WhatsApp</h1>
                <p>Úsalo cuando una clienta envía su factura y datos por WhatsApp. Se aplican las mismas validaciones que en la página (factura, fechas, SKUs Saba y una participación por cédula).</p>
            </div>
        </div>
        <div class="page-section">
            <form method="POST" action="{{ route('admin.saba.manual-register') }}" class="form-grid" style="grid-template-columns:repeat(2,minmax(0,1fr));">
                @csrf
                <div class="field">
                    <label for="manual_full_name">Nombre completo</label>
                    <input id="manual_full_name" name="full_name" type="text" value="{{ old('full_name') }}" required>
                </div>
                <div class="field">
                    <label for="manual_cedula">Cédula</label>
                    <input id="manual_cedula" name="cedula" type="text" value="{{ old('cedula') }}" placeholder="8-123-4567" required>
                </div>
                <div class="field">
                    <label for="manual_phone">Teléfono</label>
                    <input id="manual_phone" name="phone" type="text" value="{{ old('phone') }}" required>
                </div>
                <div class="field">
                    <label for="manual_email">Correo</label>
                    <input id="manual_email" name="email" type="email" value="{{ old('email') }}" required>
                </div>
                <div class="field">
                    <label for="manual_birthdate">Fecha de nacimiento</label>
                    <input id="manual_birthdate" name="birthdate" type="date" value="{{ old('birthdate') }}" required>
                </div>
                <div class="field">
                    <label for="manual_invoice_type">Tipo de factura</label>
                    <select id="manual_invoice_type" name="invoice_type">
                        <option value="cufe" @selected(old('invoice_type', 'cufe') === 'cufe')>Con CUFE o QR</option>
                        <option value="series" @selected(old('invoice_type') === 'series')>Sin CUFE (serie de factura)</option>
                    </select>
                </div>
                <div class="field" data-invoice-type="cufe">
                    <label for="manual_qr">CUFE o contenido del QR</label>
                    <input id="manual_qr" name="qr_raw_text" type="text" value="{{ old('qr_raw_text') }}" required>
                </div>
                <div class="field" data-invoice-type="series">
                    <label for="manual_series">Serie de factura</label>
                    <input id="manual_series" name="invoice_series" type="text" value="{{ old('invoice_series') }}" placeholder="BCHT-1111" maxlength="60" required>
                </div>
                <div class="field" data-invoice-type="series">
                    <label for="manual_invoice_date">Fecha de la factura</label>
                    <input id="manual_invoice_date" name="invoice_date" type="date" value="{{ old('invoice_date') }}" required>
                </div>
                <div class="field" data-invoice-type="series">
                    <label for="manual_branch">Sucursal</label>
                    <select id="manual_branch" name="branch_id" required>
                        <option value="">Selecciona una sucursal</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field" data-invoice-type="series">
                    <label for="manual_amount">Monto de la factura</label>
                    <input id="manual_amount" name="purchase_amount" type="number" step="0.01" min="0.01" value="{{ old('purchase_amount') }}" required>
                </div>
                <div class="field" data-invoice-type="series">
                    <label for="manual_product">Producto</label>
                    <select id="manual_product" name="product_rule_id" required>
                        <option value="">Selecciona un producto</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" @selected((string) old('product_rule_id') === (string) $product->id)>{{ $product->product_name }}{{ $product->presentation ? ' · '.$product->presentation : '' }} ({{ $product->barcode }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="field" data-invoice-type="series">
                    <label for="manual_quantity">Unidades</label>
                    <input id="manual_quantity" name="product_quantity" type="number" step="1" min="1" value="{{ old('product_quantity', 1) }}" required>
                </div>
                <p data-invoice-type="series" style="grid-column:1 / -1;margin:0;color:#64748b;">Las facturas sin CUFE no se validan con la DGI: quedan En revisión hasta que alguien las apruebe contra la factura física.</p>
                <div class="field" style="grid-column:1 / -1;">
                    <label for="manual_story">¿Por qué merece ganar?</label>
                    <textarea id="manual_story" name="story" rows="4" required>{{ old('story') }}</textarea>
                </div>
                <div class="field" style="grid-column:1 / -1;">
                    <label style="display:flex;gap:.5rem;align-items:center;">
                        <input type="checkbox" name="confirmed_eligibility" value="1" required style="width:auto;">
                        Confirmé con la clienta que es mujer, mayor de edad y acepta las bases de la promoción.
                    </label>
                </div>
                <div style="grid-column:1 / -1;">
                    <button class="btn btn-red" type="submit">Registrar participación</button>
                </div>
            </form>
        </div>
    </div>
    </div>
    <script>
        (() => {
            const type = document.getElementById('manual_invoice_type');
            const sync = () => document.querySelectorAll('[data-invoice-type]').forEach((block) => {
                const active = block.dataset.invoiceType === type.value;
                block.style.display = active ? '' : 'none';
                block.querySelectorAll('input, select').forEach((field) => { field.disabled = !active; });
            });
            type.addEventListener('change', sync);
            sync();
        })();
    </script>
@endsection
