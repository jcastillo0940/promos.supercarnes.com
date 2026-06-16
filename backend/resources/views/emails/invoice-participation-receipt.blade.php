<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Comprobante de participación</title>
</head>
<body style="margin:0; padding:0; background:#ffffff; font-family:Helvetica, Arial, sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#ffffff; padding:24px 0;">
  <tr>
    <td align="center">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px; background:#ffffff; border:1px solid #ececec;">

        <tr>
          <td style="padding:28px 32px; text-align:center; border-bottom:1px solid #ececec;">
            <img src="https://promos.supercarnes.com/logo_web.jpg" alt="Super Carnes" height="40" style="display:inline-block; height:40px; width:auto;">
          </td>
        </tr>

        <tr>
          <td style="padding:32px 32px 8px; text-align:center;">
            <p style="margin:0 0 6px; color:#8a8f9c; font-size:11px; font-weight:700; letter-spacing:0.18em; text-transform:uppercase;">Super Carnes 2026</p>
            <h1 style="margin:0; color:#0a0e24; font-size:22px; font-weight:700;">Comprobante de participación</h1>
          </td>
        </tr>

        <tr>
          <td style="padding:8px 32px 0;">
            <p style="margin:0; color:#3a3f4d; font-size:14px; line-height:1.6;">
              Hola <strong>{{ $participant->name }}</strong>, registramos correctamente tu factura en la promoción. Este correo es tu comprobante de participación.
            </p>
          </td>
        </tr>

        <tr>
          <td style="padding:20px 32px 0;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #ececec;">
              <tr>
                <td style="padding:14px 16px; color:#3a3f4d; font-size:13px; line-height:1.6;">
                  <strong>Importante:</strong> esto no es una confirmación de premio. Estate pendiente a nuestras redes sociales, donde anunciaremos a los <strong>100 ganadores</strong>.
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <tr>
          <td style="padding:24px 32px 0;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
              <tr>
                <td style="padding:8px 0; border-bottom:1px solid #ececec; color:#8a8f9c; font-size:12px; text-transform:uppercase; letter-spacing:0.06em;">Cédula</td>
                <td style="padding:8px 0; border-bottom:1px solid #ececec; color:#0a0e24; font-size:13px; font-weight:700; text-align:right;">{{ $participant->cedula }}</td>
              </tr>
              <tr>
                <td style="padding:8px 0; border-bottom:1px solid #ececec; color:#8a8f9c; font-size:12px; text-transform:uppercase; letter-spacing:0.06em;">Factura</td>
                <td style="padding:8px 0; border-bottom:1px solid #ececec; color:#0a0e24; font-size:13px; font-weight:700; text-align:right;">{{ $invoice->invoice_number ?? substr($invoice->cufe, -12) }}</td>
              </tr>
              <tr>
                <td style="padding:8px 0; border-bottom:1px solid #ececec; color:#8a8f9c; font-size:12px; text-transform:uppercase; letter-spacing:0.06em;">Monto</td>
                <td style="padding:8px 0; border-bottom:1px solid #ececec; color:#0a0e24; font-size:13px; font-weight:700; text-align:right;">${{ number_format((float) $invoice->purchase_amount, 2) }}</td>
              </tr>
              <tr>
                <td style="padding:8px 0; color:#8a8f9c; font-size:12px; text-transform:uppercase; letter-spacing:0.06em;">Fecha</td>
                <td style="padding:8px 0; color:#0a0e24; font-size:13px; font-weight:700; text-align:right;">{{ $invoice->issued_at?->timezone('America/Panama')->format('d/m/Y') }}</td>
              </tr>
            </table>
          </td>
        </tr>

        <tr>
          <td style="padding:28px 32px; text-align:center;">
            <p style="margin:0 0 12px; color:#8a8f9c; font-size:11px; font-weight:700; letter-spacing:0.14em; text-transform:uppercase;">Tu código de comprobante</p>
            <img src="{{ $message->embedData($qrImageBytes, 'comprobante-qr.png', 'image/png') }}" alt="Código QR de comprobante" width="200" height="200" style="display:inline-block; width:200px; height:200px; border:1px solid #ececec;">
            <p style="margin:12px 0 0; color:#0a0e24; font-size:12px; font-weight:700;">CUFE: {{ $invoice->cufe }}</p>
          </td>
        </tr>

        <tr>
          <td style="padding:0 32px 32px; text-align:center;">
            <p style="margin:0; color:#8a8f9c; font-size:12px; line-height:1.6;">
              Vigencia de la promoción: 15 de mayo al 15 de junio de 2026. Aplica para compras en tiendas Super Carnes a nivel nacional.
            </p>
          </td>
        </tr>

        <tr>
          <td style="padding:18px 32px; text-align:center; border-top:1px solid #ececec;">
            <p style="margin:0; color:#8a8f9c; font-size:11px;">Super Carnes &copy; {{ now()->year }} · Este es un correo automático, por favor no respondas.</p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
</body>
</html>
