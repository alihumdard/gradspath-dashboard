@extends('layouts.app')

@section('title', 'Admin Sign In - Grads Paths')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/demo12.css') }}" />
<style>
  .login-card .message {
    margin: 0 0 16px;
    padding: 12px 14px;
    border-radius: 12px;
    border: 1px solid transparent;
    font-size: 14px;
    line-height: 1.5;
  }

  .login-card .message.error {
    background: rgba(255, 96, 120, 0.12);
    border-color: rgba(255, 96, 120, 0.3);
    color: #ffd6dd;
  }

  .login-links {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 18px;
    font-size: 14px;
    color: var(--muted);
  }

  .login-links a {
    color: #f4f5f8;
    font-weight: 600;
    text-decoration: none;
  }

  .login-links a:hover {
    text-decoration: underline;
  }
</style>
@endsection

@section('content')
  <div class="app">
    <section class="login-screen">
      <div class="login-card">
        <h1>Admin Sign In</h1>
        <p>Use your admin credentials to access the dashboard.</p>

        @if ($errors->any())
          <div class="message error">
            {{ $errors->first('email') }}
          </div>
        @endif

        <form method="POST" action="{{ route('admin.login.post') }}" class="login-form">
          @csrf

          <input
            type="email"
            name="email"
            placeholder="Email"
            required
            value="{{ old('email') }}"
          />

          <div class="password-group">
            <input
              type="password"
              id="password"
              name="password"
              placeholder="Password"
              required
            />

            <label class="show-pass-inline">
              <input type="checkbox" id="showPassword" />
              <span>Show password</span>
            </label>
          </div>

          <label class="show-pass-inline">
            <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }} />
            <span>Remember me</span>
          </label>

          <button type="submit" class="primary-btn">Sign in</button>
        </form>

        <div class="login-links">
          <a href="{{ route('login') }}">Use regular login</a>
          <a href="{{ route('password.request') }}">Forgot password?</a>
        </div>
      </div>
    </section>
  </div>
@endsection

@section('js')
<script>
  const showPassword = document.getElementById('showPassword');
  const passwordInput = document.getElementById('password');

  if (showPassword && passwordInput) {
    showPassword.addEventListener('change', function () {
      passwordInput.type = this.checked ? 'text' : 'password';
    });
  }
</script>
@endsection