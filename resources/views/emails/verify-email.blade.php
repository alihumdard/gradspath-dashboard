<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify your Grads Paths Account</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f6fa;
            color: #111827;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        .email-shell {
            width: 100%;
            background-color: #f4f6fa;
            padding: 42px 18px 34px;
        }

        .logo {
            width: 220px;
            max-width: 220px;
            height: auto;
            display: block;
            margin: 0 auto;
            border: 0;
            outline: none;
            text-decoration: none;
        }

        .card {
            max-width: 540px;
            margin: 0 auto;
            background-color: #ffffff;
            border: 1px solid #e6e8ef;
            border-radius: 10px;
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.10);
            padding: 38px 40px 34px;
        }

        .card h1 {
            margin: 0 0 16px;
            color: #111827;
            font-size: 30px;
            line-height: 1.15;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .card p {
            margin: 0;
            color: #4b5563;
            font-size: 15px;
            line-height: 1.7;
        }

        .card p strong {
            color: #344054;
            font-weight: 800;
        }

        .code-panel {
            margin: 28px 0 22px;
            padding: 34px 24px 30px;
            background-color: #fafbfe;
            border: 1px solid #e1e4ec;
            border-radius: 12px;
            text-align: center;
        }

        .code {
            color: #111827;
            font-family: 'Courier New', Courier, monospace;
            font-size: 44px;
            line-height: 1;
            font-weight: 800;
            letter-spacing: 12px;
        }

        .expiry {
            margin-top: 14px;
            color: #6b7280;
            font-size: 15px;
            font-weight: 700;
            line-height: 1.5;
        }

        .divider {
            border: 0;
            border-top: 1px solid #e5e7eb;
            margin: 24px 0;
        }

        .footer {
            max-width: 540px;
            margin: 24px auto 0;
            text-align: center;
            color: #9ca3af;
            font-size: 14px;
            line-height: 1.6;
        }

        .footer a {
            color: #4f7fd9;
            font-weight: 700;
            text-decoration: underline;
        }

        @media (max-width: 600px) {
            .email-shell {
                padding: 28px 12px 26px;
            }

            .logo {
                width: 180px;
                max-width: 180px;
            }

            .card {
                padding: 30px 22px 28px;
            }

            .card h1 {
                font-size: 25px;
            }

            .code {
                font-size: 36px;
                letter-spacing: 8px;
            }
        }
    </style>
</head>
<body bgcolor="#f4f6fa">
    <div class="email-shell" bgcolor="#f4f6fa">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="brand" style="max-width:640px;margin:0 auto 26px;">
            <tr>
                <td align="center" bgcolor="#3161d1" style="background-color:#3161d1;padding:26px 18px;border-radius:6px;">
                    <img src="{{ asset('Logo.jpeg') }}" alt="Grads Paths" class="logo" width="220" height="220" border="0" style="width:220px;max-width:220px;height:auto;display:block;margin:0 auto;border:0;outline:none;text-decoration:none;">
                </td>
            </tr>
        </table>

        <div class="card">
            <h1>Verify your account</h1>
            <p>Use the following verification code to verify your <strong>Grads Paths</strong> account.</p>

            <div class="code-panel">
                <div class="code">{{ $code }}</div>
                <div class="expiry">This code will expire in {{ $expiresIn }} minutes.</div>
            </div>

            <hr class="divider">

            <p>If you didn't request this, you can safely ignore this email.</p>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} Grads Paths &nbsp;&bull;&nbsp;
            <a href="mailto:support@gradspaths.com">Help</a>
            &nbsp;&bull;&nbsp;
            <a href="mailto:support@gradspaths.com">Contact</a>
        </div>
    </div>
</body>
</html>
