@extends('discovery::admin.layouts.app')

@section('admin_title', 'Settings')
@section('admin_heading', 'Admin Settings')
@section('admin_subtitle', 'Update administrator login email and password settings.')

@section('admin_head')
  <style>
    /* Override browser autofill light background and black text */
    input:-webkit-autofill,
    input:-webkit-autofill:hover,
    input:-webkit-autofill:focus,
    input:-webkit-autofill:active {
      -webkit-box-shadow: 0 0 0 1000px #171c2a inset !important;
      -webkit-text-fill-color: #f5f7fb !important;
      transition: background-color 5000s ease-in-out 0s;
    }
  </style>
@endsection

@section('admin_content')
  {{-- Change Email Section --}}
  <div class="panel" style="margin-top: 20px; padding: 28px; border-radius: 24px;">
    <div class="section-head" style="margin-bottom: 24px;">
      <div>
        <h2 style="font-size: 22px; font-weight: 600; margin: 0;">Change Email Address</h2>
        <p style="margin-top: 6px; margin-bottom: 0; color: var(--muted); font-size: 14px;">Update the email address used to log in to the admin dashboard.</p>
      </div>
    </div>

    <form class="manual-form" method="POST" action="{{ route('admin.settings.update-email') }}" style="max-width: 600px;">
      @csrf
      @method('PATCH')

      <label class="manual-field manual-field--full">
        <span>Current Email Address</span>
        <input type="text" value="{{ $user->email }}" readonly style="cursor: not-allowed; color: var(--text); opacity: 0.9;" />
      </label>

      <label class="manual-field manual-field--full">
        <span>New Email Address</span>
        <input name="email" type="email" value="{{ old('email') }}" required />
        @error('email')
          <small class="manual-field__error" style="color: var(--danger, #ff6078); font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</small>
        @enderror
      </label>

      <label class="manual-field manual-field--full">
        <span>Confirm Current Password</span>
        <div style="position: relative; width: 100%;">
          <input name="current_password" type="password" required style="padding-right: 45px;" />
          <button type="button" class="password-toggle-btn" style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); background: none; border: none; padding: 0; color: var(--muted); cursor: pointer; display: flex; align-items: center; justify-content: center; outline: none; z-index: 10;">
            <svg class="eye-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
              <circle cx="12" cy="12" r="3"></circle>
            </svg>
            <svg class="eye-slash-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
              <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
              <line x1="1" y1="1" x2="23" y2="23"></line>
            </svg>
          </button>
        </div>
        @error('current_password')
          <small class="manual-field__error" style="color: var(--danger, #ff6078); font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</small>
        @enderror
      </label>

      <div class="manual-field--full" style="margin-top: 8px;">
        <button class="primary-btn" type="submit" style="padding: 12px 24px; border-radius: 12px;">Update Email</button>
      </div>
    </form>
  </div>

  {{-- Change Password Section --}}
  <div class="panel" style="margin-top: 32px; padding: 28px; border-radius: 24px; margin-bottom: 24px;">
    <div class="section-head" style="margin-bottom: 24px;">
      <div>
        <h2 style="font-size: 22px; font-weight: 600; margin: 0;">Change Password</h2>
        <p style="margin-top: 6px; margin-bottom: 0; color: var(--muted); font-size: 14px;">Ensure your account is using a long, random password to stay secure.</p>
      </div>
    </div>

    <form class="manual-form" method="POST" action="{{ route('admin.settings.update-password') }}" style="max-width: 600px;">
      @csrf
      @method('PATCH')

      <label class="manual-field manual-field--full">
        <span>Current Password</span>
        <div style="position: relative; width: 100%;">
          <input name="current_password" type="password" required style="padding-right: 45px;" />
          <button type="button" class="password-toggle-btn" style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); background: none; border: none; padding: 0; color: var(--muted); cursor: pointer; display: flex; align-items: center; justify-content: center; outline: none; z-index: 10;">
            <svg class="eye-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
              <circle cx="12" cy="12" r="3"></circle>
            </svg>
            <svg class="eye-slash-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
              <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
              <line x1="1" y1="1" x2="23" y2="23"></line>
            </svg>
          </button>
        </div>
        @error('current_password')
          <small class="manual-field__error" style="color: var(--danger, #ff6078); font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</small>
        @enderror
      </label>

      <label class="manual-field">
        <span>New Password</span>
        <div style="position: relative; width: 100%;">
          <input name="new_password" type="password" required style="padding-right: 45px;" />
          <button type="button" class="password-toggle-btn" style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); background: none; border: none; padding: 0; color: var(--muted); cursor: pointer; display: flex; align-items: center; justify-content: center; outline: none; z-index: 10;">
            <svg class="eye-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
              <circle cx="12" cy="12" r="3"></circle>
            </svg>
            <svg class="eye-slash-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
              <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
              <line x1="1" y1="1" x2="23" y2="23"></line>
            </svg>
          </button>
        </div>
        @error('new_password')
          <small class="manual-field__error" style="color: var(--danger, #ff6078); font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</small>
        @enderror
      </label>

      <label class="manual-field">
        <span>Confirm New Password</span>
        <div style="position: relative; width: 100%;">
          <input name="new_password_confirmation" type="password" required style="padding-right: 45px;" />
          <button type="button" class="password-toggle-btn" style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); background: none; border: none; padding: 0; color: var(--muted); cursor: pointer; display: flex; align-items: center; justify-content: center; outline: none; z-index: 10;">
            <svg class="eye-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
              <circle cx="12" cy="12" r="3"></circle>
            </svg>
            <svg class="eye-slash-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;">
              <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
              <line x1="1" y1="1" x2="23" y2="23"></line>
            </svg>
          </button>
        </div>
      </label>

      <div class="manual-field--full" style="margin-top: 8px;">
        <button class="primary-btn" type="submit" style="padding: 12px 24px; border-radius: 12px;">Update Password</button>
      </div>
    </form>
  </div>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const toggleButtons = document.querySelectorAll('.password-toggle-btn');
      toggleButtons.forEach(button => {
        button.addEventListener('click', function () {
          const input = button.previousElementSibling;
          const eyeIcon = button.querySelector('.eye-icon');
          const eyeSlashIcon = button.querySelector('.eye-slash-icon');
          
          if (input.type === 'password') {
            input.type = 'text';
            eyeIcon.style.display = 'none';
            eyeSlashIcon.style.display = 'block';
          } else {
            input.type = 'password';
            eyeIcon.style.display = 'block';
            eyeSlashIcon.style.display = 'none';
          }
        });
      });
    });
  </script>
@endsection

