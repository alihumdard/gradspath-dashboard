<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Booking</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f2ff; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f2ff; margin:0; padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px; background:#ffffff; border-radius:24px; overflow:hidden; box-shadow:0 18px 50px rgba(79, 70, 229, 0.10);">
                    <tr>
                        <td style="padding:32px 32px 24px; background:linear-gradient(135deg, #5b3df5 0%, #7c5cff 100%); color:#ffffff;">
                            <div style="font-size:12px; letter-spacing:1.6px; text-transform:uppercase; opacity:0.88;">Grads Paths</div>
                            <h1 style="margin:14px 0 10px; font-size:28px; line-height:1.2;">A new booking was made with you</h1>
                            <p style="margin:0; font-size:16px; line-height:1.6;">Hi {{ $recipientName }}, {{ $booking['booker_name'] }} has confirmed a session with you. Your Zoom meeting link is included below and will remain available on your bookings page.</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:28px 32px 8px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e8e2ff; border-radius:20px; background:#faf9ff;">
                                <tr>
                                    <td style="padding:22px 24px;">
                                        <div style="font-size:12px; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; color:#7c5cff; margin-bottom:14px;">Booking Summary</div>
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding:10px 0; font-size:14px; color:#6b7280; border-bottom:1px solid #e8e2ff;">Service</td>
                                                <td style="padding:10px 0; font-size:15px; font-weight:700; text-align:right; border-bottom:1px solid #e8e2ff;">{{ $booking['service_name'] }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:10px 0; font-size:14px; color:#6b7280; border-bottom:1px solid #e8e2ff;">Session Type</td>
                                                <td style="padding:10px 0; font-size:15px; font-weight:700; text-align:right; border-bottom:1px solid #e8e2ff;">{{ $booking['session_type_label'] }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:10px 0; font-size:14px; color:#6b7280; border-bottom:1px solid #e8e2ff;">Date</td>
                                                <td style="padding:10px 0; font-size:15px; font-weight:700; text-align:right; border-bottom:1px solid #e8e2ff;">{{ $booking['session_date'] }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:10px 0; font-size:14px; color:#6b7280; border-bottom:1px solid #e8e2ff;">Time</td>
                                                <td style="padding:10px 0; font-size:15px; font-weight:700; text-align:right; border-bottom:1px solid #e8e2ff;">{{ $booking['session_time'] }}</td>
                                            </tr>
                                            @if (!empty($booking['session_timezone']))
                                                <tr>
                                                    <td style="padding:10px 0; font-size:14px; color:#6b7280; border-bottom:1px solid #e8e2ff;">Timezone</td>
                                                    <td style="padding:10px 0; font-size:15px; font-weight:700; text-align:right; border-bottom:1px solid #e8e2ff;">{{ $booking['session_timezone'] }}</td>
                                                </tr>
                                            @endif
                                            <tr>
                                                <td style="padding:10px 0; font-size:14px; color:#6b7280; border-bottom:1px solid #e8e2ff;">{{ $booking['booker_label'] ?? 'Booker' }}</td>
                                                <td style="padding:10px 0; font-size:15px; font-weight:700; text-align:right; border-bottom:1px solid #e8e2ff;">{{ $booking['booker_name'] }}</td>
                                            </tr>
                                            @if (!empty($booking['booker_email']))
                                                <tr>
                                                    <td style="padding:10px 0; font-size:14px; color:#6b7280;">{{ $booking['booker_label'] ?? 'Booker' }} Email</td>
                                                    <td style="padding:10px 0; font-size:15px; font-weight:700; text-align:right;">
                                                        <a href="mailto:{{ $booking['booker_email'] }}" style="color:#3b82f6; text-decoration:none;">{{ $booking['booker_email'] }}</a>
                                                    </td>
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
                                <a href="{{ $booking['meeting_link'] }}" style="display:inline-block; padding:14px 22px; border-radius:999px; background:#5b3df5; color:#ffffff; font-size:15px; font-weight:700; text-decoration:none;">{{ $booking['meeting_link_label'] ?? 'Open Meeting Link' }}</a>
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td style="padding:8px 32px 32px;">
                            <p style="margin:0; font-size:14px; line-height:1.7; color:#4b5563;">Please review the details above and use the Zoom meeting link at session time. If anything changes, we recommend handling updates through the platform rather than sharing personal contact details by email.</p>
                            <p style="margin:22px 0 0; font-size:14px; line-height:1.7; color:#4b5563;">Thanks,<br><strong>Grads Paths</strong></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
