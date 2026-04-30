<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reconnect Zoom</title>
</head>
<body style="margin:0; padding:0; background-color:#f7f8fc; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f8fc; margin:0; padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px; background:#ffffff; border-radius:24px; overflow:hidden; box-shadow:0 18px 50px rgba(17, 24, 39, 0.08);">
                    <tr>
                        <td style="padding:32px 32px 24px; background:linear-gradient(135deg, #0f172a 0%, #7a4dff 100%); color:#ffffff;">
                            <div style="font-size:12px; letter-spacing:1.6px; text-transform:uppercase; opacity:0.82;">Grads Paths</div>
                            <h1 style="margin:14px 0 10px; font-size:28px; line-height:1.2;">Reconnect Zoom to keep bookings open</h1>
                            <p style="margin:0; font-size:16px; line-height:1.6;">Hi {{ $mentorName }}, your Zoom connection needs to be reconnected before students can book new Zoom sessions with you.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 32px 8px;">
                            <p style="margin:0 0 18px; font-size:15px; line-height:1.7; color:#4b5563;">We refresh Zoom securely in the background, but Zoom reported that your connection is no longer usable. Reconnecting keeps your availability bookable and prevents students from hitting an error at checkout.</p>
                            <a href="{{ $settingsUrl }}" style="display:inline-block; padding:14px 22px; border-radius:999px; background:#0f172a; color:#ffffff; font-size:15px; font-weight:700; text-decoration:none;">Reconnect Zoom</a>
                            <p style="margin:22px 0 0; font-size:14px; line-height:1.7; color:#4b5563;">Thanks,<br><strong>Grads Paths</strong></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
