@extends('layouts.app')

@section('title', 'Verify Reset Code - Grads Paths')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/admin-auth.css') }}" />
@endsection

@section('content')
    <div class="app">
      <section class="login-screen">
        <div class="login-card">
          <h1>Enter verification code</h1>
          <p>We sent a 6-digit verification code to <strong>{{ $email }}</strong></p>

          @if (session('status'))
            <div class="success-message">{{ session('status') }}</div>
          @endif

          @error('code')
            <span class="error">{{ $message }}</span>
          @enderror

          @error('resend')
            <span class="error">{{ $message }}</span>
          @enderror

          <form method="POST" action="{{ $verifyRoute }}" class="login-form">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <input type="hidden" name="code" id="verification-code" value="{{ old('code') }}">

            <div class="code-inputs" aria-label="Verification code" style="display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px;margin-bottom:20px;">
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
                        style="width:100%;aspect-ratio:1;text-align:center;font-size:1.5rem;font-weight:700;border-radius:6px;border:2px solid #cfd3e1;"
                    >
                @endfor
            </div>

            <p style="color:#6b7280;font-size:14px;margin-bottom:16px;">Code expires in {{ $expiresIn ?? 30 }} minutes.</p>

            <button type="submit" class="primary-btn">Verify code</button>
          </form>

          <form method="POST" action="{{ $resendRoute }}" style="margin-top:16px;">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <button type="submit" class="primary-btn" style="background:transparent;color:#4f46e5;">Resend code</button>
          </form>
        </div>
      </section>
    </div>
@endsection

@section('js')
<script src="{{ asset('assets/js/admin-auth.js') }}"></script>
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
@endsection
