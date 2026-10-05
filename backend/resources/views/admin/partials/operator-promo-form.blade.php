{{--
  Partial: operator-promo-form
  Variables expected from parent (operators.blade.php):
    $operator      — User|null  (null = create form)
    $formPrefix    — string     (unique prefix for DOM ids, e.g. 'create' or 'op-5')
    $modules       — array      (from OperatorController::MODULES)
    $permissions   — array      (from OperatorController::PERMISSIONS)
    $templates     — array      (from OperatorController::TEMPLATES)
    $promoModules  — array      (module keys that have granular permissions)
    $maltaCampaigns — Collection (product_ranking campaigns)
--}}

@php
    /** @var \App\Models\User|null $operator */
    $currentModules  = $operator?->admin_modules ?? [];
    $promoPermsIndex = [];
    if ($operator) {
        foreach ($operator->promoPermissions as $row) {
            $promoPermsIndex[$row->promo_key] = $row->permissions;
        }
    }

    $plainModules = array_diff(array_keys($modules), $promoModules);

    // For malta, build per-campaign promo keys
    $maltaPromoKeys = $maltaCampaigns->mapWithKeys(fn($c) => ["malta-{$c->id}" => $c->name])->all();
@endphp

{{-- ─── Module + permission matrix ───────────────────────────── --}}
<div style="margin-top:.75rem;">
    <p style="font-size:.82rem;font-weight:600;color:var(--bo-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:.6rem;">
        Módulos y permisos
    </p>

    {{-- ── Promo modules with granular permissions ── --}}
    @foreach($promoModules as $moduleKey)
        @php
            $isChecked = in_array($moduleKey, $currentModules, true);
            $bodyId    = $formPrefix.'-body-'.$moduleKey;
            $label     = $modules[$moduleKey] ?? $moduleKey;
        @endphp

        @if($moduleKey === 'malta')
            {{-- Malta: one block per campaign --}}
            <div style="margin-bottom:.25rem;">
                <div class="promo-block-header" style="background:var(--bo-bg);border:1px solid var(--bo-border);border-radius:10px;margin-bottom:.25rem;">
                    <input
                        type="checkbox"
                        id="{{ $formPrefix }}-mod-{{ $moduleKey }}"
                        name="modules[]"
                        value="{{ $moduleKey }}"
                        {{ $isChecked ? 'checked' : '' }}
                        data-promo-check="1"
                        data-promo-body-id="{{ $bodyId }}"
                    >
                    <label for="{{ $formPrefix }}-mod-{{ $moduleKey }}">{{ $label }}</label>
                </div>
                <div id="{{ $bodyId }}" class="promo-block-body" style="margin-left:.5rem;">
                    @if($maltaCampaigns->isEmpty())
                        <p style="font-size:.83rem;color:var(--bo-muted);">No hay campañas Malta activas.</p>
                    @else
                        @foreach($maltaCampaigns as $campaign)
                            @php
                                $promoKey   = "malta-{$campaign->id}";
                                $bodyIdMalt = $formPrefix.'-body-'.$promoKey;
                                $campaignPerms = $promoPermsIndex[$promoKey] ?? null;
                                $campaignMod = 'malta';
                            @endphp
                            <div class="promo-block" style="margin-left:.25rem;margin-bottom:.5rem;">
                                <div class="promo-block-header">
                                    <span style="font-size:.85rem;font-weight:600;">
                                        {{ $campaign->name }}
                                        <span class="badge badge-{{ $campaign->status === 'active' ? 'green' : 'gray' }}" style="margin-left:.4rem;font-size:.72rem;">{{ $campaign->status }}</span>
                                    </span>
                                </div>
                                <div class="promo-block-body open">
                                    <div class="promo-tpl-row">
                                        <span style="font-size:.8rem;color:var(--bo-muted);">Plantilla:</span>
                                        <select
                                            data-tpl-select="1"
                                            data-prefix="{{ $formPrefix }}"
                                            data-promo-key="{{ $promoKey }}"
                                        >
                                            <option value="">— aplicar plantilla —</option>
                                            <option value="readonly">Solo lectura</option>
                                            <option value="invoice_op">Operador de facturas</option>
                                            <option value="full">Encargado de promo</option>
                                        </select>
                                    </div>
                                    <div class="perm-grid">
                                        @foreach($permissions as $permKey => $permLabel)
                                            @php
                                                $checked = $campaignPerms !== null
                                                    ? ($campaignPerms[$permKey] ?? false)
                                                    : $isChecked;
                                            @endphp
                                            <label class="perm-label">
                                                <input
                                                    type="checkbox"
                                                    id="{{ $formPrefix }}-{{ $promoKey }}-{{ $permKey }}"
                                                    name="promo_permissions[{{ $promoKey }}][{{ $permKey }}]"
                                                    value="1"
                                                    {{ $checked ? 'checked' : '' }}
                                                >
                                                <span>{{ $permLabel }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        @else
            {{-- Standard promo module --}}
            @php
                $permsForModule = $promoPermsIndex[$moduleKey] ?? null;
            @endphp
            <div class="promo-block">
                <div class="promo-block-header">
                    <input
                        type="checkbox"
                        id="{{ $formPrefix }}-mod-{{ $moduleKey }}"
                        name="modules[]"
                        value="{{ $moduleKey }}"
                        {{ $isChecked ? 'checked' : '' }}
                        data-promo-check="1"
                        data-promo-body-id="{{ $bodyId }}"
                    >
                    <label for="{{ $formPrefix }}-mod-{{ $moduleKey }}">{{ $label }}</label>
                </div>
                <div id="{{ $bodyId }}" class="promo-block-body {{ $isChecked ? 'open' : '' }}">
                    <div class="promo-tpl-row">
                        <span style="font-size:.8rem;color:var(--bo-muted);">Plantilla:</span>
                        <select
                            data-tpl-select="1"
                            data-prefix="{{ $formPrefix }}"
                            data-promo-key="{{ $moduleKey }}"
                        >
                            <option value="">— aplicar plantilla —</option>
                            <option value="readonly">Solo lectura</option>
                            <option value="invoice_op">Operador de facturas</option>
                            <option value="full">Encargado de promo</option>
                        </select>
                    </div>
                    <div class="perm-grid">
                        @foreach($permissions as $permKey => $permLabel)
                            @php
                                $checked = $permsForModule !== null
                                    ? ($permsForModule[$permKey] ?? false)
                                    : $isChecked;
                            @endphp
                            <label class="perm-label">
                                <input
                                    type="checkbox"
                                    id="{{ $formPrefix }}-{{ $moduleKey }}-{{ $permKey }}"
                                    name="promo_permissions[{{ $moduleKey }}][{{ $permKey }}]"
                                    value="1"
                                    {{ $checked ? 'checked' : '' }}
                                >
                                <span>{{ $permLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    @endforeach

    {{-- ── Plain modules (no granular permissions) ── --}}
    @if(count($plainModules) > 0)
        <div style="margin-top:.5rem;padding:.65rem .9rem;border:1px solid var(--bo-border);border-radius:10px;background:var(--bo-bg);">
            <p style="font-size:.8rem;font-weight:600;color:var(--bo-muted);margin-bottom:.45rem;">Otros módulos</p>
            <div class="perm-grid" style="grid-template-columns:repeat(auto-fill,minmax(180px,1fr));">
                @foreach($plainModules as $modKey)
                    <label class="perm-label">
                        <input
                            type="checkbox"
                            id="{{ $formPrefix }}-mod-{{ $modKey }}"
                            name="modules[]"
                            value="{{ $modKey }}"
                            {{ in_array($modKey, $currentModules, true) ? 'checked' : '' }}
                        >
                        <span>{{ $modules[$modKey] ?? $modKey }}</span>
                    </label>
                @endforeach
            </div>
        </div>
    @endif
</div>
