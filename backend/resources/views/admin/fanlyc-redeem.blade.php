@extends('admin.layout')

@section('title', 'Canje Fanlyc — ' . $zone->name)
@section('subtitle', 'Escaneo y validación')

@section('topbar-actions')
    <a class="topbar-action hide-mobile" href="{{ route('admin.fanlyc') }}">Fanlyc</a>
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
                <h1>Canje de cupon — Zona {{ $zone->name }}</h1>
                <p>Escanea o pega el codigo del cupon del cliente. Solo se puede canjear cupones de esta zona.</p>
            </div>
        </div>

        <div class="page-section stack">
            <form method="POST" action="{{ route('admin.fanlyc.redeem.lookup', $zone->code) }}" class="responsive-actions" style="align-items:end;">
                @csrf
                <div class="field" style="flex:1;min-width:min(100%,420px);">
                    <label for="coupon_code">Escanear o pegar codigo del cupon</label>
                    <input id="coupon_code" name="coupon_code" type="text" value="{{ old('coupon_code') }}" placeholder="Ej: FLY-AB3K9" autocomplete="off">
                </div>
                <button class="btn btn-red" type="submit">Buscar cupon</button>
            </form>
            <div class="responsive-actions">
                <button class="btn btn-gray" type="button" id="start-camera">Abrir cámara</button>
                <button class="btn btn-gray" type="button" id="stop-camera" disabled>Detener cámara</button>
            </div>
            <div id="scanner-status" class="notice" style="display:none;"></div>
            <div id="scanner-preview" style="display:none;max-width:420px;border-radius:14px;overflow:hidden;border:1px solid #e5e7eb;background:#0f172a;">
                <video id="scanner-video" autoplay playsinline style="width:100%;display:block;"></video>
            </div>
            <div class="notice">Solo se permite canjear cupones vigentes ({{ 'issued' }}) que correspondan a la zona {{ $zone->name }}.</div>
        </div>
    </div>

    @if($coupon)
        <div class="page-card" style="margin-top:1rem;">
            <div class="page-title">
                <div>
                    <h1>Cupon {{ $coupon->code }}</h1>
                    <p>Verifica los datos antes de confirmar el canje.</p>
                </div>
            </div>
            <div class="page-section">
                <div class="table-shell">
                    <table class="wide">
                        <tbody>
                            <tr>
                                <th>Nombre</th>
                                <td>{{ $coupon->user?->full_name ?? $coupon->user?->name ?? '—' }}</td>
                                <th>Cédula</th>
                                <td>{{ $coupon->user?->cedula ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th>Zona</th>
                                <td>{{ $coupon->fanlycZone?->name ?? '—' }}</td>
                                <th>Estado</th>
                                <td>
                                    <span class="badge {{ $coupon->status === 'issued' ? 'badge-yellow' : ($coupon->status === 'redeemed' ? 'badge-green' : 'badge-gray') }}">
                                        {{ $coupon->status === 'issued' ? 'Disponible' : ($coupon->status === 'redeemed' ? 'Canjeado' : 'Anulado') }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="page-section" style="border-top:1px solid #e5e7eb;">
                @if($coupon->status === 'issued')
                    <form method="POST" action="{{ route('admin.fanlyc.redeem.store', [$zone->code, $coupon]) }}" onsubmit="return confirm('¿Confirmar canje de este cupon por un tiket?');">
                        @csrf
                        <button class="btn btn-green" type="submit">Confirmar canje</button>
                    </form>
                @else
                    <div class="empty">Este cupon ya no esta disponible para canjear.</div>
                @endif
            </div>
        </div>
    @endif
@endsection

@push('scripts')
<script>
(() => {
    const startButton = document.getElementById('start-camera');
    const stopButton = document.getElementById('stop-camera');
    const preview = document.getElementById('scanner-preview');
    const video = document.getElementById('scanner-video');
    const statusBox = document.getElementById('scanner-status');
    const input = document.getElementById('coupon_code');
    let stream = null;
    let detector = null;
    let active = false;
    let loopId = null;

    const setStatus = (message) => {
        statusBox.textContent = message;
        statusBox.style.display = 'block';
    };

    const stopCamera = () => {
        active = false;
        if (loopId) { cancelAnimationFrame(loopId); loopId = null; }
        if (stream) { stream.getTracks().forEach((track) => track.stop()); stream = null; }
        preview.style.display = 'none';
        stopButton.disabled = true;
        startButton.disabled = false;
    };

    const scanFrame = async () => {
        if (!active || !detector || !video.videoWidth) {
            loopId = requestAnimationFrame(scanFrame);
            return;
        }
        try {
            const codes = await detector.detect(video);
            if (codes && codes.length > 0) {
                const raw = codes[0].rawValue || '';
                if (raw) {
                    input.value = raw;
                    setStatus('Código detectado. Ya puedes buscar el cupón.');
                    stopCamera();
                    return;
                }
            }
        } catch (error) {
            setStatus('No se pudo leer el QR en este navegador. Pega el código manualmente.');
            stopCamera();
            return;
        }
        loopId = requestAnimationFrame(scanFrame);
    };

    startButton?.addEventListener('click', async () => {
        if (!('BarcodeDetector' in window)) {
            setStatus('Este navegador no soporta escaneo nativo. Usa un lector externo o pega el código.');
            return;
        }
        try {
            detector = detector || new BarcodeDetector({ formats: ['qr_code'] });
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
            video.srcObject = stream;
            preview.style.display = 'block';
            startButton.disabled = true;
            stopButton.disabled = false;
            active = true;
            setStatus('Cámara activa. Apunta al QR del cupón.');
            scanFrame();
        } catch (error) {
            setStatus('No se pudo abrir la cámara. Verifica permisos del navegador.');
        }
    });

    stopButton?.addEventListener('click', stopCamera);
})();
</script>
@endpush
