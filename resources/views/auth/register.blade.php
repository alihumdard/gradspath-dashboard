@extends('layouts.app')

@section('title', 'Register - Grads Paths')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/register-signup.css') }}" />
@endsection

@section('content')
<main class="signup-shell">
  <section class="signup-wrap">
    <div class="signup-card">
      <a href="{{ route('login') }}" class="close-btn" aria-label="Close registration">&times;</a>

      <h1>Create Your Account</h1>
      <p class="subtitle">Begin helping students achieve their dreams.</p>

      <form method="POST" action="{{ route('register') }}" class="signup-form" id="registerForm">
        @csrf

        <input type="hidden" name="role" id="roleInput" value="{{ old('role', 'mentor') }}" />
        <input type="hidden" name="program_level" id="programLevelInput" value="{{ old('program_level', 'professional') }}" />

        <div class="field-head">Program level</div>
        <div class="chip-row" id="programLevelGroup">
          <button type="button" class="chip" data-level="undergrad"><i class="fa-solid fa-graduation-cap"></i>Undergrad</button>
          <button type="button" class="chip" data-level="grad"><i class="fa-solid fa-graduation-cap"></i>Grad</button>
          <button type="button" class="chip" data-level="professional"><i class="fa-solid fa-briefcase"></i>Professional</button>
        </div>

        <div class="field-head top-gap">I am a:</div>
        <div class="role-row" id="roleGroup">
          <button type="button" class="role-card" data-role="student"><i class="fa-solid fa-book-open"></i>Student</button>
          <button type="button" class="role-card" data-role="mentor"><i class="fa-solid fa-building-columns"></i>Mentor</button>
        </div>
        @error('role')
          <span class="error">{{ $message }}</span>
        @enderror

        <label class="label" for="name">Full name <span>*</span></label>
        <div class="input-wrap">
          <input id="name" type="text" name="name" placeholder="Your name" required value="{{ old('name') }}" />
          <i class="fa-solid fa-user"></i>
        </div>
        @error('name')
          <span class="error">{{ $message }}</span>
        @enderror

        <label class="label" for="email">Email <span>*</span></label>
        <div class="input-wrap">
          <input id="email" type="email" name="email" placeholder="you@university.edu" required value="{{ old('email') }}" />
          <i class="fa-solid fa-envelope"></i>
        </div>
        @error('email')
          <span class="error">{{ $message }}</span>
        @enderror

        <label class="label" for="institution">Institution <span>*</span></label>
        <div class="input-wrap">
          <input id="institution" type="text" name="institution" placeholder="Start typing to search..." required value="{{ old('institution') }}" />
          <i class="fa-solid fa-school"></i>
        </div>

        <label class="label" for="password">Password <span>*</span></label>
        <div class="input-wrap password-wrap">
          <input id="password" type="password" name="password" placeholder="Create a password" required />
          <button type="button" class="inline-icon" id="togglePassword" aria-label="Show password">
            <i class="fa-solid fa-eye-slash"></i>
          </button>
        </div>
        @error('password')
          <span class="error">{{ $message }}</span>
        @enderror

        <div class="input-wrap password-wrap">
          <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Confirm password" required />
        </div>

        <p class="note">We may re-verify your email periodically to keep mentors and students real and current.</p>

        <button type="submit" class="continue-btn">Continue</button>

        <p class="signin-link">Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
      </form>
    </div>
  </section>
</main>
@endsection

@section('js')
<script>
  const roleInput = document.getElementById('roleInput');
  const levelInput = document.getElementById('programLevelInput');

  const roleCards = document.querySelectorAll('#roleGroup .role-card');
  const levelChips = document.querySelectorAll('#programLevelGroup .chip');

  function syncActiveState(buttons, key, value) {
    buttons.forEach((btn) => {
      btn.classList.toggle('active', btn.dataset[key] === value);
    });
  }

  roleCards.forEach((btn) => {
    btn.addEventListener('click', () => {
      roleInput.value = btn.dataset.role;
      syncActiveState(roleCards, 'role', roleInput.value);
    });
  });

  levelChips.forEach((btn) => {
    btn.addEventListener('click', () => {
      levelInput.value = btn.dataset.level;
      syncActiveState(levelChips, 'level', levelInput.value);
    });
  });

  syncActiveState(roleCards, 'role', roleInput.value);
  syncActiveState(levelChips, 'level', levelInput.value);

  const togglePasswordBtn = document.getElementById('togglePassword');
  const passwordInput = document.getElementById('password');
  const passwordConfirmationInput = document.getElementById('password_confirmation');

  togglePasswordBtn?.addEventListener('click', () => {
    const isHidden = passwordInput.type === 'password';
    passwordInput.type = isHidden ? 'text' : 'password';
    passwordConfirmationInput.type = isHidden ? 'text' : 'password';
    togglePasswordBtn.innerHTML = isHidden
      ? '<i class="fa-solid fa-eye"></i>'
      : '<i class="fa-solid fa-eye-slash"></i>';
  });
</script>
@endsection
