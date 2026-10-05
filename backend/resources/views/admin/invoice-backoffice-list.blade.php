@extends('admin.layout')

@section('title', 'Promociones')
@section('subtitle', 'Vista general')


@push('styles')
<style>
    .promo-toolbar { display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap; margin-bottom: 1.25rem; }
    .promo-filters { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
    .promo-search { padding: .55rem .8rem; border: 1px solid var(--bo-border-dark); border-radius: 10px; font: inherit; font-size: .875rem; min-width: 220px; background: var(--bo-surface); color: var(--bo-fg); }
    .promo-search:focus { outline: none; border-color: var(--bo-accent); box-shadow: 0 0 0 2px rgba(192,18,44,.1); }
    .filter-btn { padding: .45rem .8rem; border: 1px solid var(--bo-border); border-radius: 8px; background: var(--bo-surface); color: var(--bo-fg2); font: inherit; font-size: .8rem; font-weight: 600; cursor: pointer; transition: background .1s, color .1s; }
    .filter-btn:hover { background: var(--bo-bg); }
    .filter-btn.active { background: var(--bo-accent); color: #fff; border-color: var(--bo-accent); }
    .campaign-group-title {
        font-size: .7rem; letter-spacing: .1em; text-transform: uppercase;
        color: var(--bo-muted); font-weight: 700; margin: 1.25rem 0 .6rem;
        display: flex; align-items: center; gap: .5rem;
    }
    .campaign-group-title::after { content: ''; flex: 1; height: 1px; background: var(--bo-border); }
    .campaign-list { display: grid; gap: .65rem; }
    .campaign-row {
        display: flex; align-items: center; justify-content: space-between; gap: 1rem;
        padding: .9rem 1.1rem; border: 1px solid var(--bo-border); border-radius: 12px;
        background: var(--bo-surface); transition: box-shadow .15s, border-color .15s;
    }
    .campaign-row:hover { box-shadow: 0 4px 14px rgba(15,23,42,.07); border-color: var(--bo-border-dark); }
    .campaign-row.is-hidden { display: none; }
    .campaign-info { min-width: 0; display: grid; gap: .25rem; flex: 1; }
    .campaign-heading { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
    .campaign-heading strong { color: var(--bo-fg); font-size: .95rem; }
    .campaign-slug { color: var(--bo-muted); font-size: .76rem; font-family: monospace; }
    .campaign-meta { display: flex; align-items: center; gap: .35rem .65rem; flex-wrap: wrap; color: var(--bo-fg2); font-size: .78rem; }
    .campaign-readiness { display: flex; flex-wrap: wrap; gap: .25rem .6rem; font-size: .74rem; }
    .campaign-readiness .ok  { color: #16a34a; }
    .campaign-readiness .warn{ color: #d97706; }
    .campaign-actions { display: flex; align-items: center; justify-content: flex-end; gap: .5rem; flex-shrink: 0; }
    .toggle-switch { display: flex; align-items: center; gap: .4rem; color: var(--bo-fg2); font-size: .8rem; font-weight: 600; }
    .toggle-pill {
        position: relative; width: 38px; height: 22px; cursor: pointer;
        background: var(--bo-border-dark); border-radius: 999px; transition: background .2s; flex-shrink: 0;
        border: 0; padding: 0;
    }
    .toggle-pill::after {
        content: ''; position: absolute; top: 3px; left: 3px;
        width: 16px; height: 16px; border-radius: 50%; background: #fff;
        transition: transform .2s; box-shadow: 0 1px 3px rgba(0,0,0,.2);
    }
    .toggle-pill.is-on { background: #16a34a; }
    .toggle-pill.is-on::after { transform: translateX(16px); }
    .toggle-pill:disabled { opacity: .5; cursor: default; }
    .empty-state { padding: 2.5rem; text-align: center; color: var(--bo-fg2); border: 1px dashed var(--bo-border-dark); border-radius: 12px; }
    @media (max-width: 680px) {
        .campaign-row { flex-direction: column; align-items: stretch; }
        .campaign-actions { justify-content: flex-end; }
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
                <h1>Promociones</h1>
                <p>Entra a una campaña para operar. Usa "Editar" para su configuración completa.</p>
            </div>
            <a class="btn btn-green" href="{{ route('admin.invoice-backoffice') }}">+ Nueva promoción</a>
        </div>

        <div class="page-section">
            <div class="promo-toolbar">
                <div class="promo-filters">
                    <button class="filter-btn active" data-filter="all">Todas</button>
                    <button class="filter-btn" data-filter="active">Activas</button>
                    <button class="filter-btn" data-filter="paused">Pausadas</button>
                    <button class="filter-btn" data-filter="draft">Borrador</button>
                </div>
                <input class="promo-search" id="promo-search" type="search" placeholder="Buscar campaña…" aria-label="Buscar campaña">
            </div>

            @php
                $groups = [
                    'active'   => ['label' => 'Activas',            'campaigns' => $campaigns->where('status', 'active')],
                    'paused'   => ['label' => 'Pausadas',           'campaigns' => $campaigns->where('status', 'paused')],
                    'draft'    => ['label' => 'Borrador',           'campaigns' => $campaigns->where('status', 'draft')],
                    'archived' => ['label' => 'Archivadas',         'campaigns' => $campaigns->where('status', 'archived')],
                ];
                $modeLabel = ['points' => 'Puntos', 'threshold_form' => 'Umbral $', 'product_ranking' => 'Ranking/und.'];
                $modeColor = ['points' => 'gray', 'threshold_form' => 'yellow', 'product_ranking' => 'green'];
                $statusColor = ['active' => 'green', 'paused' => 'gray', 'draft' => 'yellow', 'archived' => 'red'];

                // Determine entry URL per campaign
                function campaignEntryUrl(\App\Models\Campaign $c): string {
                    if ($c->participation_mode === 'product_ranking') {
                        return route('admin.campaigns.product-ranking.operations', $c);
                    }
                    return route('admin.invoice-backoffice.campaigns.edit', $c);
                }
            @endphp

            @if($campaigns->isEmpty())
                <div class="empty-state">No hay promociones configuradas.</div>
            @else
                @foreach($groups as $statusKey => $group)
                    @if($group['campaigns']->isNotEmpty())
                        <p class="campaign-group-title" data-group="{{ $statusKey }}">{{ $group['label'] }}</p>
                        <div class="campaign-list" data-group="{{ $statusKey }}">
                            @foreach($group['campaigns'] as $campaign)
                                @php
                                    $mode = $campaign->participation_mode ?? 'points';
                                    $isActive = $campaign->status === 'active';

                                    // Entry URL: malta → operations, others → edit config
                                    if ($mode === 'product_ranking') {
                                        $entryUrl = route('admin.campaigns.product-ranking.operations', $campaign);
                                    } else {
                                        $entryUrl = route('admin.invoice-backoffice.campaigns.edit', $campaign);
                                    }
                                @endphp
                                <div class="campaign-row"
                                     data-status="{{ $campaign->status }}"
                                     data-search="{{ strtolower($campaign->name . ' ' . $campaign->slug) }}">
                                    <div class="campaign-info">
                                        <div class="campaign-heading">
                                            <strong>{{ $campaign->name }}</strong>
                                            <span class="campaign-slug">/{{ $campaign->slug }}</span>
                                            <span class="badge badge-{{ $modeColor[$mode] ?? 'gray' }}">
                                                {{ $modeLabel[$mode] ?? $mode }}
                                            </span>
                                        </div>
                                        <div class="campaign-meta">
                                            @if($campaign->starts_at || $campaign->ends_at)
                                                <span>
                                                    {{ optional($campaign->starts_at)->format('d/m/Y') }} —
                                                    {{ optional($campaign->ends_at)->format('d/m/Y') }}
                                                </span>
                                            @endif
                                        </div>
                                        @if($mode === 'product_ranking')
                                            <div class="campaign-readiness">
                                                <span class="{{ $campaign->productRules->where('is_active', true)->count() > 0 ? 'ok' : 'warn' }}">
                                                    {{ $campaign->productRules->where('is_active', true)->count() }} códigos activos
                                                </span>
                                                <span class="{{ $campaign->terms_approved_at ? 'ok' : 'warn' }}">
                                                    {{ $campaign->terms_approved_at ? 'Términos ✓' : 'Términos pendientes' }}
                                                </span>
                                                <span class="{{ $campaign->delivery_location && $campaign->delivery_deadline ? 'ok' : 'warn' }}">
                                                    {{ $campaign->delivery_location && $campaign->delivery_deadline ? 'Entrega ✓' : 'Entrega pendiente' }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="campaign-actions">
                                        {{-- Toggle activo/pausado (solo admin) --}}
                                        <form method="POST"
                                              action="{{ route('admin.invoice-backoffice.campaigns.toggle-status', $campaign) }}"
                                              class="toggle-switch"
                                              data-confirm="{{ $isActive ? '¿Pausar «' . $campaign->name . '»? Dejará de ser visible para los clientes.' : '¿Activar «' . $campaign->name . '»? Pasará a ser visible para los clientes.' }}">
                                            @csrf
                                            <button type="submit"
                                                    name="status"
                                                    value="{{ $isActive ? 'paused' : 'active' }}"
                                                    class="toggle-pill {{ $isActive ? 'is-on' : '' }}"
                                                    title="{{ $isActive ? 'Pausar campaña' : 'Activar campaña' }}"
                                                    aria-label="{{ $isActive ? 'Pausar ' . $campaign->name : 'Activar ' . $campaign->name }}"
                                                    @unless($campaign->status === 'active' || $campaign->status === 'paused') disabled @endunless>
                                            </button>
                                            <span>{{ $isActive ? 'Activa' : ucfirst($campaign->status) }}</span>
                                        </form>
                                        <a class="btn btn-gray" href="{{ route('admin.invoice-backoffice.campaigns.edit', $campaign) }}">Editar</a>
                                        <a class="btn btn-red" href="{{ $entryUrl }}">Entrar</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endforeach
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    var search   = document.getElementById('promo-search');
    var filters  = document.querySelectorAll('[data-filter]');
    var rows     = document.querySelectorAll('.campaign-row');
    var groups   = document.querySelectorAll('[data-group]');
    var active   = 'all';

    function apply() {
        var term = search ? search.value.trim().toLowerCase() : '';
        rows.forEach(function (row) {
            var matchFilter = active === 'all' || row.dataset.status === active;
            var matchSearch = !term || row.dataset.search.includes(term);
            row.classList.toggle('is-hidden', !(matchFilter && matchSearch));
        });
        // Hide group headers when all their rows are hidden
        groups.forEach(function (g) {
            var key = g.dataset.group;
            var visible = document.querySelectorAll('.campaign-list[data-group="' + key + '"] .campaign-row:not(.is-hidden)').length;
            g.style.display = visible ? '' : 'none';
            var list = document.querySelector('.campaign-list[data-group="' + key + '"]');
            if (list) list.style.display = visible ? '' : 'none';
        });
    }

    filters.forEach(function (btn) {
        btn.addEventListener('click', function () {
            filters.forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            active = btn.dataset.filter;
            apply();
        });
    });

    if (search) search.addEventListener('input', apply);
})();
</script>
@endpush
