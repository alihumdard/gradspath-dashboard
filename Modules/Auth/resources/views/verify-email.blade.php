<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify your email</title>
    <style>
        :root {
            --bg: #f7f7fb;
            --card: #ffffff;
            --line: #e5e7ef;
            --line-strong: #cfd3e1;
            --text: #111827;
            --muted: #667085;
            --purple: #5d2eea;
            --purple-soft: #7557ff;
            --footer: #fafafa;
            --danger-bg: #fef2f2;
            --danger-border: #fecaca;
            --danger-text: #991b1b;
            --success-bg: #ecfdf5;
            --success-border: #bbf7d0;
            --success-text: #047857;
            --shadow: 0 18px 45px rgba(15, 23, 42, 0.1);
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            min-height: 100%;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        button {
            font: inherit;
        }

        .page {
            min-height: 100vh;
            padding: 24px 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card {
            width: min(475px, 100%);
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: var(--card);
            box-shadow: var(--shadow);
        }

        .content {
            padding: 32px 34px 22px;
            text-align: center;
        }

        .brand {
            display: grid;
            justify-items: center;
            height: 82px;
            margin-bottom: 24px;
            overflow: hidden;
        }

        .brand img {
            width: 190px;
            height: auto;
            display: block;
            margin-top: -52px;
        }

        h1 {
            margin: 0 0 10px;
            color: var(--text);
            font-size: clamp(1.62rem, 5vw, 1.86rem);
            line-height: 1.08;
            letter-spacing: -0.04em;
        }

        .subtitle {
            margin: 0 0 28px;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.55;
        }

        .subtitle strong {
            color: #344054;
            font-weight: 800;
        }

        .status {
            margin: 0 0 18px;
            border: 1px solid var(--success-border);
            border-radius: 8px;
            background: var(--success-bg);
            color: var(--success-text);
            padding: 12px 14px;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.45;
            text-align: left;
        }

        .status.error {
            border-color: var(--danger-border);
            background: var(--danger-bg);
            color: var(--danger-text);
        }

        .code-form {
            display: grid;
            gap: 20px;
        }

        .code-inputs {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 16px;
        }

        .code-input {
            width: 100%;
            aspect-ratio: 1;
            border: 2px solid var(--line-strong);
            border-radius: 7px;
            background: #fff;
            color: var(--text);
            font-size: clamp(1.8rem, 7vw, 2.25rem);
            font-weight: 800;
            line-height: 1;
            text-align: center;
            outline: none;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
        }

        .code-input:focus {
            border-color: var(--purple-soft);
            box-shadow: 0 0 0 4px rgba(93, 46, 234, 0.12);
        }

        .expiry {
            margin: -4px 0 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            color: #7a8498;
            font-size: 13px;
            font-weight: 700;
        }

        .expiry svg,
        .link-button svg,
        .security svg {
            width: 18px;
            height: 18px;
            flex: 0 0 auto;
        }

        .primary-btn {
            width: 100%;
            min-height: 40px;
            border: 0;
            border-radius: 7px;
            background: linear-gradient(135deg, var(--purple), #4d2ce0);
            color: #fff;
            box-shadow: 0 12px 24px rgba(93, 46, 234, 0.24);
            cursor: pointer;
            font-size: 14px;
            font-weight: 800;
        }

        .primary-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 28px rgba(93, 46, 234, 0.28);
        }

        .secondary-actions {
            margin-top: 20px;
            display: grid;
            gap: 14px;
            justify-items: center;
        }

        .inline-form {
            margin: 0;
        }

        .link-button {
            border: 0;
            background: transparent;
            color: var(--purple-soft);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 800;
            text-decoration: none;
        }

        .link-button:hover {
            color: var(--purple);
        }

        .divider {
            width: 100%;
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 14px;
            color: #8b95a7;
            font-size: 14px;
        }

        .divider::before,
        .divider::after {
            content: "";
            height: 1px;
            background: var(--line);
        }

        .security {
            border-top: 1px solid var(--line);
            background: var(--footer);
            color: #667085;
            padding: 18px 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
        }

        @media (max-width: 560px) {
            .content {
                padding: 28px 18px 22px;
            }

            .brand {
                height: 72px;
                margin-bottom: 22px;
            }

            .brand img {
                width: 170px;
                margin-top: -46px;
            }

            .code-inputs {
                gap: 9px;
            }

            .subtitle {
                margin-bottom: 24px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="card" aria-labelledby="verification-title">
            <div class="content">
                <div class="brand">
                    <img src="{{ asset('Logo.jpeg') }}" alt="Grads Paths">
                </div>

                <h1 id="verification-title">Enter verification code</h1>
                <p class="subtitle">
                    We sent a 6-digit verification code to <strong>{{ auth()->user()?->email }}</strong>
                </p>

                @if (session('status'))
                    <div class="status">{{ session('status') }}</div>
                @endif

                @if ($errors->has('code') || $errors->has('resend'))
                    <div class="status error">{{ $errors->first('code') ?: $errors->first('resend') }}</div>
                @endif

                <form method="POST" action="{{ route('verification.verify') }}" class="code-form">
                    @csrf
                    <input type="hidden" name="code" id="verification-code" value="{{ old('code') }}">

                    <div class="code-inputs" aria-label="Verification code">
                        @for ($i = 0; $i < 6; $i++)
                            <input
                                class="code-input"
                                type="text"
                                name="code_digits[]"
                                inputmode="numeric"
                                pattern="[0-9]*"
                                maxlength="1"
                                autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}"
                                aria-label="Digit {{ $i + 1 }}"
                                value="{{ old('code_digits.' . $i) }}"
                            >
                        @endfor
                    </div>

                    <p class="expiry">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                            <path d="M12 7v5l3 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Code expires in 30 minutes.
                    </p>

                    <button type="submit" class="primary-btn">Verify account</button>
                </form>

                <div class="secondary-actions">
                    <form method="POST" action="{{ route('verification.send') }}" class="inline-form">
                        @csrf
                        <button type="submit" class="link-button">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M3 12a9 9 0 0 1 15.36-6.36M21 12a9 9 0 0 1-15.36 6.36" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <path d="M18 3v4h-4M6 21v-4h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Resend code
                        </button>
                    </form>

                    <div class="divider">or</div>

                    <form method="POST" action="{{ route('auth.logout') }}" class="inline-form">
                        @csrf
                        <button type="submit" class="link-button">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M20 21a8 8 0 0 0-16 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            Use a different email
                        </button>
                    </form>
                </div>
            </div>

            <div class="security">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 3 5 6v5c0 4.3 2.9 8.3 7 9.5 4.1-1.2 7-5.2 7-9.5V6l-7-3Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                    <path d="m9 12 2 2 4-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                For your security, this code can only be used once.
            </div>
        </section>
    </main>

    <script>
        const codeInputs = Array.from(document.querySelectorAll(".code-input"));
        const codeField = document.getElementById("verification-code");

        function syncCode() {
            if (codeField) {
                codeField.value = codeInputs.map((input) => input.value).join("");
            }
        }

        codeInputs.forEach((input, index) => {
            input.addEventListener("input", () => {
                input.value = input.value.replace(/\D/g, "").slice(0, 1);
                if (input.value && codeInputs[index + 1]) {
                    codeInputs[index + 1].focus();
                }
                syncCode();
            });

            input.addEventListener("keydown", (event) => {
                if (event.key === "Backspace" && !input.value && codeInputs[index - 1]) {
                    codeInputs[index - 1].focus();
                }
            });

            input.addEventListener("paste", (event) => {
                const pasted = event.clipboardData.getData("text").replace(/\D/g, "").slice(0, 6);

                if (!pasted) {
                    return;
                }

                event.preventDefault();
                pasted.split("").forEach((digit, digitIndex) => {
                    if (codeInputs[digitIndex]) {
                        codeInputs[digitIndex].value = digit;
                    }
                });
                codeInputs[Math.min(pasted.length, 6) - 1]?.focus();
                syncCode();
            });
        });

        syncCode();
    </script>
</body>
</html>
