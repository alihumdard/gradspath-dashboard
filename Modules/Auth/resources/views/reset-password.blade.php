@extends('layouts.app')

@section('title', 'Reset Password - Grads Paths')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/demo12.css') }}" />
@endsection

@section('content')
    <div class="app">
      <section class="login-screen">
        <div class="login-card">
          <h1>Reset Password</h1>
          <p>Enter your new password</p>

          <form method="POST" action="{{ route('password.update') }}" class="login-form">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            
            <div class="form-group">
              <input type="email" name="email" placeholder="Email" required value="{{ old('email') }}" />
              @error('email')
                <span class="error">{{ $message }}</span>
              @enderror
            </div>

            <div class="form-group">
              <input type="password" name="password" id="password" placeholder="New Password" required />
              @error('password')
                <span class="error">{{ $message }}</span>
              @enderror
            </div>

            <div class="form-group">
              <input type="password" name="password_confirmation" id="password_confirmation" placeholder="Confirm Password" required />
              @error('password_confirmation')
                <span class="error">{{ $message }}</span>
              @enderror
            </div>

            <button type="submit" class="primary-btn">Reset Password</button>
          </form>

          <div class="auth-links">
            <p>Remember your password? <a href="{{ route('login') }}">Sign in</a></p>
          </div>
        </div>
      </section>
    </div>
@endsection

@section('js')
<script src="{{ asset('assets/js/demo12.js') }}"></script>
@endsection
