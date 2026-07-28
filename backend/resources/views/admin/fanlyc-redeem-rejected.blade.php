@extends('admin.layout')

@section('title', 'Canje rechazado')
@section('subtitle', 'Validación fallida')

@section('topbar-actions')
    <a class="topbar-action hide-mobile" href="{{ route('admin.fanlyc.redeem', $zone->code) }}">Volver al canje</a>
    <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit">Cerrar sesión</button>
    </form>
@endsection

@section('content')
    <div class="page-card">
        <div class="page-title">
            <div>
                <h1>Canje rechazado — Zona {{ $zone->name }}</h1>
                <p>El sistema detuvo la operación por una validación de seguridad.</p>
            </div>
        </div>

        <div class="page-section stack">
            <div class="error">{{ $reason }}</div>

            <div class="form-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                <div class="page-card" style="box-shadow:none;border:1px solid #e5e7eb;">
                    <div class="page-section">
                        <p class="sidebar-title">Código</p>
                        <div style="word-break:break-all;">{{ $couponCode ?? '—' }}</div>
                    </div>
                </div>
                @if($coupon)
                    <div class="page-card" style="box-shadow:none;border:1px solid #e5e7eb;">
                        <div class="page-section">
                            <p class="sidebar-title">Cupon encontrado</p>
                            <div><strong>{{ $coupon->user?->full_name ?? $coupon->user?->name ?? '—' }}</strong> · {{ $coupon->user?->cedula ?? '—' }} · Zona: {{ $coupon->fanlycZone?->name ?? '—' }}</div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="responsive-actions">
                <a class="btn btn-red" href="{{ route('admin.fanlyc.redeem', $zone->code) }}">Intentar de nuevo</a>
            </div>
        </div>
    </div>
@endsection
