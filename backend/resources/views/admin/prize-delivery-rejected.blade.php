@extends('admin.layout')

@section('title', 'Entrega rechazada')
@section('subtitle', 'Validación fallida')

@section('topbar-actions')
    <a class="topbar-action hide-mobile" href="{{ route('admin.prize-delivery') }}">Volver a entrega</a>
    <a class="topbar-action hide-mobile" href="{{ route('admin.winners') }}">Ganadores</a>
    <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit">Cerrar sesión</button>
    </form>
@endsection

@section('content')
    <div class="page-card">
        <div class="page-title">
            <div>
                <h1>Entrega rechazada</h1>
                <p>El sistema detuvo la operación por una validación de seguridad.</p>
            </div>
        </div>

        <div class="page-section stack">
            <div class="error">{{ $reason }}</div>

            <div class="form-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                <div class="page-card" style="box-shadow:none;border:1px solid #e5e7eb;">
                    <div class="page-section">
                        <p class="sidebar-title">QR / CUFE</p>
                        <div style="word-break:break-all;">{{ $qrCode ?? '—' }}</div>
                    </div>
                </div>
                <div class="page-card" style="box-shadow:none;border:1px solid #e5e7eb;">
                    <div class="page-section">
                        <p class="sidebar-title">Cédula declarada</p>
                        <div>{{ $cedula ?? '—' }}</div>
                    </div>
                </div>
            </div>

            @if(!empty($winner))
                <div class="page-card" style="box-shadow:none;border:1px solid #e5e7eb;">
                    <div class="page-section">
                        <p class="sidebar-title">Ganador encontrado</p>
                        <div><strong>{{ $winner->user?->full_name ?? $winner->user?->name ?? '—' }}</strong> · {{ $winner->user?->cedula ?? '—' }}</div>
                    </div>
                </div>
            @endif

            @if(auth()->user()?->isAdmin() && !empty($winner) && ($reason_code ?? '') === 'cedula_mismatch')
                <div class="page-card" style="box-shadow:none;border:1px solid #f59e0b;background:#fffbeb;">
                    <div class="page-section stack">
                        <div>
                            <p class="sidebar-title">Reabrir con justificación</p>
                            <p style="margin:0;color:#92400e;">Solo úsalo cuando hubo un error humano comprobable, por ejemplo una cédula digitada mal.</p>
                        </div>

                        <form method="POST" action="{{ route('admin.prize-delivery.override', $winner) }}" class="stack">
                            @csrf
                            <div class="form-grid">
                                <label class="field">
                                    <span>Justificación obligatoria</span>
                                    <textarea name="override_reason" rows="4" required placeholder="Ej: la persona digitó 964 en lugar de 864; se verificó con documento físico.">{{ old('override_reason') }}</textarea>
                                </label>
                                <label class="field">
                                    <span>Cédula corregida</span>
                                    <input type="text" name="corrected_cedula" value="{{ old('corrected_cedula', $winner->user?->cedula) }}" />
                                </label>
                            </div>
                            <div class="responsive-actions">
                                <button class="btn btn-primary" type="submit">Reabrir validación</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            <div class="responsive-actions">
                <a class="btn btn-red" href="{{ route('admin.prize-delivery') }}">Intentar de nuevo</a>
            </div>
        </div>
    </div>
@endsection
