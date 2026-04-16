<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify your email</title>
    <style>
        :root {
            --bg: #f5f5f8;
            --panel: #ffffff;
            --panel-soft: #f3eef9;
            --line: #ececf1;
            --purple: #6f4cf6;
            --purple-strong: #7a4dff;
            --purple-dark: #252c3c;
            --muted: #6b7285;
            --text: #252c3c;
            --teal: #27c7b8;
            --shadow: 0 18px 38px rgba(18, 24, 38, 0.08);
            --hover-shadow: 0 20px 40px rgba(122, 77, 255, 0.18);
            --radius-xl: 28px;
            --radius-lg: 20px;
            --radius-md: 16px;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            min-height: 100%;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top left, rgba(111, 76, 246, 0.14), transparent 28%),
                radial-gradient(circle at bottom right, rgba(39, 199, 184, 0.12), transparent 24%),
                var(--bg);
            color: var(--text);
        }

        a,
        button {
            transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease, border-color 0.18s ease, color 0.18s ease;
        }

        .page {
            min-height: 100vh;
            padding: 32px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card {
            width: min(920px, 100%);
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow);
            overflow: hidden;
            display: grid;
            grid-template-columns: minmax(280px, 340px) minmax(0, 1fr);
        }

        .hero {
            padding: 36px 30px;
            background: linear-gradient(160deg, #f3eef9 0%, #efe8ff 55%, #edf9f7 100%);
            border-right: 1px solid var(--line);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 28px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.72);
            color: var(--purple);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            width: fit-content;
        }

        .hero h1 {
            margin: 18px 0 12px;
            font-size: clamp(2.1rem, 4vw, 3rem);
            line-height: 1;
            letter-spacing: -0.04em;
            color: var(--purple-dark);
        }

        .hero p {
            margin: 0;
            color: var(--muted);
            font-size: 15px;
            line-height: 1.7;
            max-width: 28ch;
        }

        .hero-list {
            display: grid;
            gap: 12px;
        }

        .hero-list-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 16px;
            border-radius: var(--radius-md);
            background: rgba(255, 255, 255, 0.78);
            border: 1px solid rgba(255, 255, 255, 0.9);
        }

        .hero-list-icon {
            width: 30px;
            height: 30px;
            border-radius: 10px;
            background: rgba(111, 76, 246, 0.12);
            color: var(--purple);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 800;
            flex-shrink: 0;
        }

        .hero-list-copy strong {
            display: block;
            margin-bottom: 4px;
            font-size: 14px;
            color: var(--purple-dark);
        }

        .hero-list-copy span {
            font-size: 13px;
            color: var(--muted);
            line-height: 1.5;
        }

        .content {
            padding: 40px 36px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 24px;
        }

        .content-header h2 {
            margin: 0 0 10px;
            font-size: 30px;
            line-height: 1.05;
            letter-spacing: -0.03em;
            color: var(--purple-dark);
        }

        .content-header p {
            margin: 0;
            color: var(--muted);
            font-size: 15px;
            line-height: 1.7;
            max-width: 56ch;
        }

        .status {
            border-radius: var(--radius-md);
            padding: 16px 18px;
            border: 1px solid rgba(39, 199, 184, 0.2);
            background: rgba(39, 199, 184, 0.1);
            color: #12786d;
            font-size: 14px;
            font-weight: 600;
            line-height: 1.6;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .info-card {
            border: 1px solid var(--line);
            background: #fafafe;
            border-radius: var(--radius-md);
            padding: 18px;
        }

        .info-card .label {
            display: block;
            margin-bottom: 8px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--purple);
        }

        .info-card .value {
            color: var(--purple-dark);
            font-size: 15px;
            line-height: 1.6;
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .actions form {
            margin: 0;
        }

        .primary-btn,
        .secondary-btn {
            min-height: 48px;
            border-radius: 14px;
            padding: 0 18px;
            border: 1px solid transparent;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .primary-btn {
            background: linear-gradient(135deg, var(--purple-strong), #9f55ff);
            color: #fff;
            box-shadow: 0 12px 24px rgba(122, 77, 255, 0.24);
        }

        .primary-btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--hover-shadow);
        }

        .secondary-btn {
            background: #fff;
            border-color: var(--line);
            color: var(--purple-dark);
        }

        .secondary-btn:hover {
            background: #f8f6ff;
            border-color: #d7c8ff;
            color: var(--purple);
            transform: translateY(-1px);
        }

        .footer-note {
            font-size: 13px;
            line-height: 1.6;
            color: var(--muted);
        }

        .footer-note a {
            color: var(--purple);
            font-weight: 700;
            text-decoration: none;
        }

        .footer-note a:hover {
            text-decoration: underline;
        }

        @media (max-width: 860px) {
            .card {
                grid-template-columns: 1fr;
            }

            .hero {
                border-right: none;
                border-bottom: 1px solid var(--line);
            }

            .content {
                padding: 28px 22px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="card">
            <aside class="hero">
                <div>
                    <span class="eyebrow">Email verification</span>
                    <h1>Check your inbox</h1>
                    <p>
                        Your account is almost ready. Open the verification email to unlock the full dashboard experience.
                    </p>
                </div>

                <div class="hero-list">
                    <div class="hero-list-item">
                        <span class="hero-list-icon">1</span>
                        <div class="hero-list-copy">
                            <strong>Open the message</strong>
                            <span>Look for the latest email from Grads Path in your inbox or spam folder.</span>
                        </div>
                    </div>
                    <div class="hero-list-item">
                        <span class="hero-list-icon">2</span>
                        <div class="hero-list-copy">
                            <strong>Tap the link</strong>
                            <span>Use the verification link to confirm your address and activate your account.</span>
                        </div>
                    </div>
                    <div class="hero-list-item">
                        <span class="hero-list-icon">3</span>
                        <div class="hero-list-copy">
                            <strong>Return to your dashboard</strong>
                            <span>Once verified, you can continue with mentor discovery, bookings, and account setup.</span>
                        </div>
                    </div>
                </div>
            </aside>

            <div class="content">
                <div class="content-header">
                    <h2>We sent a verification link to your email address.</h2>
                    <p>
                        Click the link in your email to finish setting up your Grads Path account. If nothing arrives in a minute or two, request a fresh verification email below.
                    </p>
                </div>

                @if (session('status'))
                    <div class="status">{{ session('status') }}</div>
                @endif

                <div class="info-grid">
                    <article class="info-card">
                        <span class="label">Why this matters</span>
                        <div class="value">Verification keeps the community trusted and helps protect student and mentor accounts.</div>
                    </article>
                    <article class="info-card">
                        <span class="label">Need another email?</span>
                        <div class="value">Use the resend button and we will queue a fresh verification message right away.</div>
                    </article>
                </div>

                <div class="actions">
                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <button type="submit" class="primary-btn">Resend verification email</button>
                    </form>

                    <form method="POST" action="{{ route('auth.logout') }}">
                        @csrf
                        <button type="submit" class="secondary-btn">Sign out</button>
                    </form>
                </div>

                <p class="footer-note">
                    Still not seeing the email? Wait a moment, check spam or promotions, then try again.
                </p>
            </div>
        </section>
    </main>
</body>
</html>
