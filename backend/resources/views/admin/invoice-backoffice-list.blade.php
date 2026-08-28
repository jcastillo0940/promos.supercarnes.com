@extends('admin.layout')

@section('title', 'Promociones')
@section('subtitle', 'Activa, pausa y administra tus campañas')

@section('topbar-actions')
    <a class="topbar-action hide-mobile" href="{{ route('admin.dashboard') }}">Dashboard</a>
    <a class="topbar-action hide-mobile" href="{{ route('admin.invoices') }}">Facturas</a>
    <a class="topbar-action hide-mobile" href="{{ route('admin.winners') }}">Ganadores</a>
    <a class="topbar-action hide-mobile" href="{{ route('admin.prize-delivery') }}">Entrega de premio</a>
    <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit">Cerrar sesión</button>
    </form>
@endsection

@push('styles')
    <style>
        .campaign-list { display: grid; gap: .85rem; }
        .campaign-row {
            display: flex; align-items: center; justify-content: space-between; gap: 1rem;
            padding: 1rem 1.1rem; border: 1px solid #e5e7eb; border-radius: 14px;
            background: #fff; box-shadow: 0 4px 14px rgba(15, 23, 42, .04);
        }
        .campaign-info { min-width: 0; display: grid; gap: .35rem; }
        .campaign-heading { display: flex; align-items: center; gap: .55rem; flex-wrap: wrap; }
        .campaign-heading strong { color: #0f172a; font-size: 1rem; }
        .campaign-slug { color: #64748b; font-size: .8rem; }
        .campaign-meta { display: flex; align-items: center; gap: .4rem .75rem; flex-wrap: wrap; color: #64748b; font-size: .8rem; }
        .campaign-readiness { display: flex; flex-wrap: wrap; gap: .3rem .7rem; color: #64748b; font-size: .76rem; }
        .campaign-readiness span::before { content: '•'; color: #16a34a; margin-right: .25rem; }
        .campaign-actions { display: flex; align-items: center; justify-content: flex-end; gap: .55rem; flex-wrap: wrap; flex-shrink: 0; }
        .campaign-actions .btn { min-width: 112px; text-align: center; }
        .empty-state { padding: 2rem; text-align: center; color: #64748b; border: 1px dashed #cbd5e1; border-radius: 14px; }
        @media (max-width: 680px) {
            .campaign-row { align-items: stretch; flex-direction: column; }
            .campaign-actions { justify-content: stretch; }
            .campaign-actions .btn { flex: 1; }
        }
    </style>
@endpush

@section('content')
    @if (session('status'))
        <div class="alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <div class="page-card">
        <div class="page-section">
            <div class="config-section-head">
                <div>
                    <h1>Promociones</h1>
                    <p>Controla el estado de cada campaña desde aquí. Usa “Editar” para abrir su configuración completa.</p>
                </div>
            </div>
        </div>

        <div class="page-section campaign-list" style="border-top:1px solid #e5e7eb;">
            @forelse ($campaigns as $campaign)
                @php
                    $mode = $campaign->participation_mode ?? 'points';
                    $statusLabels = ['active' => ['green', 'Activa'], 'paused' => ['gray', 'Pausada'], 'draft' => ['yellow', 'Borrador'], 'archived' => ['red', 'Archivada']];
                    [$statusColor, $statusLabel] = $statusLabels[$campaign->status] ?? ['gray', $campaign->status];
                @endphp
                <div class="campaign-row">
                    <div class="campaign-info">
                        <div class="campaign-heading">
                            <strong>{{ $campaign->name }}</strong>
                            <span class="campaign-slug">/{{ $campaign->slug }}</span>
                            <span class="badge badge-{{ $statusColor }}">{{ $statusLabel }}</span>
                            <span class="badge badge-{{ $mode === 'threshold_form' ? 'yellow' : ($mode === 'product_ranking' ? 'green' : 'gray') }}">{{ $mode === 'threshold_form' ? 'Umbral $' . number_format((float) ($campaign->entry_threshold_amount ?: 100), 0) : ($mode === 'product_ranking' ? 'Ranking por unidades' : 'Puntos y ranking') }}</span>
                        </div>
                        <div class="campaign-meta">
                            <span>{{ optional($campaign->starts_at)->format('d/m/Y H:i') }} — {{ optional($campaign->ends_at)->format('d/m/Y H:i') }}</span>
                            <span>{{ $campaign->status === 'active' ? 'Visible para clientes' : 'Fuera del catálogo' }}</span>
                        </div>
                        @if($mode === 'product_ranking')
                            <div class="campaign-readiness">
                                <span>{{ $campaign->productRules->where('is_active', true)->count() }} códigos activos</span>
                                <span>{{ $campaign->terms_approved_at ? 'Términos aprobados' : 'Términos pendientes' }}</span>
                                <span>{{ $campaign->delivery_location && $campaign->delivery_deadline ? 'Entrega configurada' : 'Entrega pendiente' }}</span>
                            </div>
                        @endif
                    </div>
                    <div class="campaign-actions">
                        <a class="btn btn-gray" href="{{ route('admin.invoice-backoffice.campaigns.edit', $campaign) }}">Editar</a>
                        <form method="POST" action="{{ route('admin.invoice-backoffice.campaigns.toggle-status', $campaign) }}">
                            @csrf
                            <button type="submit" class="btn {{ $campaign->status === 'active' ? 'btn-gray' : 'btn-green' }}" name="status" value="{{ $campaign->status === 'active' ? 'paused' : 'active' }}">
                                {{ $campaign->status === 'active' ? 'Apagar' : 'Prender' }}
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="empty-state">No hay promociones configuradas.</div>
            @endforelse
        </div>
    </div>
@endsection
