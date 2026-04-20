<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Cancelled</title>
</head>
<body style="margin:0; padding:0; background-color:#f8fafc; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8fafc; margin:0; padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px; background:#ffffff; border-radius:24px; overflow:hidden; box-shadow:0 18px 50px rgba(17, 24, 39, 0.08);">
                    <tr>
                        <td style="padding:32px; background:linear-gradient(135deg, #991b1b 0%, #dc2626 100%); color:#ffffff;">
                            <div style="font-size:12px; letter-spacing:1.6px; text-transform:uppercase; opacity:0.82;">Grads Paths</div>
                            <h1 style="margin:14px 0 10px; font-size:28px; line-height:1.2;">This booking has been cancelled</h1>
                            <p style="margin:0; font-size:16px; line-height:1.6;">Hi {{ $recipientName }}, {{ $booking['cancelled_by_name'] }} cancelled the session below.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 32px;">
                            <p style="margin:0 0 12px; font-size:14px; color:#6b7280;">{{ $booking['service_name'] }} with {{ $booking['mentor_name'] }}</p>
                            <p style="margin:0 0 8px; font-size:15px;"><strong>Date:</strong> {{ $booking['session_date'] }}</p>
                            <p style="margin:0 0 8px; font-size:15px;"><strong>Time:</strong> {{ $booking['session_time'] }}{{ !empty($booking['session_timezone']) ? ' ('.$booking['session_timezone'].')' : '' }}</p>
                            <p style="margin:16px 0 0; font-size:14px; line-height:1.7; color:#4b5563;">{{ $booking['support_message'] }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
