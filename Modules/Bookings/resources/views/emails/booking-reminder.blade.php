<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Reminder</title>
</head>
<body style="margin:0; padding:0; background-color:#f7f8fc; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f8fc; margin:0; padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px; background:#ffffff; border-radius:24px; overflow:hidden; box-shadow:0 18px 50px rgba(17, 24, 39, 0.08);">
                    <tr>
                        <td style="padding:32px 32px 24px; background:linear-gradient(135deg, #0f172a 0%, #2563eb 100%); color:#ffffff;">
                            <div style="font-size:12px; letter-spacing:1.6px; text-transform:uppercase; opacity:0.82;">Grads Paths</div>
                            <h1 style="margin:14px 0 10px; font-size:28px; line-height:1.2;">Your session starts soon</h1>
                            <p style="margin:0; font-size:16px; line-height:1.6;">Hi {{ $recipientName }}, this is a reminder that your {{ $booking['meeting_provider'] ?? 'meeting' }} session starts in {{ $hoursUntilSession === 1 ? '1 hour' : $hoursUntilSession.' hours' }}.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 32px 8px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb; border-radius:20px; background:#fcfcfd;">
                                <tr>
                                    <td style="padding:22px 24px;">
                                        <div style="font-size:12px; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; color:#2563eb; margin-bottom:14px;">Session Details</div>
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding:10px 0; font-size:14px; color:#6b7280; border-bottom:1px solid #e5e7eb;">Service</td>
                                                <td style="padding:10px 0; font-size:15px; font-weight:700; text-align:right; border-bottom:1px solid #e5e7eb;">{{ $booking['service_name'] }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:10px 0; font-size:14px; color:#6b7280; border-bottom:1px solid #e5e7eb;">Session Type</td>
                                                <td style="padding:10px 0; font-size:15px; font-weight:700; text-align:right; border-bottom:1px solid #e5e7eb;">{{ $booking['session_type_label'] }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:10px 0; font-size:14px; color:#6b7280; border-bottom:1px solid #e5e7eb;">Date</td>
                                                <td style="padding:10px 0; font-size:15px; font-weight:700; text-align:right; border-bottom:1px solid #e5e7eb;">{{ $booking['session_date'] }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:10px 0; font-size:14px; color:#6b7280; border-bottom:1px solid #e5e7eb;">Time</td>
                                                <td style="padding:10px 0; font-size:15px; font-weight:700; text-align:right; border-bottom:1px solid #e5e7eb;">{{ $booking['session_time'] }}</td>
                                            </tr>
                                            @if (!empty($booking['session_timezone']))
                                                <tr>
                                                    <td style="padding:10px 0; font-size:14px; color:#6b7280; border-bottom:1px solid #e5e7eb;">Timezone</td>
                                                    <td style="padding:10px 0; font-size:15px; font-weight:700; text-align:right; border-bottom:1px solid #e5e7eb;">{{ $booking['session_timezone'] }}</td>
                                                </tr>
                                            @endif
                                            <tr>
                                                <td style="padding:10px 0; font-size:14px; color:#6b7280;">Meeting Provider</td>
                                                <td style="padding:10px 0; font-size:15px; font-weight:700; text-align:right;">{{ $booking['meeting_provider'] ?? 'Meeting Link' }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @if (!empty($booking['meeting_link']))
                        <tr>
                            <td style="padding:16px 32px 8px;">
                                <a href="{{ $booking['meeting_link'] }}" style="display:inline-block; padding:14px 22px; border-radius:999px; background:#0f172a; color:#ffffff; font-size:15px; font-weight:700; text-decoration:none;">{{ $booking['meeting_link_label'] ?? 'Join Meeting' }}</a>
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td style="padding:8px 32px 32px;">
                            <p style="margin:0; font-size:14px; line-height:1.7; color:#4b5563;">Please join from your Grads Paths bookings page or use the Zoom link above at session time. If anything changes, handle updates through the platform so all participants stay in sync.</p>
                            <p style="margin:22px 0 0; font-size:14px; line-height:1.7; color:#4b5563;">Thanks,<br><strong>Grads Paths</strong></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
