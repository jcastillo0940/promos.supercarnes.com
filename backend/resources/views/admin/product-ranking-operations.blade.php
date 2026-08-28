@extends('admin.layout')

@section('title', 'Operación Malta Vigor')
@section('subtitle', 'Ranking, ganadores, auditoría y fraude')

@section('topbar-actions')
    <a class="topbar-action hide-mobile" href="{{ route('admin.invoice-backoffice.campaigns.edit', $campaign) }}">Configurar promo</a>
    <a class="topbar-action hide-mobile" href="{{ route('admin.invoices', ['campaign_id' => $campaign->id]) }}">Todas las facturas</a>
    <form method="POST" action="{{ route('admin.logout') }}">@csrf<button type="submit">Cerrar sesión</button></form>
@endsection

@section('sidebar-actions')
    <a class="active" href="{{ route('admin.campaigns.product-ranking.operations', $campaign) }}">Operación Malta Vigor <small>Ranking y control</small></a>
    <a href="{{ route('admin.invoice-backoffice.campaigns.edit', $campaign) }}">Configurar promoción <small>Códigos, términos y entrega</small></a>
    <a href="{{ route('admin.invoices', ['campaign_id' => $campaign->id]) }}">Facturas de la promo <small>Historial completo</small></a>
@endsection

