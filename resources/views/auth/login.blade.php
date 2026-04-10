@extends('layouts.app')

@section('title', 'Login - Grads Paths')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/demo12.css') }}" />
@endsection

@section('content')
    <div class="app">
      <section class="login-screen">
        <div class="login-card">
          <h1>Grads Paths</h1>
          <p>Sign in to your account</p>

          <form method="POST" action="{{ route('login') }}" class="login-form">
            @csrf
            
            <div class="form-group">
              <input type="email" name="email" placeholder="Email" required value="{{ old('email') }}" />
              @error('email')
                <span class="error">{{ $message }}</span>
              @enderror
            </div>

            <div class="password-group">
              <input type="password" name="password" id="password" placeholder="Password" required />
              <label class="show-pass-inline">
                <input type="checkbox" id="showPassword" />
                <span>Show password</span>
              </label>
              @error('password')
                <span class="error">{{ $message }}</span>
              @enderror
            </div>

            <button type="submit" class="primary-btn">Sign in</button>
          </form>

          <div class="auth-links">
            <p>Don't have an account? <a href="{{ route('register') }}">Sign up</a></p>
            <p><a href="{{ route('password.request') }}">Forgot password?</a></p>
          </div>
        </div>
      </section>
    </div>
@endsection

@section('js')
<script src="{{ asset('assets/js/demo12.js') }}"></script>
<script>
  document.getElementById('showPassword')?.addEventListener('change', function() {
    const input = document.getElementById('password');
    input.type = this.checked ? 'text' : 'password';
  });
</script>
@endsection
