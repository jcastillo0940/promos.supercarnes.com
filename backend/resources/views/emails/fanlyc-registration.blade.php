<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Fanlyc - Super Carnes</title>
</head>
<body style="margin:0; padding:0; background:#f8fafc; font-family:Helvetica, Arial, sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; padding:24px 0;">
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
            <p style="margin:0 0 6px; color:#b91c1c; font-size:11px; font-weight:700; letter-spacing:0.18em; text-transform:uppercase;">Fanlyc</p>
            @if($coupon)
                <h1 style="margin:0; color:#0f172a; font-size:22px; font-weight:700;">¡Tu cupon esta listo!</h1>
            @else
                <h1 style="margin:0; color:#0f172a; font-size:22px; font-weight:700;">Recibimos tu factura</h1>
            @endif
          </td>
        </tr>

        <tr>
          <td style="padding:8px 32px 0;">
            <p style="margin:0; color:#3a3f4d; font-size:14px; line-height:1.6;">
              @if($coupon)
                  Tu factura fue validada y ya tienes un cupon QR disponible. Llevalo al evento de tu zona y cambialo por tu tiket.
              @else
                  Tu factura quedo en revision manual. Te avisaremos por correo apenas se resuelva.
              @endif
            </p>
          </td>
        </tr>

        @if($coupon)
        <tr>
          <td style="padding:24px 32px 0; text-align:center;">
            <img src="{{ $message->embedData($qrImageBytes, 'fanlyc-coupon-qr.png', 'image/png') }}" alt="Codigo QR del cupon" width="220" height="220" style="display:inline-block; width:220px; height:220px; border:1px solid #ececec;">
            <p style="margin:12px 0 0; color:#0f172a; font-size:20px; font-weight:800; letter-spacing:.04em;">{{ $coupon->code }}</p>
          </td>
        </tr>
        @endif

        <tr>
          <td style="padding:24px 32px 0;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
              @if($coupon)
              <tr>
                <td style="padding:8px 0; border-bottom:1px solid #ececec; color:#8a8f9c; font-size:12px; text-transform:uppercase; letter-spacing:0.06em;">Zona</td>
                <td style="padding:8px 0; border-bottom:1px solid #ececec; color:#0a0e24; font-size:13px; font-weight:700; text-align:right;">{{ $coupon->fanlycZone?->name ?? '—' }}</td>
              </tr>
              @endif
              <tr>
                <td style="padding:8px 0; color:#8a8f9c; font-size:12px; text-transform:uppercase; letter-spacing:0.06em;">Factura</td>
                <td style="padding:8px 0; color:#0a0e24; font-size:13px; font-weight:700; text-align:right;">{{ $invoice->invoice_number ?? $invoice->cufe }}</td>
              </tr>
            </table>
          </td>
        </tr>

        <tr>
          <td style="padding:24px 32px 32px; text-align:center;">
            <p style="margin:0; color:#8a8f9c; font-size:12px; line-height:1.6;">Super Carnes · Fanlyc</p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
</body>
</html>
