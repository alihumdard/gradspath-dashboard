@extends('layouts.app')

@section('title', 'Reset Password - Grads Paths')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/admin-auth.css') }}" />
@endsection

@section('content')
    <div class="app">
      <section class="login-screen">
        <div class="login-card">
          <h1>Reset Password</h1>
          <p>Enter your new password</p>

          <form method="POST" action="{{ $passwordUpdateRoute ?? route('password.update') }}" class="login-form">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">

            <div class="form-group">
              @error('email')
                <span class="error">{{ $message }}</span>
              @enderror
            </div>

            <div class="form-group">
              <div class="password-field">
                <input type="password" name="password" id="password" placeholder="New Password" required />
                <button type="button" class="password-toggle" aria-label="Show password" data-target="password">
                  <i class="fa-solid fa-eye-slash toggle-icon" aria-hidden="true"></i>
                </button>
              </div>
              @error('password')
                <span class="error">{{ $message }}</span>
              @enderror
            </div>

            <div class="form-group">
              <div class="password-field">
                <input type="password" name="password_confirmation" id="password_confirmation" placeholder="Confirm Password" required />
                <button type="button" class="password-toggle" aria-label="Show password confirmation" data-target="password_confirmation">
                  <i class="fa-solid fa-eye-slash toggle-icon" aria-hidden="true"></i>
                </button>
              </div>
              @error('password_confirmation')
                <span class="error">{{ $message }}</span>
              @enderror
            </div>

            <button type="submit" class="primary-btn">Reset Password</button>
          </form>
        </div>
      </section>
    </div>
@endsection

@section('js')
<script src="{{ asset('assets/js/admin-auth.js') }}"></script>
@endsection
