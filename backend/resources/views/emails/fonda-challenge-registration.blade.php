<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inscripción Fonda Challenge 2026</title>
</head>
<body style="margin:0; padding:0; background:#efe4d2; font-family:Helvetica, Arial, sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#efe4d2; padding:24px 0;">
  <tr>
    <td align="center">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px; background:#ffffff; border:1px solid #ececec; border-radius:12px; overflow:hidden;">

        <tr>
          <td style="padding:28px 32px; text-align:center; border-bottom:1px solid #ececec;">
            <img src="https://promos.supercarnes.com/logo_web.jpg" alt="Super Carnes" height="40" style="display:inline-block; height:40px; width:auto;">
          </td>
        </tr>

        <tr>
          <td style="padding:32px 32px 8px; text-align:center;">
            <p style="margin:0 0 6px; color:#7a4411; font-size:11px; font-weight:700; letter-spacing:0.18em; text-transform:uppercase;">Fonda Challenge 2026</p>
            <h1 style="margin:0; color:#5d310c; font-size:22px; font-weight:700;">¡Tu fonda quedó inscrita!</h1>
          </td>
        </tr>

        <tr>
          <td style="padding:8px 32px 0;">
            <p style="margin:0; color:#3a3f4d; font-size:14px; line-height:1.6;">
              Hola <strong>{{ $registration->full_name }}</strong>, recibimos la inscripción de <strong>{{ $registration->fonda_name }}</strong>. Guarda este correo: aquí está tu código QR de participación.
            </p>
          </td>
        </tr>

        <tr>
          <td style="padding:20px 32px 0;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #ececec; border-radius:8px;">
              <tr>
                <td style="padding:14px 16px; color:#3a3f4d; font-size:13px; line-height:1.6;">
                  <strong>Estado actual:</strong> {{ $registration->status === 'pending_review' ? 'En revisión' : $registration->status }}.
                  Tu código QR ya es válido y no cambia aunque el estado se actualice; lo usará el equipo para el check-in del evento.
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <tr>
          <td style="padding:24px 32px 0; text-align:center;">
            <img src="{{ $message->embedData($qrImageBytes, 'fonda-challenge-qr.png', 'image/png') }}" alt="Código QR de inscripción" width="220" height="220" style="display:inline-block; width:220px; height:220px; border:1px solid #ececec;">
            <p style="margin:12px 0 0; color:#5d310c; font-size:20px; font-weight:800; letter-spacing:.04em;">{{ $registration->code }}</p>
          </td>
        </tr>

        <tr>
          <td style="padding:24px 32px 0;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
              <tr>
                <td style="padding:8px 0; border-bottom:1px solid #ececec; color:#8a8f9c; font-size:12px; text-transform:uppercase; letter-spacing:0.06em;">Fonda</td>
                <td style="padding:8px 0; border-bottom:1px solid #ececec; color:#0a0e24; font-size:13px; font-weight:700; text-align:right;">{{ $registration->fonda_name }}</td>
              </tr>
              <tr>
                <td style="padding:8px 0; border-bottom:1px solid #ececec; color:#8a8f9c; font-size:12px; text-transform:uppercase; letter-spacing:0.06em;">Plato</td>
                <td style="padding:8px 0; border-bottom:1px solid #ececec; color:#0a0e24; font-size:13px; font-weight:700; text-align:right;">{{ $registration->dish_name }}</td>
              </tr>
              <tr>
                <td style="padding:8px 0; color:#8a8f9c; font-size:12px; text-transform:uppercase; letter-spacing:0.06em;">Ubicación</td>
                <td style="padding:8px 0; color:#0a0e24; font-size:13px; font-weight:700; text-align:right;">{{ $registration->fonda_location ?? '—' }}</td>
              </tr>
            </table>
          </td>
        </tr>

        <tr>
          <td style="padding:24px 32px 32px; text-align:center;">
            <p style="margin:0; color:#8a8f9c; font-size:12px; line-height:1.6;">Super Carnes · Fonda Challenge 2026</p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
</body>
</html>
