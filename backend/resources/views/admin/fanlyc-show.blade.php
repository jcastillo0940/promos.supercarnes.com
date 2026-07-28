@extends('admin.layout')

@section('title', 'Fanlyc — Detalle')
@section('subtitle', 'Revision manual')

@section('topbar-actions')
    <a class="topbar-action hide-mobile" href="{{ route('admin.fanlyc') }}">Volver</a>
    <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit">Cerrar sesión</button>
    </form>
@endsection

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
                <h1>Factura {{ $invoice->invoice_number ?? $invoice->cufe }}</h1>
                <p>Estado actual: <span class="badge badge-{{ $invoice->status === 'approved' ? 'green' : ($invoice->status === 'pending_review' ? 'yellow' : 'red') }}">{{ $invoice->status }}</span></p>
            </div>
        </div>

        <div class="page-section">
            <div class="form-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                <div class="field"><label>Cliente</label><div>{{ $invoice->user?->full_name ?? $invoice->user?->name }} ({{ $invoice->user?->cedula }})</div></div>
                <div class="field"><label>Contacto</label><div>{{ $invoice->user?->email }} · {{ $invoice->user?->phone }}</div></div>
                <div class="field"><label>Sucursal</label><div>{{ $invoice->branch?->name ?? 'No determinada' }}</div></div>
                <div class="field"><label>Zona</label><div>{{ $invoice->fanlycZone?->name ?? 'No determinada' }}</div></div>
                <div class="field"><label>Monto</label><div>{{ $invoice->purchase_amount !== null ? '$'.number_format((float) $invoice->purchase_amount, 2) : '—' }}</div></div>
                <div class="field"><label>Emitida</label><div>{{ $invoice->issued_at?->format('d/m/Y H:i') ?? '—' }}</div></div>
                <div class="field"><label>RUC emisor</label><div>{{ $invoice->issuer_ruc ?? '—' }}</div></div>
                <div class="field"><label>Nombre emisor</label><div>{{ $invoice->issuer_name ?? '—' }}</div></div>
                <div class="field"><label>Chequeo de SKU</label><div>{{ $invoice->sku_check_status }}</div></div>
                <div class="field"><label>Cupon</label><div>{{ $invoice->coupon?->code ?? 'Aun no emitido' }}{{ $invoice->coupon ? ' — '.$invoice->coupon->status : '' }}</div></div>
            </div>

            @if($invoice->validation_notes)
                <div class="notice" style="margin-top:1rem;">{{ $invoice->validation_notes }}</div>
            @endif
        </div>

        <div class="page-section" style="border-top:1px solid #e5e7eb;">
            <p class="sidebar-title">Respuesta cruda del verificador (para revision manual)</p>
            <pre style="background:#0f172a;color:#e2e8f0;padding:1rem;border-radius:12px;overflow:auto;max-height:280px;font-size:.78rem;">{{ json_encode($invoice->dgi_response_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>

            @if($invoice->sku_check_payload)
                <p class="sidebar-title" style="margin-top:1rem;">Fragmento usado para el chequeo de SKU</p>
                <pre style="background:#0f172a;color:#e2e8f0;padding:1rem;border-radius:12px;overflow:auto;max-height:200px;font-size:.78rem;">{{ json_encode($invoice->sku_check_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            @endif
        </div>

        @if($invoice->status === 'pending_review')
            <div class="page-section" style="border-top:1px solid #e5e7eb;">
                <div class="responsive-actions">
                    <form method="POST" action="{{ route('admin.fanlyc.approve', $invoice) }}">
                        @csrf
                        <button class="btn btn-green" type="submit">Aprobar y emitir cupon</button>
                    </form>
                </div>
                <form method="POST" action="{{ route('admin.fanlyc.reject', $invoice) }}" class="stack" style="margin-top:1rem;">
                    @csrf
                    <div class="field">
                        <label for="reason">Motivo de rechazo</label>
                        <textarea id="reason" name="reason" rows="3" required style="width:100%;padding:.8rem;border:1px solid #cbd5e1;border-radius:10px;"></textarea>
                    </div>
                    <div class="responsive-actions">
                        <button class="btn btn-red" type="submit">Rechazar</button>
                    </div>
                </form>
            </div>
        @endif

        @if($invoice->coupon && $invoice->coupon->status === 'issued')
            <div class="page-section" style="border-top:1px solid #e5e7eb;">
                <form method="POST" action="{{ route('admin.fanlyc.void-coupon', $invoice->coupon) }}" class="stack" onsubmit="return confirm('¿Anular este cupon?');">
                    @csrf
                    <div class="field">
                        <label for="void_reason">Motivo de anulacion</label>
                        <textarea id="void_reason" name="void_reason" rows="3" required style="width:100%;padding:.8rem;border:1px solid #cbd5e1;border-radius:10px;"></textarea>
                    </div>
                    <div class="responsive-actions">
                        <button class="btn btn-red" type="submit">Anular cupon</button>
                    </div>
                </form>
            </div>
        @endif
    </div>
@endsection
