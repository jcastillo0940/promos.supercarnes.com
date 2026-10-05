<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Emprende tu Sueño con Saba - Super Carnes</title>
</head>
<body style="margin:0; padding:0; background:#f7f1fb; font-family:Helvetica, Arial, sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f7f1fb; padding:24px 0;">
  <tr>
    <td align="center">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px; background:#ffffff; border:1px solid #eadcf3; border-radius:14px; overflow:hidden;">

        <tr>
          <td style="padding:22px 32px; text-align:center; background:#5b2a86;">
            <p style="margin:0; color:#ffffff; font-size:13px; font-weight:700; letter-spacing:.2em; text-transform:uppercase;">Saba &times; Super Carnes</p>
          </td>
        </tr>

        <tr>
          <td style="padding:30px 32px 8px; text-align:center;">
            <p style="margin:0 0 6px; color:#d4145a; font-size:11px; font-weight:700; letter-spacing:0.18em; text-transform:uppercase;">Emprende tu Sueño</p>
            <h1 style="margin:0; color:#2d1242; font-size:22px; font-weight:700;">¡Gracias por participar, {{ $entry->firstName() }}!</h1>
          </td>
        </tr>

        <tr>
          <td style="padding:8px 32px 0;">
            <p style="margin:0; color:#4a3b57; font-size:14px; line-height:1.6;">
              @if($entry->status === 'approved')
                Recibimos tu factura y tu historia. Tu participación quedó registrada para la selección de las 50 ganadoras.
              @else
                Recibimos tu factura y tu historia. Nuestro equipo revisará tu factura y te avisaremos si necesitamos algo más.
              @endif
            </p>
          </td>
        </tr>

        <tr>
          <td style="padding:24px 32px 0;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
              <tr>
                <td style="padding:8px 0; border-bottom:1px solid #f0e6f6; color:#8a7d96; font-size:12px; text-transform:uppercase; letter-spacing:0.06em;">Cédula</td>
                <td style="padding:8px 0; border-bottom:1px solid #f0e6f6; color:#2d1242; font-size:13px; font-weight:700; text-align:right;">{{ $entry->cedula }}</td>
              </tr>
              <tr>
                <td style="padding:8px 0; border-bottom:1px solid #f0e6f6; color:#8a7d96; font-size:12px; text-transform:uppercase; letter-spacing:0.06em;">Sucursal</td>
                <td style="padding:8px 0; border-bottom:1px solid #f0e6f6; color:#2d1242; font-size:13px; font-weight:700; text-align:right;">{{ $entry->branch?->name ?? '—' }}</td>
              </tr>
              <tr>
                <td style="padding:8px 0; border-bottom:1px solid #f0e6f6; color:#8a7d96; font-size:12px; text-transform:uppercase; letter-spacing:0.06em;">Fecha de factura</td>
                <td style="padding:8px 0; border-bottom:1px solid #f0e6f6; color:#2d1242; font-size:13px; font-weight:700; text-align:right;">{{ $entry->issued_at?->timezone('America/Panama')->format('d/m/Y') ?? '—' }}</td>
              </tr>
              <tr>
                <td style="padding:8px 0; color:#8a7d96; font-size:12px; text-transform:uppercase; letter-spacing:0.06em;">Estado</td>
                <td style="padding:8px 0; color:#2d1242; font-size:13px; font-weight:700; text-align:right;">{{ $entry->statusLabel() }}</td>
              </tr>
            </table>
          </td>
        </tr>

        <tr>
          <td style="padding:24px 32px 28px;">
            <p style="margin:0; color:#8a7d96; font-size:12px; line-height:1.6;">
              La participación es una sola por persona. Si resultas ganadora te contactaremos al teléfono y correo registrados.
              Promoción válida del 28 de septiembre al 25 de octubre de 2026, por la compra de productos marca SABA en Super Carnes.
            </p>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</body>
</html>
