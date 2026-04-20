<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Booking Message</title>
</head>
<body style="margin:0; padding:0; background-color:#f7f8fc; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f8fc; margin:0; padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px; background:#ffffff; border-radius:24px; overflow:hidden; box-shadow:0 18px 50px rgba(17, 24, 39, 0.08);">
                    <tr>
                        <td style="padding:32px; background:linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%); color:#ffffff;">
                            <div style="font-size:12px; letter-spacing:1.6px; text-transform:uppercase; opacity:0.82;">Grads Paths</div>
                            <h1 style="margin:14px 0 10px; font-size:28px; line-height:1.2;">You have a new booking message</h1>
                            <p style="margin:0; font-size:16px; line-height:1.6;">Hi {{ $recipientName }}, {{ $chat['sender_name'] }} sent you a new message for booking #{{ $chat['booking_id'] }}.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 32px;">
                            <p style="margin:0 0 12px; font-size:14px; color:#6b7280;">{{ $chat['service_name'] }} on {{ $chat['session_date'] }} at {{ $chat['session_time'] }}{{ !empty($chat['session_timezone']) ? ' ('.$chat['session_timezone'].')' : '' }}</p>
                            <div style="padding:16px; border-radius:16px; background:#eff6ff; font-size:15px; line-height:1.7; color:#1e3a8a;">
                                {{ $chat['message_preview'] }}
                            </div>
                            <p style="margin:16px 0 0; font-size:14px; line-height:1.7; color:#4b5563;">Open your bookings page to reply inside the platform chat.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
