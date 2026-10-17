<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Invitación al backoffice – Super Carnes</title>
</head>
<body style="margin:0; padding:0; background:#f8fafc; font-family:Helvetica, Arial, sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; padding:24px 0;">
  <tr>
    <td align="center">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px; background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; overflow:hidden;">

        <tr>
          <td style="padding:22px 32px; text-align:center; background:linear-gradient(90deg,#dc2626,#ef4444);">
            <p style="margin:0; color:#ffffff; font-size:13px; font-weight:700; letter-spacing:.2em; text-transform:uppercase;">Super Carnes &mdash; Backoffice</p>
          </td>
        </tr>

        <tr>
          <td style="padding:32px 32px 8px; text-align:center;">
            <h1 style="margin:0; color:#0f172a; font-size:22px; font-weight:700;">Te han dado acceso</h1>
            <p style="margin:8px 0 0; color:#64748b; font-size:14px; line-height:1.6;">
              Hola <strong>{{ $operator->full_name ?? $operator->name }}</strong>, has sido registrado como operador en el panel de administración de Super Carnes.
            </p>
          </td>
        </tr>

        <tr>
          <td style="padding:24px 32px;">
            <p style="margin:0 0 8px; color:#334155; font-size:13px;">Para activar tu cuenta y crear tu contraseña, haz clic en el botón:</p>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td align="center" style="padding:8px 0 16px;">
                  <a href="{{ $resetUrl }}" target="_blank"
                     style="display:inline-block; padding:14px 28px; background:linear-gradient(90deg,#dc2626,#ef4444); color:#fff; text-decoration:none; border-radius:10px; font-size:15px; font-weight:800;">
                    Crear mi contraseña
                  </a>
                </td>
              </tr>
            </table>
            <p style="margin:0; color:#94a3b8; font-size:12px; line-height:1.6;">
              Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
              <a href="{{ $resetUrl }}" style="color:#dc2626; word-break:break-all;">{{ $resetUrl }}</a>
            </p>
          </td>
        </tr>

        <tr>
          <td style="padding:0 32px 24px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                   style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:16px;">
              <tr>
                <td>
                  <p style="margin:0 0 4px; color:#64748b; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.1em;">Correo de acceso</p>
                  <p style="margin:0; color:#0f172a; font-size:14px; font-weight:600;">{{ $operator->email }}</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <tr>
          <td style="padding:16px 32px 28px;">
            <p style="margin:0; color:#94a3b8; font-size:12px; line-height:1.6;">
              Este enlace expira en <strong>60 minutos</strong>. Si no lo usaste, puedes ignorar este correo.<br>
              Para volver a solicitar acceso, contacta a un administrador.
            </p>
          </td>
        </tr>

        <tr>
          <td style="padding:16px 32px; text-align:center; border-top:1px solid #f1f5f9;">
            <img src="https://promos.supercarnes.com/logo_web.jpg" alt="Super Carnes" height="32" style="display:inline-block; height:32px; width:auto;">
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
</body>
</html>
