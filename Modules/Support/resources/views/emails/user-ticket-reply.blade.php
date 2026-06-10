<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Support Ticket Update</title>
</head>
<body style="margin:0; padding:0; background:#f8fafc; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; padding:24px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px; background:#ffffff; border-radius:22px; overflow:hidden; box-shadow:0 18px 50px rgba(17,24,39,.08);">
          <tr>
            <td style="padding:28px 32px; background:linear-gradient(135deg,#6f4cf6,#23293a); color:#ffffff;">
              <div style="font-size:12px; letter-spacing:1.6px; text-transform:uppercase; opacity:.85;">Grads Paths Support</div>
              <h1 style="margin:12px 0 8px; font-size:28px; line-height:1.2;">Support replied</h1>
              <p style="margin:0; font-size:15px; line-height:1.6;">There is an update on {{ $ticket->ticket_ref }}.</p>
            </td>
          </tr>
          <tr>
            <td style="padding:28px 32px;">
              <p style="margin:0 0 10px; font-size:15px;"><strong>Subject:</strong> {{ $ticket->subject }}</p>
              <p style="margin:16px 0; font-size:14px; line-height:1.7; color:#4b5563;">{{ $ticket->admin_reply }}</p>
              <a href="{{ $supportUrl }}" style="display:inline-block; padding:13px 18px; border-radius:12px; background:#6f4cf6; color:#ffffff; text-decoration:none; font-weight:700;">Open Support</a>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
