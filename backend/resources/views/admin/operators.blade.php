@extends('admin.layout')

@section('title', 'Operadores')
@section('subtitle', 'Acceso y permisos')

@push('styles')
<style>
    .promo-block {
        border: 1px solid var(--bo-border);
        border-radius: 10px;
        margin-bottom: .6rem;
        overflow: hidden;
    }
    .promo-block-header {
        display: flex;
        align-items: center;
        gap: .65rem;
        padding: .65rem .9rem;
        background: var(--bo-bg);
        cursor: pointer;
        user-select: none;
    }
    .promo-block-header label { cursor: pointer; font-weight: 600; font-size: .9rem; }
    .promo-block-body {
        padding: .75rem .9rem 1rem;
        display: none;
        border-top: 1px solid var(--bo-border);
        background: var(--bo-surface);
    }
    .promo-block-body.open { display: block; }
    .perm-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: .35rem .75rem;
        margin-top: .5rem;
    }
    .perm-label {
        display: flex;
        align-items: center;
        gap: .4rem;
        font-size: .84rem;
        font-weight: 500;
        cursor: pointer;
        color: var(--bo-fg2);
    }
    .perm-label input[type=checkbox]:checked + span { color: var(--bo-fg); }
    .promo-tpl-row {
        display: flex;
        align-items: center;
        gap: .5rem;
        margin-bottom: .6rem;
    }
    .promo-tpl-row select {
        font-size: .8rem;
        padding: .3rem .6rem;
        border: 1px solid var(--bo-border-dark);
        border-radius: 7px;
        background: var(--bo-surface);
        color: var(--bo-fg2);
        cursor: pointer;
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

    {{-- Create operator --}}
    <div class="page-card">
        <div class="page-title">
            <div>
                <h1>Crear operador</h1>
                <p>Un operador solo ve los módulos y permisos que le asignes. Inicia sesión en /admin/login.</p>
            </div>
        </div>
        <div class="page-section" style="border-top:1px solid var(--bo-border);">
            <form method="POST" action="{{ route('admin.operators.store') }}" class="form-grid">
                @csrf
                <div class="form-grid" style="grid-template-columns:repeat(3,minmax(0,1fr));">
                    <div class="field">
                        <label for="full_name">Nombre completo</label>
                        <input id="full_name" name="full_name" type="text" value="{{ old('full_name') }}" required>
                    </div>
                    <div class="field">
                        <label for="email">Correo</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required>
                    </div>
                    <div class="field">
                        <label for="password">Contraseña</label>
                        <input id="password" name="password" type="text" minlength="8" required>
                    </div>
                </div>

                @include('admin.partials.operator-promo-form', [
                    'operator' => null,
                    'formPrefix' => 'create',
                ])

                <div style="margin-top:.5rem;">
                    <button class="btn btn-green" type="submit">Crear operador</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Operators list --}}
    <div class="page-card" style="margin-top:1rem;">
        <div class="page-title">
            <div>
                <h1>Operadores</h1>
                <p>{{ $operators->count() }} operador(es)</p>
            </div>
        </div>

        @if($operators->isEmpty())
            <div class="page-section">
                <p style="color:var(--bo-muted);">No hay operadores creados todavía.</p>
            </div>
        @else
            @foreach($operators as $op)
                <div class="page-section" style="border-top:1px solid var(--bo-border);">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:.75rem;margin-bottom:.75rem;">
                        <div>
                            <strong>{{ $op->full_name ?? $op->name }}</strong>
                            <span style="margin-left:.5rem;color:var(--bo-muted);font-size:.88rem;">{{ $op->email }}</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:.5rem;">
                            @if($op->is_active)
                                <span class="badge badge-green">Activo</span>
                            @else
                                <span class="badge badge-gray">Desactivado</span>
                            @endif
                            <form method="POST" action="{{ route('admin.operators.toggle-status', $op) }}"
                                data-confirm="{{ $op->is_active ? '¿Desactivar a '.$op->full_name.'? Ya no podrá iniciar sesión.' : '¿Activar a '.$op->full_name.'?' }}">
                                @csrf
                                <button class="btn btn-gray" type="submit" style="padding:.4rem .75rem;font-size:.8rem;">
                                    {{ $op->is_active ? 'Desactivar' : 'Activar' }}
                                </button>
                            </form>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.operators.modules', $op) }}">
                        @csrf
                        @include('admin.partials.operator-promo-form', [
                            'operator' => $op,
                            'formPrefix' => 'op-'.$op->id,
                        ])
                        <button class="btn btn-gray" type="submit" style="margin-top:.75rem;padding:.4rem .75rem;font-size:.8rem;">
                            Guardar módulos y permisos
                        </button>
                    </form>
                </div>
            @endforeach
        @endif
    </div>
@endsection

@push('scripts')
<script>
(function () {
    var PERMS = @json(array_keys($permissions));
    var TEMPLATES = @json($templates);

    function toggleBlock(checkbox, bodyId) {
        var body = document.getElementById(bodyId);
        if (!body) return;
        if (checkbox.checked) {
            body.classList.add('open');
        } else {
            body.classList.remove('open');
        }
    }

    function applyTemplate(prefix, promoKey, tplKey) {
        var tpl = TEMPLATES[tplKey];
        if (!tpl) return;
        PERMS.forEach(function (perm) {
            var el = document.getElementById(prefix + '-' + promoKey + '-' + perm);
            if (el) el.checked = !!tpl[perm];
        });
    }

    document.querySelectorAll('[data-promo-check]').forEach(function (cb) {
        var bodyId = cb.dataset.promoBodyId;
        cb.addEventListener('change', function () { toggleBlock(cb, bodyId); });
        // Sync initial state
        toggleBlock(cb, bodyId);
    });

    document.querySelectorAll('[data-tpl-select]').forEach(function (sel) {
        sel.addEventListener('change', function () {
            if (!sel.value) return;
            applyTemplate(sel.dataset.prefix, sel.dataset.promoKey, sel.value);
            sel.value = '';
        });
    });
})();
</script>
@endpush
