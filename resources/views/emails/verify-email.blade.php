<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - Grads Paths</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #EEFAF7;
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #333333;
        }

        .wrapper {
            max-width: 580px;
            margin: 48px auto;
        }

        .top-bar {
            background-color: #1a1a1a;
            border-radius: 8px 8px 0 0;
            padding: 24px 40px;
            text-align: center;
        }

        .logo {
            width: 76px;
            max-width: 76px;
            height: auto;
            margin: 0 auto 14px;
            display: block;
            border: 0;
            outline: none;
            text-decoration: none;
        }

        .top-bar h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 1px;
        }

        .card {
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            border-top: none;
            padding: 44px 48px 36px;
        }

        .card h2 {
            font-size: 22px;
            font-weight: 700;
            color: #1a1a1a;
            margin: 0 0 12px;
        }

        .card p {
            font-size: 15px;
            line-height: 1.75;
            color: #555555;
            margin: 0 0 16px;
        }

        .highlight-box {
            background-color: #EEFAF7;
            border-left: 4px solid #1a1a1a;
            border-radius: 0 6px 6px 0;
            padding: 16px 20px;
            margin: 28px 0;
        }

        .highlight-box p {
            margin: 0;
            font-size: 14px;
            color: #444444;
        }

        .btn-wrap {
            margin: 32px 0;
        }

        .btn {
            display: inline-block;
            background-color: #1a1a1a;
            color: #ffffff;
            text-decoration: none;
            font-size: 15px;
            font-weight: 600;
            padding: 15px 38px;
            border-radius: 6px;
            letter-spacing: 0.3px;
        }

        hr {
            border: none;
            border-top: 1px solid #ebebeb;
            margin: 32px 0;
        }

        .signature {
            font-size: 15px;
            color: #555555;
            line-height: 1.7;
        }

        .support-box {
            background-color: #EEFAF7;
            border-radius: 6px;
            padding: 16px 20px;
            margin-top: 28px;
            text-align: center;
        }

        .support-box p {
            font-size: 13px;
            color: #666666;
            margin: 0;
            line-height: 1.6;
        }

        .support-box a {
            color: #1a1a1a;
            font-weight: 600;
            text-decoration: none;
        }

        .url-section {
            margin-top: 28px;
        }

        .url-section p {
            font-size: 13px;
            color: #888888;
            line-height: 1.6;
            margin: 0 0 8px;
        }

        .url-box {
            background-color: #f7f7f7;
            border: 1px solid #e5e5e5;
            border-radius: 6px;
            padding: 12px 16px;
            font-size: 12px;
            color: #555555;
            word-break: break-all;
            line-height: 1.6;
        }

        .footer {
            background-color: #1a1a1a;
            border-radius: 0 0 8px 8px;
            text-align: center;
            padding: 20px 40px;
            font-size: 12px;
            color: #888888;
            line-height: 1.8;
        }

        .footer a {
            color: #aaaaaa;
            text-decoration: none;
        }
    </style>
</head>
<body>
    @php
        $userName = trim((string) ($userName ?? '')) ?: 'there';
    @endphp

    <div class="wrapper">
        <div class="top-bar">
            <img src="{{ asset('Logo.jpeg') }}" alt="Grads Paths Logo" class="logo">
            <h1>Grads Paths</h1>
        </div>

        <div class="card">
            <h2>Verify your email address</h2>
            <p>Hello {{ $userName }}! Thanks for joining Grads Paths. Please confirm your email address by clicking the button below to complete your registration.</p>

            <div class="highlight-box">
                <p>This verification link will expire in <strong>{{ $expiresIn }} minutes</strong>. If you did not create an account, you can safely ignore this email.</p>
            </div>

            <div class="btn-wrap">
                <a href="{{ $url }}" class="btn">Verify Email Address</a>
            </div>

            <hr>

            <div class="signature">
                Regards,<br>
                <strong>The Grads Paths Team</strong>
            </div>

            <div class="url-section">
                <p>If the button above doesn't work, copy and paste this URL into your browser:</p>
                <div class="url-box">
                    {{ $url }}
                </div>
            </div>

            <div class="support-box">
                <p>Need help? Reach out to our support team at<br>
                <a href="mailto:support@gradspaths.com">support@gradspaths.com</a></p>
            </div>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} Grads Paths. All rights reserved.<br>
        </div>
    </div>
</body>
</html>
