@extends('layouts.app')

@section('title', 'Forgot Password - Grads Paths')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/demo12.css') }}" />
@endsection

@section('content')
    <div class="app">
      <section class="login-screen">
        <div class="login-card">
          <h1>Forgot Password?</h1>
          <p>Enter your email to receive a password reset link</p>

          <form method="POST" action="{{ route('password.email') }}" class="login-form">
            @csrf
            
            <div class="form-group">
              <input type="email" name="email" placeholder="Email" required value="{{ old('email') }}" />
              @error('email')
                <span class="error">{{ $message }}</span>
              @enderror
            </div>

            @if (session('status'))
              <div class="success-message">
                {{ session('status') }}
              </div>
            @endif

            <button type="submit" class="primary-btn">Send Reset Link</button>
          </form>

          <div class="auth-links">
            <p>Remember your password? <a href="{{ route('login') }}">Sign in</a></p>
            <p>Don't have an account? <a href="{{ route('register') }}">Sign up</a></p>
          </div>
        </div>
      </section>
    </div>
@endsection

@section('js')
<script src="{{ asset('assets/js/demo12.js') }}"></script>
@endsection