@push('styles')
<style>
    .ops-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem}.metric{padding:1rem;border:1px solid #e2e8f0;border-radius:14px;background:#f8fafc}.metric span{display:block;color:#64748b;font-size:.78rem;font-weight:700;text-transform:uppercase}.metric strong{display:block;margin-top:.35rem;font-size:1.6rem}.two-col{display:grid;grid-template-columns:minmax(360px,.8fr) minmax(0,1.2fr);gap:1rem}.field textarea{width:100%;min-height:88px;padding:.65rem .8rem;border:1px solid #cbd5e1;border-radius:10px;font:inherit}.product-row{display:grid;grid-template-columns:minmax(0,1fr) 130px;gap:.75rem;margin-top:.6rem}.section-head{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap}.section-head h2{margin:0}.muted{color:#64748b}.mini{font-size:.78rem}.danger-box{border:1px solid #fecaca;background:#fff7f7;border-radius:12px;padding:.75rem}.audit-payload{max-width:420px;white-space:normal;word-break:break-word}.winner-card{padding:.8rem;border:1px solid #e2e8f0;border-radius:12px;margin-bottom:.7rem}.winner-card form{display:grid;grid-template-columns:1fr auto;gap:.5rem;margin-top:.65rem}.winner-card input{min-height:40px;padding:.5rem;border:1px solid #cbd5e1;border-radius:8px}.status-open{color:#991b1b;font-weight:800}@media(max-width:1000px){.ops-grid{grid-template-columns:repeat(2,1fr)}.two-col{grid-template-columns:1fr}}@media(max-width:600px){.ops-grid{grid-template-columns:1fr}.product-row,.winner-card form{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
    @if(session('status'))<div class="success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="error"><strong>No se guardó el cambio.</strong><br>{{ $errors->first() }}</div>@endif

    <div class="page-card" style="margin-bottom:1rem">
        <div class="page-title">
            <div><h1>{{ $campaign->name }}</h1><p>Centro operativo específico de la promoción por unidades.</p></div>
            <div class="responsive-actions">
                <span class="badge badge-{{ $campaign->ranking_frozen_at ? 'yellow' : 'green' }}">{{ $campaign->ranking_frozen_at ? 'Ranking congelado' : 'Ranking en vivo' }}</span>
                @if(!$campaign->ranking_frozen_at)
                    <form method="POST" action="{{ route('admin.campaigns.product-ranking.freeze', $campaign) }}" onsubmit="return confirm('Esto congelará el ranking y seleccionará {{ $winnerSlots }} ganadores y los suplentes en orden. ¿Continuar?')">@csrf<button class="btn btn-red" type="submit">Congelar y seleccionar</button></form>
                @endif
            </div>
        </div>
        <div class="page-section ops-grid">
            <div class="metric"><span>Participantes con unidades</span><strong>{{ number_format($ranking->count()) }}</strong></div>
            <div class="metric"><span>Unidades aprobadas</span><strong>{{ number_format($ranking->sum('total_units')) }}</strong></div>
            <div class="metric"><span>Ganadores principales</span><strong>{{ $winners->where('status','selected')->count() }} / {{ $winnerSlots }}</strong></div>
            <div class="metric"><span>Alertas abiertas</span><strong>{{ $fraudFlags->where('status','open')->count() }}</strong></div>
        </div>
    </div>

    <div class="two-col" style="margin-bottom:1rem">
        <div class="page-card">
            <div class="page-title"><div><h2>Factura manual auditada</h2><p>Solo para excepciones verificadas por el equipo.</p></div></div>
            <div class="page-section">
                @if($campaign->ranking_frozen_at)
                    <div class="notice">La carga manual está bloqueada porque el ranking ya fue congelado.</div>
                @else
                    <form method="POST" action="{{ route('admin.campaigns.product-ranking.manual-invoice', $campaign) }}" class="form-grid">
                        @csrf
                        <div class="form-grid" style="grid-template-columns:1fr 1fr">
                            <div class="field"><label>Cédula</label><input name="cedula" value="{{ old('cedula') }}" required></div>
                            <div class="field"><label>Correo</label><input name="email" type="email" value="{{ old('email') }}" required></div>
                        </div>
                        <div class="field"><label>Nombre completo <span class="muted">(obligatorio solo si es nuevo)</span></label><input name="full_name" value="{{ old('full_name') }}"></div>
                        <div class="form-grid" style="grid-template-columns:1fr 1fr">
                            <div class="field"><label>Número de factura</label><input name="invoice_number" value="{{ old('invoice_number') }}" required></div>
                            <div class="field"><label>Fecha de compra</label><input name="issued_at" type="datetime-local" value="{{ old('issued_at') }}" required></div>
                        </div>
                        <div>
                            <strong class="mini">PRODUCTOS Y CANTIDADES</strong>
                            @for($i=0;$i<5;$i++)
                                <div class="product-row">
                                    <div class="field"><select name="products[{{ $i }}][barcode]" {{ $i === 0 ? 'required' : '' }}><option value="">Seleccionar código…</option>@foreach($campaign->productRules->where('is_active',true) as $rule)<option value="{{ $rule->barcode }}" @selected(old("products.$i.barcode") === $rule->barcode)>{{ $rule->product_name }} {{ $rule->presentation }} — {{ $rule->barcode }}</option>@endforeach</select></div>
                                    <div class="field"><input name="products[{{ $i }}][quantity]" type="number" min="1" max="1000" value="{{ old("products.$i.quantity") }}" placeholder="Cantidad" {{ $i === 0 ? 'required' : '' }}></div>
                                </div>
                            @endfor
                        </div>
                        <div class="field"><label>Motivo y evidencia de la carga manual</label><textarea name="reason" required placeholder="Ej.: factura verificada contra foto enviada por el cliente; aprobación de…">{{ old('reason') }}</textarea></div>
                        <div class="notice mini">La cédula y el correo deben pertenecer a la misma persona. Se bloquean duplicados, empleados, participantes descalificados y códigos no oficiales. Cada cambio queda firmado en auditoría.</div>
                        <button class="btn btn-green" type="submit">Registrar factura y acreditar unidades</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="page-card">
            <div class="page-title"><div><h2>Ganadores y suplentes</h2><p>Resultado congelado y reemplazo ordenado.</p></div></div>
            <div class="page-section">
                @forelse($winners as $winner)
                    <div class="winner-card">
                        <div class="section-head"><strong>#{{ $winner->leaderboard_position }} {{ $winner->user?->full_name ?? $winner->user?->name }}</strong><span class="badge badge-{{ $winner->status === 'selected' ? 'green' : ($winner->status === 'alternate' ? 'yellow' : 'red') }}">{{ $winner->status === 'selected' ? 'Ganador' : ($winner->status === 'alternate' ? 'Suplente '.$winner->alternate_position : 'Reemplazado') }}</span></div>
                        <div class="muted mini">{{ $winner->user?->cedula }} · {{ $winner->user?->email }} · {{ number_format($winner->total_units) }} unidades</div>
                        @if($winner->status === 'selected' && $winners->where('status','alternate')->isNotEmpty())
                            <form method="POST" action="{{ route('admin.campaigns.product-ranking.replace-winner', [$campaign, $winner]) }}" onsubmit="return confirm('Se descalificará este ganador y subirá el siguiente suplente. ¿Continuar?')">@csrf<input name="reason" placeholder="Motivo documentado del reemplazo" required minlength="8"><button class="btn btn-red" type="submit">Reemplazar</button></form>
                        @endif
                    </div>
                @empty
                    <div class="empty">Todavía no se ha congelado el ranking.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="page-card" style="margin-bottom:1rem">
        <div class="page-title"><div><h2>Ranking en tiempo real</h2><p>Orden: unidades descendentes y primer momento de alcance ascendente.</p></div></div>
        <div class="page-section table-shell"><table class="wide"><thead><tr><th>Pos.</th><th>Participante</th><th>Cédula</th><th>Correo</th><th>Unidades</th><th>Alcanzó</th><th>Elegibilidad</th></tr></thead><tbody>
            @forelse($ranking as $index=>$participant)
                @php($eligible = $participant->email && !$participant->is_employee && !$participant->disqualified_at && $participant->birthdate?->age >= $minimumAge)
                <tr><td>{{ $index+1 }}</td><td><strong>{{ $participant->full_name ?? $participant->name }}</strong><br><a class="mini" href="{{ route('admin.customers.history', $participant) }}">Revisar usuario</a></td><td>{{ $participant->cedula }}</td><td>{{ $participant->email }}</td><td><strong>{{ number_format($participant->total_units) }}</strong></td><td>{{ $participant->first_reached_at ? \Illuminate\Support\Carbon::parse($participant->first_reached_at)->format('d/m/Y H:i') : '—' }}</td><td><span class="badge badge-{{ $eligible ? 'green':'red' }}">{{ $eligible ? 'Elegible':'Revisar' }}</span></td></tr>
            @empty<tr><td colspan="7">Aún no hay unidades aprobadas.</td></tr>@endforelse
        </tbody></table></div>
    </div>

    <div class="page-card" style="margin-bottom:1rem">
        <div class="page-title"><div><h2>Facturas y productos detectados</h2><p>Últimas 40 facturas, incluyendo entradas manuales.</p></div></div>
        <div class="page-section table-shell"><table class="wide"><thead><tr><th>Factura</th><th>Participante</th><th>Origen</th><th>Productos</th><th>Unidades</th><th>Estado</th><th>Alertas</th></tr></thead><tbody>
            @forelse($invoices as $invoice)
                <tr><td><strong>{{ $invoice->invoice_number }}</strong><br><span class="mini muted">{{ $invoice->created_at?->format('d/m/Y H:i') }}</span></td><td>{{ $invoice->user?->full_name }}<br><span class="mini muted">{{ $invoice->user?->cedula }}</span></td><td>{{ data_get($invoice->dgi_response_payload,'source') === 'admin_manual_entry' ? 'Manual backoffice' : 'API factura' }}</td><td>@foreach($invoice->items as $item)<div class="mini">{{ $item->description }} · {{ $item->barcode }} × {{ (float)$item->quantity }}</div>@endforeach</td><td><strong>{{ $invoice->eligible_units }}</strong></td><td><span class="badge badge-{{ $invoice->validation_status === 'approved' ? 'green':'yellow' }}">{{ $invoice->validation_status }}</span></td><td>{{ $invoice->fraudFlags->where('status','open')->count() }}</td></tr>
            @empty<tr><td colspan="7">No hay facturas registradas.</td></tr>@endforelse
        </tbody></table></div>
    </div>

    <div class="two-col">
        <div class="page-card">
            <div class="page-title"><div><h2>Prevención de fraude</h2><p>Alertas asociadas a facturas de esta campaña.</p></div></div>
            <div class="page-section">
                @forelse($fraudFlags as $flag)
                    <div class="danger-box" style="margin-bottom:.75rem"><div class="section-head"><strong>{{ $flag->title }}</strong><span class="badge badge-{{ in_array($flag->severity,['critical','high']) ? 'red':'yellow' }}">{{ $flag->severity }}</span></div><p class="mini">{{ $flag->description }}</p><div class="mini muted">{{ $flag->user?->cedula }} · Factura {{ $flag->invoice?->invoice_number }} · Estado: <span class="{{ $flag->status === 'open' ? 'status-open':'' }}">{{ $flag->status }}</span></div>@if($flag->status === 'open')<form method="POST" action="{{ route('admin.campaigns.product-ranking.resolve-fraud', [$campaign,$flag]) }}" class="form-grid" style="margin-top:.75rem">@csrf<div class="field"><select name="status" required><option value="resolved">Fraude/riesgo confirmado y resuelto</option><option value="dismissed">Descartar alerta</option></select></div><div class="field"><textarea name="resolution_notes" required minlength="8" placeholder="Resultado de la revisión"></textarea></div><button class="btn btn-gray" type="submit">Cerrar revisión</button></form>@endif</div>
                @empty<div class="empty">No hay alertas antifraude para esta campaña.</div>@endforelse
            </div>
        </div>
        <div class="page-card">
            <div class="page-title"><div><h2>Bitácora de auditoría</h2><p>Acciones firmadas y encadenadas por hash.</p></div></div>
            <div class="page-section table-shell"><table><thead><tr><th>Fecha</th><th>Actor</th><th>Evento</th><th>Detalle</th></tr></thead><tbody>@forelse($auditEntries as $entry)<tr><td>{{ $entry->created_at?->format('d/m/Y H:i:s') }}</td><td>{{ $entry->user?->email ?? 'Sistema' }}</td><td>{{ $entry->event_type }}</td><td class="audit-payload mini">{{ json_encode($entry->payload, JSON_UNESCAPED_UNICODE) }}</td></tr>@empty<tr><td colspan="4">Sin acciones auditadas todavía.</td></tr>@endforelse</tbody></table></div>
        </div>
    </div>
@endsection
