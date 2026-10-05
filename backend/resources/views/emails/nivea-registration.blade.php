<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Empollerando a Mamá - Super Carnes</title>
</head>
<body style="margin:0; padding:0; background:#f0f4fa; font-family:Helvetica, Arial, sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f0f4fa; padding:24px 0;">
  <tr>
    <td align="center">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px; background:#ffffff; border:1px solid #c7d9f0; border-radius:14px; overflow:hidden;">

        <tr>
          <td style="padding:22px 32px; text-align:center; background:#003087;">
            <p style="margin:0; color:#ffffff; font-size:13px; font-weight:700; letter-spacing:.2em; text-transform:uppercase;">NIVEA &times; Super Carnes</p>
          </td>
        </tr>

        <tr>
          <td style="padding:30px 32px 8px; text-align:center;">
            <p style="margin:0 0 6px; color:#0057b7; font-size:11px; font-weight:700; letter-spacing:0.18em; text-transform:uppercase;">Empollerando a Mamá</p>
            <h1 style="margin:0; color:#001a4d; font-size:22px; font-weight:700;">¡Gracias por participar, {{ $entry->firstName() }}!</h1>
          </td>
        </tr>

        <tr>
          <td style="padding:8px 32px 0;">
            <p style="margin:0; color:#2a3d5a; font-size:14px; line-height:1.6;">
              @if($entry->status === 'approved')
                Recibimos tu factura y la historia de <strong>{{ $entry->nominee_name }}</strong>. Tu participación quedó registrada para la selección de las 10 ganadoras.
              @else
                Recibimos tu factura y la historia de <strong>{{ $entry->nominee_name }}</strong>. Nuestro equipo revisará tu factura y te avisaremos si necesitamos algo más.
              @endif
            </p>
          </td>
        </tr>

        <tr>
          <td style="padding:24px 32px 0;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
              <tr>
                <td style="padding:8px 0; border-bottom:1px solid #e3ebf5; color:#6b84a0; font-size:12px; text-transform:uppercase; letter-spacing:0.06em;">Cédula registrante</td>
                <td style="padding:8px 0; border-bottom:1px solid #e3ebf5; color:#001a4d; font-size:13px; font-weight:700; text-align:right;">{{ $entry->cedula }}</td>
              </tr>
              <tr>
                <td style="padding:8px 0; border-bottom:1px solid #e3ebf5; color:#6b84a0; font-size:12px; text-transform:uppercase; letter-spacing:0.06em;">Mamá postulada</td>
                <td style="padding:8px 0; border-bottom:1px solid #e3ebf5; color:#001a4d; font-size:13px; font-weight:700; text-align:right;">{{ $entry->nominee_name }}</td>
              </tr>
              <tr>
                <td style="padding:8px 0; border-bottom:1px solid #e3ebf5; color:#6b84a0; font-size:12px; text-transform:uppercase; letter-spacing:0.06em;">Sucursal</td>
                <td style="padding:8px 0; border-bottom:1px solid #e3ebf5; color:#001a4d; font-size:13px; font-weight:700; text-align:right;">{{ $entry->branch?->name ?? '—' }}</td>
              </tr>
              <tr>
                <td style="padding:8px 0; border-bottom:1px solid #e3ebf5; color:#6b84a0; font-size:12px; text-transform:uppercase; letter-spacing:0.06em;">Fecha de factura</td>
                <td style="padding:8px 0; border-bottom:1px solid #e3ebf5; color:#001a4d; font-size:13px; font-weight:700; text-align:right;">{{ $entry->issued_at?->timezone('America/Panama')->format('d/m/Y') ?? '—' }}</td>
              </tr>
              <tr>
                <td style="padding:8px 0; color:#6b84a0; font-size:12px; text-transform:uppercase; letter-spacing:0.06em;">Estado</td>
                <td style="padding:8px 0; color:#001a4d; font-size:13px; font-weight:700; text-align:right;">{{ $entry->statusLabel() }}</td>
              </tr>
            </table>
          </td>
        </tr>

        <tr>
          <td style="padding:24px 32px 28px;">
            <p style="margin:0; color:#6b84a0; font-size:12px; line-height:1.6;">
              Una participación por cédula. El anuncio de las 10 ganadoras se realizará el 16 de noviembre de 2026.
              Si resultan seleccionadas, contactaremos al teléfono y correo registrados.
              Promoción válida del 5 de octubre al 14 de noviembre de 2026, por la compra de productos NIVEA en Super Carnes.
            </p>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</body>
</html>
