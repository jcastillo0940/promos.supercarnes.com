@extends('admin.layout')

@section('title', 'Entrega de premio')
@section('subtitle', 'Escaneo y validación')

@section('topbar-actions')
    <a class="topbar-action hide-mobile" href="{{ route('admin.invoice-backoffice') }}">Configuración</a>
    <a class="topbar-action hide-mobile" href="{{ route('admin.invoices') }}">Facturas</a>
    <a class="topbar-action hide-mobile" href="{{ route('admin.winners') }}">Ganadores</a>
    <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit">Cerrar sesión</button>
    </form>
@endsection

@section('sidebar-actions')
    <a href="{{ route('admin.winners') }}">Ganadores <small>Seleccionar o quitar ganadores</small></a>
    <a href="{{ route('admin.invoices') }}">Facturas <small>Buscar cliente por nombre o cédula</small></a>
    <a href="{{ route('admin.invoice-backoffice') }}">Configuración <small>Reglas de validación</small></a>
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
                <h1>Entrega de premio</h1>
                <p>Escanea el QR del correo, valida al ganador y deja evidencia fotográfica de la entrega.</p>
            </div>
        </div>

        <div class="page-section stack">
            <form method="POST" action="{{ route('admin.prize-delivery.lookup') }}" class="responsive-actions" style="align-items:end;">
                @csrf
                <div class="field" style="flex:1;min-width:min(100%,420px);">
                    <label for="qr_code">Escanear o pegar QR / CUFE</label>
                    <input id="qr_code" name="qr_code" type="text" value="{{ old('qr_code') }}" placeholder="Pega el CUFE o escanea el QR" autocomplete="off">
                </div>
                <div class="field" style="flex:1;min-width:min(100%,220px);">
                    <label for="cedula_lookup">Cédula</label>
                    <input id="cedula_lookup" name="cedula" type="text" value="{{ old('cedula') }}" placeholder="Cédula del ganador" autocomplete="off">
                </div>
                <button class="btn btn-red" type="submit">Buscar ganador</button>
            </form>
            <div class="responsive-actions">
                <button class="btn btn-gray" type="button" id="start-camera">Abrir cámara</button>
                <button class="btn btn-gray" type="button" id="stop-camera" disabled>Detener cámara</button>
            </div>
            <div id="scanner-status" class="notice" style="display:none;"></div>
            <div id="scanner-preview" style="display:none;max-width:420px;border-radius:14px;overflow:hidden;border:1px solid #e5e7eb;background:#0f172a;">
                <video id="scanner-video" autoplay playsinline style="width:100%;display:block;"></video>
            </div>

            <div class="notice">
                Usa la cámara del celular o un lector externo. El sistema solo permite continuar si el código pertenece a un ganador activo.
            </div>

            <div class="page-card" style="box-shadow:none;border:1px solid #e5e7eb;">
                <div class="page-section stack">
                    <div class="sidebar-title">Vista previa de archivos</div>
                    <div class="form-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                        <div class="field">
                            <label for="id_card_photo">Fotografía de la cédula</label>
                            <input id="id_card_photo" name="id_card_photo" type="file" accept="image/*" capture="environment" form="delivery-form">
                            <img id="id-card-preview" alt="Vista previa cédula" style="display:none;margin-top:.75rem;max-width:100%;border-radius:14px;border:1px solid #e5e7eb;">
                        </div>
                        <div class="field">
                            <label for="delivery_photo">Fotografía entregando el premio</label>
                            <input id="delivery_photo" name="delivery_photo" type="file" accept="image/*" capture="environment" form="delivery-form">
                            <img id="delivery-preview" alt="Vista previa entrega" style="display:none;margin-top:.75rem;max-width:100%;border-radius:14px;border:1px solid #e5e7eb;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-card">
        <div class="page-title">
            <div>
                <h1 id="winner-card-title">{{ $winner ? 'Ganador validado' : 'Esperando QR' }}</h1>
                <p id="winner-card-subtitle">{{ $winner ? 'Revisa los datos antes de marcar la entrega.' : 'Escanea el QR del correo para cargar al ganador en vivo.' }}</p>
            </div>
        </div>

        <div class="page-section">
            <div class="table-shell">
                <table class="wide">
                    <tbody>
                        <tr>
                            <th>Nombre</th>
                            <td id="winner-name">{{ $winner->user?->full_name ?? $winner->user?->name ?? '—' }}</td>
                            <th>Cédula</th>
                            <td id="winner-cedula">{{ $winner->user?->cedula ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th>Correo</th>
                            <td id="winner-email">{{ $winner->user?->email ?? '—' }}</td>
                            <th>Estado</th>
                            <td><span id="delivery-state" class="badge {{ $winner && $winner->delivery_status === 'delivered' ? 'badge-green' : 'badge-yellow' }}">{{ $winner && $winner->delivery_status === 'delivered' ? 'Entregado' : 'Pendiente' }}</span></td>
                        </tr>
                        <tr>
                            <th>Factura</th>
                            <td id="winner-invoice">{{ $winner ? (optional($winner->user?->invoices?->first())->invoice_number ?? '—') : '—' }}</td>
                            <th>Premio</th>
                            <td id="winner-prize">{{ $winner->notes ?? 'Balón Trionda' }}</td>
                        </tr>
                        <tr>
                            <th>Entregado</th>
                            <td id="delivered-at">{{ $winner->prize_delivered_at?->format('d/m/Y H:i') ?? 'Pendiente' }}</td>
                            <th>Observaciones</th>
                            <td id="winner-notes">{{ $winner->delivery_notes ?? '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="page-section" style="border-top:1px solid #e5e7eb;">
            @if($winner && $winner->prize_delivered_at)
                <div class="success">Este premio ya fue marcado como entregado el {{ $winner->prize_delivered_at->format('d/m/Y H:i') }}.</div>
            @endif

            @if($winner)
                <form id="delivery-form" method="POST" action="{{ route('admin.prize-delivery.store', $winner) }}" enctype="multipart/form-data" class="stack">
                    @csrf
                    <input type="hidden" name="winner_cedula" id="winner_cedula" value="{{ $winner->user?->cedula ?? '' }}">
                    <div class="field">
                        <label for="delivery_notes">Observaciones</label>
                        <textarea id="delivery_notes" name="delivery_notes" rows="4" style="width:100%;padding:.8rem;border:1px solid #cbd5e1;border-radius:10px;"></textarea>
                    </div>
                    <div class="field" style="display:flex;align-items:flex-start;gap:.75rem;">
                        <input id="delivery_confirmation" name="delivery_confirmation" type="checkbox" value="1" style="width:18px;height:18px;margin-top:.2rem;">
                        <label for="delivery_confirmation" style="margin:0;font-weight:600;line-height:1.4;">
                            Confirmo que validé la cédula física, comparé la identidad del ganador y autorizo la entrega.
                        </label>
                    </div>
                    <div class="responsive-actions">
                        <button class="btn btn-green" type="submit">Marcar premio entregado</button>
                    </div>
                </form>
            @else
                <div class="empty">Cuando un QR válido se detecte, aquí aparecerán los datos completos del ganador.</div>
            @endif
        </div>
    </div>

    @if($winner)
        <div class="page-card">
            <div class="page-title">
                <div>
                    <h1>Evidencias guardadas</h1>
                    <p>Miniaturas y enlaces de la evidencia ya registrada.</p>
                </div>
            </div>
            <div class="page-section">
                <div class="form-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                    <div class="field">
                        <label>Foto de la cédula</label>
                        @if($winner->id_card_photo_path)
                            <a href="{{ route('admin.media', ['path' => $winner->id_card_photo_path]) }}" target="_blank" rel="noopener">
                                <img id="evidence-id-card" src="{{ route('admin.media', ['path' => $winner->id_card_photo_path]) }}" alt="Cédula" style="max-width:100%;border-radius:14px;border:1px solid #e5e7eb;">
                            </a>
                            <div style="margin-top:.5rem;">
                                <a id="evidence-id-card-link" class="btn btn-gray" href="{{ route('admin.media', ['path' => $winner->id_card_photo_path]) }}" target="_blank" rel="noopener">Abrir imagen</a>
                            </div>
                        @else
                            <div class="empty" style="padding:1rem;">Aún no se ha cargado la foto de la cédula.</div>
                        @endif
                    </div>
                    <div class="field">
                        <label>Foto de entrega</label>
                        @if($winner->delivery_photo_path)
                            <a href="{{ route('admin.media', ['path' => $winner->delivery_photo_path]) }}" target="_blank" rel="noopener">
                                <img id="evidence-delivery" src="{{ route('admin.media', ['path' => $winner->delivery_photo_path]) }}" alt="Entrega" style="max-width:100%;border-radius:14px;border:1px solid #e5e7eb;">
                            </a>
                            <div style="margin-top:.5rem;">
                                <a id="evidence-delivery-link" class="btn btn-gray" href="{{ route('admin.media', ['path' => $winner->delivery_photo_path]) }}" target="_blank" rel="noopener">Abrir imagen</a>
                            </div>
                        @else
                            <div class="empty" style="padding:1rem;">Aún no se ha cargado la foto de entrega.</div>
                        @endif
                    </div>
                </div>
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
    const input = document.getElementById('qr_code');
    const cedulaLookup = document.getElementById('cedula_lookup');
    const lookupUrl = @json(route('admin.prize-delivery.find'));
    const form = document.getElementById('delivery-form');
    const idCardInput = document.getElementById('id_card_photo');
    const deliveryInput = document.getElementById('delivery_photo');
    const idCardPreview = document.getElementById('id-card-preview');
    const deliveryPreview = document.getElementById('delivery-preview');
    const prizeTitle = document.getElementById('winner-card-title');
    const prizeSubtitle = document.getElementById('winner-card-subtitle');
    let stream = null;
    let detector = null;
    let active = false;
    let loopId = null;
    let lookupTimer = null;
    let currentRequest = null;

    const setStatus = (message) => {
        statusBox.textContent = message;
        statusBox.style.display = 'block';
    };

    const showPreview = (inputEl, previewEl) => {
        const file = inputEl?.files?.[0];
        if (!file) {
            previewEl.style.display = 'none';
            previewEl.removeAttribute('src');
            return;
        }

        const reader = new FileReader();
        reader.onload = () => {
            previewEl.src = String(reader.result);
            previewEl.style.display = 'block';
        };
        reader.readAsDataURL(file);
    };

    const updateWinnerCard = (winner) => {
        if (!winner) return;
        if (prizeTitle) {
            prizeTitle.textContent = `Ganador validado: ${winner.name}`;
        }
        if (prizeSubtitle) {
            prizeSubtitle.textContent = `${winner.cedula} · ${winner.email} · ${winner.status_label}`;
        }
        const winnerCedula = document.getElementById('winner_cedula');
        if (winnerCedula) {
            winnerCedula.value = winner.cedula || '';
        }
        const deliveryState = document.getElementById('delivery-state');
        if (deliveryState) {
            deliveryState.textContent = winner.status_label;
            deliveryState.className = `badge ${winner.delivery_status === 'delivered' ? 'badge-green' : 'badge-yellow'}`;
        }
        const deliveredAt = document.getElementById('delivered-at');
        if (deliveredAt) {
            deliveredAt.textContent = winner.prize_delivered_at || 'Pendiente';
        }
        const evidenceId = document.getElementById('evidence-id-card');
        const evidenceDelivery = document.getElementById('evidence-delivery');
        if (evidenceId && winner.id_card_photo_url) evidenceId.src = winner.id_card_photo_url;
        if (evidenceDelivery && winner.delivery_photo_url) evidenceDelivery.src = winner.delivery_photo_url;
        const evidenceIdLink = document.getElementById('evidence-id-card-link');
        const evidenceDeliveryLink = document.getElementById('evidence-delivery-link');
        if (evidenceIdLink && winner.id_card_photo_url) evidenceIdLink.href = winner.id_card_photo_url;
        if (evidenceDeliveryLink && winner.delivery_photo_url) evidenceDeliveryLink.href = winner.delivery_photo_url;
    };

    const fetchWinner = async () => {
        const qrCode = input.value.trim();
        if (qrCode.length < 4) return;

        if (currentRequest) currentRequest.abort();
        currentRequest = new AbortController();

        try {
            const response = await fetch(lookupUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ qr_code: qrCode, cedula: cedulaLookup?.value?.trim() || '' }),
                signal: currentRequest.signal,
            });

            const payload = await response.json();
            if (!response.ok || !payload.found) {
                setStatus(payload.message || 'No encontramos un ganador válido para ese QR.');
                return;
            }

            setStatus(payload.message || 'Ganador validado correctamente.');
            updateWinnerCard(payload.winner);
            const confirmation = document.getElementById('delivery_confirmation');
            if (confirmation) confirmation.checked = false;
            if (payload.winner?.delivery_status === 'delivered') {
                form?.querySelector('button[type="submit"]')?.setAttribute('disabled', 'disabled');
            } else {
                form?.querySelector('button[type="submit"]')?.removeAttribute('disabled');
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                setStatus('No se pudo consultar el ganador. Intenta nuevamente.');
            }
        }
    };

    const stopCamera = () => {
        active = false;
        if (loopId) {
            cancelAnimationFrame(loopId);
            loopId = null;
        }
        if (stream) {
            stream.getTracks().forEach((track) => track.stop());
            stream = null;
        }
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
                    setStatus('Código detectado. Ya puedes buscar al ganador.');
                    stopCamera();
                    return;
                }
            }
        } catch (error) {
            setStatus('No se pudo leer el QR en este navegador. Puedes pegar el CUFE manualmente.');
            stopCamera();
            return;
        }

        loopId = requestAnimationFrame(scanFrame);
    };

    startButton?.addEventListener('click', async () => {
        if (!('BarcodeDetector' in window)) {
            setStatus('Este navegador no soporta escaneo nativo. Usa un lector externo o pega el CUFE.');
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
            setStatus('Cámara activa. Apunta al QR.');
            scanFrame();
        } catch (error) {
            setStatus('No se pudo abrir la cámara. Verifica permisos del navegador.');
        }
    });

    stopButton?.addEventListener('click', stopCamera);

    input?.addEventListener('input', () => {
        clearTimeout(lookupTimer);
        lookupTimer = setTimeout(fetchWinner, 450);
    });
    cedulaLookup?.addEventListener('input', () => {
        clearTimeout(lookupTimer);
        lookupTimer = setTimeout(fetchWinner, 450);
    });

    idCardInput?.addEventListener('change', () => showPreview(idCardInput, idCardPreview));
    deliveryInput?.addEventListener('change', () => showPreview(deliveryInput, deliveryPreview));
})();
</script>
@endpush
